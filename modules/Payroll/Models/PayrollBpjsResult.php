<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollBpjsResult extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'salary_base' => 'decimal:2',
            'employee_rate' => 'decimal:4',
            'employer_rate' => 'decimal:4',
            'employee_amount' => 'decimal:2',
            'employer_amount' => 'decimal:2',
            'rule_snapshot_json' => 'array',
        ];
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }
}
