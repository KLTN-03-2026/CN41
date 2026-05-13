<?php

use Illuminate\Support\Facades\Route;
use Modules\Lessons\Http\Controllers\LessonController;
use Modules\Lessons\Http\Controllers\SectionController;

// ── Admin routes ──────────────────────────────────────────
Route::middleware(['auth:admin'])->prefix('admin')->group(function () {

    // ── Sections ──
    Route::get('sections/trashed', [SectionController::class, 'trashed'])->middleware('permission:lessons.view');
    Route::post('sections/reorder', [SectionController::class, 'reorder'])->middleware('permission:lessons.edit');

    Route::post('sections/bulk-action', [SectionController::class, 'bulkAction'])->middleware('permission:lessons.edit');
    Route::delete('sections/bulk-delete', [SectionController::class, 'bulkDelete'])->middleware('permission:lessons.delete');
    Route::post('sections/bulk-restore', [SectionController::class, 'bulkRestore'])->middleware('permission:lessons.delete');
    Route::delete('sections/bulk-force-delete', [SectionController::class, 'bulkForceDelete'])->middleware('permission:lessons.delete');

    Route::get('courses/{course_id}/sections', [SectionController::class, 'index'])->middleware('permission:lessons.view');
    Route::post('courses/{course_id}/sections', [SectionController::class, 'store'])->middleware('permission:lessons.create');

    Route::get('sections/{id}', [SectionController::class, 'show'])->middleware('permission:lessons.view');
    Route::patch('sections/{id}', [SectionController::class, 'update'])->middleware('permission:lessons.edit');
    Route::delete('sections/{id}', [SectionController::class, 'destroy'])->middleware('permission:lessons.delete');
    Route::patch('sections/{id}/toggle-status', [SectionController::class, 'toggleStatus'])->middleware('permission:lessons.edit');
    Route::post('sections/{id}/restore', [SectionController::class, 'restore'])->middleware('permission:lessons.delete');
    Route::delete('sections/{id}/force-delete', [SectionController::class, 'forceDelete'])->middleware('permission:lessons.delete');

    // ── Lessons ──
    Route::get('lessons/trashed', [LessonController::class, 'trashed'])->middleware('permission:lessons.view');
    Route::post('lessons/reorder', [LessonController::class, 'reorder'])->middleware('permission:lessons.edit');

    Route::post('lessons/bulk-action', [LessonController::class, 'bulkAction'])->middleware('permission:lessons.edit');
    Route::delete('lessons/bulk-delete', [LessonController::class, 'bulkDelete'])->middleware('permission:lessons.delete');
    Route::post('lessons/bulk-restore', [LessonController::class, 'bulkRestore'])->middleware('permission:lessons.delete');
    Route::delete('lessons/bulk-force-delete', [LessonController::class, 'bulkForceDelete'])->middleware('permission:lessons.delete');

    // Nested: lessons thuộc course (section_id optional trong body)
    Route::get('courses/{course_id}/lessons', [LessonController::class, 'index'])->middleware('permission:lessons.view');
    Route::post('courses/{course_id}/lessons', [LessonController::class, 'store'])->middleware('permission:lessons.create');

    Route::get('lessons/{id}', [LessonController::class, 'show'])->middleware('permission:lessons.view');
    Route::patch('lessons/{id}', [LessonController::class, 'update'])->middleware('permission:lessons.edit');
    Route::delete('lessons/{id}', [LessonController::class, 'destroy'])->middleware('permission:lessons.delete');
    Route::patch('lessons/{id}/toggle-status', [LessonController::class, 'toggleStatus'])->middleware('permission:lessons.edit');
    Route::post('lessons/{id}/restore', [LessonController::class, 'restore'])->middleware('permission:lessons.delete');
    Route::delete('lessons/{id}/force-delete', [LessonController::class, 'forceDelete'])->middleware('permission:lessons.delete');
});

// ── Client routes (auth:api + email.verified — học viên đã đăng nhập và đã kích hoạt) ──
Route::middleware(['auth:api', 'email.verified'])->group(function () {
    Route::get('my-courses/{slug}/lessons', [LessonController::class, 'myLessons']);
    Route::get('my-courses/{slug}/lessons/{lesson_slug}', [LessonController::class, 'myLessonDetail']);
    Route::post('lessons/{id}/progress', [LessonController::class, 'updateProgress']);
    Route::get('courses/{slug}/progress', [LessonController::class, 'courseProgress']);
});

// ── Public routes (không cần auth) ────────────────────────
Route::get('courses/{slug}/curriculum', [SectionController::class, 'curriculum']);
