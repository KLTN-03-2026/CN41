<?php

use Illuminate\Support\Facades\Route;
use Modules\Upload\Http\Controllers\UploadController;

/*
|--------------------------------------------------------------------------
| Admin Routes (auth:admin middleware)
|--------------------------------------------------------------------------
| Local flow: uploadVideo, uploadDocument, uploadImage, destroy
| S3 flow:    presigned, confirm, destroy
*/
Route::middleware(['auth:admin'])->prefix('admin')->group(function () {
    // Local flow
    Route::post('upload/video', [UploadController::class, 'uploadVideo'])
        ->middleware('permission:lessons.create|lessons.edit');
    Route::post('upload/document', [UploadController::class, 'uploadDocument'])
        ->middleware('permission:lessons.create|lessons.edit');
    Route::post('upload/image', [UploadController::class, 'uploadImage'])
        ->middleware('permission:courses.create|courses.edit|admin_users.edit|posts.create|posts.edit');

    // S3 flow
    Route::post('upload/presigned', [UploadController::class, 'presigned'])
        ->middleware('permission:lessons.create|lessons.edit|courses.create|courses.edit');
    Route::post('upload/{id}/confirm', [UploadController::class, 'confirm'])
        ->middleware('permission:lessons.create|lessons.edit|courses.create|courses.edit');

    // Delete (dùng chung cho cả 2 flow)
    Route::delete('upload/{id}', [UploadController::class, 'destroy'])
        ->middleware('permission:courses.edit|courses.delete|lessons.edit|lessons.delete|posts.edit|posts.delete');
});

// Stream nội dung media — auth được xử lý trong controller (hỗ trợ token qua query param)
Route::get('media/{id}/stream', [UploadController::class, 'stream'])->name('media.stream');
Route::get('media/{id}/hls-key', [UploadController::class, 'hlsKey'])->name('media.hls-key');
Route::get('media/{id}/document', [UploadController::class, 'streamDocument'])->name('media.document');
