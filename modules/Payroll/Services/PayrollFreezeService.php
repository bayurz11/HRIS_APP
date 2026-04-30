<?php

namespace Modules\Payroll\Services;

use Carbon\CarbonInterface;
use Modules\Attendance\Models\AttendanceRecord;
use Modules\LeaveManagement\Models\LeaveRequest;
use Modules\Organization\Models\Employee;
use Modules\Payroll\Models\PayrollPeriod;
use Modules\Payroll\Models\PayrollPeriodInput;

class PayrollFreezeService
{
    public function findLockedPeriodForAttendance(int $employeeId, CarbonInterface|string $attendanceDate): ?PayrollPeriod
    {
        $employee = Employee::query()->with('payrollProfile')->find($employeeId);

        if (! $employee?->payrollProfile?->payroll_group_id) {
            return null;
        }

        return PayrollPeriod::query()
            ->where('payroll_group_id', $employee->payrollProfile->payroll_group_id)
            ->whereDate('start_date', '<=', $attendanceDate)
            ->whereDate('end_date', '>=', $attendanceDate)
            ->with('runs')
            ->orderByDesc('start_date')
            ->get()
            ->first(fn (PayrollPeriod $period) => $period->isLocked());
    }

    public function findLockedPeriodForAttendanceRecord(AttendanceRecord $attendance): ?PayrollPeriod
    {
        return $this->findLockedPeriodForAttendance($attendance->employee_id, $attendance->attendance_date);
    }

    public function findLockedPeriodForLeave(int $employeeId, CarbonInterface|string $startDate, CarbonInterface|string $endDate): ?PayrollPeriod
    {
        $employee = Employee::query()->with('payrollProfile')->find($employeeId);

        if (! $employee?->payrollProfile?->payroll_group_id) {
            return null;
        }

        return PayrollPeriod::query()
            ->where('payroll_group_id', $employee->payrollProfile->payroll_group_id)
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate)
            ->with('runs')
            ->orderByDesc('start_date')
            ->get()
            ->first(fn (PayrollPeriod $period) => $period->isLocked());
    }

    public function findLockedPeriodForLeaveRequest(LeaveRequest $leaveRequest): ?PayrollPeriod
    {
        return $this->findLockedPeriodForLeave(
            $leaveRequest->employee_id,
            $leaveRequest->start_date,
            $leaveRequest->end_date,
        );
    }

    public function findLockedPeriodForInput(PayrollPeriodInput $input): ?PayrollPeriod
    {
        $period = $input->relationLoaded('payrollPeriod')
            ? $input->payrollPeriod
            : $input->payrollPeriod()->with('runs')->first();

        return $period?->isLocked() ? $period : null;
    }

    public function findLockedPeriodById(?int $periodId): ?PayrollPeriod
    {
        if (! $periodId) {
            return null;
        }

        $period = PayrollPeriod::query()->with('runs')->find($periodId);

        return $period?->isLocked() ? $period : null;
    }

    public function buildLockedMessage(PayrollPeriod $period, string $dataLabel): string
    {
        return "{$dataLabel} tidak dapat diubah karena periode payroll {$period->period_name} sudah terkunci setelah proses payroll berjalan.";
    }
}
