<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\Auth\Jwt\JwtIssuer;
use App\Services\Auth\Jwt\JwtVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JwtContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_issues_and_verifies_rs256_jwt_with_exact_claims(): void
    {
        $user = User::query()->create([
            'name' => 'Test User',
            'full_name' => 'Test User',
            'email' => 'jwt@example.com',
            'password' => 'Password123!',
            'role' => 'student',
            'department_id' => null,
            'is_active' => true,
            'must_change_password' => false,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'email_verified_at' => now(),
        ]);

        $issuer = app(JwtIssuer::class);
        $verifier = app(JwtVerifier::class);

        $token = $issuer->issue($user);

        $payload = $verifier->verify($token);

        $expectedClaims = [
            'sub',
            'role',
            'department_id',
            'email',
            'full_name',
            'iat',
            'exp',
        ];

        $actualClaims = array_keys($payload);

        sort($expectedClaims);
        sort($actualClaims);

        $this->assertSame($expectedClaims, $actualClaims);
        $this->assertSame($user->id, $payload['sub']);
        $this->assertSame('student', $payload['role']);
        $this->assertNull($payload['department_id']);
        $this->assertSame('jwt@example.com', $payload['email']);
        $this->assertSame('Test User', $payload['full_name']);
        $this->assertIsInt($payload['iat']);
        $this->assertIsInt($payload['exp']);
        $this->assertSame(
    $payload['iat'] + (int) config('jwt.ttl'),
    $payload['exp']
);
    }
}