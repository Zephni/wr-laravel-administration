<?php

namespace WebRegulate\LaravelAdministration\Classes;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use WebRegulate\LaravelAdministration\Models\User;
use WebRegulate\LaravelAdministration\Classes\NavigationItems\NavigationItem;

class WRLAHelper
{
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
        return self::getThemeData($currentTheme, $keyDotNotation);
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
            // If the key dot notation exists does not exist in the current theme, return an error message
            if(!data_get($themes[$themeKey], $keyDotNotation)) {
                // If the key dot notation does not exist, return an error message
                return dd("The key '$keyDotNotation' does not exist within the current theme.");
            }

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

            // First check if the user has added their own theme within their project's /resources/views/wrla/themes folder
            if(view()->exists('wrla.themes.' . $currentTheme . '.' . $view)) {
                return 'wrla.themes.' . $currentTheme . '.' . $view;
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
            if(view()->exists('wrla.' . $view)) {
                return 'wrla.' . $view;
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
     * Get navigation items from the config and return them as an array of NavigationItem objects.
     * @return array The array of NavigationItem objects.
     */
    public static function getNavigationItems(): array
    {
        // Get the navigation items from the config
        $navigationItems = NavigationItem::$navigationItems;

        // Flatten navigation items, this is so that you can "inject" a group of navigation items within the array or child array
        $navigationItems = self::flattenNavigationItems($navigationItems);

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
                $flattenedNavigationItems = array_merge($flattenedNavigationItems, self::flattenNavigationItems($navigationItem));
            }
        }

        // Then search through the children of the navigation items flattern those arrays as well
        foreach($flattenedNavigationItems as $navigationItem) {
            $navigationItem->children = self::flattenNavigationItems($navigationItem->children);
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
        $rateLimitBy = self::rateLimiterStringByEvaluator($request, $rateLimitConfigItem['rule']);
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
        // Get all classes that extend ManageableModel
        // $manageableModels = array_filter(get_declared_classes(), fn($class) => is_subclass_of($class, 'WebRegulate\LaravelAdministration\Classes\ManageableModel'));

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
        foreach($manageableModels as $manageableModel) {
            $manageableModel::register();
        }
    }

    /**
     * Route is name, and has the specified parameters
     * 
     * @param string $routeName The route name to check.
     * @param array $parameters The parameters to check.
     * @return bool
     */
    public static function isCurrentRouteWithParameters(string $routeName, array $parameters): bool
    {
        // First check if name is true
        if(request()->route()->getName() !== $routeName) {
            return false;
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
                $rekusiveKeys = self::arrayKeysRecursive($value, $divider);
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
     * Check if table exists in database
     * 
     * @param string $table The table to check if exists.
     * @return bool
     */
    public static function tableExists(string $table): bool
    {
        return Schema::hasTable($table);
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
