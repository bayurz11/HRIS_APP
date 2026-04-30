@php
    $moduleGroups = [
        'organization' => 'core',
        'users' => 'core',
        'workflows' => 'operations',
        'documents' => 'operations',
        'reports' => 'insight',
        'notifications' => 'operations',
        'audit-trail' => 'insight',
    ];

    $quickActions = [
        [
            'label' => 'Open payroll overview',
            'description' => 'Review periods, runs, and payroll operational shortcuts.',
            'href' => route('payroll.index'),
            'tone' => 'emerald',
        ],
        [
            'label' => 'Open payroll periods',
            'description' => 'Start a new processing cycle or continue a finalized period.',
            'href' => route('payroll.periods.index'),
            'tone' => 'cyan',
        ],
        [
            'label' => 'Review notifications',
            'description' => 'See approvals, payslip publishing, and important app events.',
            'href' => route('notifications.index'),
            'tone' => 'amber',
        ],
    ];

    if (auth()->user()->isAdmin()) {
        $quickActions[] = [
            'label' => 'Manage organizations',
            'description' => 'Update company structure, reporting lines, and work units.',
            'href' => route('organization.index'),
            'tone' => 'zinc',
        ];
    }
@endphp

<x-layouts::app :title="__('Dashboard')">
    <div
        class="flex h-full w-full flex-1 flex-col gap-6"
        x-data="{ focus: 'overview', moduleFilter: 'all' }"
    >
        <section class="overflow-hidden rounded-3xl border border-white/10 bg-[radial-gradient(circle_at_top_left,_rgba(0,181,173,0.28),_transparent_34%),linear-gradient(135deg,_#0f172a,_#111827_55%,_#1f2937)] p-6 text-white shadow-2xl shadow-slate-950/20">
            <div class="grid gap-6 xl:grid-cols-[1.4fr_0.9fr]">
                <div class="space-y-5">
                    <div class="max-w-3xl space-y-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">Pusat Kendali HARIS</p>
                        <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">{{ __('Interactive HRIS command center for daily operational focus.') }}</h1>
                        <p class="max-w-2xl text-sm text-slate-200 sm:text-base">
                            {{ __('Switch your focus between module readiness, payroll pulse, and action queues without leaving the dashboard.') }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <button
                            type="button"
                            @click="focus = 'overview'"
                            :class="focus === 'overview' ? 'bg-white text-zinc-950' : 'border-white/20 text-white hover:border-white/50'"
                            class="rounded-full border px-4 py-2 text-sm font-semibold transition"
                        >
                            {{ __('Module overview') }}
                        </button>
                        <button
                            type="button"
                            @click="focus = 'payroll'"
                            :class="focus === 'payroll' ? 'bg-white text-zinc-950' : 'border-white/20 text-white hover:border-white/50'"
                            class="rounded-full border px-4 py-2 text-sm font-semibold transition"
                        >
                            {{ __('Payroll pulse') }}
                        </button>
                        <button
                            type="button"
                            @click="focus = 'actions'"
                            :class="focus === 'actions' ? 'bg-white text-zinc-950' : 'border-white/20 text-white hover:border-white/50'"
                            class="rounded-full border px-4 py-2 text-sm font-semibold transition"
                        >
                            {{ __('Action queue') }}
                        </button>
                    </div>
                </div>

                <div class="grid gap-3 rounded-2xl border border-white/10 bg-white/5 p-4 text-sm text-slate-100 backdrop-blur md:grid-cols-2">
                    <div>
                        <p class="text-xs uppercase tracking-[0.22em] text-slate-300">{{ __('API prefix') }}</p>
                        <p class="mt-1 font-medium">/api/v1</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-[0.22em] text-slate-300">{{ __('Pattern') }}</p>
                        <p class="mt-1 font-medium">{{ __('Modular monolith') }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-[0.22em] text-slate-300">{{ __('UI stack') }}</p>
                        <p class="mt-1 font-medium">Livewire + Flux</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-[0.22em] text-slate-300">{{ __('Payroll mode') }}</p>
                        <p class="mt-1 font-medium">{{ __('Snapshot ready') }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($metrics as $metric)
                <button
                    type="button"
                    @click="focus = '{{ $loop->index < 2 ? 'overview' : 'payroll' }}'"
                    class="rounded-2xl border border-zinc-200/70 bg-white p-5 text-left shadow-sm shadow-zinc-950/5 transition hover:-translate-y-0.5 hover:border-zinc-300 dark:border-zinc-700 dark:bg-zinc-900"
                >
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-zinc-500 dark:text-zinc-400">{{ __($metric['label']) }}</p>
                    <p class="mt-3 text-3xl font-semibold text-zinc-950 dark:text-white">{{ number_format($metric['value']) }}</p>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ __($metric['description']) }}</p>
                </button>
            @endforeach
        </section>

        <section x-show="focus === 'overview'" x-transition.opacity.duration.200ms class="grid gap-6 xl:grid-cols-[1.45fr_0.95fr]">
            <div class="rounded-3xl border border-zinc-200/70 bg-white p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">{{ __('Domain roadmap') }}</h2>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Filter module readiness by operational area to review what is ready to use.') }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" @click="moduleFilter = 'all'" :class="moduleFilter === 'all' ? 'bg-zinc-950 text-white dark:bg-white dark:text-zinc-950' : 'border-zinc-300 text-zinc-700 dark:border-zinc-700 dark:text-zinc-200'" class="rounded-full border px-3 py-2 text-xs font-semibold uppercase tracking-[0.18em] transition">{{ __('All') }}</button>
                        <button type="button" @click="moduleFilter = 'core'" :class="moduleFilter === 'core' ? 'bg-zinc-950 text-white dark:bg-white dark:text-zinc-950' : 'border-zinc-300 text-zinc-700 dark:border-zinc-700 dark:text-zinc-200'" class="rounded-full border px-3 py-2 text-xs font-semibold uppercase tracking-[0.18em] transition">{{ __('Core') }}</button>
                        <button type="button" @click="moduleFilter = 'operations'" :class="moduleFilter === 'operations' ? 'bg-zinc-950 text-white dark:bg-white dark:text-zinc-950' : 'border-zinc-300 text-zinc-700 dark:border-zinc-700 dark:text-zinc-200'" class="rounded-full border px-3 py-2 text-xs font-semibold uppercase tracking-[0.18em] transition">{{ __('Operations') }}</button>
                        <button type="button" @click="moduleFilter = 'insight'" :class="moduleFilter === 'insight' ? 'bg-zinc-950 text-white dark:bg-white dark:text-zinc-950' : 'border-zinc-300 text-zinc-700 dark:border-zinc-700 dark:text-zinc-200'" class="rounded-full border px-3 py-2 text-xs font-semibold uppercase tracking-[0.18em] transition">{{ __('Insight') }}</button>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    @foreach ($modulePages as $slug => $module)
                        <article
                            x-show="moduleFilter === 'all' || moduleFilter === '{{ $moduleGroups[$slug] ?? 'operations' }}'"
                            x-transition.opacity.duration.150ms
                            class="rounded-2xl border border-zinc-200/80 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/60"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-zinc-950 dark:text-white">{{ __($module['title']) }}</h3>
                                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ __($module['description']) }}</p>
                                </div>
                                <span class="rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700 dark:text-emerald-300">{{ __('Ready base') }}</span>
                            </div>

                            <ul class="mt-4 space-y-2 text-sm text-zinc-600 dark:text-zinc-300">
                                @foreach ($module['focus'] as $focus)
                                    <li class="flex gap-2">
                                        <span class="mt-1 h-2 w-2 rounded-full bg-cyan-500"></span>
                                        <span>{{ __($focus) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </article>
                    @endforeach
                </div>
            </div>

            <aside class="rounded-3xl border border-zinc-200/70 bg-white p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">{{ __('Quick actions') }}</h2>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Open the areas most often used during daily setup and payroll operations.') }}</p>

                <div class="mt-5 space-y-3">
                    @foreach ($quickActions as $action)
                        <a
                            href="{{ $action['href'] }}"
                            class="block rounded-2xl border border-zinc-200 p-4 transition hover:border-zinc-400 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:border-zinc-500 dark:hover:bg-zinc-950/60"
                        >
                            <p class="text-sm font-semibold text-zinc-950 dark:text-white">{{ __($action['label']) }}</p>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __($action['description']) }}</p>
                        </a>
                    @endforeach
                </div>
            </aside>
        </section>

        <section x-show="focus === 'payroll'" x-transition.opacity.duration.200ms class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <div class="rounded-3xl border border-zinc-200/70 bg-white p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">{{ __('Payroll pulse') }}</h2>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Monitor period status, export readiness, and recent payroll movement from one panel.') }}</p>
                    </div>
                    <a href="{{ route('payroll.index') }}" class="rounded-full border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:border-zinc-950 hover:text-zinc-950 dark:border-zinc-700 dark:text-zinc-200 dark:hover:border-white dark:hover:text-white">
                        {{ __('Open payroll overview') }}
                    </a>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl bg-zinc-950 p-4 text-white dark:bg-zinc-800">
                        <p class="text-xs uppercase tracking-[0.22em] text-zinc-400">{{ __('Payroll groups') }}</p>
                        <p class="mt-2 text-3xl font-semibold">{{ number_format($payrollSnapshot['group_count']) }}</p>
                    </div>
                    <div class="rounded-2xl border border-amber-300/50 bg-amber-50 p-4 dark:border-amber-500/20 dark:bg-amber-500/10">
                        <p class="text-xs uppercase tracking-[0.22em] text-amber-700 dark:text-amber-300">{{ __('Needs follow-up') }}</p>
                        <p class="mt-2 text-3xl font-semibold text-amber-900 dark:text-amber-100">{{ number_format($payrollSnapshot['draft_run_count']) }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                        <p class="text-xs uppercase tracking-[0.22em] text-zinc-500 dark:text-zinc-400">{{ __('Periods') }}</p>
                        <p class="mt-2 text-2xl font-semibold text-zinc-950 dark:text-white">{{ number_format($payrollSnapshot['period_count']) }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                        <p class="text-xs uppercase tracking-[0.22em] text-zinc-500 dark:text-zinc-400">{{ __('Finalized') }}</p>
                        <p class="mt-2 text-2xl font-semibold text-zinc-950 dark:text-white">{{ number_format($payrollSnapshot['finalized_period_count']) }}</p>
                    </div>
                </div>

                <div class="mt-6 overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-700">
                    <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                        <thead class="bg-zinc-50 text-left text-xs uppercase tracking-[0.18em] text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400">
                            <tr>
                                <th class="px-4 py-3">{{ __('Recent periods') }}</th>
                                <th class="px-4 py-3">{{ __('Group') }}</th>
                                <th class="px-4 py-3">{{ __('Pay date') }}</th>
                                <th class="px-4 py-3">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Runs') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-800 dark:bg-zinc-900">
                            @forelse ($recentPeriods as $period)
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
                                    <td colspan="5" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">{{ __('No payroll periods yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <aside class="rounded-3xl border border-zinc-200/70 bg-white p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">{{ __('Recent runs') }}</h2>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Open the latest calculations and continue the approval workflow.') }}</p>
                    </div>
                    <a href="{{ route('payroll.runs.index') }}" class="text-sm font-medium text-emerald-700 dark:text-emerald-300">{{ __('View all runs') }}</a>
                </div>

                <div class="mt-5 space-y-3">
                    @forelse ($recentRuns as $run)
                        <a href="{{ route('payroll.runs.show', $run) }}" class="block rounded-2xl border border-zinc-200 p-4 transition hover:border-zinc-400 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:border-zinc-500 dark:hover:bg-zinc-950/60">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-zinc-950 dark:text-white">{{ $run->employee?->full_name ?? __('Unknown employee') }}</p>
                                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $run->payrollPeriod?->period_name ?? '-' }}</p>
                                </div>
                                <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                    {{ __(ucfirst($run->calculation_status->value)) }}
                                </span>
                            </div>
                            <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Take home pay') }}: {{ number_format((float) $run->take_home_pay, 0, ',', '.') }}</p>
                        </a>
                    @empty
                        <div class="rounded-2xl border border-dashed border-zinc-300 px-4 py-8 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                            {{ __('No payroll runs processed yet.') }}
                        </div>
                    @endforelse
                </div>
            </aside>
        </section>

        <section x-show="focus === 'actions'" x-transition.opacity.duration.200ms class="grid gap-6 lg:grid-cols-3">
            <article class="rounded-3xl border border-zinc-200/70 bg-white p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900 lg:col-span-2">
                <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">{{ __('Operational queue') }}</h2>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Use this queue as the next-step checklist for routine payroll operations.') }}</p>

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <div class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-zinc-500 dark:text-zinc-400">{{ __('Review required') }}</p>
                        <p class="mt-3 text-3xl font-semibold text-zinc-950 dark:text-white">{{ number_format($payrollSnapshot['draft_run_count']) }}</p>
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Runs still waiting for approval, finalization, or payment completion.') }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-zinc-500 dark:text-zinc-400">{{ __('Export ready') }}</p>
                        <p class="mt-3 text-3xl font-semibold text-zinc-950 dark:text-white">{{ number_format($payrollSnapshot['finalized_period_count']) }}</p>
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Payroll periods that are ready for bank transfer, BPJS, and tax export.') }}</p>
                    </div>
                </div>

                <div class="mt-6 space-y-3">
                    <a href="{{ route('payroll.runs.index') }}" class="flex items-center justify-between rounded-2xl border border-zinc-200 px-4 py-4 transition hover:border-zinc-400 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:border-zinc-500 dark:hover:bg-zinc-950/60">
                        <div>
                            <p class="font-semibold text-zinc-950 dark:text-white">{{ __('Continue payroll approvals') }}</p>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Open pending runs and continue the approval workflow.') }}</p>
                        </div>
                        <span class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">{{ __('Open') }}</span>
                    </a>
                    <a href="{{ route('payroll.exports.index') }}" class="flex items-center justify-between rounded-2xl border border-zinc-200 px-4 py-4 transition hover:border-zinc-400 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:border-zinc-500 dark:hover:bg-zinc-950/60">
                        <div>
                            <p class="font-semibold text-zinc-950 dark:text-white">{{ __('Generate statutory exports') }}</p>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Download bank transfer, BPJS recap, and monthly or yearly PPh21 files.') }}</p>
                        </div>
                        <span class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">{{ __('Open') }}</span>
                    </a>
                    <a href="{{ route('notifications.index') }}" class="flex items-center justify-between rounded-2xl border border-zinc-200 px-4 py-4 transition hover:border-zinc-400 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:border-zinc-500 dark:hover:bg-zinc-950/60">
                        <div>
                            <p class="font-semibold text-zinc-950 dark:text-white">{{ __('Review notifications') }}</p>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Check published payslips, processed periods, and approval reminders.') }}</p>
                        </div>
                        <span class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">{{ __('Open') }}</span>
                    </a>
                </div>
            </article>

            <aside class="rounded-3xl border border-zinc-200/70 bg-white p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">{{ __('Context summary') }}</h2>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Fast reference for what is currently ready in the system.') }}</p>

                <div class="mt-5 space-y-3 text-sm">
                    <div class="rounded-2xl bg-zinc-50 px-4 py-3 text-zinc-700 dark:bg-zinc-950/60 dark:text-zinc-200">
                        {{ __('Role-based approvals, BPJS/PPh21 formulas, payslips, attendance sync, and statutory exports are active.') }}
                    </div>
                    <div class="rounded-2xl bg-zinc-50 px-4 py-3 text-zinc-700 dark:bg-zinc-950/60 dark:text-zinc-200">
                        {{ __('Use the language switcher in the header or sidebar to change the app language instantly.') }}
                    </div>
                    <div class="rounded-2xl bg-zinc-50 px-4 py-3 text-zinc-700 dark:bg-zinc-950/60 dark:text-zinc-200">
                        {{ __('Audit trail and notifications are already connected to key payroll workflow events.') }}
                    </div>
                </div>
            </aside>
        </section>
    </div>
</x-layouts::app>
