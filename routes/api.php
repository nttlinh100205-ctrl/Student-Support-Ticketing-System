<?php

use App\Http\Controllers\Api\RequestController;
use Illuminate\Support\Facades\Route;

/**
 * Quy ước route (Mục 6 API_CONTRACT.md): danh từ số nhiều, kebab-case,
 * hành động đặc biệt gắn sau {id} — /requests/{id}/cancel không phải
 * /cancel-request/{id}.
 *
 * Middleware 'auth.fake' đọc header giả X-User-Id/X-User-Role (Mục 3.5).
 * Khi Module 1 xong: đổi 'auth.fake' -> middleware verify JWT thật.
 */
Route::middleware('auth.fake')->prefix('requests')->group(function () {
    Route::get('/', [RequestController::class, 'index']);
    Route::post('/', [RequestController::class, 'store']);
    Route::get('/{supportRequest}', [RequestController::class, 'show']);
    Route::put('/{supportRequest}/status', [RequestController::class, 'updateStatus']);
    Route::put('/{supportRequest}/assign', [RequestController::class, 'assign']);
    Route::put('/{supportRequest}/cancel', [RequestController::class, 'cancel']);
    Route::get('/{supportRequest}/history', [RequestController::class, 'history']);
});
