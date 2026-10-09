<?php

namespace App\Services;

use App\Contracts\OrgServiceClientInterface;
use App\Contracts\RequestServiceClientInterface;
use App\Enums\RequestStatus;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Response;
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
        $staffMembers = collect($this->orgClient->getStaffMembers())->keyBy('id');
        if (! empty($filters['department_id'])) {
            $departments = $departments->where('id', (int) $filters['department_id']);
            $staffMembers = $staffMembers->where('department_id', (int) $filters['department_id']);
        }
        if (! empty($filters['staff_id'])) {
            $staffMembers = $staffMembers->where('id', (int) $filters['staff_id']);
        }
        $ratingsSummary = $this->ratingService->getRatingSummary($filters);

        $slaMetrics = $this->calculateSlaMetrics($requests, $supportTypes);
        $deptRankings = $this->calculateDepartmentRankings($requests, $departments, $ratingsSummary);
        $staffRankings = $this->calculateStaffRankings($requests, $staffMembers, $departments, $ratingsSummary);
        $staffWorkloads = $this->calculateStaffWorkloads($requests, $staffMembers, $departments);

        return [
            'total_requests' => count($requests),
            'by_status' => $this->countByStatus($requests),
            'by_department' => $this->groupByDepartment($requests, $departments),
            'by_support_type' => $this->groupBySupportType($requests, $supportTypes),
            'avg_processing_hours' => $this->averageProcessingHours($requests),
            'requests_over_time' => $this->groupOverTime($requests),
            'ratings_summary' => $ratingsSummary,
            'sla_metrics' => $slaMetrics,
            'department_rankings' => $deptRankings,
            'staff_rankings' => $staffRankings,
            'staff_workloads' => $staffWorkloads,
        ];
    }

    /**
     * Tính toán chi tiết các chỉ số SLA (Đúng hạn vs Quá hạn).
     *
     * @param  array<int, array<string, mixed>>  $requests
     * @param  Collection<int, array<string, mixed>>  $supportTypes
     * @return array<string, mixed>
     */
    private function calculateSlaMetrics(array $requests, Collection $supportTypes): array
    {
        $now = Carbon::now();
        $totalTracked = 0;
        $onTimeCount = 0;
        $overdueCount = 0;
        $overdueList = [];
        $processingHours = [];

        foreach ($requests as $r) {
            $status = $r['status'] ?? 'new';
            if (in_array($status, ['cancelled', 'rejected'], true)) {
                continue;
            }

            $totalTracked++;
            $type = $supportTypes->get((int) ($r['support_type_id'] ?? 0));
            $slaHours = (int) ($type['sla_hours'] ?? (isset($type['sla_days']) ? $type['sla_days'] * 24 : 48));

            $createdAt = ! empty($r['created_at']) ? Carbon::parse($r['created_at']) : null;
            $resolvedAt = ! empty($r['resolved_at']) ? Carbon::parse($r['resolved_at']) : null;
            $dueAt = ! empty($r['due_at']) ? Carbon::parse($r['due_at']) : ($createdAt ? $createdAt->copy()->addHours($slaHours) : null);

            $isOverdue = false;
            $hoursTaken = null;

            if ($resolvedAt && $createdAt) {
                $hoursTaken = round($createdAt->diffInMinutes($resolvedAt) / 60, 1);
                $processingHours[] = $hoursTaken;
                if ($dueAt && $resolvedAt->gt($dueAt)) {
                    $isOverdue = true;
                }
            } elseif ($dueAt && $now->gt($dueAt)) {
                $isOverdue = true;
            }

            if ($isOverdue) {
                $overdueCount++;
                $overdueList[] = [
                    'id' => $r['id'],
                    'code' => $r['code'] ?? ('YC-'.$r['id']),
                    'title' => $r['title'] ?? '',
                    'student_name' => $r['student_name'] ?? '',
                    'department_id' => $r['department_id'] ?? null,
                    'staff_name' => $r['staff_name'] ?? 'Chưa phân công',
                    'status' => $status,
                    'created_at' => $createdAt ? $createdAt->format('d/m/Y H:i') : '',
                    'due_at' => $dueAt ? $dueAt->format('d/m/Y H:i') : '',
                    'hours_taken' => $hoursTaken,
                    'overdue_hours' => $dueAt ? max(0, round($dueAt->diffInMinutes($resolvedAt ?? $now) / 60, 1)) : 0,
                ];
            } else {
                $onTimeCount++;
            }
        }

        $complianceRate = $totalTracked > 0 ? round(($onTimeCount / $totalTracked) * 100, 1) : 100.0;
        $overdueRate = $totalTracked > 0 ? round(($overdueCount / $totalTracked) * 100, 1) : 0.0;
        $avgHours = count($processingHours) > 0 ? round(array_sum($processingHours) / count($processingHours), 1) : null;

        return [
            'total_tracked' => $totalTracked,
            'on_time_count' => $onTimeCount,
            'overdue_count' => $overdueCount,
            'sla_compliance_rate' => $complianceRate,
            'sla_overdue_rate' => $overdueRate,
            'avg_processing_hours' => $avgHours,
            'overdue_requests' => $overdueList,
        ];
    }

    /**
     * Xếp hạng phòng ban theo hiệu suất xử lý và điểm hài lòng.
     *
     * @param  array<int, array<string, mixed>>  $requests
     * @param  Collection<int, array<string, mixed>>  $departments
     * @param  array<string, mixed>  $ratingsSummary
     * @return array<int, array<string, mixed>>
     */
    private function calculateDepartmentRankings(array $requests, Collection $departments, array $ratingsSummary): array
    {
        $deptRatings = collect($ratingsSummary['by_department'] ?? [])->keyBy('department_id');
        $grouped = collect($requests)->groupBy('department_id');

        $rankings = $departments->map(function ($dept, $deptId) use ($grouped, $deptRatings) {
            $items = $grouped->get($deptId, collect());
            $totalAssigned = $items->count();
            $resolved = $items->filter(fn ($r) => in_array($r['status'] ?? '', ['resolved', 'closed'], true));
            $resolvedCount = $resolved->count();

            $completionRate = $totalAssigned > 0 ? round(($resolvedCount / $totalAssigned) * 100, 1) : 0.0;

            // SLA calculation
            $onTime = $items->filter(function ($r) {
                if (in_array($r['status'] ?? '', ['cancelled', 'rejected'], true)) {
                    return false;
                }
                $createdAt = ! empty($r['created_at']) ? Carbon::parse($r['created_at']) : null;
                $resolvedAt = ! empty($r['resolved_at']) ? Carbon::parse($r['resolved_at']) : null;
                $dueAt = ! empty($r['due_at']) ? Carbon::parse($r['due_at']) : ($createdAt ? $createdAt->copy()->addHours(48) : null);
                if ($resolvedAt && $dueAt) {
                    return $resolvedAt->lte($dueAt);
                }
                if ($dueAt) {
                    return Carbon::now()->lte($dueAt);
                }

                return true;
            })->count();

            $nonCancelled = $items->filter(fn ($r) => ! in_array($r['status'] ?? '', ['cancelled', 'rejected'], true))->count();
            $slaRate = $nonCancelled > 0 ? round(($onTime / $nonCancelled) * 100, 1) : 100.0;

            // Avg hours
            $hours = $resolved->filter(fn ($r) => ! empty($r['created_at']) && ! empty($r['resolved_at']))->map(function ($r) {
                return Carbon::parse($r['created_at'])->diffInMinutes(Carbon::parse($r['resolved_at'])) / 60;
            });
            $avgHours = $hours->isNotEmpty() ? round($hours->avg(), 1) : null;

            // CSAT
            $ratingData = $deptRatings->get($deptId);
            $csat = $ratingData ? (float) $ratingData['average_rating'] : null;

            // Performance score (0 - 100): 40% completion, 35% SLA, 25% CSAT
            $score = $csat === null
                ? round((($completionRate * 0.40) + ($slaRate * 0.35)) / 0.75, 1)
                : round(($completionRate * 0.40) + ($slaRate * 0.35) + (($csat / 5) * 100 * 0.25), 1);

            return [
                'department_id' => (int) $deptId,
                'department_name' => $dept['name'] ?? ('Phòng ban #'.$deptId),
                'total_assigned' => $totalAssigned,
                'resolved_count' => $resolvedCount,
                'completion_rate' => $completionRate,
                'avg_hours' => $avgHours,
                'sla_rate' => $slaRate,
                'csat' => $csat,
                'performance_score' => $score,
            ];
        })->sortByDesc('performance_score')->values()->map(function ($dept, $index) {
            $dept['rank'] = $index + 1;

            return $dept;
        })->toArray();

        return $rankings;
    }

    /**
     * Xếp hạng cán bộ theo hiệu suất và điểm hài lòng.
     *
     * @param  array<int, array<string, mixed>>  $requests
     * @param  Collection<int, array<string, mixed>>  $staffMembers
     * @param  Collection<int, array<string, mixed>>  $departments
     * @param  array<string, mixed>  $ratingsSummary
     * @return array<int, array<string, mixed>>
     */
    private function calculateStaffRankings(array $requests, Collection $staffMembers, Collection $departments, array $ratingsSummary): array
    {
        $staffRatings = collect($ratingsSummary['by_staff'] ?? [])->keyBy('staff_id');
        $grouped = collect($requests)->groupBy('staff_id');

        $rankings = $staffMembers->map(function ($staff, $staffId) use ($grouped, $departments, $staffRatings) {
            $items = $grouped->get($staffId, collect());
            $deptId = $staff['department_id'] ?? 0;
            $deptName = $departments->get($deptId)['name'] ?? ('Phòng ban #'.$deptId);

            $totalAssigned = $items->count();
            $resolved = $items->filter(fn ($r) => in_array($r['status'] ?? '', ['resolved', 'closed'], true));
            $resolvedCount = $resolved->count();
            $completionRate = $totalAssigned > 0 ? round(($resolvedCount / $totalAssigned) * 100, 1) : 0.0;

            // SLA
            $onTime = $items->filter(function ($r) {
                if (in_array($r['status'] ?? '', ['cancelled', 'rejected'], true)) {
                    return false;
                }
                $createdAt = ! empty($r['created_at']) ? Carbon::parse($r['created_at']) : null;
                $resolvedAt = ! empty($r['resolved_at']) ? Carbon::parse($r['resolved_at']) : null;
                $dueAt = ! empty($r['due_at']) ? Carbon::parse($r['due_at']) : ($createdAt ? $createdAt->copy()->addHours(48) : null);
                if ($resolvedAt && $dueAt) {
                    return $resolvedAt->lte($dueAt);
                }
                if ($dueAt) {
                    return Carbon::now()->lte($dueAt);
                }

                return true;
            })->count();

            $nonCancelled = $items->filter(fn ($r) => ! in_array($r['status'] ?? '', ['cancelled', 'rejected'], true))->count();
            $slaRate = $nonCancelled > 0 ? round(($onTime / $nonCancelled) * 100, 1) : 100.0;

            // CSAT
            $ratingData = $staffRatings->get($staffId);
            $csat = $ratingData ? (float) $ratingData['average_rating'] : null;

            $score = $csat === null
                ? round((($completionRate * 0.40) + ($slaRate * 0.35)) / 0.75, 1)
                : round(($completionRate * 0.40) + ($slaRate * 0.35) + (($csat / 5) * 100 * 0.25), 1);

            $score = $totalAssigned > 0 ? $score : null;
            $tier = match (true) {
                $totalAssigned === 0 => 'Chưa có dữ liệu',
                $score >= 88 => 'Xuất sắc',
                $score >= 75 => 'Tốt',
                $score >= 60 => 'Đạt',
                default => 'Cần cải thiện',
            };

            return [
                'staff_id' => (int) $staffId,
                'staff_name' => $staff['name'] ?? ('Cán bộ #'.$staffId),
                'department_id' => (int) $deptId,
                'department_name' => $deptName,
                'total_assigned' => $totalAssigned,
                'resolved_count' => $resolvedCount,
                'completion_rate' => $completionRate,
                'sla_rate' => $slaRate,
                'csat' => $csat,
                'performance_score' => $score,
                'tier' => $tier,
            ];
        })->sortByDesc('performance_score')->values()->map(function ($staff, $index) {
            $staff['rank'] = $index + 1;

            return $staff;
        })->toArray();

        return $rankings;
    }

    /**
     * Tính toán khối lượng công việc từng cán bộ.
     *
     * @param  array<int, array<string, mixed>>  $requests
     * @param  Collection<int, array<string, mixed>>  $staffMembers
     * @param  Collection<int, array<string, mixed>>  $departments
     * @return array<int, array<string, mixed>>
     */
    private function calculateStaffWorkloads(array $requests, Collection $staffMembers, Collection $departments): array
    {
        $grouped = collect($requests)->groupBy('staff_id');

        return $staffMembers->map(function ($staff, $staffId) use ($grouped, $departments) {
            $items = $grouped->get($staffId, collect());
            $deptId = $staff['department_id'] ?? 0;
            $deptName = $departments->get($deptId)['name'] ?? ('Phòng ban #'.$deptId);

            $inProgress = $items->filter(fn ($r) => in_array($r['status'] ?? '', ['in_progress', 'received', 'waiting_info'], true))->count();
            $resolved = $items->filter(fn ($r) => in_array($r['status'] ?? '', ['resolved', 'closed'], true))->count();
            $pending = $items->filter(fn ($r) => ($r['status'] ?? '') === 'new')->count();
            $total = $items->count();

            $status = match (true) {
                $inProgress >= 4 => 'Quá tải',
                $inProgress >= 2 => 'Cao',
                $inProgress === 1 => 'Bình thường',
                default => 'Thấp',
            };

            return [
                'staff_id' => (int) $staffId,
                'staff_name' => $staff['name'] ?? ('Cán bộ #'.$staffId),
                'department_id' => (int) $deptId,
                'department_name' => $deptName,
                'in_progress' => $inProgress,
                'resolved' => $resolved,
                'pending' => $pending,
                'total' => $total,
                'workload_status' => $status,
            ];
        })->sortByDesc('in_progress')->values()->toArray();
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

            // Ghi UTF-8 BOM để Excel hiển thị đúng tiếng Việt
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Mã yêu cầu',
                'ID',
                'Tiêu đề',
                'Mã sinh viên',
                'Họ tên sinh viên',
                'Phòng ban',
                'Cán bộ phụ trách',
                'Loại yêu cầu',
                'Trạng thái',
                'Mức độ ưu tiên',
                'Ngày tạo',
                'Hạn xử lý SLA',
                'Ngày giải quyết',
                'Thời gian xử lý (giờ)',
                'Đạt chuẩn SLA',
            ]);

            foreach ($requests as $r) {
                $deptName = $departments[$r['department_id']]['name'] ?? ('Phòng ban #'.($r['department_id'] ?? ''));
                $typeName = $supportTypes[$r['support_type_id']]['name'] ?? ('Loại yêu cầu #'.($r['support_type_id'] ?? ''));
                $staffName = $r['staff_name'] ?? 'Chưa phân công';

                $statusEnum = RequestStatus::tryFrom($r['status'] ?? '');
                $statusLabel = $statusEnum ? $statusEnum->label() : ($r['status'] ?? '');

                $createdAt = ! empty($r['created_at']) ? Carbon::parse($r['created_at']) : null;
                $resolvedAt = ! empty($r['resolved_at']) ? Carbon::parse($r['resolved_at']) : null;
                $slaHours = $supportTypes[$r['support_type_id']]['sla_hours'] ?? 48;
                $dueAt = ! empty($r['due_at']) ? Carbon::parse($r['due_at']) : ($createdAt ? $createdAt->copy()->addHours($slaHours) : null);

                $hours = null;
                if ($createdAt && $resolvedAt) {
                    $hours = round($createdAt->diffInMinutes($resolvedAt) / 60, 1);
                }

                $slaStatusText = 'Trong hạn';
                if ($resolvedAt && $dueAt) {
                    $slaStatusText = $resolvedAt->lte($dueAt) ? 'Đúng hạn' : 'Quá hạn';
                } elseif ($dueAt && Carbon::now()->gt($dueAt)) {
                    $slaStatusText = 'Quá hạn';
                }

                fputcsv($handle, array_map(
                    fn ($value) => is_string($value) && preg_match('/^\s*[=+@-]/u', $value) ? "'".$value : $value,
                    [
                        $r['code'] ?? ('YC-'.$r['id']),
                        $r['id'],
                        $r['title'] ?? '',
                        $r['student_code'] ?? ($r['student_id'] ?? ''),
                        $r['student_name'] ?? '',
                        $deptName,
                        $staffName,
                        $typeName,
                        $statusLabel,
                        $r['priority'] ?? 'normal',
                        $createdAt ? $createdAt->format('d/m/Y H:i') : '',
                        $dueAt ? $dueAt->format('d/m/Y H:i') : '',
                        $resolvedAt ? $resolvedAt->format('d/m/Y H:i') : '',
                        $hours !== null ? $hours : 'Chưa xong',
                        $slaStatusText,
                    ]
                ));
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ]);
    }

    /**
     * Xuất báo cáo tổng hợp dạng PDF tiêu chuẩn.
     *
     * @param  array<string, mixed>  $filters
     */
    public function exportPdf(array $filters): Response
    {
        $statistics = $this->getStatistics($filters);
        $departments = collect($this->orgClient->getDepartments())->keyBy('id');
        $supportTypes = collect($this->orgClient->getSupportTypes())->keyBy('id');
        $requests = $this->fetchFilteredRequests($filters);

        $pdf = Pdf::loadView('reports.pdf', [
            'stats' => $statistics,
            'departments' => $departments,
            'supportTypes' => $supportTypes,
            'requests' => $requests,
            'filters' => $filters,
            'generatedAt' => now()->format('d/m/Y H:i:s'),
        ])->setPaper('a4', 'portrait');

        $fileName = 'bao-cao-thong-ke-'.now()->format('Y-m-d_His').'.pdf';

        return $pdf->download($fileName);
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

            if (! empty($filters['staff_id']) && (int) ($item['staff_id'] ?? 0) !== (int) $filters['staff_id']) {
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
