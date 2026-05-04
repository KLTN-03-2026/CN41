<?php

use Illuminate\Support\Facades\Route;
use Modules\Teachers\Http\Controllers\TeachersController;

/*
|--------------------------------------------------------------------------
| Admin Routes (auth:admin middleware)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:admin'])->prefix('admin')->group(function () {
    // Extra routes (đặt TRƯỚC apiResource để tránh bị match bởi {teacher})
    Route::get('teachers/trashed', [TeachersController::class, 'trashed'])->middleware('permission:users.view');

    // Bulk routes
    Route::post('teachers/bulk-restore', [TeachersController::class, 'bulkRestore'])->middleware('permission:users.edit');
    Route::delete('teachers/bulk-delete', [TeachersController::class, 'bulkDelete'])->middleware('permission:users.delete');
    Route::delete('teachers/bulk-force-delete', [TeachersController::class, 'bulkForceDelete'])->middleware('permission:users.delete');

    // Standard CRUD - từng route riêng để phân quyền chính xác
    Route::get('teachers', [TeachersController::class, 'index'])->middleware('permission:users.view');
    Route::post('teachers', [TeachersController::class, 'store'])->middleware('permission:users.create');
    Route::get('teachers/{teacher}', [TeachersController::class, 'show'])->middleware('permission:users.view');
    Route::put('teachers/{teacher}', [TeachersController::class, 'update'])->middleware('permission:users.edit');
    Route::patch('teachers/{teacher}', [TeachersController::class, 'update'])->middleware('permission:users.edit');
    Route::delete('teachers/{teacher}', [TeachersController::class, 'destroy'])->middleware('permission:users.delete');

    // Per-item actions (đặt SAU apiResource)
    Route::patch('teachers/{id}/toggle-status', [TeachersController::class, 'toggleStatus'])->middleware('permission:users.edit');
    Route::post('teachers/{id}/restore', [TeachersController::class, 'restore'])->middleware('permission:users.edit');
    Route::delete('teachers/{id}/force-delete', [TeachersController::class, 'forceDelete'])->middleware('permission:users.delete');
});

/*
|--------------------------------------------------------------------------
| Public Routes (không cần auth)
|--------------------------------------------------------------------------
*/
Route::group([], function () {
    Route::get('teachers', [TeachersController::class, 'publicIndex']);
    Route::get('teachers/{slug}', [TeachersController::class, 'publicShow']);
});
