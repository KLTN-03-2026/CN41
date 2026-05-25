<?php

use Illuminate\Support\Facades\Broadcast;
use Modules\Quiz\Models\QuizGenerationJob;
use Modules\Upload\Models\MediaFile;

// Admin private channel: private-admin.{userId}
Broadcast::channel('admin.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
}, ['guards' => ['admin']]);

// Teacher private channel: private-teacher.{teacherId}
Broadcast::channel('teacher.{teacherId}', function ($user, $teacherId) {
    return $user->teacher && (int) $user->teacher->id === (int) $teacherId;
}, ['guards' => ['admin']]);

// Quiz job channel — any authenticated admin may subscribe
Broadcast::channel('quiz-job.{jobId}', function ($user, $jobId) {
    return QuizGenerationJob::where('id', $jobId)->exists();
}, ['guards' => ['admin']]);

// HLS media channel — any authenticated admin may subscribe
Broadcast::channel('hls.{mediaId}', function ($user, $mediaId) {
    return MediaFile::where('id', $mediaId)->exists();
}, ['guards' => ['admin']]);
