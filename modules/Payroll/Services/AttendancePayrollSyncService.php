<?php

namespace Modules\Payroll\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Attendance\Models\AttendanceRecord;
use Modules\Attendance\Models\AttendanceSummary;
use Modules\LeaveManagement\Models\LeaveRequest;
use Modules\Payroll\Models\EmployeePayrollProfile;
use Modules\Payroll\Models\PayrollComponent;
use Modules\Payroll\Models\PayrollPeriod;
use Modules\Payroll\Models\PayrollPeriodInput;

class AttendancePayrollSyncService
{
    public function syncPeriod(PayrollPeriod $period, Collection $profiles): void
    {
        foreach ($profiles as $profile) {
            $summary = $this->syncForProfile($period, $profile);
            $this->syncGeneratedInputs($period, $profile, $summary);
        }
    }

    public function syncForProfile(PayrollPeriod $period, EmployeePayrollProfile $profile): AttendanceSummary
    {
        $employee = $profile->employee;
        $attendanceRecords = AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$period->start_date, $period->end_date])
            ->get();

        $leaveRequests = LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $period->end_date)
            ->whereDate('end_date', '>=', $period->start_date)
            ->get();

        $totalWorkDays = $this->countWeekdays($period);
        $totalPresentDays = $attendanceRecords->whereIn('status', ['present', 'late', 'remote', 'field'])->count();
        $totalLateCount = $attendanceRecords->filter(fn (AttendanceRecord $record) => $record->late_minutes > 0)->count();
        $totalLateMinutes = (int) $attendanceRecords->sum('late_minutes');
        $totalEarlyLeaveMinutes = (int) $attendanceRecords->sum('early_leave_minutes');
        $totalOvertimeHours = round((float) $attendanceRecords->sum('approved_overtime_hours'), 2);
        $explicitAbsentDays = (float) $attendanceRecords->where('status', 'absent')->count();

        $paidLeaveDays = 0.0;
        $unpaidLeaveDays = 0.0;
        $sickDays = 0.0;
        $permissionDays = 0.0;

        foreach ($leaveRequests as $leaveRequest) {
            $days = $this->overlappingWeekdays($period, $leaveRequest);
            if ($days <= 0) {
                continue;
            }

            if ($leaveRequest->is_paid_leave) {
                $paidLeaveDays += $days;
            } else {
                $unpaidLeaveDays += $days;
            }

            if ($leaveRequest->leave_type === 'sick') {
                $sickDays += $days;
            }

            if ($leaveRequest->leave_type === 'permission') {
                $permissionDays += $days;
            }
        }

        $totalAbsentDays = round($explicitAbsentDays + $unpaidLeaveDays, 2);
        $dailyRate = $totalWorkDays > 0 ? round((float) $profile->basic_salary / $totalWorkDays, 2) : 0.0;
        $deductionAmount = round($dailyRate * $totalAbsentDays, 2);

        return AttendanceSummary::query()->updateOrCreate(
            [
                'employee_id' => $employee->id,
                'payroll_period_id' => $period->id,
            ],
            [
                'total_work_days' => $totalWorkDays,
                'total_present_days' => $totalPresentDays,
                'total_absent_days' => $totalAbsentDays,
                'total_late_count' => $totalLateCount,
                'total_late_minutes' => $totalLateMinutes,
                'total_early_leave_minutes' => $totalEarlyLeaveMinutes,
                'total_paid_leave_days' => round($paidLeaveDays, 2),
                'total_unpaid_leave_days' => round($unpaidLeaveDays, 2),
                'total_sick_days' => round($sickDays, 2),
                'total_permission_days' => round($permissionDays, 2),
                'total_overtime_hours' => $totalOvertimeHours,
                'deduction_amount' => $deductionAmount,
                'summary_snapshot_json' => [
                    'explicit_absent_days' => $explicitAbsentDays,
                    'attendance_records' => $attendanceRecords->count(),
                    'leave_requests' => $leaveRequests->count(),
                    'daily_rate' => $dailyRate,
                ],
            ],
        );
    }

    protected function syncGeneratedInputs(PayrollPeriod $period, EmployeePayrollProfile $profile, AttendanceSummary $summary): void
    {
        $employeeId = $profile->employee_id;
        $hourlyRate = $profile->is_overtime_eligible ? round((float) $profile->basic_salary / 173, 2) : 0.0;
        $overtimeAmount = $profile->is_overtime_eligible
            ? round((float) $summary->total_overtime_hours * $hourlyRate * 1.5, 2)
            : 0.0;

        $overtimeComponent = PayrollComponent::query()->where('code', 'OVERTIME')->first();
        $absenceComponent = PayrollComponent::query()->where('code', 'ABSENCE_DED')->first();

        $this->upsertGeneratedInput(
            period: $period,
            employeeId: $employeeId,
            component: $overtimeComponent,
            quantity: (float) $summary->total_overtime_hours,
            rate: $hourlyRate * 1.5,
            amount: $overtimeAmount,
            isActive: $overtimeAmount > 0,
            metadata: [
                'generated_by' => 'attendance_sync',
                'attendance_summary_id' => $summary->id,
                'calculation' => 'hours * hourly_rate * 1.5',
            ],
        );

        $this->upsertGeneratedInput(
            period: $period,
            employeeId: $employeeId,
            component: $absenceComponent,
            quantity: (float) $summary->total_absent_days,
            rate: $summary->total_work_days > 0 ? round((float) $profile->basic_salary / $summary->total_work_days, 2) : 0,
            amount: (float) $summary->deduction_amount,
            isActive: (float) $summary->deduction_amount > 0,
            metadata: [
                'generated_by' => 'attendance_sync',
                'attendance_summary_id' => $summary->id,
                'calculation' => 'absence_days * daily_rate',
            ],
        );
    }

    protected function upsertGeneratedInput(
        PayrollPeriod $period,
        int $employeeId,
        ?PayrollComponent $component,
        float $quantity,
        float $rate,
        float $amount,
        bool $isActive,
        array $metadata,
    ): void {
        if (! $component) {
            return;
        }

        $existing = PayrollPeriodInput::query()
            ->where('payroll_period_id', $period->id)
            ->where('employee_id', $employeeId)
            ->where('input_code', $component->code)
            ->where('notes', 'Auto-generated from attendance and leave integration.')
            ->first();

        if (! $existing) {
            PayrollPeriodInput::query()->create([
                'payroll_period_id' => $period->id,
                'employee_id' => $employeeId,
                'payroll_component_id' => $component->id,
                'input_code' => $component->code,
                'input_name' => $component->name,
                'component_type' => $component->category,
                'quantity' => $quantity > 0 ? $quantity : 1,
                'rate' => $rate,
                'amount' => $amount,
                'is_taxable' => (bool) $component->default_taxable,
                'is_bpjs_applicable' => (bool) $component->default_bpjs_applicable,
                'is_active' => $isActive,
                'notes' => 'Auto-generated from attendance and leave integration.',
                'metadata_json' => $metadata,
            ]);

            return;
        }

        $existing->update([
            'payroll_component_id' => $component->id,
            'input_name' => $component->name,
            'component_type' => $component->category,
            'quantity' => $quantity > 0 ? $quantity : 1,
            'rate' => $rate,
            'amount' => $amount,
            'is_taxable' => (bool) $component->default_taxable,
            'is_bpjs_applicable' => (bool) $component->default_bpjs_applicable,
            'is_active' => $isActive,
            'metadata_json' => $metadata,
        ]);
    }

    protected function countWeekdays(PayrollPeriod $period): int
    {
        $days = 0;

        for ($date = $period->start_date; $date->lte($period->end_date); $date = $date->addDay()) {
            if (! $date->isWeekend()) {
                $days++;
            }
        }

        return $days;
    }

    protected function overlappingWeekdays(PayrollPeriod $period, LeaveRequest $leaveRequest): float
    {
        $start = $leaveRequest->start_date->greaterThan($period->start_date) ? $leaveRequest->start_date : $period->start_date;
        $end = $leaveRequest->end_date->lessThan($period->end_date) ? $leaveRequest->end_date : $period->end_date;

        if ($start->gt($end)) {
            return 0.0;
        }

        $days = 0.0;

        for ($date = $start; $date->lte($end); $date = $date->addDay()) {
            if (! $date->isWeekend()) {
                $days += 1;
            }
        }

        return $days;
    }
}
