<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Attendance\Models\AttendanceSummary;
use Modules\Payroll\Enums\PayrollPeriodStatus;
use Modules\Payroll\Enums\PayrollRunStatus;

class PayrollPeriod extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'pay_date' => 'date',
            'closed_at' => 'datetime',
            'status' => PayrollPeriodStatus::class,
        ];
    }

    public function payrollGroup(): BelongsTo
    {
        return $this->belongsTo(PayrollGroup::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(PayrollRun::class);
    }

    public function processedRuns(): HasMany
    {
        return $this->runs()->whereIn('calculation_status', ['calculated', 'approved', 'paid']);
    }

    public function inputs(): HasMany
    {
        return $this->hasMany(PayrollPeriodInput::class);
    }

    public function attendanceSummaries(): HasMany
    {
        return $this->hasMany(AttendanceSummary::class);
    }

    public function isLocked(): bool
    {
        $status = $this->status?->value ?? (string) $this->status;

        if (in_array($status, [
            PayrollPeriodStatus::Processing->value,
            PayrollPeriodStatus::Finalized->value,
            PayrollPeriodStatus::Paid->value,
        ], true)) {
            return true;
        }

        if ($this->closed_at !== null) {
            return true;
        }

        if ($this->relationLoaded('runs')) {
            return $this->runs->contains(function (PayrollRun $run): bool {
                $runStatus = $run->calculation_status?->value ?? (string) $run->calculation_status;

                return in_array($runStatus, [
                    PayrollRunStatus::Calculated->value,
                    PayrollRunStatus::Approved->value,
                    PayrollRunStatus::Paid->value,
                ], true);
            });
        }

        return $this->processedRuns()->exists();
    }
}
