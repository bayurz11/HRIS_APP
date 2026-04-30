<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;

class BpjsRule extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'employee_rate' => 'decimal:4',
            'employer_rate' => 'decimal:4',
            'max_salary_base' => 'decimal:2',
            'min_salary_base' => 'decimal:2',
            'effective_start_date' => 'date',
            'effective_end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }
}
