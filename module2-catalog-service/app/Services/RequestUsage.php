<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kiểm tra phòng ban / loại hỗ trợ đã được yêu cầu (Module 3) sử dụng chưa.
 *
 * Bảng requests thuộc Module 3. Khi hai service dùng chung database thì
 * kiểm tra được; nếu chạy riêng (không có bảng) thì bỏ qua.
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
        if (! Schema::hasTable('requests')) {
            return false;
        }

        return DB::table('requests')
            ->where($column, $id)
            ->exists();
    }
}
