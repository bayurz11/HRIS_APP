<?php

use App\Http\Controllers\Api\V1\Payroll\PayrollPeriodController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->name('api.v1.')
    ->middleware(['auth', 'admin'])
    ->group(function (): void {
        Route::apiResource('payroll-periods', PayrollPeriodController::class)
            ->only(['index', 'show']);
    });
