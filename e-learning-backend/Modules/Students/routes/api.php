<?php

use Illuminate\Support\Facades\Route;
use Modules\Students\Http\Controllers\StudentProfileController;
use Modules\Students\Http\Controllers\StudentsController;

// ─── STUDENT PROFILE (guard: api) ───────────────────────────
Route::middleware(['auth:api', 'email.verified'])->prefix('profile')->group(function () {
    Route::get('/', [StudentProfileController::class, 'show']);
    Route::patch('/', [StudentProfileController::class, 'update']);
    Route::post('/avatar', [StudentProfileController::class, 'uploadAvatar']);
    Route::post('/change-password', [StudentProfileController::class, 'changePassword']);
});

// ─── ADMIN STUDENTS (guard: admin) ──────────────────────────
Route::middleware(['auth:admin'])->prefix('admin')->group(function () {
    // Bulk + static routes phải đặt TRƯỚC apiResource để tránh bị match bởi {student}
    Route::get('students/trashed', [StudentsController::class, 'trashed'])->middleware('permission:students.view');
    Route::post('students/bulk-restore', [StudentsController::class, 'bulkRestore'])->middleware('permission:students.edit');
    Route::delete('students/bulk-delete', [StudentsController::class, 'bulkDelete'])->middleware('permission:students.edit');
    Route::delete('students/bulk-force-delete', [StudentsController::class, 'bulkForceDelete'])->middleware('permission:students.edit');

    // Standard CRUD - từng route riêng để phân quyền chính xác
    Route::get('students', [StudentsController::class, 'index'])->middleware('permission:students.view');
    Route::post('students', [StudentsController::class, 'store'])->middleware('permission:students.edit');
    Route::get('students/{student}', [StudentsController::class, 'show'])->middleware('permission:students.view');
    Route::put('students/{student}', [StudentsController::class, 'update'])->middleware('permission:students.edit');
    Route::patch('students/{student}', [StudentsController::class, 'update'])->middleware('permission:students.edit');
    Route::delete('students/{student}', [StudentsController::class, 'destroy'])->middleware('permission:students.edit');

    Route::post('students/{id}/restore', [StudentsController::class, 'restore'])->middleware('permission:students.edit');
    Route::delete('students/{id}/force-delete', [StudentsController::class, 'forceDelete'])->middleware('permission:students.edit');
});
