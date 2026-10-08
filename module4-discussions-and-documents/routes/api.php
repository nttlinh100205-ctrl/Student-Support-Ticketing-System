<?php

use App\Http\Controllers\Api\NewsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| TIN TỨC - SINH VIÊN / PHÒNG / ADMIN
|--------------------------------------------------------------------------
*/

// Danh sách tin
Route::middleware('account.auth')->group(function () {
    Route::get(
        '/news',
        [NewsController::class, 'index']
    );

    // Xem file
    Route::get(
        '/news/{id}/file',
        [NewsController::class, 'viewFile']
    )->middleware('web');

    // Tải file
    Route::get(
        '/news/{id}/download',
        [NewsController::class, 'downloadFile']
    )->middleware('web');

    // Chi tiết tin
    Route::get(
        '/news/{id}',
        [NewsController::class, 'show']
    );

    // Admin xem tất cả tin
    Route::get(
        '/admin/news',
        [NewsController::class, 'adminIndex']
    );

    // Thêm tin
    Route::post(
        '/news',
        [NewsController::class, 'store']
    );

    // Sửa tin
    Route::put(
        '/news/{id}',
        [NewsController::class, 'update']
    );

    // Xóa tin
    Route::delete(
        '/news/{id}',
        [NewsController::class, 'destroy']
    );
});
