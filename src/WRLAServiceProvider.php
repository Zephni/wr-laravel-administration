<?php

namespace WebRegulate\LaravelAdministration;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use WebRegulate\LaravelAdministration\Models\User;
use Illuminate\Auth\Passwords\PasswordBrokerManager;
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
        $this->configureRateLimiting();

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

    protected function configureRateLimiting()
    {
        RateLimiter::for('login', function ($request) {
            return Limit::perMinutes(10, 5)->by($request->input('email'))->response(function() {
                return redirect()->route('wrla.login')->with('error', 'Too many login attempts. Please try again in 10 minutes.');
            });
        });

        RateLimiter::for('forgot-password', function ($request) {
            return Limit::perMinutes(10, 3)->by($request->input('email'))->response(function() {
                return redirect()->route('wrla.forgot-password')->with('error', 'Too many requests. Please try again in 10 minutes.');
            });
        });

        RateLimiter::for('reset-password', function ($request) {
            return Limit::perMinutes(10, 3)->by($request->input('email'))->response(function() {
                return redirect()->route('wrla.reset-password')->with('error', 'Too many requests. Please try again in 10 minutes.');
            });
        });
    }
}
