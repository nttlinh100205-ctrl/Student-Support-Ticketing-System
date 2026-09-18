<?php
use App\Http\Controllers\Api\ReportController;

Route::middleware('can-view-reports')->group(function () {
    Route::get('/reports/statistics', [ReportController::class, 'statistics']);
    Route::get('/reports/export', [ReportController::class, 'export']);
});