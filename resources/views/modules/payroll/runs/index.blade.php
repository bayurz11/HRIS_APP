<x-layouts::app :title="__('Payroll Runs')">
    @php
        $pageCounts = [
            'draft' => $runs->getCollection()->filter(fn ($item) => $item->calculation_status->value === 'draft')->count(),
            'calculated' => $runs->getCollection()->filter(fn ($item) => $item->calculation_status->value === 'calculated')->count(),
            'approved' => $runs->getCollection()->filter(fn ($item) => $item->calculation_status->value === 'approved')->count(),
            'paid' => $runs->getCollection()->filter(fn ($item) => $item->calculation_status->value === 'paid')->count(),
        ];
    @endphp

    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <section class="rounded-3xl border border-zinc-200/70 bg-white p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                <div class="max-w-3xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-cyan-600 dark:text-cyan-300">{{ __('Payroll runs') }}</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight text-zinc-950 dark:text-white">{{ __('Payroll results and approval queue') }}</h1>
                    <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Use this page to see who has been calculated, which payrolls still need review, and whether a payslip is ready to share.') }}</p>
                </div>

                <form method="GET" action="{{ route('payroll.runs.index') }}" class="grid gap-3 sm:grid-cols-3">
                    <select name="period_id" class="rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        <option value="">{{ __('All periods') }}</option>
                        @foreach ($periods as $period)
                            <option value="{{ $period->id }}" @selected($selectedPeriodId === $period->id)>{{ $period->period_name }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected($selectedStatus === $status->value)>{{ __(ucfirst($status->value)) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="rounded-2xl bg-zinc-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-zinc-800 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">
                        {{ __('Apply filter') }}
                    </button>
                </form>
            </div>

            <div class="mt-5 grid gap-3 md:grid-cols-4">
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50/80 p-4 dark:border-zinc-700 dark:bg-zinc-950/60">
                    <p class="text-xs uppercase tracking-[0.22em] text-zinc-500 dark:text-zinc-400">{{ __('Draft') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-zinc-950 dark:text-white">{{ number_format($pageCounts['draft']) }}</p>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Not ready for review yet.') }}</p>
                </div>
                <div class="rounded-2xl border border-amber-300/60 bg-amber-50 p-4 dark:border-amber-500/20 dark:bg-amber-500/10">
                    <p class="text-xs uppercase tracking-[0.22em] text-amber-700 dark:text-amber-300">{{ __('Calculated') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-amber-900 dark:text-amber-100">{{ number_format($pageCounts['calculated']) }}</p>
                    <p class="mt-1 text-sm text-amber-800/80 dark:text-amber-100/80">{{ __('Waiting for approval.') }}</p>
                </div>
                <div class="rounded-2xl border border-emerald-300/60 bg-emerald-50 p-4 dark:border-emerald-500/20 dark:bg-emerald-500/10">
                    <p class="text-xs uppercase tracking-[0.22em] text-emerald-700 dark:text-emerald-300">{{ __('Approved') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-emerald-900 dark:text-emerald-100">{{ number_format($pageCounts['approved']) }}</p>
                    <p class="mt-1 text-sm text-emerald-800/80 dark:text-emerald-100/80">{{ __('Ready to be marked as paid.') }}</p>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50/80 p-4 dark:border-zinc-700 dark:bg-zinc-950/60">
                    <p class="text-xs uppercase tracking-[0.22em] text-zinc-500 dark:text-zinc-400">{{ __('Paid') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-zinc-950 dark:text-white">{{ number_format($pageCounts['paid']) }}</p>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Finished and ready for archive.') }}</p>
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-zinc-200/70 bg-white shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 px-4 py-4 text-sm text-zinc-600 dark:border-zinc-800 dark:text-zinc-300">
                {{ __('Tip: open the detail page to see the payroll breakdown, approval status, and the safest next action.') }}
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                    <thead class="bg-zinc-50 text-left text-xs uppercase tracking-[0.18em] text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400">
                        <tr>
                            <th class="px-4 py-3">{{ __('Employee') }}</th>
                            <th class="px-4 py-3">{{ __('Period') }}</th>
                            <th class="px-4 py-3">{{ __('Gross salary') }}</th>
                            <th class="px-4 py-3">{{ __('Take home pay') }}</th>
                            <th class="px-4 py-3">{{ __('Status') }}</th>
                            <th class="px-4 py-3">{{ __('Payslip') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @forelse ($runs as $run)
                            <tr>
                                <td class="px-4 py-4">
                                    <p class="font-semibold text-zinc-950 dark:text-white">{{ $run->employee?->full_name ?? __('Unknown employee') }}</p>
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $run->employee?->employee_number ?? '-' }}</p>
                                </td>
                                <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ $run->payrollPeriod?->period_name ?? '-' }}</td>
                                <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ number_format((float) $run->gross_salary, 0, ',', '.') }}</td>
                                <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ number_format((float) $run->take_home_pay, 0, ',', '.') }}</td>
                                <td class="px-4 py-4">
                                    <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                        {{ __(ucfirst($run->calculation_status->value)) }}
                                    </span>
                                    @if ($run->calculation_status->value === 'calculated' && $run->approvalSteps->firstWhere('status', 'pending'))
                                        <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Waiting for :role', ['role' => $run->approvalSteps->firstWhere('status', 'pending')->role_name]) }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">
                                    @if ($run->payslip?->is_published)
                                        {{ __('Published') }}
                                    @elseif ($run->payslip)
                                        {{ __('Draft') }}
                                    @else
                                        {{ __('Not created') }}
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <a href="{{ route('payroll.runs.show', $run) }}" class="inline-flex rounded-full border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-zinc-950 dark:border-zinc-700 dark:text-zinc-200 dark:hover:border-white">
                                        {{ __('View details') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-zinc-500 dark:text-zinc-400">{{ __('No payroll runs yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-zinc-200 px-4 py-4 dark:border-zinc-800">
                {{ $runs->links() }}
            </div>
        </section>
    </div>
</x-layouts::app>
