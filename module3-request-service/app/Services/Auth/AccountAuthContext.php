<?php

namespace App\Services\Auth;

use App\Contracts\AuthContext;
use Illuminate\Http\Request;

class AccountAuthContext implements AuthContext
{
    public function __construct(private Request $request) {}

    private function user(): array
    {
        $user = $this->request->attributes->get('account_user');
        abort_unless(is_array($user), 401, 'Vui lòng đăng nhập.');

        return $user;
    }

    public function userId(): int
    {
        return $this->user()['id'];
    }

    public function role(): string
    {
        return $this->user()['role'];
    }

    public function departmentId(): ?int
    {
        return $this->user()['department_id'];
    }

    public function email(): ?string
    {
        return $this->user()['email'];
    }

    public function fullName(): ?string
    {
        return $this->user()['full_name'];
    }
}
