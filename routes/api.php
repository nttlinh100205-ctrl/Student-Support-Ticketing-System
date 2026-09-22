<?php

use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RequestController;
use App\Http\Controllers\Api\SupportDepartmentController;
use App\Http\Controllers\Api\SupportTypeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| MODULE 1 - TÀI KHOẢN VÀ QUYỀN TRUY CẬP
|--------------------------------------------------------------------------
*/

// ============================================================
// ĐĂNG KÝ + ĐĂNG NHẬP
// Không cần token
// ============================================================

Route::prefix('v1/auth')->group(function () {

    Route::post('/register', [
        AuthController::class,
        'register',
    ]);

    Route::post('/login', [
        AuthController::class,
        'login',
    ]);

});

// ============================================================
// LOGOUT + THÔNG TIN TÀI KHOẢN HIỆN TẠI
// Có Sanctum token
// ============================================================

Route::middleware('auth:sanctum')
    ->prefix('v1/auth')
    ->group(function () {

        Route::post('/logout', [
            AuthController::class,
            'logout',
        ]);

        Route::get('/me', [
            AuthController::class,
            'me',
        ]);

    });

// ============================================================
// HỒ SƠ CÁ NHÂN
// ============================================================

Route::middleware('auth:sanctum')
    ->prefix('v1/profile')
    ->group(function () {

        Route::get('/', [
            ProfileController::class,
            'show',
        ]);

        Route::put('/', [
            ProfileController::class,
            'update',
        ]);

        Route::put('/password', [
            ProfileController::class,
            'updatePassword',
        ]);

    });

// ============================================================
// QUẢN LÝ TÀI KHOẢN - CHỈ ADMIN
// ============================================================

Route::middleware([
    'auth:sanctum',
    'role:ADMIN',
])
    ->prefix('v1/admin')
    ->group(function () {

        // Kiểm tra quyền ADMIN
        Route::get('/test', function () {

            return response()->json([
                'message' => 'Ban la ADMIN va co quyen truy cap.',
            ]);

        });

        // Danh sách tài khoản
        Route::get('/users', [
            AdminUserController::class,
            'index',
        ]);

        // Chi tiết tài khoản
        Route::get('/users/{user}', [
            AdminUserController::class,
            'show',
        ]);

        // Thay đổi role + phòng ban
        Route::put('/users/{user}/role', [
            AdminUserController::class,
            'updateRole',
        ]);

        // Khóa / mở khóa
        Route::put('/users/{user}/status', [
            AdminUserController::class,
            'updateStatus',
        ]);

    });

/*
|--------------------------------------------------------------------------
| MODULE REQUEST - HỖ TRỢ SINH VIÊN
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')
    ->prefix('requests')
    ->group(function () {

        // Danh sách yêu cầu
        Route::get('/', [
            RequestController::class,
            'index',
        ]);

        // Tạo yêu cầu
        Route::post('/', [
            RequestController::class,
            'store',
        ]);

        // Chi tiết yêu cầu
        Route::get('/{supportRequest}', [
            RequestController::class,
            'show',
        ]);

        // Cập nhật trạng thái
        Route::put('/{supportRequest}/status', [
            RequestController::class,
            'updateStatus',
        ]);

        // Phân công cán bộ
        Route::put('/{supportRequest}/assign', [
            RequestController::class,
            'assign',
        ]);

        // Hủy yêu cầu
        Route::put('/{supportRequest}/cancel', [
            RequestController::class,
            'cancel',
        ]);

        // Lịch sử yêu cầu
        Route::get('/{supportRequest}/history', [
            RequestController::class,
            'history',
        ]);

    });

/*
|--------------------------------------------------------------------------
| MODULE 2 - DANH MỤC VÀ TỔ CHỨC
|--------------------------------------------------------------------------
|
| Chỉ ADMIN được sử dụng.
|
| Bao gồm:
| - Quản lý phòng ban
| - Cán bộ theo phòng ban
| - Quản lý loại hỗ trợ
|
*/

Route::middleware([
    'auth:sanctum',
    'role:ADMIN',
])
    ->prefix('v1/admin')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | PHÒNG BAN
        |--------------------------------------------------------------------------
        */

        // Danh sách phòng ban
        Route::get('/departments', [
            SupportDepartmentController::class,
            'index',
        ]);

        // Thêm phòng ban
        Route::post('/departments', [
            SupportDepartmentController::class,
            'store',
        ]);

        // Chi tiết phòng ban
        Route::get('/departments/{department}', [
            SupportDepartmentController::class,
            'show',
        ]);

        // Cập nhật phòng ban
        Route::put('/departments/{department}', [
            SupportDepartmentController::class,
            'update',
        ]);

        // Danh sách cán bộ thuộc phòng ban
        Route::get('/departments/{department}/staff', [
            SupportDepartmentController::class,
            'staff',
        ]);

        // Xóa phòng ban
        Route::delete('/departments/{department}', [
            SupportDepartmentController::class,
            'destroy',
        ]);

        /*
        |--------------------------------------------------------------------------
        | LOẠI HỖ TRỢ
        |--------------------------------------------------------------------------
        */

        // Danh sách loại hỗ trợ
        Route::get('/support-types', [
            SupportTypeController::class,
            'index',
        ]);

        // Thêm loại hỗ trợ
        Route::post('/support-types', [
            SupportTypeController::class,
            'store',
        ]);

        // Chi tiết loại hỗ trợ
        Route::get('/support-types/{supportType}', [
            SupportTypeController::class,
            'show',
        ]);

        // Cập nhật loại hỗ trợ
        Route::put('/support-types/{supportType}', [
            SupportTypeController::class,
            'update',
        ]);

        // Xóa loại hỗ trợ
        Route::delete('/support-types/{supportType}', [
            SupportTypeController::class,
            'destroy',
        ]);
    });
