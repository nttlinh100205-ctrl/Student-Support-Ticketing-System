<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CatalogData
{
    public function __construct(private ServiceClient $client) {}

    public function load(): void
    {
        $url = config('account.catalog_url');
        $fetch = fn () => [
            $this->client->all($url, '/api/catalog/departments'),
            $this->client->all($url, '/api/catalog/support-types'),
            $this->client->all(config('account.url'), '/api/v1/directory/staff'),
        ];
        $request = request();
        $user = $request->attributes->get('account_user');
        $token = $request->attributes->get('account_token') ?: $request->bearerToken();
        $ttl = max(0, (int) config('account.catalog_cache_seconds', 30));
        // Chỉ lưu danh mục để hiển thị; ghi dữ liệu luôn kiểm tra danh mục mới.
        // Khóa riêng theo phiên và quyền, không lưu token thô trong tên khóa.
        $key = 'catalog:v1:'.hash('sha256', json_encode([$url, config('account.url'), $token, $user]));
        if ($request->isMethodSafe() && $ttl > 0 && $token && $user) {
            [$departments, $types, $staff] = Cache::remember($key, $ttl, $fetch);
        } else {
            Cache::forget($key);
            [$departments, $types, $staff] = $fetch();
        }
        $staffByDepartment = [];
        $users = [];
        foreach ($staff as $person) {
            $person['full_name'] = $person['name'];
            $person['role'] = strtolower($person['role']);
            $users[$person['id']] = $person;
            if ($person['role'] === 'staff' && $person['department_id']) {
                $staffByDepartment[$person['department_id']][] = $person['id'];
            }
        }
        $current = request()->attributes->get('account_user');
        config([
            'master_data.facilities_department_id' => collect($departments)->firstWhere('code', config('account.facilities_department_code'))['id'] ?? null,
            'master_data.departments' => array_column($departments, 'name', 'id'),
            'master_data.support_types' => array_column($types, null, 'id'),
            'master_data.staff' => $users,
            'master_data.staff_by_department' => $staffByDepartment,
            'master_data.users' => array_replace($users, [$current['id'] => $current]),
        ]);
    }
}
