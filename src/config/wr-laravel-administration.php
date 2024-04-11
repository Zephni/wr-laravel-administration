<?php

return [

    // Base URL for the administration panel, e.g. 'wr-admin' will result in 'http://example.com/wr-admin'
    'base_url' => 'wr-admin',

    // Company logo
    'logo' => [
        'light' => 'vendor/wr-laravel-administration/images/logo-light.svg',
        'dark' => 'vendor/wr-laravel-administration/images/logo-dark.svg',
    ],

    // Default theme (key from the 'themes' array below)
    'default_theme' => 'default',

    // Themes
    'themes' => [
        // Default
        'default' => [
            'name' => 'Default',        // Name of the theme displayed to user (if multiple themes are available)
            'path' => 'default',        // Path to the theme folder in the 'resources/views/themes/?' directory
            'default_mode' => 'dark',   // Default mode for the theme (dark or light)
        ]
    ],
];
