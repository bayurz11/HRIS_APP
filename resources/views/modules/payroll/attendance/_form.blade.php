@php $isEditing = $record->exists; @endphp

<form method="POST" action="{{ $isEditing ? route('payroll.attendance.update', $record) : route('payroll.attendance.store') }}" class="space-y-6">
    @csrf
    @if ($isEditing)
        @method('PUT')
    @endif

    <div class="rounded-2xl border border-cyan-200 bg-cyan-50/80 px-4 py-4 text-sm text-cyan-950 dark:border-cyan-500/20 dark:bg-cyan-500/10 dark:text-cyan-100">
        <p class="font-semibold">{{ __('Record daily attendance here so payroll can calculate overtime and absence deductions correctly.') }}</p>
        <p class="mt-1 text-cyan-900/80 dark:text-cyan-100/80">{{ __('Fill the minimum required data first, then add timing details only if they are available.') }}</p>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label for="employee_id" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Employee') }}</label>
            <select id="employee_id" name="employee_id" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white" required>
                <option value="">{{ __('Select employee') }}</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" @selected(old('employee_id', $record->employee_id) == $employee->id)>{{ $employee->full_name }} - {{ $employee->employee_number }}</option>
                @endforeach
            </select>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Choose the employee whose attendance is being recorded.') }}</p>
        </div>
        <div>
            <label for="attendance_date" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Attendance date') }}</label>
            <input id="attendance_date" name="attendance_date" type="date" value="{{ old('attendance_date', optional($record->attendance_date)->toDateString()) }}" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white" required>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Fill with the work date, not the date when the data is entered.') }}</p>
        </div>
        <div>
            <label for="status" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Status') }}</label>
            <select id="status" name="status" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white" required>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(old('status', $record->status) === $status)>{{ __(ucfirst($status)) }}</option>
                @endforeach
            </select>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Choose present, absent, late, or other status based on the actual attendance result.') }}</p>
        </div>
        <div>
            <label for="source" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Source') }}</label>
            <input id="source" name="source" type="text" value="{{ old('source', $record->source ?? 'manual') }}" placeholder="{{ __('manual, machine, import') }}" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Use this field to explain where the attendance data came from.') }}</p>
        </div>
        <div>
            <label for="check_in_at" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Check in') }}</label>
            <input id="check_in_at" name="check_in_at" type="datetime-local" value="{{ old('check_in_at', optional($record->check_in_at)->format('Y-m-d\\TH:i')) }}" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Fill this if the employee has a recorded check-in time.') }}</p>
        </div>
        <div>
            <label for="check_out_at" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Check out') }}</label>
            <input id="check_out_at" name="check_out_at" type="datetime-local" value="{{ old('check_out_at', optional($record->check_out_at)->format('Y-m-d\\TH:i')) }}" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Fill this if the employee has a recorded check-out time.') }}</p>
        </div>
        <div>
            <label for="worked_minutes" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Worked minutes') }}</label>
            <input id="worked_minutes" name="worked_minutes" type="number" min="0" value="{{ old('worked_minutes', $record->worked_minutes ?? 0) }}" placeholder="480" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Use total worked minutes, for example 480 for 8 working hours.') }}</p>
        </div>
        <div>
            <label for="approved_overtime_hours" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Approved overtime hours') }}</label>
            <input id="approved_overtime_hours" name="approved_overtime_hours" type="number" min="0" step="0.01" value="{{ old('approved_overtime_hours', $record->approved_overtime_hours ?? 0) }}" placeholder="2.5" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Fill only overtime hours that have been approved for payroll calculation.') }}</p>
        </div>
        <div>
            <label for="late_minutes" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Late minutes') }}</label>
            <input id="late_minutes" name="late_minutes" type="number" min="0" value="{{ old('late_minutes', $record->late_minutes ?? 0) }}" placeholder="15" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Fill with how many minutes the employee arrived late, if any.') }}</p>
        </div>
        <div>
            <label for="early_leave_minutes" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Early leave minutes') }}</label>
            <input id="early_leave_minutes" name="early_leave_minutes" type="number" min="0" value="{{ old('early_leave_minutes', $record->early_leave_minutes ?? 0) }}" placeholder="30" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Fill with how many minutes the employee left earlier than scheduled, if any.') }}</p>
        </div>
    </div>

    <div>
        <label for="notes" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Notes') }}</label>
        <textarea id="notes" name="notes" rows="4" placeholder="{{ __('Example: approved overtime after system maintenance') }}" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">{{ old('notes', $record->notes) }}</textarea>
        <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Use notes for context that helps payroll reviewers understand the attendance record.') }}</p>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="rounded-full bg-zinc-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-zinc-800 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">
            {{ $isEditing ? __('Update attendance') : __('Create attendance') }}
        </button>
        <a href="{{ route('payroll.attendance.index') }}" class="rounded-full border border-zinc-300 px-5 py-3 text-sm font-semibold text-zinc-800 transition hover:border-zinc-950 dark:border-zinc-700 dark:text-zinc-100 dark:hover:border-white">
            {{ __('Cancel') }}
        </a>
    </div>
</form>
