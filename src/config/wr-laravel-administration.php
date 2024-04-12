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

    // Rate limiting for wrla. routes
    // Note: each key is bound to middleware 'throttle:route_name' in routes automatically (Within WRLAServicesProvider.php)
    'rate_limiting' => [
        'login.post' => [
            'rule' => 'input:email ip',
            'max_attempts' => 5,
            'decay_minutes' => 10,
            'message' => 'Too many login requests. Please try again in :decay_minutes minutes.',
        ],
        'forgot-password.post' => [
            'rule' => 'input:email ip',
            'max_attempts' => 2,
            'decay_minutes' => 10,
            'message' => 'Too many forgot password requests. Please try again in :decay_minutes minutes.',
        ],
        'reset-password.post' => [
            'rule' => 'input:email ip',
            'max_attempts' => 2,
            'decay_minutes' => 10,
            'message' => 'Too many reset password requests. Please try again in :decay_minutes minutes.',
        ],
    ],
];
