<?php

namespace App\Http\Resources\Api\V1\Payroll;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollPeriodResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = $this->status;

        return [
            'id' => $this->id,
            'period_name' => $this->period_name,
            'status' => is_object($status) && method_exists($status, 'value') ? $status->value : $status,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'pay_date' => $this->pay_date?->toDateString(),
            'closed_at' => $this->closed_at?->toISOString(),
            'runs_count' => $this->runs_count,
            'payroll_group' => $this->whenLoaded('payrollGroup', fn () => [
                'id' => $this->payrollGroup?->id,
                'code' => $this->payrollGroup?->code,
                'name' => $this->payrollGroup?->name,
            ]),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
