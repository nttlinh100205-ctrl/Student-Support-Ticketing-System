<?php

namespace App\Services\Auth\Token;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Support\Str;
use RuntimeException;

class RefreshTokenService
{
    /**
     * Create and persist a new opaque refresh token.
     *
     * The raw token is returned only to the client.
     * Only its SHA-256 hash is stored in the database.
     *
     * @return array{token:string, model:RefreshToken}
     */
    public function create(
        User $user,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $deviceName = null
    ): array {
        $rawToken = Str::random(96);

        $model = RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $rawToken),
            'expires_at' => now()->addSeconds(
                (int) config('jwt.refresh_ttl', 604800)
            ),
            'revoked_at' => null,
            'last_used_at' => null,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'device_name' => $deviceName,
        ]);

        return [
            'token' => $rawToken,
            'model' => $model,
        ];
    }

    public function findUsable(string $rawToken): RefreshToken
    {
        $hash = hash('sha256', $rawToken);

        $refreshToken = RefreshToken::query()
            ->where('token_hash', $hash)
            ->with('user')
            ->first();

        if ($refreshToken === null) {
            throw new RuntimeException(
                'Refresh token không hợp lệ.'
            );
        }

        if (!$refreshToken->isUsable()) {
            throw new RuntimeException(
                'Refresh token đã hết hạn hoặc đã bị thu hồi.'
            );
        }

        if (!$refreshToken->user->is_active) {
            throw new RuntimeException(
                'Tài khoản đang bị vô hiệu hóa.'
            );
        }

        return $refreshToken;
    }

    public function rotate(
        RefreshToken $current,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $deviceName = null
    ): array {
        $current->forceFill([
            'revoked_at' => now(),
            'last_used_at' => now(),
        ])->save();

        return $this->create(
            user: $current->user,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            deviceName: $deviceName
        );
    }

    public function revoke(string $rawToken): void
    {
        $hash = hash('sha256', $rawToken);

        RefreshToken::query()
            ->where('token_hash', $hash)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
            ]);
    }

    public function revokeAllForUser(User $user): int
    {
        return RefreshToken::query()
            ->where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
            ]);
    }
}