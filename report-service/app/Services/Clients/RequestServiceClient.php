<?php

namespace App\Services\Clients;

use App\Contracts\RequestServiceClientInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
        $mock = config('services.request_service.mock', true);
        $baseUrl = config('services.request_service.url', 'http://localhost:8003');

        if (! $mock) {
            try {
                $response = Http::timeout(5)
                    ->withHeaders($this->forwardHeaders())
                    ->get("{$baseUrl}/api/requests", $filters);

                if ($response->successful()) {
                    $json = $response->json();

                    return $json['data'] ?? [];
                }
            } catch (\Throwable $e) {
                Log::warning('Không thể kết nối tới Request Service: '.$e->getMessage());
            }
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
        $mock = config('services.request_service.mock', true);
        $baseUrl = config('services.request_service.url', 'http://localhost:8003');

        if (! $mock) {
            try {
                $response = Http::timeout(5)
                    ->withHeaders($this->forwardHeaders())
                    ->get("{$baseUrl}/api/requests/{$id}");

                if ($response->successful()) {
                    $json = $response->json();

                    return $json['data'] ?? null;
                }
            } catch (\Throwable $e) {
                Log::warning("Không thể lấy chi tiết request {$id}: ".$e->getMessage());
            }
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

    /**
     * Chuyển tiếp các header xác thực tới service khác.
     *
     * @return array<string, string>
     */
    private function forwardHeaders(): array
    {
        $headers = [];

        if ($auth = $this->request->header('Authorization')) {
            $headers['Authorization'] = $auth;
        }

        if ($userId = $this->request->header('X-User-Id')) {
            $headers['X-User-Id'] = $userId;
        }

        if ($role = $this->request->header('X-User-Role')) {
            $headers['X-User-Role'] = $role;
        }

        if ($deptId = $this->request->header('X-Department-Id')) {
            $headers['X-Department-Id'] = $deptId;
        }

        return $headers;
    }
}
