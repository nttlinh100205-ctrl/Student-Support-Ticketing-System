<?php

use App\Http\Controllers\Web\RequestWebController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::post('/switch-role', [RequestWebController::class, 'switchRole'])
    ->name('requests.switch-role');

Route::get('/requests', [RequestWebController::class, 'index'])
    ->name('requests.index');

Route::get('/requests/create', [RequestWebController::class, 'create'])
    ->name('requests.create');

Route::post('/requests', [RequestWebController::class, 'store'])
    ->name('requests.store');

Route::get('/requests/{supportRequest}', [RequestWebController::class, 'show'])
    ->name('requests.show');

Route::put('/requests/{supportRequest}/status', [RequestWebController::class, 'updateStatus'])
    ->name('requests.update-status');

Route::put('/requests/{supportRequest}/assign', [RequestWebController::class, 'assign'])
    ->name('requests.assign');

Route::put('/requests/{supportRequest}/cancel', [RequestWebController::class, 'cancel'])
    ->name('requests.cancel');

/**
 * ============================================================
 * CLIENT - TÀI KHOẢN VÀ QUYỀN TRUY CẬP
 * ============================================================
 */

/**
 * Trang đăng nhập
 */
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

/**
 * Trang quên mật khẩu
 */
Route::get('/forgot-password', function () {
    return view('auth.forgot-password');
})->name('password.request');

/**
 * Trang đăng ký
 */
Route::get('/register', function () {
    return view('auth.register');
})->name('register');

/**
 * Trang hồ sơ cá nhân
 */
Route::get('/profile', function () {
    return view('profile.index');
})->name('profile');

/**
 * Trang quản lý tài khoản dành cho ADMIN
 */
Route::get('/admin/users', function () {
    return view('admin.users');
})->name('admin.users');

/**
 * Trang đặt lại mật khẩu từ liên kết email
 */
Route::get('/reset-password/{token}', function (string $token) {
    return view('auth.reset-password', [
        'token' => $token,
        'email' => request()->query('email', ''),
    ]);
})->name('password.reset');

/**
 * Trang bắt buộc đổi mật khẩu lần đầu.
 */
Route::get('/change-password', function () {
    return view('auth.change-password');
})->name('password.change');
