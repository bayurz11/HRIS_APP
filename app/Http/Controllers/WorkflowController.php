<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Payroll\Enums\PayrollRunStatus;
use Modules\Payroll\Models\PayrollPeriod;
use Modules\Payroll\Models\PayrollRun;
use Modules\Payroll\Models\PayrollRunWorkflowLog;

class WorkflowController extends Controller
{
    public function index(Request $request)
    {
        $periodId = $request->integer('period_id');
        $user = $request->user();

        $runs = PayrollRun::query()
            ->with([
                'employee.organization',
                'payrollPeriod.payrollGroup',
                'approvalSteps.actor',
                'workflowLogs.actor',
            ])
            ->when($periodId, fn ($query) => $query->where('payroll_period_id', $periodId))
            ->latest('updated_at')
            ->get();

        $pendingApprovals = $runs
            ->filter(function (PayrollRun $run) use ($user): bool {
                $currentStep = $run->approvalSteps->firstWhere('status', 'pending');

                return $run->calculation_status === PayrollRunStatus::Calculated
                    && $currentStep !== null
                    && $user?->canApprovePayrollStep($currentStep->role_name);
            })
            ->values();

        $returnedRuns = $runs
            ->filter(fn (PayrollRun $run): bool => $run->workflowLogs->contains('action', 'returned_to_draft'))
            ->map(function (PayrollRun $run): PayrollRun {
                $latestReturn = $run->workflowLogs->firstWhere('action', 'returned_to_draft');
                $run->setRelation('latest_return_log', $latestReturn);

                return $run;
            })
            ->sortByDesc(fn (PayrollRun $run) => optional($run->getRelation('latest_return_log'))->created_at)
            ->values();

        $paymentQueue = $runs
            ->filter(fn (PayrollRun $run): bool => $run->calculation_status === PayrollRunStatus::Approved)
            ->values();

        $recentLogs = PayrollRunWorkflowLog::query()
            ->with(['actor', 'payrollRun.employee', 'payrollRun.payrollPeriod'])
            ->when($periodId, function ($query) use ($periodId) {
                $query->whereHas('payrollRun', fn ($inner) => $inner->where('payroll_period_id', $periodId));
            })
            ->latest()
            ->limit(15)
            ->get();

        return view('modules.workflows.index', [
            'periods' => PayrollPeriod::query()->with('payrollGroup')->orderByDesc('start_date')->get(),
            'selectedPeriodId' => $periodId,
            'pendingApprovals' => $pendingApprovals,
            'returnedRuns' => $returnedRuns,
            'paymentQueue' => $paymentQueue,
            'recentLogs' => $recentLogs,
            'summary' => [
                'pending_approvals' => $pendingApprovals->count(),
                'returned_runs' => $returnedRuns->count(),
                'payment_queue' => $paymentQueue->count(),
                'recent_logs' => $recentLogs->count(),
            ],
        ]);
    }
}
