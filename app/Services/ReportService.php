<?php

namespace App\Services;

use App\Enums\RequestStatus;

class ReportService
{
    public function getStatistics(array $filters): array
    {
        $requests = $this->fetchRequests($filters);

        return [
            'total_requests' => count($requests),
            'by_status' => $this->countByStatus($requests),
            'avg_processing_hours' => $this->averageProcessingHours($requests),
        ];
    }

    public function exportCsv(array $filters)
    {
        $requests = $this->fetchRequests($filters);

        return response()->streamDownload(function () use ($requests) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Phòng ban', 'Trạng thái', 'Ngày tạo']);
            foreach ($requests as $r) {
                fputcsv($handle, [$r['id'], $r['department_id'], $r['status'], $r['created_at']]);
            }
            fclose($handle);
        }, 'bao-cao-yeu-cau.csv');
    }

    private function countByStatus(array $requests): array
    {
        $counts = collect(RequestStatus::cases())->mapWithKeys(fn ($s) => [$s->value => 0]);

        foreach ($requests as $r) {
            $counts[$r['status']] = ($counts[$r['status']] ?? 0) + 1;
        }

        return $counts->toArray();
    }

    private function fetchRequests(array $filters): array
    {
        return json_decode(file_get_contents(storage_path('app/mock_requests.json')), true);
    }

    private function averageProcessingHours(array $requests): ?float
    {
        $resolved = collect($requests)->filter(fn ($r) => $r['resolved_at'] !== null);
        if ($resolved->isEmpty()) return null;

        $hours = $resolved->map(fn ($r) => now()->parse($r['resolved_at'])->diffInHours(now()->parse($r['created_at'])));
        return round($hours->avg(), 1);
    }
}