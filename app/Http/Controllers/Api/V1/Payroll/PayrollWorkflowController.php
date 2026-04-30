<?php

namespace App\Http\Controllers\Api\V1\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Payroll\PayrollRunResource;
use App\Support\Http\ApiResponse;
use Illuminate\Http\Request;
use Modules\AuditTrail\Services\AuditTrailService;
use Modules\Payroll\Enums\PayrollRunStatus;
use Modules\Payroll\Models\PayrollPeriod;
use Modules\Payroll\Models\PayrollRun;
use Modules\Payroll\Models\PayrollRunWorkflowLog;
use Modules\Payroll\Services\PayrollApprovalService;
use Modules\Payroll\Services\PayrollNotificationService;
use Modules\Payroll\Services\PayrollRunService;
use Modules\Payroll\Services\PayrollWorkflowService;
use Modules\Payroll\Services\PayslipService;

class PayrollWorkflowController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AuditTrailService $auditTrailService,
        protected PayrollApprovalService $payrollApprovalService,
        protected PayrollNotificationService $payrollNotificationService,
        protected PayrollRunService $payrollRunService,
        protected PayrollWorkflowService $payrollWorkflowService,
        protected PayslipService $payslipService,
    ) {
    }

    public function index(Request $request)
    {
        $periodId = $request->integer('period_id');
        $user = $request->user();

        $runs = PayrollRun::query()
            ->with([
                'employee',
                'payrollPeriod',
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
            ->get()
            ->map(fn (PayrollRunWorkflowLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'status_before' => $log->status_before,
                'status_after' => $log->status_after,
                'notes' => $log->notes,
                'created_at' => $log->created_at?->toISOString(),
                'actor' => $log->actor ? [
                    'id' => $log->actor->id,
                    'name' => $log->actor->name,
                    'email' => $log->actor->email,
                ] : null,
                'payroll_run' => $log->payrollRun ? [
                    'id' => $log->payrollRun->id,
                    'payroll_number' => $log->payrollRun->payroll_number,
                    'employee_name' => $log->payrollRun->employee?->full_name,
                    'period_name' => $log->payrollRun->payrollPeriod?->period_name,
                ] : null,
            ])
            ->values()
            ->all();

        return $this->success([
            'summary' => [
                'pending_approvals' => $pendingApprovals->count(),
                'returned_runs' => $returnedRuns->count(),
                'payment_queue' => $paymentQueue->count(),
                'recent_logs' => count($recentLogs),
            ],
            'pending_approvals' => PayrollRunResource::collection($pendingApprovals)->resolve(),
            'returned_runs' => PayrollRunResource::collection($returnedRuns)->resolve(),
            'payment_queue' => PayrollRunResource::collection($paymentQueue)->resolve(),
            'recent_logs' => $recentLogs,
        ], 'Workflow queue retrieved successfully');
    }

    public function processPeriod(PayrollPeriod $payrollPeriod, Request $request)
    {
        $count = $this->payrollRunService->processPeriod($payrollPeriod, $request->user());
        $this->payrollNotificationService->notifyPeriodProcessed($payrollPeriod->fresh(), $count, $request->user());

        return $this->success([
            'processed_count' => $count,
            'period' => [
                'id' => $payrollPeriod->id,
                'period_name' => $payrollPeriod->period_name,
                'status' => $payrollPeriod->fresh()->status?->value ?? $payrollPeriod->fresh()->status,
            ],
        ], 'Payroll period processed successfully');
    }

    public function approve(Request $request, PayrollRun $payrollRun)
    {
        if ($payrollRun->calculation_status !== PayrollRunStatus::Calculated) {
            return $this->error('Only calculated payroll runs can be approved.', status: 422);
        }

        $validated = $request->validate([
            'approval_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $payrollRun = $this->payrollApprovalService->approve($payrollRun, $request->user(), $validated['approval_notes'] ?? null);

        return $this->success(
            new PayrollRunResource($payrollRun->load(['employee', 'payrollPeriod', 'approvalSteps.actor', 'workflowLogs.actor', 'payslip'])),
            'Payroll run approval recorded successfully',
        );
    }

    public function returnToDraft(Request $request, PayrollRun $payrollRun)
    {
        if ($payrollRun->calculation_status === PayrollRunStatus::Paid) {
            return $this->error('Paid payroll runs cannot be returned to draft.', status: 422);
        }

        $validated = $request->validate([
            'return_reason' => ['required', 'string', 'max:1000'],
        ]);

        $payrollRun = $this->payrollApprovalService->resetToDraft($payrollRun, $request->user(), $validated['return_reason']);

        return $this->success(
            new PayrollRunResource($payrollRun->load(['employee', 'payrollPeriod', 'approvalSteps.actor', 'workflowLogs.actor', 'payslip'])),
            'Payroll run returned to draft successfully',
        );
    }

    public function markPaid(Request $request, PayrollRun $payrollRun)
    {
        if ($payrollRun->calculation_status !== PayrollRunStatus::Approved) {
            return $this->error('Only approved payroll runs can be marked as paid.', status: 422);
        }

        $payrollRun = $this->payrollWorkflowService->transition($payrollRun, PayrollRunStatus::Paid, $request->user(), 'marked_paid');
        $this->payslipService->publish($payrollRun->fresh(), $request->user());

        return $this->success(
            new PayrollRunResource($payrollRun->fresh()->load(['employee', 'payrollPeriod', 'approvalSteps.actor', 'workflowLogs.actor', 'payslip'])),
            'Payroll run marked as paid successfully',
        );
    }

    public function publishPayslip(Request $request, PayrollRun $payrollRun)
    {
        $this->payslipService->publish($payrollRun, $request->user());

        return $this->success(
            new PayrollRunResource($payrollRun->fresh()->load(['employee', 'payrollPeriod', 'approvalSteps.actor', 'workflowLogs.actor', 'payslip'])),
            'Payslip published successfully',
        );
    }
}
