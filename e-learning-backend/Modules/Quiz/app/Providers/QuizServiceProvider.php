<?php

namespace Modules\Quiz\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Quiz\Repositories\QuizRepository;
use Modules\Quiz\Repositories\QuizRepositoryInterface;
use Modules\Quiz\Services\GeminiQuizService;

class QuizServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(QuizRepositoryInterface::class, QuizRepository::class);
        $this->app->singleton(GeminiQuizService::class, fn () => new GeminiQuizService);
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path('Quiz', 'database/migrations'));
        $this->registerConfig();
    }

    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(module_path('Quiz', 'config/config.php'), 'quiz');
    }
}
