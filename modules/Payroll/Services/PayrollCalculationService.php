<?php

namespace Modules\Payroll\Services;

use Modules\Payroll\DTOs\PayrollPeriodSnapshotData;
use Modules\Payroll\Models\PayrollPeriod;

class PayrollCalculationService
{
    public function buildSnapshot(PayrollPeriod $period): PayrollPeriodSnapshotData
    {
        $period->loadMissing('runs');

        return new PayrollPeriodSnapshotData(
            periodId: $period->id,
            runCount: $period->runs->count(),
            status: is_object($period->status) ? $period->status->value : (string) $period->status,
            grossTotal: (float) $period->runs->sum('gross_salary'),
            takeHomeTotal: (float) $period->runs->sum('take_home_pay'),
        );
    }
}
