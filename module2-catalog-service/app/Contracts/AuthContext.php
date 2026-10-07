<?php

namespace App\Contracts;

/**
 * Interface trừu tượng hóa "ai đang gọi API".
 *
 * Giai đoạn dev: implement bằng FakeHeaderAuthContext (đọc header giả
 * X-User-Id / X-User-Role / X-Department-Id — xem Mục 3.5 tài liệu).
 * Khi Module 1 (Auth) xong: chỉ cần đổi binding trong
 * AuthContextServiceProvider sang implementation đọc JWT thật
 * (JwtAuthContext), toàn bộ Controller/Service dùng AuthContext
 * KHÔNG PHẢI SỬA GÌ.
 */
interface AuthContext
{
    /** Tương ứng field "sub" trong JWT — id của user (bảng users ở Module 1). */
    public function userId(): int;

    /** Tương ứng field "role": student | staff | department_head | admin. */
    public function role(): string;

    /** Tương ứng field "department_id" — null nếu user là student/admin. */
    public function departmentId(): ?int;

    public function email(): ?string;

    public function fullName(): ?string;
}
