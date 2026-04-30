<?php

namespace Modules\Payroll\Services;

use Illuminate\Support\Collection;
use Modules\Payroll\Models\EmployeePayrollProfile;
use Modules\Payroll\Models\PayrollPeriod;
use Modules\Payroll\Models\PayrollRun;

class Pph21CalculationService
{
    public function calculate(
        PayrollPeriod $period,
        EmployeePayrollProfile $profile,
        float $taxableGrossMonthly,
        float $employeePensionAmount,
        ?PayrollRun $existingRun = null,
    ): array {
        if (! $profile->is_taxable || $taxableGrossMonthly <= 0) {
            return $this->emptyResult($profile, $taxableGrossMonthly);
        }

        $priorRuns = $this->priorYearRuns($period, $profile, $existingRun);
        $monthsProcessed = $priorRuns->count() + 1;
        $priorTaxableGross = round($priorRuns->sum(fn (PayrollRun $run) => (float) ($run->taxResults->first()?->taxable_income_monthly ?? $run->gross_salary)), 2);
        $priorWithheld = round($priorRuns->sum(fn (PayrollRun $run) => (float) ($run->taxResults->first()?->monthly_tax_amount ?? 0)), 2);
        $priorPension = round($priorRuns->sum(
            fn (PayrollRun $run) => (float) $run->bpjsResults->where('bpjs_type', 'jp')->sum('employee_amount')
        ), 2);

        if ($this->isFinalPeriod($period, $profile)) {
            return $this->annualReconciliation(
                $profile,
                $taxableGrossMonthly,
                $employeePensionAmount,
                $priorTaxableGross,
                $priorWithheld,
                $priorPension,
                $monthsProcessed,
            );
        }

        return $this->monthlyTer($profile, $taxableGrossMonthly);
    }

    protected function monthlyTer(EmployeePayrollProfile $profile, float $taxableGrossMonthly): array
    {
        $category = $this->resolveTerCategory($profile);
        $rate = $this->resolveTerRate($category, $taxableGrossMonthly);
        $monthlyTax = round($taxableGrossMonthly * $rate, 2);
        $jobExpense = $this->jobExpenseProjected($taxableGrossMonthly * 12, 12);
        $netIncomeYearly = max(($taxableGrossMonthly * 12) - $jobExpense, 0);
        $ptkp = (float) ($profile->taxStatus?->ptkp_amount_yearly ?? 0);
        $pkp = $this->roundDownToThousands(max($netIncomeYearly - $ptkp, 0));

        return [
            'taxable_income_monthly' => round($taxableGrossMonthly, 2),
            'taxable_income_yearly_projection' => round($taxableGrossMonthly * 12, 2),
            'job_expense_amount' => round($jobExpense, 2),
            'pension_cost_amount' => 0,
            'net_income_yearly' => round($netIncomeYearly, 2),
            'ptkp_amount_yearly' => $ptkp,
            'pkp_amount_yearly' => $pkp,
            'yearly_tax_amount' => round($monthlyTax * 12, 2),
            'monthly_tax_amount' => $monthlyTax,
            'method_snapshot_json' => [
                'method' => 'ter_monthly',
                'ter_category' => $category,
                'ter_rate' => $rate,
            ],
        ];
    }

    protected function annualReconciliation(
        EmployeePayrollProfile $profile,
        float $taxableGrossMonthly,
        float $employeePensionAmount,
        float $priorTaxableGross,
        float $priorWithheld,
        float $priorPension,
        int $monthsProcessed,
    ): array {
        $grossYearly = round($priorTaxableGross + $taxableGrossMonthly, 2);
        $pensionCost = round($priorPension + $employeePensionAmount, 2);
        $jobExpense = $this->jobExpenseProjected($grossYearly, $monthsProcessed);
        $netIncomeYearly = max($grossYearly - $jobExpense - $pensionCost, 0);
        $ptkp = (float) ($profile->taxStatus?->ptkp_amount_yearly ?? 0);
        $pkp = $this->roundDownToThousands(max($netIncomeYearly - $ptkp, 0));
        $yearlyTax = round($this->progressiveTax($pkp), 2);
        $monthlyTax = round(max($yearlyTax - $priorWithheld, 0), 2);

        return [
            'taxable_income_monthly' => round($taxableGrossMonthly, 2),
            'taxable_income_yearly_projection' => $grossYearly,
            'job_expense_amount' => round($jobExpense, 2),
            'pension_cost_amount' => $pensionCost,
            'net_income_yearly' => round($netIncomeYearly, 2),
            'ptkp_amount_yearly' => $ptkp,
            'pkp_amount_yearly' => $pkp,
            'yearly_tax_amount' => $yearlyTax,
            'monthly_tax_amount' => $monthlyTax,
            'method_snapshot_json' => [
                'method' => 'annual_reconciliation',
                'ter_category' => $this->resolveTerCategory($profile),
                'months_processed' => $monthsProcessed,
                'prior_withheld' => $priorWithheld,
            ],
        ];
    }

    protected function priorYearRuns(PayrollPeriod $period, EmployeePayrollProfile $profile, ?PayrollRun $existingRun): Collection
    {
        return PayrollRun::query()
            ->with(['taxResults', 'bpjsResults', 'payrollPeriod'])
            ->where('employee_id', $profile->employee_id)
            ->whereKeyNot($existingRun?->id)
            ->whereIn('calculation_status', ['calculated', 'approved', 'paid'])
            ->whereHas('payrollPeriod', function ($query) use ($period) {
                $query->whereYear('start_date', $period->start_date->year)
                    ->whereDate('start_date', '<', $period->start_date);
            })
            ->get();
    }

    protected function isFinalPeriod(PayrollPeriod $period, EmployeePayrollProfile $profile): bool
    {
        $resignDate = $profile->resign_date ?: $profile->employee?->resign_date;

        return $period->end_date->month === 12
            || ($resignDate !== null && $resignDate->between($period->start_date, $period->end_date));
    }

    protected function resolveTerCategory(EmployeePayrollProfile $profile): string
    {
        return strtoupper((string) ($profile->taxStatus?->ter_category ?: match ($profile->taxStatus?->code) {
            'TK2', 'TK3', 'K1', 'K2' => 'B',
            'K3', 'KI0', 'KI1', 'KI2', 'KI3' => 'C',
            default => 'A',
        }));
    }

    protected function resolveTerRate(string $category, float $taxableGrossMonthly): float
    {
        foreach (config("payroll.pph21.ter_categories.{$category}", []) as $bracket) {
            if ($bracket['up_to'] === null || $taxableGrossMonthly <= $bracket['up_to']) {
                return (float) $bracket['rate'];
            }
        }

        return 0.0;
    }

    protected function progressiveTax(float $pkp): float
    {
        $remaining = $pkp;
        $previousCap = 0;
        $tax = 0.0;

        foreach (config('payroll.pph21.progressive_brackets', []) as $bracket) {
            if ($remaining <= 0) {
                break;
            }

            $cap = $bracket['up_to'];
            $segment = $cap === null
                ? $remaining
                : min($remaining, $cap - $previousCap);

            $tax += $segment * (float) $bracket['rate'];
            $remaining -= $segment;

            if ($cap !== null) {
                $previousCap = $cap;
            }
        }

        return $tax;
    }

    protected function jobExpenseProjected(float $grossAmount, int $monthsProcessed): float
    {
        $rate = (float) config('payroll.pph21.job_expense_rate', 0.05);
        $monthlyCap = (float) config('payroll.pph21.job_expense_cap_monthly', 500000);
        $yearlyCap = (float) config('payroll.pph21.job_expense_cap_yearly', 6000000);

        return min($grossAmount * $rate, $monthlyCap * $monthsProcessed, $yearlyCap);
    }

    protected function roundDownToThousands(float $amount): float
    {
        return floor($amount / 1000) * 1000;
    }

    protected function emptyResult(EmployeePayrollProfile $profile, float $taxableGrossMonthly): array
    {
        return [
            'taxable_income_monthly' => round($taxableGrossMonthly, 2),
            'taxable_income_yearly_projection' => round($taxableGrossMonthly * 12, 2),
            'job_expense_amount' => 0,
            'pension_cost_amount' => 0,
            'net_income_yearly' => round($taxableGrossMonthly * 12, 2),
            'ptkp_amount_yearly' => (float) ($profile->taxStatus?->ptkp_amount_yearly ?? 0),
            'pkp_amount_yearly' => 0,
            'yearly_tax_amount' => 0,
            'monthly_tax_amount' => 0,
            'method_snapshot_json' => [
                'method' => 'non_taxable',
            ],
        ];
    }
}
