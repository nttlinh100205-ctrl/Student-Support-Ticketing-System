<?php

namespace App\Services\Clients;

use App\Contracts\RequestServiceClientInterface;
use App\Services\ServiceClient;
use Illuminate\Http\Request;

class RequestServiceClient implements RequestServiceClientInterface
{
    public function __construct(private Request $request) {}

    /**
     * Lấy danh sách yêu cầu từ Request Service (:8003) hoặc mock file khi chạy độc lập.
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function getRequests(array $filters = []): array
    {
        $mock = config('services.request_service.mock', false);
        $baseUrl = config('services.request_service.url', 'http://localhost:8003');

        if (! $mock) {
            $query = $filters;
            foreach (['from_date' => 'from', 'to_date' => 'to', 'staff_id' => 'assigned_to'] as $source => $target) {
                if (isset($query[$source])) {
                    $query[$target] = $query[$source];
                    unset($query[$source]);
                }
            }

            return array_map(fn ($item) => $this->normalize($item), app(ServiceClient::class)->all($baseUrl, '/api/requests', $query));
        }

        return $this->getMockRequests($filters);
    }

    /**
     * Lấy chi tiết một yêu cầu.
     *
     * @return array<string, mixed>|null
     */
    public function getRequestById(int $id): ?array
    {
        $mock = config('services.request_service.mock', false);
        $baseUrl = config('services.request_service.url', 'http://localhost:8003');

        if (! $mock) {
            return $this->normalize(app(ServiceClient::class)->get($baseUrl, "/api/requests/{$id}")['data']);
        }

        $all = $this->getMockRequests();

        return collect($all)->firstWhere('id', $id);
    }

    /**
     * Lấy mock requests từ file JSON.
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function getMockRequests(array $filters = []): array
    {
        $path = storage_path('app/mock_requests.json');
        if (! file_exists($path)) {
            return [];
        }

        $raw = file_get_contents($path);
        /** @var array<int, array<string, mixed>> $requests */
        $requests = json_decode($raw, true) ?: [];

        return $requests;
    }

    private function normalize(array $item): array
    {
        return array_merge($item, ['due_at' => $item['sla_deadline_at'] ?? null, 'staff_id' => $item['assigned_to'] ?? null, 'staff_name' => $item['assigned_staff_name'] ?? null]);
    }
}
