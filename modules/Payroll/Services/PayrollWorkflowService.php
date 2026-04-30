<?php

namespace Modules\Payroll\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Payroll\Enums\PayrollPeriodStatus;
use Modules\Payroll\Enums\PayrollRunStatus;
use Modules\Payroll\Models\PayrollPeriod;
use Modules\Payroll\Models\PayrollRun;

class PayrollWorkflowService
{
    public function log(PayrollRun $run, ?User $actor, string $action, ?string $before, ?string $after, ?string $notes = null): void
    {
        $run->workflowLogs()->create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'status_before' => $before,
            'status_after' => $after,
            'notes' => $notes,
        ]);
    }

    public function syncPeriodStatus(PayrollPeriod $period): void
    {
        $period->loadMissing('runs');

        if ($period->runs->isEmpty()) {
            $period->update([
                'status' => PayrollPeriodStatus::Draft,
                'closed_at' => null,
            ]);

            return;
        }

        $statuses = $period->runs
            ->map(fn (PayrollRun $run) => $run->calculation_status?->value ?? (string) $run->calculation_status)
            ->values();

        if ($statuses->every(fn (string $status) => $status === PayrollRunStatus::Paid->value)) {
            $period->update([
                'status' => PayrollPeriodStatus::Paid,
                'closed_at' => now(),
            ]);

            return;
        }

        if ($statuses->every(fn (string $status) => in_array($status, [PayrollRunStatus::Approved->value, PayrollRunStatus::Paid->value], true))) {
            $period->update([
                'status' => PayrollPeriodStatus::Finalized,
                'closed_at' => null,
            ]);

            return;
        }

        $period->update([
            'status' => PayrollPeriodStatus::Processing,
            'closed_at' => null,
        ]);
    }

    public function transition(PayrollRun $run, PayrollRunStatus $targetStatus, ?User $actor, string $action, ?string $notes = null): PayrollRun
    {
        return DB::transaction(function () use ($run, $targetStatus, $actor, $action, $notes): PayrollRun {
            $before = $run->calculation_status?->value ?? (string) $run->calculation_status;

            $payload = [
                'calculation_status' => $targetStatus,
            ];

            if ($targetStatus === PayrollRunStatus::Approved) {
                $payload['approved_at'] = now();
                $payload['approved_by'] = $actor?->id;
            }

            if ($targetStatus === PayrollRunStatus::Paid) {
                $payload['paid_at'] = now();
            }

            if ($targetStatus === PayrollRunStatus::Draft) {
                $payload['approved_at'] = null;
                $payload['approved_by'] = null;
                $payload['paid_at'] = null;
            }

            $run->update($payload);

            $this->log($run->fresh(), $actor, $action, $before, $targetStatus->value, $notes);
            $this->syncPeriodStatus($run->payrollPeriod()->first());

            return $run->fresh(['employee', 'payrollPeriod', 'payslip']);
        });
    }
}
