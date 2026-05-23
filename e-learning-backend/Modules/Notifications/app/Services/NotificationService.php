<?php

namespace Modules\Notifications\Services;

use Illuminate\Support\Facades\Broadcast;
use Modules\Notifications\Models\AppNotification;

class NotificationService
{
    public function send(
        string $recipientType,
        int $recipientId,
        string $type,
        string $title,
        string $body,
        array $data = []
    ): AppNotification {
        $notification = AppNotification::create([
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data ?: null,
        ]);

        $channel = "private-{$recipientType}.{$recipientId}";

        Broadcast::on($channel)->as('NewNotification')->with([
            'id' => $notification->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'created_at' => $notification->created_at->toISOString(),
        ])->send();

        return $notification;
    }

    // ── Convenience methods for each event type ───────────────────────────

    public function notifyEnrollment(int $teacherId, string $studentName, string $courseTitle, int $courseId): void
    {
        $this->send(
            recipientType: 'teacher',
            recipientId: $teacherId,
            type: 'enrollment',
            title: 'Học viên mới đăng ký',
            body: "{$studentName} vừa đăng ký khóa học \"{$courseTitle}\".",
            data: ['course_id' => $courseId, 'student_name' => $studentName]
        );
    }

    public function notifyPayoutRequest(int $adminId, string $teacherName, float $amount, int $payoutId): void
    {
        $formatted = number_format($amount, 0, ',', '.');

        $this->send(
            recipientType: 'admin',
            recipientId: $adminId,
            type: 'payout_request',
            title: 'Yêu cầu rút tiền mới',
            body: "{$teacherName} yêu cầu rút {$formatted}₫.",
            data: ['payout_id' => $payoutId, 'amount' => $amount]
        );
    }

    public function notifyPayoutDecision(int $teacherId, string $status, float $amount, int $payoutId): void
    {
        $label = $status === 'approved' ? 'được duyệt' : 'bị từ chối';
        $formatted = number_format($amount, 0, ',', '.');

        $this->send(
            recipientType: 'teacher',
            recipientId: $teacherId,
            type: 'payout_decision',
            title: 'Cập nhật yêu cầu rút tiền',
            body: "Yêu cầu rút {$formatted}₫ của bạn đã {$label}.",
            data: ['payout_id' => $payoutId, 'status' => $status, 'amount' => $amount]
        );
    }

    public function notifyCoursePending(int $adminId, string $teacherName, string $courseTitle, int $courseId): void
    {
        $this->send(
            recipientType: 'admin',
            recipientId: $adminId,
            type: 'course_pending',
            title: 'Khóa học chờ duyệt',
            body: "{$teacherName} vừa gửi khóa học \"{$courseTitle}\" để duyệt.",
            data: ['course_id' => $courseId]
        );
    }

    public function notifyNewComment(int $teacherId, string $studentName, string $courseTitle, int $courseId, int $lessonId): void
    {
        $this->send(
            recipientType: 'teacher',
            recipientId: $teacherId,
            type: 'new_comment',
            title: 'Bình luận mới',
            body: "{$studentName} đã bình luận vào bài học trong khóa \"{$courseTitle}\".",
            data: ['course_id' => $courseId, 'lesson_id' => $lessonId]
        );
    }
}
