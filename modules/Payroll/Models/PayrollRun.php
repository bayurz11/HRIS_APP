<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Organization\Models\Employee;
use Modules\Payroll\Enums\PayrollRunStatus;

class PayrollRun extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'basic_salary_snapshot' => 'decimal:2',
            'gross_salary' => 'decimal:2',
            'total_allowance' => 'decimal:2',
            'total_deduction' => 'decimal:2',
            'total_bpjs_company' => 'decimal:2',
            'total_bpjs_employee' => 'decimal:2',
            'total_pph21' => 'decimal:2',
            'total_overtime' => 'decimal:2',
            'total_loan_deduction' => 'decimal:2',
            'total_absence_deduction' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'rounding_amount' => 'decimal:2',
            'take_home_pay' => 'decimal:2',
            'calculated_at' => 'datetime',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
            'calculation_status' => PayrollRunStatus::class,
        ];
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payrollProfile(): BelongsTo
    {
        return $this->belongsTo(EmployeePayrollProfile::class, 'employee_payroll_profile_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollRunItem::class);
    }

    public function bpjsResults(): HasMany
    {
        return $this->hasMany(PayrollBpjsResult::class);
    }

    public function taxResults(): HasMany
    {
        return $this->hasMany(PayrollTaxResult::class);
    }

    public function payslip(): HasOne
    {
        return $this->hasOne(Payslip::class);
    }

    public function workflowLogs(): HasMany
    {
        return $this->hasMany(PayrollRunWorkflowLog::class)->latest();
    }

    public function approvalSteps(): HasMany
    {
        return $this->hasMany(PayrollRunApprovalStep::class)->orderBy('step_order');
    }
}
