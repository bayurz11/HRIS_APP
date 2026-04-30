<x-layouts::app :title="__('Attendance')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <section class="rounded-3xl border border-zinc-200/70 bg-white p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-cyan-600 dark:text-cyan-300">{{ __('Attendance') }}</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight text-zinc-950 dark:text-white">{{ __('Attendance records for payroll') }}</h1>
                    <p class="mt-3 max-w-2xl text-sm text-zinc-600 dark:text-zinc-300">{{ __('This data becomes the automatic source for attendance summaries, overtime, and payroll deductions.') }}</p>
                </div>

                @if ($canManage)
                    <a href="{{ route('payroll.attendance.create') }}" class="rounded-full bg-zinc-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-zinc-800 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">
                        {{ __('New attendance') }}
                    </a>
                @endif
            </div>
        </section>

        <section class="overflow-hidden rounded-3xl border border-zinc-200/70 bg-white shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                <thead class="bg-zinc-50 text-left text-xs uppercase tracking-[0.18em] text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">{{ __('Employee') }}</th>
                        <th class="px-4 py-3">{{ __('Date') }}</th>
                        <th class="px-4 py-3">{{ __('Status') }}</th>
                        <th class="px-4 py-3">{{ __('Late / Early') }}</th>
                        <th class="px-4 py-3">{{ __('Overtime') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($records as $record)
                        <tr>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-semibold text-zinc-950 dark:text-white">{{ $record->employee?->full_name ?? __('Unknown employee') }}</p>
                                    @if ($record->locked_period_name)
                                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-amber-700 dark:bg-amber-500/20 dark:text-amber-200">
                                            {{ __('Locked') }}
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $record->employee?->employee_number ?? '-' }}</p>
                                @if ($record->locked_period_name)
                                    <p class="mt-2 text-xs text-amber-700 dark:text-amber-200">{{ __('Frozen by period :period', ['period' => $record->locked_period_name]) }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ $record->attendance_date?->format('d M Y') }}</td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ __(ucfirst($record->status)) }}</td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ $record->late_minutes }} / {{ $record->early_leave_minutes }} {{ __('min') }}</td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ number_format((float) $record->approved_overtime_hours, 2) }} {{ __('hr') }}</td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    @if ($canManage && ! $record->locked_period_name)
                                        <a href="{{ route('payroll.attendance.edit', $record) }}" class="rounded-full border border-zinc-300 px-3 py-2 text-xs font-semibold text-zinc-700 transition hover:border-zinc-950 dark:border-zinc-700 dark:text-zinc-200 dark:hover:border-white">{{ __('Edit') }}</a>
                                        <form method="POST" action="{{ route('payroll.attendance.destroy', $record) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-full border border-rose-300 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-50 dark:border-rose-500/20 dark:text-rose-300 dark:hover:bg-rose-500/10" onclick="return confirm('{{ __('Delete this attendance record?') }}')">{{ __('Delete') }}</button>
                                        </form>
                                    @elseif ($canManage)
                                        <span class="text-xs font-semibold text-amber-700 dark:text-amber-200">{{ __('Locked after process') }}</span>
                                    @else
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('View only') }}</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-zinc-500 dark:text-zinc-400">{{ __('No attendance records yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="border-t border-zinc-200 px-4 py-4 dark:border-zinc-800">
                {{ $records->links() }}
            </div>
        </section>
    </div>
</x-layouts::app>
