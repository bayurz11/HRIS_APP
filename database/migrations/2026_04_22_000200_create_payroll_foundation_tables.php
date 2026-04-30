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
        Schema::create('payroll_groups', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('pay_frequency')->default('monthly');
            $table->unsignedTinyInteger('payroll_day')->nullable();
            $table->string('overtime_policy_id')->nullable();
            $table->string('attendance_policy_id')->nullable();
            $table->string('leave_policy_id')->nullable();
            $table->timestamps();
        });

        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_group_id')->constrained()->cascadeOnDelete();
            $table->string('period_name');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('pay_date')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('payroll_components', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('category');
            $table->string('calculation_method')->default('manual');
            $table->boolean('default_taxable')->default(false);
            $table->boolean('default_bpjs_applicable')->default(false);
            $table->boolean('display_on_payslip')->default(true);
            $table->boolean('affects_take_home_pay')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tax_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->decimal('ptkp_amount_yearly', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->date('effective_start_date')->nullable();
            $table->date('effective_end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('bpjs_rules', function (Blueprint $table) {
            $table->id();
            $table->string('rule_name');
            $table->string('bpjs_type');
            $table->string('participant_portion_type')->default('both');
            $table->decimal('employee_rate', 8, 4)->default(0);
            $table->decimal('employer_rate', 8, 4)->default(0);
            $table->decimal('max_salary_base', 15, 2)->nullable();
            $table->decimal('min_salary_base', 15, 2)->nullable();
            $table->string('company_risk_level')->nullable();
            $table->date('effective_start_date')->nullable();
            $table->date('effective_end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bpjs_rules');
        Schema::dropIfExists('tax_statuses');
        Schema::dropIfExists('payroll_components');
        Schema::dropIfExists('payroll_periods');
        Schema::dropIfExists('payroll_groups');
    }
};
