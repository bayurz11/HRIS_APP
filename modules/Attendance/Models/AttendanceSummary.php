<?php

namespace Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Organization\Models\Employee;
use Modules\Payroll\Models\PayrollPeriod;

class AttendanceSummary extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'total_work_days' => 'integer',
            'total_present_days' => 'integer',
            'total_absent_days' => 'decimal:2',
            'total_late_count' => 'integer',
            'total_late_minutes' => 'integer',
            'total_early_leave_minutes' => 'integer',
            'total_paid_leave_days' => 'decimal:2',
            'total_unpaid_leave_days' => 'decimal:2',
            'total_sick_days' => 'decimal:2',
            'total_permission_days' => 'decimal:2',
            'total_overtime_hours' => 'decimal:2',
            'deduction_amount' => 'decimal:2',
            'summary_snapshot_json' => 'array',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }
}
