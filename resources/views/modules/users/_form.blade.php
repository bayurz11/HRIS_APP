@php
    $isEditing = $employee->exists;
    $profile = $employee->payrollProfile;
    $wizardSteps = [
        1 => ['employee_number', 'full_name', 'email', 'phone', 'organization_id', 'employment_status', 'hire_date', 'resign_date'],
        2 => ['create_login_account', 'account_role', 'password'],
        3 => ['basic_salary', 'payroll_group_id', 'tax_status_id', 'payment_type', 'is_taxable', 'is_overtime_eligible'],
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
    action="{{ $isEditing ? route('users.update', $employee) : route('users.store') }}"
    class="space-y-8"
    x-data="createEmployeeWizard(@js([
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
        ],
    ]))"
>
    @csrf
    @if ($isEditing)
        @method('PUT')
    @endif

    <section class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-zinc-950 dark:text-white">{{ __('Employee setup wizard') }}</p>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Complete the employee data in order so nothing important is missed.') }}</p>
            </div>
            <span class="rounded-full border border-zinc-200 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500 dark:border-zinc-700 dark:text-zinc-300">
                <span x-text="step"></span>/<span x-text="totalSteps"></span>
            </span>
        </div>

        <div class="grid gap-3 md:grid-cols-4">
            <button type="button" @click="step = 1" :class="stepClasses(1)" class="rounded-2xl border px-4 py-3 text-left transition">
                <p class="text-xs font-semibold uppercase tracking-[0.2em]">{{ __('Step 1') }}</p>
                <div class="mt-2 flex items-center justify-between gap-2">
                    <p class="font-semibold">{{ __('Employee identity') }}</p>
                    <span :class="statusClasses(1)" class="rounded-full px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.18em]" x-text="statusLabel(1)"></span>
                </div>
                <p class="mt-1 text-xs">{{ __('Basic HR data and employment status.') }}</p>
            </button>
            <button type="button" @click="step = 2" :class="stepClasses(2)" class="rounded-2xl border px-4 py-3 text-left transition">
                <p class="text-xs font-semibold uppercase tracking-[0.2em]">{{ __('Step 2') }}</p>
                <div class="mt-2 flex items-center justify-between gap-2">
                    <p class="font-semibold">{{ __('Application account') }}</p>
                    <span :class="statusClasses(2)" class="rounded-full px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.18em]" x-text="statusLabel(2)"></span>
                </div>
                <p class="mt-1 text-xs">{{ __('Login access and role selection.') }}</p>
            </button>
            <button type="button" @click="step = 3" :class="stepClasses(3)" class="rounded-2xl border px-4 py-3 text-left transition">
                <p class="text-xs font-semibold uppercase tracking-[0.2em]">{{ __('Step 3') }}</p>
                <div class="mt-2 flex items-center justify-between gap-2">
                    <p class="font-semibold">{{ __('Payroll profile') }}</p>
                    <span :class="statusClasses(3)" class="rounded-full px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.18em]" x-text="statusLabel(3)"></span>
                </div>
                <p class="mt-1 text-xs">{{ __('Salary, tax, and payroll setup.') }}</p>
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

    <section class="space-y-6" x-show="step === 1" x-cloak>
        <div>
            <h2 class="text-lg font-semibold text-zinc-950 dark:text-white">{{ __('Employee identity') }}</h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Core employee data used across modules.') }}</p>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <label for="employee_number" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Employee number') }}</label>
                <input id="employee_number" name="employee_number" type="text" value="{{ old('employee_number', $employee->employee_number) }}" placeholder="EMP-0001" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white" required>
                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Use the official employee ID used by HR or payroll administration.') }}</p>
            </div>
            <div>
                <label for="full_name" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Full name') }}</label>
                <input id="full_name" name="full_name" type="text" value="{{ old('full_name', $employee->full_name) }}" placeholder="{{ __('Full legal name') }}" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white" required>
                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Enter the employee name as it should appear in payroll and official documents.') }}</p>
            </div>
            <div>
                <label for="email" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Email') }}</label>
                <input id="email" name="email" type="email" value="{{ old('email', $employee->email) }}" placeholder="nama@perusahaan.com" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('This email can also be used for login and payroll notifications if an account is created.') }}</p>
            </div>
            <div>
                <label for="phone" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Phone') }}</label>
                <input id="phone" name="phone" type="text" value="{{ old('phone', $employee->phone) }}" placeholder="0812xxxxxxx" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Fill a phone number that HR can contact if there is a payroll issue.') }}</p>
            </div>
            <div>
                <label for="organization_id" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Organization') }}</label>
                <select id="organization_id" name="organization_id" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                    <option value="">{{ __('No organization') }}</option>
                    @foreach ($organizations as $organization)
                        <option value="{{ $organization->id }}" @selected(old('organization_id', $employee->organization_id) == $organization->id)>{{ $organization->name }}</option>
                    @endforeach
                </select>
                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Choose the employee work unit so the employee is grouped correctly in the HR structure.') }}</p>
            </div>
            <div>
                <label for="employment_status" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Employment status') }}</label>
                <select id="employment_status" name="employment_status" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                    @foreach (['active' => 'Active', 'probation' => 'Probation', 'inactive' => 'Inactive'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('employment_status', $employee->employment_status) === $value)>{{ __($label) }}</option>
                    @endforeach
                </select>
                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Choose active for current employees, probation for trial period, or inactive if no longer employed.') }}</p>
            </div>
            <div>
                <label for="hire_date" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Hire date') }}</label>
                <input id="hire_date" name="hire_date" type="date" value="{{ old('hire_date', optional($employee->hire_date)->toDateString()) }}" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Fill the official start date of employment if available.') }}</p>
            </div>
            <div>
                <label for="resign_date" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Resign date') }}</label>
                <input id="resign_date" name="resign_date" type="date" value="{{ old('resign_date', optional($employee->resign_date)->toDateString()) }}" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Fill only if the employee has already stopped working.') }}</p>
            </div>
        </div>
    </section>

    <section class="space-y-6 rounded-3xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-700 dark:bg-zinc-950/60" x-show="step === 2" x-cloak>
        <div>
            <h2 class="text-lg font-semibold text-zinc-950 dark:text-white">{{ __('Application account') }}</h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Optional, but recommended for employee self-service or internal admin access.') }}</p>
        </div>

        <div class="flex items-center gap-3">
            <input id="create_login_account" name="create_login_account" type="checkbox" value="1" @checked(old('create_login_account', $employee->user !== null)) class="size-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500">
            <label for="create_login_account" class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Create or keep a login account for this employee') }}</label>
        </div>
        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Turn this on if the employee needs to log in, view payslips, or access the application directly.') }}</p>

        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <label for="account_role" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Account role') }}</label>
                <select id="account_role" name="account_role" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                    <option value="">{{ __('Select role') }}</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role }}" @selected(old('account_role', $employee->user?->getRoleNames()->first()) === $role)>{{ $role }}</option>
                    @endforeach
                </select>
                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Choose the access level that matches the employee responsibilities in the application.') }}</p>
            </div>
            <div>
                <label for="password" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Password') }} {{ $isEditing ? __('(leave blank to keep current)') : '' }}</label>
                <input id="password" name="password" type="password" placeholder="{{ __('Temporary password for first login') }}" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Use a temporary password if needed. The employee can change it later.') }}</p>
            </div>
        </div>
    </section>

    <section class="space-y-6" x-show="step === 3" x-cloak>
        <div>
            <h2 class="text-lg font-semibold text-zinc-950 dark:text-white">{{ __('Payroll profile') }}</h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Fill in this section if the employee should be processed in payroll immediately.') }}</p>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <label for="basic_salary" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Basic salary') }}</label>
                <input id="basic_salary" name="basic_salary" type="number" min="0" step="0.01" value="{{ old('basic_salary', $profile?->basic_salary) }}" placeholder="5000000" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Fill the regular base salary before allowances, deductions, tax, and BPJS are calculated.') }}</p>
            </div>
            <div>
                <label for="payroll_group_id" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Payroll group') }}</label>
                <select id="payroll_group_id" name="payroll_group_id" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                    <option value="">{{ __('No payroll group') }}</option>
                    @foreach ($payrollGroups as $payrollGroup)
                        <option value="{{ $payrollGroup->id }}" @selected(old('payroll_group_id', $profile?->payroll_group_id) == $payrollGroup->id)>{{ $payrollGroup->name }}</option>
                    @endforeach
                </select>
                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Assign the employee to a payroll group so periods and payment schedule can be determined automatically.') }}</p>
            </div>
            <div>
                <label for="tax_status_id" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Tax status') }}</label>
                <select id="tax_status_id" name="tax_status_id" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                    <option value="">{{ __('No tax status') }}</option>
                    @foreach ($taxStatuses as $taxStatus)
                        <option value="{{ $taxStatus->id }}" @selected(old('tax_status_id', $profile?->tax_status_id) == $taxStatus->id)>{{ $taxStatus->code }} - {{ $taxStatus->name }}</option>
                    @endforeach
                </select>
                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Choose the tax status used for PPh21 calculation, for example TK0 or K1.') }}</p>
            </div>
            <div>
                <label for="payment_type" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Payment type') }}</label>
                <select id="payment_type" name="payment_type" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                    @foreach (['monthly' => 'Monthly', 'daily' => 'Daily', 'hourly' => 'Hourly'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('payment_type', $profile?->payment_type ?? 'monthly') === $value)>{{ __($label) }}</option>
                    @endforeach
                </select>
                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Select how the salary is basically measured: monthly, daily, or hourly.') }}</p>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <label class="flex items-center gap-3 rounded-2xl border border-zinc-200 px-4 py-3 dark:border-zinc-700">
                <input name="is_taxable" type="checkbox" value="1" @checked(old('is_taxable', $profile?->is_taxable ?? true)) class="size-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500">
                <span class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Taxable employee') }}</span>
            </label>
            <label class="flex items-center gap-3 rounded-2xl border border-zinc-200 px-4 py-3 dark:border-zinc-700">
                <input name="is_overtime_eligible" type="checkbox" value="1" @checked(old('is_overtime_eligible', $profile?->is_overtime_eligible ?? false)) class="size-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500">
                <span class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Eligible for overtime') }}</span>
            </label>
        </div>
        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Turn these options on only if the employee should be included in tax and overtime calculations.') }}</p>
    </section>

    <section class="space-y-6" x-show="step === 4" x-cloak>
        <div>
            <h2 class="text-lg font-semibold text-zinc-950 dark:text-white">{{ __('Final review') }}</h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Review the most important data below before saving the employee record.') }}</p>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <article class="rounded-3xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Account readiness') }}</p>
                <p class="mt-3 text-2xl font-semibold text-zinc-950 dark:text-white" x-text="accountReady()"></p>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Shows whether login access data is already complete enough.') }}</p>
            </article>
            <article class="rounded-3xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Payroll readiness') }}</p>
                <p class="mt-3 text-2xl font-semibold text-zinc-950 dark:text-white" x-text="payrollReady()"></p>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Checks whether salary and payroll group are ready for payroll processing.') }}</p>
            </article>
            <article class="rounded-3xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Tax setup') }}</p>
                <p class="mt-3 text-2xl font-semibold text-zinc-950 dark:text-white" x-text="taxSetupReady()"></p>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Checks whether taxable setup is already supported by tax status data.') }}</p>
            </article>
        </div>

        <div x-show="employeeWarnings().length" x-cloak class="rounded-3xl border border-amber-300/60 bg-amber-50 p-5 dark:border-amber-500/20 dark:bg-amber-500/10">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-amber-950 dark:text-amber-100">{{ __('Preview warnings') }}</h3>
                    <p class="mt-1 text-sm text-amber-900/80 dark:text-amber-100/80">{{ __('These warnings do not always block saving, but they may affect payroll accuracy or access readiness.') }}</p>
                </div>
                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-amber-700 dark:bg-amber-500/20 dark:text-amber-200" x-text="employeeWarnings().length"></span>
            </div>

            <ul class="mt-4 space-y-3">
                <template x-for="warning in employeeWarnings()" :key="warning">
                    <li class="rounded-2xl bg-white/70 px-4 py-3 text-sm text-amber-950 dark:bg-zinc-950/20 dark:text-amber-100" x-text="warning"></li>
                </template>
            </ul>
        </div>

        <div class="grid gap-4 xl:grid-cols-3">
            <article class="rounded-3xl border border-cyan-200 bg-cyan-50/70 p-5 dark:border-cyan-500/20 dark:bg-cyan-500/10">
                <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-cyan-800 dark:text-cyan-200">{{ __('Identity review') }}</h3>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-cyan-900/70 dark:text-cyan-100/70">{{ __('Employee number') }}</dt>
                        <dd class="text-right font-semibold text-cyan-950 dark:text-cyan-50" x-text="text('employee_number')"></dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-cyan-900/70 dark:text-cyan-100/70">{{ __('Full name') }}</dt>
                        <dd class="text-right font-semibold text-cyan-950 dark:text-cyan-50" x-text="text('full_name')"></dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-cyan-900/70 dark:text-cyan-100/70">{{ __('Email') }}</dt>
                        <dd class="text-right font-semibold text-cyan-950 dark:text-cyan-50" x-text="text('email')"></dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-cyan-900/70 dark:text-cyan-100/70">{{ __('Organization') }}</dt>
                        <dd class="text-right font-semibold text-cyan-950 dark:text-cyan-50" x-text="option('organization_id')"></dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-cyan-900/70 dark:text-cyan-100/70">{{ __('Employment status') }}</dt>
                        <dd class="text-right font-semibold text-cyan-950 dark:text-cyan-50" x-text="option('employment_status')"></dd>
                    </div>
                </dl>
            </article>

            <article class="rounded-3xl border border-amber-200 bg-amber-50/70 p-5 dark:border-amber-500/20 dark:bg-amber-500/10">
                <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-amber-800 dark:text-amber-200">{{ __('Access review') }}</h3>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-amber-900/70 dark:text-amber-100/70">{{ __('Login account') }}</dt>
                        <dd class="text-right font-semibold text-amber-950 dark:text-amber-50" x-text="bool('create_login_account')"></dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-amber-900/70 dark:text-amber-100/70">{{ __('Account role') }}</dt>
                        <dd class="text-right font-semibold text-amber-950 dark:text-amber-50" x-text="option('account_role')"></dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-amber-900/70 dark:text-amber-100/70">{{ __('Password') }}</dt>
                        <dd class="text-right font-semibold text-amber-950 dark:text-amber-50" x-text="text('password', '{{ __('Keep current password') }}')"></dd>
                    </div>
                </dl>
            </article>

            <article class="rounded-3xl border border-sky-200 bg-sky-50/70 p-5 dark:border-sky-500/20 dark:bg-sky-500/10">
                <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-sky-800 dark:text-sky-200">{{ __('Payroll review') }}</h3>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-sky-900/70 dark:text-sky-100/70">{{ __('Basic salary') }}</dt>
                        <dd class="text-right font-semibold text-sky-950 dark:text-sky-50" x-text="text('basic_salary')"></dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-sky-900/70 dark:text-sky-100/70">{{ __('Payroll group') }}</dt>
                        <dd class="text-right font-semibold text-sky-950 dark:text-sky-50" x-text="option('payroll_group_id')"></dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-sky-900/70 dark:text-sky-100/70">{{ __('Tax status') }}</dt>
                        <dd class="text-right font-semibold text-sky-950 dark:text-sky-50" x-text="option('tax_status_id')"></dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-sky-900/70 dark:text-sky-100/70">{{ __('Payment type') }}</dt>
                        <dd class="text-right font-semibold text-sky-950 dark:text-sky-50" x-text="option('payment_type')"></dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-sky-900/70 dark:text-sky-100/70">{{ __('Taxable employee') }}</dt>
                        <dd class="text-right font-semibold text-sky-950 dark:text-sky-50" x-text="bool('is_taxable')"></dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-sky-900/70 dark:text-sky-100/70">{{ __('Eligible for overtime') }}</dt>
                        <dd class="text-right font-semibold text-sky-950 dark:text-sky-50" x-text="bool('is_overtime_eligible')"></dd>
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

        <div class="flex items-center gap-3">
            <p class="text-sm text-zinc-500 dark:text-zinc-400" x-text="step === 1 ? '{{ __('Complete employee identity first.') }}' : (step === 2 ? '{{ __('Set account access if needed.') }}' : (step === 3 ? '{{ __('Review payroll setup and continue to final check.') }}' : '{{ __('Everything important is summarized here before saving.') }}'))"></p>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" x-show="step === totalSteps" class="rounded-full bg-zinc-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-zinc-800 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">
                {{ $isEditing ? __('Update employee') : __('Create employee') }}
            </button>
            <a href="{{ route('users.index') }}" class="rounded-full border border-zinc-300 px-5 py-3 text-sm font-semibold text-zinc-800 transition hover:border-zinc-950 dark:border-zinc-700 dark:text-zinc-100 dark:hover:border-white">
                {{ __('Cancel') }}
            </a>
        </div>
    </div>
</form>
