<?php

use Illuminate\Support\Facades\Route;
use Modules\Categories\Http\Controllers\CategoriesController;

Route::middleware(['auth:admin'])->prefix('admin')->group(function () {
    // Nested set routes — BEFORE parameterized routes to avoid {category} matching
    Route::get('categories/tree', [CategoriesController::class, 'tree'])->middleware('permission:course_categories.view|courses.view');
    Route::get('categories/flat-tree', [CategoriesController::class, 'flatTree'])->middleware('permission:course_categories.view|courses.view');
    Route::get('categories/trashed', [CategoriesController::class, 'trashed'])->middleware('permission:course_categories.view');

    // Bulk routes
    Route::post('categories/bulk-restore', [CategoriesController::class, 'bulkRestore'])->middleware('permission:course_categories.delete');
    Route::delete('categories/bulk-delete', [CategoriesController::class, 'bulkDelete'])->middleware('permission:course_categories.delete');
    Route::delete('categories/bulk-force-delete', [CategoriesController::class, 'bulkForceDelete'])->middleware('permission:course_categories.delete');

    // Standard CRUD
    Route::get('categories', [CategoriesController::class, 'index'])->middleware('permission:course_categories.view');
    Route::post('categories', [CategoriesController::class, 'store'])->middleware('permission:course_categories.create');
    Route::get('categories/{category}', [CategoriesController::class, 'show'])->middleware('permission:course_categories.view');
    Route::patch('categories/{category}', [CategoriesController::class, 'update'])->middleware('permission:course_categories.edit');
    Route::delete('categories/{category}', [CategoriesController::class, 'destroy'])->middleware('permission:course_categories.delete');

    // Per-item actions
    Route::post('categories/{id}/move', [CategoriesController::class, 'move'])->middleware('permission:course_categories.edit');
    Route::get('categories/{id}/ancestors', [CategoriesController::class, 'ancestors'])->middleware('permission:course_categories.view');
    Route::get('categories/{id}/descendants', [CategoriesController::class, 'descendants'])->middleware('permission:course_categories.view');
    Route::patch('categories/{id}/toggle-status', [CategoriesController::class, 'toggleStatus'])->middleware('permission:course_categories.edit');
    Route::post('categories/{id}/restore', [CategoriesController::class, 'restore'])->middleware('permission:course_categories.delete');
    Route::delete('categories/{id}/force-delete', [CategoriesController::class, 'forceDelete'])->middleware('permission:course_categories.delete');
});

Route::group([], function () {
    Route::get('categories', [CategoriesController::class, 'publicIndex']);
    Route::get('categories/tree', [CategoriesController::class, 'publicTree']);
    Route::get('categories/{slug}', [CategoriesController::class, 'publicShow']);
});
