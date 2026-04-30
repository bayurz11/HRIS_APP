<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Modules\Organization\Models\Employee;
use Modules\Organization\Models\Organization;
use Modules\Payroll\Models\PayrollGroup;
use Modules\Payroll\Models\TaxStatus;

class EmployeeController extends Controller
{
    public function index()
    {
        return view('modules.users.index', [
            'employees' => Employee::query()
                ->with(['organization', 'user', 'payrollProfile.payrollGroup'])
                ->orderBy('full_name')
                ->paginate(10),
            'stats' => [
                'employee_count' => Employee::query()->count(),
                'account_count' => User::query()->count(),
            ],
        ]);
    }

    public function create()
    {
        return view('modules.users.create', [
            'employee' => new Employee(['employment_status' => 'active']),
            'organizations' => Organization::query()->orderBy('name')->get(),
            'payrollGroups' => PayrollGroup::query()->orderBy('name')->get(),
            'taxStatuses' => TaxStatus::query()->where('is_active', true)->orderBy('code')->get(),
            'roles' => $this->accountRoles(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateEmployee($request);

        DB::transaction(function () use ($request, $validated): void {
            $user = $this->upsertLinkedUser(null, $request, $validated);

            $employee = Employee::query()->create([
                'user_id' => $user?->id,
                'organization_id' => $validated['organization_id'] ?? null,
                'employee_number' => $validated['employee_number'],
                'full_name' => $validated['full_name'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'employment_status' => $validated['employment_status'],
                'hire_date' => $validated['hire_date'] ?? null,
                'timezone' => $validated['timezone'] ?? null,
            ]);

            $this->syncPayrollProfile($employee, $validated);
        });

        return redirect()
            ->route('users.index')
            ->with('status', 'Karyawan berhasil dibuat.');
    }

    public function edit(Employee $user)
    {
        $user->load(['user.roles', 'organization', 'payrollProfile']);

        return view('modules.users.edit', [
            'employee' => $user,
            'organizations' => Organization::query()->orderBy('name')->get(),
            'payrollGroups' => PayrollGroup::query()->orderBy('name')->get(),
            'taxStatuses' => TaxStatus::query()->where('is_active', true)->orderBy('code')->get(),
            'roles' => $this->accountRoles(),
        ]);
    }

    public function update(Request $request, Employee $user): RedirectResponse
    {
        $employee = $user;
        $validated = $this->validateEmployee($request, $employee);

        DB::transaction(function () use ($employee, $request, $validated): void {
            $linkedUser = $this->upsertLinkedUser($employee, $request, $validated);

            $employee->update([
                'user_id' => $linkedUser?->id,
                'organization_id' => $validated['organization_id'] ?? null,
                'employee_number' => $validated['employee_number'],
                'full_name' => $validated['full_name'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'employment_status' => $validated['employment_status'],
                'hire_date' => $validated['hire_date'] ?? null,
                'resign_date' => $validated['resign_date'] ?? null,
                'timezone' => $validated['timezone'] ?? null,
            ]);

            $this->syncPayrollProfile($employee, $validated);
        });

        return redirect()
            ->route('users.index')
            ->with('status', 'Karyawan berhasil diperbarui.');
    }

    public function destroy(Employee $user): RedirectResponse
    {
        if ($user->payrollRuns()->exists()) {
            return back()->with('error', 'Karyawan tidak bisa dihapus karena payroll run sudah terbentuk.');
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('status', 'Karyawan berhasil dihapus.');
    }

    protected function validateEmployee(Request $request, ?Employee $employee = null): array
    {
        $createLoginAccount = $request->boolean('create_login_account') || $employee?->user !== null;
        $linkedUserId = $employee?->user?->id;

        return $request->validate([
            'employee_number' => ['required', 'string', 'max:50', Rule::unique('employees', 'employee_number')->ignore($employee?->id)],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                Rule::requiredIf($createLoginAccount),
                'nullable',
                'email',
                'max:255',
                Rule::unique('employees', 'email')->ignore($employee?->id),
                Rule::unique('users', 'email')->ignore($linkedUserId),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'employment_status' => ['required', Rule::in(['active', 'probation', 'inactive'])],
            'hire_date' => ['nullable', 'date'],
            'resign_date' => ['nullable', 'date', 'after_or_equal:hire_date'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'create_login_account' => ['nullable', 'boolean'],
            'account_role' => [Rule::requiredIf($createLoginAccount), 'nullable', Rule::in($this->accountRoles())],
            'password' => [Rule::requiredIf($createLoginAccount && $linkedUserId === null), 'nullable', 'string', 'min:8'],
            'basic_salary' => ['nullable', 'numeric', 'min:0'],
            'payroll_group_id' => ['nullable', 'exists:payroll_groups,id'],
            'tax_status_id' => ['nullable', 'exists:tax_statuses,id'],
            'payment_type' => ['nullable', Rule::in(['monthly', 'daily', 'hourly'])],
            'is_taxable' => ['nullable', 'boolean'],
            'is_overtime_eligible' => ['nullable', 'boolean'],
        ]);
    }

    protected function upsertLinkedUser(?Employee $employee, Request $request, array $validated): ?User
    {
        $shouldHaveAccount = $request->boolean('create_login_account') || $employee?->user !== null;

        if (! $shouldHaveAccount) {
            return null;
        }

        $user = $employee?->user ?? new User();

        $user->name = $validated['full_name'];
        $user->email = $validated['email'];
        $user->email_verified_at ??= now();

        if (filled($validated['password'] ?? null)) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();
        $user->syncRoles([$validated['account_role'] ?? 'Staff']);

        return $user;
    }

    protected function accountRoles(): array
    {
        return [
            'Administrator',
            'Payroll Officer',
            'Payroll Approver',
            'Finance Approver',
            'Employee',
            'Staff',
        ];
    }

    protected function syncPayrollProfile(Employee $employee, array $validated): void
    {
        $hasPayrollData = filled($validated['basic_salary'] ?? null)
            || filled($validated['payroll_group_id'] ?? null)
            || filled($validated['tax_status_id'] ?? null)
            || filled($validated['payment_type'] ?? null);

        if (! $hasPayrollData && $employee->payrollProfile === null) {
            return;
        }

        $profile = $employee->payrollProfiles()->firstOrNew();

        $profile->fill([
            'employee_code' => $employee->employee_number,
            'tax_status_id' => $validated['tax_status_id'] ?? null,
            'payroll_group_id' => $validated['payroll_group_id'] ?? null,
            'basic_salary' => $validated['basic_salary'] ?? 0,
            'payment_type' => $validated['payment_type'] ?? 'monthly',
            'join_date' => $validated['hire_date'] ?? null,
            'resign_date' => $validated['resign_date'] ?? null,
            'is_taxable' => ($validated['is_taxable'] ?? null) !== null ? (bool) $validated['is_taxable'] : true,
            'is_bpjs_kesehatan_enrolled' => true,
            'is_bpjs_tk_enrolled' => true,
            'is_overtime_eligible' => (bool) ($validated['is_overtime_eligible'] ?? false),
        ]);

        $employee->payrollProfiles()->save($profile);
    }
}
