<x-layouts::app :title="__('Payroll Periods')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <section class="flex flex-col gap-4 rounded-3xl border border-zinc-200/70 bg-white p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-cyan-600 dark:text-cyan-300">{{ __('Payroll periods') }}</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight text-zinc-950 dark:text-white">{{ __('Manage payroll periods') }}</h1>
                <p class="mt-3 max-w-2xl text-sm text-zinc-600 dark:text-zinc-300">{{ __('Set the processing range, pay date, and payroll period status.') }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('payroll.attendance.index') }}" class="self-start rounded-full border border-zinc-300 px-4 py-3 text-sm font-semibold text-zinc-800 transition hover:border-zinc-950 dark:border-zinc-700 dark:text-zinc-100 dark:hover:border-white">
                    {{ __('Attendance') }}
                </a>
                <a href="{{ route('payroll.leave.index') }}" class="self-start rounded-full border border-zinc-300 px-4 py-3 text-sm font-semibold text-zinc-800 transition hover:border-zinc-950 dark:border-zinc-700 dark:text-zinc-100 dark:hover:border-white">
                    {{ __('Leave') }}
                </a>
                <a href="{{ route('payroll.exports.index') }}" class="self-start rounded-full border border-zinc-300 px-4 py-3 text-sm font-semibold text-zinc-800 transition hover:border-zinc-950 dark:border-zinc-700 dark:text-zinc-100 dark:hover:border-white">
                    {{ __('Exports') }}
                </a>
                <a href="{{ route('payroll.inputs.index') }}" class="self-start rounded-full border border-zinc-300 px-4 py-3 text-sm font-semibold text-zinc-800 transition hover:border-zinc-950 dark:border-zinc-700 dark:text-zinc-100 dark:hover:border-white">
                    {{ __('Variable inputs') }}
                </a>
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('payroll.periods.create') }}" class="self-start rounded-full bg-zinc-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-zinc-800 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">
                        {{ __('New payroll period') }}
                    </a>
                @endif
            </div>
        </section>

        <section class="overflow-hidden rounded-3xl border border-zinc-200/70 bg-white shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                <thead class="bg-zinc-50 text-left text-xs uppercase tracking-[0.18em] text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">{{ __('Period') }}</th>
                        <th class="px-4 py-3">{{ __('Group') }}</th>
                        <th class="px-4 py-3">{{ __('Date range') }}</th>
                        <th class="px-4 py-3">{{ __('Pay date') }}</th>
                        <th class="px-4 py-3">{{ __('Status') }}</th>
                        <th class="px-4 py-3">{{ __('Runs') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($periods as $period)
                        @php($isLocked = $period->isLocked())
                        <tr>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-semibold text-zinc-950 dark:text-white">{{ $period->period_name }}</p>
                                    @if ($isLocked)
                                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-amber-700 dark:bg-amber-500/20 dark:text-amber-200">
                                            {{ __('Locked') }}
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __(':count payroll runs', ['count' => number_format($period->runs_count)]) }}</p>
                                @if ($isLocked)
                                    <p class="mt-2 text-xs text-amber-700 dark:text-amber-200">{{ __('Source data is frozen for this period.') }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ $period->payrollGroup?->name ?: '-' }}</td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ $period->start_date?->format('d M Y') }} - {{ $period->end_date?->format('d M Y') }}</td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ $period->pay_date?->format('d M Y') ?: '-' }}</td>
                            <td class="px-4 py-4">
                                <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                    {{ __(ucfirst(is_object($period->status) ? $period->status->value : $period->status)) }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ number_format($period->runs_count) }}</td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    <form method="POST" action="{{ route('payroll.periods.process', $period) }}">
                                        @csrf
                                        <button type="submit" class="rounded-full border border-emerald-300 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-50 dark:border-emerald-500/20 dark:text-emerald-300 dark:hover:bg-emerald-500/10">
                                            {{ __('Process') }}
                                        </button>
                                    </form>
                                    <a href="{{ route('payroll.inputs.index', ['period_id' => $period->id]) }}" class="rounded-full border border-zinc-300 px-3 py-2 text-xs font-semibold text-zinc-700 transition hover:border-zinc-950 dark:border-zinc-700 dark:text-zinc-200 dark:hover:border-white">
                                        {{ __('Inputs') }}
                                    </a>
                                    @if (in_array(is_object($period->status) ? $period->status->value : $period->status, ['finalized', 'paid'], true))
                                        <a href="{{ route('payroll.exports.index', ['period_id' => $period->id]) }}" class="rounded-full border border-zinc-300 px-3 py-2 text-xs font-semibold text-zinc-700 transition hover:border-zinc-950 dark:border-zinc-700 dark:text-zinc-200 dark:hover:border-white">
                                            {{ __('Export') }}
                                        </a>
                                    @endif
                                    <a href="{{ route('payroll.runs.index', ['period_id' => $period->id]) }}" class="rounded-full border border-zinc-300 px-3 py-2 text-xs font-semibold text-zinc-700 transition hover:border-zinc-950 dark:border-zinc-700 dark:text-zinc-200 dark:hover:border-white">
                                        {{ __('Runs') }}
                                    </a>
                                    @if (auth()->user()->isAdmin())
                                        @if ($isLocked)
                                            <span class="inline-flex items-center rounded-full border border-amber-200 px-3 py-2 text-xs font-semibold text-amber-700 dark:border-amber-500/20 dark:text-amber-200">
                                                {{ __('Locked after process') }}
                                            </span>
                                        @else
                                            <a href="{{ route('payroll.periods.edit', $period) }}" class="rounded-full border border-zinc-300 px-3 py-2 text-xs font-semibold text-zinc-700 transition hover:border-zinc-950 dark:border-zinc-700 dark:text-zinc-200 dark:hover:border-white">{{ __('Edit') }}</a>
                                            <form method="POST" action="{{ route('payroll.periods.destroy', $period) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-full border border-rose-300 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-50 dark:border-rose-500/20 dark:text-rose-300 dark:hover:bg-rose-500/10" onclick="return confirm('{{ __('Delete this payroll period?') }}')">{{ __('Delete') }}</button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-zinc-500 dark:text-zinc-400">{{ __('No payroll periods yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="border-t border-zinc-200 px-4 py-4 dark:border-zinc-800">
                {{ $periods->links() }}
            </div>
        </section>
    </div>
</x-layouts::app>
