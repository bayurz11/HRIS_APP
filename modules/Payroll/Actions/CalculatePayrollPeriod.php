<?php

namespace Modules\Payroll\Actions;

use Modules\Payroll\DTOs\PayrollPeriodSnapshotData;
use Modules\Payroll\Models\PayrollPeriod;
use Modules\Payroll\Services\PayrollCalculationService;

class CalculatePayrollPeriod
{
    public function __construct(
        protected PayrollCalculationService $payrollCalculationService,
    ) {
    }

    public function execute(PayrollPeriod $period): PayrollPeriodSnapshotData
    {
        return $this->payrollCalculationService->buildSnapshot($period);
    }
}
