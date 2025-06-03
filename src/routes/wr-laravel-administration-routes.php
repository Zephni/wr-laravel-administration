<?php

use Illuminate\Support\Facades\Route;
use WebRegulate\LaravelAdministration\Http\Controllers\WRLAAdminController;
use WebRegulate\LaravelAdministration\Http\Controllers\WRLAAuthController;

Route::group(['namespace' => 'WebRegulate\LaravelAdministration\Http\Controllers'], function (): void {

    // Prefix routes with the base url and name
    Route::prefix(config('wr-laravel-administration.base_url', 'wr-admin'))->name('wrla.')->group(function (): void {

        // Other
        Route::get('to-frontend', fn () => redirect('/'))->name('to-frontend');
        Route::post('upload-wysiwyg-image', [WRLAAdminController::class, 'uploadWysiwygImage'])->name('upload-wysiwyg-image');

        // Auth controller
        Route::group(['controller' => WRLAAuthController::class, 'middleware' => ['wrla_is_not_admin']], function (): void {
            // Login - If wrla_auth_routes_enabled is true
            if (config('wr-laravel-administration.wrla_auth_routes_enabled')) {
                // Base Url if not logged in
                Route::get('', fn () => redirect()->route('wrla.login'));

                // Login
                Route::get('login', 'login')->name('login');
                Route::post('login', 'loginPost')->name('login.post');
            

                // Forgot / Reset password
                Route::get('forgot-password', 'forgotPassword')->name('forgot-password');
                Route::post('forgot-password', 'forgotPasswordPost')->name('forgot-password.post');
                Route::get('reset-password/{email}/{token}', 'resetPassword')->name('reset-password');
                Route::post('reset-password/{token}', 'resetPasswordPost')->name('reset-password.post');
            }
            // If wrla_auth_routes_enabled is false, redirect to the frontend 
            else {
                Route::get('', fn () => redirect('/'))->name('login');
            }
        });

        // Administration controller
        Route::group(['controller' => WRLAAdminController::class, 'middleware' => ['wrla_is_admin']], function (): void {
            // Base Url if logged in
            Route::get('', fn () => redirect()->route('wrla.dashboard'));

            // Dashboard
            Route::get('dashboard', 'index')->name('dashboard');

            // View file manager
            Route::get('file-manager', 'fileManager')->name('file-manager');

            // View logs
            Route::get('logs', 'logs')->name('logs');

            // Manage account
            Route::get('manage-account', 'manageAccount')->name('manage-account');

            // Manageable model routes
            Route::get('browse/{modelUrlAlias}', 'browse')->name('manageable-models.browse');
            Route::get('create/{modelUrlAlias}', 'upsert')->name('manageable-models.create');
            Route::get('edit/{modelUrlAlias}/{id}', 'upsert')->name('manageable-models.edit');
            Route::post('create/{modelUrlAlias}/{modelId?}', 'upsertPost')->name('manageable-models.upsert.post');
            Route::post('edit/{modelUrlAlias}/{modelId?}', 'upsertPost')->name('manageable-models.upsert.post');
        });

        // Impersonate routes
        Route::get('impersonate/login-as/{id}', [WRLAAuthController::class, 'impersonateLoginAs'])->name('impersonate.login-as');
        Route::get('impersonate/switch-back', [WRLAAuthController::class, 'impersonateSwitchBack'])->name('impersonate.switch-back');

        // Logout
        Route::get('logout', [WRLAAuthController::class, 'logout'])->name('logout');
    });
});
