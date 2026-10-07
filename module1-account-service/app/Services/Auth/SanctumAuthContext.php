<?php

namespace App\Services\Auth;

use App\Contracts\AuthContext;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class SanctumAuthContext implements AuthContext
{
    protected function user(): User
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            throw new RuntimeException('Chưa xác thực người dùng.');
        }

        return $user;
    }

    public function userId(): int
    {
        return (int) $this->user()->id;
    }

    public function role(): string
    {
        return strtolower($this->user()->role);
    }

    public function departmentId(): ?int
    {
        $departmentId = $this->user()->department_id;

        return $departmentId !== null
            ? (int) $departmentId
            : null;
    }

    public function email(): ?string
    {
        return $this->user()->email;
    }

    public function fullName(): ?string
    {
        return $this->user()->name;
    }
}