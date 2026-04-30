<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Organization\Models\Employee;

class EmployeePayrollProfile extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'join_date' => 'date',
            'resign_date' => 'date',
            'is_taxable' => 'boolean',
            'is_bpjs_kesehatan_enrolled' => 'boolean',
            'is_bpjs_tk_enrolled' => 'boolean',
            'is_overtime_eligible' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function taxStatus(): BelongsTo
    {
        return $this->belongsTo(TaxStatus::class);
    }

    public function bpjsProfile(): BelongsTo
    {
        return $this->belongsTo(BpjsProfile::class);
    }

    public function payrollGroup(): BelongsTo
    {
        return $this->belongsTo(PayrollGroup::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(PayrollRun::class, 'employee_payroll_profile_id');
    }
}
