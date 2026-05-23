<?php

use Illuminate\Support\Facades\Route;
use Modules\Notifications\Http\Controllers\NotificationsController;

// Admin notification routes
Route::middleware(['auth:admin'])->prefix('api/v1/admin')->group(function () {
    Route::get('notifications', [NotificationsController::class, 'adminIndex']);
    Route::patch('notifications/mark-all-read', [NotificationsController::class, 'adminMarkAllRead']);
    Route::patch('notifications/{id}/read', [NotificationsController::class, 'adminMarkRead']);
});

// Teacher notification routes
Route::middleware(['auth:admin'])->prefix('api/v1/teacher')->group(function () {
    Route::get('notifications', [NotificationsController::class, 'teacherIndex']);
    Route::patch('notifications/mark-all-read', [NotificationsController::class, 'teacherMarkAllRead']);
    Route::patch('notifications/{id}/read', [NotificationsController::class, 'teacherMarkRead']);
});
