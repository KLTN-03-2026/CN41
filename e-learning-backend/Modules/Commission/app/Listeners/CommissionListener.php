<?php

namespace Modules\Commission\Listeners;

use Modules\Payment\Events\OrderPlaced;
use Modules\Payment\Events\OrderRefunded;

class CommissionListener
{
    // Full implementation will be added in Task 3
    public function handleOrderPlaced(OrderPlaced $event): void
    {
        // TODO: Task 3
    }

    public function handleOrderRefunded(OrderRefunded $event): void
    {
        // TODO: Task 3
    }
}
