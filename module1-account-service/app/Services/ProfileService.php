<?php

namespace App\Services;

use App\Contracts\AuthContext;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class ProfileService
{
    public function __construct(
        private readonly AuthContext $authContext
    ) {}

    public function show(): array
    {
        return $this->userData(
            $this->currentUser()
        );
    }

    public function update(array $data): array
    {
        $user = $this->currentUser();

        $user->update([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ]);

        return $this->userData(
            $user->fresh()
        );
    }

    public function updatePassword(array $data): void
    {
        $user = $this->currentUser();

        if (! Hash::check(
            $data['current_password'],
            $user->password
        )) {
            throw new RuntimeException(
                'Mat khau hien tai khong dung.'
            );
        }

        $user->update([
            'password' => $data['password'],
        ]);
    }

    private function currentUser(): User
    {
        $user = User::query()->find(
            $this->authContext->userId()
        );

        if ($user === null) {
            throw new RuntimeException(
                'Tai khoan khong ton tai.'
            );
        }

        return $user;
    }

    private function userData(User $user): array
    {
        return [
            'id' => (int) $user->id,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => strtolower($user->role),
            'status' => $user->status,
            'department_id' => $user->department_id !== null
                ? (int) $user->department_id
                : null,
        ];
    }
}
