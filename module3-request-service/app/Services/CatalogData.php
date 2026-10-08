<?php

namespace App\Services;

class CatalogData
{
    public function __construct(private ServiceClient $client) {}

    public function load(): void
    {
        $url = config('account.catalog_url');
        $departments = $this->client->all($url, '/api/catalog/departments');
        $types = $this->client->all($url, '/api/catalog/support-types');
        $staff = $this->client->all(config('account.url'), '/api/v1/directory/staff');
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
