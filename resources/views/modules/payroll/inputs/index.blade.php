<x-layouts::app :title="__('Payroll Inputs')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <section class="rounded-3xl border border-zinc-200/70 bg-white p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-cyan-600 dark:text-cyan-300">{{ __('Payroll input engine') }}</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight text-zinc-950 dark:text-white">{{ __('Variable payroll inputs per period') }}</h1>
                    <p class="mt-3 max-w-2xl text-sm text-zinc-600 dark:text-zinc-300">{{ __('Manage bonuses, overtime, reimbursements, loans, adjustments, and periodic deductions before payroll runs are calculated.') }}</p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <form method="GET" action="{{ route('payroll.inputs.index') }}" class="flex flex-wrap gap-3">
                        <select name="period_id" class="rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            <option value="">{{ __('All periods') }}</option>
                            @foreach ($periods as $period)
                                <option value="{{ $period->id }}" @selected($selectedPeriodId === $period->id)>{{ $period->period_name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="rounded-2xl border border-zinc-300 px-4 py-3 text-sm font-semibold text-zinc-800 transition hover:border-zinc-950 dark:border-zinc-700 dark:text-zinc-100 dark:hover:border-white">
                            {{ __('Filter') }}
                        </button>
                    </form>

                    @if ($canManage)
                        @if ($selectedPeriod?->isLocked())
                            <span class="rounded-full border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-200">
                                {{ __('Selected period is locked') }}
                            </span>
                        @else
                            <a href="{{ route('payroll.inputs.create', ['period_id' => $selectedPeriodId]) }}" class="rounded-full bg-zinc-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-zinc-800 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">
                                {{ __('New input') }}
                            </a>
                        @endif
                    @endif
                </div>
            </div>
        </section>

        @if ($selectedPeriod?->isLocked())
            <section class="rounded-3xl border border-amber-300/70 bg-amber-50 p-5 text-sm text-amber-950 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-100">
                <p class="font-semibold">{{ __('This payroll period is locked.') }}</p>
                <p class="mt-1 text-amber-900/80 dark:text-amber-100/80">{{ __('Variable inputs can no longer be changed because payroll processing has already started for this period.') }}</p>
            </section>
        @endif

        <section class="overflow-hidden rounded-3xl border border-zinc-200/70 bg-white shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                <thead class="bg-zinc-50 text-left text-xs uppercase tracking-[0.18em] text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">{{ __('Employee') }}</th>
                        <th class="px-4 py-3">{{ __('Period') }}</th>
                        <th class="px-4 py-3">{{ __('Component') }}</th>
                        <th class="px-4 py-3">{{ __('Amount') }}</th>
                        <th class="px-4 py-3">{{ __('Flags') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($inputs as $input)
                        @php($isLocked = $input->payrollPeriod?->isLocked())
                        <tr>
                            <td class="px-4 py-4">
                                <p class="font-semibold text-zinc-950 dark:text-white">{{ $input->employee?->full_name ?? __('Unknown employee') }}</p>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $input->employee?->employee_number ?? '-' }}</p>
                            </td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ $input->payrollPeriod?->period_name ?? '-' }}</td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-medium text-zinc-950 dark:text-white">{{ $input->input_name }}</p>
                                    @if ($isLocked)
                                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-amber-700 dark:bg-amber-500/20 dark:text-amber-200">
                                            {{ __('Locked') }}
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-1 text-xs uppercase tracking-[0.16em] text-zinc-500 dark:text-zinc-400">{{ $input->component_type }}</p>
                            </td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ number_format($input->resolvedAmount(), 0, ',', '.') }}</td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">
                                {{ $input->is_taxable ? __('Taxable') : __('Non-taxable') }} / {{ $input->is_bpjs_applicable ? __('BPJS base') : __('No BPJS') }}
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    @if ($canManage && ! $isLocked)
                                        <a href="{{ route('payroll.inputs.edit', $input) }}" class="rounded-full border border-zinc-300 px-3 py-2 text-xs font-semibold text-zinc-700 transition hover:border-zinc-950 dark:border-zinc-700 dark:text-zinc-200 dark:hover:border-white">
                                            {{ __('Edit') }}
                                        </a>
                                        <form method="POST" action="{{ route('payroll.inputs.destroy', $input) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-full border border-rose-300 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-50 dark:border-rose-500/20 dark:text-rose-300 dark:hover:bg-rose-500/10" onclick="return confirm('{{ __('Delete this payroll input?') }}')">
                                                {{ __('Delete') }}
                                            </button>
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
                            <td colspan="6" class="px-4 py-10 text-center text-zinc-500 dark:text-zinc-400">{{ __('No variable payroll inputs for this period yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="border-t border-zinc-200 px-4 py-4 dark:border-zinc-800">
                {{ $inputs->links() }}
            </div>
        </section>
    </div>
</x-layouts::app>
