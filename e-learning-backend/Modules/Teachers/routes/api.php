<?php

use Illuminate\Support\Facades\Route;
use Modules\Teachers\Http\Controllers\TeachersController;

Route::middleware(['auth:admin'])->prefix('admin')->group(function () {
    // Static/bulk routes BEFORE parameterized routes
    Route::get('teachers/trashed', [TeachersController::class, 'trashed'])->middleware('permission:teachers.view');

    Route::patch('teachers/bulk-restore', [TeachersController::class, 'bulkRestore'])->middleware('permission:teachers.edit');
    Route::delete('teachers/bulk-delete', [TeachersController::class, 'bulkDelete'])->middleware('permission:teachers.delete');
    Route::delete('teachers/bulk-force-delete', [TeachersController::class, 'bulkForceDelete'])->middleware('permission:teachers.delete');

    // courses.view also grants read access so course managers can pick a teacher when creating a course
    Route::get('teachers', [TeachersController::class, 'index'])->middleware('permission:teachers.view|courses.view');
    Route::post('teachers', [TeachersController::class, 'store'])->middleware('permission:teachers.create');
    Route::get('teachers/{teacher}', [TeachersController::class, 'show'])->middleware('permission:teachers.view|courses.view');
    Route::patch('teachers/{teacher}', [TeachersController::class, 'update'])->middleware('permission:teachers.edit');
    Route::delete('teachers/{teacher}', [TeachersController::class, 'destroy'])->middleware('permission:teachers.delete');

    Route::patch('teachers/{id}/toggle-status', [TeachersController::class, 'toggleStatus'])->middleware('permission:teachers.edit');
    Route::patch('teachers/{id}/restore', [TeachersController::class, 'restore'])->middleware('permission:teachers.edit');
    Route::delete('teachers/{id}/force-delete', [TeachersController::class, 'forceDelete'])->middleware('permission:teachers.delete');
});

Route::group([], function () {
    Route::get('teachers', [TeachersController::class, 'publicIndex']);
    Route::get('teachers/{slug}', [TeachersController::class, 'publicShow']);
});
