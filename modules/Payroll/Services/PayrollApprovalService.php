<?php

namespace Modules\Payroll\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Payroll\Enums\PayrollRunStatus;
use Modules\Payroll\Models\PayrollRun;

class PayrollApprovalService
{
    public function __construct(
        protected PayrollWorkflowService $payrollWorkflowService,
    ) {
    }

    public function syncSteps(PayrollRun $run): void
    {
        $run->approvalSteps()->delete();

        foreach (config('payroll.roles.approval_flow', []) as $index => $roleName) {
            $run->approvalSteps()->create([
                'step_order' => $index + 1,
                'role_name' => $roleName,
                'status' => 'pending',
            ]);
        }
    }

    public function approve(PayrollRun $run, User $actor, ?string $notes = null): PayrollRun
    {
        return DB::transaction(function () use ($run, $actor, $notes): PayrollRun {
            $run = $run->fresh(['approvalSteps', 'payrollPeriod']);
            $currentStep = $run->approvalSteps->firstWhere('status', 'pending');

            abort_if($currentStep === null, 422, 'This payroll run has no pending approval step.');
            abort_unless($actor->canApprovePayrollStep($currentStep->role_name), 403, 'You are not assigned to the current approval step.');

            $currentStep->update([
                'status' => 'approved',
                'acted_by' => $actor->id,
                'acted_at' => now(),
                'notes' => $notes,
            ]);

            $nextStep = $run->approvalSteps()->where('status', 'pending')->orderBy('step_order')->first();

            $this->payrollWorkflowService->log(
                $run,
                $actor,
                'approval_step_completed',
                $run->calculation_status?->value,
                $nextStep ? $run->calculation_status?->value : PayrollRunStatus::Approved->value,
                $notes
                    ? "{$currentStep->role_name}: {$notes}"
                    : $currentStep->role_name,
            );

            if ($nextStep) {
                return $run->fresh(['approvalSteps', 'payrollPeriod']);
            }

            return $this->payrollWorkflowService->transition(
                $run,
                PayrollRunStatus::Approved,
                $actor,
                'approved',
                $notes,
            )->load('approvalSteps');
        });
    }

    public function resetToDraft(PayrollRun $run, ?User $actor, string $reason): PayrollRun
    {
        return DB::transaction(function () use ($run, $actor, $reason): PayrollRun {
            $run->approvalSteps()->update([
                'status' => 'pending',
                'acted_by' => null,
                'acted_at' => null,
                'notes' => null,
            ]);

            return $this->payrollWorkflowService->transition(
                $run,
                PayrollRunStatus::Draft,
                $actor,
                'returned_to_draft',
                $reason,
            )->load('approvalSteps');
        });
    }
}
