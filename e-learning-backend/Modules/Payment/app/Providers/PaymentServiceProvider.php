<?php

namespace Modules\Payment\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Payment\Repositories\OrderRepository;
use Modules\Payment\Repositories\OrderRepositoryInterface;

class PaymentServiceProvider extends ServiceProvider
{
    protected string $name = 'Payment';

    protected string $nameLower = 'payment';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        $this->mergeConfigFrom(module_path($this->name, 'config/vnpay.php'), 'vnpay');
        $this->mergeConfigFrom(module_path($this->name, 'config/zalopay.php'), 'zalopay');
        $this->loadViewsFrom(module_path($this->name, 'resources/views'), $this->nameLower);
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->bind(
            OrderRepositoryInterface::class,
            OrderRepository::class
        );

        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }
}
