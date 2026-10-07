<?php

namespace App\Services\Auth;

use App\Contracts\AuthContextInterface;
use Illuminate\Http\Request;

class FakeHeaderAuthContext implements AuthContextInterface
{
    public function __construct(private Request $request) {}

    public function userId(): ?int
    {
        return $this->request->header('X-User-Id')
            ? (int) $this->request->header('X-User-Id')
            : null;
    }

    public function role(): ?string
    {
        return $this->request->header('X-User-Role');
    }

    public function departmentId(): ?int
    {
        return $this->request->header('X-Department-Id')
            ? (int) $this->request->header('X-Department-Id')
            : null;
    }
}
