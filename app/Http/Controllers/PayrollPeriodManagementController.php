<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\AuditTrail\Services\AuditTrailService;
use Modules\Organization\Models\Employee;
use Modules\Payroll\Enums\PayrollPeriodStatus;
use Modules\Payroll\Models\PayrollGroup;
use Modules\Payroll\Models\PayrollPeriod;
use Modules\Payroll\Services\PayrollFreezeService;

class PayrollPeriodManagementController extends Controller
{
    public function __construct(
        protected AuditTrailService $auditTrailService,
        protected PayrollFreezeService $payrollFreezeService,
    ) {
    }

    public function index()
    {
        return view('modules.payroll.periods.index', [
            'periods' => PayrollPeriod::query()
                ->with(['payrollGroup', 'runs'])
                ->withCount('runs')
                ->orderByDesc('start_date')
                ->paginate(10),
            'statuses' => PayrollPeriodStatus::cases(),
        ]);
    }

    public function create()
    {
        return view('modules.payroll.periods.create', [
            'period' => new PayrollPeriod(['status' => PayrollPeriodStatus::Draft]),
            'groups' => PayrollGroup::query()->orderBy('name')->get(),
            'statuses' => PayrollPeriodStatus::cases(),
            'candidatePreview' => $this->candidatePreview(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePeriod($request);

        $period = PayrollPeriod::query()->create($validated);
        $this->auditTrailService->recordModelChange(
            module: 'payroll_master',
            event: 'created',
            description: "Periode payroll {$period->period_name} dibuat.",
            auditable: $period,
            actor: $request->user(),
            after: $period->toArray(),
        );

        return redirect()
            ->route('payroll.periods.index')
            ->with('status', 'Periode payroll berhasil dibuat.');
    }

    public function edit(PayrollPeriod $period)
    {
        if ($period->loadMissing('runs')->isLocked()) {
            return redirect()
                ->route('payroll.periods.index')
                ->with('error', $this->payrollFreezeService->buildLockedMessage($period, 'Periode payroll'));
        }

        return view('modules.payroll.periods.edit', [
            'period' => $period,
            'groups' => PayrollGroup::query()->orderBy('name')->get(),
            'statuses' => PayrollPeriodStatus::cases(),
            'candidatePreview' => $this->candidatePreview(),
        ]);
    }

    public function update(Request $request, PayrollPeriod $period): RedirectResponse
    {
        if ($period->loadMissing('runs')->isLocked()) {
            return redirect()
                ->route('payroll.periods.index')
                ->with('error', $this->payrollFreezeService->buildLockedMessage($period, 'Periode payroll'));
        }

        $validated = $this->validatePeriod($request);

        $before = $period->toArray();
        $period->update($validated);
        $this->auditTrailService->recordModelChange(
            module: 'payroll_master',
            event: 'updated',
            description: "Periode payroll {$period->period_name} diperbarui.",
            auditable: $period,
            actor: $request->user(),
            before: $before,
            after: $period->fresh()->toArray(),
        );

        return redirect()
            ->route('payroll.periods.index')
            ->with('status', 'Periode payroll berhasil diperbarui.');
    }

    public function destroy(PayrollPeriod $period): RedirectResponse
    {
        if ($period->loadMissing('runs')->isLocked()) {
            return redirect()
                ->route('payroll.periods.index')
                ->with('error', $this->payrollFreezeService->buildLockedMessage($period, 'Periode payroll'));
        }

        if ($period->runs()->exists()) {
            return back()->with('error', 'Periode payroll tidak bisa dihapus setelah payroll run terbentuk.');
        }

        $before = $period->toArray();
        $period->delete();
        $this->auditTrailService->record(
            module: 'payroll_master',
            event: 'deleted',
            description: "Periode payroll {$before['period_name']} dihapus.",
            actor: auth()->user(),
            before: $before,
        );

        return redirect()
            ->route('payroll.periods.index')
            ->with('status', 'Periode payroll berhasil dihapus.');
    }

    protected function validatePeriod(Request $request): array
    {
        return $request->validate([
            'payroll_group_id' => ['required', 'exists:payroll_groups,id'],
            'period_name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'pay_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(array_map(fn (PayrollPeriodStatus $status) => $status->value, PayrollPeriodStatus::cases()))],
        ]);
    }

    protected function candidatePreview(): array
    {
        return Employee::query()
            ->with(['organization', 'payrollProfile.taxStatus', 'payrollProfile.payrollGroup'])
            ->where('employment_status', 'active')
            ->whereHas('payrollProfile', fn ($query) => $query->whereNotNull('payroll_group_id'))
            ->orderBy('full_name')
            ->get()
            ->groupBy(fn (Employee $employee) => (string) $employee->payrollProfile?->payroll_group_id)
            ->map(fn ($employees) => $employees
                ->map(fn (Employee $employee) => [
                    'id' => $employee->id,
                    'name' => $employee->full_name,
                    'number' => $employee->employee_number,
                    'organization' => $employee->organization?->name,
                    'basicSalary' => (float) ($employee->payrollProfile?->basic_salary ?? 0),
                    'taxStatus' => $employee->payrollProfile?->taxStatus?->code,
                    'hireDate' => optional($employee->hire_date)->toDateString(),
                    'resignDate' => optional($employee->resign_date)->toDateString(),
                ])
                ->values())
            ->all();
    }
}
