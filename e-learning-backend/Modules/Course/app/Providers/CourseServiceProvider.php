<?php

namespace Modules\Course\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Course\Repositories\CourseRepository;
use Modules\Course\Repositories\CourseRepositoryInterface;

class CourseServiceProvider extends ServiceProvider
{
    protected string $name = 'Course';

    protected string $nameLower = 'course';

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
            CourseRepositoryInterface::class,
            CourseRepository::class
        );

        $this->app->register(RouteServiceProvider::class);
    }
}
