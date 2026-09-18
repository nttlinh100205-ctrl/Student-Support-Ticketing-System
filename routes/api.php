<?php

use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RequestController;
use Illuminate\Support\Facades\Route;

/**
 * ============================================================
 * MODULE 1 - TÀI KHOẢN VÀ QUYỀN TRUY CẬP
 * ============================================================
 *
 * API công khai:
 * POST /api/v1/auth/register
 * POST /api/v1/auth/login
 *
 * API cần đăng nhập:
 * POST /api/v1/auth/logout
 * GET  /api/v1/auth/me
 *
 * API hồ sơ:
 * GET  /api/v1/profile
 * PUT  /api/v1/profile
 * PUT  /api/v1/profile/password
 *
 */

/**
 * ============================================================
 * ĐĂNG KÝ + ĐĂNG NHẬP
 * Không cần token
 * ============================================================
 */
Route::prefix('v1/auth')->group(function () {

    Route::post('/register', [
        AuthController::class,
        'register'
    ]);

    Route::post('/login', [
        AuthController::class,
        'login'
    ]);
});


/**
 * ============================================================
 * LOGOUT + THÔNG TIN TÀI KHOẢN HIỆN TẠI
 * Bắt buộc có Sanctum token
 * ============================================================
 */
Route::middleware('auth:sanctum')
    ->prefix('v1/auth')
    ->group(function () {

        Route::post('/logout', [
            AuthController::class,
            'logout'
        ]);

        Route::get('/me', [
            AuthController::class,
            'me'
        ]);
    });


/**
 * ============================================================
 * HỒ SƠ CÁ NHÂN
 * Bắt buộc có Sanctum token
 * ============================================================
 */
Route::middleware('auth:sanctum')
    ->prefix('v1/profile')
    ->group(function () {

        // Xem hồ sơ cá nhân
        Route::get('/', [
            ProfileController::class,
            'show'
        ]);

        // Cập nhật họ tên, email, số điện thoại
        Route::put('/', [
            ProfileController::class,
            'update'
        ]);

        // Đổi mật khẩu
        Route::put('/password', [
            ProfileController::class,
            'updatePassword'
        ]);
    });


/**
 * ============================================================
 * QUẢN LÝ TÀI KHOẢN - CHỈ ADMIN
 * ============================================================
 *
 * Bắt buộc:
 * 1. Có Sanctum token
 * 2. Role = ADMIN
 *
 * Các chức năng:
 * - Kiểm tra quyền ADMIN
 * - Xem danh sách tài khoản
 * - Xem chi tiết tài khoản
 * - Thay đổi quyền
 * - Khóa / mở khóa tài khoản
 *
 */
Route::middleware(['auth:sanctum', 'role:ADMIN'])
    ->prefix('v1/admin')
    ->group(function () {

        /**
         * API kiểm tra quyền ADMIN
         */
        Route::get('/test', function () {
            return response()->json([
                'message' => 'Ban la ADMIN va co quyen truy cap.'
            ]);
        });

        /**
         * Danh sách tất cả tài khoản
         * GET /api/v1/admin/users
         */
        Route::get('/users', [
            AdminUserController::class,
            'index'
        ]);

        /**
         * Xem chi tiết một tài khoản
         * GET /api/v1/admin/users/{user}
         */
        Route::get('/users/{user}', [
            AdminUserController::class,
            'show'
        ]);

        /**
         * Thay đổi quyền
         * PUT /api/v1/admin/users/{user}/role
         */
        Route::put('/users/{user}/role', [
            AdminUserController::class,
            'updateRole'
        ]);

        /**
         * Khóa / mở khóa tài khoản
         * PUT /api/v1/admin/users/{user}/status
         */
        Route::put('/users/{user}/status', [
            AdminUserController::class,
            'updateStatus'
        ]);
    });


/**
 * ============================================================
 * MODULE REQUEST / HỖ TRỢ SINH VIÊN - CODE CŨ
 * ============================================================
 *
 * Middleware 'auth.fake' hiện tại vẫn được giữ nguyên.
 * Sau khi hoàn thành phần xác thực thật, chúng ta sẽ thay
 * auth.fake bằng middleware xác thực thật.
 *
 * Header hiện tại:
 * X-User-Id
 * X-User-Role
 *
 */
Route::middleware('auth.fake')
    ->prefix('requests')
    ->group(function () {

        Route::get('/', [
            RequestController::class,
            'index'
        ]);

        Route::post('/', [
            RequestController::class,
            'store'
        ]);

        Route::get('/{supportRequest}', [
            RequestController::class,
            'show'
        ]);

        Route::put('/{supportRequest}/status', [
            RequestController::class,
            'updateStatus'
        ]);

        Route::put('/{supportRequest}/assign', [
            RequestController::class,
            'assign'
        ]);

        Route::put('/{supportRequest}/cancel', [
            RequestController::class,
            'cancel'
        ]);

        Route::get('/{supportRequest}/history', [
            RequestController::class,
            'history'
        ]);
    });