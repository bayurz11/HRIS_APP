<?php

use App\Models\User;
use Database\Seeders\CoreMasterDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Attendance\Models\AttendanceRecord;
use Modules\Attendance\Models\AttendanceSummary;
use Modules\AuditTrail\Models\AuditLog;
use Modules\LeaveManagement\Models\LeaveRequest;
use Modules\Organization\Models\Employee;
use Modules\Organization\Models\Organization;
use Modules\Payroll\Models\EmployeePayrollComponent;
use Modules\Payroll\Models\EmployeePayrollProfile;
use Modules\Payroll\Models\PayrollComponent;
use Modules\Payroll\Models\PayrollGroup;
use Modules\Payroll\Models\PayrollPeriod;
use Modules\Payroll\Models\PayrollPeriodInput;
use Modules\Payroll\Models\PayrollRun;
use Modules\Payroll\Models\TaxStatus;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([
        RolesAndPermissionsSeeder::class,
        CoreMasterDataSeeder::class,
    ]);
});

it('processes a payroll period into payroll runs and workflow logs', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Administrator');

    $organization = Organization::query()->where('code', 'HQ')->firstOrFail();
    $payrollGroup = PayrollGroup::query()->where('code', 'MONTHLY-HQ')->firstOrFail();
    $taxStatus = TaxStatus::query()->where('code', 'TK0')->firstOrFail();

    $employee = Employee::query()->create([
        'organization_id' => $organization->id,
        'employee_number' => 'EMP-100',
        'full_name' => 'Payroll Tester',
        'employment_status' => 'active',
        'hire_date' => '2026-04-01',
    ]);

    $profile = EmployeePayrollProfile::query()->create([
        'employee_id' => $employee->id,
        'employee_code' => $employee->employee_number,
        'tax_status_id' => $taxStatus->id,
        'payroll_group_id' => $payrollGroup->id,
        'basic_salary' => 10000000,
        'payment_type' => 'monthly',
        'join_date' => '2026-04-01',
        'is_taxable' => true,
    ]);

    EmployeePayrollComponent::query()->create([
        'employee_id' => $employee->id,
        'payroll_component_id' => PayrollComponent::query()->where('code', 'ALLOW_TRANSPORT')->firstOrFail()->id,
        'amount' => 500000,
        'is_active' => true,
    ]);

    $period = PayrollPeriod::query()->create([
        'payroll_group_id' => $payrollGroup->id,
        'period_name' => 'April 2026',
        'start_date' => '2026-04-01',
        'end_date' => '2026-04-30',
        'pay_date' => '2026-04-25',
        'status' => 'draft',
    ]);

    $this->actingAs($admin)
        ->post(route('payroll.periods.process', $period))
        ->assertRedirect(route('payroll.runs.index', ['period_id' => $period->id]));

    $run = PayrollRun::query()->where('payroll_period_id', $period->id)->first();

    expect($run)->not->toBeNull();
    expect($run->employee_payroll_profile_id)->toBe($profile->id);
    expect($run->calculation_status->value)->toBe('calculated');
    expect((float) $run->gross_salary)->toBe(10500000.0);
    expect((float) $run->total_pph21)->toBe(262500.0);
    expect((float) $run->total_bpjs_employee)->toBe(400000.0);
    expect((float) $run->take_home_pay)->toBe(9837500.0);
    expect($run->items()->count())->toBe(11);
    expect($run->approvalSteps()->count())->toBe(2);
    expect($run->workflowLogs()->count())->toBe(1);
    expect($run->payslip)->not->toBeNull();
});

it('approves pays and downloads a payslip pdf', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Administrator');

    $organization = Organization::query()->where('code', 'HQ')->firstOrFail();
    $payrollGroup = PayrollGroup::query()->where('code', 'MONTHLY-HQ')->firstOrFail();
    $taxStatus = TaxStatus::query()->where('code', 'TK0')->firstOrFail();

    $employee = Employee::query()->create([
        'organization_id' => $organization->id,
        'employee_number' => 'EMP-200',
        'full_name' => 'PDF Tester',
        'employment_status' => 'active',
        'hire_date' => '2026-04-01',
    ]);

    EmployeePayrollProfile::query()->create([
        'employee_id' => $employee->id,
        'employee_code' => $employee->employee_number,
        'tax_status_id' => $taxStatus->id,
        'payroll_group_id' => $payrollGroup->id,
        'basic_salary' => 8000000,
        'payment_type' => 'monthly',
        'join_date' => '2026-04-01',
        'is_taxable' => true,
    ]);

    $period = PayrollPeriod::query()->create([
        'payroll_group_id' => $payrollGroup->id,
        'period_name' => 'May 2026',
        'start_date' => '2026-05-01',
        'end_date' => '2026-05-31',
        'pay_date' => '2026-05-25',
        'status' => 'draft',
    ]);

    $this->actingAs($admin)->post(route('payroll.periods.process', $period));

    $run = PayrollRun::query()->where('payroll_period_id', $period->id)->firstOrFail();

    $this->actingAs($admin)
        ->post(route('payroll.runs.approve', $run), [
            'approval_notes' => 'Semua komponen payroll sudah sesuai.',
        ])
        ->assertSessionHas('status');

    $run->refresh();
    expect($run->calculation_status->value)->toBe('calculated');
    expect($run->approvalSteps()->orderBy('step_order')->first()->notes)->toBe('Semua komponen payroll sudah sesuai.');

    $this->actingAs($admin)
        ->post(route('payroll.runs.approve', $run))
        ->assertSessionHas('status');

    $run->refresh();
    expect($run->calculation_status->value)->toBe('approved');

    $this->actingAs($admin)
        ->post(route('payroll.runs.mark-paid', $run))
        ->assertSessionHas('status');

    $run->refresh();
    $payslip = $run->payslip()->firstOrFail();

    expect($run->calculation_status->value)->toBe('paid');
    expect($payslip->is_published)->toBeTrue();

    $this->actingAs($admin)
        ->get(route('payroll.payslips.download', $payslip))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('stores approval comments and return reasons in the workflow trail', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Administrator');

    $organization = Organization::query()->where('code', 'HQ')->firstOrFail();
    $payrollGroup = PayrollGroup::query()->where('code', 'MONTHLY-HQ')->firstOrFail();
    $taxStatus = TaxStatus::query()->where('code', 'TK0')->firstOrFail();

    $employee = Employee::query()->create([
        'organization_id' => $organization->id,
        'employee_number' => 'EMP-250',
        'full_name' => 'Workflow Comment Tester',
        'employment_status' => 'active',
        'hire_date' => '2026-05-01',
    ]);

    EmployeePayrollProfile::query()->create([
        'employee_id' => $employee->id,
        'employee_code' => $employee->employee_number,
        'tax_status_id' => $taxStatus->id,
        'payroll_group_id' => $payrollGroup->id,
        'basic_salary' => 6500000,
        'payment_type' => 'monthly',
        'join_date' => '2026-05-01',
        'is_taxable' => true,
    ]);

    $period = PayrollPeriod::query()->create([
        'payroll_group_id' => $payrollGroup->id,
        'period_name' => 'Comment May 2026',
        'start_date' => '2026-05-01',
        'end_date' => '2026-05-31',
        'pay_date' => '2026-05-25',
        'status' => 'draft',
    ]);

    $this->actingAs($admin)->post(route('payroll.periods.process', $period));
    $run = PayrollRun::query()->where('payroll_period_id', $period->id)->firstOrFail();

    $this->actingAs($admin)
        ->post(route('payroll.runs.approve', $run), [
            'approval_notes' => 'Cek absensi dan komponen lembur sudah valid.',
        ])
        ->assertSessionHas('status');

    $run->refresh();

    expect($run->approvalSteps()->orderBy('step_order')->first()->notes)->toBe('Cek absensi dan komponen lembur sudah valid.');
    expect($run->workflowLogs()->where('action', 'approval_step_completed')->latest()->first()?->notes)
        ->toContain('Cek absensi dan komponen lembur sudah valid.');

    $this->actingAs($admin)
        ->post(route('payroll.runs.return', $run), [
            'return_reason' => 'Mohon periksa kembali potongan absensi sebelum diajukan ulang.',
        ])
        ->assertSessionHas('status');

    $run->refresh();

    expect($run->calculation_status->value)->toBe('draft');
    expect($run->workflowLogs()->where('action', 'returned_to_draft')->latest()->first()?->notes)
        ->toBe('Mohon periksa kembali potongan absensi sebelum diajukan ulang.');
});

it('applies payroll variable inputs into processed payroll runs', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Administrator');

    $organization = Organization::query()->where('code', 'HQ')->firstOrFail();
    $payrollGroup = PayrollGroup::query()->where('code', 'MONTHLY-HQ')->firstOrFail();
    $taxStatus = TaxStatus::query()->where('code', 'TK0')->firstOrFail();

    $employee = Employee::query()->create([
        'organization_id' => $organization->id,
        'employee_number' => 'EMP-300',
        'full_name' => 'Variable Input Tester',
        'employment_status' => 'active',
        'hire_date' => '2026-04-01',
    ]);

    EmployeePayrollProfile::query()->create([
        'employee_id' => $employee->id,
        'employee_code' => $employee->employee_number,
        'tax_status_id' => $taxStatus->id,
        'payroll_group_id' => $payrollGroup->id,
        'basic_salary' => 5000000,
        'payment_type' => 'monthly',
        'join_date' => '2026-04-01',
        'is_taxable' => false,
        'is_bpjs_kesehatan_enrolled' => false,
        'is_bpjs_tk_enrolled' => false,
    ]);

    $period = PayrollPeriod::query()->create([
        'payroll_group_id' => $payrollGroup->id,
        'period_name' => 'June 2026',
        'start_date' => '2026-06-01',
        'end_date' => '2026-06-30',
        'pay_date' => '2026-06-25',
        'status' => 'draft',
    ]);

    PayrollPeriodInput::query()->create([
        'payroll_period_id' => $period->id,
        'employee_id' => $employee->id,
        'payroll_component_id' => PayrollComponent::query()->where('code', 'OVERTIME')->firstOrFail()->id,
        'input_code' => 'OVERTIME',
        'input_name' => 'Overtime',
        'component_type' => 'earning',
        'amount' => 300000,
        'is_taxable' => false,
        'is_bpjs_applicable' => false,
        'is_active' => true,
    ]);

    PayrollPeriodInput::query()->create([
        'payroll_period_id' => $period->id,
        'employee_id' => $employee->id,
        'payroll_component_id' => PayrollComponent::query()->where('code', 'LOAN_DED')->firstOrFail()->id,
        'input_code' => 'LOAN_DED',
        'input_name' => 'Loan Deduction',
        'component_type' => 'deduction',
        'amount' => 200000,
        'is_taxable' => false,
        'is_bpjs_applicable' => false,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('payroll.periods.process', $period))
        ->assertRedirect(route('payroll.runs.index', ['period_id' => $period->id]));

    $run = PayrollRun::query()->where('payroll_period_id', $period->id)->firstOrFail();

    expect((float) $run->gross_salary)->toBe(5300000.0);
    expect((float) $run->total_overtime)->toBe(300000.0);
    expect((float) $run->total_loan_deduction)->toBe(200000.0);
    expect((float) $run->total_absence_deduction)->toBe(0.0);
    expect((float) $run->total_deduction)->toBe(200000.0);
    expect((float) $run->total_pph21)->toBe(0.0);
    expect((float) $run->take_home_pay)->toBe(5100000.0);
    expect($run->items()->where('source_type', 'period_input')->count())->toBe(2);
});

it('syncs attendance and leave into payroll inputs automatically', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Administrator');

    $organization = Organization::query()->where('code', 'HQ')->firstOrFail();
    $payrollGroup = PayrollGroup::query()->where('code', 'MONTHLY-HQ')->firstOrFail();
    $taxStatus = TaxStatus::query()->where('code', 'TK0')->firstOrFail();

    $employee = Employee::query()->create([
        'organization_id' => $organization->id,
        'employee_number' => 'EMP-400',
        'full_name' => 'Attendance Sync Tester',
        'employment_status' => 'active',
        'hire_date' => '2026-07-01',
    ]);

    EmployeePayrollProfile::query()->create([
        'employee_id' => $employee->id,
        'employee_code' => $employee->employee_number,
        'tax_status_id' => $taxStatus->id,
        'payroll_group_id' => $payrollGroup->id,
        'basic_salary' => 5200000,
        'payment_type' => 'monthly',
        'join_date' => '2026-07-01',
        'is_taxable' => false,
        'is_bpjs_kesehatan_enrolled' => false,
        'is_bpjs_tk_enrolled' => false,
        'is_overtime_eligible' => true,
    ]);

    $period = PayrollPeriod::query()->create([
        'payroll_group_id' => $payrollGroup->id,
        'period_name' => 'July 2026',
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-31',
        'pay_date' => '2026-07-25',
        'status' => 'draft',
    ]);

    AttendanceRecord::query()->create([
        'employee_id' => $employee->id,
        'attendance_date' => '2026-07-01',
        'status' => 'present',
        'approved_overtime_hours' => 2,
        'worked_minutes' => 600,
    ]);

    AttendanceRecord::query()->create([
        'employee_id' => $employee->id,
        'attendance_date' => '2026-07-02',
        'status' => 'absent',
    ]);

    LeaveRequest::query()->create([
        'employee_id' => $employee->id,
        'leave_type' => 'unpaid',
        'start_date' => '2026-07-03',
        'end_date' => '2026-07-03',
        'total_days' => 1,
        'is_paid_leave' => false,
        'status' => 'approved',
        'approved_by' => $admin->id,
        'approved_at' => now(),
    ]);

    $this->actingAs($admin)
        ->post(route('payroll.periods.process', $period))
        ->assertRedirect(route('payroll.runs.index', ['period_id' => $period->id]));

    $summary = AttendanceSummary::query()
        ->where('employee_id', $employee->id)
        ->where('payroll_period_id', $period->id)
        ->firstOrFail();

    $run = PayrollRun::query()->where('payroll_period_id', $period->id)->firstOrFail();

    expect((float) $summary->total_overtime_hours)->toBe(2.0);
    expect((float) $summary->total_absent_days)->toBe(2.0);
    expect((float) $summary->total_unpaid_leave_days)->toBe(1.0);

    expect($employee->payrollPeriodInputs()->where('payroll_period_id', $period->id)->where('input_code', 'OVERTIME')->exists())->toBeTrue();
    expect($employee->payrollPeriodInputs()->where('payroll_period_id', $period->id)->where('input_code', 'ABSENCE_DED')->exists())->toBeTrue();
    expect((float) $run->total_overtime)->toBeGreaterThan(0.0);
    expect((float) $run->total_absence_deduction)->toBeGreaterThan(0.0);
    expect((float) $run->gross_salary)->toBeGreaterThan(5200000.0);
});

it('locks payroll source data after a payroll period has been processed', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Administrator');

    $organization = Organization::query()->where('code', 'HQ')->firstOrFail();
    $payrollGroup = PayrollGroup::query()->where('code', 'MONTHLY-HQ')->firstOrFail();
    $taxStatus = TaxStatus::query()->where('code', 'TK0')->firstOrFail();

    $employee = Employee::query()->create([
        'organization_id' => $organization->id,
        'employee_number' => 'EMP-450',
        'full_name' => 'Freeze Lock Tester',
        'employment_status' => 'active',
        'hire_date' => '2026-07-01',
    ]);

    EmployeePayrollProfile::query()->create([
        'employee_id' => $employee->id,
        'employee_code' => $employee->employee_number,
        'tax_status_id' => $taxStatus->id,
        'payroll_group_id' => $payrollGroup->id,
        'basic_salary' => 6000000,
        'payment_type' => 'monthly',
        'join_date' => '2026-07-01',
        'is_taxable' => false,
        'is_bpjs_kesehatan_enrolled' => false,
        'is_bpjs_tk_enrolled' => false,
    ]);

    $period = PayrollPeriod::query()->create([
        'payroll_group_id' => $payrollGroup->id,
        'period_name' => 'Lock Period July 2026',
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-31',
        'pay_date' => '2026-07-25',
        'status' => 'draft',
    ]);

    $attendance = AttendanceRecord::query()->create([
        'employee_id' => $employee->id,
        'attendance_date' => '2026-07-10',
        'status' => 'present',
        'approved_overtime_hours' => 1,
    ]);

    $leave = LeaveRequest::query()->create([
        'employee_id' => $employee->id,
        'leave_type' => 'annual',
        'start_date' => '2026-07-15',
        'end_date' => '2026-07-15',
        'total_days' => 1,
        'is_paid_leave' => true,
        'status' => 'approved',
        'approved_by' => $admin->id,
        'approved_at' => now(),
    ]);

    $input = PayrollPeriodInput::query()->create([
        'payroll_period_id' => $period->id,
        'employee_id' => $employee->id,
        'payroll_component_id' => PayrollComponent::query()->where('code', 'BONUS')->firstOrFail()->id,
        'input_code' => 'BONUS',
        'input_name' => 'Bonus',
        'component_type' => 'earning',
        'amount' => 250000,
        'is_taxable' => false,
        'is_bpjs_applicable' => false,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('payroll.periods.process', $period))
        ->assertRedirect(route('payroll.runs.index', ['period_id' => $period->id]));

    $period->refresh();

    expect($period->status->value)->toBe('processing');
    expect($period->isLocked())->toBeTrue();

    $this->actingAs($admin)
        ->from(route('payroll.periods.index'))
        ->put(route('payroll.periods.update', $period), [
            'payroll_group_id' => $payrollGroup->id,
            'period_name' => 'Changed Name',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
            'pay_date' => '2026-07-26',
            'status' => 'draft',
        ])
        ->assertRedirect(route('payroll.periods.index'))
        ->assertSessionHas('error');

    $this->actingAs($admin)
        ->from(route('payroll.attendance.index'))
        ->post(route('payroll.attendance.store'), [
            'employee_id' => $employee->id,
            'attendance_date' => '2026-07-20',
            'status' => 'present',
        ])
        ->assertSessionHas('error');

    $this->actingAs($admin)
        ->delete(route('payroll.attendance.destroy', $attendance))
        ->assertRedirect(route('payroll.attendance.index'))
        ->assertSessionHas('error');

    $this->actingAs($admin)
        ->from(route('payroll.leave.index'))
        ->post(route('payroll.leave.store'), [
            'employee_id' => $employee->id,
            'leave_type' => 'unpaid',
            'start_date' => '2026-07-20',
            'end_date' => '2026-07-21',
            'total_days' => 2,
            'is_paid_leave' => false,
            'status' => 'approved',
        ])
        ->assertSessionHas('error');

    $this->actingAs($admin)
        ->delete(route('payroll.leave.destroy', $leave))
        ->assertRedirect(route('payroll.leave.index'))
        ->assertSessionHas('error');

    $this->actingAs($admin)
        ->from(route('payroll.inputs.index', ['period_id' => $period->id]))
        ->post(route('payroll.inputs.store'), [
            'payroll_period_id' => $period->id,
            'employee_id' => $employee->id,
            'payroll_component_id' => PayrollComponent::query()->where('code', 'ADJUST_PLUS')->firstOrFail()->id,
            'amount' => 100000,
            'is_taxable' => false,
            'is_bpjs_applicable' => false,
            'is_active' => true,
        ])
        ->assertSessionHas('error');

    $this->actingAs($admin)
        ->delete(route('payroll.inputs.destroy', $input))
        ->assertRedirect(route('payroll.inputs.index', ['period_id' => $period->id]))
        ->assertSessionHas('error');

    expect($period->fresh()->period_name)->toBe('Lock Period July 2026');
    expect(AttendanceRecord::query()->whereKey($attendance->id)->exists())->toBeTrue();
    expect(AttendanceRecord::query()->where('employee_id', $employee->id)->count())->toBe(1);
    expect(LeaveRequest::query()->whereKey($leave->id)->exists())->toBeTrue();
    expect(LeaveRequest::query()->where('employee_id', $employee->id)->count())->toBe(1);
    expect(PayrollPeriodInput::query()->whereKey($input->id)->exists())->toBeTrue();
    expect(PayrollPeriodInput::query()
        ->where('payroll_period_id', $period->id)
        ->where('input_code', 'ADJUST_PLUS')
        ->exists())->toBeFalse();
});

it('exports bank transfer bpjs and pph21 csv from finalized payroll periods', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Administrator');

    $organization = Organization::query()->where('code', 'HQ')->firstOrFail();
    $payrollGroup = PayrollGroup::query()->where('code', 'MONTHLY-HQ')->firstOrFail();
    $taxStatus = TaxStatus::query()->where('code', 'TK0')->firstOrFail();

    $employee = Employee::query()->create([
        'organization_id' => $organization->id,
        'employee_number' => 'EMP-500',
        'full_name' => 'Export Tester',
        'employment_status' => 'active',
        'hire_date' => '2026-08-01',
    ]);

    EmployeePayrollProfile::query()->create([
        'employee_id' => $employee->id,
        'employee_code' => $employee->employee_number,
        'tax_status_id' => $taxStatus->id,
        'payroll_group_id' => $payrollGroup->id,
        'basic_salary' => 9000000,
        'payment_type' => 'monthly',
        'join_date' => '2026-08-01',
        'is_taxable' => true,
        'bank_name' => 'BCA',
        'bank_account_name' => 'Export Tester',
        'bank_account_number' => '1234567890',
    ]);

    $period = PayrollPeriod::query()->create([
        'payroll_group_id' => $payrollGroup->id,
        'period_name' => 'August 2026',
        'start_date' => '2026-08-01',
        'end_date' => '2026-08-31',
        'pay_date' => '2026-08-25',
        'status' => 'draft',
    ]);

    $this->actingAs($admin)->post(route('payroll.periods.process', $period));
    $run = PayrollRun::query()->where('payroll_period_id', $period->id)->firstOrFail();

    $this->actingAs($admin)->post(route('payroll.runs.approve', $run));
    $this->actingAs($admin)->post(route('payroll.runs.approve', $run));

    $period->refresh();
    expect($period->status->value)->toBe('finalized');

    $bankResponse = $this->actingAs($admin)
        ->get(route('payroll.exports.bank-transfer', $period));

    $bankResponse->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    expect($bankResponse->getContent())->toContain('bank_account_number');
    expect($bankResponse->getContent())->toContain('1234567890');

    $bpjsResponse = $this->actingAs($admin)
        ->get(route('payroll.exports.bpjs', $period));

    $bpjsResponse->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    expect($bpjsResponse->getContent())->toContain('jht_employee');
    expect($bpjsResponse->getContent())->toContain('Export Tester');

    $monthlyResponse = $this->actingAs($admin)
        ->get(route('payroll.exports.pph21-monthly', $period));

    $monthlyResponse->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    expect($monthlyResponse->getContent())->toContain('pph21_monthly');
    expect($monthlyResponse->getContent())->toContain('Export Tester');

    $yearlyResponse = $this->actingAs($admin)
        ->get(route('payroll.exports.pph21-yearly', 2026));

    $yearlyResponse->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    expect($yearlyResponse->getContent())->toContain('pph21_total');
    expect($yearlyResponse->getContent())->toContain('EMP-500');
});

it('writes audit trail and sends notifications for payroll events', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Administrator');

    $payrollApprover = User::factory()->create();
    $payrollApprover->assignRole('Payroll Approver');

    $financeApprover = User::factory()->create();
    $financeApprover->assignRole('Finance Approver');

    $employeeUser = User::factory()->create();
    $employeeUser->assignRole('Employee');

    $organization = Organization::query()->where('code', 'HQ')->firstOrFail();
    $payrollGroup = PayrollGroup::query()->where('code', 'MONTHLY-HQ')->firstOrFail();
    $taxStatus = TaxStatus::query()->where('code', 'TK0')->firstOrFail();

    $employee = Employee::query()->create([
        'user_id' => $employeeUser->id,
        'organization_id' => $organization->id,
        'employee_number' => 'EMP-600',
        'full_name' => 'Audit Notification Tester',
        'employment_status' => 'active',
        'hire_date' => '2026-09-01',
        'email' => $employeeUser->email,
    ]);

    EmployeePayrollProfile::query()->create([
        'employee_id' => $employee->id,
        'employee_code' => $employee->employee_number,
        'tax_status_id' => $taxStatus->id,
        'payroll_group_id' => $payrollGroup->id,
        'basic_salary' => 7000000,
        'payment_type' => 'monthly',
        'join_date' => '2026-09-01',
        'is_taxable' => true,
    ]);

    $period = PayrollPeriod::query()->create([
        'payroll_group_id' => $payrollGroup->id,
        'period_name' => 'September 2026',
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-30',
        'pay_date' => '2026-09-25',
        'status' => 'draft',
    ]);

    $this->actingAs($admin)->post(route('payroll.periods.process', $period));
    $run = PayrollRun::query()->where('payroll_period_id', $period->id)->firstOrFail();

    expect(AuditLog::query()->where('event', 'period_processed')->exists())->toBeTrue();
    expect($payrollApprover->fresh()->notifications()->count())->toBe(1);
    expect($payrollApprover->fresh()->notifications()->first()->data['metadata']['type'])->toBe('payroll_period_processed');

    $this->actingAs($payrollApprover)->post(route('payroll.runs.approve', $run));

    expect(AuditLog::query()->where('event', 'approval_action')->count())->toBeGreaterThanOrEqual(1);
    expect($financeApprover->fresh()->notifications()->count())->toBe(1);
    expect($financeApprover->fresh()->notifications()->first()->data['metadata']['type'])->toBe('payroll_approval_pending');

    $this->actingAs($financeApprover)->post(route('payroll.runs.approve', $run));
    $this->actingAs($financeApprover)->post(route('payroll.runs.mark-paid', $run));

    expect(AuditLog::query()->where('event', 'marked_paid')->exists())->toBeTrue();
    expect($employeeUser->fresh()->notifications()->count())->toBe(1);
    expect($employeeUser->fresh()->notifications()->first()->data['metadata']['type'])->toBe('payslip_published');
});

it('shows the workflow center for payroll users', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Administrator');

    $response = $this->actingAs($admin)->get(route('workflows.index'));

    $response
        ->assertOk()
        ->assertSeeText('Antrian persetujuan dan kontrol revisi')
        ->assertSeeText('Antrian persetujuan');
});
