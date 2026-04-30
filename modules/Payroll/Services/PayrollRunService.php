<?php

namespace Modules\Payroll\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Payroll\Enums\PayrollPeriodStatus;
use Modules\Payroll\Enums\PayrollRunStatus;
use Modules\Payroll\Models\EmployeePayrollComponent;
use Modules\Payroll\Models\EmployeePayrollProfile;
use Modules\Payroll\Models\PayrollPeriod;
use Modules\Payroll\Models\PayrollRun;

class PayrollRunService
{
    public function __construct(
        protected AttendancePayrollSyncService $attendancePayrollSyncService,
        protected BpjsCalculationService $bpjsCalculationService,
        protected PayrollApprovalService $payrollApprovalService,
        protected Pph21CalculationService $pph21CalculationService,
        protected PayrollWorkflowService $payrollWorkflowService,
        protected PayrollVariableInputService $payrollVariableInputService,
    ) {
    }

    public function processPeriod(PayrollPeriod $period, ?User $actor = null): int
    {
        if ($period->status === PayrollPeriodStatus::Cancelled) {
            abort(422, 'Cancelled payroll periods cannot be processed.');
        }

        if ($period->runs()->whereIn('calculation_status', ['approved', 'paid'])->exists()) {
            abort(422, 'Approved or paid payroll runs cannot be recalculated.');
        }

        $profiles = $this->eligibleProfiles($period);

        DB::transaction(function () use ($period, $profiles, $actor): void {
            $this->attendancePayrollSyncService->syncPeriod($period, $profiles);

            foreach ($profiles as $profile) {
                $this->upsertRunForProfile($period, $profile, $actor);
            }

            $this->payrollWorkflowService->syncPeriodStatus($period->fresh('runs'));
        });

        return $profiles->count();
    }

    protected function eligibleProfiles(PayrollPeriod $period): Collection
    {
        return EmployeePayrollProfile::query()
            ->with([
                'employee.payrollComponents.payrollComponent',
                'employee.bpjsProfile',
                'employee.organization',
                'bpjsProfile',
                'payrollGroup',
                'taxStatus',
            ])
            ->where('payroll_group_id', $period->payroll_group_id)
            ->whereHas('employee', function ($query) use ($period) {
                $query
                    ->where('employment_status', 'active')
                    ->where(function ($inner) use ($period) {
                        $inner->whereNull('hire_date')
                            ->orWhereDate('hire_date', '<=', $period->end_date);
                    })
                    ->where(function ($inner) use ($period) {
                        $inner->whereNull('resign_date')
                            ->orWhereDate('resign_date', '>=', $period->start_date);
                    });
            })
            ->get();
    }

    protected function upsertRunForProfile(PayrollPeriod $period, EmployeePayrollProfile $profile, ?User $actor): void
    {
        $employee = $profile->employee;

        $earnings = [];
        $deductions = [];
        $taxes = [];
        $employerCosts = [];

        $basicSalary = round((float) $profile->basic_salary, 2);

        $earnings[] = $this->makeItem(
            componentCode: 'BASIC',
            componentName: 'Basic Salary',
            componentType: 'earning',
            sourceType: 'system',
            quantity: 1,
            rate: $basicSalary,
            amount: $basicSalary,
            isTaxable: (bool) $profile->is_taxable,
            isBpjsApplicable: true,
        );

        foreach ($employee->payrollComponents as $employeeComponent) {
            $component = $employeeComponent->payrollComponent;

            if (! $component || ! $component->is_active || ! $employeeComponent->is_active) {
                continue;
            }

            if ($component->category === 'tax' && $component->code === 'PPH21') {
                continue;
            }

            if ($employeeComponent->effective_start_date && $employeeComponent->effective_start_date->gt($period->end_date)) {
                continue;
            }

            if ($employeeComponent->effective_end_date && $employeeComponent->effective_end_date->lt($period->start_date)) {
                continue;
            }

            $amount = $this->resolveComponentAmount($employeeComponent, $basicSalary);

            if ($amount <= 0) {
                continue;
            }

            $item = $this->makeItem(
                componentCode: $component->code,
                componentName: $component->name,
                componentType: $component->category,
                sourceType: 'manual',
                quantity: 1,
                rate: $amount,
                amount: $amount,
                isTaxable: (bool) $component->default_taxable,
                isBpjsApplicable: (bool) $component->default_bpjs_applicable,
            );

            match ($component->category) {
                'earning', 'reimbursement' => $earnings[] = $item,
                'deduction' => $deductions[] = $item,
                'tax' => $taxes[] = $item,
                'employer_cost' => $employerCosts[] = $item,
                default => $earnings[] = $item,
            };
        }

        $variableInputs = $this->payrollVariableInputService->resolve($period, $profile);
        $earnings = [...$earnings, ...$variableInputs['earnings']];
        $deductions = [...$deductions, ...$variableInputs['deductions']];
        $taxes = [...$taxes, ...$variableInputs['taxes']];
        $employerCosts = [...$employerCosts, ...$variableInputs['employer_costs']];

        $bpjsCalculation = $this->bpjsCalculationService->calculate($period, $profile, $earnings);
        $deductions = [...$deductions, ...$bpjsCalculation['employee_items']];
        $employerCosts = [...$employerCosts, ...$bpjsCalculation['employer_items']];

        $taxableGross = round(collect($earnings)->where('is_taxable', true)->sum('amount'), 2);

        $existingRun = PayrollRun::query()
            ->where('payroll_period_id', $period->id)
            ->where('employee_id', $employee->id)
            ->first();

        $pph21Result = $this->pph21CalculationService->calculate(
            $period,
            $profile,
            $taxableGross,
            $bpjsCalculation['employee_pension_amount'],
            $existingRun,
        );

        if ($pph21Result['monthly_tax_amount'] > 0) {
            $taxes[] = $this->makeItem(
                componentCode: 'PPH21',
                componentName: 'PPh 21',
                componentType: 'tax',
                sourceType: 'system',
                quantity: 1,
                rate: (float) $pph21Result['monthly_tax_amount'],
                amount: (float) $pph21Result['monthly_tax_amount'],
                isTaxable: false,
                isBpjsApplicable: false,
            );
        }

        $grossSalary = round(collect($earnings)->sum('amount'), 2);
        $totalAllowance = collect($earnings)
            ->reject(fn (array $item) => $item['component_code'] === 'BASIC')
            ->sum('amount');
        $totalDeduction = round(collect($deductions)->sum('amount'), 2);
        $totalPph21 = round(collect($taxes)->sum('amount'), 2);
        $totalBpjsEmployee = round((float) $bpjsCalculation['employee_total'], 2);
        $totalBpjsCompany = round((float) $bpjsCalculation['employer_total'], 2);
        $netSalary = round($grossSalary - $totalDeduction - $totalPph21, 2);
        $takeHomePay = $netSalary;

        $before = $existingRun?->calculation_status?->value ?? null;

        $run = PayrollRun::query()->updateOrCreate(
            [
                'payroll_period_id' => $period->id,
                'employee_id' => $employee->id,
            ],
            [
                'employee_payroll_profile_id' => $profile->id,
                'payroll_number' => sprintf('PR-%s-%s', $period->id, $employee->id),
                'basic_salary_snapshot' => $basicSalary,
                'gross_salary' => $grossSalary,
                'total_allowance' => $totalAllowance,
                'total_deduction' => $totalDeduction,
                'total_bpjs_company' => $totalBpjsCompany,
                'total_bpjs_employee' => $totalBpjsEmployee,
                'total_pph21' => $totalPph21,
                'total_overtime' => $variableInputs['totals']['overtime'],
                'total_loan_deduction' => $variableInputs['totals']['loan_deduction'],
                'total_absence_deduction' => $variableInputs['totals']['absence_deduction'],
                'net_salary' => $netSalary,
                'rounding_amount' => 0,
                'take_home_pay' => $takeHomePay,
                'calculation_status' => PayrollRunStatus::Calculated,
                'calculated_at' => now(),
            ],
        );

        $run->items()->delete();
        $run->bpjsResults()->delete();
        $run->taxResults()->delete();

        $sortOrder = 1;

        foreach ([...$earnings, ...$deductions, ...$taxes, ...$employerCosts] as $item) {
            $run->items()->create([
                ...$item,
                'sort_order' => $sortOrder++,
            ]);
        }

        foreach ($bpjsCalculation['results'] as $result) {
            $run->bpjsResults()->create($result);
        }

        $run->taxResults()->create([
            'tax_status_id' => $profile->tax_status_id,
            ...$pph21Result,
        ]);

        $run->payslip()->updateOrCreate(
            ['payroll_run_id' => $run->id],
            [
                'payslip_number' => sprintf('PS-%s-%s', $period->id, $employee->id),
                'employee_id' => $employee->id,
                'payroll_period_id' => $period->id,
                'issue_date' => $period->pay_date ?? $period->end_date,
                'is_published' => false,
            ],
        );

        $this->payrollApprovalService->syncSteps($run);

        $this->payrollWorkflowService->log(
            $run,
            $actor,
            $existingRun ? 'reprocessed' : 'processed',
            $before,
            PayrollRunStatus::Calculated->value,
        );
    }

    protected function makeItem(
        string $componentCode,
        string $componentName,
        string $componentType,
        string $sourceType,
        float $quantity,
        float $rate,
        float $amount,
        bool $isTaxable,
        bool $isBpjsApplicable,
    ): array {
        return [
            'component_code' => $componentCode,
            'component_name' => $componentName,
            'component_type' => $componentType,
            'source_type' => $sourceType,
            'notes' => null,
            'quantity' => $quantity,
            'rate' => $rate,
            'amount' => $amount,
            'is_taxable' => $isTaxable,
            'is_bpjs_applicable' => $isBpjsApplicable,
        ];
    }

    protected function resolveComponentAmount(EmployeePayrollComponent $employeeComponent, float $basicSalary): float
    {
        if ($employeeComponent->amount !== null) {
            return round((float) $employeeComponent->amount, 2);
        }

        if ($employeeComponent->percentage_value !== null) {
            return round($basicSalary * ((float) $employeeComponent->percentage_value / 100), 2);
        }

        return 0.0;
    }
}
