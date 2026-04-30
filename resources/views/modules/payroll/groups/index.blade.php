<x-layouts::app :title="__('Payroll Groups')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <section class="flex flex-col gap-4 rounded-3xl border border-zinc-200/70 bg-white p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-cyan-600 dark:text-cyan-300">{{ __('Payroll groups') }}</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight text-zinc-950 dark:text-white">{{ __('Group payroll processing by business unit') }}</h1>
                <p class="mt-3 max-w-2xl text-sm text-zinc-600 dark:text-zinc-300">{{ __('Set payroll frequency and split processing by organization or work unit.') }}</p>
            </div>
            <a href="{{ route('payroll.groups.create') }}" class="self-start rounded-full bg-zinc-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-zinc-800 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">
                {{ __('New payroll group') }}
            </a>
        </section>

        <section class="overflow-hidden rounded-3xl border border-zinc-200/70 bg-white shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                <thead class="bg-zinc-50 text-left text-xs uppercase tracking-[0.18em] text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">{{ __('Group') }}</th>
                        <th class="px-4 py-3">{{ __('Organization') }}</th>
                        <th class="px-4 py-3">{{ __('Frequency') }}</th>
                        <th class="px-4 py-3">{{ __('Periods') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($groups as $group)
                        <tr>
                            <td class="px-4 py-4">
                                <p class="font-semibold text-zinc-950 dark:text-white">{{ $group->name }}</p>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $group->code }}</p>
                            </td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ $group->organization?->name ?: '-' }}</td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ __(ucfirst($group->pay_frequency)) }} | {{ __('Day') }} {{ $group->payroll_day ?: '-' }}</td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ number_format($group->periods_count) }}</td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('payroll.groups.edit', $group) }}" class="rounded-full border border-zinc-300 px-3 py-2 text-xs font-semibold text-zinc-700 transition hover:border-zinc-950 dark:border-zinc-700 dark:text-zinc-200 dark:hover:border-white">{{ __('Edit') }}</a>
                                    <form method="POST" action="{{ route('payroll.groups.destroy', $group) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-full border border-rose-300 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-50 dark:border-rose-500/20 dark:text-rose-300 dark:hover:bg-rose-500/10" onclick="return confirm('{{ __('Delete this payroll group?') }}')">{{ __('Delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-zinc-500 dark:text-zinc-400">{{ __('No payroll groups yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="border-t border-zinc-200 px-4 py-4 dark:border-zinc-800">
                {{ $groups->links() }}
            </div>
        </section>
    </div>
</x-layouts::app>
