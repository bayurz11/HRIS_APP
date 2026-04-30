<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'is_active' => (bool) $this->is_active,
            'parent' => $this->whenLoaded('parent', fn () => [
                'id' => $this->parent?->id,
                'code' => $this->parent?->code,
                'name' => $this->parent?->name,
            ]),
            'employees_count' => $this->whenCounted('employees'),
            'payroll_groups_count' => $this->whenCounted('payrollGroups'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
