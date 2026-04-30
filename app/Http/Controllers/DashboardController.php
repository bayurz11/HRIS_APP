<?php

namespace App\Http\Controllers;

use Modules\Dashboard\Queries\DashboardOverviewQuery;
use Modules\Payroll\Queries\PayrollOverviewQuery;

class DashboardController extends Controller
{
    public function __invoke(DashboardOverviewQuery $dashboardOverviewQuery, PayrollOverviewQuery $payrollOverviewQuery)
    {
        return view('modules.dashboard.index', [
            'metrics' => $dashboardOverviewQuery->metrics(),
            'modulePages' => config('haris.module_pages'),
            'payrollSnapshot' => $payrollOverviewQuery->summary(),
            'recentPeriods' => $payrollOverviewQuery->recentPeriods(),
            'recentRuns' => $payrollOverviewQuery->recentRuns(),
        ]);
    }
}
