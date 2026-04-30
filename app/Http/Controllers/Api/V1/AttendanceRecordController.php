<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AttendanceRecordResource;
use App\Support\Http\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Attendance\Models\AttendanceRecord;
use Modules\Payroll\Services\PayrollFreezeService;

class AttendanceRecordController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PayrollFreezeService $payrollFreezeService,
    ) {
    }

    public function index(Request $request)
    {
        $employeeId = $request->integer('employee_id');

        $records = AttendanceRecord::query()
            ->with('employee')
            ->when($employeeId, fn ($query) => $query->where('employee_id', $employeeId))
            ->orderByDesc('attendance_date')
            ->paginate(15);

        return $this->success(
            AttendanceRecordResource::collection($records->getCollection()),
            'Attendance records retrieved successfully',
            meta: $this->paginationMeta($records),
        );
    }

    public function show(AttendanceRecord $attendanceRecord)
    {
        return $this->success(
            new AttendanceRecordResource($attendanceRecord->load('employee')),
            'Attendance record retrieved successfully',
        );
    }

    public function store(Request $request)
    {
        $validated = $this->validateRecord($request);

        if ($lockedPeriod = $this->payrollFreezeService->findLockedPeriodForAttendance($validated['employee_id'], $validated['attendance_date'])) {
            return $this->error($this->payrollFreezeService->buildLockedMessage($lockedPeriod, 'Attendance data'), status: 422);
        }

        $record = AttendanceRecord::query()->create([
            ...$validated,
            'created_by' => $request->user()?->id,
        ]);

        return $this->success(new AttendanceRecordResource($record->load('employee')), 'Attendance record created successfully', 201);
    }

    public function update(Request $request, AttendanceRecord $attendanceRecord)
    {
        if ($lockedPeriod = $this->payrollFreezeService->findLockedPeriodForAttendanceRecord($attendanceRecord)) {
            return $this->error($this->payrollFreezeService->buildLockedMessage($lockedPeriod, 'Attendance data'), status: 422);
        }

        $validated = $this->validateRecord($request, $attendanceRecord);

        if ($lockedPeriod = $this->payrollFreezeService->findLockedPeriodForAttendance($validated['employee_id'], $validated['attendance_date'])) {
            return $this->error($this->payrollFreezeService->buildLockedMessage($lockedPeriod, 'Attendance data'), status: 422);
        }

        $attendanceRecord->update($validated);

        return $this->success(new AttendanceRecordResource($attendanceRecord->fresh('employee')), 'Attendance record updated successfully');
    }

    public function destroy(AttendanceRecord $attendanceRecord)
    {
        if ($lockedPeriod = $this->payrollFreezeService->findLockedPeriodForAttendanceRecord($attendanceRecord)) {
            return $this->error($this->payrollFreezeService->buildLockedMessage($lockedPeriod, 'Attendance data'), status: 422);
        }

        $attendanceRecord->delete();

        return $this->success(null, 'Attendance record deleted successfully');
    }

    protected function validateRecord(Request $request, ?AttendanceRecord $attendanceRecord = null): array
    {
        return $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'attendance_date' => [
                'required',
                'date',
                Rule::unique('attendance_records', 'attendance_date')
                    ->where(fn ($query) => $query->where('employee_id', $request->integer('employee_id')))
                    ->ignore($attendanceRecord?->id),
            ],
            'status' => ['required', Rule::in(['present', 'late', 'absent', 'remote', 'field'])],
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

    protected function paginationMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
