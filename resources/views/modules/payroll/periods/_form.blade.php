@php
    $isEditing = $period->exists;
    $wizardSteps = [
        1 => ['payroll_group_id', 'period_name', 'start_date', 'end_date'],
        2 => ['pay_date', 'status'],
    ];
    $initialStep = 1;

    foreach ($wizardSteps as $stepNumber => $fields) {
        if (collect($errors->keys())->intersect($fields)->isNotEmpty()) {
            $initialStep = $stepNumber;
            break;
        }
    }
@endphp

<form
    method="POST"
    action="{{ $isEditing ? route('payroll.periods.update', $period) : route('payroll.periods.store') }}"
    class="space-y-6"
    x-data="createPayrollPeriodWizard(@js([
        'initialStep' => $initialStep,
        'totalSteps' => 3,
        'activePalette' => [
            1 => 'border-cyan-400 bg-cyan-50 text-cyan-950 dark:border-cyan-500/30 dark:bg-cyan-500/10 dark:text-cyan-100',
            2 => 'border-amber-400 bg-amber-50 text-amber-950 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100',
            3 => 'border-rose-400 bg-rose-50 text-rose-950 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-100',
        ],
        'labels' => [
            'active' => __('Active'),
            'done' => __('Done'),
            'pending' => __('Pending'),
            'yes' => __('Yes'),
            'no' => __('No'),
            'notFilled' => __('Not filled yet'),
        ],
        'messages' => [
            'periodPayBeforeEnd' => __('Pay date is earlier than the period end date.'),
            'periodAdvancedWithoutPayDate' => __('Period status is already advanced but pay date is still empty.'),
            'periodLongRange' => __('This payroll period is longer than 40 days and may need to be reviewed.'),
            'periodNameMissing' => __('Period name is still empty.'),
            'periodNoCandidates' => __('No active employees are currently assigned to the selected payroll group.'),
        ],
        'candidatesByGroup' => $candidatePreview,
    ]))"
>
    @csrf
    @if ($isEditing)
        @method('PUT')
    @endif

    <section class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-zinc-950 dark:text-white">{{ __('Payroll period wizard') }}</p>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Set the period identity first, then confirm the pay schedule and status.') }}</p>
            </div>
            <span class="rounded-full border border-zinc-200 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500 dark:border-zinc-700 dark:text-zinc-300">
                <span x-text="step"></span>/<span x-text="totalSteps"></span>
            </span>
        </div>

        <div class="grid gap-3 md:grid-cols-3">
            <button type="button" @click="step = 1" :class="stepClasses(1)" class="rounded-2xl border px-4 py-3 text-left transition">
                <p class="text-xs font-semibold uppercase tracking-[0.2em]">{{ __('Step 1') }}</p>
                <div class="mt-2 flex items-center justify-between gap-2">
                    <p class="font-semibold">{{ __('Period identity') }}</p>
                    <span :class="statusClasses(1)" class="rounded-full px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.18em]" x-text="statusLabel(1)"></span>
                </div>
                <p class="mt-1 text-xs">{{ __('Group, name, and covered dates.') }}</p>
            </button>
            <button type="button" @click="step = 2" :class="stepClasses(2)" class="rounded-2xl border px-4 py-3 text-left transition">
                <p class="text-xs font-semibold uppercase tracking-[0.2em]">{{ __('Step 2') }}</p>
                <div class="mt-2 flex items-center justify-between gap-2">
                    <p class="font-semibold">{{ __('Pay schedule') }}</p>
                    <span :class="statusClasses(2)" class="rounded-full px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.18em]" x-text="statusLabel(2)"></span>
                </div>
                <p class="mt-1 text-xs">{{ __('Pay date and processing status.') }}</p>
            </button>
            <button type="button" @click="step = 3" :class="stepClasses(3)" class="rounded-2xl border px-4 py-3 text-left transition">
                <p class="text-xs font-semibold uppercase tracking-[0.2em]">{{ __('Step 3') }}</p>
                <div class="mt-2 flex items-center justify-between gap-2">
                    <p class="font-semibold">{{ __('Final review') }}</p>
                    <span :class="statusClasses(3)" class="rounded-full px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.18em]" x-text="statusLabel(3)"></span>
                </div>
                <p class="mt-1 text-xs">{{ __('Review all important inputs before saving.') }}</p>
            </button>
        </div>
    </section>

    <div class="rounded-2xl border border-cyan-200 bg-cyan-50/80 px-4 py-4 text-sm text-cyan-950 dark:border-cyan-500/20 dark:bg-cyan-500/10 dark:text-cyan-100">
        <p class="font-semibold">{{ __('Create one payroll period for each salary cycle that will be processed.') }}</p>
        <p class="mt-1 text-cyan-900/80 dark:text-cyan-100/80">{{ __('Set the working date range first, then set the pay date when salary will actually be paid.') }}</p>
    </div>

    <div class="grid gap-6 md:grid-cols-2" x-show="step === 1" x-cloak>
        <div>
            <label for="payroll_group_id" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Payroll group') }}</label>
            <select id="payroll_group_id" name="payroll_group_id" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white" required>
                <option value="">{{ __('Select group') }}</option>
                @foreach ($groups as $group)
                    <option value="{{ $group->id }}" @selected(old('payroll_group_id', $period->payroll_group_id) == $group->id)>{{ $group->name }}</option>
                @endforeach
            </select>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Choose the payroll group whose employees will be included in this period.') }}</p>
        </div>
        <div>
            <label for="period_name" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Period name') }}</label>
            <input id="period_name" name="period_name" type="text" value="{{ old('period_name', $period->period_name) }}" placeholder="{{ __('Salary April 2026') }}" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white" required>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Use a name that is easy to identify in approvals, exports, and payslips.') }}</p>
        </div>
        <div>
            <label for="start_date" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Start date') }}</label>
            <input id="start_date" name="start_date" type="date" value="{{ old('start_date', optional($period->start_date)->toDateString()) }}" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white" required>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('This is the first work date included in the payroll calculation.') }}</p>
        </div>
        <div>
            <label for="end_date" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('End date') }}</label>
            <input id="end_date" name="end_date" type="date" value="{{ old('end_date', optional($period->end_date)->toDateString()) }}" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white" required>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('This is the last work date included in the payroll period.') }}</p>
        </div>
    </div>

    <div class="grid gap-6 md:grid-cols-2" x-show="step === 2" x-cloak>
        <div>
            <label for="pay_date" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Pay date') }}</label>
            <input id="pay_date" name="pay_date" type="date" value="{{ old('pay_date', optional($period->pay_date)->toDateString()) }}" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Fill this with the date salary will be transferred or handed over to employees.') }}</p>
        </div>
        <div>
            <label for="status" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Status') }}</label>
            <select id="status" name="status" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(old('status', is_object($period->status) ? $period->status->value : $period->status) === $status->value)>{{ __(ucfirst($status->value)) }}</option>
                @endforeach
            </select>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Use draft for a new period. Move it forward only after payroll data is ready to process.') }}</p>
        </div>
    </div>

    <section class="space-y-6" x-show="step === 3" x-cloak>
        <div>
            <h2 class="text-lg font-semibold text-zinc-950 dark:text-white">{{ __('Final review') }}</h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Confirm the payroll period summary below before saving.') }}</p>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <article class="rounded-3xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Covered days') }}</p>
                <p class="mt-3 text-2xl font-semibold text-zinc-950 dark:text-white" x-text="periodLength() ?? '{{ __('Not filled yet') }}'"></p>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Estimated number of days included in this payroll period.') }}</p>
            </article>
            <article class="rounded-3xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Pay lag') }}</p>
                <p class="mt-3 text-2xl font-semibold text-zinc-950 dark:text-white" x-text="payLag() !== null ? `${payLag()} {{ __('days') }}` : '{{ __('Not filled yet') }}'"></p>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Shows how many days after period end the salary is planned to be paid.') }}</p>
            </article>
            <article class="rounded-3xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Completion') }}</p>
                <p class="mt-3 text-2xl font-semibold text-zinc-950 dark:text-white" x-text="`${ratio(2)}%`"></p>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Identity and pay schedule steps are already completed before final save.') }}</p>
            </article>
        </div>

        <section class="rounded-3xl border border-cyan-200 bg-cyan-50/70 p-5 dark:border-cyan-500/20 dark:bg-cyan-500/10">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-cyan-950 dark:text-cyan-50">{{ __('Payroll candidate preview') }}</h3>
                    <p class="mt-1 text-sm text-cyan-900/80 dark:text-cyan-100/80">{{ __('These active employees currently match the selected payroll group and are expected to be processed in this period.') }}</p>
                </div>
                <div class="grid gap-3 sm:grid-cols-3 lg:min-w-[520px]">
                    <div class="rounded-2xl bg-white/70 px-4 py-3 dark:bg-zinc-950/20">
                        <p class="text-xs uppercase tracking-[0.18em] text-cyan-800/70 dark:text-cyan-100/70">{{ __('Candidates') }}</p>
                        <p class="mt-2 text-2xl font-semibold text-cyan-950 dark:text-cyan-50" x-text="candidateCount()"></p>
                    </div>
                    <div class="rounded-2xl bg-white/70 px-4 py-3 dark:bg-zinc-950/20">
                        <p class="text-xs uppercase tracking-[0.18em] text-cyan-800/70 dark:text-cyan-100/70">{{ __('Base salary total') }}</p>
                        <p class="mt-2 text-lg font-semibold text-cyan-950 dark:text-cyan-50" x-text="currency(candidateSalaryTotal())"></p>
                    </div>
                    <div class="rounded-2xl bg-white/70 px-4 py-3 dark:bg-zinc-950/20">
                        <p class="text-xs uppercase tracking-[0.18em] text-cyan-800/70 dark:text-cyan-100/70">{{ __('Average salary') }}</p>
                        <p class="mt-2 text-lg font-semibold text-cyan-950 dark:text-cyan-50" x-text="candidateAverageSalary() !== null ? currency(candidateAverageSalary()) : '{{ __('Not filled yet') }}'"></p>
                    </div>
                </div>
            </div>

            <div class="mt-5 overflow-hidden rounded-2xl border border-cyan-200/70 bg-white/70 dark:border-cyan-500/20 dark:bg-zinc-950/20">
                <template x-if="candidateCount() > 0">
                    <div class="divide-y divide-cyan-100 dark:divide-cyan-500/10">
                        <template x-for="candidate in candidatePreviewLimit()" :key="candidate.id">
                            <div class="grid gap-3 px-4 py-3 text-sm md:grid-cols-[1.1fr_0.9fr_0.8fr_0.7fr]">
                                <div>
                                    <p class="font-semibold text-cyan-950 dark:text-cyan-50" x-text="candidate.name"></p>
                                    <p class="mt-1 text-xs text-cyan-800/70 dark:text-cyan-100/70" x-text="candidate.number"></p>
                                </div>
                                <p class="text-cyan-900/80 dark:text-cyan-100/80" x-text="candidate.organization || '{{ __('No organization') }}'"></p>
                                <p class="font-semibold text-cyan-950 dark:text-cyan-50" x-text="currency(Number(candidate.basicSalary || 0))"></p>
                                <p class="text-cyan-900/80 dark:text-cyan-100/80" x-text="candidate.taxStatus || '{{ __('No tax status') }}'"></p>
                            </div>
                        </template>
                    </div>
                </template>
                <template x-if="candidateCount() === 0">
                    <p class="px-4 py-8 text-center text-sm text-cyan-900/80 dark:text-cyan-100/80">{{ __('No candidates to preview for the selected payroll group.') }}</p>
                </template>
            </div>

            <p class="mt-3 text-xs text-cyan-900/70 dark:text-cyan-100/70" x-show="candidateCount() > 5" x-cloak>
                {{ __('Showing the first 5 candidates only. The full payroll process will include all matching candidates.') }}
            </p>
        </section>

        <div x-show="periodWarnings().length" x-cloak class="rounded-3xl border border-amber-300/60 bg-amber-50 p-5 dark:border-amber-500/20 dark:bg-amber-500/10">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-amber-950 dark:text-amber-100">{{ __('Preview warnings') }}</h3>
                    <p class="mt-1 text-sm text-amber-900/80 dark:text-amber-100/80">{{ __('Review these warnings before saving the payroll period.') }}</p>
                </div>
                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-amber-700 dark:bg-amber-500/20 dark:text-amber-200" x-text="periodWarnings().length"></span>
            </div>

            <ul class="mt-4 space-y-3">
                <template x-for="warning in periodWarnings()" :key="warning">
                    <li class="rounded-2xl bg-white/70 px-4 py-3 text-sm text-amber-950 dark:bg-zinc-950/20 dark:text-amber-100" x-text="warning"></li>
                </template>
            </ul>
        </div>

        <article class="rounded-3xl border border-rose-200 bg-rose-50/70 p-5 dark:border-rose-500/20 dark:bg-rose-500/10">
            <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-rose-800 dark:text-rose-200">{{ __('Period review') }}</h3>
            <dl class="mt-4 grid gap-3 md:grid-cols-2 text-sm">
                <div class="flex items-start justify-between gap-4 rounded-2xl bg-white/60 px-4 py-3 dark:bg-zinc-950/20">
                    <dt class="text-rose-900/70 dark:text-rose-100/70">{{ __('Payroll group') }}</dt>
                    <dd class="text-right font-semibold text-rose-950 dark:text-rose-50" x-text="option('payroll_group_id')"></dd>
                </div>
                <div class="flex items-start justify-between gap-4 rounded-2xl bg-white/60 px-4 py-3 dark:bg-zinc-950/20">
                    <dt class="text-rose-900/70 dark:text-rose-100/70">{{ __('Period name') }}</dt>
                    <dd class="text-right font-semibold text-rose-950 dark:text-rose-50" x-text="text('period_name')"></dd>
                </div>
                <div class="flex items-start justify-between gap-4 rounded-2xl bg-white/60 px-4 py-3 dark:bg-zinc-950/20">
                    <dt class="text-rose-900/70 dark:text-rose-100/70">{{ __('Start date') }}</dt>
                    <dd class="text-right font-semibold text-rose-950 dark:text-rose-50" x-text="text('start_date')"></dd>
                </div>
                <div class="flex items-start justify-between gap-4 rounded-2xl bg-white/60 px-4 py-3 dark:bg-zinc-950/20">
                    <dt class="text-rose-900/70 dark:text-rose-100/70">{{ __('End date') }}</dt>
                    <dd class="text-right font-semibold text-rose-950 dark:text-rose-50" x-text="text('end_date')"></dd>
                </div>
                <div class="flex items-start justify-between gap-4 rounded-2xl bg-white/60 px-4 py-3 dark:bg-zinc-950/20">
                    <dt class="text-rose-900/70 dark:text-rose-100/70">{{ __('Pay date') }}</dt>
                    <dd class="text-right font-semibold text-rose-950 dark:text-rose-50" x-text="text('pay_date')"></dd>
                </div>
                <div class="flex items-start justify-between gap-4 rounded-2xl bg-white/60 px-4 py-3 dark:bg-zinc-950/20">
                    <dt class="text-rose-900/70 dark:text-rose-100/70">{{ __('Status') }}</dt>
                    <dd class="text-right font-semibold text-rose-950 dark:text-rose-50" x-text="option('status')"></dd>
                </div>
            </dl>
        </article>
    </section>

    <div class="flex flex-wrap items-center justify-between gap-3 rounded-3xl border border-zinc-200 bg-white px-5 py-4 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-center gap-3">
            <button type="button" x-show="step > 1" @click="step = Math.max(1, step - 1)" class="rounded-full border border-zinc-300 px-5 py-3 text-sm font-semibold text-zinc-800 transition hover:border-zinc-950 dark:border-zinc-700 dark:text-zinc-100 dark:hover:border-white">
                {{ __('Back') }}
            </button>
            <button type="button" x-show="step < totalSteps" @click="step = Math.min(totalSteps, step + 1)" class="rounded-full bg-cyan-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-cyan-500">
                {{ __('Continue') }}
            </button>
        </div>

        <p class="text-sm text-zinc-500 dark:text-zinc-400" x-text="step === 1 ? '{{ __('Set the covered payroll dates first.') }}' : (step === 2 ? '{{ __('Review the pay date and continue to final check.') }}' : '{{ __('Everything important is summarized here before saving.') }}')"></p>

        <div class="flex items-center gap-3">
            <button type="submit" x-show="step === totalSteps" class="rounded-full bg-zinc-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-zinc-800 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">
                {{ $isEditing ? __('Update period') : __('Create period') }}
            </button>
            <a href="{{ route('payroll.periods.index') }}" class="rounded-full border border-zinc-300 px-5 py-3 text-sm font-semibold text-zinc-800 transition hover:border-zinc-950 dark:border-zinc-700 dark:text-zinc-100 dark:hover:border-white">
                {{ __('Cancel') }}
            </a>
        </div>
    </div>
</form>
