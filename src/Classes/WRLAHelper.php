<?php

namespace WebRegulate\LaravelAdministration\Classes;

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
        $themes = config('wr-laravel-administration.themes');
        // TODO: NEED TO PASS USER CHOSEN THEME IF SET
        $currentTheme = config('wr-laravel-administration.default_theme');

        if(!array_key_exists($currentTheme, $themes)) {
            return dd("The current theme key '$currentTheme' is not set in the wr-laravel-administration.themes config.");
        }

        if(!empty($keyDotNotation)) {
            if(!data_get($themes[$currentTheme], $keyDotNotation)) {
                return dd("The key '$keyDotNotation' does not exist in the current theme.");
            }

            return data_get($themes[$currentTheme], $keyDotNotation);
        }

        return $themes[$currentTheme];
    }

    /**
     * Get the view path for a given view.
     *
     * @param string $view The name of the view.
     * @param bool $includeTheme Whether the view is inside the theme folder.
     * @return string The fully qualified view path.
     */
    public static function getViewPath(string $view, bool $includeTheme = true): string
    {
        if($includeTheme)
        {
            $currentTheme = WRLAHelper::getCurrentThemeData('path');
            return 'wr-laravel-administration::themes.' . $currentTheme . '.' . $view;
        }
        else
        {
            return 'wr-laravel-administration::' . $view;
        }
    }
}
