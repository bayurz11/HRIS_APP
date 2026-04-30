<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollComponent extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'default_taxable' => 'boolean',
            'default_bpjs_applicable' => 'boolean',
            'display_on_payslip' => 'boolean',
            'affects_take_home_pay' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function employeeComponents(): HasMany
    {
        return $this->hasMany(EmployeePayrollComponent::class);
    }
}
