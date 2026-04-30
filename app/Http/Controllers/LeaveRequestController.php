<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\AuditTrail\Services\AuditTrailService;
use Modules\LeaveManagement\Models\LeaveRequest;
use Modules\Organization\Models\Employee;
use Modules\Payroll\Services\PayrollFreezeService;

class LeaveRequestController extends Controller
{
    public function __construct(
        protected AuditTrailService $auditTrailService,
        protected PayrollFreezeService $payrollFreezeService,
    ) {
    }

    public function index()
    {
        $leaveRequests = LeaveRequest::query()
            ->with(['employee.organization', 'approver'])
            ->latest('start_date')
            ->paginate(12);

        $leaveRequests->through(function (LeaveRequest $leaveRequest): LeaveRequest {
            $lockedPeriod = $this->payrollFreezeService->findLockedPeriodForLeaveRequest($leaveRequest);

            $leaveRequest->setAttribute('locked_period_name', $lockedPeriod?->period_name);

            return $leaveRequest;
        });

        return view('modules.payroll.leave.index', [
            'leaveRequests' => $leaveRequests,
            'canManage' => $this->canManage(),
        ]);
    }

    public function create()
    {
        abort_unless($this->canManage(), 403);

        return view('modules.payroll.leave.create', [
            'leaveRequest' => new LeaveRequest(['status' => 'submitted', 'is_paid_leave' => true]),
            'employees' => $this->employees(),
            'statuses' => $this->statuses(),
            'types' => $this->types(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->canManage(), 403);

        $validated = $this->validateLeave($request);
        $lockedPeriod = $this->payrollFreezeService->findLockedPeriodForLeave(
            $validated['employee_id'],
            $validated['start_date'],
            $validated['end_date'],
        );

        if ($lockedPeriod) {
            return back()
                ->withInput()
                ->with('error', $this->payrollFreezeService->buildLockedMessage($lockedPeriod, 'Data cuti'));
        }

        $leave = LeaveRequest::query()->create([
            ...$validated,
            'approved_by' => ($validated['status'] ?? 'submitted') === 'approved' ? $request->user()?->id : null,
            'approved_at' => ($validated['status'] ?? 'submitted') === 'approved' ? now() : null,
        ]);

        $this->auditTrailService->recordModelChange(
            module: 'leave',
            event: 'created',
            description: "Pengajuan cuti {$leave->leave_type} dibuat.",
            auditable: $leave,
            actor: $request->user(),
            after: $leave->toArray(),
        );

        return redirect()
            ->route('payroll.leave.index')
            ->with('status', 'Pengajuan cuti berhasil dibuat.');
    }

    public function edit(LeaveRequest $leave)
    {
        abort_unless($this->canManage(), 403);

        if ($lockedPeriod = $this->payrollFreezeService->findLockedPeriodForLeaveRequest($leave)) {
            return redirect()
                ->route('payroll.leave.index')
                ->with('error', $this->payrollFreezeService->buildLockedMessage($lockedPeriod, 'Data cuti'));
        }

        return view('modules.payroll.leave.edit', [
            'leaveRequest' => $leave,
            'employees' => $this->employees(),
            'statuses' => $this->statuses(),
            'types' => $this->types(),
        ]);
    }

    public function update(Request $request, LeaveRequest $leave): RedirectResponse
    {
        abort_unless($this->canManage(), 403);

        $validated = $this->validateLeave($request);
        if ($lockedPeriod = $this->payrollFreezeService->findLockedPeriodForLeaveRequest($leave)) {
            return redirect()
                ->route('payroll.leave.index')
                ->with('error', $this->payrollFreezeService->buildLockedMessage($lockedPeriod, 'Data cuti'));
        }

        $targetLockedPeriod = $this->payrollFreezeService->findLockedPeriodForLeave(
            $validated['employee_id'],
            $validated['start_date'],
            $validated['end_date'],
        );

        if ($targetLockedPeriod) {
            return back()
                ->withInput()
                ->with('error', $this->payrollFreezeService->buildLockedMessage($targetLockedPeriod, 'Data cuti'));
        }

        $before = $leave->toArray();
        $leave->update([
            ...$validated,
            'approved_by' => ($validated['status'] ?? 'submitted') === 'approved' ? $request->user()?->id : null,
            'approved_at' => ($validated['status'] ?? 'submitted') === 'approved' ? now() : null,
        ]);

        $this->auditTrailService->recordModelChange(
            module: 'leave',
            event: 'updated',
            description: "Pengajuan cuti {$leave->leave_type} diperbarui.",
            auditable: $leave,
            actor: $request->user(),
            before: $before,
            after: $leave->fresh()->toArray(),
        );

        return redirect()
            ->route('payroll.leave.index')
            ->with('status', 'Pengajuan cuti berhasil diperbarui.');
    }

    public function destroy(LeaveRequest $leave): RedirectResponse
    {
        abort_unless($this->canManage(), 403);

        if ($lockedPeriod = $this->payrollFreezeService->findLockedPeriodForLeaveRequest($leave)) {
            return redirect()
                ->route('payroll.leave.index')
                ->with('error', $this->payrollFreezeService->buildLockedMessage($lockedPeriod, 'Data cuti'));
        }

        $before = $leave->toArray();
        $leave->delete();
        $this->auditTrailService->record(
            module: 'leave',
            event: 'deleted',
            description: "Pengajuan cuti {$before['leave_type']} dihapus.",
            actor: auth()->user(),
            before: $before,
        );

        return redirect()
            ->route('payroll.leave.index')
            ->with('status', 'Pengajuan cuti berhasil dihapus.');
    }

    protected function validateLeave(Request $request): array
    {
        return $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'leave_type' => ['required', Rule::in($this->types())],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'total_days' => ['required', 'numeric', 'min:0.5'],
            'is_paid_leave' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in($this->statuses())],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    protected function employees()
    {
        return Employee::query()->orderBy('full_name')->get();
    }

    protected function statuses(): array
    {
        return ['submitted', 'approved', 'rejected'];
    }

    protected function types(): array
    {
        return ['annual', 'sick', 'permission', 'unpaid', 'special'];
    }

    protected function canManage(): bool
    {
        return auth()->user()?->isAdmin() || auth()->user()?->hasRole('Payroll Officer');
    }
}
