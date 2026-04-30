<x-layouts::app :title="__('Payroll')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <section class="rounded-3xl border border-zinc-200/70 bg-white p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-emerald-600 dark:text-emerald-300">{{ __('Payroll module') }}</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight text-zinc-950 dark:text-white">{{ __('Payroll foundation aligned with the README blueprint.') }}</h1>
                    <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-300">
                        {{ __('This domain already includes model structures, snapshot-ready migrations, a query layer, action and service foundations, and payroll period API endpoints.') }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('payroll.groups.index') }}" class="rounded-full bg-zinc-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-zinc-800 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">
                            {{ __('Manage groups') }}
                        </a>
                    @endif
                    <a href="{{ route('payroll.periods.index') }}" class="rounded-full border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-800 transition hover:border-zinc-950 dark:border-zinc-700 dark:text-zinc-100 dark:hover:border-white">
                        {{ __('Payroll periods') }}
                    </a>
                    <a href="{{ route('payroll.attendance.index') }}" class="rounded-full border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-800 transition hover:border-zinc-950 dark:border-zinc-700 dark:text-zinc-100 dark:hover:border-white">
                        {{ __('Attendance') }}
                    </a>
                    <a href="{{ route('payroll.leave.index') }}" class="rounded-full border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-800 transition hover:border-zinc-950 dark:border-zinc-700 dark:text-zinc-100 dark:hover:border-white">
                        {{ __('Leave') }}
                    </a>
                    <a href="{{ route('payroll.exports.index') }}" class="rounded-full border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-800 transition hover:border-zinc-950 dark:border-zinc-700 dark:text-zinc-100 dark:hover:border-white">
                        {{ __('Exports') }}
                    </a>
                    <a href="{{ route('payroll.inputs.index') }}" class="rounded-full border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-800 transition hover:border-zinc-950 dark:border-zinc-700 dark:text-zinc-100 dark:hover:border-white">
                        {{ __('Variable inputs') }}
                    </a>
                    <a href="{{ route('payroll.runs.index') }}" class="rounded-full border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-800 transition hover:border-zinc-950 dark:border-zinc-700 dark:text-zinc-100 dark:hover:border-white">
                        {{ __('Review runs') }}
                    </a>
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-zinc-200/70 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs uppercase tracking-[0.22em] text-zinc-500 dark:text-zinc-400">{{ __('Finalized periods') }}</p>
                <p class="mt-3 text-3xl font-semibold text-zinc-950 dark:text-white">{{ number_format($summary['finalized_period_count']) }}</p>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Periods that are already safe for history and payslip distribution.') }}</p>
            </article>
            <article class="rounded-2xl border border-amber-300/60 bg-amber-50 p-5 dark:border-amber-500/20 dark:bg-amber-500/10">
                <p class="text-xs uppercase tracking-[0.22em] text-amber-700 dark:text-amber-300">{{ __('Draft or calculated') }}</p>
                <p class="mt-3 text-3xl font-semibold text-amber-900 dark:text-amber-100">{{ number_format($summary['draft_run_count']) }}</p>
                <p class="mt-2 text-sm text-amber-800/80 dark:text-amber-100/80">{{ __('Runs that still need approval, finalization, or payment.') }}</p>
            </article>
            <article class="rounded-2xl border border-zinc-200/70 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs uppercase tracking-[0.22em] text-zinc-500 dark:text-zinc-400">{{ __('Schema style') }}</p>
                <p class="mt-3 text-3xl font-semibold text-zinc-950 dark:text-white">{{ __('Snapshot') }}</p>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Historical payroll is not recalculated from master data that changes later.') }}</p>
            </article>
            <article class="rounded-2xl border border-zinc-200/70 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs uppercase tracking-[0.22em] text-zinc-500 dark:text-zinc-400">API</p>
                <p class="mt-3 text-3xl font-semibold text-zinc-950 dark:text-white">{{ __('Ready') }}</p>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ __('`GET /api/v1/payroll-periods` already uses the standard response envelope.') }}</p>
            </article>
        </section>

        <section class="rounded-3xl border border-zinc-200/70 bg-white p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">{{ __('Recent payroll periods') }}</h2>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('This data is loaded from the modular query layer, not written directly in Blade.') }}</p>
                </div>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('BPJS and PPh21 are now calculated with rule-driven formulas plus multi-level approval support.') }}</p>
            </div>

            <div class="mt-5 overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-700">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                    <thead class="bg-zinc-50 text-left text-xs uppercase tracking-[0.18em] text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400">
                        <tr>
                            <th class="px-4 py-3">{{ __('Period') }}</th>
                            <th class="px-4 py-3">{{ __('Group') }}</th>
                            <th class="px-4 py-3">{{ __('Pay date') }}</th>
                            <th class="px-4 py-3">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('Runs') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-800 dark:bg-zinc-900">
                        @forelse ($periods as $period)
                            <tr>
                                <td class="px-4 py-4 font-medium text-zinc-950 dark:text-white">{{ $period->period_name }}</td>
                                <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ $period->payrollGroup?->name ?? __('Unassigned') }}</td>
                                <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ $period->pay_date?->format('d M Y') ?? '-' }}</td>
                                <td class="px-4 py-4">
                                    <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                        {{ __(ucfirst(is_object($period->status) ? $period->status->value : $period->status)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-right font-medium text-zinc-950 dark:text-white">{{ number_format($period->runs_count) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                                    {{ __('No payroll periods yet. Run migrations and start filling organization and payroll group master data.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="grid gap-6 lg:grid-cols-2">
            <article class="rounded-3xl border border-zinc-200/70 bg-white p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">{{ __('Payroll groups') }}</h2>
                    <a href="{{ route('payroll.groups.create') }}" class="text-sm font-medium text-emerald-700 dark:text-emerald-300">{{ __('New group') }}</a>
                </div>

                <div class="mt-5 space-y-3">
                    @forelse ($groups as $group)
                        <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-zinc-950 dark:text-white">{{ $group->name }}</p>
                                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $group->code }} | {{ $group->organization?->name ?? __('No organization') }}</p>
                                </div>
                                <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                    {{ __(':count periods', ['count' => $group->periods_count]) }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="rounded-2xl border border-dashed border-zinc-300 px-4 py-8 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                            {{ __('No payroll groups yet.') }}
                        </p>
                    @endforelse
                </div>
            </article>

            <article class="rounded-3xl border border-zinc-200/70 bg-white p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">{{ __('Supported statuses') }}</h2>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Period status follows the domain enum so UI routes, queries, and APIs stay consistent.') }}</p>

                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    @foreach ($statuses as $status)
                        <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <p class="text-xs uppercase tracking-[0.22em] text-zinc-500 dark:text-zinc-400">{{ __('Payroll period') }}</p>
                            <p class="mt-2 text-lg font-semibold capitalize text-zinc-950 dark:text-white">{{ __(ucfirst($status->value)) }}</p>
                        </div>
                    @endforeach
                </div>
            </article>
        </section>

        <section class="rounded-3xl border border-zinc-200/70 bg-white p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">{{ __('Recent payroll runs') }}</h2>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Monitor the latest payroll processing results and open workflow details quickly.') }}</p>
                </div>
                <a href="{{ route('payroll.runs.index') }}" class="text-sm font-medium text-emerald-700 dark:text-emerald-300">{{ __('View all runs') }}</a>
            </div>

            <div class="mt-5 overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-700">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                    <thead class="bg-zinc-50 text-left text-xs uppercase tracking-[0.18em] text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400">
                        <tr>
                            <th class="px-4 py-3">{{ __('Employee') }}</th>
                            <th class="px-4 py-3">{{ __('Period') }}</th>
                            <th class="px-4 py-3">{{ __('Take home pay') }}</th>
                            <th class="px-4 py-3">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-800 dark:bg-zinc-900">
                        @forelse ($recentRuns as $run)
                            <tr>
                                <td class="px-4 py-4 font-medium text-zinc-950 dark:text-white">{{ $run->employee?->full_name ?? __('Unknown employee') }}</td>
                                <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ $run->payrollPeriod?->period_name ?? '-' }}</td>
                                <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ number_format((float) $run->take_home_pay, 0, ',', '.') }}</td>
                                <td class="px-4 py-4">
                                    <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                        {{ __(ucfirst($run->calculation_status->value)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <a href="{{ route('payroll.runs.show', $run) }}" class="text-sm font-medium text-emerald-700 dark:text-emerald-300">{{ __('Open') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">{{ __('No payroll runs processed yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts::app>
