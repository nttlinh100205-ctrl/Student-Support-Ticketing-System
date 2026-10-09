<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kiểm tra phòng ban / loại hỗ trợ đã được yêu cầu (Module 3) sử dụng chưa.
 *
 * Chạy tích hợp gọi API Module 3; test dùng dữ liệu cục bộ tường minh.
 */
class RequestUsage
{
    public static function usesDepartment(int $departmentId): bool
    {
        return self::exists('department_id', $departmentId);
    }

    public static function usesSupportType(int $supportTypeId): bool
    {
        return self::exists('support_type_id', $supportTypeId);
    }

    private static function exists(string $column, int $id): bool
    {
        if (! config('account.fake')) {
            return (bool) app(ServiceClient::class)->get(config('account.request_url'), '/api/catalog-usage', [$column => $id])['data']['used'];
        }
        if (! Schema::hasTable('requests')) {
            return false;
        }

        return DB::table('requests')
            ->where($column, $id)
            ->exists();
    }
}
