<?php

namespace App\Http\Controllers\Api;

use App\Contracts\AuthContextInterface;
use App\Contracts\OrgServiceClientInterface;
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

    public function filters(OrgServiceClientInterface $catalog, AuthContextInterface $auth)
    {
        $departments = collect($catalog->getDepartments());
        $types = collect($catalog->getSupportTypes());
        $staff = collect($catalog->getStaffMembers())->filter(fn ($person) => ! isset($person['role']) || strtolower($person['role']) === 'staff');
        if ($auth->role() !== 'admin') {
            $departments = $departments->where('id', $auth->departmentId());
            $types = $types->where('department_id', $auth->departmentId());
            $staff = $staff->where('department_id', $auth->departmentId());
        }
        if ($auth->role() === 'staff') {
            $staff = $staff->where('id', $auth->userId());
        }

        return ApiResponse::success(['departments' => $departments->values(), 'types' => $types->values(), 'staff' => $staff->values()]);
    }

    public function export(ReportExportRequest $request)
    {
        return $this->reportService->exportCsv($request->validated());
    }

    public function exportPdf(ReportExportRequest $request)
    {
        return $this->reportService->exportPdf($request->validated());
    }
}
