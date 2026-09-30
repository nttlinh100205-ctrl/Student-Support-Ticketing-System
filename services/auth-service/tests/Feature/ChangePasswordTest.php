<?php

namespace Tests\Feature;

use App\Models\RefreshToken;
use App\Models\User;
use App\Services\Auth\Jwt\JwtIssuer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_change_password_and_revoke_refresh_tokens(): void
    {
        $user = User::factory()->create([
            'email' => 'change-password@example.com',
            'password' => 'OldPassword123!',
            'full_name' => 'Change Password User',
            'name' => 'Change Password User',
'role' => 'student',
            'must_change_password' => true,
            'is_active' => true,
        ]);

        $refreshToken = RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', Str::random(64)),
            'expires_at' => now()->addDays(7),
            'revoked_at' => null,
            'last_used_at' => null,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'device_name' => 'Test Device',
        ]);

        $accessToken = app(JwtIssuer::class)->issue($user);
$payload = app(\App\Services\Auth\Jwt\JwtVerifier::class)
    ->verify($accessToken);

$this->assertSame(
    $user->id,
    $payload['sub']
);

        $response = $this
            ->withHeader(
                'Authorization',
                'Bearer ' . $accessToken
            )
            ->postJson('/api/auth/change-password', [
                'current_password' => 'OldPassword123!',
                'new_password' => 'NewPassword123!',
                'new_password_confirmation' => 'NewPassword123!',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'message',
                'Đổi mật khẩu thành công.'
            )
            ->assertJsonPath(
                'data.must_change_password',
                false
            );

        $user->refresh();

        $this->assertTrue(
            password_verify(
                'NewPassword123!',
                $user->password
            )
        );

        $this->assertFalse(
            $user->must_change_password
        );

        $this->assertNotNull(
            $refreshToken->fresh()->revoked_at
        );
    }

    public function test_change_password_rejects_wrong_current_password(): void
    {
        $user = User::factory()->create([
            'email' => 'wrong-current@example.com',
            'password' => 'CorrectPassword123!',
            'full_name' => 'Wrong Current User',
            'name' => 'Wrong Current User',
'role' => 'student',
            'must_change_password' => true,
            'is_active' => true,
        ]);

        $accessToken = app(JwtIssuer::class)->issue($user);

        $response = $this
            ->withHeader(
                'Authorization',
                'Bearer ' . $accessToken
            )
            ->postJson('/api/auth/change-password', [
                'current_password' => 'WrongPassword123!',
                'new_password' => 'NewPassword123!',
                'new_password_confirmation' => 'NewPassword123!',
            ]);

        $response
            ->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath(
                'message',
                'Mật khẩu hiện tại không đúng.'
            );
    }

    public function test_new_password_must_differ_from_current_password(): void
    {
        $user = User::factory()->create([
            'email' => 'same-password@example.com',
            'password' => 'SamePassword123!',
            'full_name' => 'Same Password User',
            'name' => 'Same Password User',
'role' => 'student',
            'must_change_password' => false,
            'is_active' => true,
        ]);

        $accessToken = app(JwtIssuer::class)->issue($user);

        $response = $this
            ->withHeader(
                'Authorization',
                'Bearer ' . $accessToken
            )
            ->postJson('/api/auth/change-password', [
                'current_password' => 'SamePassword123!',
                'new_password' => 'SamePassword123!',
                'new_password_confirmation' => 'SamePassword123!',
            ]);

        $response->assertStatus(422);
    }
}