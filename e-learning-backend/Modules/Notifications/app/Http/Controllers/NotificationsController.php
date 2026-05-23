<?php

namespace Modules\Notifications\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Notifications\Models\AppNotification;

class NotificationsController extends Controller
{
    use ApiResponse;

    // ── Admin notifications ───────────────────────────────────────────────

    public function adminIndex(): JsonResponse
    {
        $id = auth('admin')->id();

        return $this->listFor('admin', $id);
    }

    public function adminMarkRead(int $notificationId): JsonResponse
    {
        return $this->doMarkRead('admin', auth('admin')->id(), $notificationId);
    }

    public function adminMarkAllRead(): JsonResponse
    {
        return $this->doMarkAllRead('admin', auth('admin')->id());
    }

    // ── Teacher notifications ─────────────────────────────────────────────

    public function teacherIndex(): JsonResponse
    {
        $teacher = auth('admin')->user()->teacher;
        abort_unless($teacher, 403, 'Không phải giảng viên.');

        return $this->listFor('teacher', $teacher->id);
    }

    public function teacherMarkRead(int $notificationId): JsonResponse
    {
        $teacher = auth('admin')->user()->teacher;
        abort_unless($teacher, 403);

        return $this->doMarkRead('teacher', $teacher->id, $notificationId);
    }

    public function teacherMarkAllRead(): JsonResponse
    {
        $teacher = auth('admin')->user()->teacher;
        abort_unless($teacher, 403);

        return $this->doMarkAllRead('teacher', $teacher->id);
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private function listFor(string $type, int $id): JsonResponse
    {
        $notifications = AppNotification::forRecipient($type, $id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'body' => $n->body,
                'data' => $n->data,
                'read_at' => $n->read_at?->toISOString(),
                'created_at' => $n->created_at->toISOString(),
            ]);

        $unreadCount = AppNotification::forRecipient($type, $id)->unread()->count();

        return $this->success([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ], 'Danh sách thông báo');
    }

    private function doMarkRead(string $type, int $recipientId, int $notificationId): JsonResponse
    {
        $notification = AppNotification::forRecipient($type, $recipientId)->findOrFail($notificationId);
        $notification->markAsRead();

        return $this->success(null, 'Đã đánh dấu đã đọc');
    }

    private function doMarkAllRead(string $type, int $id): JsonResponse
    {
        AppNotification::forRecipient($type, $id)->unread()->update(['read_at' => now()]);

        return $this->success(null, 'Đã đọc tất cả thông báo');
    }
}
