<?php

use Illuminate\Support\Facades\Route;
use Modules\Posts\Http\Controllers\PostsController;

use Modules\Posts\Http\Controllers\Admin\PostController as AdminPostController;
use Modules\Posts\Http\Controllers\Admin\PostCategoryController as AdminPostCategoryController;
use Modules\Posts\Http\Controllers\Admin\TagController as AdminTagController;
use Modules\Posts\Http\Controllers\Admin\CommentController as AdminCommentController;

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
    Route::get('posts', [AdminPostController::class, 'index']);
    Route::post('posts', [AdminPostController::class, 'store']);
    Route::get('posts/{id}', [AdminPostController::class, 'show']);
    Route::patch('posts/{id}', [AdminPostController::class, 'update']);
    Route::delete('posts/{id}', [AdminPostController::class, 'destroy']);
    Route::patch('posts/{id}/toggle-publish', [AdminPostController::class, 'togglePublish']);

    // Comments
    Route::get('comments', [AdminCommentController::class, 'index']);
    Route::patch('comments/{id}/toggle-approval', [AdminCommentController::class, 'toggleApproval']);
    Route::delete('comments/{id}', [AdminCommentController::class, 'destroy']);
});

// Client Public Routes
Route::prefix('v1')->group(function () {
    Route::get('posts', [\Modules\Posts\Http\Controllers\Client\PostController::class, 'index']);
    Route::get('posts/{slug}', [\Modules\Posts\Http\Controllers\Client\PostController::class, 'show']);
    Route::post('posts/{id}/increment-views', [\Modules\Posts\Http\Controllers\Client\PostController::class, 'incrementViews']);
});

// Client Auth Routes
Route::prefix('v1')->middleware(['auth:api'])->group(function () {
    Route::post('posts/{id}/comments', [\Modules\Posts\Http\Controllers\Client\CommentController::class, 'store']);
});
