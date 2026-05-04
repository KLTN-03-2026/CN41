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
    Route::get('coupons/trashed', [CouponsController::class, 'trashed'])->middleware('permission:coupons.view');

    // Bulk routes
    Route::post('coupons/bulk-restore', [CouponsController::class, 'bulkRestore'])->middleware('permission:coupons.delete');
    Route::delete('coupons/bulk-delete', [CouponsController::class, 'bulkDelete'])->middleware('permission:coupons.delete');

    // Standard CRUD - từng route riêng để phân quyền chính xác
    Route::get('coupons', [CouponsController::class, 'index'])->middleware('permission:coupons.view');
    Route::post('coupons', [CouponsController::class, 'store'])->middleware('permission:coupons.create');
    Route::get('coupons/{coupon}', [CouponsController::class, 'show'])->middleware('permission:coupons.view');
    Route::put('coupons/{coupon}', [CouponsController::class, 'update'])->middleware('permission:coupons.edit');
    Route::patch('coupons/{coupon}', [CouponsController::class, 'update'])->middleware('permission:coupons.edit');
    Route::delete('coupons/{coupon}', [CouponsController::class, 'destroy'])->middleware('permission:coupons.delete');

    // Per-item actions (đặt SAU apiResource)
    Route::patch('coupons/{id}/toggle-status', [CouponsController::class, 'toggleStatus'])->middleware('permission:coupons.edit');
    Route::post('coupons/{id}/restore', [CouponsController::class, 'restore'])->middleware('permission:coupons.delete');
    Route::delete('coupons/{id}/force-delete', [CouponsController::class, 'forceDelete'])->middleware('permission:coupons.delete');
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
