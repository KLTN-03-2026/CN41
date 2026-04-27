<?php

use Illuminate\Support\Facades\Route;
use Modules\Posts\Http\Controllers\Admin\CommentController as AdminCommentController;
use Modules\Posts\Http\Controllers\Admin\PostCategoryController;
use Modules\Posts\Http\Controllers\Admin\PostCategoryController as AdminPostCategoryController;
use Modules\Posts\Http\Controllers\Admin\PostController as AdminPostController;
use Modules\Posts\Http\Controllers\Admin\TagController;
use Modules\Posts\Http\Controllers\Admin\TagController as AdminTagController;
use Modules\Posts\Http\Controllers\Client\CommentController;
use Modules\Posts\Http\Controllers\Client\PostController;

Route::prefix('v1/admin')->middleware(['auth:admin'])->group(function () {
    // Post Categories
    Route::get('post-categories', [AdminPostCategoryController::class, 'index']);
    Route::post('post-categories', [AdminPostCategoryController::class, 'store']);
    Route::get('post-categories/{id}', [AdminPostCategoryController::class, 'show']);
    Route::patch('post-categories/{id}', [AdminPostCategoryController::class, 'update']);
    Route::delete('post-categories/{id}', [AdminPostCategoryController::class, 'destroy']);

    // Tags
    Route::get('tags', [AdminTagController::class, 'index']);
    Route::post('tags', [AdminTagController::class, 'store']);
    Route::get('tags/{id}', [AdminTagController::class, 'show']);
    Route::patch('tags/{id}', [AdminTagController::class, 'update']);
    Route::delete('tags/{id}', [AdminTagController::class, 'destroy']);

    // Posts
    Route::post('posts/bulk-delete', [AdminPostController::class, 'bulkDelete']);
    Route::get('posts', [AdminPostController::class, 'index']);
    Route::post('posts', [AdminPostController::class, 'store']);
    Route::get('posts/{id}', [AdminPostController::class, 'show']);
    Route::match(['put', 'patch'], 'posts/{id}', [AdminPostController::class, 'update']);
    Route::delete('posts/{id}', [AdminPostController::class, 'destroy']);
    Route::patch('posts/{id}/toggle-publish', [AdminPostController::class, 'togglePublish']);

    // Comments
    Route::post('comments/bulk-delete', [AdminCommentController::class, 'bulkDelete']);
    Route::get('comments', [AdminCommentController::class, 'index']);
    Route::patch('comments/{id}/toggle-approval', [AdminCommentController::class, 'toggleApproval']);
    Route::delete('comments/{id}', [AdminCommentController::class, 'destroy']);
});

// Client Public Routes
Route::prefix('v1')->group(function () {
    Route::get('posts', [PostController::class, 'index']);
    Route::get('posts/{slug}', [PostController::class, 'show']);
    Route::post('posts/{id}/increment-views', [PostController::class, 'incrementViews']);

    // Public Categories & Tags
    Route::get('post-categories', [PostCategoryController::class, 'index']);
    Route::get('tags', [TagController::class, 'index']);
});

// Client Auth Routes
Route::prefix('v1')->middleware(['auth:api'])->group(function () {
    Route::post('posts/{id}/comments', [CommentController::class, 'store']);
});
