<?php

use Illuminate\Support\Facades\Route;
use Modules\Commission\Http\Controllers\Admin\CommissionSettingsController;
use Modules\Commission\Http\Controllers\Admin\PayoutController;
use Modules\Commission\Http\Controllers\Admin\TeacherEarningsController;
use Modules\Commission\Http\Controllers\Teacher\EarningsController;
use Modules\Commission\Http\Controllers\Teacher\TeacherCourseController;
use Modules\Commission\Http\Controllers\Teacher\TeacherLessonController;
use Modules\Commission\Http\Controllers\Teacher\TeacherPortalController;
use Modules\Commission\Http\Controllers\Teacher\TeacherSectionController;

Route::middleware(['auth:admin'])->prefix('admin')->group(function () {
    Route::get('commission-settings', [CommissionSettingsController::class, 'show'])
        ->middleware('permission:commission_settings.view');
    Route::patch('commission-settings', [CommissionSettingsController::class, 'update'])
        ->middleware('permission:commission_settings.update');

    Route::get('payouts/export', [PayoutController::class, 'export'])
        ->middleware('permission:payouts.export');
    Route::get('payouts', [PayoutController::class, 'index'])
        ->middleware('permission:payouts.view');
    Route::patch('payouts/{id}/approve', [PayoutController::class, 'approve'])
        ->middleware('permission:payouts.approve');
    Route::patch('payouts/{id}/reject', [PayoutController::class, 'reject'])
        ->middleware('permission:payouts.approve');
    Route::patch('payouts/{id}/mark-paid', [PayoutController::class, 'markPaid'])
        ->middleware('permission:payouts.approve');

    Route::get('teacher-earnings/export', [TeacherEarningsController::class, 'export'])
        ->middleware('permission:teacher_earnings.export');
    Route::get('teacher-earnings', [TeacherEarningsController::class, 'index'])
        ->middleware('permission:teacher_earnings.view');
});

Route::middleware(['auth:admin', 'role:teacher'])->prefix('teacher')->group(function () {
    Route::get('earnings/export', [EarningsController::class, 'export']);
    Route::get('earnings', [EarningsController::class, 'index']);
    Route::get('payouts', [EarningsController::class, 'myPayouts']);
    Route::post('payouts', [EarningsController::class, 'requestPayout']);

    Route::get('dashboard', [TeacherPortalController::class, 'dashboard']);
    Route::get('courses', [TeacherPortalController::class, 'courses']);
    Route::get('profile', [TeacherPortalController::class, 'profile']);
    Route::patch('profile', [TeacherPortalController::class, 'updateProfile']);

    Route::post('change-password/send-otp', [TeacherPortalController::class, 'sendPasswordOtp']);
    Route::post('change-password/confirm', [TeacherPortalController::class, 'confirmPasswordChange']);
    Route::post('change-email/send-otp', [TeacherPortalController::class, 'sendEmailChangeOtp']);
    Route::post('change-email/confirm', [TeacherPortalController::class, 'confirmEmailChange']);

    Route::post('courses', [TeacherCourseController::class, 'store']);
    Route::get('courses/{id}', [TeacherCourseController::class, 'show']);
    Route::patch('courses/{id}', [TeacherCourseController::class, 'update']);
    Route::delete('courses/{id}', [TeacherCourseController::class, 'destroy']);
    Route::patch('courses/{id}/toggle-status', [TeacherCourseController::class, 'toggleStatus']);

    Route::post('sections/reorder', [TeacherSectionController::class, 'reorder']);
    Route::get('courses/{course_id}/sections', [TeacherSectionController::class, 'index']);
    Route::post('courses/{course_id}/sections', [TeacherSectionController::class, 'store']);
    Route::patch('sections/{id}', [TeacherSectionController::class, 'update']);
    Route::delete('sections/{id}', [TeacherSectionController::class, 'destroy']);
    Route::patch('sections/{id}/toggle-status', [TeacherSectionController::class, 'toggleStatus']);

    Route::get('lessons/trashed', [TeacherLessonController::class, 'trashed']);
    Route::post('lessons/reorder', [TeacherLessonController::class, 'reorder']);
    Route::delete('lessons/bulk-delete', [TeacherLessonController::class, 'bulkDelete']);
    Route::post('lessons/bulk-action', [TeacherLessonController::class, 'bulkAction']);
    Route::patch('lessons/bulk-restore', [TeacherLessonController::class, 'bulkRestore']);
    Route::delete('lessons/bulk-force-delete', [TeacherLessonController::class, 'bulkForceDelete']);
    Route::get('courses/{course_id}/lessons', [TeacherLessonController::class, 'index']);
    Route::post('courses/{course_id}/lessons', [TeacherLessonController::class, 'store']);
    Route::get('lessons/{id}', [TeacherLessonController::class, 'show']);
    Route::patch('lessons/{id}', [TeacherLessonController::class, 'update']);
    Route::delete('lessons/{id}', [TeacherLessonController::class, 'destroy']);
    Route::patch('lessons/{id}/toggle-status', [TeacherLessonController::class, 'toggleStatus']);
    Route::patch('lessons/{id}/restore', [TeacherLessonController::class, 'restore']);
    Route::delete('lessons/{id}/force-delete', [TeacherLessonController::class, 'forceDelete']);
});
