@php $isEditing = $group->exists; @endphp

<form method="POST" action="{{ $isEditing ? route('payroll.groups.update', $group) : route('payroll.groups.store') }}" class="space-y-6">
    @csrf
    @if ($isEditing)
        @method('PUT')
    @endif

    <div class="rounded-2xl border border-cyan-200 bg-cyan-50/80 px-4 py-4 text-sm text-cyan-950 dark:border-cyan-500/20 dark:bg-cyan-500/10 dark:text-cyan-100">
        <p class="font-semibold">{{ __('Set one payroll group for employees who share the same salary schedule.') }}</p>
        <p class="mt-1 text-cyan-900/80 dark:text-cyan-100/80">{{ __('Example: staff paid monthly on the 25th can be placed in one payroll group.') }}</p>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label for="code" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Code') }}</label>
            <input id="code" name="code" type="text" value="{{ old('code', $group->code) }}" placeholder="PG-MONTHLY-HO" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white" required>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Use a short unique code so this group is easy to recognize in periods and reports.') }}</p>
        </div>
        <div>
            <label for="name" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Name') }}</label>
            <input id="name" name="name" type="text" value="{{ old('name', $group->name) }}" placeholder="{{ __('Monthly Head Office Payroll') }}" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white" required>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Use a clear business name that non-technical users can recognize quickly.') }}</p>
        </div>
        <div>
            <label for="organization_id" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Organization') }}</label>
            <select id="organization_id" name="organization_id" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                <option value="">{{ __('No organization') }}</option>
                @foreach ($organizations as $organization)
                    <option value="{{ $organization->id }}" @selected(old('organization_id', $group->organization_id) == $organization->id)>{{ $organization->name }}</option>
                @endforeach
            </select>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Choose an organization if this payroll group only applies to one branch, unit, or company.') }}</p>
        </div>
        <div>
            <label for="pay_frequency" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Pay frequency') }}</label>
            <select id="pay_frequency" name="pay_frequency" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                @foreach (['monthly' => 'Monthly', 'biweekly' => 'Biweekly', 'weekly' => 'Weekly'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('pay_frequency', $group->pay_frequency) === $value)>{{ __($label) }}</option>
                @endforeach
            </select>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('This setting determines how often payroll periods are normally created for the group.') }}</p>
        </div>
        <div>
            <label for="payroll_day" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Payroll day') }}</label>
            <input id="payroll_day" name="payroll_day" type="number" min="1" max="31" value="{{ old('payroll_day', $group->payroll_day) }}" placeholder="25" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Fill with the usual pay date in the month, for example 25 for salary paid every 25th.') }}</p>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="rounded-full bg-zinc-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-zinc-800 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">
            {{ $isEditing ? __('Update payroll group') : __('Create payroll group') }}
        </button>
        <a href="{{ route('payroll.groups.index') }}" class="rounded-full border border-zinc-300 px-5 py-3 text-sm font-semibold text-zinc-800 transition hover:border-zinc-950 dark:border-zinc-700 dark:text-zinc-100 dark:hover:border-white">
            {{ __('Cancel') }}
        </a>
    </div>
</form>
