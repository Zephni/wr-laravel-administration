<?php

namespace WebRegulate\LaravelAdministration;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use WebRegulate\LaravelAdministration\Models\User;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Http\Middleware\IsAdmin;
use WebRegulate\LaravelAdministration\Http\Middleware\IsNotAdmin;

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

        /* Mergeable & Loadable assets
        --------------------------------------------- */
        // Merge config
        $this->mergeConfigFrom(__DIR__ . '/config/wr-laravel-administration.php', 'wr-laravel-administration');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        // Load middleware
        $this->app['router']->aliasMiddleware('is_admin', IsAdmin::class);
        $this->app['router']->aliasMiddleware('is_not_admin', IsNotAdmin::class);

        // Load routes
        Route::middleware('web')->group(function () {
            $this->loadRoutesFrom(__DIR__ . '/routes/wr-laravel-administration-routes.php');
        });

        // Load views
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'wr-laravel-administration');

        // Get theme data
        $currentThemeData = (object)WRLAHelper::getCurrentThemeData();

        // Pass variables to all routes within this package
        view()->composer('wr-laravel-administration::*', function ($view) use ($currentThemeData) {
            // Current user
            $view->with('user', User::current());

            // Theme data
            $view->with('themeData', $currentThemeData);
            $view->with('themeViewPath', WRLAHelper::getViewPath('', true));
        });

        /* Blade directives
        --------------------------------------------- */
        // Theme component attempts to load a theme specific component first, then falls back to the default component if doesn't exist
        Blade::directive('themeComponent', function ($expression) use ($currentThemeData) {
            // Split first argument from the rest (component path)
            $args = explode(',', $expression, 2);

            // Remove string quotes from the first argument
            $componentPath = trim($args[0], " \t\n\r\0\x0B'\"");

            // First check whether a theme specific comoponent exists
            $componentPath = file_exists(__DIR__ . '/resources/views/themes/' . $currentThemeData->path . '/components/' . $args[0] . '.blade.php')
                ? WRLAHelper::getViewPath('components.' . $componentPath, true)
                : WRLAHelper::getViewPath('components.' . $componentPath, false);

            // Display the component with the provided attributes
            return "<?php echo view('$componentPath', $args[1])->render(); ?>";
        });
    }
}
