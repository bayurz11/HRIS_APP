<?php

namespace App\Http\Resources\Api\V1\Payroll;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollRunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payroll_number' => $this->payroll_number,
            'calculation_status' => $this->calculation_status?->value ?? $this->calculation_status,
            'basic_salary_snapshot' => (float) $this->basic_salary_snapshot,
            'gross_salary' => (float) $this->gross_salary,
            'total_allowance' => (float) $this->total_allowance,
            'total_deduction' => (float) $this->total_deduction,
            'total_bpjs_company' => (float) $this->total_bpjs_company,
            'total_bpjs_employee' => (float) $this->total_bpjs_employee,
            'total_pph21' => (float) $this->total_pph21,
            'total_overtime' => (float) $this->total_overtime,
            'total_loan_deduction' => (float) $this->total_loan_deduction,
            'total_absence_deduction' => (float) $this->total_absence_deduction,
            'net_salary' => (float) $this->net_salary,
            'take_home_pay' => (float) $this->take_home_pay,
            'calculated_at' => $this->calculated_at?->toISOString(),
            'approved_at' => $this->approved_at?->toISOString(),
            'paid_at' => $this->paid_at?->toISOString(),
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
            'payroll_profile' => $this->whenLoaded('payrollProfile', fn () => $this->payrollProfile ? [
                'id' => $this->payrollProfile->id,
                'employee_code' => $this->payrollProfile->employee_code,
                'basic_salary' => (float) $this->payrollProfile->basic_salary,
            ] : null),
            'payslip' => $this->whenLoaded('payslip', fn () => $this->payslip ? [
                'id' => $this->payslip->id,
                'payslip_number' => $this->payslip->payslip_number,
                'is_published' => (bool) $this->payslip->is_published,
                'issue_date' => $this->payslip->issue_date?->toDateString(),
            ] : null),
            'approval_steps' => $this->whenLoaded('approvalSteps', fn () => $this->approvalSteps->map(fn ($step) => [
                'id' => $step->id,
                'step_order' => $step->step_order,
                'role_name' => $step->role_name,
                'status' => $step->status,
                'notes' => $step->notes,
                'acted_at' => $step->acted_at?->toISOString(),
                'actor' => $step->relationLoaded('actor') && $step->actor ? [
                    'id' => $step->actor->id,
                    'name' => $step->actor->name,
                    'email' => $step->actor->email,
                ] : null,
            ])->values()->all()),
            'workflow_logs' => $this->whenLoaded('workflowLogs', fn () => $this->workflowLogs->map(fn ($log) => [
                'id' => $log->id,
                'action' => $log->action,
                'status_before' => $log->status_before,
                'status_after' => $log->status_after,
                'notes' => $log->notes,
                'created_at' => $log->created_at?->toISOString(),
                'actor' => $log->relationLoaded('actor') && $log->actor ? [
                    'id' => $log->actor->id,
                    'name' => $log->actor->name,
                    'email' => $log->actor->email,
                ] : null,
            ])->values()->all()),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'component_code' => $item->component_code,
                'component_name' => $item->component_name,
                'component_type' => $item->component_type,
                'source_type' => $item->source_type,
                'quantity' => (float) $item->quantity,
                'rate' => (float) $item->rate,
                'amount' => (float) $item->amount,
                'notes' => $item->notes,
            ])->values()->all()),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
