<?php

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
});
