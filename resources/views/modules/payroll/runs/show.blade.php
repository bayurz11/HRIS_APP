@php
    $status = $run->calculation_status->value;
    $nextPendingStep = $run->approvalSteps->firstWhere('status', 'pending');
    $latestReturnLog = $run->workflowLogs->firstWhere('action', 'returned_to_draft');

    $nextActionMap = [
        'draft' => __('This payroll is still in draft. Reprocess the payroll period if the calculation has not been generated yet.'),
        'calculated' => $nextPendingStep
            ? __('This payroll is waiting for approval from :role.', ['role' => $nextPendingStep->role_name])
            : __('This payroll is ready to move to the next approval step.'),
        'approved' => __('This payroll has been approved and is ready to be marked as paid.'),
        'paid' => __('This payroll is finished. You can download or share the payslip if needed.'),
    ];

    $statusSteps = [
        ['key' => 'draft', 'label' => __('Draft')],
        ['key' => 'calculated', 'label' => __('Calculated')],
        ['key' => 'approved', 'label' => __('Approved')],
        ['key' => 'paid', 'label' => __('Paid')],
    ];
@endphp

<x-layouts::app :title="$run->payroll_number">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <section class="rounded-3xl border border-zinc-200/70 bg-white p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                <div class="max-w-3xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-cyan-600 dark:text-cyan-300">{{ __('Payroll run') }}</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight text-zinc-950 dark:text-white">{{ $run->payroll_number }}</h1>
                    <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-300">
                        {{ $run->employee?->full_name }} | {{ $run->payrollPeriod?->period_name }} | {{ $run->payrollPeriod?->payrollGroup?->name }}
                    </p>
                    <div class="mt-4 rounded-2xl border border-amber-300/50 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-100">
                        <strong>{{ __('Next step') }}:</strong> {{ $nextActionMap[$status] ?? __('Review the payroll detail before taking action.') }}
                    </div>
                    @if ($status === 'draft' && $latestReturnLog)
                        <div class="mt-4 rounded-2xl border border-rose-300/50 bg-rose-50 px-4 py-3 text-sm text-rose-900 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-100">
                            <strong>{{ __('Latest revision reason') }}:</strong> {{ $latestReturnLog->notes ?: __('No reason was written.') }}
                        </div>
                    @endif
                </div>

                <div class="flex max-w-xl flex-wrap gap-2">
                    @if ($run->calculation_status->value === 'calculated')
                        <form method="POST" action="{{ route('payroll.runs.approve', $run) }}" class="flex-1 space-y-2 rounded-2xl border border-emerald-200/70 bg-emerald-50/60 p-4 dark:border-emerald-500/20 dark:bg-emerald-500/10">
                            @csrf
                            <label for="approval_notes" class="block text-xs font-semibold uppercase tracking-[0.18em] text-emerald-800 dark:text-emerald-200">{{ __('Approval comment') }}</label>
                            <textarea id="approval_notes" name="approval_notes" rows="3" placeholder="{{ __('Example: salary items and attendance are already consistent.') }}" class="w-full rounded-2xl border border-emerald-200 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-emerald-500/20 dark:bg-zinc-900 dark:text-white">{{ old('approval_notes') }}</textarea>
                            <button type="submit" class="rounded-full bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-500">{{ __('Approve payroll') }}</button>
                        </form>
                    @endif

                    @if (in_array($run->calculation_status->value, ['calculated', 'approved'], true))
                        <form method="POST" action="{{ route('payroll.runs.return', $run) }}" class="flex-1 space-y-2 rounded-2xl border border-rose-200/70 bg-rose-50/60 p-4 dark:border-rose-500/20 dark:bg-rose-500/10">
                            @csrf
                            <label for="return_reason" class="block text-xs font-semibold uppercase tracking-[0.18em] text-rose-800 dark:text-rose-200">{{ __('Revision reason') }}</label>
                            <textarea id="return_reason" name="return_reason" rows="3" required placeholder="{{ __('Explain clearly what must be corrected before payroll is submitted again.') }}" class="w-full rounded-2xl border border-rose-200 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-rose-500/20 dark:bg-zinc-900 dark:text-white">{{ old('return_reason') }}</textarea>
                            <button type="submit" class="rounded-full border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-800 transition hover:border-zinc-950 dark:border-zinc-700 dark:text-zinc-100 dark:hover:border-white">{{ __('Return to draft') }}</button>
                        </form>
                    @endif

                    @if ($run->calculation_status->value === 'approved')
                        <form method="POST" action="{{ route('payroll.runs.mark-paid', $run) }}">
                            @csrf
                            <button type="submit" class="rounded-full bg-zinc-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-zinc-800 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">{{ __('Mark as paid') }}</button>
                        </form>
                    @endif

                    @if ($run->payslip && ! $run->payslip->is_published)
                        <form method="POST" action="{{ route('payroll.runs.publish-payslip', $run) }}">
                            @csrf
                            <button type="submit" class="rounded-full border border-emerald-300 px-4 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-50 dark:border-emerald-500/20 dark:text-emerald-300 dark:hover:bg-emerald-500/10">{{ __('Publish payslip') }}</button>
                        </form>
                    @endif

                    @if ($run->payslip && $run->payslip->canBeDownloaded())
                        <a href="{{ route('payroll.payslips.download', $run->payslip) }}" class="rounded-full border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-800 transition hover:border-zinc-950 dark:border-zinc-700 dark:text-zinc-100 dark:hover:border-white">
                            {{ __('Download payslip') }}
                        </a>
                    @endif
                </div>
            </div>

            <div class="mt-6 grid gap-3 md:grid-cols-4">
                @foreach ($statusSteps as $step)
                    @php
                        $currentIndex = collect($statusSteps)->search(fn ($item) => $item['key'] === $status);
                        $stepIndex = $loop->index;
                        $isActive = $status === $step['key'];
                        $isDone = $currentIndex !== false && $stepIndex <= $currentIndex;
                    @endphp
                    <div class="rounded-2xl border px-4 py-3 {{ $isActive ? 'border-emerald-400 bg-emerald-50 dark:border-emerald-500/30 dark:bg-emerald-500/10' : ($isDone ? 'border-zinc-300 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-950/60' : 'border-zinc-200 dark:border-zinc-800') }}">
                        <p class="text-xs uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Step') }} {{ $loop->iteration }}</p>
                        <p class="mt-2 font-semibold text-zinc-950 dark:text-white">{{ $step['label'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-zinc-200/70 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs uppercase tracking-[0.22em] text-zinc-500 dark:text-zinc-400">{{ __('Gross salary') }}</p>
                <p class="mt-3 text-3xl font-semibold text-zinc-950 dark:text-white">{{ number_format((float) $run->gross_salary, 0, ',', '.') }}</p>
            </article>
            <article class="rounded-2xl border border-zinc-200/70 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs uppercase tracking-[0.22em] text-zinc-500 dark:text-zinc-400">{{ __('Deduction') }}</p>
                <p class="mt-3 text-3xl font-semibold text-zinc-950 dark:text-white">{{ number_format((float) $run->total_deduction, 0, ',', '.') }}</p>
            </article>
            <article class="rounded-2xl border border-zinc-200/70 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs uppercase tracking-[0.22em] text-zinc-500 dark:text-zinc-400">PPh21</p>
                <p class="mt-3 text-3xl font-semibold text-zinc-950 dark:text-white">{{ number_format((float) $run->total_pph21, 0, ',', '.') }}</p>
            </article>
            <article class="rounded-2xl border border-zinc-200/70 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs uppercase tracking-[0.22em] text-zinc-500 dark:text-zinc-400">{{ __('Take home pay') }}</p>
                <p class="mt-3 text-3xl font-semibold text-zinc-950 dark:text-white">{{ number_format((float) $run->take_home_pay, 0, ',', '.') }}</p>
            </article>
        </section>

        <section class="grid gap-4 md:grid-cols-3">
            <article class="rounded-2xl border border-zinc-200/70 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs uppercase tracking-[0.22em] text-zinc-500 dark:text-zinc-400">{{ __('Overtime input') }}</p>
                <p class="mt-3 text-3xl font-semibold text-zinc-950 dark:text-white">{{ number_format((float) $run->total_overtime, 0, ',', '.') }}</p>
            </article>
            <article class="rounded-2xl border border-zinc-200/70 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs uppercase tracking-[0.22em] text-zinc-500 dark:text-zinc-400">{{ __('Loan deduction') }}</p>
                <p class="mt-3 text-3xl font-semibold text-zinc-950 dark:text-white">{{ number_format((float) $run->total_loan_deduction, 0, ',', '.') }}</p>
            </article>
            <article class="rounded-2xl border border-zinc-200/70 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs uppercase tracking-[0.22em] text-zinc-500 dark:text-zinc-400">{{ __('Absence deduction') }}</p>
                <p class="mt-3 text-3xl font-semibold text-zinc-950 dark:text-white">{{ number_format((float) $run->total_absence_deduction, 0, ',', '.') }}</p>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.35fr_1fr]">
            <article class="rounded-3xl border border-zinc-200/70 bg-white p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">{{ __('Payroll items') }}</h2>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('This table shows the components that build the employee payslip.') }}</p>
                    </div>
                </div>

                <div class="mt-5 overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-700">
                    <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                        <thead class="bg-zinc-50 text-left text-xs uppercase tracking-[0.18em] text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400">
                            <tr>
                                <th class="px-4 py-3">{{ __('Component') }}</th>
                                <th class="px-4 py-3">{{ __('Type') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            @foreach ($run->items->sortBy('sort_order') as $item)
                                <tr>
                                    <td class="px-4 py-4">
                                        <p class="font-medium text-zinc-950 dark:text-white">{{ $item->component_name }}</p>
                                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $item->component_code }}</p>
                                    </td>
                                    <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ strtoupper($item->component_type) }}</td>
                                    <td class="px-4 py-4 text-right text-zinc-600 dark:text-zinc-300">{{ number_format((float) $item->amount, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="space-y-6">
                <div class="rounded-3xl border border-zinc-200/70 bg-white p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900">
                    <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">{{ __('Approval timeline') }}</h2>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('See who has already approved and who is still expected to act.') }}</p>

                    <div class="mt-5 space-y-3">
                        @foreach ($run->approvalSteps as $step)
                            <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="font-semibold text-zinc-950 dark:text-white">{{ __('Step :step - :role', ['step' => $step->step_order, 'role' => $step->role_name]) }}</p>
                                    <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">{{ __(ucfirst($step->status)) }}</span>
                                </div>
                                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">
                                    {{ $step->actor?->name ?? __('Waiting for action') }}{{ $step->acted_at ? ' | '.$step->acted_at->format('d M Y H:i') : '' }}
                                </p>
                                @if ($step->notes)
                                    <p class="mt-3 rounded-2xl bg-zinc-50 px-3 py-3 text-sm text-zinc-700 dark:bg-zinc-950/60 dark:text-zinc-200">{{ $step->notes }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-3xl border border-zinc-200/70 bg-white p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900">
                    <h3 class="text-lg font-semibold text-zinc-950 dark:text-white">{{ __('Workflow log') }}</h3>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Use this history to explain what already happened in this payroll run.') }}</p>

                    <div class="mt-5 space-y-4">
                        @forelse ($run->workflowLogs as $log)
                            <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="font-semibold capitalize text-zinc-950 dark:text-white">{{ str_replace('_', ' ', $log->action) }}</p>
                                    <p class="text-xs uppercase tracking-[0.16em] text-zinc-500 dark:text-zinc-400">{{ $log->created_at->format('d M Y H:i') }}</p>
                                </div>
                                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">
                                    {{ $log->actor?->name ?? __('System') }} | {{ $log->status_before ?: __('none') }} -> {{ $log->status_after ?: __('none') }}
                                </p>
                                @if ($log->notes)
                                    <p class="mt-3 rounded-2xl bg-zinc-50 px-3 py-3 text-sm text-zinc-700 dark:bg-zinc-950/60 dark:text-zinc-200">{{ $log->notes }}</p>
                                @endif
                            </div>
                        @empty
                            <p class="rounded-2xl border border-dashed border-zinc-300 px-4 py-8 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                                {{ __('No workflow log for this payroll run yet.') }}
                            </p>
                        @endforelse
                    </div>
                </div>
            </article>
        </section>
    </div>
</x-layouts::app>
