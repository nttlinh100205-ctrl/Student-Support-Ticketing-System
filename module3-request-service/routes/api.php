<?php

use App\Http\Controllers\Api\AiChatController;
use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\CatalogUsageController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\RatingController;
use App\Http\Controllers\Api\RequestController;
use App\Http\Controllers\Api\SlaController;
use App\Http\Controllers\Web\AdminChatController;
use Illuminate\Support\Facades\Route;

Route::post('/ai/chat', AiChatController::class)->middleware('account.auth');
Route::middleware('account.auth')->group(function () {
    Route::get('/support-chat', [AdminChatController::class, 'index']);
    Route::get('/support-chat/images/{message}', [AdminChatController::class, 'image'])->whereNumber('message');
    Route::post('/support-chat', [AdminChatController::class, 'store'])->middleware(['throttle:20,1', 'idempotent']);
});

/**
 * Alias cũ auth.fake dùng AccountAuthentication mặc định; mock chỉ bật qua cấu hình.
 */
Route::middleware('auth.fake')->prefix('requests')->group(function () {
    Route::get('/', [RequestController::class, 'index']);
    Route::post('/', [RequestController::class, 'store'])->middleware('idempotent');
    Route::get('/{supportRequest}', [RequestController::class, 'show']);
    Route::put('/{supportRequest}', [RequestController::class, 'update']);
    Route::put('/{supportRequest}/status', [RequestController::class, 'updateStatus']);
    Route::put('/{supportRequest}/assign', [RequestController::class, 'assign']);
    Route::put('/{supportRequest}/transfer', [RequestController::class, 'transfer']);
    Route::put('/{supportRequest}/cancel', [RequestController::class, 'cancel']);
    Route::delete('/{supportRequest}', [RequestController::class, 'destroy']);
    Route::post('/{supportRequest}/rating', [RatingController::class, 'store']);
    Route::get('/{supportRequest}/history', [RequestController::class, 'history']);

    /*
    |--------------------------------------------------------------------------
    | Comment Thread (Trao đổi)
    |--------------------------------------------------------------------------
    */
    Route::get('/{supportRequest}/comments', [CommentController::class, 'index']);
    Route::post('/{supportRequest}/comments', [CommentController::class, 'store']);
    Route::delete('/{supportRequest}/comments/{comment}', [CommentController::class, 'destroy']);
    Route::post('/{supportRequest}/comments/{comment}/attachments', [AttachmentController::class, 'store'])
        ->name('api.requests.comments.attachments.store');

    Route::get('/{supportRequest}/comments/{comment}/attachments/{commentAttachment}/preview', [AttachmentController::class, 'preview'])
        ->name('api.requests.comments.attachments.preview');
});

/*
|--------------------------------------------------------------------------
| SLA Monitoring Endpoints
|--------------------------------------------------------------------------
*/
Route::middleware('auth.fake')->prefix('sla')->group(function () {
    // Danh sách thông báo SLA của user hiện tại (chuông thông báo)
    Route::get('/notifications', [SlaController::class, 'notifications']);
    // Danh sách ticket vi phạm / sắp quá hạn SLA (dashboard)
    Route::get('/tickets', [SlaController::class, 'tickets']);
});

Route::get('/catalog-usage', CatalogUsageController::class)->middleware('auth.fake');
