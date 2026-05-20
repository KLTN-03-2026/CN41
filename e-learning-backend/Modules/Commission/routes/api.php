<?php

use Illuminate\Support\Facades\Route;
use Modules\Commission\Http\Controllers\Admin\CommissionSettingsController;
use Modules\Commission\Http\Controllers\Admin\PayoutController;
use Modules\Commission\Http\Controllers\Admin\TeacherEarningsController;
use Modules\Commission\Http\Controllers\Teacher\EarningsController;

Route::middleware(['auth:admin'])->prefix('admin')->group(function () {
    Route::get('commission-settings', [CommissionSettingsController::class, 'show']);
    Route::patch('commission-settings', [CommissionSettingsController::class, 'update']);

    // Static routes before parameterized
    Route::get('payouts', [PayoutController::class, 'index']);
    Route::patch('payouts/{id}/approve', [PayoutController::class, 'approve']);
    Route::patch('payouts/{id}/reject', [PayoutController::class, 'reject']);
    Route::patch('payouts/{id}/mark-paid', [PayoutController::class, 'markPaid']);

    Route::get('teacher-earnings', [TeacherEarningsController::class, 'index']);
});

Route::middleware(['auth:admin', 'role:teacher'])->prefix('teacher')->group(function () {
    Route::get('earnings', [EarningsController::class, 'index']);
    Route::get('payouts', [EarningsController::class, 'myPayouts']);
    Route::post('payouts', [EarningsController::class, 'requestPayout']);
});
