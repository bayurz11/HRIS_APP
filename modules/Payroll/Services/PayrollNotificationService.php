<?php

namespace Modules\Payroll\Services;

use App\Models\User;
use App\Notifications\PayrollActionNotification;
use Modules\Payroll\Models\PayrollPeriod;
use Modules\Payroll\Models\PayrollRun;

class PayrollNotificationService
{
    public function notifyPeriodProcessed(PayrollPeriod $period, int $runCount, ?User $actor = null): void
    {
        $roleName = config('payroll.roles.approval_flow.0');

        if (! $roleName) {
            return;
        }

        User::role($roleName)->get()->each(function (User $user) use ($period, $runCount, $actor): void {
            if ($actor && $user->is($actor)) {
                return;
            }

            $user->notify(new PayrollActionNotification(
                title: 'Payroll siap direview',
                message: "Periode {$period->period_name} sudah diproses dengan {$runCount} payroll run dan menunggu approval.",
                url: route('payroll.runs.index', ['period_id' => $period->id]),
                metadata: [
                    'type' => 'payroll_period_processed',
                    'payroll_period_id' => $period->id,
                ],
            ));
        });
    }

    public function notifyNextApprover(PayrollRun $run, string $roleName, ?User $actor = null): void
    {
        User::role($roleName)->get()->each(function (User $user) use ($run, $roleName, $actor): void {
            if ($actor && $user->is($actor)) {
                return;
            }

            $user->notify(new PayrollActionNotification(
                title: 'Approval payroll menunggu tindakan',
                message: "Payroll run {$run->payroll_number} untuk {$run->employee?->full_name} menunggu approval role {$roleName}.",
                url: route('payroll.runs.show', $run),
                metadata: [
                    'type' => 'payroll_approval_pending',
                    'payroll_run_id' => $run->id,
                    'role_name' => $roleName,
                ],
            ));
        });
    }

    public function notifyRunApproved(PayrollRun $run, ?User $actor = null): void
    {
        User::role(['Administrator', 'Payroll Officer'])->get()->each(function (User $user) use ($run, $actor): void {
            if ($actor && $user->is($actor)) {
                return;
            }

            $user->notify(new PayrollActionNotification(
                title: 'Payroll run disetujui penuh',
                message: "Payroll run {$run->payroll_number} sudah selesai melalui seluruh approval step.",
                url: route('payroll.runs.show', $run),
                metadata: [
                    'type' => 'payroll_run_approved',
                    'payroll_run_id' => $run->id,
                ],
            ));
        });
    }

    public function notifyPayslipPublished(PayrollRun $run): void
    {
        $user = $run->employee?->user;

        if (! $user) {
            return;
        }

        $user->notify(new PayrollActionNotification(
            title: 'Slip gaji tersedia',
            message: "Slip gaji untuk periode {$run->payrollPeriod?->period_name} sudah tersedia untuk diunduh.",
            url: route('self-service.payslips.index'),
            metadata: [
                'type' => 'payslip_published',
                'payroll_run_id' => $run->id,
                'payroll_period_id' => $run->payroll_period_id,
            ],
        ));
    }
}
