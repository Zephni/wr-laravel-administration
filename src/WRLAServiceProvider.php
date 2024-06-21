<?php

namespace WebRegulate\LaravelAdministration;

use Livewire\Livewire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use WebRegulate\LaravelAdministration\Models\User;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Commands\WikiCommand;
use WebRegulate\LaravelAdministration\Livewire\LivewireModal;
use WebRegulate\LaravelAdministration\Classes\WRLAPermissions;
use WebRegulate\LaravelAdministration\Commands\InstallCommand;
use WebRegulate\LaravelAdministration\Http\Middleware\IsAdmin;
use WebRegulate\LaravelAdministration\Commands\CreateUserCommand;
use WebRegulate\LaravelAdministration\Http\Middleware\IsNotAdmin;
use WebRegulate\LaravelAdministration\Commands\CreateManageableModelCommand;
use WebRegulate\LaravelAdministration\Classes\NavigationItems\NavigationItem;
use WebRegulate\LaravelAdministration\Livewire\ManageableModels\ManageableModelBrowse;

class WRLAServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(__DIR__ . '/config/wr-laravel-administration.php', 'wr-laravel-administration');

        // Merge wrla info and error logging channels
        $this->app->make('config')->set('logging.channels.wrla-info', [
            'driver' => 'single',
            'path' => storage_path('logs/wrla-info.log'),
            'level' => 'debug',
        ]);

        $this->app->make('config')->set('logging.channels.wrla-error', [
            'driver' => 'single',
            'path' => storage_path('logs/wrla-error.log'),
            'level' => 'debug',
        ]);

        // Register Livewire
        $this->app->register(\Livewire\LivewireServiceProvider::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish assets
        $this->publishableAssets();

        // Main setup - Loading migrations, routes, views, etc.
        $this->mainSetup();

        // Pass variables to all routes within this package
        $this->passVariablesToViews();

        // Provide blade directives
        $this->provideBladeDirectives();

        // Post boot calls
        $this->app->booted(function () {
            $this->postBootCalls();
        });
    }

    /**
     * Set publishable assets
     * @return void
     */
    protected function publishableAssets(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/config/wr-laravel-administration.php' => config_path('wr-laravel-administration.php'),
        ], 'wrla-config');

        // Publish assets
        $this->publishes([
            __DIR__ . '/resources/images/logo-light.svg' => public_path('vendor/wr-laravel-administration/images/logo-light.svg'),
            __DIR__ . '/resources/images/logo-dark.svg' => public_path('vendor/wr-laravel-administration/images/logo-dark.svg'),
            __DIR__ . '/resources/images/no-image-transparent.svg' => public_path('vendor/wr-laravel-administration/images/no-image-transparent.svg'),
        ], 'wrla-assets');

        // Publish models
        $this->publishes([
            __DIR__ . '/app/WRLA' => app_path('WRLA'),
        ], 'wrla-models');
    }

    /**
     * Main setup - Loading assets, routes, etc.
     * @return void
     */
    protected function mainSetup(): void
    {
        // Commands
        $this->commands([
            InstallCommand::class,
            CreateManageableModelCommand::class,
            CreateUserCommand::class,
            WikiCommand::class,
        ]);

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        // Load middleware
        $this->app['router']->aliasMiddleware('is_admin', IsAdmin::class);
        $this->app['router']->aliasMiddleware('is_not_admin', IsNotAdmin::class);

        // Load routes
        Route::middleware('web')->group(function () {
            $this->loadRoutesFrom(__DIR__ . '/routes/wr-laravel-administration-routes.php');
        });

        // Find all classes that extend ManageableModel and register them
        WRLAHelper::registerManageableModels();

        // Register validation rules
        $this->registerValidationRules();

        // Configure rate limiting for routes - Set in wr-laravel-administration.rate_limiting config
        $this->configureRateLimiting(Request::capture());

        // Load views
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'wr-laravel-administration');

        // Livewire component registering and asset injection
        Livewire::component('wrla.manageable-models.browse', ManageableModelBrowse::class);
        Livewire::component('wrla.livewire-modal', LivewireModal::class);
        Livewire::forceAssetInjection();
    }

    /**
     * Configure rate limiting for applicable routes.
     *
     * @param Request $request
     */
    protected function configureRateLimiting(Request $request)
    {
        // Get the rate limiting configuration
        $rateLimitingConfig = config('wr-laravel-administration.rate_limiting');
        $prefix = 'wrla.';

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

    /**
     * Register custom validation rules
     * 
     * @return void
     */
    protected function registerValidationRules(): void
    {
        Validator::extend('wrla_no_change', function ($attribute, $value, $parameters, $validator) {
            // Parameter 0 is the table name, 1 is the id, 2 is the column name
            $tableName = $parameters[0];
            $id = $parameters[1];
            $column = $parameters[2];
            $jsonDotNotation = false; // Note that we must pass the column name in the format 'column->key1->key2' if using json notation

            // Check if column uses wrla json notation
            if(str($column)->contains('->')) {
                [$column, $jsonDotNotation] = WRLAHelper::parseJsonNotation($column);
            }

            // Use query builder to get the original value
            $originalValue = DB::table($tableName)->where('id', $id)->value($column);

            // If using json notation, get the value from the json column
            if($jsonDotNotation !== false) {
                $originalValue = data_get(json_decode($originalValue), $jsonDotNotation);
            }

            // Add message to validator
            $validator->addReplacer('wrla_no_change', function ($message, $attribute, $rule, $parameters) use ($originalValue) {
                // If originonal value is a boolean, convert to string
                if(is_bool($originalValue)) {
                    $originalValue = $originalValue ? 'true' : 'false';
                }
                
                return str_replace(':origional_value', $originalValue, $message);
            });

            // Check if value has changed, if type passed then check strict comparison
            return $originalValue === $value;
        }, "':attribute' cannot be changed from it's original value: :origional_value.");
    }

    /**
     * Pass variables to all views within this package
     * @return void
     */
    protected function passVariablesToViews(): void
    {
        // Share variables with all views within this package
        view()->composer('wr-laravel-administration::*', function ($view) {
            // Current user
            $view->with('WRLAUser', User::current());

            // Theme data
            $view->with('WRLAThemeData', (object)WRLAHelper::getCurrentThemeData());

            // Share WRLAHelper class
            $view->with('WRLAHelper', WRLAHelper::class);

            // Share WRLAPermissions class
            $view->with('WRLAPermissions', WRLAPermissions::class);
        });
    }

    /**
     * Provide blade directives
     * @return void
     */
    protected function provideBladeDirectives(): void
    {
        // Theme component attempts to load a theme specific component first, then falls back to the default component if doesn't exist
        Blade::directive('themeComponent', function ($expression) {
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

            // If still false, throw error
            throw_if(
                $fullComponentPath === false,
                new \Exception("@themeComponent error, args passed: $expression, The component '$componentPath' does not exist within the current theme or the default theme. Full component path: $fullComponentPath")
            );

            // Display the component with the provided attributes
            return "<?php echo view('{$fullComponentPath}', {$args[1]})->render(); ?>";
        });
    }

    /**
     * Post boot calls
     * @return void
     */
    protected function postBootCalls(): void
    {
        // Run mainSetup on all manageable models. and then run all globalSetup methods, this is so that
        // the base MM class data is set before the globalSetup method is called in case of dependencies
        foreach(WRLAHelper::$globalManageableModelData as $className => $value) {
            $className::mainSetup();
        }

        foreach(WRLAHelper::$globalManageableModelData as $className => $value) {
            $className::globalSetup();
        }

        // Set navigation items (if App\WRLA\WRLASetup exists)
        if(class_exists('\App\WRLA\WRLASetup')) {
            NavigationItem::$navigationItems = \App\WRLA\WRLASetup::buildNavigation() ?? [];
        }
    }
}
