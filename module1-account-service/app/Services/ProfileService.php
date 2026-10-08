<?php

namespace App\Services;

use App\Contracts\AuthContext;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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

    public function updateAvatar(
        UploadedFile $avatar
    ): array {
        $user = $this->currentUser();

        if (
            $user->avatar &&
            Storage::disk('public')->exists($user->avatar)
        ) {
            Storage::disk('public')->delete(
                $user->avatar
            );
        }

        $path = $avatar->store(
            'avatars',
            'public'
        );

        if ($path === false) {
            throw new RuntimeException(
                'Khong the luu anh dai dien.'
            );
        }

        $user->update([
            'avatar' => $path,
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
            'avatar' => $user->avatar,
            'avatar_url' => $user->avatar
                ? Storage::disk('public')->url($user->avatar)
                : null,
            'role' => strtolower($user->role),
            'status' => $user->status,
            'department_id' => $user->department_id !== null
                ? (int) $user->department_id
                : null,
        ];
    }
}
