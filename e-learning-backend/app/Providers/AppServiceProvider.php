<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;

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
        // Grant all permissions to super-admin.
        // Try/catch prevents crash when permissions table doesn't exist yet (e.g. during migrate).
        try {
            Gate::before(function ($user, $ability) {
                return $user->hasRole('super-admin') ? true : null;
            });
        } catch (\Exception $e) {
            //
        }

        Feature::define('ai-quiz', true);
        Feature::define('hls-transcoding', true);
        Feature::define('payout-requests', true);
    }
}
