<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_number' => $this->employee_number,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'employment_status' => $this->employment_status,
            'hire_date' => $this->hire_date?->toDateString(),
            'resign_date' => $this->resign_date?->toDateString(),
            'timezone' => $this->timezone,
            'organization' => $this->whenLoaded('organization', fn () => [
                'id' => $this->organization?->id,
                'code' => $this->organization?->code,
                'name' => $this->organization?->name,
            ]),
            'user' => $this->whenLoaded('user', fn () => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'roles' => $this->user->roles->pluck('name')->values()->all(),
            ] : null),
            'payroll_profile' => $this->whenLoaded('payrollProfile', fn () => $this->payrollProfile ? [
                'id' => $this->payrollProfile->id,
                'employee_code' => $this->payrollProfile->employee_code,
                'basic_salary' => (float) $this->payrollProfile->basic_salary,
                'payment_type' => $this->payrollProfile->payment_type,
                'join_date' => $this->payrollProfile->join_date?->toDateString(),
                'resign_date' => $this->payrollProfile->resign_date?->toDateString(),
                'is_taxable' => (bool) $this->payrollProfile->is_taxable,
                'is_bpjs_kesehatan_enrolled' => (bool) $this->payrollProfile->is_bpjs_kesehatan_enrolled,
                'is_bpjs_tk_enrolled' => (bool) $this->payrollProfile->is_bpjs_tk_enrolled,
                'is_overtime_eligible' => (bool) $this->payrollProfile->is_overtime_eligible,
                'tax_status' => $this->payrollProfile->relationLoaded('taxStatus') ? [
                    'id' => $this->payrollProfile->taxStatus?->id,
                    'code' => $this->payrollProfile->taxStatus?->code,
                    'name' => $this->payrollProfile->taxStatus?->name,
                ] : null,
                'payroll_group' => $this->payrollProfile->relationLoaded('payrollGroup') ? [
                    'id' => $this->payrollProfile->payrollGroup?->id,
                    'code' => $this->payrollProfile->payrollGroup?->code,
                    'name' => $this->payrollProfile->payrollGroup?->name,
                ] : null,
            ] : null),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
