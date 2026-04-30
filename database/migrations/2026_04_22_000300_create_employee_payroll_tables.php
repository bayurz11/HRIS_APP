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
        Schema::create('bpjs_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('bpjs_kesehatan_number')->nullable();
            $table->string('bpjs_tk_number')->nullable();
            $table->string('kelas_rawat')->nullable();
            $table->decimal('base_salary_override', 15, 2)->nullable();
            $table->boolean('is_bpjs_kesehatan_enrolled')->default(true);
            $table->boolean('is_jht_enrolled')->default(true);
            $table->boolean('is_jp_enrolled')->default(true);
            $table->boolean('is_jkk_enrolled')->default(true);
            $table->boolean('is_jkm_enrolled')->default(true);
            $table->date('effective_start_date')->nullable();
            $table->date('effective_end_date')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_payroll_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('employee_code')->nullable();
            $table->foreignId('tax_status_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('bpjs_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payroll_group_id')->nullable()->constrained()->nullOnDelete();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('npwp_number')->nullable();
            $table->string('bpjs_kesehatan_number')->nullable();
            $table->string('bpjs_tk_number')->nullable();
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->string('payment_type')->default('monthly');
            $table->date('join_date')->nullable();
            $table->date('resign_date')->nullable();
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_bpjs_kesehatan_enrolled')->default(true);
            $table->boolean('is_bpjs_tk_enrolled')->default(true);
            $table->boolean('is_overtime_eligible')->default(false);
            $table->timestamps();
        });

        Schema::create('employee_payroll_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_component_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2)->nullable();
            $table->decimal('percentage_value', 8, 4)->nullable();
            $table->date('effective_start_date')->nullable();
            $table->date('effective_end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_payroll_components');
        Schema::dropIfExists('employee_payroll_profiles');
        Schema::dropIfExists('bpjs_profiles');
    }
};
