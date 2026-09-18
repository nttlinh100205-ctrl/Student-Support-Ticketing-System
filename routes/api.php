<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ExchangeController;
use App\Http\Controllers\Api\DocumentController;


/*
|--------------------------------------------------------------------------
| Module Trao đổi
|--------------------------------------------------------------------------
*/

Route::get('/exchanges', [ExchangeController::class, 'index']);

Route::get('/exchanges/{id}', [ExchangeController::class, 'show']);

Route::post('/exchanges', [ExchangeController::class, 'store']);

Route::put('/exchanges/{id}', [ExchangeController::class, 'update']);

Route::delete('/exchanges/{id}', [ExchangeController::class, 'destroy']);


/*
|--------------------------------------------------------------------------
| Module Tài liệu
|--------------------------------------------------------------------------
*/

Route::get('/documents', [DocumentController::class, 'index']);

Route::get('/documents/{id}', [DocumentController::class, 'show']);

Route::post('/documents', [DocumentController::class, 'store']);

Route::put('/documents/{id}', [DocumentController::class, 'update']);

Route::delete('/documents/{id}', [DocumentController::class, 'destroy']);


/*
|--------------------------------------------------------------------------
| API theo yêu cầu hỗ trợ
|--------------------------------------------------------------------------
*/

Route::get(
    '/requests/{requestId}/exchanges',
    [ExchangeController::class, 'byRequest']
);

Route::get(
    '/requests/{requestId}/documents',
    [DocumentController::class, 'byRequest']
);