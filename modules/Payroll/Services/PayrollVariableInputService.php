<?php

namespace Modules\Payroll\Services;

use Modules\Payroll\Models\EmployeePayrollProfile;
use Modules\Payroll\Models\PayrollPeriod;
use Modules\Payroll\Models\PayrollPeriodInput;

class PayrollVariableInputService
{
    public function resolve(PayrollPeriod $period, EmployeePayrollProfile $profile): array
    {
        $inputs = PayrollPeriodInput::query()
            ->with('payrollComponent')
            ->where('payroll_period_id', $period->id)
            ->where('employee_id', $profile->employee_id)
            ->where('is_active', true)
            ->orderBy('input_name')
            ->get();

        $earnings = [];
        $deductions = [];
        $taxes = [];
        $employerCosts = [];

        foreach ($inputs as $input) {
            if ($input->payrollComponent && ! $input->payrollComponent->is_active) {
                continue;
            }

            $amount = $input->resolvedAmount();

            if ($amount <= 0) {
                continue;
            }

            $item = [
                'component_code' => $input->input_code,
                'component_name' => $input->input_name,
                'component_type' => $input->component_type,
                'source_type' => 'period_input',
                'quantity' => (float) $input->quantity,
                'rate' => $input->amount !== null ? $amount : (float) $input->rate,
                'amount' => $amount,
                'is_taxable' => (bool) $input->is_taxable,
                'is_bpjs_applicable' => (bool) $input->is_bpjs_applicable,
                'notes' => $input->notes,
            ];

            match ($input->component_type) {
                'earning', 'reimbursement' => $earnings[] = $item,
                'deduction' => $deductions[] = $item,
                'tax' => $taxes[] = $item,
                'employer_cost' => $employerCosts[] = $item,
                default => $earnings[] = $item,
            };
        }

        return [
            'earnings' => $earnings,
            'deductions' => $deductions,
            'taxes' => $taxes,
            'employer_costs' => $employerCosts,
            'totals' => [
                'overtime' => $this->sumByCode($inputs, ['OVERTIME']),
                'loan_deduction' => $this->sumByCode($inputs, ['LOAN_DED']),
                'absence_deduction' => $this->sumByCode($inputs, ['ABSENCE_DED']),
            ],
        ];
    }

    protected function sumByCode(iterable $inputs, array $codes): float
    {
        $normalized = array_map('strtoupper', $codes);

        return round(collect($inputs)
            ->filter(fn (PayrollPeriodInput $input) => in_array(strtoupper($input->input_code), $normalized, true))
            ->sum(fn (PayrollPeriodInput $input) => $input->resolvedAmount()), 2);
    }
}
