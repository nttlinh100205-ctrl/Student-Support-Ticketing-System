<?php

use App\Http\Controllers\Web\RequestWebController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('requests.index'));

Route::post('/switch-role', [RequestWebController::class, 'switchRole'])->name('requests.switch-role');

Route::get('/requests', [RequestWebController::class, 'index'])->name('requests.index');
Route::get('/requests/create', [RequestWebController::class, 'create'])->name('requests.create');
Route::post('/requests', [RequestWebController::class, 'store'])->name('requests.store');
Route::get('/requests/{supportRequest}', [RequestWebController::class, 'show'])->name('requests.show');
Route::put('/requests/{supportRequest}/status', [RequestWebController::class, 'updateStatus'])->name('requests.update-status');
Route::put('/requests/{supportRequest}/assign', [RequestWebController::class, 'assign'])->name('requests.assign');
Route::put('/requests/{supportRequest}/cancel', [RequestWebController::class, 'cancel'])->name('requests.cancel');
