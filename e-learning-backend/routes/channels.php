<?php

use Illuminate\Support\Facades\Broadcast;

// Admin private channel: private-admin.{userId}
Broadcast::channel('admin.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
}, ['guards' => ['admin']]);

// Teacher private channel: private-teacher.{teacherId}
Broadcast::channel('teacher.{teacherId}', function ($user, $teacherId) {
    return $user->teacher && (int) $user->teacher->id === (int) $teacherId;
}, ['guards' => ['admin']]);
