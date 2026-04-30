<?php

namespace Modules\Payroll\DTOs;

class PayrollPeriodSnapshotData
{
    public function __construct(
        public readonly int $periodId,
        public readonly int $runCount,
        public readonly string $status,
        public readonly float $grossTotal,
        public readonly float $takeHomeTotal,
    ) {
    }

    public function toArray(): array
    {
        return [
            'period_id' => $this->periodId,
            'run_count' => $this->runCount,
            'status' => $this->status,
            'gross_total' => $this->grossTotal,
            'take_home_total' => $this->takeHomeTotal,
        ];
    }
}
