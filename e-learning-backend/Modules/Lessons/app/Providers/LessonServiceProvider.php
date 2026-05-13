<?php

namespace Modules\Lessons\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Lessons\Repositories\LessonRepository;
use Modules\Lessons\Repositories\LessonRepositoryInterface;
use Modules\Lessons\Repositories\SectionRepository;
use Modules\Lessons\Repositories\SectionRepositoryInterface;

class LessonServiceProvider extends ServiceProvider
{
    protected string $name = 'Lessons';

    protected string $nameLower = 'lessons';

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
            LessonRepositoryInterface::class,
            LessonRepository::class
        );

        $this->app->bind(
            SectionRepositoryInterface::class,
            SectionRepository::class
        );

        $this->app->register(RouteServiceProvider::class);
    }
}
