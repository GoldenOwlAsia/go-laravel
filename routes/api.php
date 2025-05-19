<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\SocialAuthController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'api'], function () {
    // Auth routes
    Route::controller(AuthController::class)
    ->prefix('auth')->group(function () {
        Route::post('login', 'login');
        Route::post('register', 'register');
        Route::post('logout', 'logout');
        Route::post('refresh', 'refresh');

        // Auth Social routes
        Route::get('{provider}/redirect', [SocialAuthController::class, 'redirectToProvider']);
        Route::get('{provider}/callback', [SocialAuthController::class, 'handleProviderCallback']);

    });

    // User routes
    Route::middleware('auth:api')->controller(UserController::class)
        ->group(function () {
            Route::get('me', 'me');
        });
});
