<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollTaxResult extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'taxable_income_monthly' => 'decimal:2',
            'taxable_income_yearly_projection' => 'decimal:2',
            'job_expense_amount' => 'decimal:2',
            'pension_cost_amount' => 'decimal:2',
            'net_income_yearly' => 'decimal:2',
            'ptkp_amount_yearly' => 'decimal:2',
            'pkp_amount_yearly' => 'decimal:2',
            'yearly_tax_amount' => 'decimal:2',
            'monthly_tax_amount' => 'decimal:2',
            'method_snapshot_json' => 'array',
        ];
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function taxStatus(): BelongsTo
    {
        return $this->belongsTo(TaxStatus::class);
    }
}
