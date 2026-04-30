<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\AuditTrail\Services\AuditTrailService;
use Modules\Payroll\Enums\PayrollRunStatus;
use Modules\Payroll\Models\PayrollPeriod;
use Modules\Payroll\Models\PayrollRun;
use Modules\Payroll\Services\PayrollApprovalService;
use Modules\Payroll\Services\PayrollNotificationService;
use Modules\Payroll\Services\PayrollRunService;
use Modules\Payroll\Services\PayrollWorkflowService;
use Modules\Payroll\Services\PayslipService;

class PayrollWorkflowController extends Controller
{
    public function __construct(
        protected AuditTrailService $auditTrailService,
        protected PayrollApprovalService $payrollApprovalService,
        protected PayrollNotificationService $payrollNotificationService,
        protected PayrollRunService $payrollRunService,
        protected PayrollWorkflowService $payrollWorkflowService,
        protected PayslipService $payslipService,
    ) {
    }

    public function processPeriod(PayrollPeriod $period): RedirectResponse
    {
        abort_unless(auth()->user()?->isAdmin() || auth()->user()?->hasRole('Payroll Officer'), 403);

        $count = $this->payrollRunService->processPeriod($period, auth()->user());
        $this->payrollNotificationService->notifyPeriodProcessed($period->fresh(), $count, auth()->user());
        $this->auditTrailService->recordModelChange(
            module: 'payroll',
            event: 'period_processed',
            description: "Periode payroll {$period->period_name} diproses.",
            auditable: $period,
            actor: auth()->user(),
            after: $period->fresh()->toArray(),
            metadata: ['payroll_run_count' => $count],
        );

        return redirect()
            ->route('payroll.runs.index', ['period_id' => $period->id])
            ->with('status', "{$count} payroll run berhasil diproses.");
    }

    public function approve(Request $request, PayrollRun $run): RedirectResponse
    {
        abort_unless($run->calculation_status === PayrollRunStatus::Calculated, 422, 'Hanya payroll run berstatus calculated yang bisa di-approve.');

        $validated = $request->validate([
            'approval_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $run = $this->payrollApprovalService->approve($run, auth()->user(), $validated['approval_notes'] ?? null);
        $nextRole = $run->approvalSteps->firstWhere('status', 'pending')?->role_name;

        $this->auditTrailService->recordModelChange(
            module: 'payroll',
            event: 'approval_action',
            description: "Approval payroll run {$run->payroll_number} dicatat.",
            auditable: $run,
            actor: auth()->user(),
            after: $run->toArray(),
            metadata: [
                'next_role' => $nextRole,
                'approval_notes' => $validated['approval_notes'] ?? null,
            ],
        );

        if ($nextRole) {
            $this->payrollNotificationService->notifyNextApprover($run, $nextRole, auth()->user());
        } else {
            $this->payrollNotificationService->notifyRunApproved($run, auth()->user());
        }

        return back()->with('status', $nextRole
            ? "Approval dicatat. Menunggu role {$nextRole}."
            : 'Payroll run berhasil disetujui.');
    }

    public function returnToDraft(Request $request, PayrollRun $run): RedirectResponse
    {
        abort_if($run->calculation_status === PayrollRunStatus::Paid, 422, 'Payroll run yang sudah dibayar tidak bisa dikembalikan ke draft.');

        $validated = $request->validate([
            'return_reason' => ['required', 'string', 'max:1000'],
        ]);

        $run = $this->payrollApprovalService->resetToDraft($run, auth()->user(), $validated['return_reason']);
        $this->auditTrailService->recordModelChange(
            module: 'payroll',
            event: 'returned_to_draft',
            description: "Payroll run {$run->payroll_number} dikembalikan ke draft.",
            auditable: $run,
            actor: auth()->user(),
            after: $run->toArray(),
            metadata: ['return_reason' => $validated['return_reason']],
        );

        return back()->with('status', 'Payroll run dikembalikan ke draft dengan catatan revisi.');
    }

    public function markPaid(PayrollRun $run): RedirectResponse
    {
        abort_unless($run->calculation_status === PayrollRunStatus::Approved, 422, 'Hanya payroll run berstatus approved yang bisa ditandai dibayar.');
        abort_unless(auth()->user()?->isAdmin() || auth()->user()?->hasRole('Finance Approver'), 403);

        $run = $this->payrollWorkflowService->transition($run, PayrollRunStatus::Paid, auth()->user(), 'marked_paid');
        $this->payslipService->publish($run->fresh(), auth()->user());
        $this->auditTrailService->recordModelChange(
            module: 'payroll',
            event: 'marked_paid',
            description: "Payroll run {$run->payroll_number} ditandai dibayar.",
            auditable: $run,
            actor: auth()->user(),
            after: $run->toArray(),
        );

        return back()->with('status', 'Payroll run berhasil ditandai dibayar dan slip gaji diterbitkan.');
    }

    public function publishPayslip(PayrollRun $run): RedirectResponse
    {
        abort_unless(auth()->user()?->isAdmin() || auth()->user()?->hasRole('Finance Approver'), 403);

        $this->payslipService->publish($run, auth()->user());
        $this->auditTrailService->recordModelChange(
            module: 'payroll',
            event: 'payslip_published',
            description: "Slip gaji untuk payroll run {$run->payroll_number} diterbitkan.",
            auditable: $run,
            actor: auth()->user(),
            after: $run->fresh()->toArray(),
        );

        return back()->with('status', 'Slip gaji berhasil diterbitkan.');
    }
}
