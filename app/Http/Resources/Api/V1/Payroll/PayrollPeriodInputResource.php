<?php

namespace App\Http\Resources\Api\V1\Payroll;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollPeriodInputResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payroll_period_id' => $this->payroll_period_id,
            'employee_id' => $this->employee_id,
            'payroll_component_id' => $this->payroll_component_id,
            'input_code' => $this->input_code,
            'input_name' => $this->input_name,
            'component_type' => $this->component_type,
            'quantity' => (float) $this->quantity,
            'rate' => (float) $this->rate,
            'amount' => $this->amount !== null ? (float) $this->amount : null,
            'resolved_amount' => $this->resolvedAmount(),
            'is_taxable' => (bool) $this->is_taxable,
            'is_bpjs_applicable' => (bool) $this->is_bpjs_applicable,
            'is_active' => (bool) $this->is_active,
            'notes' => $this->notes,
            'employee' => $this->whenLoaded('employee', fn () => [
                'id' => $this->employee?->id,
                'employee_number' => $this->employee?->employee_number,
                'full_name' => $this->employee?->full_name,
            ]),
            'payroll_period' => $this->whenLoaded('payrollPeriod', fn () => [
                'id' => $this->payrollPeriod?->id,
                'period_name' => $this->payrollPeriod?->period_name,
                'status' => $this->payrollPeriod?->status?->value ?? $this->payrollPeriod?->status,
            ]),
            'payroll_component' => $this->whenLoaded('payrollComponent', fn () => [
                'id' => $this->payrollComponent?->id,
                'code' => $this->payrollComponent?->code,
                'name' => $this->payrollComponent?->name,
                'category' => $this->payrollComponent?->category,
            ]),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
