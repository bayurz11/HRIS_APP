<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Payroll\Models\Payslip;
use Modules\Payroll\Services\PayslipService;

class SelfServicePayslipController extends Controller
{
    public function __construct(
        protected PayslipService $payslipService,
    ) {
    }

    public function index(Request $request)
    {
        $employeeId = $request->user()->employee?->id;

        abort_unless($employeeId !== null, 403);

        $payslips = Payslip::query()
            ->with(['payrollPeriod.payrollGroup', 'payrollRun'])
            ->where('employee_id', $employeeId)
            ->where(function ($query) {
                $query->where('is_published', true)
                    ->orWhereHas('payrollRun', fn ($runQuery) => $runQuery->where('calculation_status', 'paid'));
            })
            ->latest('issue_date')
            ->paginate(10);

        return view('modules.payroll.self-service.index', [
            'payslips' => $payslips,
        ]);
    }

    public function download(Request $request, Payslip $payslip)
    {
        abort_unless($request->user()->employee?->id === $payslip->employee_id, 403);
        abort_unless($payslip->canBeDownloaded(), 422, 'Payslip must be published before download.');

        $this->payslipService->markViewed($payslip);

        return $this->payslipService->download($payslip);
    }
}
