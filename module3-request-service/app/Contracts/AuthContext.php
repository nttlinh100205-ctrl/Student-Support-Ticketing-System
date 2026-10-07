<?php

namespace App\Contracts;

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
