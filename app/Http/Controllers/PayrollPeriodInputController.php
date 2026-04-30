<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\AuditTrail\Services\AuditTrailService;
use Modules\Organization\Models\Employee;
use Modules\Payroll\Models\PayrollComponent;
use Modules\Payroll\Models\PayrollPeriod;
use Modules\Payroll\Models\PayrollPeriodInput;
use Modules\Payroll\Services\PayrollFreezeService;

class PayrollPeriodInputController extends Controller
{
    public function __construct(
        protected AuditTrailService $auditTrailService,
        protected PayrollFreezeService $payrollFreezeService,
    ) {
    }

    public function index(Request $request)
    {
        $selectedPeriodId = $request->integer('period_id');
        $selectedPeriod = $selectedPeriodId
            ? PayrollPeriod::query()->with('runs', 'payrollGroup')->find($selectedPeriodId)
            : null;

        return view('modules.payroll.inputs.index', [
            'inputs' => PayrollPeriodInput::query()
                ->with(['employee.organization', 'payrollPeriod.payrollGroup', 'payrollComponent'])
                ->when($selectedPeriodId, fn ($query) => $query->where('payroll_period_id', $selectedPeriodId))
                ->latest()
                ->paginate(12)
                ->withQueryString(),
            'periods' => PayrollPeriod::query()->with('payrollGroup')->orderByDesc('start_date')->get(),
            'selectedPeriodId' => $selectedPeriodId,
            'selectedPeriod' => $selectedPeriod,
            'canManage' => $this->canManage(),
        ]);
    }

    public function create(Request $request)
    {
        abort_unless($this->canManage(), 403);

        if ($lockedPeriod = $this->payrollFreezeService->findLockedPeriodById($request->integer('period_id'))) {
            return redirect()
                ->route('payroll.inputs.index', ['period_id' => $lockedPeriod->id])
                ->with('error', $this->payrollFreezeService->buildLockedMessage($lockedPeriod, 'Input payroll variabel'));
        }

        return view('modules.payroll.inputs.create', [
            'input' => new PayrollPeriodInput([
                'payroll_period_id' => $request->integer('period_id'),
                'quantity' => 1,
                'is_active' => true,
            ]),
            'periods' => PayrollPeriod::query()->with('payrollGroup')->orderByDesc('start_date')->get(),
            'employees' => $this->employeesForForm(),
            'components' => $this->variableComponents(),
            'previewPayload' => $this->previewPayload(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->canManage(), 403);

        $validated = $this->validateInput($request);
        $component = PayrollComponent::query()->findOrFail($validated['payroll_component_id']);

        $this->assertEmployeeBelongsToPeriodGroup($validated['employee_id'], $validated['payroll_period_id']);

        if ($lockedPeriod = $this->payrollFreezeService->findLockedPeriodById($validated['payroll_period_id'])) {
            return back()
                ->withInput()
                ->with('error', $this->payrollFreezeService->buildLockedMessage($lockedPeriod, 'Input payroll variabel'));
        }

        $input = PayrollPeriodInput::query()->create([
            'payroll_period_id' => $validated['payroll_period_id'],
            'employee_id' => $validated['employee_id'],
            'payroll_component_id' => $component->id,
            'input_code' => $component->code,
            'input_name' => $component->name,
            'component_type' => $component->category,
            'quantity' => $validated['quantity'] ?? 1,
            'rate' => $validated['rate'] ?? 0,
            'amount' => $validated['amount'] ?? null,
            'is_taxable' => $request->boolean('is_taxable', (bool) $component->default_taxable),
            'is_bpjs_applicable' => $request->boolean('is_bpjs_applicable', (bool) $component->default_bpjs_applicable),
            'is_active' => $request->boolean('is_active', true),
            'notes' => $validated['notes'] ?? null,
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        $this->auditTrailService->recordModelChange(
            module: 'payroll_master',
            event: 'created',
            description: "Input payroll variabel {$input->input_name} dibuat.",
            auditable: $input,
            actor: $request->user(),
            after: $input->toArray(),
        );

        return redirect()
            ->route('payroll.inputs.index', ['period_id' => $validated['payroll_period_id']])
            ->with('status', 'Input payroll variabel berhasil dibuat.');
    }

    public function edit(PayrollPeriodInput $input)
    {
        abort_unless($this->canManage(), 403);

        if ($lockedPeriod = $this->payrollFreezeService->findLockedPeriodForInput($input)) {
            return redirect()
                ->route('payroll.inputs.index', ['period_id' => $input->payroll_period_id])
                ->with('error', $this->payrollFreezeService->buildLockedMessage($lockedPeriod, 'Input payroll variabel'));
        }

        return view('modules.payroll.inputs.edit', [
            'input' => $input,
            'periods' => PayrollPeriod::query()->with('payrollGroup')->orderByDesc('start_date')->get(),
            'employees' => $this->employeesForForm(),
            'components' => $this->variableComponents(),
            'previewPayload' => $this->previewPayload(),
        ]);
    }

    public function update(Request $request, PayrollPeriodInput $input): RedirectResponse
    {
        abort_unless($this->canManage(), 403);

        $validated = $this->validateInput($request);
        $component = PayrollComponent::query()->findOrFail($validated['payroll_component_id']);

        if ($lockedPeriod = $this->payrollFreezeService->findLockedPeriodForInput($input)) {
            return redirect()
                ->route('payroll.inputs.index', ['period_id' => $input->payroll_period_id])
                ->with('error', $this->payrollFreezeService->buildLockedMessage($lockedPeriod, 'Input payroll variabel'));
        }

        $this->assertEmployeeBelongsToPeriodGroup($validated['employee_id'], $validated['payroll_period_id']);

        if ($targetLockedPeriod = $this->payrollFreezeService->findLockedPeriodById($validated['payroll_period_id'])) {
            return back()
                ->withInput()
                ->with('error', $this->payrollFreezeService->buildLockedMessage($targetLockedPeriod, 'Input payroll variabel'));
        }

        $before = $input->toArray();
        $input->update([
            'payroll_period_id' => $validated['payroll_period_id'],
            'employee_id' => $validated['employee_id'],
            'payroll_component_id' => $component->id,
            'input_code' => $component->code,
            'input_name' => $component->name,
            'component_type' => $component->category,
            'quantity' => $validated['quantity'] ?? 1,
            'rate' => $validated['rate'] ?? 0,
            'amount' => $validated['amount'] ?? null,
            'is_taxable' => $request->boolean('is_taxable', (bool) $component->default_taxable),
            'is_bpjs_applicable' => $request->boolean('is_bpjs_applicable', (bool) $component->default_bpjs_applicable),
            'is_active' => $request->boolean('is_active', true),
            'notes' => $validated['notes'] ?? null,
            'updated_by' => $request->user()?->id,
        ]);

        $this->auditTrailService->recordModelChange(
            module: 'payroll_master',
            event: 'updated',
            description: "Input payroll variabel {$input->input_name} diperbarui.",
            auditable: $input,
            actor: $request->user(),
            before: $before,
            after: $input->fresh()->toArray(),
        );

        return redirect()
            ->route('payroll.inputs.index', ['period_id' => $validated['payroll_period_id']])
            ->with('status', 'Input payroll variabel berhasil diperbarui.');
    }

    public function destroy(PayrollPeriodInput $input): RedirectResponse
    {
        abort_unless($this->canManage(), 403);

        if ($lockedPeriod = $this->payrollFreezeService->findLockedPeriodForInput($input)) {
            return redirect()
                ->route('payroll.inputs.index', ['period_id' => $input->payroll_period_id])
                ->with('error', $this->payrollFreezeService->buildLockedMessage($lockedPeriod, 'Input payroll variabel'));
        }

        $periodId = $input->payroll_period_id;
        $before = $input->toArray();
        $input->delete();
        $this->auditTrailService->record(
            module: 'payroll_master',
            event: 'deleted',
            description: "Input payroll variabel {$before['input_name']} dihapus.",
            actor: auth()->user(),
            before: $before,
        );

        return redirect()
            ->route('payroll.inputs.index', ['period_id' => $periodId])
            ->with('status', 'Input payroll variabel berhasil dihapus.');
    }

    protected function validateInput(Request $request): array
    {
        return $request->validate([
            'payroll_period_id' => ['required', 'exists:payroll_periods,id'],
            'employee_id' => ['required', 'exists:employees,id'],
            'payroll_component_id' => ['required', 'exists:payroll_components,id'],
            'quantity' => ['nullable', 'numeric', 'min:0.01'],
            'rate' => ['nullable', 'numeric', 'min:0'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_taxable' => ['nullable', 'boolean'],
            'is_bpjs_applicable' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    protected function variableComponents()
    {
        return PayrollComponent::query()
            ->where('is_active', true)
            ->whereIn('code', ['OVERTIME', 'BONUS', 'REIMB_MEDICAL', 'REIMB_OTHER', 'ABSENCE_DED', 'LOAN_DED', 'ADJUST_PLUS', 'ADJUST_MINUS'])
            ->orderBy('name')
            ->get();
    }

    protected function employeesForForm()
    {
        return Employee::query()
            ->with(['organization', 'payrollProfile.payrollGroup'])
            ->where('employment_status', 'active')
            ->orderBy('full_name')
            ->get();
    }

    protected function assertEmployeeBelongsToPeriodGroup(int $employeeId, int $periodId): void
    {
        $period = PayrollPeriod::query()->findOrFail($periodId);
        $employee = Employee::query()->with('payrollProfile')->findOrFail($employeeId);

        abort_unless(
            $employee->payrollProfile?->payroll_group_id === $period->payroll_group_id,
            422,
            'Grup payroll karyawan harus sama dengan grup pada periode payroll yang dipilih.',
        );
    }

    protected function canManage(): bool
    {
        return auth()->user()?->isAdmin() || auth()->user()?->hasRole('Payroll Officer');
    }

    protected function previewPayload(): array
    {
        return [
            'periods' => PayrollPeriod::query()
                ->with('payrollGroup')
                ->orderByDesc('start_date')
                ->get()
                ->mapWithKeys(fn (PayrollPeriod $period) => [
                    $period->id => [
                        'id' => $period->id,
                        'name' => $period->period_name,
                        'groupId' => $period->payroll_group_id,
                        'groupName' => $period->payrollGroup?->name,
                        'startDate' => optional($period->start_date)->toDateString(),
                        'endDate' => optional($period->end_date)->toDateString(),
                    ],
                ])
                ->all(),
            'employees' => $this->employeesForForm()
                ->mapWithKeys(fn (Employee $employee) => [
                    $employee->id => [
                        'id' => $employee->id,
                        'name' => $employee->full_name,
                        'number' => $employee->employee_number,
                        'organization' => $employee->organization?->name,
                        'groupId' => $employee->payrollProfile?->payroll_group_id,
                        'groupName' => $employee->payrollProfile?->payrollGroup?->name,
                        'basicSalary' => (float) ($employee->payrollProfile?->basic_salary ?? 0),
                        'isTaxable' => (bool) ($employee->payrollProfile?->is_taxable ?? false),
                    ],
                ])
                ->all(),
            'components' => $this->variableComponents()
                ->mapWithKeys(fn (PayrollComponent $component) => [
                    $component->id => [
                        'id' => $component->id,
                        'code' => $component->code,
                        'name' => $component->name,
                        'category' => $component->category,
                        'affectsTakeHomePay' => (bool) $component->affects_take_home_pay,
                        'defaultTaxable' => (bool) $component->default_taxable,
                        'defaultBpjsApplicable' => (bool) $component->default_bpjs_applicable,
                    ],
                ])
                ->all(),
        ];
    }
}
