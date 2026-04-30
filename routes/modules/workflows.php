<?php

use App\Http\Controllers\WorkflowController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'payroll.access'])->group(function (): void {
    Route::get('workflows', [WorkflowController::class, 'index'])->name('workflows.index');
});
