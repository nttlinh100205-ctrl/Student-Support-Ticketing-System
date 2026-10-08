<?php

namespace App\Services\Auth;

use RuntimeException;

class JwtVerifier
{
    public function verify(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new RuntimeException('JWT khong hop le.');
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

        $header = $this->decodeJson($encodedHeader);
        $payload = $this->decodeJson($encodedPayload);

        if (($header['alg'] ?? null) !== 'RS256') {
            throw new RuntimeException(
                'Thuat toan JWT khong hop le.'
            );
        }

        if (($header['typ'] ?? null) !== 'JWT') {
            throw new RuntimeException(
                'Loai token khong hop le.'
            );
        }

        $signature = $this->base64UrlDecode(
            $encodedSignature
        );

        $publicKeyPath = base_path(
            config('jwt.public_key_path')
        );

        if (! is_file($publicKeyPath)) {
            throw new RuntimeException(
                'JWT public key khong ton tai.'
            );
        }

        $publicKey = openssl_pkey_get_public(
            file_get_contents($publicKeyPath)
        );

        if ($publicKey === false) {
            throw new RuntimeException(
                'Khong doc duoc JWT public key.'
            );
        }

        $verified = openssl_verify(
            $encodedHeader.'.'.$encodedPayload,
            $signature,
            $publicKey,
            OPENSSL_ALGO_SHA256
        );

        if ($verified !== 1) {
            throw new RuntimeException(
                'Chu ky JWT khong hop le.'
            );
        }

        $requiredClaims = [
            'sub',
            'role',
            'department_id',
            'email',
            'full_name',
            'iat',
            'exp',
        ];

        foreach ($requiredClaims as $claim) {
            if (! array_key_exists($claim, $payload)) {
                throw new RuntimeException(
                    'JWT thieu claim bat buoc: '.$claim
                );
            }
        }

        if (! is_numeric($payload['iat']) ||
            ! is_numeric($payload['exp'])) {
            throw new RuntimeException(
                'Thoi gian JWT khong hop le.'
            );
        }

        if ((int) $payload['exp'] <= time()) {
            throw new RuntimeException(
                'JWT da het han.'
            );
        }

        return $payload;
    }

    private function decodeJson(string $value): array
    {
        $decoded = $this->base64UrlDecode($value);
        $data = json_decode($decoded, true);

        if (! is_array($data)) {
            throw new RuntimeException(
                'Noi dung JWT khong hop le.'
            );
        }

        return $data;
    }

    private function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;

        if ($remainder !== 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(
            strtr($value, '-_', '+/'),
            true
        );

        if ($decoded === false) {
            throw new RuntimeException(
                'JWT Base64Url khong hop le.'
            );
        }

        return $decoded;
    }
}
