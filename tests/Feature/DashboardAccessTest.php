<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects guests away from the dashboard', function () {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));
});

it('allows authenticated users to access dashboard and payroll pages', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Staff');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Pusat Kendali HARIS');
});

it('blocks staff users from admin modules', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Staff');

    $this->actingAs($user)
        ->get(route('organization.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('payroll.index'))
        ->assertForbidden();
});
