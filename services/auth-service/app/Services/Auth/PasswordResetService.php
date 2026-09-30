<?php

namespace App\Services\Auth;

use App\Models\PasswordResetToken;
use App\Models\User;
use App\Services\Auth\Token\RefreshTokenService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;

class PasswordResetService
{
    private const TOKEN_TTL_MINUTES = 30;

    public function __construct(
        private readonly RefreshTokenService $refreshTokenService,
    ) {
    }

    /**
     * Tạo yêu cầu reset password.
     *
     * Luôn trả về thông báo thành công để tránh tiết lộ
     * email nào đang tồn tại trong hệ thống.
     */
    public function requestReset(string $email, ?string $ipAddress = null): void
    {
        $email = strtolower(trim($email));

        $user = User::query()
            ->where('email', $email)
            ->where('is_active', true)
            ->first();

        if ($user === null) {
            return;
        }

        // Thu hồi các token reset cũ chưa sử dụng.
        PasswordResetToken::query()
            ->where('user_id', $user->id)
            ->whereNull('used_at')
            ->update([
                'used_at' => now(),
            ]);

        // Token thực tế chỉ tồn tại ở phía người dùng.
        $rawToken = Str::random(64);

        PasswordResetToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $rawToken),
            'expires_at' => now()->addMinutes(self::TOKEN_TTL_MINUTES),
            'used_at' => null,
            'ip_address' => $ipAddress,
        ]);

        $resetUrl = rtrim(
            (string) config('app.url', 'http://127.0.0.1:8001'),
            '/'
        ) . '/reset-password?token=' . urlencode($rawToken)
          . '&email=' . urlencode($user->email);

        Mail::raw(
            "Xin chào {$user->full_name},\n\n"
            . "Bạn vừa yêu cầu đặt lại mật khẩu tài khoản Student Support.\n\n"
            . "Token đặt lại mật khẩu:\n"
            . $rawToken
            . "\n\n"
            . "Liên kết reset:\n"
            . $resetUrl
            . "\n\n"
            . "Token có hiệu lực trong "
            . self::TOKEN_TTL_MINUTES
            . " phút.\n\n"
            . "Nếu bạn không thực hiện yêu cầu này, hãy bỏ qua email.",
            function ($message) use ($user): void {
                $message
                    ->to($user->email)
                    ->subject('Đặt lại mật khẩu - Student Support');
            }
        );
    }

    /**
     * Reset mật khẩu bằng token.
     */
    public function resetPassword(
        string $email,
        string $rawToken,
        string $newPassword,
        ?string $ipAddress = null,
    ): User {
        $email = strtolower(trim($email));
        $tokenHash = hash('sha256', $rawToken);

        $resetToken = PasswordResetToken::query()
            ->where('token_hash', $tokenHash)
            ->with('user')
            ->first();

        if ($resetToken === null) {
            throw new RuntimeException('Reset token không hợp lệ.');
        }

        if ($resetToken->isExpired()) {
            throw new RuntimeException('Reset token đã hết hạn.');
        }

        if ($resetToken->isUsed()) {
            throw new RuntimeException('Reset token đã được sử dụng.');
        }

        $user = $resetToken->user;

        if ($user === null) {
            throw new RuntimeException('Người dùng không tồn tại.');
        }

        if (!$user->is_active) {
            throw new RuntimeException('Tài khoản đang bị vô hiệu hóa.');
        }

        if ($user->email !== $email) {
            throw new RuntimeException('Reset token không hợp lệ.');
        }

        $user->forceFill([
            'password' => $newPassword,
            'must_change_password' => false,
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ])->save();

        $resetToken->forceFill([
            'used_at' => now(),
        ])->save();

        // Reset password phải đăng xuất toàn bộ thiết bị.
        $this->refreshTokenService->revokeAllForUser($user);

        return $user->fresh();
    }

    public function tokenTtlMinutes(): int
    {
        return self::TOKEN_TTL_MINUTES;
    }
}