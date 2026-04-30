<?php

namespace App\Http\Resources\Api\V1\Payroll;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayslipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payslip_number' => $this->payslip_number,
            'issue_date' => $this->issue_date?->toDateString(),
            'is_published' => (bool) $this->is_published,
            'published_at' => $this->published_at?->toISOString(),
            'viewed_at' => $this->viewed_at?->toISOString(),
            'email_sent_at' => $this->email_sent_at?->toISOString(),
            'employee' => $this->whenLoaded('employee', fn () => [
                'id' => $this->employee?->id,
                'employee_number' => $this->employee?->employee_number,
                'full_name' => $this->employee?->full_name,
            ]),
            'payroll_period' => $this->whenLoaded('payrollPeriod', fn () => [
                'id' => $this->payrollPeriod?->id,
                'period_name' => $this->payrollPeriod?->period_name,
                'start_date' => $this->payrollPeriod?->start_date?->toDateString(),
                'end_date' => $this->payrollPeriod?->end_date?->toDateString(),
            ]),
            'payroll_run' => $this->whenLoaded('payrollRun', fn () => $this->payrollRun ? [
                'id' => $this->payrollRun->id,
                'payroll_number' => $this->payrollRun->payroll_number,
                'calculation_status' => $this->payrollRun->calculation_status?->value ?? $this->payrollRun->calculation_status,
                'take_home_pay' => (float) $this->payrollRun->take_home_pay,
            ] : null),
            'can_be_downloaded' => $this->canBeDownloaded(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
