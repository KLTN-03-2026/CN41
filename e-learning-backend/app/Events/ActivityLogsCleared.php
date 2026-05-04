<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Users\Models\User;

class ActivityLogsCleared
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $admin
    ) {}
}
