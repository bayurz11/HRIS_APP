<?php

namespace App\Http\Controllers;

use Modules\Payroll\Enums\PayrollPeriodStatus;
use Modules\Payroll\Models\PayrollGroup;
use Modules\Payroll\Models\PayrollRun;
use Modules\Payroll\Queries\PayrollOverviewQuery;

class PayrollController extends Controller
{
    public function __invoke(PayrollOverviewQuery $payrollOverviewQuery)
    {
        return view('modules.payroll.index', [
            'summary' => $payrollOverviewQuery->summary(),
            'periods' => $payrollOverviewQuery->recentPeriods(),
            'groups' => PayrollGroup::query()
                ->with('organization')
                ->withCount('periods')
                ->orderBy('name')
                ->limit(5)
                ->get(),
            'recentRuns' => PayrollRun::query()
                ->with(['employee', 'payrollPeriod'])
                ->latest()
                ->limit(5)
                ->get(),
            'statuses' => PayrollPeriodStatus::cases(),
        ]);
    }
}
