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
 * Dang ky va dang nhap.
 * Khong can JWT.
 *
 * POST /api/auth/register
 * POST /api/auth/login
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
 * Ho so cua tai khoan dang dang nhap.
 * Bat buoc JWT hop le.
 *
 * GET /api/profile
 * PUT /api/profile
 * PUT /api/profile/password
 */
Route::middleware('jwt.auth')
    ->prefix('profile')
    ->group(function () {
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

        Route::put('/password', [
            ProfileController::class,
            'updatePassword',
        ]);
    });

/**
 * Quan ly tai khoan.
 * Chi ADMIN duoc truy cap.
 *
 * GET /api/users
 * GET /api/users/{user}
 * PUT /api/users/{user}/role
 * PUT /api/users/{user}/status
 */
Route::middleware(['jwt.auth', 'role:admin'])
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
 * MODULE REQUEST / HỖ TRỢ SINH VIÊN
 * ============================================================
 *
 * Sử dụng Sanctum để xác thực tài khoản thật.
 *
 * User thật được lấy từ:
 * Authorization: Bearer <token>
 *
 * AuthContext sẽ lấy:
 * - user_id
 * - role
 * - department_id
 * - email
 * - full_name
 *
 * Role:
 * - student
 * - staff
 * - department_head
 * - admin
 */
Route::middleware('auth:sanctum')
    ->prefix('requests')
    ->group(function () {

        /**
         * GET /api/requests
         *
         * Student:
         *   chỉ thấy request của mình
         *
         * Staff:
         *   chỉ thấy request được giao cho mình
         *
         * Department Head:
         *   thấy request thuộc phòng mình
         *
         * Admin:
         *   thấy tất cả
         */
        Route::get('/', [
            RequestController::class,
            'index',
        ]);

        /**
         * POST /api/requests
         *
         * Chỉ STUDENT được tạo request.
         */
        Route::post('/', [
            RequestController::class,
            'store',
        ]);

        /**
         * GET /api/requests/{supportRequest}
         *
         * Xem chi tiết request.
         *
         * Quyền truy cập chi tiết sẽ được kiểm tra
         * trong RequestController.
         */
        Route::get('/{supportRequest}', [
            RequestController::class,
            'show',
        ]);

        /**
         * PUT /api/requests/{supportRequest}/status
         *
         * Staff / Department Head / Admin được xử lý
         * trạng thái theo quyền nghiệp vụ.
         */
        Route::put('/{supportRequest}/status', [
            RequestController::class,
            'updateStatus',
        ]);

        /**
         * PUT /api/requests/{supportRequest}/assign
         *
         * Department Head / Admin được phân công STAFF.
         */
        Route::put('/{supportRequest}/assign', [
            RequestController::class,
            'assign',
        ]);

        /**
         * PUT /api/requests/{supportRequest}/cancel
         *
         * Student:
         *   chỉ được hủy request của chính mình
         *
         * Admin:
         *   được hủy request.
         */
        Route::put('/{supportRequest}/cancel', [
            RequestController::class,
            'cancel',
        ]);

        /**
         * GET /api/requests/{supportRequest}/history
         *
         * Xem lịch sử thay đổi trạng thái.
         */
        Route::get('/{supportRequest}/history', [
            RequestController::class,
            'history',
        ]);
    });
