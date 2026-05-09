<?php

namespace Modules\Payment\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
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
            // lockForUpdate bên trong transaction — tránh race condition
            // khi VNPAY IPN đến đúng lúc job này đang chạy
            $cancelled = DB::transaction(function () use ($order) {
                $locked = Order::where('id', $order->id)
                    ->where('status', 'pending')
                    ->lockForUpdate()
                    ->first();

                if (! $locked) {
                    return null; // đã được IPN xử lý trước
                }

                $locked->update(['status' => 'cancelled']);

                return $locked;
            });

            if ($cancelled && $cancelled->student?->email) {
                Mail::to($cancelled->student->email)
                    ->queue(new OrderCancelledMail($cancelled->load(['items.course'])));
            }
        }
    }
}
