<?php

namespace App\Services\Auth\Jwt;

use App\Models\User;
use Firebase\JWT\JWT;
use RuntimeException;

class JwtIssuer
{
    /**
     * Issue an access JWT containing exactly the required payload claims.
     */
    public function issue(User $user): string
    {
        $now = time();
        $ttl = (int) config('jwt.ttl', 900);

        $payload = [
            'sub' => (int) $user->id,
            'role' => (string) $user->role,
            'department_id' => $user->department_id !== null
                ? (int) $user->department_id
                : null,
            'email' => (string) $user->email,
            'full_name' => (string) $user->full_name,
            'iat' => $now,
            'exp' => $now + $ttl,
        ];

        return JWT::encode(
            $payload,
            $this->privateKey(),
            (string) config('jwt.algorithm', 'RS256')
        );
    }

    private function privateKey(): string
    {
        $path = base_path(
            (string) config(
                'jwt.private_key_path',
                'storage/app/jwt/private.pem'
            )
        );

        if (!is_file($path)) {
            throw new RuntimeException(
                "JWT private key not found: {$path}"
            );
        }

        $contents = file_get_contents($path);

        if ($contents === false || trim($contents) === '') {
            throw new RuntimeException(
                "JWT private key cannot be read: {$path}"
            );
        }

        return $contents;
    }
}