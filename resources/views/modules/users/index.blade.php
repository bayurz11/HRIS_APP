<x-layouts::app :title="__('Employees')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <section class="flex flex-col gap-4 rounded-3xl border border-zinc-200/70 bg-white p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-cyan-600 dark:text-cyan-300">{{ __('Employee module') }}</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight text-zinc-950 dark:text-white">{{ __('Employee data and access accounts') }}</h1>
                <p class="mt-3 max-w-2xl text-sm text-zinc-600 dark:text-zinc-300">{{ __('Manage employee data and internal login accounts from one place.') }}</p>
            </div>

            <div class="flex flex-wrap gap-3">
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                    <p class="text-xs uppercase tracking-[0.18em] text-zinc-500 dark:text-zinc-400">{{ __('Employees') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-zinc-950 dark:text-white">{{ number_format($stats['employee_count']) }}</p>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                    <p class="text-xs uppercase tracking-[0.18em] text-zinc-500 dark:text-zinc-400">{{ __('Accounts') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-zinc-950 dark:text-white">{{ number_format($stats['account_count']) }}</p>
                </div>
                <a href="{{ route('users.create') }}" class="self-center rounded-full bg-zinc-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-zinc-800 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">
                    {{ __('New employee') }}
                </a>
            </div>
        </section>

        <section class="overflow-hidden rounded-3xl border border-zinc-200/70 bg-white shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                <thead class="bg-zinc-50 text-left text-xs uppercase tracking-[0.18em] text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">{{ __('Employee') }}</th>
                        <th class="px-4 py-3">{{ __('Organization') }}</th>
                        <th class="px-4 py-3">{{ __('Account') }}</th>
                        <th class="px-4 py-3">{{ __('Payroll') }}</th>
                        <th class="px-4 py-3">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($employees as $employee)
                        <tr>
                            <td class="px-4 py-4">
                                <p class="font-semibold text-zinc-950 dark:text-white">{{ $employee->full_name }}</p>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $employee->employee_number }} | {{ $employee->email ?: __('No email') }}</p>
                            </td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ $employee->organization?->name ?: '-' }}</td>
                            <td class="px-4 py-4">
                                @if ($employee->user)
                                    <p class="font-medium text-zinc-900 dark:text-white">{{ $employee->user->email }}</p>
                                    <p class="mt-1 text-xs uppercase tracking-[0.16em] text-zinc-500 dark:text-zinc-400">{{ $employee->user->getRoleNames()->implode(', ') }}</p>
                                @else
                                    <span class="text-zinc-500 dark:text-zinc-400">{{ __('No account yet') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">
                                @if ($employee->payrollProfile)
                                    {{ number_format((float) $employee->payrollProfile->basic_salary, 0, ',', '.') }} | {{ $employee->payrollProfile->payrollGroup?->name ?: __('No group yet') }}
                                @else
                                    {{ __('Not configured yet') }}
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] {{ $employee->employment_status === 'active' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' }}">
                                    {{ __(ucfirst($employee->employment_status)) }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('users.edit', $employee) }}" class="rounded-full border border-zinc-300 px-3 py-2 text-xs font-semibold text-zinc-700 transition hover:border-zinc-950 dark:border-zinc-700 dark:text-zinc-200 dark:hover:border-white">
                                        {{ __('Edit') }}
                                    </a>
                                    <form method="POST" action="{{ route('users.destroy', $employee) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-full border border-rose-300 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-50 dark:border-rose-500/20 dark:text-rose-300 dark:hover:bg-rose-500/10" onclick="return confirm('{{ __('Delete this employee?') }}')">
                                            {{ __('Delete') }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-zinc-500 dark:text-zinc-400">{{ __('No employee data yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="border-t border-zinc-200 px-4 py-4 dark:border-zinc-800">
                {{ $employees->links() }}
            </div>
        </section>
    </div>
</x-layouts::app>
