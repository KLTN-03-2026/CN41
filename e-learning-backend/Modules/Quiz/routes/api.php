<?php

use Illuminate\Support\Facades\Route;
use Modules\Quiz\Http\Controllers\Admin\AdminQuizController;
use Modules\Quiz\Http\Controllers\Student\QuizController;

// Admin routes
Route::middleware(['auth:admin'])->prefix('admin')->group(function () {
    Route::get('quizzes', [AdminQuizController::class, 'index'])->middleware('permission:quizzes.view');
    Route::post('quizzes', [AdminQuizController::class, 'store'])->middleware('permission:quizzes.create');
    Route::get('quizzes/{id}', [AdminQuizController::class, 'show'])->middleware('permission:quizzes.view');
    Route::patch('quizzes/{id}', [AdminQuizController::class, 'update'])->middleware('permission:quizzes.edit');
    Route::delete('quizzes/{id}', [AdminQuizController::class, 'destroy'])->middleware('permission:quizzes.delete');
    Route::post('quizzes/{id}/generate', [AdminQuizController::class, 'generate'])->middleware('permission:quizzes.edit');
    Route::patch('quizzes/{id}/toggle-status', [AdminQuizController::class, 'toggleStatus'])->middleware('permission:quizzes.edit');
});

// Student routes
Route::middleware(['auth:api', 'email.verified'])->group(function () {
    Route::get('lessons/{lessonId}/quiz', [QuizController::class, 'show']);
    Route::post('quizzes/{id}/submit', [QuizController::class, 'submit']);
    Route::get('quizzes/{id}/attempts', [QuizController::class, 'attempts']);
});
