<?php

namespace Modules\Payroll\Services;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Modules\Payroll\Models\PayrollRun;
use Modules\Payroll\Models\Payslip;

class PayslipService
{
    public function __construct(
        protected PayrollNotificationService $payrollNotificationService,
        protected PayrollWorkflowService $payrollWorkflowService,
    ) {
    }

    public function publish(PayrollRun $run, ?User $actor = null): Payslip
    {
        $existingPayslip = $run->payslip()->first();
        $wasPublished = (bool) $existingPayslip?->is_published;

        $payslip = $run->payslip()->updateOrCreate(
            ['payroll_run_id' => $run->id],
            [
                'payslip_number' => sprintf('PS-%s-%s', $run->payroll_period_id, $run->employee_id),
                'employee_id' => $run->employee_id,
                'payroll_period_id' => $run->payroll_period_id,
                'issue_date' => $run->payrollPeriod?->pay_date ?? $run->payrollPeriod?->end_date,
                'is_published' => true,
                'published_at' => now(),
            ],
        );

        $this->payrollWorkflowService->log(
            $run,
            $actor,
            'payslip_published',
            $run->calculation_status?->value,
            $run->calculation_status?->value,
        );

        if (! $wasPublished) {
            $this->payrollNotificationService->notifyPayslipPublished($run->fresh(['employee.user', 'payrollPeriod']));
        }

        return $payslip;
    }

    public function download(Payslip $payslip): Response
    {
        $payslip->loadMissing([
            'employee.organization',
            'payrollPeriod.payrollGroup',
            'payrollRun.items',
            'payrollRun.bpjsResults',
            'payrollRun.taxResults',
            'payrollRun.workflowLogs.actor',
        ]);

        $pdf = Pdf::loadView('modules.payroll.payslips.pdf', [
            'payslip' => $payslip,
            'run' => $payslip->payrollRun,
            'employee' => $payslip->employee,
            'period' => $payslip->payrollPeriod,
        ])->setPaper('a4');

        return $pdf->download($payslip->payslip_number.'.pdf');
    }

    public function markViewed(Payslip $payslip): void
    {
        $payslip->forceFill([
            'viewed_at' => now(),
        ])->save();
    }
}
