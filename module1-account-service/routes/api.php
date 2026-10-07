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

        Route::get('/', [
            ProfileController::class,
            'show'
        ]);

        Route::put('/', [
            ProfileController::class,
            'update'
        ]);

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
         */
        Route::get('/users', [
            AdminUserController::class,
            'index'
        ]);

        /**
         * Xem chi tiết một tài khoản
         */
        Route::get('/users/{user}', [
            AdminUserController::class,
            'show'
        ]);

        /**
         * Thay đổi quyền và phòng ban
         */
        Route::put('/users/{user}/role', [
            AdminUserController::class,
            'updateRole'
        ]);

        /**
         * Khóa / mở khóa tài khoản
         */
        Route::put('/users/{user}/status', [
            AdminUserController::class,
            'updateStatus'
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
 *
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
            'index'
        ]);

        /**
         * POST /api/requests
         *
         * Chỉ STUDENT được tạo request.
         */
        Route::post('/', [
            RequestController::class,
            'store'
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
            'show'
        ]);

        /**
         * PUT /api/requests/{supportRequest}/status
         *
         * Staff / Department Head / Admin được xử lý
         * trạng thái theo quyền nghiệp vụ.
         */
        Route::put('/{supportRequest}/status', [
            RequestController::class,
            'updateStatus'
        ]);

        /**
         * PUT /api/requests/{supportRequest}/assign
         *
         * Department Head / Admin được phân công STAFF.
         */
        Route::put('/{supportRequest}/assign', [
            RequestController::class,
            'assign'
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
            'cancel'
        ]);

        /**
         * GET /api/requests/{supportRequest}/history
         *
         * Xem lịch sử thay đổi trạng thái.
         */
        Route::get('/{supportRequest}/history', [
            RequestController::class,
            'history'
        ]);
    });