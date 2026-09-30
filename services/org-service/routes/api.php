<?php

use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\SupportTypeController;
use Illuminate\Support\Facades\Route;

Route::middleware('jwt')->group(function () {
    Route::get(
        '/departments',
        [DepartmentController::class, 'index']
    );

    Route::get(
        '/departments/{department}',
        [DepartmentController::class, 'show']
    );

    Route::get(
        '/departments/{department}/staff',
        [DepartmentController::class, 'staff']
    );

    Route::get(
        '/support-types',
        [SupportTypeController::class, 'index']
    );

    Route::middleware('role:admin')->group(function () {
        Route::post(
            '/departments',
            [DepartmentController::class, 'store']
        );

        Route::post(
            '/support-types',
            [SupportTypeController::class, 'store']
        );
    });
});