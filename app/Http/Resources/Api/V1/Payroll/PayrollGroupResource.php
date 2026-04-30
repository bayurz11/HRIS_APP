<?php

namespace App\Http\Resources\Api\V1\Payroll;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollGroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'pay_frequency' => $this->pay_frequency,
            'payroll_day' => $this->payroll_day,
            'organization' => $this->whenLoaded('organization', fn () => [
                'id' => $this->organization?->id,
                'code' => $this->organization?->code,
                'name' => $this->organization?->name,
            ]),
            'periods_count' => $this->whenCounted('periods'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
