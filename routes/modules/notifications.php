<?php

use App\Http\Controllers\UserNotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('notifications', [UserNotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/mark-all-read', [UserNotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
});
