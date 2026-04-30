<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Organization\Models\Employee;
use Modules\Organization\Models\Organization;
use Modules\Payroll\Models\PayrollGroup;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('allows administrators to create organizations', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Administrator');

    $this->actingAs($admin)
        ->post(route('organization.store'), [
            'code' => 'OPS',
            'name' => 'Operations',
            'type' => 'Division',
            'is_active' => '1',
        ])
        ->assertRedirect(route('organization.index'));

    expect(Organization::query()->where('code', 'OPS')->exists())->toBeTrue();
});

it('allows administrators to create employees with login accounts', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Administrator');

    $organization = Organization::query()->create([
        'code' => 'HQ',
        'name' => 'Head Office',
        'is_active' => true,
    ]);

    $payrollGroup = PayrollGroup::query()->create([
        'code' => 'MONTHLY-HQ',
        'name' => 'Monthly HQ',
        'organization_id' => $organization->id,
        'pay_frequency' => 'monthly',
        'payroll_day' => 25,
    ]);

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'employee_number' => 'EMP-001',
            'full_name' => 'Sari Admin',
            'email' => 'sari@example.com',
            'employment_status' => 'active',
            'organization_id' => $organization->id,
            'hire_date' => '2026-04-01',
            'create_login_account' => '1',
            'account_role' => 'Staff',
            'password' => 'Password123!',
            'basic_salary' => '7500000',
            'payroll_group_id' => $payrollGroup->id,
            'payment_type' => 'monthly',
            'is_taxable' => '1',
        ])
        ->assertRedirect(route('users.index'));

    $employee = Employee::query()->where('employee_number', 'EMP-001')->first();

    expect($employee)->not->toBeNull();
    expect($employee->user)->not->toBeNull();
    expect($employee->user->hasRole('Staff'))->toBeTrue();
    expect((float) $employee->payrollProfile->basic_salary)->toBe(7500000.0);
});
