<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Grant all permissions to super-admin
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super-admin') ? true : null;
        });

        // Register Activity Log Listener (Laravel 11 tự động discovery nếu để trong App/Listeners)
        // \Illuminate\Support\Facades\Event::subscribe(\App\Listeners\LogActivityListener::class);
    }
}
