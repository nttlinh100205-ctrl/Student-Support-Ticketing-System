<?php

use App\Http\Controllers\Api\RatingController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Module 5: Đánh giá và Báo cáo (Report & Evaluation Service - Port :8005)
|--------------------------------------------------------------------------
*/

// API Báo cáo & Thống kê (Dành cho cán bộ, trưởng phòng ban, admin)
Route::middleware('can-view-reports')->group(function () {
    Route::get('/reports/statistics', [ReportController::class, 'statistics']);
    Route::get('/reports/export', [ReportController::class, 'export']);
    Route::get('/reports/ratings/statistics', [RatingController::class, 'summary']);
    Route::get('/ratings', [RatingController::class, 'index']);
});

// API Đánh giá chất lượng hỗ trợ (Dành cho Sinh viên)
Route::middleware('is-student')->group(function () {
    Route::post('/ratings', [RatingController::class, 'store']);
});
