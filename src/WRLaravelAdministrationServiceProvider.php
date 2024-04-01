<?php

namespace Zephni\WRLaravelAdministration;

use Illuminate\Support\ServiceProvider;

class WRLaravelAdministrationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publishable assets
        $this->publishes([
            __DIR__ . '/config/wr-laravel-administration.php' => config_path('wr-laravel-administration.php'),
        ], 'wrla-config');

        $this->publishes([
            __DIR__ . '/resources/images/logo.svg' => public_path('vendor/wr-laravel-administration/images/logo.svg'),
        ]);

        // Merge config
        $this->mergeConfigFrom(__DIR__ . '/config/wr-laravel-administration.php', 'wr-laravel-administration');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/routes/wr-laravel-administration-routes.php');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'wr-laravel-administration');
    }
}
