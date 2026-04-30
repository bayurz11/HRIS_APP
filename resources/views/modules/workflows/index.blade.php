<x-layouts::app :title="__('Workflow Center')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <section class="rounded-3xl border border-zinc-200/70 bg-white p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                <div class="max-w-3xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-cyan-600 dark:text-cyan-300">{{ __('Workflow center') }}</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight text-zinc-950 dark:text-white">{{ __('Approval queue and revision control') }}</h1>
                    <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Use this module to monitor payroll items waiting for approval, payroll runs returned for revision, and the latest workflow comments from the team.') }}</p>
                </div>

                <form method="GET" action="{{ route('workflows.index') }}" class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto]">
                    <select name="period_id" class="rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        <option value="">{{ __('All periods') }}</option>
                        @foreach ($periods as $period)
                            <option value="{{ $period->id }}" @selected($selectedPeriodId === $period->id)>{{ $period->period_name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="rounded-2xl bg-zinc-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-zinc-800 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">
                        {{ __('Apply filter') }}
                    </button>
                </form>
            </div>

            <div class="mt-5 grid gap-3 md:grid-cols-4">
                <article class="rounded-2xl border border-amber-300/60 bg-amber-50 p-4 dark:border-amber-500/20 dark:bg-amber-500/10">
                    <p class="text-xs uppercase tracking-[0.22em] text-amber-700 dark:text-amber-300">{{ __('Pending approvals') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-amber-950 dark:text-amber-100">{{ number_format($summary['pending_approvals']) }}</p>
                    <p class="mt-1 text-sm text-amber-900/80 dark:text-amber-100/80">{{ __('Payroll runs waiting for the current role to act.') }}</p>
                </article>
                <article class="rounded-2xl border border-rose-300/60 bg-rose-50 p-4 dark:border-rose-500/20 dark:bg-rose-500/10">
                    <p class="text-xs uppercase tracking-[0.22em] text-rose-700 dark:text-rose-300">{{ __('Returned runs') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-rose-950 dark:text-rose-100">{{ number_format($summary['returned_runs']) }}</p>
                    <p class="mt-1 text-sm text-rose-900/80 dark:text-rose-100/80">{{ __('Items that already have revision notes and need follow-up.') }}</p>
                </article>
                <article class="rounded-2xl border border-emerald-300/60 bg-emerald-50 p-4 dark:border-emerald-500/20 dark:bg-emerald-500/10">
                    <p class="text-xs uppercase tracking-[0.22em] text-emerald-700 dark:text-emerald-300">{{ __('Ready for payment') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-emerald-950 dark:text-emerald-100">{{ number_format($summary['payment_queue']) }}</p>
                    <p class="mt-1 text-sm text-emerald-900/80 dark:text-emerald-100/80">{{ __('Approved payroll runs that can move to payment.') }}</p>
                </article>
                <article class="rounded-2xl border border-zinc-200 bg-zinc-50/80 p-4 dark:border-zinc-700 dark:bg-zinc-950/60">
                    <p class="text-xs uppercase tracking-[0.22em] text-zinc-500 dark:text-zinc-400">{{ __('Recent log') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-zinc-950 dark:text-white">{{ number_format($summary['recent_logs']) }}</p>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Latest workflow comments and decisions in the selected scope.') }}</p>
                </article>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
            <div class="space-y-6">
                <article class="rounded-3xl border border-zinc-200/70 bg-white p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">{{ __('Pending approval queue') }}</h2>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Open the detail page to approve with comments or return the payroll run with a clear revision note.') }}</p>
                        </div>
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-amber-700 dark:bg-amber-500/20 dark:text-amber-200">{{ number_format($pendingApprovals->count()) }}</span>
                    </div>

                    <div class="mt-5 space-y-3">
                        @forelse ($pendingApprovals as $run)
                            @php($currentStep = $run->approvalSteps->firstWhere('status', 'pending'))
                            <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                    <div>
                                        <p class="font-semibold text-zinc-950 dark:text-white">{{ $run->employee?->full_name ?? __('Unknown employee') }}</p>
                                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $run->payroll_number }} | {{ $run->payrollPeriod?->period_name }} | {{ $run->payrollPeriod?->payrollGroup?->name }}</p>
                                        <p class="mt-2 text-xs uppercase tracking-[0.18em] text-amber-700 dark:text-amber-300">{{ __('Waiting for :role', ['role' => $currentStep?->role_name ?? __('Unknown role')]) }}</p>
                                    </div>
                                    <a href="{{ route('payroll.runs.show', $run) }}" class="inline-flex rounded-full border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-zinc-950 dark:border-zinc-700 dark:text-zinc-200 dark:hover:border-white">
                                        {{ __('Open run detail') }}
                                    </a>
                                </div>
                            </div>
                        @empty
                            <p class="rounded-2xl border border-dashed border-zinc-300 px-4 py-8 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                                {{ __('There is no approval queue for the current filter or your current role.') }}
                            </p>
                        @endforelse
                    </div>
                </article>

                <article class="rounded-3xl border border-zinc-200/70 bg-white p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">{{ __('Returned for revision') }}</h2>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('This queue helps payroll officers see what was returned and the exact correction reason from approvers.') }}</p>
                        </div>
                        <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-rose-700 dark:bg-rose-500/20 dark:text-rose-200">{{ number_format($returnedRuns->count()) }}</span>
                    </div>

                    <div class="mt-5 space-y-3">
                        @forelse ($returnedRuns as $run)
                            @php($returnLog = $run->getRelation('latest_return_log'))
                            <div class="rounded-2xl border border-rose-200/70 bg-rose-50/60 p-4 dark:border-rose-500/20 dark:bg-rose-500/10">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                    <div>
                                        <p class="font-semibold text-zinc-950 dark:text-white">{{ $run->employee?->full_name ?? __('Unknown employee') }}</p>
                                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $run->payroll_number }} | {{ $run->payrollPeriod?->period_name }}</p>
                                        <div class="mt-3 rounded-2xl bg-white/80 px-4 py-3 text-sm text-rose-950 dark:bg-zinc-950/20 dark:text-rose-100">
                                            <p class="text-xs uppercase tracking-[0.18em] text-rose-700 dark:text-rose-300">{{ __('Revision reason') }}</p>
                                            <p class="mt-2">{{ $returnLog?->notes ?: __('No reason was written.') }}</p>
                                            <p class="mt-2 text-xs text-rose-800/70 dark:text-rose-100/70">{{ $returnLog?->actor?->name ?? __('System') }}{{ $returnLog?->created_at ? ' | '.$returnLog->created_at->format('d M Y H:i') : '' }}</p>
                                        </div>
                                    </div>
                                    <a href="{{ route('payroll.runs.show', $run) }}" class="inline-flex rounded-full border border-rose-300 px-4 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-100 dark:border-rose-500/20 dark:text-rose-200 dark:hover:bg-rose-500/20">
                                        {{ __('Open for revision') }}
                                    </a>
                                </div>
                            </div>
                        @empty
                            <p class="rounded-2xl border border-dashed border-zinc-300 px-4 py-8 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                                {{ __('No returned payroll runs yet.') }}
                            </p>
                        @endforelse
                    </div>
                </article>
            </div>

            <div class="space-y-6">
                <article class="rounded-3xl border border-zinc-200/70 bg-white p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">{{ __('Ready for payment') }}</h2>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Finance can continue from this queue after all approval steps are completed.') }}</p>
                        </div>
                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-200">{{ number_format($paymentQueue->count()) }}</span>
                    </div>

                    <div class="mt-5 space-y-3">
                        @forelse ($paymentQueue as $run)
                            <div class="rounded-2xl border border-emerald-200/70 bg-emerald-50/60 p-4 dark:border-emerald-500/20 dark:bg-emerald-500/10">
                                <p class="font-semibold text-zinc-950 dark:text-white">{{ $run->employee?->full_name ?? __('Unknown employee') }}</p>
                                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $run->payroll_number }} | {{ $run->payrollPeriod?->period_name }}</p>
                                <p class="mt-3 text-sm font-semibold text-emerald-900 dark:text-emerald-100">{{ __('Take home pay') }}: {{ number_format((float) $run->take_home_pay, 0, ',', '.') }}</p>
                                <a href="{{ route('payroll.runs.show', $run) }}" class="mt-3 inline-flex rounded-full border border-emerald-300 px-4 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100 dark:border-emerald-500/20 dark:text-emerald-200 dark:hover:bg-emerald-500/20">
                                    {{ __('Open payment action') }}
                                </a>
                            </div>
                        @empty
                            <p class="rounded-2xl border border-dashed border-zinc-300 px-4 py-8 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                                {{ __('No approved payroll runs are waiting for payment.') }}
                            </p>
                        @endforelse
                    </div>
                </article>

                <article class="rounded-3xl border border-zinc-200/70 bg-white p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900">
                    <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">{{ __('Recent workflow comments') }}</h2>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Use this timeline to understand the latest approval decisions and revision context without opening every payroll run one by one.') }}</p>

                    <div class="mt-5 space-y-3">
                        @forelse ($recentLogs as $log)
                            <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="font-semibold capitalize text-zinc-950 dark:text-white">{{ str_replace('_', ' ', $log->action) }}</p>
                                    <p class="text-xs uppercase tracking-[0.16em] text-zinc-500 dark:text-zinc-400">{{ $log->created_at->format('d M Y H:i') }}</p>
                                </div>
                                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ $log->payrollRun?->employee?->full_name ?? __('Unknown employee') }} | {{ $log->payrollRun?->payrollPeriod?->period_name ?? '-' }}</p>
                                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ $log->actor?->name ?? __('System') }} | {{ $log->status_before ?: __('none') }} -> {{ $log->status_after ?: __('none') }}</p>
                                @if ($log->notes)
                                    <p class="mt-3 rounded-2xl bg-zinc-50 px-3 py-3 text-sm text-zinc-700 dark:bg-zinc-950/60 dark:text-zinc-200">{{ $log->notes }}</p>
                                @endif
                            </div>
                        @empty
                            <p class="rounded-2xl border border-dashed border-zinc-300 px-4 py-8 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                                {{ __('No workflow comments yet for the selected filter.') }}
                            </p>
                        @endforelse
                    </div>
                </article>
            </div>
        </section>
    </div>
</x-layouts::app>
