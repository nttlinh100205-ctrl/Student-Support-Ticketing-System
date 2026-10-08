<?php

namespace App\Services\Auth;

use App\Models\User;
use RuntimeException;

class JwtIssuer
{
    public function issue(User $user): string
    {
        $now = time();
        $ttl = (int) config('jwt.ttl', 3600);

        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
        ];

        $payload = [
            'sub' => (int) $user->id,
            'role' => strtolower($user->role),
            'department_id' => $user->department_id !== null
                ? (int) $user->department_id
                : null,
            'email' => $user->email,
            'full_name' => $user->full_name,
            'iat' => $now,
            'exp' => $now + $ttl,
        ];

        $encodedHeader = $this->base64UrlEncode(
            json_encode($header, JSON_UNESCAPED_SLASHES)
        );

        $encodedPayload = $this->base64UrlEncode(
            json_encode($payload, JSON_UNESCAPED_SLASHES)
        );

        $signingInput = $encodedHeader.'.'.$encodedPayload;

        $privateKeyPath = base_path(
            config('jwt.private_key_path')
        );

        if (! is_file($privateKeyPath)) {
            throw new RuntimeException(
                'JWT private key khong ton tai.'
            );
        }

        $privateKey = openssl_pkey_get_private(
            file_get_contents($privateKeyPath)
        );

        if ($privateKey === false) {
            throw new RuntimeException(
                'Khong doc duoc JWT private key.'
            );
        }

        $signed = openssl_sign(
            $signingInput,
            $signature,
            $privateKey,
            OPENSSL_ALGO_SHA256
        );

        if (! $signed) {
            throw new RuntimeException(
                'Khong the ky JWT.'
            );
        }

        return $signingInput.'.'.$this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(
            strtr(base64_encode($value), '+/', '-_'),
            '='
        );
    }
}
