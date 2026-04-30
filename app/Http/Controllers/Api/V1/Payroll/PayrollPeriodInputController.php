<?php

namespace App\Http\Controllers\Api\V1\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Payroll\PayrollPeriodInputResource;
use App\Support\Http\ApiResponse;
use Illuminate\Http\Request;
use Modules\Organization\Models\Employee;
use Modules\Payroll\Models\PayrollComponent;
use Modules\Payroll\Models\PayrollPeriod;
use Modules\Payroll\Models\PayrollPeriodInput;
use Modules\Payroll\Services\PayrollFreezeService;

class PayrollPeriodInputController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PayrollFreezeService $payrollFreezeService,
    ) {
    }

    public function index(Request $request)
    {
        $periodId = $request->integer('payroll_period_id');
        $employeeId = $request->integer('employee_id');

        $inputs = PayrollPeriodInput::query()
            ->with(['employee', 'payrollPeriod', 'payrollComponent'])
            ->when($periodId, fn ($query) => $query->where('payroll_period_id', $periodId))
            ->when($employeeId, fn ($query) => $query->where('employee_id', $employeeId))
            ->latest()
            ->paginate(15);

        return $this->success(
            PayrollPeriodInputResource::collection($inputs->getCollection()),
            'Payroll period inputs retrieved successfully',
            meta: $this->paginationMeta($inputs),
        );
    }

    public function show(PayrollPeriodInput $payrollPeriodInput)
    {
        return $this->success(
            new PayrollPeriodInputResource($payrollPeriodInput->load(['employee', 'payrollPeriod', 'payrollComponent'])),
            'Payroll period input retrieved successfully',
        );
    }

    public function store(Request $request)
    {
        $validated = $this->validateInput($request);
        $component = PayrollComponent::query()->findOrFail($validated['payroll_component_id']);

        $this->assertEmployeeBelongsToPeriodGroup($validated['employee_id'], $validated['payroll_period_id']);

        if ($lockedPeriod = $this->payrollFreezeService->findLockedPeriodById($validated['payroll_period_id'])) {
            return $this->error($this->payrollFreezeService->buildLockedMessage($lockedPeriod, 'Payroll variable input'), status: 422);
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

        return $this->success(
            new PayrollPeriodInputResource($input->load(['employee', 'payrollPeriod', 'payrollComponent'])),
            'Payroll period input created successfully',
            201,
        );
    }

    public function update(Request $request, PayrollPeriodInput $payrollPeriodInput)
    {
        if ($lockedPeriod = $this->payrollFreezeService->findLockedPeriodForInput($payrollPeriodInput)) {
            return $this->error($this->payrollFreezeService->buildLockedMessage($lockedPeriod, 'Payroll variable input'), status: 422);
        }

        $validated = $this->validateInput($request);
        $component = PayrollComponent::query()->findOrFail($validated['payroll_component_id']);

        $this->assertEmployeeBelongsToPeriodGroup($validated['employee_id'], $validated['payroll_period_id']);

        if ($lockedPeriod = $this->payrollFreezeService->findLockedPeriodById($validated['payroll_period_id'])) {
            return $this->error($this->payrollFreezeService->buildLockedMessage($lockedPeriod, 'Payroll variable input'), status: 422);
        }

        $payrollPeriodInput->update([
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

        return $this->success(
            new PayrollPeriodInputResource($payrollPeriodInput->fresh()->load(['employee', 'payrollPeriod', 'payrollComponent'])),
            'Payroll period input updated successfully',
        );
    }

    public function destroy(PayrollPeriodInput $payrollPeriodInput)
    {
        if ($lockedPeriod = $this->payrollFreezeService->findLockedPeriodForInput($payrollPeriodInput)) {
            return $this->error($this->payrollFreezeService->buildLockedMessage($lockedPeriod, 'Payroll variable input'), status: 422);
        }

        $payrollPeriodInput->delete();

        return $this->success(null, 'Payroll period input deleted successfully');
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

    protected function assertEmployeeBelongsToPeriodGroup(int $employeeId, int $periodId): void
    {
        $period = PayrollPeriod::query()->findOrFail($periodId);
        $employee = Employee::query()->with('payrollProfile')->findOrFail($employeeId);

        abort_unless(
            $employee->payrollProfile?->payroll_group_id === $period->payroll_group_id,
            422,
            'Employee payroll group must match the selected payroll period group.',
        );
    }

    protected function paginationMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
