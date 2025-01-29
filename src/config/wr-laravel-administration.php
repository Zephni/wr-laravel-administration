<?php

return [

    // Base URL for the administration panel, e.g. 'wr-admin' will result in 'http://example.com/wr-admin'
    'base_url' => 'wr-admin',

    // Model definitions
    'models' => [
        'user' => \App\Models\User::class,
        'wrla_user_data' => \App\Models\UserData::class,
    ],

    // How the page title should be displayed
    'title_template' => '{page_title} - WebRegulate Admin',

    // Company logo
    'logo' => [
        'light' => 'vendor/wr-laravel-administration/images/logo-light.svg',
        'dark' => 'vendor/wr-laravel-administration/images/logo-dark.svg',
    ],

    // Colors - These add/override tailwind's available colors in the layouts
    'colors' => [
        // Use this amazing tailwind color generator: https://uicolors.app/create to generate your color palette
        'primary' => [
            '50'  => '#eefffb',
            '100' => '#c6fff3',
            '200' => '#8effe9',
            '300' => '#4dfbdc',
            '400' => '#19e8ca',
            '500' => '#00bfa6',
            '600' => '#00a493',
            '700' => '#028376',
            '800' => '#08675f',
            '900' => '#0c554e',
            '950' => '#003432',
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
            '50'  => '#f8fafc',
            '100' => '#f1f5f9',
            '200' => '#e2e8f0',
            '300' => '#cbd5e1',
            '400' => '#94a3b8',
            '500' => '#64748b',
            '550' => '#56657A',
            '600' => '#475569',
            '700' => '#334155',
            '725' => '#303d51',
            '750' => '#2a364a',
            '800' => '#1e293b',
            '850' => '#161E2E',
            '900' => '#0f172a',
            '950' => '#020617',
        ],
    ],

    // Common CSS - Be careful that this does not break the layout as this is injected into the head of the layout
    'common_css' => <<<CSS
        /* Add your custom / override CSS here */
    CSS,

    // Wysiwyg CSS - Be careful that this does not break the layout as this is injected into the wysiwyg editor
    'wysiwyg_css' => <<<CSS
        /* Add your custom / override CSS here */
    CSS,

    // Default theme (key from the 'themes' array below)
    'default_theme' => 'default',

    // Themes
    'themes' => [
        // Default
        'default' => [
            'name' => 'Default',        // Name of the theme displayed to user (if multiple themes are available)
            'path' => 'default',        // Path to the theme folder in the 'resources/views/themes/?' directory
            'default_mode' => 'light',  // Default mode for the theme (dark or light)
            'no_image_src' => '/vendor/wr-laravel-administration/images/no-image-transparent.svg',
        ]
    ],

    // User avatar, override the default user image path with a callback function that passes the \App\Models\User model as an argument
    'user_avatar' => null,
    // 'user_avatar' => function(\App\Models\User $user) {
    //     return $user->profile_image;
    // },

    // Dashboard display notifications for users / groups, use '@self' for the user's own notifications
    'dashboard' => [
        'notifications' => [
            'user_groups' => ['@self', 'admin'],
        ],
    ],

    // Logs
    'logs' => [
        'max_characters' => 100000,
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
