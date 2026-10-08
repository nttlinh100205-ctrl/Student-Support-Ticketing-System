<?php

namespace App\Services\Clients;

use App\Contracts\OrgServiceClientInterface;
use App\Services\ServiceClient;
use Illuminate\Http\Request;

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
        $mock = config('services.org_service.mock', false);
        $baseUrl = config('services.org_service.url', 'http://localhost:8002');

        if (! $mock) {
            return app(ServiceClient::class)->all($baseUrl, '/api/catalog/departments');
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
        $mock = config('services.org_service.mock', false);
        $baseUrl = config('services.org_service.url', 'http://localhost:8002');

        if (! $mock) {
            return app(ServiceClient::class)->all($baseUrl, '/api/catalog/support-types');
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
     * Lấy danh sách cán bộ / nhân viên hỗ trợ từ Org Service (:8002).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getStaffMembers(): array
    {
        $mock = config('services.org_service.mock', false);
        $baseUrl = config('services.org_service.url', 'http://localhost:8002');

        if (! $mock) {
            return app(ServiceClient::class)->all(config('account.url'), '/api/v1/directory/staff');
        }

        $path = storage_path('app/mock_staff.json');
        if (! file_exists($path)) {
            return [];
        }

        return json_decode(file_get_contents($path), true) ?: [];
    }

    /**
     * Lấy chi tiết cán bộ hỗ trợ theo ID.
     *
     * @return array<string, mixed>|null
     */
    public function getStaffById(int $staffId): ?array
    {
        $staff = $this->getStaffMembers();

        return collect($staff)->firstWhere('id', $staffId);
    }
}
