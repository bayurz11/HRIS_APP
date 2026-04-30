<?php

namespace Modules\Payroll\Queries;

use Modules\Payroll\Models\PayrollGroup;
use Modules\Payroll\Models\PayrollPeriod;
use Modules\Payroll\Models\PayrollRun;

class PayrollOverviewQuery
{
    public function summary(): array
    {
        return [
            'group_count' => PayrollGroup::query()->count(),
            'period_count' => PayrollPeriod::query()->count(),
            'finalized_period_count' => PayrollPeriod::query()
                ->whereIn('status', ['finalized', 'paid'])
                ->count(),
            'run_count' => PayrollRun::query()->count(),
            'draft_run_count' => PayrollRun::query()
                ->whereIn('calculation_status', ['draft', 'calculated'])
                ->count(),
        ];
    }

    public function recentPeriods()
    {
        return PayrollPeriod::query()
            ->with('payrollGroup')
            ->withCount('runs')
            ->orderByDesc('pay_date')
            ->limit(6)
            ->get();
    }

    public function recentRuns()
    {
        return PayrollRun::query()
            ->with(['employee', 'payrollPeriod'])
            ->latest()
            ->limit(6)
            ->get();
    }
}
