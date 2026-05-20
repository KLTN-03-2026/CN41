<?php

namespace Modules\Commission\Listeners;

use Modules\Commission\Services\CommissionService;
use Modules\Payment\Events\OrderPlaced;
use Modules\Payment\Events\OrderRefunded;

class CommissionListener
{
    public function __construct(private CommissionService $service) {}

    public function handleOrderPlaced(OrderPlaced $event): void
    {
        $event->order->loadMissing('items.course.teacher');
        $this->service->recordEarnings($event->order);
    }

    public function handleOrderRefunded(OrderRefunded $event): void
    {
        $event->order->loadMissing('items.course.teacher');
        $this->service->reverseEarnings($event->order);
    }
}
