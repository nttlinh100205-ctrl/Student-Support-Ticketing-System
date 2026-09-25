<?php

namespace App\Services;

use App\Contracts\OrgServiceClientInterface;
use App\Contracts\RequestServiceClientInterface;
use App\Enums\RequestStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportService
{
    public function __construct(
        private RequestServiceClientInterface $requestClient,
        private OrgServiceClientInterface $orgClient,
        private RatingService $ratingService
    ) {}

    /**
     * Lấy dữ liệu báo cáo thống kê tổng hợp toàn hệ thống.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getStatistics(array $filters): array
    {
        $requests = $this->fetchFilteredRequests($filters);
        $departments = collect($this->orgClient->getDepartments())->keyBy('id');
        $supportTypes = collect($this->orgClient->getSupportTypes())->keyBy('id');

        return [
            'total_requests' => count($requests),
            'by_status' => $this->countByStatus($requests),
            'by_department' => $this->groupByDepartment($requests, $departments),
            'by_support_type' => $this->groupBySupportType($requests, $supportTypes),
            'avg_processing_hours' => $this->averageProcessingHours($requests),
            'requests_over_time' => $this->groupOverTime($requests),
            'ratings_summary' => $this->ratingService->getRatingSummary($filters),
        ];
    }

    /**
     * Xuất file CSV danh sách yêu cầu chi tiết (kèm BOM UTF-8 cho Excel).
     *
     * @param  array<string, mixed>  $filters
     */
    public function exportCsv(array $filters): StreamedResponse
    {
        $requests = $this->fetchFilteredRequests($filters);
        $departments = collect($this->orgClient->getDepartments())->keyBy('id');
        $supportTypes = collect($this->orgClient->getSupportTypes())->keyBy('id');

        $fileName = 'bao-cao-yeu-cau-'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($requests, $departments, $supportTypes) {
            $handle = fopen('php://output', 'w');

            // Ghi UTF-8 BOM để Excel hiển thị đúng tiếng Việt không bị lỗi font
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Mã yêu cầu',
                'ID',
                'Tiêu đề',
                'Mã sinh viên',
                'Họ tên sinh viên',
                'Phòng ban',
                'Loại yêu cầu',
                'Trạng thái',
                'Mức độ ưu tiên',
                'Ngày tạo',
                'Ngày giải quyết',
                'Thời gian xử lý (giờ)',
            ]);

            foreach ($requests as $r) {
                $deptName = $departments[$r['department_id']]['name'] ?? ('Phòng ban #'.($r['department_id'] ?? ''));
                $typeName = $supportTypes[$r['support_type_id']]['name'] ?? ('Loại yêu cầu #'.($r['support_type_id'] ?? ''));

                $statusEnum = RequestStatus::tryFrom($r['status'] ?? '');
                $statusLabel = $statusEnum ? $statusEnum->label() : ($r['status'] ?? '');

                $hours = null;
                if (! empty($r['created_at']) && ! empty($r['resolved_at'])) {
                    $created = Carbon::parse($r['created_at']);
                    $resolved = Carbon::parse($r['resolved_at']);
                    $hours = round($created->diffInMinutes($resolved) / 60, 1);
                }

                fputcsv($handle, [
                    $r['code'] ?? ('YC-'.$r['id']),
                    $r['id'],
                    $r['title'] ?? '',
                    $r['student_code'] ?? ($r['student_id'] ?? ''),
                    $r['student_name'] ?? '',
                    $deptName,
                    $typeName,
                    $statusLabel,
                    $r['priority'] ?? 'normal',
                    $r['created_at'] ? Carbon::parse($r['created_at'])->format('d/m/Y H:i') : '',
                    $r['resolved_at'] ? Carbon::parse($r['resolved_at'])->format('d/m/Y H:i') : '',
                    $hours !== null ? $hours : 'Chưa xong',
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ]);
    }

    /**
     * Lấy và lọc danh sách yêu cầu.
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function fetchFilteredRequests(array $filters): array
    {
        $requests = $this->requestClient->getRequests($filters);

        return collect($requests)->filter(function (array $item) use ($filters) {
            if (! empty($filters['department_id']) && (int) ($item['department_id'] ?? 0) !== (int) $filters['department_id']) {
                return false;
            }

            if (! empty($filters['support_type_id']) && (int) ($item['support_type_id'] ?? 0) !== (int) $filters['support_type_id']) {
                return false;
            }

            if (! empty($filters['from_date']) && ! empty($item['created_at'])) {
                $createdAt = Carbon::parse($item['created_at'])->startOfDay();
                $fromDate = Carbon::parse($filters['from_date'])->startOfDay();
                if ($createdAt->lt($fromDate)) {
                    return false;
                }
            }

            if (! empty($filters['to_date']) && ! empty($item['created_at'])) {
                $createdAt = Carbon::parse($item['created_at'])->endOfDay();
                $toDate = Carbon::parse($filters['to_date'])->endOfDay();
                if ($createdAt->gt($toDate)) {
                    return false;
                }
            }

            return true;
        })->values()->toArray();
    }

    /**
     * Đếm số lượng theo trạng thái.
     *
     * @param  array<int, array<string, mixed>>  $requests
     * @return array<string, int>
     */
    private function countByStatus(array $requests): array
    {
        $counts = [];
        foreach (RequestStatus::cases() as $case) {
            $counts[$case->value] = 0;
        }

        foreach ($requests as $r) {
            $st = $r['status'] ?? 'new';
            if (isset($counts[$st])) {
                $counts[$st]++;
            } else {
                $counts[$st] = 1;
            }
        }

        return $counts;
    }

    /**
     * Thống kê theo phòng ban.
     *
     * @param  array<int, array<string, mixed>>  $requests
     * @param  Collection<int, array<string, mixed>>  $departments
     * @return array<int, array{department_id: int, department_name: string, total: int}>
     */
    private function groupByDepartment(array $requests, $departments): array
    {
        $grouped = collect($requests)->groupBy('department_id');

        return $grouped->map(function ($items, $deptId) use ($departments) {
            $dept = $departments->get((int) $deptId);

            return [
                'department_id' => (int) $deptId,
                'department_name' => $dept['name'] ?? ('Phòng ban #'.$deptId),
                'total' => $items->count(),
            ];
        })->values()->toArray();
    }

    /**
     * Thống kê theo loại yêu cầu hỗ trợ.
     *
     * @param  array<int, array<string, mixed>>  $requests
     * @param  Collection<int, array<string, mixed>>  $supportTypes
     * @return array<int, array{support_type_id: int, name: string, total: int}>
     */
    private function groupBySupportType(array $requests, $supportTypes): array
    {
        $grouped = collect($requests)->groupBy('support_type_id');

        return $grouped->map(function ($items, $typeId) use ($supportTypes) {
            $type = $supportTypes->get((int) $typeId);

            return [
                'support_type_id' => (int) $typeId,
                'name' => $type['name'] ?? ('Loại yêu cầu #'.$typeId),
                'total' => $items->count(),
            ];
        })->values()->toArray();
    }

    /**
     * Tính thời gian xử lý trung bình (theo giờ).
     *
     * @param  array<int, array<string, mixed>>  $requests
     */
    private function averageProcessingHours(array $requests): ?float
    {
        $resolved = collect($requests)->filter(fn ($r) => ! empty($r['resolved_at']) && ! empty($r['created_at']));
        if ($resolved->isEmpty()) {
            return null;
        }

        $hours = $resolved->map(function ($r) {
            $created = Carbon::parse($r['created_at']);
            $resolved = Carbon::parse($r['resolved_at']);

            return $created->diffInMinutes($resolved) / 60;
        });

        return round($hours->avg(), 1);
    }

    /**
     * Thống kê số lượng yêu cầu theo từng ngày.
     *
     * @param  array<int, array<string, mixed>>  $requests
     * @return array<int, array{date: string, total: int}>
     */
    private function groupOverTime(array $requests): array
    {
        $grouped = collect($requests)->groupBy(function ($r) {
            return Carbon::parse($r['created_at'])->format('Y-m-d');
        })->sortKeys();

        return $grouped->map(function ($items, $date) {
            return [
                'date' => $date,
                'total' => $items->count(),
            ];
        })->values()->toArray();
    }
}
