<?php

namespace Modules\Teachers\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Teachers\Helpers\TeachersHelper;
use Modules\Teachers\Repositories\TeachersRepository;
use Modules\Teachers\Repositories\TeachersRepositoryInterface;

class TeachersServiceProvider extends ServiceProvider
{
    protected string $name = 'Teachers';

    protected string $nameLower = 'teachers';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        // ── Repository Binding ──
        $this->app->bind(
            TeachersRepositoryInterface::class,
            TeachersRepository::class
        );

        // ── Helper Binding ──
        $this->app->singleton('TeachersHelper', function () {
            return new TeachersHelper;
        });

        $this->app->register(RouteServiceProvider::class);
    }
}
