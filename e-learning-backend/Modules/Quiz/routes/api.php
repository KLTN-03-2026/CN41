<?php

use Illuminate\Support\Facades\Route;
use Modules\Quiz\Http\Controllers\Admin\AdminQuizController;
use Modules\Quiz\Http\Controllers\Admin\QuizGenerateController;
use Modules\Quiz\Http\Controllers\Student\QuizController;

// ── Admin: Quiz management (standalone) ──────────────────────
Route::middleware(['auth:admin'])->prefix('admin')->group(function () {
    Route::get('quizzes', [AdminQuizController::class, 'index'])->middleware('permission:quizzes.view');
    Route::post('quizzes', [AdminQuizController::class, 'store'])->middleware('permission:quizzes.create');
    Route::get('quizzes/{id}', [AdminQuizController::class, 'show'])->middleware('permission:quizzes.view');
    Route::patch('quizzes/{id}', [AdminQuizController::class, 'update'])->middleware('permission:quizzes.edit');
    Route::delete('quizzes/{id}', [AdminQuizController::class, 'destroy'])->middleware('permission:quizzes.delete');
    Route::post('quizzes/{id}/generate', [AdminQuizController::class, 'generate'])->middleware('permission:quizzes.edit');
    Route::patch('quizzes/{id}/toggle-status', [AdminQuizController::class, 'toggleStatus'])->middleware('permission:quizzes.edit');

    // ── Quiz gắn với lesson (dùng prefix lesson-quiz để tránh conflict với Lessons module) ──
    Route::get('lesson-quiz/{lessonId}', [QuizGenerateController::class, 'show'])->middleware('permission:quizzes.view');
    Route::post('lesson-quiz/{lessonId}/generate', [QuizGenerateController::class, 'generate'])->middleware('permission:quizzes.edit');
    Route::get('lesson-quiz/{lessonId}/chapter-pdfs', [QuizGenerateController::class, 'chapterPdfs'])->middleware('permission:quizzes.view');

    // ── Sửa/xóa từng câu hỏi ──
    Route::patch('quiz-questions/{questionId}', [QuizGenerateController::class, 'updateQuestion'])->middleware('permission:quizzes.edit');
    Route::delete('quiz-questions/{questionId}', [QuizGenerateController::class, 'deleteQuestion'])->middleware('permission:quizzes.delete');
});

// ── Student: làm bài quiz ────────────────────────────────────
Route::middleware(['auth:api', 'email.verified'])->group(function () {
    // Lấy quiz theo lesson (dùng trong LearnPage)
    Route::get('lessons/{lessonId}/quiz', [QuizController::class, 'show']);
    // Submit bài
    Route::post('quizzes/{id}/submit', [QuizController::class, 'submit']);
    // Lịch sử làm bài
    Route::get('quizzes/{id}/attempts', [QuizController::class, 'attempts']);
});
