<?php

namespace WebRegulate\LaravelAdministration\Classes;

use Livewire\Livewire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use WebRegulate\LaravelAdministration\Models\User;
use WebRegulate\LaravelAdministration\Enums\PageType;
use WebRegulate\LaravelAdministration\Enums\ManageableModelPermissions;
use WebRegulate\LaravelAdministration\Classes\NavigationItems\NavigationItem;

class WRLAHelper
{
    /**
     * Key remove constant
     *
     * @var string
     */
    const WRLA_KEY_REMOVE = '__WRLA::KEY::REMOVE__';

    /**
     * Key remove constant
     *
     * @var string
     */
    const WRLA_REL_DOT = '__WRLA::REL::DOT__';

    /**
     * Manageable model setup global data, uses format
     * '\App\WRLA\ManageableModelClass' => $staticOptions
     *
     * @return array
     */
    public static array $globalManageableModelData = [];

    /**
     * Current page type
     *
     * @var PageType
     */
    public static PageType $currentPageType = PageType::GENERAL;

    /**
     * Current active manageable model class
     *
     * @var ?string
     */
    public static ?string $currentActiveManageableModelClass = null;

    /**
     * Get documenation URL
     *
     * @return string
     */
    public static function getDocumentationUrl(): string
    {
        return 'https://github.com/Zephni/wr-laravel-administration/wiki';
    }

    /**
     * Builds page title from 'title_template' config which uses the format '{page_title} - WebRegulate Admin'.
     *
     * @param string $pageTitle The page title to build.
     * @return string The built page title.
     */
    public static function buildPageTitle(string $pageTitle): string
    {
        // Setup final title as empty string
        $returnTitle = '';

        // Get the title template from the config
        $titleTemplate = config('wr-laravel-administration.title_template');

        // Replace the page title in the title format
        $returnTitle = str_replace('{page_title}', $pageTitle, $titleTemplate);

        return $returnTitle;
    }

    /**
     * Set current page type
     *
     * @param PageType $pageType The page type to set.
     * @return PageType
     */
    public static function setCurrentPageType(PageType $pageType): PageType
    {
        static::$currentPageType = $pageType;
        return static::$currentPageType;
    }

    /**
     * Get current page type
     *
     * @return ?PageType $pageType
     */
    public static function getCurrentPageType(): ?PageType
    {
        return static::$currentPageType;
    }

    /**
     * Set currently active manageable model, if user does not have HAS_ACCESS permission then redirect to dashboard.
     *
     * @param ?string $manageableModel The manageable model to set as active.
     * @return ?string
     */
    public static function setCurrentActiveManageableModelClass(?string $manageableModelClass): ?string
    {
        static::$currentActiveManageableModelClass = $manageableModelClass;

        // Get current active manageable model and check it has HAS_ACCESS permission
        if(static::$currentActiveManageableModelClass::getPermission(ManageableModelPermissions::ENABLED) !== true) {
            $message = "Cannot access requested route: You do not have access to the ". static::$currentActiveManageableModelClass::getDisplayName() ." manageable model.";
            abort(redirect()->route('wrla.dashboard')->with('error', $message));
        }

        return static::$currentActiveManageableModelClass;
    }

    /**
     * Get currently active manageable model
     * 
     * @return ?string
     */
    public static function getCurrentActiveManageableModelClass(): ?string
    {
        return static::$currentActiveManageableModelClass;
    }

    /**
     * Get the data of the current theme from config, either the entire array or key dot notation within it.
     *
     * @param string|null $keyDotNotation The dot notation key to retrieve specific data from the theme.
     * @return mixed The data or found value within the current theme.
     */
    public static function getCurrentThemeData(?string $keyDotNotation = null): mixed
    {
        // Get user's current selected theme.
        $currentTheme = User::current()?->getCurrentThemeKey();

        // If current theme is empty then fall back to the config default_theme.
        if(empty($currentTheme)) {
            $currentTheme = config('wr-laravel-administration.default_theme');
        }

        // Return either the entire array or the specific key dot notation value of the current theme.
        return static::getThemeData($currentTheme, $keyDotNotation);
    }

    /**
     * Get the data of the given theme from config, either the entire array or key dot notation within it.
     * @param string $themeKey The key of the theme to retrieve data from.
     * @param string|null $keyDotNotation The dot notation key to retrieve specific data from the theme.
     * @return mixed The data or found value within the given theme.
     */
    public static function getThemeData(string $themeKey, ?string $keyDotNotation = null): mixed
    {
        // Retrieve the themes array from the config
        $themes = config('wr-laravel-administration.themes');

        // Check if the theme key exists in the themes array
        if(!array_key_exists($themeKey, $themes)) {
            // If the theme key does not exist, resort to the default theme
            $themeKey = config('wr-laravel-administration.default_theme');
        }

        // Check if a specific key dot notation is provided
        if(!empty($keyDotNotation)) {
            // If the key dot notation exists does not exist in the current theme, throw error
            throw_if(
                !data_get($themes[$themeKey], $keyDotNotation),
                new \Exception("The key dot notation '$keyDotNotation' does not exist in the current theme '$themeKey'.")
            );

            // Return the value of the key dot notation in the current theme
            return data_get($themes[$themeKey], $keyDotNotation);
        }

        // Otherwise return the entire array of the current theme
        return $themes[$themeKey];
    }

    /**
     * Get the view path for a given view.
     *
     * @param string $view The name of the view.
     * @param bool $includeTheme Whether the view is inside the theme folder.
     * @return string|bool The fully qualified view path, or false if the view does not exist.
     */
    public static function getViewPath(string $view, bool $includeTheme = true): string|false
    {
        if($includeTheme)
        {
            $currentTheme = WRLAHelper::getCurrentThemeData('path');

            // First check if the user has added their own theme within their project's /resources/vendor/views/wrla/themes folder
            if(view()->exists('vendor.wrla.themes.' . $currentTheme . '.' . $view)) {
                return 'vendor.wrla.themes.' . $currentTheme . '.' . $view;
            }
            // If not then check if theme exists within the package
            else if(view()->exists('wr-laravel-administration::themes.' . $currentTheme . '.' . $view)) {
                return 'wr-laravel-administration::themes.' . $currentTheme . '.' . $view;
            }
            // Else check if view exists in views directory without any theme
            else if(view()->exists('wr-laravel-administration::' . $view)) {
                return 'wr-laravel-administration::' . $view;
            }
            // Else return false
            else {
                return false;
                //dd("The view '$view' does not exist within the current theme. Stack trace: ", debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2));
            }
        }
        else
        {
            // First check if the user has added their own view within their project's /resources/views/wrla folder
            if(view()->exists('vendor.wrla.' . $view)) {
                return 'vendor.wrla.' . $view;
            }
            // If not then check if view exists within the package
            else if(view()->exists('wr-laravel-administration::' . $view)) {
                return 'wr-laravel-administration::' . $view;
            }
            // Else return false
            else {
                return false;
                //dd("The view '$view' does not exist within the package. Stack trace: ", debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2));
            }
        }
    }

    /**
     * Uses browsable column relationship syntax. Relationship key strings use the format: 'relationship.remote_column'.
     *
     * @string $relationshipKeyString The relationship string to check against.
     * @return bool
     */
    public static function isBrowseColumnRelationship(string $relationshipKeyString): bool
    {
        return static::parseBrowseColumnRelationship($relationshipKeyString) !== false;
    }

    /**
     * Interpret browsable column relationship string
     *
     * @param string $relationshipKeyString The relationship string to interpret.
     * @return array|false The interpreted relationship array.
     */
    public static function parseBrowseColumnRelationship(string $relationshipKeyString): array|false
    {
        // If does not contain :: then return false
        if(strpos($relationshipKeyString, '.') === false) {
            return false;
        }

        // Explode the relationship key string
        return (array)explode('.', $relationshipKeyString);
    }

    /**
     * Get navigation items from the config and return them as an array of NavigationItem objects.
     * @return array The array of NavigationItem objects.
     */
    public static function getNavigationItems(): array
    {
        // Get the navigation items from the config
        $navigationItems = NavigationItem::$navigationItems;

        // Flatten navigation items, this is so that you can "inject" a group of navigation items within the array or child array
        $navigationItems = static::flattenNavigationItems($navigationItems);

        return $navigationItems;
    }

    /**
     * Recursivly loop throught the array and navigationItem->children, if ever come across a standard array
     * within then "flaten" it to the array that it was within, in other words, remove the array but keep the items where they were.
     * @param array $navigationItems The array of navigation items.
     * @return array The array of NavigationItem objects.
     */
    public static function flattenNavigationItems(array $navigationItems): array
    {
        $flattenedNavigationItems = [];

        foreach($navigationItems as $navigationItem) {
            // If the navigation item is an instance of NavigationItem then add it to the flattened array
            if($navigationItem instanceof NavigationItem) {
                $flattenedNavigationItems[] = $navigationItem;
            }
            // If the navigation item is an array then loop through it and add the items to the flattened array
            else if(is_array($navigationItem)) {
                $flattenedNavigationItems = array_merge($flattenedNavigationItems, static::flattenNavigationItems($navigationItem));
            }
        }

        // Then search through the children of the navigation items flattern those arrays as well
        foreach($flattenedNavigationItems as $navigationItem) {
            $navigationItem->children = static::flattenNavigationItems($navigationItem->children);
        }

        return $flattenedNavigationItems;
    }

    /**
     * Build rate limiter from rate_limiting configuration array item.
     * @param array $rateLimitConfigItem The rate limiting configuration array.
     * @return void
     */
    public static function buildRateLimiter(Request $request, string $throttleAlias, array $rateLimitConfigItem): void
    {
        // Get the rate limiting configuration
        $rateLimitBy = static::rateLimiterStringByEvaluator($request, $rateLimitConfigItem['rule']);
        $rateLimitMaxAttempts = $rateLimitConfigItem['max_attempts'];
        $rateLimitDecayMinutes = $rateLimitConfigItem['decay_minutes'];
        $rateLimitMessage = str_replace(':decay_minutes', $rateLimitDecayMinutes, $rateLimitConfigItem['message']);

        // Build the rate limiter
        RateLimiter::for($throttleAlias, function (Request $request) use ($rateLimitBy, $rateLimitMaxAttempts, $rateLimitDecayMinutes, $rateLimitMessage) {
            return Limit::perMinutes($rateLimitDecayMinutes, $rateLimitMaxAttempts)->by($rateLimitBy)->response(function() use ($rateLimitMessage) {
                return redirect()->route('wrla.login')->with('error', $rateLimitMessage);
            });
        });
    }

    /**
     * Rate limiter by evaluator
     * @param Request $request The request object.
     * @param string $rateLimitBy The rate limit by string.
     * @return string The compiled rate limit by string.
     */
    public static function rateLimiterStringByEvaluator(Request $request, string $rateLimitBy): string
    {
        $rateLimitByArray = explode(' ', $rateLimitBy);
        $rateLimitByCompiled = '';

        foreach($rateLimitByArray as $rateLimitByItem) {
            $rateLimitByItem = trim($rateLimitByItem);

            // If begins with input: then check the request input
            if(strpos($rateLimitByItem, 'input:') === 0) {
                $rateLimitByCompiled .= $request->input(substr($rateLimitByItem, 6));
            }
            // If is ip then check the request ip
            else if($rateLimitByItem === 'ip') {
                $rateLimitByCompiled .= $request->ip();
            }
        }

        return $rateLimitByCompiled;
    }

    /**
     * Find all classes that extend ManageableModel and register them within ManageableModel::$manageableModels.
     * @return void
     */
    public static function registerManageableModels(): void
    {
        // If app_path('WRLA') does not exist, return
        if(!File::isDirectory(app_path('WRLA'))) {
            return;
        }

        // Rather than looking at the declared classes, we now look at the files within the app_path('WRLA') directory (recursively)
        $manageableModels = [];
        $files = File::allFiles(app_path('WRLA'));
        foreach($files as $file) {
            $class = 'App\\WRLA\\' . $file->getBasename('.php');
            if(is_subclass_of($class, 'WebRegulate\LaravelAdministration\Classes\ManageableModel')) {
                $manageableModels[] = $class;
            }
        }

        // Loop through each class and register it
        foreach($manageableModels as $manageableModelClass) {
            $manageableModelClass::register();
        }

        // dd(self::$globalManageableModelData);
    }
    
    /**
     * Is the current route the given route name, and has the given parameters.
     *
     * @param string $routeName The route name to check.
     * @param array $parameters The parameters to check.
     * @return bool
     */
    public static function isCurrentRouteWithParameters(?string $routeName, ?array $parameters): bool
    {        
        // First check if name is true
        if(request()->route()->getName() !== $routeName) {
            return false;
        }

        // If parameters empty or null, return true
        if(empty($parameters)) {
            return true;
        }

        // Check if all of the parameters passed are in the route parameters
        $routeParameters = request()->route()->parameters();
        foreach($parameters as $key => $value) {
            if(!array_key_exists($key, $routeParameters) || $routeParameters[$key] !== $value) {
                return false;
            }
        }

        // If all checks pass, return true
        return true;
    }

    /**
     * Is the current route the given NavigationItem.
     *
     * @param NavigationItem $navigationItem The navigation item to check.
     * @return bool
     */
    public static function isNavItemCurrentRoute(NavigationItem $navigationItem): bool
    {
        return static::isCurrentRouteWithParameters($navigationItem->route, $navigationItem->routeData);
    }

    /**
     * generate a file from a stub and replace variables.
     *
     * @param string $stub The stub to replace variables in.
     * @param array $variables The variables to replace in the stub.
     * @param string $destination The destination path to save the final file.
     * @return string|false The path of the file created (minus the base path) or false if the file already exists and $forceOverwrite is false.
     */
    public static function generateFileFromStub(string $stub, array $variables, string $destination, bool $forceOverwrite = false): string|false
    {
        // If $forceOverwrite is false and the file already exists, return false
        if(!$forceOverwrite && File::exists($destination)) {
            return false;
        }

        // Get the stub
        $stub = File::get(__DIR__ . '/../stubs/' . $stub);

        // Replace the stub variables
        foreach ($variables as $key => $value) {
            $stub = str_replace($key, $value, $stub);
        }

        // If directory does not exist, create it
        $directory = dirname($destination);
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true, true);
        }

        // Create the file
        File::put($destination, $stub);

        return WRLAHelper::removeBasePath($destination);
    }

    /**
     * Copy file from location to destination.
     *
     * @param string $location The location of the file to copy.
     * @param string $destination The destination path to save the final file.
     * @return string|false The path of the file created (minus the base path) or false if the file already exists and $forceOverwrite is false.
     */
    public static function copyFile(string $location, string $destination, bool $forceOverwrite = false): string|false
    {
        // If $forceOverwrite is false and the file already exists, return false
        if(!$forceOverwrite && File::exists($destination)) {
            return false;
        }

        // If directory does not exist, create it
        $directory = dirname($destination);
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true, true);
        }

        // Copy the file
        File::copy($location, $destination);

        return WRLAHelper::removeBasePath($destination);
    }

    /**
     * Replace backslashes with forward slashes.
     *
     * @param string $string The string to replace backslashes with forward slashes.
     * @return string The string with backslashes replaced with forward slashes.
     */
    public static function forwardSlashPath(string $string): string
    {
        $string = addslashes($string);
        return str_replace('//', '/', str_replace('\\', '/', $string));
    }

    /**
     * Remove base path
     *
     * @param string $string The string to remove the base path from.
     * @return string The string with the base path removed.
     */
    public static function removeBasePath(string $path): string
    {
        return WRLAHelper::forwardSlashPath(str_replace(base_path(), '', $path));
    }

    /**
     * Get all array keys from a multidimentional array recursively with a divider, dot notation by default.
     *
     * @param array $array
     * @param string $divider
     * @return array
     */
    static public function arrayKeysRecursive(array $array, $divider='.'){
        $arrayKeys = [];
        foreach( $array as $key=>$value ){
            if( is_array($value) ){
                $rekusiveKeys = static::arrayKeysRecursive($value, $divider);
                foreach( $rekusiveKeys as $rekursiveKey ){
                    $arrayKeys[] = $key.$divider.$rekursiveKey;
                }
            }else{
                $arrayKeys[] = $key;
            }
        }
        return $arrayKeys;
    }

    /**
     * Json pretty print
     *
     * @param string $json The json string to pretty print.
     * @return string The pretty printed json string.
     */
    public static function jsonPrettyPrint(string $json): string
    {
        $jsonArrary = json_decode($json, true);
        return json_encode($jsonArrary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Json format validation
     *
     * @param string $json The json string to validate.
     * @param array $valueDefinitions The value definitions to validate against, use dot notation for nested arrays.
     * @param bool $allRequired Whether all keys are required.
     * @return true|string True if the json is valid, otherwise the error message.
     */
    public static function jsonFormatValidation(string $json, array $valueDefinitions, bool $allRequired = true): true|string
    {
        $jsonData = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return json_last_error_msg();
        }

        // Merge error messages
        $mergeErrorMessages = [];

        // Setup data array of nested.key => values,... to validate
        $validateDataArray = [];
        foreach($valueDefinitions as $key => $valueDefinition) {
            $valueDefinitions[$key] = 'required|' . $valueDefinition;
            $validateDataArray[$key] = $jsonData[$key] ?? null;

            // If value definition has an in: then extract it and set the custom message
            preg_match('/in:([a-zA-Z0-9,]+)/', $valueDefinition, $matches);
            if(!empty($matches)) {
                $inValues = explode(',', $matches[1]);

                // If only one value
                if(count($inValues) === 1) {
                    $mergeErrorMessages[$key] = "The $key field must be set to `<b>{$inValues[0]}</b>.`";
                } else {
                    $mergeErrorMessages[$key] = "The $key field must set to one of the following: `<b>".implode('</b>`, `<b>', $inValues)."</b>`.";
                }
            }
        }

        //dd($validateDataArray, $mergeErrorMessages);

        $validator = Validator::make($validateDataArray, $valueDefinitions, $mergeErrorMessages);
        if ($validator->fails()) {
            // Merge error messages with validator messages
            $mergedErrorMessages = array_merge($mergeErrorMessages, $validator->errors()->messages());

            // Build an array of modified messages
            $modifiedMessages = [];
            foreach($mergedErrorMessages as $key => $message) {
                // If we can find the wording "$key field", replace it with "<b>$key</b> key"
                $modifiedMessages[$key] = str_replace($key . ' field', '<b>' . $key . '</b> key', $message[0]);
            }

            return implode(', ', $modifiedMessages);
        }

        return true;
    }

    /**
     * Get wrla column json notation parts from a key.
     *
     * @param string $key The key to get the json notation parts from.
     */
    public static function parseJsonNotation(string $key): array
    {
        $parts = explode('->', $key);
        $column = $parts[0];
        $dotNotation = implode('.', array_slice($parts, 1));

        return [$column, $dotNotation];
    }

    /**
     * Query builder join callback function
     *
     * @param Builder $query The query builder.
     * @param string $joinTable The table to join.
     * @param string $tableAndColumn Local table and join column, eg. 'base_table.relationship_column_id'
     * @param ?array $selectColumns Specify extra relationship columns to select, 'id' will always be selected on the relationship table.
     * @param ?string $useAlias
     * @return Builder The query builder with the added join.
     */
    public static function queryBuilderJoin(Builder $query, string $joinTable, string $tableAndColumn, ?array $selectColumns = null, ?string $useAlias = null): mixed
    {
        // NOTE: MAYBE AT SOME POINT WE CAN ALSO SPLIT $joinTable to allow user to specify something other than $joinTable.id
        // Split table and column
        $tableColumnSplit = explode('.', $tableAndColumn);

        // If $tableAndColumn is not using 'table.column' format then throw exception
        if(count($tableColumnSplit) != 2) {
            throw new \Exception('queryBuilderJoin $tableAndColumn parameter must be in the format of "table.column". '.$tableAndColumn.' passed.');
        }

        // Plug the select columns in as selectRaw's, this way we can be more specific with the columns we want to select
        if($selectColumns != null && count($selectColumns) > 0) {
            $query->selectRaw(implode(', ', $selectColumns));
        }

        // Run join
        $query->leftJoin(
            DB::raw($joinTable.(empty($useAlias) ? '' : " $useAlias")),
            $tableAndColumn,
            '=',
            empty($useAlias) ? "$joinTable.id" : "$useAlias.id"
        );

        return $query;
    }

    /**
     * Query builder multi join callback function
     *
     * @param Builder $query The query builder.
     * @param array $joinTables The tables to join.
     * @param array $tableAndColumns Local table and join columns, eg. ['base_table.relationship_column_id', ...]
     * @param ?array $selectColumns Specify extra relationship columns to select, 'id' will always be selected on the relationship table.
     * @param ?string $useAlias
     * @return Builder The query builder with the added join.
     */
    public static function queryBuilderMultiJoin(Builder $query, array $joinTables, array $tableAndColumns, ?array $selectColumns = null, ?string $useAlias = null): mixed
    {
        // Note that select columns must happen after all joins

        // Loop through each join table and column
        foreach($joinTables as $key => $joinTable) {
            $tableAndColumn = $tableAndColumns[$key];

            // Run join
            $query = static::queryBuilderJoin($query, $joinTable, $tableAndColumn, [], $useAlias);
        }

        // Plug the select columns in as selectRaw's, this way we can be more specific with the columns we want to select
        if($selectColumns != null && count($selectColumns) > 0) {
            $query->selectRaw(implode(', ', $selectColumns));
        }

        return $query;
    }

    /**
     * Is impersonating user
     *
     * @return bool
     */
    public static function isImpersonatingUser(): bool
    {
        return session()->has('wrla_impersonating_user');
    }

    /**
     * Get original user while impersonating
     *
     * @return User|null
     */
    public static function getImpersonatingOriginalUser(): ?User
    {
        return User::find(session('wrla_impersonating_user'));
    }

    /**
     * Remove a rule from a validation string
     *
     * @param string|array $rule The rule/s to remove.
     * @param string $validation The validation string to remove the rule from.
     * @param bool $useRegex Whether to use regex to remove the rule.
     * @return string The validation string with the rule removed.
     */
    public static function removeRuleFromValidationString(string|array $rule, string $validationString, bool $useRegex = false): string
    {
        if(is_string($rule)) {
            $rule = [$rule];
        }

        foreach($rule as $r) {
            if(!$useRegex) {
                // Simply replace the rule with an empty string
                $validationString = str_replace($rule, '', $validationString);
            } else {
                // The \b here is a word boundary, so it will only match the word 'required' and not 'required_if' etc.
                $validationString = preg_replace('/\b' . $r . '\b/', '', $validationString);
            }
        }

        // Finally clean up the validation string by removing unnecessary pipes
        return str_replace('||', '|', rtrim(ltrim($validationString, '|'), '|'));
    }

    /**
     * Register livewire route (For use in WRLASettings::buildCustomRoutes)
     *
     * @param string $routeName The route name to create. Note the route name will default to "wrla.$routeName".
     * @param string $livewireComponentAlias The livewire component alias to use.
     * @param string $livewireComponentClass The livewire component class to use.
     * @param array $livewireComponentData The livewire component data to use.
     * @param string $title The title to use.
     * @return \Illuminate\Routing\Route The route created.
     */
    public static function registerLivewireRoute(string $routeName, string $livewireComponentAlias, string $livewireComponentClass, array $livewireComponentData, string $title): \Illuminate\Routing\Route
    {
        // Register the livewire component
        Livewire::component($livewireComponentAlias, $livewireComponentClass);

        // Build the route
        $route = Route::get($routeName, function() use ($livewireComponentAlias, $livewireComponentClass, $livewireComponentData, $title) {
            return view(WRLAHelper::getViewPath('livewire-content'), [
                'title' => $title,
                'livewireComponentAlias' => $livewireComponentAlias,
                'livewireComponentData' => $livewireComponentData
            ]);
        });

        // Set default name
        $route->name("wrla.$routeName");

        return $route;
    }

    /**
     * Is current route allowed (Based on NavigationItem show and enabled conditions)
     * 
     * @return bool|string
     */
    public static function isCurrentRouteAllowed(): bool|string
    {
        $navigationItems = static::getNavigationItems();

        foreach ($navigationItems as $navigationItem) {
            $result = static::isCurrentRouteAllowedForItem($navigationItem);
            if ($result !== true) {
                return $result;
            }
        }

        return true;
    }

    /**
     * Determines if the current route is allowed for the given navigation item and it's children recursively.
     *
     * @param object $navigationItem The navigation item to check.
     * @return bool|string Returns true, false, or string error message.
     */
    private static function isCurrentRouteAllowedForItem($navigationItem): bool|string
    {
        if (static::isNavItemCurrentRoute($navigationItem)) {
            if ($navigationItem->checkShowCondition() === false) {
                return false;
            }

            $checkEnabledCondition = $navigationItem->checkEnabledCondition();
            if ($checkEnabledCondition !== true) {
                return $checkEnabledCondition;
            }
        }

        if (!empty($navigationItem->children) && is_array($navigationItem->children)) {
            foreach ($navigationItem->children as $childItem) {
                $result = static::isCurrentRouteAllowedForItem($childItem);
                if ($result !== true) {
                    return $result;
                }
            }
        }

        return true;
    }

    /**
     * Check if table exists in database
     *
     * @param mixed $baseModelInstance The base model instance, we need this to get the connection name this model uses
     * @param string $table The table to check if exists.
     * @return bool
     */
    public static function tableExists(mixed $baseModelInstance, string $table): bool
    {
        return Schema::connection($baseModelInstance->getConnectionName())->hasTable($table);
    }

    /**
     * Log to WRLA error channel, automatically adds 'user' => user->id if available, shows as 'x' if no user.
     *
     * @param string $message The message to log.
     * @param array $context The context to log.
     */
    public static function logError(string $message, array $context = []): void
    {
        $data = array_merge([
            'user' => User::current()?->id ?? 'x',
        ], $context);

        Log::channel('wrla-error')->error($message, $data);
    }

    /**
     * Log to WRLA info channel, automatically adds 'user' => user->id if available, shows as 'x' if no user.
     *
     * @param string $message The message to log.
     * @param array $context The context to log.
     */
    public static function logInfo(string $message, array $context = []): void
    {
        $data = array_merge([
            'user' => User::current()?->id ?? 'x',
        ], $context);

        Log::channel('wrla-info')->info($message, $data);
    }

    /**
     * Evaulate arguments as string and define as array.
     * For example the expression "'val1', ['key1' => 'val1'... etc])" would be evaluated as:
     * $args = ['val1', ['key1' => 'val1'... etc]]
     *
     * @param string $expression The expression to evaluate.
     * @return array The evaluated arguments.
     */
    public static function evaluateArguments(string $expression): array
    {
        $args = [];
        eval("\$args = [$expression];");
        return $args;
    }
}
