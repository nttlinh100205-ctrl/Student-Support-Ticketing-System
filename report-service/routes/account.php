<?php

use App\Http\Controllers\AccountSessionController;
use Illuminate\Support\Facades\Route;

Route::get('/auth/start', [AccountSessionController::class, 'start'])->name('account.start');
Route::get('/auth/callback', [AccountSessionController::class, 'callback'])->name('account.callback');
Route::post('/auth/logout', [AccountSessionController::class, 'logout'])->name('account.logout');
