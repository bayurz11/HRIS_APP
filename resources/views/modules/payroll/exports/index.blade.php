<x-layouts::app :title="__('Payroll Exports')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <section class="rounded-3xl border border-zinc-200/70 bg-white p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-cyan-600 dark:text-cyan-300">{{ __('Statutory output') }}</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight text-zinc-950 dark:text-white">{{ __('Bank transfer, BPJS, and PPh21 exports') }}</h1>
                    <p class="mt-3 max-w-3xl text-sm text-zinc-600 dark:text-zinc-300">{{ __('All exports are generated from payroll run snapshots that are already finalized or paid, so they are not affected by master data changes after closing payroll.') }}</p>
                </div>

                <form method="GET" action="{{ route('payroll.exports.index') }}" class="grid gap-3 sm:grid-cols-2">
                    <select name="period_id" class="rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        <option value="">{{ __('Select finalized period') }}</option>
                        @foreach ($periods as $period)
                            <option value="{{ $period->id }}" @selected($selectedPeriodId === $period->id)>{{ $period->period_name }} - {{ $period->payrollGroup?->name ?? __('No group') }}</option>
                        @endforeach
                    </select>
                    <select name="year" class="rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        @foreach ($years as $year)
                            <option value="{{ $year }}" @selected($selectedYear === (int) $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="rounded-2xl bg-zinc-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-zinc-800 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">
                        {{ __('Apply') }}
                    </button>
                </form>
            </div>
        </section>

        <section class="grid gap-6 lg:grid-cols-2">
            <article class="rounded-3xl border border-zinc-200/70 bg-white p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">{{ __('Period exports') }}</h2>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Select a completed payroll period, then download operational and statutory files.') }}</p>

                @if ($selectedPeriodId)
                    <div class="mt-5 grid gap-3">
                        <a href="{{ route('payroll.exports.bank-transfer', $selectedPeriodId) }}" class="rounded-2xl border border-zinc-300 px-4 py-3 text-sm font-semibold text-zinc-800 transition hover:border-zinc-950 dark:border-zinc-700 dark:text-zinc-100 dark:hover:border-white">{{ __('Download bank transfer CSV') }}</a>
                        <a href="{{ route('payroll.exports.bpjs', $selectedPeriodId) }}" class="rounded-2xl border border-zinc-300 px-4 py-3 text-sm font-semibold text-zinc-800 transition hover:border-zinc-950 dark:border-zinc-700 dark:text-zinc-100 dark:hover:border-white">{{ __('Download BPJS recap CSV') }}</a>
                        <a href="{{ route('payroll.exports.pph21-monthly', $selectedPeriodId) }}" class="rounded-2xl border border-zinc-300 px-4 py-3 text-sm font-semibold text-zinc-800 transition hover:border-zinc-950 dark:border-zinc-700 dark:text-zinc-100 dark:hover:border-white">{{ __('Download PPh21 monthly CSV') }}</a>
                    </div>
                @else
                    <p class="mt-5 rounded-2xl border border-dashed border-zinc-300 px-4 py-8 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">{{ __('No period selected yet. Choose a finalized or paid period to unlock period-based exports.') }}</p>
                @endif
            </article>

            <article class="rounded-3xl border border-zinc-200/70 bg-white p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">{{ __('Yearly tax export') }}</h2>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Yearly PPh21 recap per employee based on all finalized or paid payroll periods in the selected year.') }}</p>

                <div class="mt-5 grid gap-3">
                    <a href="{{ route('payroll.exports.pph21-yearly', $selectedYear) }}" class="rounded-2xl border border-zinc-300 px-4 py-3 text-sm font-semibold text-zinc-800 transition hover:border-zinc-950 dark:border-zinc-700 dark:text-zinc-100 dark:hover:border-white">{{ __('Download PPh21 yearly CSV') }}</a>
                </div>
            </article>
        </section>
    </div>
</x-layouts::app>
