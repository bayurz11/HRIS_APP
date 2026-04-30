<?php

namespace App\Http\Resources\Api\V1\Payroll;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxStatusResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'ter_category' => $this->ter_category,
            'ptkp_amount_yearly' => (float) $this->ptkp_amount_yearly,
            'description' => $this->description,
            'effective_start_date' => $this->effective_start_date?->toDateString(),
            'effective_end_date' => $this->effective_end_date?->toDateString(),
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
