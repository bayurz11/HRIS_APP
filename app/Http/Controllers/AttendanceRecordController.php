<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Attendance\Models\AttendanceRecord;
use Modules\AuditTrail\Services\AuditTrailService;
use Modules\Organization\Models\Employee;
use Modules\Payroll\Services\PayrollFreezeService;

class AttendanceRecordController extends Controller
{
    public function __construct(
        protected AuditTrailService $auditTrailService,
        protected PayrollFreezeService $payrollFreezeService,
    ) {
    }

    public function index()
    {
        $records = AttendanceRecord::query()
            ->with(['employee.organization'])
            ->orderByDesc('attendance_date')
            ->paginate(12);

        $records->through(function (AttendanceRecord $record): AttendanceRecord {
            $lockedPeriod = $this->payrollFreezeService->findLockedPeriodForAttendanceRecord($record);

            $record->setAttribute('locked_period_name', $lockedPeriod?->period_name);

            return $record;
        });

        return view('modules.payroll.attendance.index', [
            'records' => $records,
            'canManage' => $this->canManage(),
        ]);
    }

    public function create()
    {
        abort_unless($this->canManage(), 403);

        return view('modules.payroll.attendance.create', [
            'record' => new AttendanceRecord(['status' => 'present']),
            'employees' => $this->employees(),
            'statuses' => $this->statuses(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->canManage(), 403);

        $validated = $this->validateRecord($request);
        $lockedPeriod = $this->payrollFreezeService->findLockedPeriodForAttendance(
            $validated['employee_id'],
            $validated['attendance_date'],
        );

        if ($lockedPeriod) {
            return back()
                ->withInput()
                ->with('error', $this->payrollFreezeService->buildLockedMessage($lockedPeriod, 'Data absensi'));
        }

        $record = AttendanceRecord::query()->create([
            ...$validated,
            'created_by' => $request->user()?->id,
        ]);

        $this->auditTrailService->recordModelChange(
            module: 'attendance',
            event: 'created',
            description: "Data absensi {$record->attendance_date?->toDateString()} dibuat.",
            auditable: $record,
            actor: $request->user(),
            after: $record->toArray(),
        );

        return redirect()
            ->route('payroll.attendance.index')
            ->with('status', 'Data absensi berhasil dibuat.');
    }

    public function edit(AttendanceRecord $attendance)
    {
        abort_unless($this->canManage(), 403);

        if ($lockedPeriod = $this->payrollFreezeService->findLockedPeriodForAttendanceRecord($attendance)) {
            return redirect()
                ->route('payroll.attendance.index')
                ->with('error', $this->payrollFreezeService->buildLockedMessage($lockedPeriod, 'Data absensi'));
        }

        return view('modules.payroll.attendance.edit', [
            'record' => $attendance,
            'employees' => $this->employees(),
            'statuses' => $this->statuses(),
        ]);
    }

    public function update(Request $request, AttendanceRecord $attendance): RedirectResponse
    {
        abort_unless($this->canManage(), 403);

        if ($lockedPeriod = $this->payrollFreezeService->findLockedPeriodForAttendanceRecord($attendance)) {
            return redirect()
                ->route('payroll.attendance.index')
                ->with('error', $this->payrollFreezeService->buildLockedMessage($lockedPeriod, 'Data absensi'));
        }

        $validated = $this->validateRecord($request, $attendance);
        $targetLockedPeriod = $this->payrollFreezeService->findLockedPeriodForAttendance(
            $validated['employee_id'],
            $validated['attendance_date'],
        );

        if ($targetLockedPeriod) {
            return back()
                ->withInput()
                ->with('error', $this->payrollFreezeService->buildLockedMessage($targetLockedPeriod, 'Data absensi'));
        }

        $before = $attendance->toArray();
        $attendance->update($validated);
        $this->auditTrailService->recordModelChange(
            module: 'attendance',
            event: 'updated',
            description: "Data absensi {$attendance->attendance_date?->toDateString()} diperbarui.",
            auditable: $attendance,
            actor: $request->user(),
            before: $before,
            after: $attendance->fresh()->toArray(),
        );

        return redirect()
            ->route('payroll.attendance.index')
            ->with('status', 'Data absensi berhasil diperbarui.');
    }

    public function destroy(AttendanceRecord $attendance): RedirectResponse
    {
        abort_unless($this->canManage(), 403);

        if ($lockedPeriod = $this->payrollFreezeService->findLockedPeriodForAttendanceRecord($attendance)) {
            return redirect()
                ->route('payroll.attendance.index')
                ->with('error', $this->payrollFreezeService->buildLockedMessage($lockedPeriod, 'Data absensi'));
        }

        $before = $attendance->toArray();
        $attendance->delete();
        $this->auditTrailService->record(
            module: 'attendance',
            event: 'deleted',
            description: "Data absensi {$before['attendance_date']} dihapus.",
            actor: auth()->user(),
            before: $before,
        );

        return redirect()
            ->route('payroll.attendance.index')
            ->with('status', 'Data absensi berhasil dihapus.');
    }

    protected function validateRecord(Request $request, ?AttendanceRecord $attendance = null): array
    {
        return $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'attendance_date' => [
                'required',
                'date',
                Rule::unique('attendance_records', 'attendance_date')
                    ->where(fn ($query) => $query->where('employee_id', $request->integer('employee_id')))
                    ->ignore($attendance?->id),
            ],
            'status' => ['required', Rule::in($this->statuses())],
            'check_in_at' => ['nullable', 'date'],
            'check_out_at' => ['nullable', 'date', 'after_or_equal:check_in_at'],
            'worked_minutes' => ['nullable', 'integer', 'min:0'],
            'late_minutes' => ['nullable', 'integer', 'min:0'],
            'early_leave_minutes' => ['nullable', 'integer', 'min:0'],
            'approved_overtime_hours' => ['nullable', 'numeric', 'min:0'],
            'source' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    protected function employees()
    {
        return Employee::query()->orderBy('full_name')->get();
    }

    protected function statuses(): array
    {
        return ['present', 'late', 'absent', 'remote', 'field'];
    }

    protected function canManage(): bool
    {
        return auth()->user()?->isAdmin() || auth()->user()?->hasRole('Payroll Officer');
    }
}
