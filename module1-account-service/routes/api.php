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
 * Dang ky, dang nhap va khoi phuc mat khau.
 * Khong can JWT.
 *
 * POST /api/auth/register
 * POST /api/auth/login
 * POST /api/auth/forgot-password
 * POST /api/auth/reset-password
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
 * ============================================================
 * HO SO CA NHAN
 * ============================================================
 *
 * Tat ca endpoint ben duoi deu can JWT hop le.
 *
 * PUT /api/profile/password:
 * Duoc phep su dung ke ca khi tai khoan dang bi bat buoc
 * doi mat khau lan dau.
 *
 * Cac endpoint con lai:
 * Chi duoc truy cap sau khi must_change_password = false.
 */
Route::middleware('jwt.auth')
    ->prefix('profile')
    ->group(function () {

        /**
         * Doi mat khau.
         *
         * KHONG gan middleware password.changed cho route nay,
         * vi nguoi dung phai truy cap duoc route nay de hoan thanh
         * viec doi mat khau bat buoc.
         */
        Route::put('/password', [
            ProfileController::class,
            'updatePassword',
        ]);

        /**
         * Cac chuc nang ho so binh thuong.
         * Bat buoc da doi mat khau lan dau.
         */
        Route::middleware('password.changed')
            ->group(function () {

                /**
                 * GET /api/profile
                 * Xem ho so.
                 */
                Route::get('/', [
                    ProfileController::class,
                    'show',
                ]);

                /**
                 * PUT /api/profile
                 * Cap nhat ho so.
                 */
                Route::put('/', [
                    ProfileController::class,
                    'update',
                ]);

                /**
                 * POST /api/profile/avatar
                 * Cap nhat anh dai dien.
                 */
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
 *
 * Bat buoc:
 * - JWT hop le
 * - Da doi mat khau bat buoc
 * - Role ADMIN
 *
 * GET /api/users
 * GET /api/users/{user}
 * PUT /api/users/{user}/role
 * PUT /api/users/{user}/status
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
