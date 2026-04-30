<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Payroll\Models\PayrollPeriod;
use Modules\Payroll\Services\StatutoryExportService;

class PayrollExportController extends Controller
{
    public function __construct(
        protected StatutoryExportService $statutoryExportService,
    ) {
    }

    public function index(Request $request)
    {
        $selectedPeriodId = $request->integer('period_id');
        $selectedYear = $request->integer('year') ?: now()->year;

        return view('modules.payroll.exports.index', [
            'periods' => PayrollPeriod::query()
                ->with('payrollGroup')
                ->whereIn('status', ['finalized', 'paid'])
                ->orderByDesc('start_date')
                ->get(),
            'selectedPeriodId' => $selectedPeriodId,
            'selectedYear' => $selectedYear,
            'years' => PayrollPeriod::query()
                ->selectRaw('distinct YEAR(start_date) as year')
                ->whereIn('status', ['finalized', 'paid'])
                ->orderByDesc('year')
                ->pluck('year'),
        ]);
    }

    public function bankTransfer(PayrollPeriod $period)
    {
        return $this->statutoryExportService->bankTransferExport($period);
    }

    public function bpjs(PayrollPeriod $period)
    {
        return $this->statutoryExportService->bpjsRecapExport($period);
    }

    public function pph21Monthly(PayrollPeriod $period)
    {
        return $this->statutoryExportService->pph21MonthlyExport($period);
    }

    public function pph21Yearly(int $year)
    {
        return $this->statutoryExportService->pph21YearlyExport($year);
    }
}
