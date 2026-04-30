<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Organization\Models\Organization;
use Modules\Payroll\Models\PayrollGroup;
use Modules\Payroll\Models\PayrollPeriod;

uses(RefreshDatabase::class);

it('returns the standardized payroll periods response envelope', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Administrator');

    $organization = Organization::query()->create([
        'code' => 'HQ',
        'name' => 'Head Office',
        'is_active' => true,
    ]);

    $payrollGroup = PayrollGroup::query()->create([
        'code' => 'MTH-HQ',
        'name' => 'Monthly HQ',
        'organization_id' => $organization->id,
        'pay_frequency' => 'monthly',
        'payroll_day' => 25,
    ]);

    PayrollPeriod::query()->create([
        'payroll_group_id' => $payrollGroup->id,
        'period_name' => 'April 2026',
        'start_date' => '2026-04-01',
        'end_date' => '2026-04-30',
        'pay_date' => '2026-04-25',
        'status' => 'draft',
    ]);

    $this->actingAs($user)
        ->getJson('/api/v1/payroll-periods')
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                [
                    'id',
                    'period_name',
                    'status',
                    'start_date',
                    'end_date',
                    'pay_date',
                    'runs_count',
                ],
            ],
            'meta' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
            ],
        ])
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.0.period_name', 'April 2026');
});

it('forbids non admin users from accessing payroll period api', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Staff');

    $this->actingAs($user)
        ->getJson('/api/v1/payroll-periods')
        ->assertForbidden();
});
