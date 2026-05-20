<?php

namespace Modules\Commission\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Commission\Listeners\CommissionListener;
use Modules\Commission\Repositories\CommissionRepository;
use Modules\Commission\Repositories\CommissionRepositoryInterface;
use Modules\Payment\Events\OrderPlaced;
use Modules\Payment\Events\OrderRefunded;

class CommissionServiceProvider extends ServiceProvider
{
    protected string $name = 'Commission';

    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        Event::listen(OrderPlaced::class, [CommissionListener::class, 'handleOrderPlaced']);
        Event::listen(OrderRefunded::class, [CommissionListener::class, 'handleOrderRefunded']);
    }

    public function register(): void
    {
        $this->app->bind(CommissionRepositoryInterface::class, CommissionRepository::class);
        $this->app->register(RouteServiceProvider::class);
    }
}
