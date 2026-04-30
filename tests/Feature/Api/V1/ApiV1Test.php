<?php

use App\Models\User;
use Database\Seeders\CoreMasterDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Organization\Models\Employee;
use Modules\Organization\Models\Organization;
use Modules\Payroll\Models\EmployeePayrollProfile;
use Modules\Payroll\Models\PayrollGroup;
use Modules\Payroll\Models\PayrollPeriod;
use Modules\Payroll\Models\PayrollRun;
use Modules\Payroll\Models\TaxStatus;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([
        RolesAndPermissionsSeeder::class,
        CoreMasterDataSeeder::class,
    ]);
});

it('returns dashboard and organization api payloads for administrator', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Administrator');

    Sanctum::actingAs($admin);

    $this
        ->getJson('/api/v1/dashboard/overview')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'metrics',
                'payroll_snapshot',
                'recent_periods',
                'recent_runs',
            ],
        ]);

    $this
        ->getJson('/api/v1/organizations')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'message',
            'data',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
});

it('creates and updates organization records through the api', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Administrator');

    Sanctum::actingAs($admin);

    $createResponse = $this
        ->postJson('/api/v1/organizations', [
            'code' => 'API-HQ',
            'name' => 'API Headquarter',
            'type' => 'Head Office',
            'is_active' => true,
        ]);

    $createResponse
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.code', 'API-HQ');

    $organizationId = $createResponse->json('data.id');

    $this
        ->putJson("/api/v1/organizations/{$organizationId}", [
            'code' => 'API-HQ',
            'name' => 'API Headquarter Updated',
            'type' => 'Head Office',
            'is_active' => false,
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'API Headquarter Updated')
        ->assertJsonPath('data.is_active', false);
});

it('processes payroll and returns workflow payloads through the api', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Administrator');

    Sanctum::actingAs($admin);

    $organization = Organization::query()->where('code', 'HQ')->firstOrFail();
    $payrollGroup = PayrollGroup::query()->where('code', 'MONTHLY-HQ')->firstOrFail();
    $taxStatus = TaxStatus::query()->where('code', 'TK0')->firstOrFail();

    $employee = Employee::query()->create([
        'organization_id' => $organization->id,
        'employee_number' => 'EMP-API-1',
        'full_name' => 'API Workflow Tester',
        'employment_status' => 'active',
        'hire_date' => '2026-04-01',
    ]);

    EmployeePayrollProfile::query()->create([
        'employee_id' => $employee->id,
        'employee_code' => $employee->employee_number,
        'tax_status_id' => $taxStatus->id,
        'payroll_group_id' => $payrollGroup->id,
        'basic_salary' => 7000000,
        'payment_type' => 'monthly',
        'join_date' => '2026-04-01',
        'is_taxable' => true,
    ]);

    $period = PayrollPeriod::query()->create([
        'payroll_group_id' => $payrollGroup->id,
        'period_name' => 'API April 2026',
        'start_date' => '2026-04-01',
        'end_date' => '2026-04-30',
        'pay_date' => '2026-04-25',
        'status' => 'draft',
    ]);

    $this
        ->postJson("/api/v1/payroll-periods/{$period->id}/process")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.processed_count', 1);

    $run = PayrollRun::query()->where('payroll_period_id', $period->id)->firstOrFail();

    $this
        ->postJson("/api/v1/payroll-runs/{$run->id}/approve", [
            'approval_notes' => 'API approval note.',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $this
        ->getJson('/api/v1/workflows')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'summary' => ['pending_approvals', 'returned_runs', 'payment_queue', 'recent_logs'],
                'pending_approvals',
                'returned_runs',
                'payment_queue',
                'recent_logs',
            ],
        ]);
});

it('authenticates api users with sanctum tokens', function () {
    $admin = User::factory()->create([
        'email' => 'api-admin@example.com',
        'password' => 'password',
    ]);
    $admin->assignRole('Administrator');

    $loginResponse = $this->postJson('/api/v1/auth/login', [
        'email' => 'api-admin@example.com',
        'password' => 'password',
        'device_name' => 'pest-suite',
    ]);

    $loginResponse
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.token_type', 'Bearer');

    $token = $loginResponse->json('data.access_token');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.email', 'api-admin@example.com');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/auth/logout')
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($admin->fresh()->tokens()->count())->toBe(0);
});
