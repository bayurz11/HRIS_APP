<?php

use App\Http\Controllers\EmployeeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin'])->group(function (): void {
    Route::resource('users', EmployeeController::class)
        ->except('show')
        ->parameters(['users' => 'user']);
});
