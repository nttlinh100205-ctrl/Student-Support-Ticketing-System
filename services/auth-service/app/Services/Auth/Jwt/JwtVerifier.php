<?php

namespace App\Services\Auth\Jwt;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use RuntimeException;
use Throwable;

class JwtVerifier
{
    /**
     * Verify and return the decoded JWT payload.
     *
     * @return array<string, mixed>
     */
    public function verify(string $token): array
    {
        if (trim($token) === '') {
            throw new RuntimeException('JWT token is empty.');
        }

        $algorithm = (string) config('jwt.algorithm', 'RS256');

        if ($algorithm !== 'RS256') {
            throw new RuntimeException(
                'Only RS256 is supported by this service.'
            );
        }

        try {
            $decoded = JWT::decode(
                $token,
                new Key(
                    $this->publicKey(),
                    $algorithm
                )
            );
        } catch (Throwable $e) {
            throw new RuntimeException(
                'Invalid JWT token.',
                0,
                $e
            );
        }

        $payload = json_decode(
            json_encode($decoded, JSON_THROW_ON_ERROR),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->validatePayload($payload);

        return $payload;
    }

    private function publicKey(): string
    {
        $path = base_path(
            (string) config(
                'jwt.public_key_path',
                'storage/app/jwt/public.pem'
            )
        );

        if (!is_file($path)) {
            throw new RuntimeException(
                "JWT public key not found: {$path}"
            );
        }

        $contents = file_get_contents($path);

        if ($contents === false || trim($contents) === '') {
            throw new RuntimeException(
                "JWT public key cannot be read: {$path}"
            );
        }

        return $contents;
    }

    /**
     * The project specification requires exactly these 7 payload claims.
     *
     * @param array<string, mixed> $payload
     */
    private function validatePayload(array $payload): void
    {
        $required = [
            'sub',
            'role',
            'department_id',
            'email',
            'full_name',
            'iat',
            'exp',
        ];

        $actual = array_keys($payload);

        sort($actual);
        $expected = $required;
        sort($expected);

        if ($actual !== $expected) {
            throw new RuntimeException(
                'JWT payload must contain exactly the required claims.'
            );
        }

        if (!is_numeric($payload['sub'])) {
            throw new RuntimeException(
                'JWT sub claim is invalid.'
            );
        }

        if (!is_string($payload['role']) || $payload['role'] === '') {
            throw new RuntimeException(
                'JWT role claim is invalid.'
            );
        }

        if (
            $payload['department_id'] !== null
            && !is_numeric($payload['department_id'])
        ) {
            throw new RuntimeException(
                'JWT department_id claim is invalid.'
            );
        }

        if (
            !is_string($payload['email'])
            || $payload['email'] === ''
        ) {
            throw new RuntimeException(
                'JWT email claim is invalid.'
            );
        }

        if (
            !is_string($payload['full_name'])
            || $payload['full_name'] === ''
        ) {
            throw new RuntimeException(
                'JWT full_name claim is invalid.'
            );
        }

        if (!is_numeric($payload['iat'])) {
            throw new RuntimeException(
                'JWT iat claim is invalid.'
            );
        }

        if (!is_numeric($payload['exp'])) {
            throw new RuntimeException(
                'JWT exp claim is invalid.'
            );
        }
    }
}