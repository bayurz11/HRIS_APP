<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_payroll_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payroll_number')->unique();
            $table->decimal('basic_salary_snapshot', 15, 2)->default(0);
            $table->decimal('gross_salary', 15, 2)->default(0);
            $table->decimal('total_allowance', 15, 2)->default(0);
            $table->decimal('total_deduction', 15, 2)->default(0);
            $table->decimal('total_bpjs_company', 15, 2)->default(0);
            $table->decimal('total_bpjs_employee', 15, 2)->default(0);
            $table->decimal('total_pph21', 15, 2)->default(0);
            $table->decimal('total_overtime', 15, 2)->default(0);
            $table->decimal('total_loan_deduction', 15, 2)->default(0);
            $table->decimal('total_absence_deduction', 15, 2)->default(0);
            $table->decimal('net_salary', 15, 2)->default(0);
            $table->decimal('rounding_amount', 15, 2)->default(0);
            $table->decimal('take_home_pay', 15, 2)->default(0);
            $table->string('calculation_status')->default('draft');
            $table->timestamp('calculated_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payroll_run_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
            $table->string('component_code');
            $table->string('component_name');
            $table->string('component_type');
            $table->string('source_type')->default('manual');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->boolean('is_taxable')->default(false);
            $table->boolean('is_bpjs_applicable')->default(false);
            $table->decimal('quantity', 15, 2)->default(1);
            $table->decimal('rate', 15, 2)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('payroll_bpjs_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
            $table->string('bpjs_type');
            $table->decimal('salary_base', 15, 2)->default(0);
            $table->decimal('employee_rate', 8, 4)->default(0);
            $table->decimal('employer_rate', 8, 4)->default(0);
            $table->decimal('employee_amount', 15, 2)->default(0);
            $table->decimal('employer_amount', 15, 2)->default(0);
            $table->json('rule_snapshot_json')->nullable();
            $table->timestamps();
        });

        Schema::create('payroll_tax_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_status_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('taxable_income_monthly', 15, 2)->default(0);
            $table->decimal('taxable_income_yearly_projection', 15, 2)->default(0);
            $table->decimal('job_expense_amount', 15, 2)->default(0);
            $table->decimal('pension_cost_amount', 15, 2)->default(0);
            $table->decimal('net_income_yearly', 15, 2)->default(0);
            $table->decimal('ptkp_amount_yearly', 15, 2)->default(0);
            $table->decimal('pkp_amount_yearly', 15, 2)->default(0);
            $table->decimal('yearly_tax_amount', 15, 2)->default(0);
            $table->decimal('monthly_tax_amount', 15, 2)->default(0);
            $table->json('method_snapshot_json')->nullable();
            $table->timestamps();
        });

        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
            $table->string('payslip_number')->unique();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
            $table->date('issue_date')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_disk')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('email_sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('payroll_tax_results');
        Schema::dropIfExists('payroll_bpjs_results');
        Schema::dropIfExists('payroll_run_items');
        Schema::dropIfExists('payroll_runs');
    }
};
