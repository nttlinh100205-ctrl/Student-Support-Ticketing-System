<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportExportRequest;
use App\Http\Requests\ReportStatisticsRequest;
use App\Http\Responses\ApiResponse;
use App\Services\ReportService;

class ReportController extends Controller
{
    public function __construct(private ReportService $reportService) {}

    public function statistics(ReportStatisticsRequest $request)
    {
        return ApiResponse::success($this->reportService->getStatistics($request->validated()));
    }

    public function export(ReportExportRequest $request)
    {
        return $this->reportService->exportCsv($request->validated());
    }
}