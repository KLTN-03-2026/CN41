<?php

use Illuminate\Support\Facades\Route;
use Modules\Coupons\Http\Controllers\CouponsController;

/*
|--------------------------------------------------------------------------
| Admin Routes (auth:admin middleware)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:admin'])->prefix('admin')->group(function () {
    // Extra routes (đặt TRƯỚC apiResource để tránh bị match bởi {coupon})
    Route::get('coupons/trashed', [CouponsController::class, 'trashed']);

    // Bulk routes
    Route::post('coupons/bulk-restore', [CouponsController::class, 'bulkRestore']);
    Route::delete('coupons/bulk-delete', [CouponsController::class, 'bulkDelete']);

    // Standard CRUD
    Route::apiResource('coupons', CouponsController::class)->names('admin.coupons');

    // Per-item actions (đặt SAU apiResource)
    Route::patch('coupons/{id}/toggle-status', [CouponsController::class, 'toggleStatus']);
    Route::post('coupons/{id}/restore', [CouponsController::class, 'restore']);
    Route::delete('coupons/{id}/force-delete', [CouponsController::class, 'forceDelete']);
});

/*
|--------------------------------------------------------------------------
| Client Routes
|--------------------------------------------------------------------------
*/

// Public: Xem danh sách mã giảm giá có sẵn (không cần đăng nhập)
Route::get('coupons/available', [CouponsController::class, 'listAvailable']);

// Protected: Validate mã giảm giá (cần đăng nhập + email verified)
Route::middleware(['auth:api', 'email.verified'])->group(function () {
    Route::post('coupons/validate', [CouponsController::class, 'validateCoupon']);
});
