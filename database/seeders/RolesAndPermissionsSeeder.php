<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',
            'organization.manage',
            'users.manage',
            'payroll.manage',
            'payroll.approve',
            'payroll.pay',
            'payslip.self-service',
            'reports.view',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $administrator = Role::findOrCreate('Administrator', 'web');
        $payrollOfficer = Role::findOrCreate('Payroll Officer', 'web');
        $payrollApprover = Role::findOrCreate('Payroll Approver', 'web');
        $financeApprover = Role::findOrCreate('Finance Approver', 'web');
        $employee = Role::findOrCreate('Employee', 'web');
        $staff = Role::findOrCreate('Staff', 'web');

        $administrator->syncPermissions($permissions);
        $payrollOfficer->syncPermissions(['dashboard.view', 'payroll.manage']);
        $payrollApprover->syncPermissions(['dashboard.view', 'payroll.manage', 'payroll.approve']);
        $financeApprover->syncPermissions(['dashboard.view', 'payroll.manage', 'payroll.approve', 'payroll.pay']);
        $employee->syncPermissions(['dashboard.view', 'payslip.self-service']);
        $staff->syncPermissions(['dashboard.view']);
    }
}
