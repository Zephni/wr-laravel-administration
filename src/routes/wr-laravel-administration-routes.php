<?php

use Illuminate\Support\Facades\Route;
use WebRegulate\LaravelAdministration\Http\Controllers\WRLAAuthController;
use WebRegulate\LaravelAdministration\Http\Controllers\WRLAAdminController;

Route::group(['namespace' => 'WebRegulate\LaravelAdministration\Http\Controllers'], function () {

    Route::prefix(config('wr-laravel-administration.base_url', 'wr-admin'))->name('wrla.')->group(function () {

        // Auth controller
        Route::group(['controller' => WRLAAuthController::class, 'middleware' => ['is_not_admin']], function () {
            Route::get('login', 'login')->name('login');
            Route::post('login', 'loginPost')->name('login.post');
        });

        // Administration controller
        Route::group(['controller' => WRLAAdminController::class, 'middleware' => ['auth', 'is_admin']], function () {
            // Dashboard
            Route::get('', function () { return redirect()->route('wrla.dashboard'); });
            Route::get('dashboard', 'index')->name('dashboard');

            // Logout
            Route::get('logout', 'logout')->name('logout');
        });

    });

});
