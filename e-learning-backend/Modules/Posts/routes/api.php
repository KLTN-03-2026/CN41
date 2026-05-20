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
    Route::get('post-categories', [AdminPostCategoryController::class, 'index'])->middleware('permission:posts.view');
    Route::post('post-categories', [AdminPostCategoryController::class, 'store'])->middleware('permission:posts.create');
    Route::get('post-categories/{id}', [AdminPostCategoryController::class, 'show'])->middleware('permission:posts.view');
    Route::patch('post-categories/{id}', [AdminPostCategoryController::class, 'update'])->middleware('permission:posts.edit');
    Route::delete('post-categories/{id}', [AdminPostCategoryController::class, 'destroy'])->middleware('permission:posts.delete');

    // Tags
    Route::get('tags', [AdminTagController::class, 'index'])->middleware('permission:tags.view');
    Route::post('tags', [AdminTagController::class, 'store'])->middleware('permission:tags.create');
    Route::get('tags/{id}', [AdminTagController::class, 'show'])->middleware('permission:tags.view');
    Route::patch('tags/{id}', [AdminTagController::class, 'update'])->middleware('permission:tags.edit');
    Route::delete('tags/{id}', [AdminTagController::class, 'destroy'])->middleware('permission:tags.delete');

    // Posts — static routes BEFORE parameterized
    Route::get('posts/trashed', [AdminPostController::class, 'trashed'])->middleware('permission:posts.view');
    Route::delete('posts/bulk-delete', [AdminPostController::class, 'bulkDelete'])->middleware('permission:posts.delete');
    Route::get('posts', [AdminPostController::class, 'index'])->middleware('permission:posts.view');
    Route::post('posts', [AdminPostController::class, 'store'])->middleware('permission:posts.create');
    Route::get('posts/{id}', [AdminPostController::class, 'show'])->middleware('permission:posts.view');
    Route::patch('posts/{id}', [AdminPostController::class, 'update'])->middleware('permission:posts.edit');
    Route::delete('posts/{id}', [AdminPostController::class, 'destroy'])->middleware('permission:posts.delete');
    Route::patch('posts/{id}/toggle-publish', [AdminPostController::class, 'togglePublish'])->middleware('permission:posts.edit');
    Route::patch('posts/{id}/restore', [AdminPostController::class, 'restore'])->middleware('permission:posts.edit');
    Route::delete('posts/{id}/force-delete', [AdminPostController::class, 'forceDelete'])->middleware('permission:posts.delete');
    Route::patch('posts/{id}/approve', [AdminPostController::class, 'approve'])->middleware('permission:posts.edit');
    Route::patch('posts/{id}/reject', [AdminPostController::class, 'reject'])->middleware('permission:posts.edit');

    // Comments — static routes BEFORE parameterized
    Route::delete('comments/bulk-delete', [AdminCommentController::class, 'bulkDelete'])->middleware('permission:comments.delete');
    Route::get('comments', [AdminCommentController::class, 'index'])->middleware('permission:comments.view');
    Route::patch('comments/{id}/toggle-approval', [AdminCommentController::class, 'toggleApproval'])->middleware('permission:comments.delete');
    Route::delete('comments/{id}', [AdminCommentController::class, 'destroy'])->middleware('permission:comments.delete');
});

// Client Public Routes
Route::prefix('v1')->group(function () {
    Route::get('posts', [PostController::class, 'index']);
    Route::get('posts/{slug}', [PostController::class, 'show']);
    Route::post('posts/{id}/increment-views', [PostController::class, 'incrementViews']);

    Route::get('post-categories', [PostCategoryController::class, 'index']);
    Route::get('tags', [TagController::class, 'index']);
});

// Client Auth Routes
Route::prefix('v1')->middleware(['auth:api'])->group(function () {
    Route::post('posts/{id}/comments', [CommentController::class, 'store']);
});
