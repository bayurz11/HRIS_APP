<?php

namespace Modules\Payroll\Services;

use Modules\Payroll\Models\BpjsRule;
use Modules\Payroll\Models\EmployeePayrollProfile;
use Modules\Payroll\Models\PayrollPeriod;

class BpjsCalculationService
{
    public function calculate(PayrollPeriod $period, EmployeePayrollProfile $profile, array $earnings): array
    {
        $bpjsProfile = $profile->bpjsProfile ?: $profile->employee?->bpjsProfile;
        $bpjsBase = $this->resolveSalaryBase($profile, $earnings, $bpjsProfile?->base_salary_override);
        $riskLevel = config('payroll.bpjs.default_company_risk_level', 'sangat_rendah');

        $employeeItems = [];
        $employerItems = [];
        $results = [];

        foreach (['kesehatan', 'jht', 'jp', 'jkk', 'jkm'] as $bpjsType) {
            if (! $this->isEnrolled($bpjsType, $profile, $bpjsProfile)) {
                continue;
            }

            $rule = $this->resolveRule($bpjsType, $period, $riskLevel);

            if (! $rule) {
                continue;
            }

            $salaryBase = $this->applyRuleBase($bpjsBase, $rule);
            $employeeAmount = round($salaryBase * (float) $rule->employee_rate, 2);
            $employerAmount = round($salaryBase * (float) $rule->employer_rate, 2);

            $results[] = [
                'bpjs_type' => $bpjsType,
                'salary_base' => $salaryBase,
                'employee_rate' => (float) $rule->employee_rate,
                'employer_rate' => (float) $rule->employer_rate,
                'employee_amount' => $employeeAmount,
                'employer_amount' => $employerAmount,
                'rule_snapshot_json' => [
                    'rule_name' => $rule->rule_name,
                    'participant_portion_type' => $rule->participant_portion_type,
                    'company_risk_level' => $rule->company_risk_level,
                    'effective_start_date' => optional($rule->effective_start_date)->toDateString(),
                ],
            ];

            if ($employeeAmount > 0) {
                $employeeItems[] = [
                    'component_code' => 'BPJS_'.strtoupper($bpjsType).'_EMP',
                    'component_name' => 'BPJS '.strtoupper($bpjsType).' Employee',
                    'component_type' => 'deduction',
                    'source_type' => 'system',
                    'quantity' => 1,
                    'rate' => $employeeAmount,
                    'amount' => $employeeAmount,
                    'is_taxable' => false,
                    'is_bpjs_applicable' => false,
                ];
            }

            if ($employerAmount > 0) {
                $employerItems[] = [
                    'component_code' => 'BPJS_'.strtoupper($bpjsType).'_COM',
                    'component_name' => 'BPJS '.strtoupper($bpjsType).' Company',
                    'component_type' => 'employer_cost',
                    'source_type' => 'system',
                    'quantity' => 1,
                    'rate' => $employerAmount,
                    'amount' => $employerAmount,
                    'is_taxable' => false,
                    'is_bpjs_applicable' => false,
                ];
            }
        }

        return [
            'employee_items' => $employeeItems,
            'employer_items' => $employerItems,
            'results' => $results,
            'employee_total' => round(collect($results)->sum('employee_amount'), 2),
            'employer_total' => round(collect($results)->sum('employer_amount'), 2),
            'employee_pension_amount' => round(collect($results)->where('bpjs_type', 'jp')->sum('employee_amount'), 2),
        ];
    }

    protected function resolveSalaryBase(EmployeePayrollProfile $profile, array $earnings, mixed $override): float
    {
        if ($override !== null) {
            return round((float) $override, 2);
        }

        $applicableEarnings = collect($earnings)
            ->filter(fn (array $item) => ($item['is_bpjs_applicable'] ?? false) === true)
            ->sum('amount');

        return round(max((float) $profile->basic_salary, (float) $applicableEarnings), 2);
    }

    protected function applyRuleBase(float $salaryBase, BpjsRule $rule): float
    {
        $base = $salaryBase;

        if ($rule->max_salary_base !== null) {
            $base = min($base, (float) $rule->max_salary_base);
        }

        if ($rule->min_salary_base !== null) {
            $base = max($base, (float) $rule->min_salary_base);
        }

        return round($base, 2);
    }

    protected function resolveRule(string $bpjsType, PayrollPeriod $period, string $riskLevel): ?BpjsRule
    {
        return BpjsRule::query()
            ->where('bpjs_type', $bpjsType)
            ->where('is_active', true)
            ->when($bpjsType === 'jkk', fn ($query) => $query->where('company_risk_level', $riskLevel))
            ->when($bpjsType !== 'jkk', fn ($query) => $query->whereNull('company_risk_level'))
            ->where(function ($query) use ($period) {
                $query->whereNull('effective_start_date')
                    ->orWhereDate('effective_start_date', '<=', $period->end_date);
            })
            ->where(function ($query) use ($period) {
                $query->whereNull('effective_end_date')
                    ->orWhereDate('effective_end_date', '>=', $period->start_date);
            })
            ->latest('effective_start_date')
            ->first();
    }

    protected function isEnrolled(string $bpjsType, EmployeePayrollProfile $profile, mixed $bpjsProfile): bool
    {
        return match ($bpjsType) {
            'kesehatan' => (bool) $profile->is_bpjs_kesehatan_enrolled
                && ($bpjsProfile?->is_bpjs_kesehatan_enrolled ?? true),
            'jht' => (bool) $profile->is_bpjs_tk_enrolled
                && ($bpjsProfile?->is_jht_enrolled ?? true),
            'jp' => (bool) $profile->is_bpjs_tk_enrolled
                && ($bpjsProfile?->is_jp_enrolled ?? true),
            'jkk' => (bool) $profile->is_bpjs_tk_enrolled
                && ($bpjsProfile?->is_jkk_enrolled ?? true),
            'jkm' => (bool) $profile->is_bpjs_tk_enrolled
                && ($bpjsProfile?->is_jkm_enrolled ?? true),
            default => false,
        };
    }
}
