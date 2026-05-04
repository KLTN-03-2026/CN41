<?php

use Illuminate\Support\Facades\Route;
use Modules\Categories\Http\Controllers\CategoriesController;

/*
|--------------------------------------------------------------------------
| Admin Routes (auth:admin middleware)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:admin'])->prefix('admin')->group(function () {
    // Nested set routes (đặt TRƯỚC apiResource để tránh bị match bởi {category})
    Route::get('categories/tree', [CategoriesController::class, 'tree'])->middleware('permission:categories.view|courses.view');
    Route::get('categories/flat-tree', [CategoriesController::class, 'flatTree'])->middleware('permission:categories.view|courses.view');
    Route::get('categories/trashed', [CategoriesController::class, 'trashed'])->middleware('permission:categories.view');

    // Bulk routes
    Route::post('categories/bulk-restore', [CategoriesController::class, 'bulkRestore'])->middleware('permission:categories.delete');
    Route::delete('categories/bulk-delete', [CategoriesController::class, 'bulkDelete'])->middleware('permission:categories.delete');
    Route::delete('categories/bulk-force-delete', [CategoriesController::class, 'bulkForceDelete'])->middleware('permission:categories.delete');

    // Standard CRUD - từng route riêng để phân quyền chính xác
    Route::get('categories', [CategoriesController::class, 'index'])->middleware('permission:categories.view');
    Route::post('categories', [CategoriesController::class, 'store'])->middleware('permission:categories.create');
    Route::get('categories/{category}', [CategoriesController::class, 'show'])->middleware('permission:categories.view');
    Route::put('categories/{category}', [CategoriesController::class, 'update'])->middleware('permission:categories.edit');
    Route::patch('categories/{category}', [CategoriesController::class, 'update'])->middleware('permission:categories.edit');
    Route::delete('categories/{category}', [CategoriesController::class, 'destroy'])->middleware('permission:categories.delete');

    // Per-item actions (đặt SAU apiResource)
    Route::post('categories/{id}/move', [CategoriesController::class, 'move'])->middleware('permission:categories.edit');
    Route::get('categories/{id}/ancestors', [CategoriesController::class, 'ancestors'])->middleware('permission:categories.view');
    Route::get('categories/{id}/descendants', [CategoriesController::class, 'descendants'])->middleware('permission:categories.view');
    Route::patch('categories/{id}/toggle-status', [CategoriesController::class, 'toggleStatus'])->middleware('permission:categories.edit');
    Route::post('categories/{id}/restore', [CategoriesController::class, 'restore'])->middleware('permission:categories.delete');
    Route::delete('categories/{id}/force-delete', [CategoriesController::class, 'forceDelete'])->middleware('permission:categories.delete');
});

/*
|--------------------------------------------------------------------------
| Public Routes (không cần auth)
|--------------------------------------------------------------------------
*/
Route::group([], function () {
    Route::get('categories', [CategoriesController::class, 'publicIndex']);
    Route::get('categories/tree', [CategoriesController::class, 'publicTree']);
    Route::get('categories/{slug}', [CategoriesController::class, 'publicShow']);
});
