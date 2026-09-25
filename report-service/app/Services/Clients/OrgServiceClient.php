<?php

namespace App\Services\Clients;

use App\Contracts\OrgServiceClientInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OrgServiceClient implements OrgServiceClientInterface
{
    public function __construct(private Request $request) {}

    /**
     * Lấy danh sách phòng ban từ Org Service (:8002).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDepartments(): array
    {
        $mock = config('services.org_service.mock', true);
        $baseUrl = config('services.org_service.url', 'http://localhost:8002');

        if (! $mock) {
            try {
                $response = Http::timeout(5)
                    ->withHeaders($this->forwardHeaders())
                    ->get("{$baseUrl}/api/departments");

                if ($response->successful()) {
                    $json = $response->json();

                    return $json['data'] ?? [];
                }
            } catch (\Throwable $e) {
                Log::warning('Không thể kết nối tới Org Service (departments): '.$e->getMessage());
            }
        }

        $path = storage_path('app/mock_departments.json');
        if (! file_exists($path)) {
            return [];
        }

        return json_decode(file_get_contents($path), true) ?: [];
    }

    /**
     * Lấy chi tiết phòng ban theo ID.
     *
     * @return array<string, mixed>|null
     */
    public function getDepartmentById(int $departmentId): ?array
    {
        $departments = $this->getDepartments();

        return collect($departments)->firstWhere('id', $departmentId);
    }

    /**
     * Lấy danh sách loại yêu cầu hỗ trợ từ Org Service (:8002).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getSupportTypes(): array
    {
        $mock = config('services.org_service.mock', true);
        $baseUrl = config('services.org_service.url', 'http://localhost:8002');

        if (! $mock) {
            try {
                $response = Http::timeout(5)
                    ->withHeaders($this->forwardHeaders())
                    ->get("{$baseUrl}/api/support-types");

                if ($response->successful()) {
                    $json = $response->json();

                    return $json['data'] ?? [];
                }
            } catch (\Throwable $e) {
                Log::warning('Không thể kết nối tới Org Service (support-types): '.$e->getMessage());
            }
        }

        $path = storage_path('app/mock_support_types.json');
        if (! file_exists($path)) {
            return [];
        }

        return json_decode(file_get_contents($path), true) ?: [];
    }

    /**
     * Lấy chi tiết loại yêu cầu hỗ trợ theo ID.
     *
     * @return array<string, mixed>|null
     */
    public function getSupportTypeById(int $supportTypeId): ?array
    {
        $types = $this->getSupportTypes();

        return collect($types)->firstWhere('id', $supportTypeId);
    }

    /**
     * Chuyển tiếp các header xác thực.
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

        return $headers;
    }
}
