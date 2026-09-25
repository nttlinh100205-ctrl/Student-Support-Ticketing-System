<?php

use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\RequestController;
use Illuminate\Support\Facades\Route;

/**
 * Middleware 'auth.fake' — khi Module 1 xong đổi sang JWT.
 */
Route::middleware('auth.fake')->prefix('requests')->group(function () {
    Route::get('/', [RequestController::class, 'index']);
    Route::post('/', [RequestController::class, 'store']);
    Route::get('/{supportRequest}', [RequestController::class, 'show']);
    Route::put('/{supportRequest}', [RequestController::class, 'update']);
    Route::put('/{supportRequest}/status', [RequestController::class, 'updateStatus']);
    Route::put('/{supportRequest}/assign', [RequestController::class, 'assign']);
    Route::put('/{supportRequest}/cancel', [RequestController::class, 'cancel']);
    Route::delete('/{supportRequest}', [RequestController::class, 'destroy']);
    Route::get('/{supportRequest}/history', [RequestController::class, 'history']);

    /*
    |--------------------------------------------------------------------------
    | Comment Thread (Trao đổi)
    |--------------------------------------------------------------------------
    */
    Route::get('/{supportRequest}/comments', [CommentController::class, 'index']);
    Route::post('/{supportRequest}/comments', [CommentController::class, 'store']);
    Route::delete('/{supportRequest}/comments/{comment}', [CommentController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| SLA Monitoring Endpoints
|--------------------------------------------------------------------------
*/
Route::middleware('auth.fake')->prefix('sla')->group(function () {
    // Danh sách thông báo SLA của user hiện tại (chuông thông báo)
    Route::get('/notifications', [\App\Http\Controllers\Api\SlaController::class, 'notifications']);
    // Danh sách ticket vi phạm / sắp quá hạn SLA (dashboard)
    Route::get('/tickets', [\App\Http\Controllers\Api\SlaController::class, 'tickets']);
});
