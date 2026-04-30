<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Organization\Models\Organization;
use Modules\Payroll\Models\BpjsRule;
use Modules\Payroll\Models\PayrollComponent;
use Modules\Payroll\Models\PayrollGroup;
use Modules\Payroll\Models\TaxStatus;

class CoreMasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organization = Organization::query()->updateOrCreate(
            ['code' => 'HQ'],
            [
                'name' => 'Head Office',
                'type' => 'Headquarter',
                'is_active' => true,
            ],
        );

        PayrollGroup::query()->updateOrCreate(
            ['code' => 'MONTHLY-HQ'],
            [
                'name' => 'Monthly Head Office',
                'organization_id' => $organization->id,
                'pay_frequency' => 'monthly',
                'payroll_day' => 25,
            ],
        );

        foreach ([
            ['code' => 'TK0', 'name' => 'Tidak Kawin / 0 Tanggungan', 'ptkp_amount_yearly' => 54000000, 'ter_category' => 'A'],
            ['code' => 'TK1', 'name' => 'Tidak Kawin / 1 Tanggungan', 'ptkp_amount_yearly' => 58500000, 'ter_category' => 'A'],
            ['code' => 'TK2', 'name' => 'Tidak Kawin / 2 Tanggungan', 'ptkp_amount_yearly' => 63000000, 'ter_category' => 'B'],
            ['code' => 'TK3', 'name' => 'Tidak Kawin / 3 Tanggungan', 'ptkp_amount_yearly' => 67500000, 'ter_category' => 'B'],
            ['code' => 'K0', 'name' => 'Kawin / 0 Tanggungan', 'ptkp_amount_yearly' => 58500000, 'ter_category' => 'A'],
            ['code' => 'K1', 'name' => 'Kawin / 1 Tanggungan', 'ptkp_amount_yearly' => 63000000, 'ter_category' => 'B'],
            ['code' => 'K2', 'name' => 'Kawin / 2 Tanggungan', 'ptkp_amount_yearly' => 67500000, 'ter_category' => 'B'],
            ['code' => 'K3', 'name' => 'Kawin / 3 Tanggungan', 'ptkp_amount_yearly' => 72000000, 'ter_category' => 'C'],
            ['code' => 'KI0', 'name' => 'Penghasilan Istri Digabung / 0 Tanggungan', 'ptkp_amount_yearly' => 112500000, 'ter_category' => 'C'],
            ['code' => 'KI1', 'name' => 'Penghasilan Istri Digabung / 1 Tanggungan', 'ptkp_amount_yearly' => 117000000, 'ter_category' => 'C'],
            ['code' => 'KI2', 'name' => 'Penghasilan Istri Digabung / 2 Tanggungan', 'ptkp_amount_yearly' => 121500000, 'ter_category' => 'C'],
            ['code' => 'KI3', 'name' => 'Penghasilan Istri Digabung / 3 Tanggungan', 'ptkp_amount_yearly' => 126000000, 'ter_category' => 'C'],
        ] as $status) {
            TaxStatus::query()->updateOrCreate(
                ['code' => $status['code']],
                [
                    ...$status,
                    'is_active' => true,
                ],
            );
        }

        foreach (config('payroll.bpjs.rule_defaults', []) as $rule) {
            BpjsRule::query()->updateOrCreate(
                [
                    'bpjs_type' => $rule['bpjs_type'],
                    'company_risk_level' => $rule['company_risk_level'],
                    'effective_start_date' => '2026-01-01',
                ],
                [
                    'rule_name' => $rule['rule_name'],
                    'participant_portion_type' => $rule['participant_portion_type'],
                    'employee_rate' => $rule['employee_rate'],
                    'employer_rate' => $rule['employer_rate'],
                    'max_salary_base' => $rule['max_salary_base'],
                    'min_salary_base' => $rule['min_salary_base'] ?? null,
                    'company_risk_level' => $rule['company_risk_level'],
                    'effective_start_date' => '2026-01-01',
                    'is_active' => true,
                ],
            );
        }

        foreach ([
            ['code' => 'BASIC', 'name' => 'Basic Salary', 'category' => 'earning', 'calculation_method' => 'fixed', 'default_taxable' => true],
            ['code' => 'ALLOW_TRANSPORT', 'name' => 'Transport Allowance', 'category' => 'earning', 'calculation_method' => 'fixed', 'default_taxable' => true],
            ['code' => 'OVERTIME', 'name' => 'Overtime', 'category' => 'earning', 'calculation_method' => 'manual', 'default_taxable' => true],
            ['code' => 'BONUS', 'name' => 'Bonus', 'category' => 'earning', 'calculation_method' => 'manual', 'default_taxable' => true],
            ['code' => 'REIMB_MEDICAL', 'name' => 'Medical Reimbursement', 'category' => 'reimbursement', 'calculation_method' => 'manual', 'default_taxable' => false],
            ['code' => 'REIMB_OTHER', 'name' => 'Other Reimbursement', 'category' => 'reimbursement', 'calculation_method' => 'manual', 'default_taxable' => false],
            ['code' => 'ABSENCE_DED', 'name' => 'Absence Deduction', 'category' => 'deduction', 'calculation_method' => 'manual'],
            ['code' => 'LOAN_DED', 'name' => 'Loan Deduction', 'category' => 'deduction', 'calculation_method' => 'manual'],
            ['code' => 'ADJUST_PLUS', 'name' => 'Positive Adjustment', 'category' => 'earning', 'calculation_method' => 'manual', 'default_taxable' => true],
            ['code' => 'ADJUST_MINUS', 'name' => 'Negative Adjustment', 'category' => 'deduction', 'calculation_method' => 'manual'],
            ['code' => 'BPJS_EMP', 'name' => 'BPJS Employee Portion', 'category' => 'deduction', 'calculation_method' => 'percentage'],
            ['code' => 'PPH21', 'name' => 'PPh 21', 'category' => 'tax', 'calculation_method' => 'manual'],
        ] as $component) {
            PayrollComponent::query()->updateOrCreate(
                ['code' => $component['code']],
                [
                    'name' => $component['name'],
                    'category' => $component['category'],
                    'calculation_method' => $component['calculation_method'],
                    'default_taxable' => $component['default_taxable'] ?? false,
                    'default_bpjs_applicable' => $component['default_bpjs_applicable'] ?? false,
                    'display_on_payslip' => true,
                    'affects_take_home_pay' => $component['category'] !== 'employer_cost',
                    'is_active' => true,
                ],
            );
        }
    }
}
