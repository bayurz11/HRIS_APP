<?php

use App\Http\Controllers\OrganizationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin'])->group(function (): void {
    Route::resource('organization', OrganizationController::class)
        ->except('show')
        ->parameters(['organization' => 'organization']);
});
