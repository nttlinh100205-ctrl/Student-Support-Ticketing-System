<?php

namespace App\Enums;

/**
 * Vai trò người dùng — đúng 4 giá trị của field "role" trong JWT (API Contract mục 3.3).
 */
enum UserRole: string
{
    case Student = 'student';
    case Staff = 'staff';
    case DepartmentHead = 'department_head';
    case Admin = 'admin';

    /**
     * Vai trò có thể gán cho cán bộ trong phòng ban.
     *
     * @return list<string>
     */
    public static function staffValues(): array
    {
        return [self::Staff->value, self::DepartmentHead->value];
    }
}
