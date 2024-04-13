<?php

namespace WebRegulate\LaravelAdministration;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
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
        // Merge config
        $this->mergeConfigFrom(__DIR__ . '/config/wr-laravel-administration.php', 'wr-laravel-administration');

        // Register Livewire
        $this->app->register(\Livewire\LivewireServiceProvider::class);
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

        /* Loadable assets
        --------------------------------------------- */
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        // Load middleware
        $this->app['router']->aliasMiddleware('is_admin', IsAdmin::class);
        $this->app['router']->aliasMiddleware('is_not_admin', IsNotAdmin::class);

        // Load routes
        Route::middleware('web')->group(function () {
            $this->loadRoutesFrom(__DIR__ . '/routes/wr-laravel-administration-routes.php');
        });

        // Configure rate limiting for routes - Set in wr-laravel-administration.rate_limiting config
        $this->configureRateLimiting(Request::capture());

        // Load views
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'wr-laravel-administration');

        // Livewire asset injection
        \Livewire\Livewire::forceAssetInjection();

        // Get theme data
        $currentThemeData = (object)WRLAHelper::getCurrentThemeData();

        // Pass variables to all routes within this package
        view()->composer('wr-laravel-administration::*', function ($view) use ($currentThemeData) {
            // Current user
            $view->with('user', User::current());

            // Theme data
            $view->with('themeData', $currentThemeData);

            // Share WRLAHelper class
            $view->with('WRLAHelper', WRLAHelper::class);
        });

        // Theme component attempts to load a theme specific component first, then falls back to the default component if doesn't exist
        Blade::directive('themeComponent', function ($expression) use ($currentThemeData) {
            // Split first argument from the rest (component path)
            $args = explode(',', $expression, 2);

            // Remove string quotes from the first argument
            $componentPath = 'components.' . trim($args[0], " \t\n\r\0\x0B'\"");

            // First check whether a theme specific comoponent exists
            $fullComponentPath = WRLAHelper::getViewPath($componentPath, true);

            // If not then fall back to the default component
            if($fullComponentPath === false) {
                $fullComponentPath = WRLAHelper::getViewPath($componentPath, false);
            }

            // If still false, return an error message
            if($fullComponentPath === false) {
                dd(
                    "@themeComponent error",
                    "args passed: $expression",
                    "The component '$componentPath' does not exist within the current theme or the default theme.",
                    $fullComponentPath
                );
            }

            // Display the component with the provided attributes
            return "<?php echo view('{$fullComponentPath}', {$args[1]})->render(); ?>";
        });
    }

    /**
     * Configure rate limiting for routes.
     *
     * @param Request $request
     */
    protected function configureRateLimiting(Request $request)
    {
        // Get the rate limiting configuration
        $rateLimitingConfig = config('wr-laravel-administration.rate_limiting');
        $prefix = 'wrla.';

        // Prepend prefix to each key in the rate limiting configuration
        $rateLimitingConfig = array_combine(
            array_map(fn($key) => $prefix . $key, array_keys($rateLimitingConfig)),
            array_values($rateLimitingConfig)
        );

        // Refresh route name lookups
        Route::getRoutes()->refreshNameLookups();
        $routes = Route::getRoutes()->getRoutesByName();

        // Loop through each route and apply rate limiting if configured
        foreach ($routes as $routeName => $route) {
            // Check if the route name starts with the specified prefix and if it exists in the rate limiting configuration
            if (str_starts_with($routeName, $prefix) && array_key_exists($routeName, $rateLimitingConfig)) {
                // Get the rate limit configuration item for the route
                $rateLimitConfigItem = $rateLimitingConfig[$routeName];

                // Build the rate limiter for the route
                WRLAHelper::buildRateLimiter($request, $routeName, $rateLimitConfigItem);

                // Apply the throttle middleware to the route
                $route->middleware("throttle:{$routeName}");
            }
        }
    }
}
