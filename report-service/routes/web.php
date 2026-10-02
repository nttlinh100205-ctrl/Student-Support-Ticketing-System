<?php

use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('dashboard');
});

Route::get('/dashboard', function () {
    return view('dashboard');
});

Route::middleware('can-view-reports')->group(function () {
    Route::get('/reports/export-pdf', [ReportController::class, 'exportPdf']);
});
