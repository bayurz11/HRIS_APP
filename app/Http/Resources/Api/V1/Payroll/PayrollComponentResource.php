<?php

namespace App\Http\Resources\Api\V1\Payroll;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollComponentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'category' => $this->category,
            'calculation_method' => $this->calculation_method,
            'default_taxable' => (bool) $this->default_taxable,
            'default_bpjs_applicable' => (bool) $this->default_bpjs_applicable,
            'display_on_payslip' => (bool) $this->display_on_payslip,
            'affects_take_home_pay' => (bool) $this->affects_take_home_pay,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
