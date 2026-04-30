<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin'])->group(function (): void {
    Route::get('reports', function () {
        return view('modules.shared.placeholder', [
            'module' => config('haris.module_pages.reports'),
        ]);
    })->name('reports.index');
});
