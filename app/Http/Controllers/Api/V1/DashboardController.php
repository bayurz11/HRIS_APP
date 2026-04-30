<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Payroll\PayrollPeriodResource;
use App\Http\Resources\Api\V1\Payroll\PayrollRunResource;
use App\Support\Http\ApiResponse;
use Modules\Dashboard\Queries\DashboardOverviewQuery;
use Modules\Payroll\Queries\PayrollOverviewQuery;

class DashboardController extends Controller
{
    use ApiResponse;

    public function __invoke(DashboardOverviewQuery $dashboardOverviewQuery, PayrollOverviewQuery $payrollOverviewQuery)
    {
        return $this->success([
            'metrics' => $dashboardOverviewQuery->metrics(),
            'payroll_snapshot' => $payrollOverviewQuery->summary(),
            'recent_periods' => PayrollPeriodResource::collection($payrollOverviewQuery->recentPeriods())->resolve(),
            'recent_runs' => PayrollRunResource::collection($payrollOverviewQuery->recentRuns())->resolve(),
        ], 'Dashboard overview retrieved successfully');
    }
}
