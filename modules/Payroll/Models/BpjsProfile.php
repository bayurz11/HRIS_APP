<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Organization\Models\Employee;

class BpjsProfile extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'base_salary_override' => 'decimal:2',
            'is_bpjs_kesehatan_enrolled' => 'boolean',
            'is_jht_enrolled' => 'boolean',
            'is_jp_enrolled' => 'boolean',
            'is_jkk_enrolled' => 'boolean',
            'is_jkm_enrolled' => 'boolean',
            'effective_start_date' => 'date',
            'effective_end_date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
