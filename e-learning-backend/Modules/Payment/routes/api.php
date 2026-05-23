<?php

use Illuminate\Support\Facades\Route;
use Modules\Payment\Http\Controllers\AdminOrderController;
use Modules\Payment\Http\Controllers\OrderController;
use Modules\Payment\Http\Controllers\VnpayController;
use Modules\Payment\Http\Controllers\ZalopayController;

/*
|--------------------------------------------------------------------------
| Admin Routes (auth:admin middleware)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:admin'])->prefix('admin')->group(function () {
    // Extra routes trước để tránh conflict với {id}
    Route::get('orders/export', [AdminOrderController::class, 'export'])->middleware('permission:orders.export');
    Route::get('orders/trashed', [AdminOrderController::class, 'trashed'])->middleware('permission:orders.view');
    Route::delete('orders/bulk-delete', [AdminOrderController::class, 'bulkDelete'])->middleware('permission:orders.edit');
    Route::get('orders/stats/revenue', [AdminOrderController::class, 'revenueStats'])->middleware('permission:orders.view');

    // Danh sách + chi tiết
    Route::get('orders', [AdminOrderController::class, 'index'])->middleware('permission:orders.view');
    Route::get('orders/{id}', [AdminOrderController::class, 'show'])->middleware('permission:orders.view');
    Route::patch('orders/{id}/status', [AdminOrderController::class, 'updateStatus'])->middleware('permission:orders.edit');
    Route::delete('orders/{id}', [AdminOrderController::class, 'destroy'])->middleware('permission:orders.edit');
    Route::patch('orders/{id}/restore', [AdminOrderController::class, 'restore'])->middleware('permission:orders.edit');
});

/*
|--------------------------------------------------------------------------
| Student Routes (auth:api + email.verified)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:api', 'email.verified'])->group(function () {
    // Tạo đơn hàng → nhận URL VNPAY
    Route::post('orders', [OrderController::class, 'store']);

    // Lịch sử đơn hàng của sinh viên
    Route::get('my-orders', [OrderController::class, 'myOrders']);
    Route::get('my-orders/{orderCode}', [OrderController::class, 'show']);

    // Thanh toán lại đơn pending
    Route::post('orders/{orderCode}/retry-payment', [OrderController::class, 'retryPayment']);
});

/*
|--------------------------------------------------------------------------
| VNPAY Callback (public — VNPAY redirect user về đây)
|--------------------------------------------------------------------------
*/
Route::get('payment/vnpay/return', [VnpayController::class, 'return']);

/*
|--------------------------------------------------------------------------
| VNPAY IPN — Webhook (server-to-server, public, không cần auth)
|--------------------------------------------------------------------------
*/
Route::get('payment/vnpay/ipn', [VnpayController::class, 'ipn']);

/*
|--------------------------------------------------------------------------
| ZaloPay Callback (POST) — ZaloPay server-to-server IPN
|--------------------------------------------------------------------------
*/
Route::post('payment/zalopay/callback', [ZalopayController::class, 'callback']);

/*
|--------------------------------------------------------------------------
| ZaloPay Redirect (GET) — ZaloPay redirects user here after payment
|--------------------------------------------------------------------------
*/
Route::get('payment/zalopay/redirect', [ZalopayController::class, 'redirect']);
