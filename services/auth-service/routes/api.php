<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);

    Route::post('/login', [AuthController::class, 'login']);

    Route::post('/refresh', [AuthController::class, 'refresh']);

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post(
        '/forgot-password',
        [AuthController::class, 'forgotPassword']
    );

    Route::post(
        '/reset-password',
        [AuthController::class, 'resetPassword']
    );

    Route::middleware('jwt')->group(function () {
        Route::post(
            '/logout-all',
            [AuthController::class, 'logoutAll']
        );
Route::post(
    '/change-password',
    [AuthController::class, 'changePassword']
);
Route::post(
    '/avatar',
    [AuthController::class, 'uploadAvatar']
);
    });
});