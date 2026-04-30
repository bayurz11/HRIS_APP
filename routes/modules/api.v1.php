<?php

use App\Http\Controllers\Api\V1\AttendanceRecordController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AuditTrailController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\LeaveRequestController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\Payroll\MyPayslipController;
use App\Http\Controllers\Api\V1\Payroll\PayslipController;
use App\Http\Controllers\Api\V1\Payroll\PayrollComponentController;
use App\Http\Controllers\Api\V1\Payroll\PayrollGroupController;
use App\Http\Controllers\Api\V1\Payroll\PayrollPeriodController;
use App\Http\Controllers\Api\V1\Payroll\PayrollPeriodInputController;
use App\Http\Controllers\Api\V1\Payroll\PayrollRunController;
use App\Http\Controllers\Api\V1\Payroll\PayrollWorkflowController;
use App\Http\Controllers\Api\V1\Payroll\TaxStatusController;
use App\Http\Controllers\Api\V1\UserNotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->name('api.v1.')
    ->group(function (): void {
        Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');

        Route::middleware(['auth:sanctum'])->group(function (): void {
            Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');
            Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

            Route::get('dashboard/overview', DashboardController::class)->name('dashboard.overview');

            Route::get('notifications', [UserNotificationController::class, 'index'])->name('notifications.index');
            Route::post('notifications/mark-all-read', [UserNotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');

            Route::middleware('admin')->group(function (): void {
                Route::apiResource('organizations', OrganizationController::class);
                Route::apiResource('employees', EmployeeController::class);
                Route::apiResource('payroll-groups', PayrollGroupController::class);
                Route::apiResource('payroll-periods', PayrollPeriodController::class);
            });

            Route::middleware('payroll.access')->group(function (): void {
                Route::apiResource('attendance-records', AttendanceRecordController::class);
                Route::apiResource('leave-requests', LeaveRequestController::class);
                Route::apiResource('payroll-inputs', PayrollPeriodInputController::class);
                Route::apiResource('payroll-runs', PayrollRunController::class)->only(['index', 'show']);
                Route::apiResource('payslips', PayslipController::class)->only(['index', 'show']);
                Route::get('tax-statuses', [TaxStatusController::class, 'index'])->name('tax-statuses.index');
                Route::get('tax-statuses/{taxStatus}', [TaxStatusController::class, 'show'])->name('tax-statuses.show');
                Route::get('payroll-components', [PayrollComponentController::class, 'index'])->name('payroll-components.index');
                Route::get('payroll-components/{payrollComponent}', [PayrollComponentController::class, 'show'])->name('payroll-components.show');
                Route::get('workflows', [PayrollWorkflowController::class, 'index'])->name('workflows.index');
                Route::post('payroll-periods/{payrollPeriod}/process', [PayrollWorkflowController::class, 'processPeriod'])->name('payroll-periods.process');
                Route::post('payroll-runs/{payrollRun}/approve', [PayrollWorkflowController::class, 'approve'])->name('payroll-runs.approve');
                Route::post('payroll-runs/{payrollRun}/return-to-draft', [PayrollWorkflowController::class, 'returnToDraft'])->name('payroll-runs.return');
                Route::post('payroll-runs/{payrollRun}/mark-paid', [PayrollWorkflowController::class, 'markPaid'])->name('payroll-runs.mark-paid');
                Route::post('payroll-runs/{payrollRun}/publish-payslip', [PayrollWorkflowController::class, 'publishPayslip'])->name('payroll-runs.publish-payslip');
                Route::get('audit-trail', [AuditTrailController::class, 'index'])->name('audit-trail.index');
            });

            Route::middleware('employee.self-service')->group(function (): void {
                Route::get('my/payslips', [MyPayslipController::class, 'index'])->name('my-payslips.index');
                Route::get('my/payslips/{payslip}', [MyPayslipController::class, 'show'])->name('my-payslips.show');
            });
        });
    });
