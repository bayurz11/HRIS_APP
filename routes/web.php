<?php

use App\Http\Controllers\LocaleController;

Route::view('/', 'welcome')->name('home');
Route::post('/locale', LocaleController::class)->name('locale.update');

require __DIR__.'/modules/dashboard.php';
require __DIR__.'/modules/organization.php';
require __DIR__.'/modules/users.php';
require __DIR__.'/modules/payroll.php';
require __DIR__.'/modules/workflows.php';
require __DIR__.'/modules/documents.php';
require __DIR__.'/modules/reports.php';
require __DIR__.'/modules/notifications.php';
require __DIR__.'/modules/audit-trail.php';
require __DIR__.'/settings.php';
