<?php

use App\Http\Controllers\Module1ProxyController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'success' => true,
        'message' => 'Student Support API Gateway is running.',
    ]);
});

Route::get('/media/{path}', [
    Module1ProxyController::class,
    'storage',
])->where('path', '.*');