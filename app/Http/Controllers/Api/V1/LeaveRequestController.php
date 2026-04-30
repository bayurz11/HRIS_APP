<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\LeaveRequestResource;
use App\Support\Http\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\LeaveManagement\Models\LeaveRequest;
use Modules\Payroll\Services\PayrollFreezeService;

class LeaveRequestController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PayrollFreezeService $payrollFreezeService,
    ) {
    }

    public function index(Request $request)
    {
        $employeeId = $request->integer('employee_id');
        $status = $request->string('status')->value();

        $leaveRequests = LeaveRequest::query()
            ->with(['employee', 'approver'])
            ->when($employeeId, fn ($query) => $query->where('employee_id', $employeeId))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest('start_date')
            ->paginate(15);

        return $this->success(
            LeaveRequestResource::collection($leaveRequests->getCollection()),
            'Leave requests retrieved successfully',
            meta: $this->paginationMeta($leaveRequests),
        );
    }

    public function show(LeaveRequest $leaveRequest)
    {
        return $this->success(
            new LeaveRequestResource($leaveRequest->load(['employee', 'approver'])),
            'Leave request retrieved successfully',
        );
    }

    public function store(Request $request)
    {
        $validated = $this->validateLeave($request);

        if ($lockedPeriod = $this->payrollFreezeService->findLockedPeriodForLeave($validated['employee_id'], $validated['start_date'], $validated['end_date'])) {
            return $this->error($this->payrollFreezeService->buildLockedMessage($lockedPeriod, 'Leave data'), status: 422);
        }

        $leave = LeaveRequest::query()->create([
            ...$validated,
            'approved_by' => ($validated['status'] ?? 'submitted') === 'approved' ? $request->user()?->id : null,
            'approved_at' => ($validated['status'] ?? 'submitted') === 'approved' ? now() : null,
        ]);

        return $this->success(new LeaveRequestResource($leave->load(['employee', 'approver'])), 'Leave request created successfully', 201);
    }

    public function update(Request $request, LeaveRequest $leaveRequest)
    {
        if ($lockedPeriod = $this->payrollFreezeService->findLockedPeriodForLeaveRequest($leaveRequest)) {
            return $this->error($this->payrollFreezeService->buildLockedMessage($lockedPeriod, 'Leave data'), status: 422);
        }

        $validated = $this->validateLeave($request);

        if ($lockedPeriod = $this->payrollFreezeService->findLockedPeriodForLeave($validated['employee_id'], $validated['start_date'], $validated['end_date'])) {
            return $this->error($this->payrollFreezeService->buildLockedMessage($lockedPeriod, 'Leave data'), status: 422);
        }

        $leaveRequest->update([
            ...$validated,
            'approved_by' => ($validated['status'] ?? 'submitted') === 'approved' ? $request->user()?->id : null,
            'approved_at' => ($validated['status'] ?? 'submitted') === 'approved' ? now() : null,
        ]);

        return $this->success(new LeaveRequestResource($leaveRequest->fresh()->load(['employee', 'approver'])), 'Leave request updated successfully');
    }

    public function destroy(LeaveRequest $leaveRequest)
    {
        if ($lockedPeriod = $this->payrollFreezeService->findLockedPeriodForLeaveRequest($leaveRequest)) {
            return $this->error($this->payrollFreezeService->buildLockedMessage($lockedPeriod, 'Leave data'), status: 422);
        }

        $leaveRequest->delete();

        return $this->success(null, 'Leave request deleted successfully');
    }

    protected function validateLeave(Request $request): array
    {
        return $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'leave_type' => ['required', Rule::in(['annual', 'sick', 'permission', 'unpaid', 'special'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'total_days' => ['required', 'numeric', 'min:0.5'],
            'is_paid_leave' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(['submitted', 'approved', 'rejected'])],
            'reason' => ['nullable', 'string', 'max:1000'],
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
