@php
    $isEditing = $input->exists;
    $wizardSteps = [
        1 => ['payroll_period_id', 'employee_id', 'payroll_component_id'],
        2 => ['amount', 'quantity', 'rate', 'notes'],
        3 => ['is_taxable', 'is_bpjs_applicable', 'is_active'],
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
    action="{{ $isEditing ? route('payroll.inputs.update', $input) : route('payroll.inputs.store') }}"
    class="space-y-6"
    x-data="createPayrollInputWizard(@js([
        'initialStep' => $initialStep,
        'totalSteps' => 4,
        'activePalette' => [
            1 => 'border-cyan-400 bg-cyan-50 text-cyan-950 dark:border-cyan-500/30 dark:bg-cyan-500/10 dark:text-cyan-100',
            2 => 'border-amber-400 bg-amber-50 text-amber-950 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100',
            3 => 'border-sky-400 bg-sky-50 text-sky-950 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-100',
            4 => 'border-rose-400 bg-rose-50 text-rose-950 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-100',
        ],
        'labels' => [
            'active' => __('Active'),
            'done' => __('Done'),
            'pending' => __('Pending'),
            'yes' => __('Yes'),
            'no' => __('No'),
            'notFilled' => __('Not filled yet'),
            'fixedAmountMode' => __('Fixed amount'),
            'quantityRateMode' => __('Quantity x rate'),
        ],
        'messages' => [
            'inputIncompleteAmount' => __('Final amount cannot be estimated yet because quantity or rate is still empty.'),
            'inputFixedOverridesFormula' => __('Fixed amount is filled, so quantity x rate will be ignored.'),
            'inputNotesMissing' => __('Notes are still empty, so approvers may not know why this payroll input was added.'),
            'inputInactiveFlags' => __('Input is inactive, so tax or BPJS flags will not affect payroll processing yet.'),
            'inputGroupMismatch' => __('Selected employee payroll group is different from the selected payroll period group.'),
            'inputNoEmployeeProfile' => __('Selected employee does not have a payroll profile yet.'),
            'inputInactiveNoImpact' => __('Input is inactive, so it will not change estimated take-home pay yet.'),
            'inputNoTakeHomeImpact' => __('Selected component does not affect take-home pay.'),
        ],
        'preview' => $previewPayload,
    ]))"
>
    @csrf
    @if ($isEditing)
        @method('PUT')
    @endif

    <section class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-zinc-950 dark:text-white">{{ __('Payroll input wizard') }}</p>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Choose the target first, then fill the value and confirm the calculation options.') }}</p>
            </div>
            <span class="rounded-full border border-zinc-200 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500 dark:border-zinc-700 dark:text-zinc-300">
                <span x-text="step"></span>/<span x-text="totalSteps"></span>
            </span>
        </div>

        <div class="grid gap-3 md:grid-cols-4">
            <button type="button" @click="step = 1" :class="stepClasses(1)" class="rounded-2xl border px-4 py-3 text-left transition">
                <p class="text-xs font-semibold uppercase tracking-[0.2em]">{{ __('Step 1') }}</p>
                <div class="mt-2 flex items-center justify-between gap-2">
                    <p class="font-semibold">{{ __('Target payroll') }}</p>
                    <span :class="statusClasses(1)" class="rounded-full px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.18em]" x-text="statusLabel(1)"></span>
                </div>
                <p class="mt-1 text-xs">{{ __('Period, employee, and component.') }}</p>
            </button>
            <button type="button" @click="step = 2" :class="stepClasses(2)" class="rounded-2xl border px-4 py-3 text-left transition">
                <p class="text-xs font-semibold uppercase tracking-[0.2em]">{{ __('Step 2') }}</p>
                <div class="mt-2 flex items-center justify-between gap-2">
                    <p class="font-semibold">{{ __('Value details') }}</p>
                    <span :class="statusClasses(2)" class="rounded-full px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.18em]" x-text="statusLabel(2)"></span>
                </div>
                <p class="mt-1 text-xs">{{ __('Amount, quantity, rate, and notes.') }}</p>
            </button>
            <button type="button" @click="step = 3" :class="stepClasses(3)" class="rounded-2xl border px-4 py-3 text-left transition">
                <p class="text-xs font-semibold uppercase tracking-[0.2em]">{{ __('Step 3') }}</p>
                <div class="mt-2 flex items-center justify-between gap-2">
                    <p class="font-semibold">{{ __('Calculation options') }}</p>
                    <span :class="statusClasses(3)" class="rounded-full px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.18em]" x-text="statusLabel(3)"></span>
                </div>
                <p class="mt-1 text-xs">{{ __('Tax, BPJS, and active flags.') }}</p>
            </button>
            <button type="button" @click="step = 4" :class="stepClasses(4)" class="rounded-2xl border px-4 py-3 text-left transition">
                <p class="text-xs font-semibold uppercase tracking-[0.2em]">{{ __('Step 4') }}</p>
                <div class="mt-2 flex items-center justify-between gap-2">
                    <p class="font-semibold">{{ __('Final review') }}</p>
                    <span :class="statusClasses(4)" class="rounded-full px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.18em]" x-text="statusLabel(4)"></span>
                </div>
                <p class="mt-1 text-xs">{{ __('Review all important inputs before saving.') }}</p>
            </button>
        </div>
    </section>

    <div class="rounded-2xl border border-cyan-200 bg-cyan-50/80 px-4 py-4 text-sm text-cyan-950 dark:border-cyan-500/20 dark:bg-cyan-500/10 dark:text-cyan-100">
        <p class="font-semibold">{{ __('Use this form for additional payroll values such as overtime, bonus, reimbursement, deductions, or corrections.') }}</p>
        <p class="mt-1 text-cyan-900/80 dark:text-cyan-100/80">{{ __('If the value is final, fill the amount only. If the value needs multiplication, use quantity and rate.') }}</p>
    </div>

    <div class="grid gap-6 md:grid-cols-2" x-show="step === 1" x-cloak>
        <div>
            <label for="payroll_period_id" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Payroll period') }}</label>
            <select id="payroll_period_id" name="payroll_period_id" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white" required>
                <option value="">{{ __('Select period') }}</option>
                @foreach ($periods as $period)
                    <option value="{{ $period->id }}" @selected(old('payroll_period_id', $input->payroll_period_id) == $period->id)>{{ $period->period_name }} - {{ $period->payrollGroup?->name ?? __('No group') }}</option>
                @endforeach
            </select>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Choose the payroll period that should receive this extra value.') }}</p>
        </div>
        <div>
            <label for="employee_id" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Employee') }}</label>
            <select id="employee_id" name="employee_id" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white" required>
                <option value="">{{ __('Select employee') }}</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" @selected(old('employee_id', $input->employee_id) == $employee->id)>{{ $employee->full_name }} - {{ $employee->employee_number }}</option>
                @endforeach
            </select>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Select the employee who should receive this payroll adjustment.') }}</p>
        </div>
        <div>
            <label for="payroll_component_id" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Variable component') }}</label>
            <select id="payroll_component_id" name="payroll_component_id" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white" required>
                <option value="">{{ __('Select component') }}</option>
                @foreach ($components as $component)
                    <option value="{{ $component->id }}" @selected(old('payroll_component_id', $input->payroll_component_id) == $component->id)>{{ $component->name }} - {{ strtoupper($component->category) }}</option>
                @endforeach
            </select>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Pick the payroll component that best matches the purpose of this input.') }}</p>
        </div>
    </div>

    <div class="grid gap-6 md:grid-cols-2" x-show="step === 2" x-cloak>
        <div>
            <label for="amount" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Fixed amount') }}</label>
            <input id="amount" name="amount" type="number" min="0" step="0.01" value="{{ old('amount', $input->amount) }}" placeholder="150000" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Fill `amount` for a final value. Leave it empty to use `quantity x rate`.') }}</p>
        </div>
        <div>
            <label for="quantity" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Quantity') }}</label>
            <input id="quantity" name="quantity" type="number" min="0.01" step="0.01" value="{{ old('quantity', $input->quantity ?? 1) }}" placeholder="10" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Use quantity for units such as hours, days, trips, or items.') }}</p>
        </div>
        <div>
            <label for="rate" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Rate') }}</label>
            <input id="rate" name="rate" type="number" min="0" step="0.01" value="{{ old('rate', $input->rate) }}" placeholder="15000" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Use rate for the value of each unit, for example amount per hour or per day.') }}</p>
        </div>
    </div>

    <div x-show="step === 2" x-cloak>
        <label for="notes" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Notes') }}</label>
        <textarea id="notes" name="notes" rows="4" placeholder="{{ __('Example: overtime for weekend deployment') }}" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">{{ old('notes', $input->notes) }}</textarea>
        <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Add a short reason so approvers and auditors understand why this input exists.') }}</p>
    </div>

    <div class="grid gap-4 md:grid-cols-3" x-show="step === 3" x-cloak>
        <label class="flex items-center gap-3 rounded-2xl border border-zinc-200 px-4 py-3 dark:border-zinc-700">
            <input name="is_taxable" type="checkbox" value="1" @checked(old('is_taxable', $input->is_taxable ?? false)) class="size-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500">
            <span class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Taxable') }}</span>
        </label>
        <label class="flex items-center gap-3 rounded-2xl border border-zinc-200 px-4 py-3 dark:border-zinc-700">
            <input name="is_bpjs_applicable" type="checkbox" value="1" @checked(old('is_bpjs_applicable', $input->is_bpjs_applicable ?? false)) class="size-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500">
            <span class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('BPJS applicable') }}</span>
        </label>
        <label class="flex items-center gap-3 rounded-2xl border border-zinc-200 px-4 py-3 dark:border-zinc-700">
            <input name="is_active" type="checkbox" value="1" @checked(old('is_active', $input->is_active ?? true)) class="size-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500">
            <span class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Active for processing') }}</span>
        </label>
    </div>
    <p class="text-xs text-zinc-500 dark:text-zinc-400" x-show="step === 3" x-cloak>{{ __('Turn on only the flags that should affect tax, BPJS, and payroll calculation in this period.') }}</p>

    <section class="space-y-6" x-show="step === 4" x-cloak>
        <div>
            <h2 class="text-lg font-semibold text-zinc-950 dark:text-white">{{ __('Final review') }}</h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Check the payroll input summary below before saving it to the selected period.') }}</p>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <article class="rounded-3xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Calculation mode') }}</p>
                <p class="mt-3 text-2xl font-semibold text-zinc-950 dark:text-white" x-text="calculationMode()"></p>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Shows whether payroll will use fixed amount or quantity multiplied by rate.') }}</p>
            </article>
            <article class="rounded-3xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Estimated applied amount') }}</p>
                <p class="mt-3 text-2xl font-semibold text-zinc-950 dark:text-white" x-text="estimatedAmount() !== null ? currency(estimatedAmount()) : '{{ __('Not filled yet') }}'"></p>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Estimated amount that will be inserted into payroll processing if this input is active.') }}</p>
            </article>
            <article class="rounded-3xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Completion') }}</p>
                <p class="mt-3 text-2xl font-semibold text-zinc-950 dark:text-white" x-text="`${ratio(3)}%`"></p>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Target, value, and calculation option steps are already completed before final save.') }}</p>
            </article>
        </div>

        <section class="rounded-3xl border border-emerald-200 bg-emerald-50/70 p-5 dark:border-emerald-500/20 dark:bg-emerald-500/10">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-emerald-950 dark:text-emerald-50">{{ __('Take-home pay simulation') }}</h3>
                    <p class="mt-1 text-sm text-emerald-900/80 dark:text-emerald-100/80">{{ __('This preview estimates the direct effect of this input before the payroll run is processed.') }}</p>
                </div>
                <span
                    class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em]"
                    :class="periodEmployeeGroupMatches() ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-200' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-200'"
                    x-text="periodEmployeeGroupMatches() ? '{{ __('Group matched') }}' : '{{ __('Group mismatch') }}'"
                ></span>
            </div>

            <div class="mt-5 grid gap-3 md:grid-cols-4">
                <div class="rounded-2xl bg-white/70 px-4 py-3 dark:bg-zinc-950/20">
                    <p class="text-xs uppercase tracking-[0.18em] text-emerald-800/70 dark:text-emerald-100/70">{{ __('Base take-home estimate') }}</p>
                    <p class="mt-2 text-lg font-semibold text-emerald-950 dark:text-emerald-50" x-text="estimatedBaseTakeHome() !== null ? currency(estimatedBaseTakeHome()) : '{{ __('Not filled yet') }}'"></p>
                </div>
                <div class="rounded-2xl bg-white/70 px-4 py-3 dark:bg-zinc-950/20">
                    <p class="text-xs uppercase tracking-[0.18em] text-emerald-800/70 dark:text-emerald-100/70">{{ __('Input impact') }}</p>
                    <p class="mt-2 text-lg font-semibold text-emerald-950 dark:text-emerald-50" x-text="currency(estimatedTakeHomeDelta())"></p>
                </div>
                <div class="rounded-2xl bg-white/70 px-4 py-3 dark:bg-zinc-950/20">
                    <p class="text-xs uppercase tracking-[0.18em] text-emerald-800/70 dark:text-emerald-100/70">{{ __('Estimated after input') }}</p>
                    <p class="mt-2 text-lg font-semibold text-emerald-950 dark:text-emerald-50" x-text="estimatedTakeHomeAfterInput() !== null ? currency(estimatedTakeHomeAfterInput()) : '{{ __('Not filled yet') }}'"></p>
                </div>
                <div class="rounded-2xl bg-white/70 px-4 py-3 dark:bg-zinc-950/20">
                    <p class="text-xs uppercase tracking-[0.18em] text-emerald-800/70 dark:text-emerald-100/70">{{ __('Component category') }}</p>
                    <p class="mt-2 text-lg font-semibold text-emerald-950 dark:text-emerald-50" x-text="selectedComponent()?.category || '{{ __('Not filled yet') }}'"></p>
                </div>
            </div>

            <div class="mt-5 grid gap-3 md:grid-cols-3">
                <div class="rounded-2xl border border-emerald-200/70 bg-white/60 px-4 py-3 text-sm dark:border-emerald-500/20 dark:bg-zinc-950/20">
                    <p class="text-xs uppercase tracking-[0.18em] text-emerald-800/70 dark:text-emerald-100/70">{{ __('Selected period') }}</p>
                    <p class="mt-2 font-semibold text-emerald-950 dark:text-emerald-50" x-text="selectedPeriod()?.name || '{{ __('Not filled yet') }}'"></p>
                    <p class="mt-1 text-xs text-emerald-900/70 dark:text-emerald-100/70" x-text="selectedPeriod()?.groupName || '{{ __('No group') }}'"></p>
                </div>
                <div class="rounded-2xl border border-emerald-200/70 bg-white/60 px-4 py-3 text-sm dark:border-emerald-500/20 dark:bg-zinc-950/20">
                    <p class="text-xs uppercase tracking-[0.18em] text-emerald-800/70 dark:text-emerald-100/70">{{ __('Selected employee') }}</p>
                    <p class="mt-2 font-semibold text-emerald-950 dark:text-emerald-50" x-text="selectedEmployee()?.name || '{{ __('Not filled yet') }}'"></p>
                    <p class="mt-1 text-xs text-emerald-900/70 dark:text-emerald-100/70" x-text="selectedEmployee()?.groupName || '{{ __('No payroll group') }}'"></p>
                </div>
                <div class="rounded-2xl border border-emerald-200/70 bg-white/60 px-4 py-3 text-sm dark:border-emerald-500/20 dark:bg-zinc-950/20">
                    <p class="text-xs uppercase tracking-[0.18em] text-emerald-800/70 dark:text-emerald-100/70">{{ __('Selected component') }}</p>
                    <p class="mt-2 font-semibold text-emerald-950 dark:text-emerald-50" x-text="selectedComponent()?.name || '{{ __('Not filled yet') }}'"></p>
                    <p class="mt-1 text-xs text-emerald-900/70 dark:text-emerald-100/70" x-text="selectedComponent()?.affectsTakeHomePay ? '{{ __('Affects take-home pay') }}' : '{{ __('Does not affect take-home pay') }}'"></p>
                </div>
            </div>
        </section>

        <div x-show="payrollInputWarnings().length" x-cloak class="rounded-3xl border border-amber-300/60 bg-amber-50 p-5 dark:border-amber-500/20 dark:bg-amber-500/10">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-amber-950 dark:text-amber-100">{{ __('Preview warnings') }}</h3>
                    <p class="mt-1 text-sm text-amber-900/80 dark:text-amber-100/80">{{ __('These warnings help you catch ambiguous payroll values before they affect payroll processing.') }}</p>
                </div>
                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-amber-700 dark:bg-amber-500/20 dark:text-amber-200" x-text="payrollInputWarnings().length"></span>
            </div>

            <ul class="mt-4 space-y-3">
                <template x-for="warning in payrollInputWarnings()" :key="warning">
                    <li class="rounded-2xl bg-white/70 px-4 py-3 text-sm text-amber-950 dark:bg-zinc-950/20 dark:text-amber-100" x-text="warning"></li>
                </template>
            </ul>
        </div>

        <div class="grid gap-4 xl:grid-cols-3">
            <article class="rounded-3xl border border-cyan-200 bg-cyan-50/70 p-5 dark:border-cyan-500/20 dark:bg-cyan-500/10">
                <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-cyan-800 dark:text-cyan-200">{{ __('Target review') }}</h3>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-cyan-900/70 dark:text-cyan-100/70">{{ __('Payroll period') }}</dt>
                        <dd class="text-right font-semibold text-cyan-950 dark:text-cyan-50" x-text="option('payroll_period_id')"></dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-cyan-900/70 dark:text-cyan-100/70">{{ __('Employee') }}</dt>
                        <dd class="text-right font-semibold text-cyan-950 dark:text-cyan-50" x-text="option('employee_id')"></dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-cyan-900/70 dark:text-cyan-100/70">{{ __('Variable component') }}</dt>
                        <dd class="text-right font-semibold text-cyan-950 dark:text-cyan-50" x-text="option('payroll_component_id')"></dd>
                    </div>
                </dl>
            </article>

            <article class="rounded-3xl border border-amber-200 bg-amber-50/70 p-5 dark:border-amber-500/20 dark:bg-amber-500/10">
                <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-amber-800 dark:text-amber-200">{{ __('Value review') }}</h3>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-amber-900/70 dark:text-amber-100/70">{{ __('Fixed amount') }}</dt>
                        <dd class="text-right font-semibold text-amber-950 dark:text-amber-50" x-text="text('amount')"></dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-amber-900/70 dark:text-amber-100/70">{{ __('Quantity') }}</dt>
                        <dd class="text-right font-semibold text-amber-950 dark:text-amber-50" x-text="text('quantity')"></dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-amber-900/70 dark:text-amber-100/70">{{ __('Rate') }}</dt>
                        <dd class="text-right font-semibold text-amber-950 dark:text-amber-50" x-text="text('rate')"></dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-amber-900/70 dark:text-amber-100/70">{{ __('Notes') }}</dt>
                        <dd class="text-right font-semibold text-amber-950 dark:text-amber-50" x-text="text('notes')"></dd>
                    </div>
                </dl>
            </article>

            <article class="rounded-3xl border border-sky-200 bg-sky-50/70 p-5 dark:border-sky-500/20 dark:bg-sky-500/10">
                <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-sky-800 dark:text-sky-200">{{ __('Option review') }}</h3>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-sky-900/70 dark:text-sky-100/70">{{ __('Taxable') }}</dt>
                        <dd class="text-right font-semibold text-sky-950 dark:text-sky-50" x-text="bool('is_taxable')"></dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-sky-900/70 dark:text-sky-100/70">{{ __('BPJS applicable') }}</dt>
                        <dd class="text-right font-semibold text-sky-950 dark:text-sky-50" x-text="bool('is_bpjs_applicable')"></dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-sky-900/70 dark:text-sky-100/70">{{ __('Active for processing') }}</dt>
                        <dd class="text-right font-semibold text-sky-950 dark:text-sky-50" x-text="bool('is_active')"></dd>
                    </div>
                </dl>
            </article>
        </div>
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

        <p class="text-sm text-zinc-500 dark:text-zinc-400" x-text="step === 1 ? '{{ __('Choose the payroll target first.') }}' : (step === 2 ? '{{ __('Review the value and supporting notes.') }}' : (step === 3 ? '{{ __('Confirm the calculation flags and continue to final check.') }}' : '{{ __('Everything important is summarized here before saving.') }}'))"></p>

        <div class="flex items-center gap-3">
            <button type="submit" x-show="step === totalSteps" class="rounded-full bg-zinc-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-zinc-800 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">
                {{ $isEditing ? __('Update input') : __('Create input') }}
            </button>
            <a href="{{ route('payroll.inputs.index') }}" class="rounded-full border border-zinc-300 px-5 py-3 text-sm font-semibold text-zinc-800 transition hover:border-zinc-950 dark:border-zinc-700 dark:text-zinc-100 dark:hover:border-white">
                {{ __('Cancel') }}
            </a>
        </div>
    </div>
</form>
