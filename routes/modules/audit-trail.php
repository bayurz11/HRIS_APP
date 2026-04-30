<?php

use App\Http\Controllers\AuditTrailController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'payroll.access'])->group(function (): void {
    Route::get('audit-trail', [AuditTrailController::class, 'index'])->name('audit-trail.index');
});
