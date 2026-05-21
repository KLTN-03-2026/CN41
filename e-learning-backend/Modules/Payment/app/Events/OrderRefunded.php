<?php

namespace Modules\Payment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Payment\Models\Order;

class OrderRefunded
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Order $order) {}
}
