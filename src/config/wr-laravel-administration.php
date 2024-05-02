<?php

use WebRegulate\LaravelAdministration\Classes\NavigationItems\NavigationItem;
use WebRegulate\LaravelAdministration\Classes\NavigationItems\NavigationItemsAllManageableModels;
use WebRegulate\LaravelAdministration\Classes\NavigationItems\NavigationItemManageableModel;

return [

    // Base URL for the administration panel, e.g. 'wr-admin' will result in 'http://example.com/wr-admin'
    'base_url' => 'wr-admin',

    // Company logo
    'logo' => [
        'light' => 'vendor/wr-laravel-administration/images/logo-light.svg',
        'dark' => 'vendor/wr-laravel-administration/images/logo-dark.svg',
    ],

    // Colors - These add/override tailwind's available colors in the layouts
    'colors' => [
        'primary' => [
            '500' => '#00BFA6',
            '600' => '#00A88F',
        ],
        'notes' => [
            '200' => '#e2f0fb',
            '300' => '#c8dae9',
            '400' => '#a3b9d1',
            '600' => '#417ece',
            '700' => '#2e5f9e',
            '800' => '#162945',
            '900' => '#1c3f6e',
        ],
        'slate' => [
            '850' => '#161E2E',
            '950' => '#0D1016',
        ],
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

    // Navigation items
    'navigation' => [
        // Dashboard
        new NavigationItem('wrla.dashboard', [], 'Dashboard', 'fa fa-tachometer-alt'),

        // Import all manageable models as nav items - Optionally use example below to add individually
        NavigationItemsAllManageableModels::import(),

        // Example manageable model
        // new NavigationItemManageableModel(App\WRLA\YourModel::class),

        // Manage account
        new NavigationItem('wrla.manage-account', [], 'Manage Account', 'fa fa-user-circle'),
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
