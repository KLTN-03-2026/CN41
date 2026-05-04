<?php

namespace App\Listeners;

use App\Events\ActivityLogsCleared;
use App\Events\AdminLoggedIn;
use App\Events\PaymentSuccessful;

class LogActivityListener
{
    /**
     * Handle Admin Login
     */
    public function handleAdminLogin(AdminLoggedIn $event): void
    {
        activity('auth')
            ->performedOn($event->admin)
            ->causedBy($event->admin)
            ->withProperties([
                'ip' => $event->ipAddress,
                'user_agent' => $event->userAgent,
            ])
            ->log('Quản trị viên đã đăng nhập hệ thống');
    }

    /**
     * Handle Payment Success
     */
    public function handlePaymentSuccess(PaymentSuccessful $event): void
    {
        activity('payment')
            ->performedOn($event->order)
            ->causedBy($event->order->user)
            ->withProperties([
                'order_id' => $event->order->id,
                'amount' => $event->order->total_amount,
                'vnpay_transaction' => $event->vnpayData['vnp_TransactionNo'] ?? 'N/A',
            ])
            ->log("Thanh toán thành công đơn hàng #{$event->order->order_number}");
    }

    /**
     * Handle Logs Cleared
     */
    public function handleLogsCleared(ActivityLogsCleared $event): void
    {
        activity('system')
            ->causedBy($event->admin)
            ->log('Quản trị viên đã dọn dẹp toàn bộ lịch sử hoạt động');
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe($events): array
    {
        return [
            AdminLoggedIn::class => 'handleAdminLogin',
            PaymentSuccessful::class => 'handlePaymentSuccess',
            ActivityLogsCleared::class => 'handleLogsCleared',
        ];
    }
}
