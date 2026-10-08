<?php

use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RequestController;
use Illuminate\Support\Facades\Route;

/**
 * ============================================================
 * MODULE 1 - TAI KHOAN VA QUYEN TRUY CAP
 * ============================================================
 */

/**
 * Dang ky, dang nhap, refresh token va khoi phuc mat khau.
 * Khong can JWT.
 */
Route::prefix('auth')->group(function () {
    Route::post('/register', [
        AuthController::class,
        'register',
    ]);

    Route::post('/login', [
        AuthController::class,
        'login',
    ]);

    Route::post('/refresh', [
        AuthController::class,
        'refresh',
    ]);

    Route::post('/forgot-password', [
        PasswordResetController::class,
        'forgot',
    ]);

    Route::post('/reset-password', [
        PasswordResetController::class,
        'reset',
    ]);
});

/**
 * Dang xuat tat ca thiet bi.
 * Bat buoc JWT hop le.
 */
Route::middleware('jwt.auth')
    ->prefix('auth')
    ->group(function () {
        Route::post('/logout-all', [
            AuthController::class,
            'logoutAll',
        ]);
    });

/**
 * ============================================================
 * HO SO CA NHAN
 * ============================================================
 *
 * PUT /api/profile/password duoc phep khi
 * must_change_password = true.
 *
 * Cac route con lai bat buoc da doi mat khau.
 */
Route::middleware('jwt.auth')
    ->prefix('profile')
    ->group(function () {
        Route::put('/password', [
            ProfileController::class,
            'updatePassword',
        ]);

        Route::middleware('password.changed')
            ->group(function () {
                Route::get('/login-history', [
                    ProfileController::class,
                    'loginHistory',
                ]);
                Route::get('/', [
                    ProfileController::class,
                    'show',
                ]);

                Route::put('/', [
                    ProfileController::class,
                    'update',
                ]);

                Route::post('/avatar', [
                    ProfileController::class,
                    'updateAvatar',
                ]);
            });
    });

/**
 * ============================================================
 * QUAN LY TAI KHOAN - ADMIN
 * ============================================================
 */
Route::middleware([
    'jwt.auth',
    'password.changed',
    'role:admin',
])
    ->prefix('users')
    ->group(function () {
        Route::get('/', [
            AdminUserController::class,
            'index',
        ]);

        Route::get('/{user}', [
            AdminUserController::class,
            'show',
        ]);

        Route::put('/{user}/role', [
            AdminUserController::class,
            'updateRole',
        ]);

        Route::put('/{user}/status', [
            AdminUserController::class,
            'updateStatus',
        ]);
    });

/**
 * ============================================================
 * MODULE REQUEST / HO TRO SINH VIEN
 * ============================================================
 *
 * Su dung Sanctum de xac thuc tai khoan.
 */
Route::middleware('auth:sanctum')
    ->prefix('requests')
    ->group(function () {
        /**
         * Danh sach request.
         */
        Route::get('/', [
            RequestController::class,
            'index',
        ]);

        /**
         * Tao request.
         */
        Route::post('/', [
            RequestController::class,
            'store',
        ]);

        /**
         * Xem chi tiet request.
         */
        Route::get('/{supportRequest}', [
            RequestController::class,
            'show',
        ]);

        /**
         * Cap nhat trang thai.
         */
        Route::put('/{supportRequest}/status', [
            RequestController::class,
            'updateStatus',
        ]);

        /**
         * Phan cong request.
         */
        Route::put('/{supportRequest}/assign', [
            RequestController::class,
            'assign',
        ]);

        /**
         * Huy request.
         */
        Route::put('/{supportRequest}/cancel', [
            RequestController::class,
            'cancel',
        ]);

        /**
         * Lich su request.
         */
        Route::get('/{supportRequest}/history', [
            RequestController::class,
            'history',
        ]);
    });
