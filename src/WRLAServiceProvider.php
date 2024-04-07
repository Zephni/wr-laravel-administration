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
        /* Publishable assets
        --------------------------------------------- */
        // Publish config
        $this->publishes([
            __DIR__ . '/config/wr-laravel-administration.php' => config_path('wr-laravel-administration.php'),
        ], 'wrla-config');

        // Publish assets
        $this->publishes([
            __DIR__ . '/resources/images/logo-light.svg' => public_path('vendor/wr-laravel-administration/images/logo-light.svg'),
            __DIR__ . '/resources/images/logo-dark.svg' => public_path('vendor/wr-laravel-administration/images/logo-dark.svg')
        ], 'wrla-assets');

        // Publish models
        $this->publishes([
            __DIR__ . '/app/WRLA' => app_path('WRLA')
        ], 'wrla-models');

        // Publish service provider
        $this->publishes([
            __DIR__ . '/WRLAServiceProvider.php' => app_path('Providers/WRLAServiceProvider.php')
        ], 'wrla-providers');

        /* Mergeable & Loadable assets
        --------------------------------------------- */
        // Merge config
        $this->mergeConfigFrom(__DIR__ . '/config/wr-laravel-administration.php', 'wr-laravel-administration');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        // Load middleware
        $this->app['router']->aliasMiddleware('is_admin', \WebRegulate\LaravelAdministration\Http\Middleware\IsAdmin::class);
        $this->app['router']->aliasMiddleware('is_not_admin', \WebRegulate\LaravelAdministration\Http\Middleware\IsNotAdmin::class);

        // Load routes
        Route::middleware('web')->group(function () {
            $this->loadRoutesFrom(__DIR__ . '/routes/wr-laravel-administration-routes.php');
        });

        // Load views
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'wr-laravel-administration');

        // Pass current user to all views
        view()->composer('*', function ($view) {
            $view->with('user', \App\WRLA\User::current());
        });
    }
}
