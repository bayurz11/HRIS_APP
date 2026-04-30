<?php

namespace Modules\Dashboard\Queries;

use Modules\Organization\Models\Employee;
use Modules\Organization\Models\Organization;
use Modules\Payroll\Models\PayrollPeriod;
use Modules\Payroll\Models\PayrollRun;

class DashboardOverviewQuery
{
    public function metrics(): array
    {
        return [
            [
                'label' => 'Organizations',
                'value' => Organization::query()->count(),
                'description' => 'Documented organization structures that are ready to be used.',
            ],
            [
                'label' => 'Employees',
                'value' => Employee::query()->count(),
                'description' => 'Employees already connected to the HRIS structure.',
            ],
            [
                'label' => 'Payroll Periods',
                'value' => PayrollPeriod::query()->count(),
                'description' => 'Payroll periods available for processing or audit.',
            ],
            [
                'label' => 'Pending Runs',
                'value' => PayrollRun::query()
                    ->whereNotIn('calculation_status', ['paid'])
                    ->count(),
                'description' => 'Payroll runs that have not reached paid status yet.',
            ],
        ];
    }
}
