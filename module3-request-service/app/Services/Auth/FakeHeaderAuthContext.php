<?php

namespace App\Services\Auth;

use App\Contracts\AuthContext;
use Illuminate\Http\Request;

class FakeHeaderAuthContext implements AuthContext
{
    public function __construct(protected Request $request) {}

    public function userId(): int
    {
        return (int) $this->request->header('X-User-Id', 0);
    }

    public function role(): string
    {
        return strtolower((string) $this->request->header('X-User-Role', 'student'));
    }

    public function departmentId(): ?int
    {
        $value = $this->request->header('X-Department-Id');

        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    public function email(): ?string
    {
        return $this->request->header('X-User-Email');
    }

    public function fullName(): ?string
    {
        return $this->request->header('X-User-FullName');
    }
}
