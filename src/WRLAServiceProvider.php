<?php

namespace WebRegulate\LaravelAdministration;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class WRLAServiceProvider extends ServiceProvider
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
            __DIR__ . '/resources/images/logo-light.svg' => public_path('vendor/wr-laravel-administration/images/logo-light.svg'),
            __DIR__ . '/resources/images/logo-dark.svg' => public_path('vendor/wr-laravel-administration/images/logo-dark.svg')
        ], 'wrla-assets');

        $this->publishes([
            __DIR__ . '/Publish/Models' => app_path('WRLA/Models')
        ], 'wrla-models');

        // Merge config
        $this->mergeConfigFrom(__DIR__ . '/config/wr-laravel-administration.php', 'wr-laravel-administration');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        // Load routes
        Route::middleware('web')->group(function () {
            $this->loadRoutesFrom(__DIR__ . '/routes/wr-laravel-administration-routes.php');
        });

        // Load views
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'wr-laravel-administration');
    }
}
