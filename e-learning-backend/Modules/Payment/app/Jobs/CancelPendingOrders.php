<?php

namespace Modules\Payment\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Modules\Payment\Mail\OrderCancelledMail;
use Modules\Payment\Models\Order;

class CancelPendingOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $expiredOrders = Order::with(['student', 'items.course'])
            ->where('status', 'pending')
            ->where('created_at', '<=', now()->subMinutes(15))
            ->get();

        foreach ($expiredOrders as $order) {
            $order->update(['status' => 'cancelled']);

            if ($order->student?->email) {
                Mail::to($order->student->email)
                    ->queue(new OrderCancelledMail($order));
            }
        }
    }
}
