window.createWizard = function createWizard(config = {}) {
    return {
        step: config.initialStep ?? 1,
        totalSteps: config.totalSteps ?? 1,
        activePalette: config.activePalette ?? {},
        locale: document.documentElement.lang === 'en' ? 'en-US' : 'id-ID',
        labels: {
            active: 'Active',
            done: 'Done',
            pending: 'Pending',
            yes: 'Yes',
            no: 'No',
            notFilled: 'Not filled yet',
            ...config.labels,
        },
        messages: {
            employeeAccountEmailMissing: 'Login account is enabled but email is still empty.',
            employeeAccountRoleMissing: 'Login account is enabled but account role has not been selected.',
            employeeActiveWithResignDate: 'Employee is marked active but resign date is already filled.',
            employeeSalaryWithoutGroup: 'Basic salary is filled but payroll group is still empty.',
            employeeTaxableWithoutStatus: 'Employee is taxable but tax status has not been selected.',
            employeeGroupWithoutSalary: 'Payroll group is selected but basic salary is still empty.',
            periodPayBeforeEnd: 'Pay date is earlier than the period end date.',
            periodAdvancedWithoutPayDate: 'Period status is already advanced but pay date is still empty.',
            periodLongRange: 'This payroll period is longer than 40 days and may need to be reviewed.',
            periodNameMissing: 'Period name is still empty.',
            periodNoCandidates: 'No active employees are currently assigned to the selected payroll group.',
            inputIncompleteAmount: 'Final amount cannot be estimated yet because quantity or rate is still empty.',
            inputFixedOverridesFormula: 'Fixed amount is filled, so quantity x rate will be ignored.',
            inputNotesMissing: 'Notes are still empty, so approvers may not know why this payroll input was added.',
            inputInactiveFlags: 'Input is inactive, so tax or BPJS flags will not affect payroll processing yet.',
            inputGroupMismatch: 'Selected employee payroll group is different from the selected payroll period group.',
            inputNoEmployeeProfile: 'Selected employee does not have a payroll profile yet.',
            inputInactiveNoImpact: 'Input is inactive, so it will not change estimated take-home pay yet.',
            inputNoTakeHomeImpact: 'Selected component does not affect take-home pay.',
            ...config.messages,
        },
        t(key) {
            return this.messages[key] ?? key;
        },
        field(name) {
            return this.$root.querySelector(`[name="${CSS.escape(name)}"]`);
        },
        hasValue(name) {
            const field = this.field(name);

            if (!field) {
                return false;
            }

            if (field.type === 'checkbox' || field.type === 'radio') {
                return field.checked;
            }

            return String(field.value ?? '').trim() !== '';
        },
        value(name, fallback = null) {
            const field = this.field(name);
            const defaultValue = fallback ?? '';

            if (!field) {
                return defaultValue;
            }

            return String(field.value ?? '').trim();
        },
        state(index) {
            if (this.step === index) {
                return 'active';
            }

            if (this.step > index) {
                return 'done';
            }

            return 'pending';
        },
        stepClasses(index) {
            const state = this.state(index);

            if (state === 'active') {
                return this.activePalette[index] ?? 'border-cyan-400 bg-cyan-50 text-cyan-950 dark:border-cyan-500/30 dark:bg-cyan-500/10 dark:text-cyan-100';
            }

            if (state === 'done') {
                return 'border-emerald-400 bg-emerald-50 text-emerald-950 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-100';
            }

            return 'border-zinc-200 text-zinc-600 dark:border-zinc-700 dark:text-zinc-300';
        },
        statusClasses(index) {
            const state = this.state(index);

            if (state === 'active') {
                return 'bg-white/80 text-current dark:bg-white/10';
            }

            if (state === 'done') {
                return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-200';
            }

            return 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-300';
        },
        statusLabel(index) {
            const state = this.state(index);

            if (state === 'active') {
                return this.labels.active;
            }

            if (state === 'done') {
                return this.labels.done;
            }

            return this.labels.pending;
        },
        text(name, fallback = null) {
            const field = this.field(name);
            const defaultValue = fallback ?? this.labels.notFilled;

            if (!field) {
                return defaultValue;
            }

            if (field.type === 'checkbox') {
                return field.checked ? this.labels.yes : this.labels.no;
            }

            const value = String(field.value ?? '').trim();

            return value !== '' ? value : defaultValue;
        },
        option(name, fallback = null) {
            const field = this.field(name);
            const defaultValue = fallback ?? this.labels.notFilled;

            if (!field) {
                return defaultValue;
            }

            if (field.tagName?.toLowerCase() === 'select') {
                const option = field.options[field.selectedIndex];
                const value = String(option?.textContent ?? '').trim();

                return value !== '' ? value : defaultValue;
            }

            return this.text(name, defaultValue);
        },
        bool(name, yesLabel = null, noLabel = null) {
            const field = this.field(name);

            if (!field) {
                return noLabel ?? this.labels.no;
            }

            return field.checked ? (yesLabel ?? this.labels.yes) : (noLabel ?? this.labels.no);
        },
        number(name, fallback = null) {
            const field = this.field(name);

            if (!field) {
                return fallback;
            }

            const parsed = Number.parseFloat(String(field.value ?? '').replace(',', '.'));

            return Number.isFinite(parsed) ? parsed : fallback;
        },
        currency(name, fallback = null) {
            const value = typeof name === 'number' ? name : this.number(name, null);
            const defaultValue = fallback ?? this.labels.notFilled;

            if (value === null) {
                return defaultValue;
            }

            return new Intl.NumberFormat(this.locale, {
                style: 'currency',
                currency: 'IDR',
                maximumFractionDigits: 0,
            }).format(value);
        },
        dateValue(name) {
            const field = this.field(name);

            if (!field || !field.value) {
                return null;
            }

            const parsed = new Date(field.value);

            return Number.isNaN(parsed.getTime()) ? null : parsed;
        },
        daysBetween(startName, endName, inclusive = true) {
            const start = this.dateValue(startName);
            const end = this.dateValue(endName);

            if (!start || !end) {
                return null;
            }

            const difference = Math.round((end.getTime() - start.getTime()) / 86400000);

            return inclusive ? difference + 1 : difference;
        },
        ratio(doneSteps = this.step - 1) {
            return Math.max(0, Math.min(100, Math.round((doneSteps / this.totalSteps) * 100)));
        },
    };
};

window.createEmployeeWizard = function createEmployeeWizard(config = {}) {
    return {
        ...window.createWizard(config),
        accountIsEnabled() {
            return Boolean(this.field('create_login_account')?.checked);
        },
        accountReady() {
            if (!this.accountIsEnabled()) {
                return this.labels.notFilled;
            }

            return this.hasValue('email') && this.hasValue('account_role')
                ? this.labels.done
                : this.labels.pending;
        },
        payrollReady() {
            return this.hasValue('basic_salary') && this.hasValue('payroll_group_id')
                ? this.labels.done
                : this.labels.pending;
        },
        taxSetupReady() {
            if (!this.field('is_taxable')?.checked) {
                return this.labels.notFilled;
            }

            return this.hasValue('tax_status_id') ? this.labels.done : this.labels.pending;
        },
        employeeWarnings() {
            const warnings = [];

            if (this.accountIsEnabled() && !this.hasValue('email')) {
                warnings.push(this.t('employeeAccountEmailMissing'));
            }

            if (this.accountIsEnabled() && !this.hasValue('account_role')) {
                warnings.push(this.t('employeeAccountRoleMissing'));
            }

            if (this.value('employment_status') === 'active' && this.hasValue('resign_date')) {
                warnings.push(this.t('employeeActiveWithResignDate'));
            }

            if (this.hasValue('basic_salary') && !this.hasValue('payroll_group_id')) {
                warnings.push(this.t('employeeSalaryWithoutGroup'));
            }

            if (this.field('is_taxable')?.checked && !this.hasValue('tax_status_id')) {
                warnings.push(this.t('employeeTaxableWithoutStatus'));
            }

            if (this.hasValue('payroll_group_id') && !this.hasValue('basic_salary')) {
                warnings.push(this.t('employeeGroupWithoutSalary'));
            }

            return warnings;
        },
    };
};

window.createPayrollPeriodWizard = function createPayrollPeriodWizard(config = {}) {
    return {
        ...window.createWizard(config),
        candidatesByGroup: config.candidatesByGroup ?? {},
        periodLength() {
            return this.daysBetween('start_date', 'end_date');
        },
        selectedCandidates() {
            return this.candidatesByGroup[this.value('payroll_group_id')] ?? [];
        },
        candidateCount() {
            return this.selectedCandidates().length;
        },
        candidateSalaryTotal() {
            return this.selectedCandidates().reduce((total, employee) => total + Number(employee.basicSalary ?? 0), 0);
        },
        candidateAverageSalary() {
            const count = this.candidateCount();

            return count > 0 ? this.candidateSalaryTotal() / count : null;
        },
        candidatePreviewLimit() {
            return this.selectedCandidates().slice(0, 5);
        },
        payLag() {
            const end = this.dateValue('end_date');
            const pay = this.dateValue('pay_date');

            if (!end || !pay) {
                return null;
            }

            return Math.round((pay.getTime() - end.getTime()) / 86400000);
        },
        periodWarnings() {
            const warnings = [];
            const periodLength = this.periodLength();
            const payLag = this.payLag();
            const status = this.value('status');

            if (payLag !== null && payLag < 0) {
                warnings.push(this.t('periodPayBeforeEnd'));
            }

            if (status !== 'draft' && !this.hasValue('pay_date')) {
                warnings.push(this.t('periodAdvancedWithoutPayDate'));
            }

            if (periodLength !== null && periodLength > 40) {
                warnings.push(this.t('periodLongRange'));
            }

            if (!this.hasValue('period_name')) {
                warnings.push(this.t('periodNameMissing'));
            }

            if (this.hasValue('payroll_group_id') && this.candidateCount() === 0) {
                warnings.push(this.t('periodNoCandidates'));
            }

            return warnings;
        },
    };
};

window.createPayrollInputWizard = function createPayrollInputWizard(config = {}) {
    return {
        ...window.createWizard(config),
        preview: config.preview ?? { periods: {}, employees: {}, components: {} },
        selectedPeriod() {
            return this.preview.periods?.[this.value('payroll_period_id')] ?? null;
        },
        selectedEmployee() {
            return this.preview.employees?.[this.value('employee_id')] ?? null;
        },
        selectedComponent() {
            return this.preview.components?.[this.value('payroll_component_id')] ?? null;
        },
        estimatedAmount() {
            const fixedAmount = this.number('amount', null);
            const quantity = this.number('quantity', null);
            const rate = this.number('rate', null);

            if (fixedAmount !== null) {
                return fixedAmount;
            }

            if (quantity !== null && rate !== null) {
                return quantity * rate;
            }

            return null;
        },
        componentImpactSign() {
            const component = this.selectedComponent();

            if (!component?.affectsTakeHomePay) {
                return 0;
            }

            if (['deduction', 'tax'].includes(component.category)) {
                return -1;
            }

            if (component.category === 'employer_cost') {
                return 0;
            }

            return 1;
        },
        estimatedTakeHomeDelta() {
            if (!this.field('is_active')?.checked) {
                return 0;
            }

            return (this.estimatedAmount() ?? 0) * this.componentImpactSign();
        },
        estimatedBaseTakeHome() {
            const employee = this.selectedEmployee();

            return employee ? Number(employee.basicSalary ?? 0) : null;
        },
        estimatedTakeHomeAfterInput() {
            const base = this.estimatedBaseTakeHome();

            if (base === null) {
                return null;
            }

            return base + this.estimatedTakeHomeDelta();
        },
        periodEmployeeGroupMatches() {
            const period = this.selectedPeriod();
            const employee = this.selectedEmployee();

            if (!period || !employee) {
                return true;
            }

            return String(period.groupId ?? '') === String(employee.groupId ?? '');
        },
        calculationMode() {
            return this.number('amount', null) !== null
                ? (this.labels.fixedAmountMode ?? 'Fixed amount')
                : (this.labels.quantityRateMode ?? 'Quantity x rate');
        },
        payrollInputWarnings() {
            const warnings = [];
            const fixedAmount = this.number('amount', null);
            const quantity = this.number('quantity', null);
            const rate = this.number('rate', null);

            if (fixedAmount === null && (quantity === null || rate === null)) {
                warnings.push(this.t('inputIncompleteAmount'));
            }

            if (fixedAmount !== null && (quantity !== null || rate !== null)) {
                warnings.push(this.t('inputFixedOverridesFormula'));
            }

            if (!this.hasValue('notes')) {
                warnings.push(this.t('inputNotesMissing'));
            }

            if (!this.field('is_active')?.checked && (this.field('is_taxable')?.checked || this.field('is_bpjs_applicable')?.checked)) {
                warnings.push(this.t('inputInactiveFlags'));
            }

            if (!this.periodEmployeeGroupMatches()) {
                warnings.push(this.t('inputGroupMismatch'));
            }

            if (this.hasValue('employee_id') && !this.selectedEmployee()?.groupId) {
                warnings.push(this.t('inputNoEmployeeProfile'));
            }

            if (!this.field('is_active')?.checked && this.estimatedAmount() !== null) {
                warnings.push(this.t('inputInactiveNoImpact'));
            }

            if (this.selectedComponent() && this.componentImpactSign() === 0) {
                warnings.push(this.t('inputNoTakeHomeImpact'));
            }

            return warnings;
        },
    };
};

const APP_INVALID_FIELD_CLASS = 'app-invalid-field';
const APP_INVALID_LABEL_CLASS = 'app-invalid-label';
const APP_INVALID_GROUP_CLASS = 'app-invalid-group';
const APP_FIELD_ERROR_SELECTOR = '[data-app-generated-error]';
const APP_SUMMARY_SELECTOR = '[data-app-validation-summary]';

function parseAppFeedbackPayload() {
    const source = document.querySelector('[data-app-feedback]');

    if (!source) {
        return { toasts: [], errors: {}, summary: null };
    }

    try {
        return JSON.parse(source.dataset.payload ?? '{}');
    } catch (error) {
        console.error('Failed to parse feedback payload.', error);

        return { toasts: [], errors: {}, summary: null };
    }
}

function convertDotNotationToBrackets(field) {
    const parts = String(field).split('.');

    if (parts.length <= 1) {
        return field;
    }

    return parts.reduce((carry, part, index) => (index === 0 ? part : `${carry}[${part}]`), '');
}

function findField(fieldName) {
    const variants = [fieldName, convertDotNotationToBrackets(fieldName)];

    for (const variant of variants) {
        const field = document.querySelector(`[name="${CSS.escape(variant)}"]`);

        if (field) {
            return field;
        }
    }

    return null;
}

function clearGeneratedValidationState() {
    document.querySelectorAll(APP_FIELD_ERROR_SELECTOR).forEach((element) => element.remove());
    document.querySelectorAll(APP_SUMMARY_SELECTOR).forEach((element) => element.remove());

    document.querySelectorAll('[data-app-invalid-field]').forEach((field) => {
        field.classList.remove(APP_INVALID_FIELD_CLASS);
        field.removeAttribute('data-app-invalid-field');
        field.removeAttribute('aria-invalid');
        field.removeAttribute('aria-describedby');
    });

    document.querySelectorAll('[data-app-invalid-group]').forEach((group) => {
        group.classList.remove(APP_INVALID_GROUP_CLASS);
        group.removeAttribute('data-app-invalid-group');
    });

    document.querySelectorAll('[data-app-invalid-label]').forEach((label) => {
        label.classList.remove(APP_INVALID_LABEL_CLASS);
        label.removeAttribute('data-app-invalid-label');
    });
}

function appendValidationSummary(summary, form) {
    if (!summary || !form) {
        return;
    }

    const panel = document.createElement('div');
    const title = document.createElement('p');
    const message = document.createElement('p');

    panel.className = 'app-validation-summary';
    panel.dataset.appValidationSummary = 'true';

    title.className = 'app-validation-summary__title';
    title.textContent = summary.title;

    message.className = 'app-validation-summary__message';
    message.textContent = summary.message;

    panel.append(title, message);
    form.insertAdjacentElement('afterbegin', panel);
}

function appendFieldError(field, message) {
    const targetContainer = field.closest('[data-flux-field]') ?? field.parentElement;

    if (!targetContainer) {
        return;
    }

    const messageElement = document.createElement('p');
    messageElement.className = 'app-field-error';
    messageElement.dataset.appGeneratedError = 'true';
    messageElement.textContent = message;

    const errorId = `${field.name.replace(/[^a-zA-Z0-9_-]/g, '-')}-error`;
    messageElement.id = errorId;

    field.setAttribute('aria-describedby', errorId);
    targetContainer.appendChild(messageElement);
}

function applyFieldErrors(errors) {
    const entries = Object.entries(errors ?? {});

    if (!entries.length) {
        return null;
    }

    let firstInvalidField = null;

    entries.forEach(([fieldName, message]) => {
        const field = findField(fieldName);

        if (!field) {
            return;
        }

        field.classList.add(APP_INVALID_FIELD_CLASS);
        field.dataset.appInvalidField = 'true';
        field.setAttribute('aria-invalid', 'true');

        const label = field.id ? document.querySelector(`label[for="${CSS.escape(field.id)}"]`) : null;
        const fluxGroup = field.closest('[data-flux-field]');

        if (label) {
            label.classList.add(APP_INVALID_LABEL_CLASS);
            label.dataset.appInvalidLabel = 'true';
        }

        if (fluxGroup) {
            fluxGroup.classList.add(APP_INVALID_GROUP_CLASS);
            fluxGroup.dataset.appInvalidGroup = 'true';
        }

        if (field.type === 'checkbox' || field.type === 'radio') {
            const group = field.closest('label') ?? field.parentElement;

            if (group) {
                group.classList.add(APP_INVALID_GROUP_CLASS);
                group.dataset.appInvalidGroup = 'true';
            }
        }

        appendFieldError(field, message);

        if (!firstInvalidField) {
            firstInvalidField = field;
        }
    });

    if (firstInvalidField) {
        firstInvalidField.focus({ preventScroll: true });
        firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    return firstInvalidField;
}

function removeToast(toast) {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(-8px)';

    window.setTimeout(() => toast.remove(), 180);
}

function renderToasts(toasts) {
    const viewport = document.querySelector('[data-app-toast-viewport]');

    if (!viewport) {
        return;
    }

    viewport.querySelectorAll('[data-app-toast]').forEach((toast) => toast.remove());

    toasts.forEach((toast, index) => {
        const element = document.createElement('article');
        const row = document.createElement('div');
        const body = document.createElement('div');
        const title = document.createElement('p');
        const message = document.createElement('p');
        const close = document.createElement('button');

        element.className = `app-toast app-toast--${toast.type ?? 'info'}`;
        element.dataset.appToast = 'true';
        element.style.opacity = '0';
        element.style.transform = 'translateY(-8px)';
        element.style.transition = 'opacity 180ms ease, transform 180ms ease';

        row.className = 'flex items-start gap-3';
        body.className = 'min-w-0 flex-1';

        title.className = 'app-toast__title';
        title.textContent = toast.title ?? '';

        message.className = 'app-toast__message';
        message.textContent = toast.message ?? '';

        close.type = 'button';
        close.className = 'app-toast__close';
        close.dataset.appToastClose = 'true';
        close.innerHTML = '&times;';
        close.addEventListener('click', () => removeToast(element));

        body.append(title, message);
        row.append(body, close);
        element.append(row);

        viewport.appendChild(element);

        window.setTimeout(() => {
            element.style.opacity = '1';
            element.style.transform = 'translateY(0)';
        }, 30);

        window.setTimeout(() => removeToast(element), 4800 + index * 350);
    });
}

function bootAppFeedback() {
    clearGeneratedValidationState();

    const payload = parseAppFeedbackPayload();
    const firstInvalidField = applyFieldErrors(payload.errors);
    const targetForm = firstInvalidField?.closest('form') ?? document.querySelector('form');

    appendValidationSummary(payload.summary, targetForm);
    renderToasts(payload.toasts ?? []);
}

document.addEventListener('DOMContentLoaded', bootAppFeedback);
document.addEventListener('livewire:navigated', bootAppFeedback);
