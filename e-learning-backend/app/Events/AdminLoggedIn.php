<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Users\Models\User;

class AdminLoggedIn
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $admin,
        public string $ipAddress,
        public string $userAgent
    ) {}
}
