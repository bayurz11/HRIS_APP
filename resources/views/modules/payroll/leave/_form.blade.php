@php $isEditing = $leaveRequest->exists; @endphp

<form method="POST" action="{{ $isEditing ? route('payroll.leave.update', $leaveRequest) : route('payroll.leave.store') }}" class="space-y-6">
    @csrf
    @if ($isEditing)
        @method('PUT')
    @endif

    <div class="rounded-2xl border border-cyan-200 bg-cyan-50/80 px-4 py-4 text-sm text-cyan-950 dark:border-cyan-500/20 dark:bg-cyan-500/10 dark:text-cyan-100">
        <p class="font-semibold">{{ __('Record leave requests here so payroll can distinguish paid leave from unpaid leave correctly.') }}</p>
        <p class="mt-1 text-cyan-900/80 dark:text-cyan-100/80">{{ __('If the leave should reduce salary, make sure the paid leave option is turned off.') }}</p>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label for="employee_id" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Employee') }}</label>
            <select id="employee_id" name="employee_id" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white" required>
                <option value="">{{ __('Select employee') }}</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" @selected(old('employee_id', $leaveRequest->employee_id) == $employee->id)>{{ $employee->full_name }} - {{ $employee->employee_number }}</option>
                @endforeach
            </select>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Choose the employee who is taking leave.') }}</p>
        </div>
        <div>
            <label for="leave_type" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Leave type') }}</label>
            <select id="leave_type" name="leave_type" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white" required>
                @foreach ($types as $type)
                    <option value="{{ $type }}" @selected(old('leave_type', $leaveRequest->leave_type) === $type)>{{ __(ucfirst($type)) }}</option>
                @endforeach
            </select>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Select the leave category, such as annual leave, sick leave, or unpaid leave.') }}</p>
        </div>
        <div>
            <label for="start_date" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Start date') }}</label>
            <input id="start_date" name="start_date" type="date" value="{{ old('start_date', optional($leaveRequest->start_date)->toDateString()) }}" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white" required>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('The first day the employee is officially on leave.') }}</p>
        </div>
        <div>
            <label for="end_date" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('End date') }}</label>
            <input id="end_date" name="end_date" type="date" value="{{ old('end_date', optional($leaveRequest->end_date)->toDateString()) }}" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white" required>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('The last day included in the leave request.') }}</p>
        </div>
        <div>
            <label for="total_days" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Total days') }}</label>
            <input id="total_days" name="total_days" type="number" min="0.5" step="0.5" value="{{ old('total_days', $leaveRequest->total_days ?? 1) }}" placeholder="1" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white" required>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Use 0.5 for half day leave or whole numbers for full days.') }}</p>
        </div>
        <div>
            <label for="status" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Status') }}</label>
            <select id="status" name="status" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white" required>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(old('status', $leaveRequest->status) === $status)>{{ __(ucfirst($status)) }}</option>
                @endforeach
            </select>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Set approved only after the request has been confirmed by the responsible approver.') }}</p>
        </div>
    </div>

    <label class="flex items-center gap-3 rounded-2xl border border-zinc-200 px-4 py-3 dark:border-zinc-700">
        <input name="is_paid_leave" type="checkbox" value="1" @checked(old('is_paid_leave', $leaveRequest->is_paid_leave ?? true)) class="size-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500">
        <span class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Paid leave') }}</span>
    </label>
    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Turn this off for leave that should reduce salary, such as unpaid leave.') }}</p>

    <div>
        <label for="reason" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Reason') }}</label>
        <textarea id="reason" name="reason" rows="4" placeholder="{{ __('Example: annual leave for family event') }}" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">{{ old('reason', $leaveRequest->reason) }}</textarea>
        <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Use a short reason that can be understood by approvers and payroll staff.') }}</p>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="rounded-full bg-zinc-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-zinc-800 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">
            {{ $isEditing ? __('Update leave') : __('Create leave') }}
        </button>
        <a href="{{ route('payroll.leave.index') }}" class="rounded-full border border-zinc-300 px-5 py-3 text-sm font-semibold text-zinc-800 transition hover:border-zinc-950 dark:border-zinc-700 dark:text-zinc-100 dark:hover:border-white">
            {{ __('Cancel') }}
        </a>
    </div>
</form>
