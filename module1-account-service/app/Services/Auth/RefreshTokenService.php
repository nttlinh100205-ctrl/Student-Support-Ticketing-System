<?php

namespace App\Services\Auth;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RefreshTokenService
{
    public function issue(
        User $user,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): string {
        $plainToken = $this->generatePlainToken();

        RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => $this->hashToken(
                $plainToken
            ),
            'expires_at' => now()->addSeconds(
                (int) config(
                    'jwt.refresh_ttl',
                    2592000
                )
            ),
            'revoked_at' => null,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        return $plainToken;
    }

    public function rotate(
        string $plainToken,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): array {
        return DB::transaction(function () use (
            $plainToken,
            $ipAddress,
            $userAgent
        ) {
            $refreshToken = RefreshToken::query()
                ->where(
                    'token_hash',
                    $this->hashToken($plainToken)
                )
                ->lockForUpdate()
                ->first();

            if (
                $refreshToken === null ||
                $refreshToken->revoked_at !== null ||
                $refreshToken->expires_at->isPast()
            ) {
                throw new RuntimeException(
                    'Refresh token khong hop le hoac da het han.'
                );
            }

            $user = User::query()->find(
                $refreshToken->user_id
            );

            if (
                $user === null ||
                $user->status !== 'ACTIVE'
            ) {
                throw new RuntimeException(
                    'Refresh token khong hop le hoac da het han.'
                );
            }

            /*
             * Refresh token rotation:
             * token cu bi thu hoi ngay.
             */
            $refreshToken->update([
                'revoked_at' => now(),
            ]);

            $newRefreshToken = $this->issue(
                $user,
                $ipAddress,
                $userAgent
            );

            return [
                'user' => $user,
                'refresh_token' => $newRefreshToken,
            ];
        });
    }

    public function revokeAllForUser(
        User $user
    ): void {
        RefreshToken::query()
            ->where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
            ]);
    }

    private function generatePlainToken(): string
    {
        return rtrim(
            strtr(
                base64_encode(
                    random_bytes(64)
                ),
                '+/',
                '-_'
            ),
            '='
        );
    }

    private function hashToken(
        string $plainToken
    ): string {
        return hash(
            'sha256',
            $plainToken
        );
    }
}
