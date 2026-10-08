<?php

namespace App\Services\Password;

use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use RuntimeException;

class PasswordResetService
{
    public function sendResetLink(array $data): void
    {
        Password::sendResetLink([
            'email' => $data['email'],
        ]);
    }

    public function resetPassword(array $data): void
    {
        $status = Password::reset(
            [
                'email' => $data['email'],
                'password' => $data['password'],
                'password_confirmation' => $data['password_confirmation'],
                'token' => $data['token'],
            ],
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'must_change_password' => false,
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw new RuntimeException(
                'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.'
            );
        }
    }
}
