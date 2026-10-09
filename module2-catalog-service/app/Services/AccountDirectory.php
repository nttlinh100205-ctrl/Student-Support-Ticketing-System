<?php

namespace App\Services;

use App\Models\SupportDepartment;
use App\Services\Auth\AccountClient;
use Illuminate\Pagination\LengthAwarePaginator;

class AccountDirectory
{
    private ?array $users = null;

    public function __construct(private AccountClient $client) {}

    public function all(): array
    {
        if ($this->users !== null) {
            return $this->users;
        }
        $users = [];
        $page = 1;
        do {
            $data = $this->client->send('GET', '/api/v1/admin/users', request()->attributes->get('account_token'), ['page' => $page])['data'] ?? [];
            abort_unless(is_array($data['data'] ?? null) && isset($data['last_page']), 503);
            foreach ($data['data'] as $user) {
                $user['role'] = strtolower($user['role']);
                $user['department'] = null;
                $users[] = $user;
            }
            $page++;
        } while ($page <= $data['last_page']);

        return $this->users = $users;
    }

    public function paginate(array $filters, ?int $departmentId = null): LengthAwarePaginator
    {
        $items = collect($this->all())->filter(function ($user) use ($filters, $departmentId) {
            if ($user['role'] === 'admin' || ($departmentId !== null && (int) $user['department_id'] !== $departmentId)) {
                return false;
            }
            foreach (['role', 'status'] as $field) {
                if (! empty($filters[$field]) && $user[$field] !== $filters[$field]) {
                    return false;
                }
            }

            return empty($filters['search']) || str_contains(mb_strtolower($user['name'].' '.$user['email']), mb_strtolower($filters['search']));
        })->values();
        $page = max(1, (int) ($filters['page'] ?? 1));

        return new LengthAwarePaginator($items->forPage($page, 50)->values(), $items->count(), 50, $page, ['path' => request()->url()]);
    }

    public function assign(int $userId, SupportDepartment $department, string $role): array
    {
        $user = collect($this->all())->firstWhere('id', $userId);
        abort_unless($user, 404);
        abort_if($user['role'] === 'admin', 409, 'Không thể gán quản trị viên vào phòng ban.');
        abort_unless($department->is_active || (int) $user['department_id'] === $department->id, 409, 'Phòng ban đã ngừng hoạt động.');
        $result = $this->client->send('PUT', '/api/v1/admin/users/'.$userId.'/role', request()->attributes->get('account_token'), [
            'role' => strtoupper($role), 'department_id' => $department->id,
        ])['user'];
        $result['role'] = strtolower($result['role']);
        $result['department'] = $department->only(['id', 'name', 'code', 'is_active']);

        return $result;
    }
}
