<?php

use App\Http\Controllers\Module1ProxyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| MODULE 1 - AUTH SERVICE
|--------------------------------------------------------------------------
|
| Frontend -> Gateway :8000
| Gateway  -> Module 1 :8001
|
*/

/*
 * /api/auth/*
 *
 * login
 * register
 * refresh
 * forgot-password
 * reset-password
 * logout-all
 */
Route::any('/auth/{path?}', [
    Module1ProxyController::class,
    'proxy',
])
    ->where('path', '.*');

/*
 * /api/profile/*
 *
 * profile
 * password
 * avatar
 * login-history
 */
Route::any('/profile/{path?}', [
    Module1ProxyController::class,
    'proxy',
])
    ->where('path', '.*');

/*
 * /api/users/*
 *
 * Quan ly tai khoan cua ADMIN.
 */
Route::any('/users/{path?}', [
    Module1ProxyController::class,
    'proxy',
])
    ->where('path', '.*');