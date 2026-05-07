<?php

namespace Modules\Quiz\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Quiz\Repositories\QuizRepository;
use Modules\Quiz\Repositories\QuizRepositoryInterface;
use Modules\Quiz\Services\AIQuizService;

class QuizServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(QuizRepositoryInterface::class, QuizRepository::class);
        $this->app->singleton(AIQuizService::class, fn () => new AIQuizService);
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
