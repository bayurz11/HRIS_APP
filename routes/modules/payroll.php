<?php

use App\Http\Controllers\AttendanceRecordController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PayrollExportController;
use App\Http\Controllers\PayrollGroupController;
use App\Http\Controllers\PayrollPeriodInputController;
use App\Http\Controllers\PayrollPeriodManagementController;
use App\Http\Controllers\PayrollRunController;
use App\Http\Controllers\PayrollWorkflowController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\SelfServicePayslipController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin'])->group(function (): void {
    Route::resource('payroll/groups', PayrollGroupController::class)
        ->except('show')
        ->parameters(['groups' => 'group'])
        ->names('payroll.groups');

    Route::resource('payroll/periods', PayrollPeriodManagementController::class)
        ->except(['show', 'index'])
        ->parameters(['periods' => 'period'])
        ->names('payroll.periods');

});

Route::middleware(['auth', 'verified', 'payroll.access'])->group(function (): void {
    Route::get('payroll', PayrollController::class)->name('payroll.index');
    Route::resource('payroll/attendance', AttendanceRecordController::class)
        ->except('show')
        ->parameters(['attendance' => 'attendance'])
        ->names('payroll.attendance');
    Route::resource('payroll/leave', LeaveRequestController::class)
        ->except('show')
        ->parameters(['leave' => 'leave'])
        ->names('payroll.leave');
    Route::get('payroll/exports', [PayrollExportController::class, 'index'])->name('payroll.exports.index');
    Route::get('payroll/exports/periods/{period}/bank-transfer', [PayrollExportController::class, 'bankTransfer'])->name('payroll.exports.bank-transfer');
    Route::get('payroll/exports/periods/{period}/bpjs', [PayrollExportController::class, 'bpjs'])->name('payroll.exports.bpjs');
    Route::get('payroll/exports/periods/{period}/pph21-monthly', [PayrollExportController::class, 'pph21Monthly'])->name('payroll.exports.pph21-monthly');
    Route::get('payroll/exports/years/{year}/pph21-yearly', [PayrollExportController::class, 'pph21Yearly'])->name('payroll.exports.pph21-yearly');
    Route::get('payroll/periods', [PayrollPeriodManagementController::class, 'index'])->name('payroll.periods.index');
    Route::post('payroll/periods/{period}/process', [PayrollWorkflowController::class, 'processPeriod'])->name('payroll.periods.process');
    Route::resource('payroll/inputs', PayrollPeriodInputController::class)
        ->except('show')
        ->parameters(['inputs' => 'input'])
        ->names('payroll.inputs');
    Route::get('payroll/runs', [PayrollRunController::class, 'index'])->name('payroll.runs.index');
    Route::get('payroll/runs/{run}', [PayrollRunController::class, 'show'])->name('payroll.runs.show');
    Route::post('payroll/runs/{run}/approve', [PayrollWorkflowController::class, 'approve'])->name('payroll.runs.approve');
    Route::post('payroll/runs/{run}/return-to-draft', [PayrollWorkflowController::class, 'returnToDraft'])->name('payroll.runs.return');
    Route::post('payroll/runs/{run}/mark-paid', [PayrollWorkflowController::class, 'markPaid'])->name('payroll.runs.mark-paid');
    Route::post('payroll/runs/{run}/publish-payslip', [PayrollWorkflowController::class, 'publishPayslip'])->name('payroll.runs.publish-payslip');

    Route::get('payroll/payslips/{payslip}/download', [PayslipController::class, 'download'])->name('payroll.payslips.download');
});

Route::middleware(['auth', 'verified', 'employee.self-service'])->group(function (): void {
    Route::get('my/payslips', [SelfServicePayslipController::class, 'index'])->name('self-service.payslips.index');
    Route::get('my/payslips/{payslip}/download', [SelfServicePayslipController::class, 'download'])->name('self-service.payslips.download');
});
