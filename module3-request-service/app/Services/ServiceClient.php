<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class ServiceClient
{
    public function get(string $baseUrl, string $path, array $query = [], string $method = 'GET'): array
    {
        $request = request();
        $token = $request->attributes->get('account_token') ?: $request->bearerToken();
        abort_unless($token, 401, 'Thiếu phiên đăng nhập liên dịch vụ.');
        try {
            $response = Http::withOptions(parse_url($baseUrl, PHP_URL_HOST) === 'localhost' ? ['force_ip_resolve' => 'v4'] : [])
                ->acceptJson()->withToken($token)->connectTimeout(3)->timeout(8)
                ->send($method, rtrim($baseUrl, '/').$path, $method === 'GET' ? ['query' => $query] : ['json' => $query]);
        } catch (ConnectionException $e) {
            abort(503, 'Không thể kết nối dịch vụ dữ liệu.');
        }
        if ($response->status() === 422 && is_array($response->json('errors'))) {
            throw ValidationException::withMessages(collect($response->json('errors'))->mapWithKeys(
                fn ($messages, $key) => [preg_replace('/^values\./', 'form_values.', $key) => $messages]
            )->all());
        }
        if (in_array($response->status(), [401, 403, 404, 422], true)) {
            abort($response->status(), 'Không thể truy cập dữ liệu được yêu cầu.');
        }
        abort_unless($response->successful() && is_array($response->json()), 503, 'Dịch vụ dữ liệu không khả dụng.');

        return $response->json();
    }

    public function all(string $baseUrl, string $path, array $query = []): array
    {
        $items = [];
        $page = 1;
        do {
            $result = $this->get($baseUrl, $path, array_merge($query, ['page' => $page]));
            $data = $result['data'] ?? null;
            abort_unless(is_array($data), 503, 'Sai cấu trúc danh sách.');
            $rows = $data['data'] ?? $data;
            abort_unless(is_array($rows) && array_is_list($rows), 503, 'Sai cấu trúc danh sách.');
            $items = array_merge($items, $rows);
            $lastPage = (int) ($result['meta']['last_page'] ?? $data['last_page'] ?? 1);
            abort_unless($lastPage >= $page && $lastPage <= 10000, 503, 'Sai thông tin phân trang.');
            $page++;
        } while ($page <= $lastPage);

        return $items;
    }
}
