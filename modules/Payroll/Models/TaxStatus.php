<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;

class TaxStatus extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'ptkp_amount_yearly' => 'decimal:2',
            'ter_category' => 'string',
            'effective_start_date' => 'date',
            'effective_end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }
}
