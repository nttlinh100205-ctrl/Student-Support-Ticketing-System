<?php

namespace App\Services\Auth;

use RuntimeException;

class JwtVerifier
{
    public function verify(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new RuntimeException('Invalid JWT.');
        }

        [$head, $body, $sig] = $parts;

        $header = json_decode(
            $this->decode($head),
            true
        );

        $payload = json_decode(
            $this->decode($body),
            true
        );

        if (
            ($header['alg'] ?? null) !== 'RS256' ||
            !is_array($payload)
        ) {
            throw new RuntimeException('Invalid JWT.');
        }

        $publicPath = env(
            'JWT_PUBLIC_KEY_PATH',
            storage_path('app/jwt/public.pem')
        );

        $publicPath = base_path($publicPath);

        $public = is_file($publicPath)
            ? file_get_contents($publicPath)
            : false;

        if (
            !$public ||
            openssl_verify(
                $head . '.' . $body,
                $this->decode($sig),
                $public,
                OPENSSL_ALGO_SHA256
            ) !== 1
        ) {
            throw new RuntimeException('Invalid signature.');
        }

        if (
            isset($payload['exp']) &&
            (int) $payload['exp'] < time()
        ) {
            throw new RuntimeException('Expired JWT.');
        }

        return $payload;
    }

    private function decode(string $value): string
    {
        $pad = strlen($value) % 4;

        if ($pad > 0) {
            $value .= str_repeat(
                '=',
                4 - $pad
            );
        }

        $decoded = base64_decode(
            strtr($value, '-_', '+/'),
            true
        );

        if ($decoded === false) {
            throw new RuntimeException(
                'Invalid base64url.'
            );
        }

        return $decoded;
    }
}