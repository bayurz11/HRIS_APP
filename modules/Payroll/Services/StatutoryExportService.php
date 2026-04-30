<?php

namespace Modules\Payroll\Services;

use Illuminate\Http\Response;
use Modules\Payroll\Models\PayrollPeriod;
use Modules\Payroll\Models\PayrollRun;

class StatutoryExportService
{
    public function bankTransferExport(PayrollPeriod $period): Response
    {
        $this->assertExportablePeriod($period);

        $rows = PayrollRun::query()
            ->with(['employee', 'payrollProfile', 'payrollPeriod'])
            ->where('payroll_period_id', $period->id)
            ->whereIn('calculation_status', ['approved', 'paid'])
            ->orderBy('employee_id')
            ->get()
            ->map(function (PayrollRun $run): array {
                return [
                    'payroll_number' => $run->payroll_number,
                    'period' => $run->payrollPeriod?->period_name,
                    'pay_date' => $run->payrollPeriod?->pay_date?->toDateString() ?? $run->payrollPeriod?->end_date?->toDateString(),
                    'employee_number' => $run->employee?->employee_number,
                    'employee_name' => $run->employee?->full_name,
                    'bank_name' => $run->payrollProfile?->bank_name,
                    'bank_account_number' => $run->payrollProfile?->bank_account_number,
                    'bank_account_name' => $run->payrollProfile?->bank_account_name,
                    'take_home_pay' => $this->money($run->take_home_pay),
                ];
            })
            ->all();

        return $this->csvResponse(
            "bank-transfer-period-{$period->id}.csv",
            ['payroll_number', 'period', 'pay_date', 'employee_number', 'employee_name', 'bank_name', 'bank_account_number', 'bank_account_name', 'take_home_pay'],
            $rows,
        );
    }

    public function bpjsRecapExport(PayrollPeriod $period): Response
    {
        $this->assertExportablePeriod($period);

        $runs = PayrollRun::query()
            ->with(['employee', 'bpjsResults'])
            ->where('payroll_period_id', $period->id)
            ->whereIn('calculation_status', ['approved', 'paid'])
            ->orderBy('employee_id')
            ->get();

        $rows = $runs->map(function (PayrollRun $run): array {
            $bpjs = $run->bpjsResults->keyBy('bpjs_type');

            return [
                'employee_number' => $run->employee?->employee_number,
                'employee_name' => $run->employee?->full_name,
                'salary_base_jht' => $this->money($bpjs->get('jht')?->salary_base),
                'jht_employee' => $this->money($bpjs->get('jht')?->employee_amount),
                'jht_employer' => $this->money($bpjs->get('jht')?->employer_amount),
                'jp_employee' => $this->money($bpjs->get('jp')?->employee_amount),
                'jp_employer' => $this->money($bpjs->get('jp')?->employer_amount),
                'kesehatan_employee' => $this->money($bpjs->get('kesehatan')?->employee_amount),
                'kesehatan_employer' => $this->money($bpjs->get('kesehatan')?->employer_amount),
                'jkk_employer' => $this->money($bpjs->get('jkk')?->employer_amount),
                'jkm_employer' => $this->money($bpjs->get('jkm')?->employer_amount),
                'total_employee' => $this->money($run->total_bpjs_employee),
                'total_employer' => $this->money($run->total_bpjs_company),
            ];
        })->all();

        return $this->csvResponse(
            "bpjs-recap-period-{$period->id}.csv",
            ['employee_number', 'employee_name', 'salary_base_jht', 'jht_employee', 'jht_employer', 'jp_employee', 'jp_employer', 'kesehatan_employee', 'kesehatan_employer', 'jkk_employer', 'jkm_employer', 'total_employee', 'total_employer'],
            $rows,
        );
    }

    public function pph21MonthlyExport(PayrollPeriod $period): Response
    {
        $this->assertExportablePeriod($period);

        $rows = PayrollRun::query()
            ->with(['employee', 'payrollProfile.taxStatus', 'taxResults'])
            ->where('payroll_period_id', $period->id)
            ->whereIn('calculation_status', ['approved', 'paid'])
            ->orderBy('employee_id')
            ->get()
            ->map(function (PayrollRun $run): array {
                $taxResult = $run->taxResults->first();

                return [
                    'employee_number' => $run->employee?->employee_number,
                    'employee_name' => $run->employee?->full_name,
                    'tax_status' => $run->payrollProfile?->taxStatus?->code,
                    'gross_salary' => $this->money($run->gross_salary),
                    'taxable_income_monthly' => $this->money($taxResult?->taxable_income_monthly),
                    'ptkp_yearly' => $this->money($taxResult?->ptkp_amount_yearly),
                    'pkp_yearly' => $this->money($taxResult?->pkp_amount_yearly),
                    'pph21_monthly' => $this->money($taxResult?->monthly_tax_amount),
                    'calculation_method' => $taxResult?->method_snapshot_json['method'] ?? null,
                ];
            })
            ->all();

        return $this->csvResponse(
            "pph21-monthly-period-{$period->id}.csv",
            ['employee_number', 'employee_name', 'tax_status', 'gross_salary', 'taxable_income_monthly', 'ptkp_yearly', 'pkp_yearly', 'pph21_monthly', 'calculation_method'],
            $rows,
        );
    }

    public function pph21YearlyExport(int $year): Response
    {
        $runs = PayrollRun::query()
            ->with(['employee', 'payrollProfile.taxStatus', 'taxResults', 'payrollPeriod'])
            ->whereIn('calculation_status', ['approved', 'paid'])
            ->whereHas('payrollPeriod', function ($query) use ($year) {
                $query->whereYear('start_date', $year)
                    ->whereIn('status', ['finalized', 'paid']);
            })
            ->orderBy('employee_id')
            ->get()
            ->groupBy('employee_id');

        $rows = $runs->map(function ($employeeRuns) {
            /** @var PayrollRun $firstRun */
            $firstRun = $employeeRuns->first();
            $latestTax = $employeeRuns->sortByDesc(fn (PayrollRun $run) => $run->payrollPeriod?->start_date?->timestamp ?? 0)
                ->first()
                ?->taxResults
                ?->first();

            return [
                'employee_number' => $firstRun->employee?->employee_number,
                'employee_name' => $firstRun->employee?->full_name,
                'tax_status' => $firstRun->payrollProfile?->taxStatus?->code,
                'period_count' => $employeeRuns->count(),
                'gross_salary_total' => $this->money($employeeRuns->sum(fn (PayrollRun $run) => (float) $run->gross_salary)),
                'net_salary_total' => $this->money($employeeRuns->sum(fn (PayrollRun $run) => (float) $run->net_salary)),
                'pph21_total' => $this->money($employeeRuns->sum(fn (PayrollRun $run) => (float) $run->total_pph21)),
                'pkp_yearly' => $this->money($latestTax?->pkp_amount_yearly),
                'ptkp_yearly' => $this->money($latestTax?->ptkp_amount_yearly),
            ];
        })->values()->all();

        return $this->csvResponse(
            "pph21-yearly-{$year}.csv",
            ['employee_number', 'employee_name', 'tax_status', 'period_count', 'gross_salary_total', 'net_salary_total', 'pph21_total', 'pkp_yearly', 'ptkp_yearly'],
            $rows,
        );
    }

    protected function assertExportablePeriod(PayrollPeriod $period): void
    {
        abort_unless(in_array($period->status?->value ?? (string) $period->status, ['finalized', 'paid'], true), 422, 'Only finalized or paid payroll periods can be exported.');
    }

    protected function csvResponse(string $filename, array $headers, array $rows): Response
    {
        $content = $this->toCsv($headers, $rows);

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    protected function toCsv(array $headers, array $rows): string
    {
        $lines = [
            $this->csvLine($headers),
        ];

        foreach ($rows as $row) {
            $lines[] = $this->csvLine(array_map(fn (string $header) => $row[$header] ?? '', $headers));
        }

        return implode("\n", $lines)."\n";
    }

    protected function csvLine(array $values): string
    {
        return implode(',', array_map(function ($value): string {
            $escaped = str_replace('"', '""', (string) ($value ?? ''));

            return '"'.$escaped.'"';
        }, $values));
    }

    protected function money(mixed $value): string
    {
        return number_format((float) ($value ?? 0), 2, '.', '');
    }
}
