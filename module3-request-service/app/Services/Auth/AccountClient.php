<?php

namespace App\Services\Auth;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class AccountClient
{
    public function send(string $method, string $path, ?string $token = null, array $data = []): array
    {
        try {
            $response = Http::baseUrl(rtrim(config('account.url'), '/'))
                ->acceptJson()->withToken($token ?? '')
                ->connectTimeout(3)->timeout(8)
                ->send($method, $path, $method === 'GET' ? ['query' => $data] : ['json' => $data]);
        } catch (ConnectionException $e) {
            abort(503, 'Không thể kết nối dịch vụ tài khoản. Vui lòng thử lại.');
        }

        if (in_array($response->status(), [401, 403, 422], true)) {
            abort($response->status(), 'Phiên đăng nhập hoặc quyền truy cập không hợp lệ.');
        }
        abort_unless($response->successful() && is_array($response->json()), 503, 'Dịch vụ tài khoản trả dữ liệu không hợp lệ.');

        return $response->json();
    }

    public function identity(string $token): array
    {
        $user = $this->send('GET', '/api/v1/auth/me', $token)['user'] ?? null;
        abort_unless(is_array($user) && filter_var($user['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) && is_string($user['role'] ?? null), 503, 'Thiếu thông tin tài khoản.');
        abort_unless(($user['status'] ?? null) === 'ACTIVE', 403, 'Tài khoản đang bị khóa.');
        $role = strtolower($user['role']);
        abort_unless(in_array($role, ['student', 'staff', 'department_head', 'admin'], true), 403, 'Vai trò không hợp lệ.');
        $departmentId = $user['department_id'] ?? null;
        if (in_array($role, ['staff', 'department_head'], true)) {
            abort_unless(filter_var($departmentId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]), 403, 'Tài khoản chưa được gán phòng ban.');
        }

        return [
            'id' => (int) $user['id'], 'role' => $role,
            'department_id' => $departmentId === null ? null : (int) $departmentId,
            'full_name' => $user['name'] ?? '', 'name' => $user['name'] ?? '',
            'email' => $user['email'] ?? null, 'status' => $user['status'],
        ];
    }
}
