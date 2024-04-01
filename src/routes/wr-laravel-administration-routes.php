<?php

use Illuminate\Support\Facades\Route;
use Zephni\WRLaravelAdministration\Http\Controllers\WRLaravelAdministrationAuthController;

Route::group(['namespace' => 'Zephni\WRLaravelAdministration\Http\Controllers'], function () {

    Route::prefix(config('wr-laravel-administration.base_url', 'wr-admin'))->name('wrla.')->group(function () {

        // Auth controller
        Route::group(['controller' => WRLaravelAdministrationAuthController::class], function () {
            Route::get('login', 'login')->name('login');
        });

    });

});
