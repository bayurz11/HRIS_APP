<?php

namespace Modules\Organization\Models;

use Modules\Attendance\Models\AttendanceRecord;
use Modules\Attendance\Models\AttendanceSummary;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\LeaveManagement\Models\LeaveRequest;
use Modules\Payroll\Models\BpjsProfile;
use Modules\Payroll\Models\EmployeePayrollComponent;
use Modules\Payroll\Models\EmployeePayrollProfile;
use Modules\Payroll\Models\PayrollPeriodInput;
use Modules\Payroll\Models\PayrollRun;

class Employee extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'resign_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function payrollProfile(): HasOne
    {
        return $this->hasOne(EmployeePayrollProfile::class)->latestOfMany();
    }

    public function payrollProfiles(): HasMany
    {
        return $this->hasMany(EmployeePayrollProfile::class);
    }

    public function payrollComponents(): HasMany
    {
        return $this->hasMany(EmployeePayrollComponent::class);
    }

    public function bpjsProfile(): HasOne
    {
        return $this->hasOne(BpjsProfile::class)->latestOfMany();
    }

    public function bpjsProfiles(): HasMany
    {
        return $this->hasMany(BpjsProfile::class);
    }

    public function payrollRuns(): HasMany
    {
        return $this->hasMany(PayrollRun::class);
    }

    public function payrollPeriodInputs(): HasMany
    {
        return $this->hasMany(PayrollPeriodInput::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function attendanceSummaries(): HasMany
    {
        return $this->hasMany(AttendanceSummary::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
