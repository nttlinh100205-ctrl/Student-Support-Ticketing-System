<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AuthService
{
    public function __construct(
        private readonly JwtIssuer $jwtIssuer
    ) {}

    public function register(array $data): array
    {
        $user = User::create([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'role' => 'student',
            'status' => 'ACTIVE',
            'department_id' => null,
        ]);

        return [
            'user' => $this->userData($user),
        ];
    }

    public function login(array $data): array
    {
        $user = User::query()
            ->where('email', $data['email'])
            ->first();

        if (
            $user === null ||
            ! Hash::check($data['password'], $user->password)
        ) {
            throw new RuntimeException(
                'Email hoac mat khau khong dung.'
            );
        }

        if ($user->status !== 'ACTIVE') {
            throw new RuntimeException(
                'Tai khoan da bi khoa.'
            );
        }

        return [
            'token' => $this->jwtIssuer->issue($user),
            'user' => $this->userData($user),
        ];
    }

    private function userData(User $user): array
    {
        return [
            'id' => (int) $user->id,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'role' => strtolower($user->role),
        ];
    }
}
