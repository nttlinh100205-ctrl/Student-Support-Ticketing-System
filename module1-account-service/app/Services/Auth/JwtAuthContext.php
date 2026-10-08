<?php

namespace App\Services\Auth;

use App\Contracts\AuthContext;
use RuntimeException;

class JwtAuthContext implements AuthContext
{
    private ?array $claims = null;

    public function __construct(
        private readonly JwtVerifier $jwtVerifier
    ) {}

    public function setToken(string $token): void
    {
        $this->claims = $this->jwtVerifier->verify($token);
    }

    private function claims(): array
    {
        if ($this->claims === null) {
            throw new RuntimeException(
                'Chua xac thuc JWT.'
            );
        }

        return $this->claims;
    }

    public function userId(): int
    {
        return (int) $this->claims()['sub'];
    }

    public function role(): string
    {
        return (string) $this->claims()['role'];
    }

    public function departmentId(): ?int
    {
        $departmentId = $this->claims()['department_id'];

        return $departmentId !== null
            ? (int) $departmentId
            : null;
    }

    public function email(): ?string
    {
        return $this->claims()['email'];
    }

    public function fullName(): ?string
    {
        return $this->claims()['full_name'];
    }
}
