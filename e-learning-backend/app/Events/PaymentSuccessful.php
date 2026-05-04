<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Payment\Models\Order;

class PaymentSuccessful
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Order $order;

    public array $vnpayData;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order, array $vnpayData)
    {
        $this->order = $order;
        $this->vnpayData = $vnpayData;
    }
}
