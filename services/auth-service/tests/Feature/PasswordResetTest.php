<?php

namespace Tests\Feature;

use App\Models\PasswordResetToken;
use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_changes_password_and_revokes_refresh_tokens(): void
    {
        $user = User::factory()->create([
            'email' => 'reset-test@example.com',
            'password' => 'OldPassword123!',
            'full_name' => 'Reset Test User',
            'name' => 'Reset Test User',
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

        $rawToken = Str::random(64);

        PasswordResetToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $rawToken),
            'expires_at' => now()->addMinutes(30),
            'used_at' => null,
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'token' => $rawToken,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'message',
                'Đặt lại mật khẩu thành công.'
            );

        $user->refresh();

        $this->assertTrue(
            Hash::check('NewPassword123!', $user->password)
        );

        $this->assertFalse(
            Hash::check('OldPassword123!', $user->password)
        );

        $this->assertNotNull(
            $refreshToken->fresh()->revoked_at
        );

        $this->assertNotNull(
            PasswordResetToken::query()
                ->where('user_id', $user->id)
                ->first()
                ->used_at
        );
    }

    public function test_used_reset_token_cannot_be_used_again(): void
    {
        $user = User::factory()->create([
            'email' => 'reset-used@example.com',
            'password' => 'OldPassword123!',
            'full_name' => 'Used Reset User',
            'name' => 'Used Reset User',
            'is_active' => true,
        ]);

        $rawToken = Str::random(64);

        PasswordResetToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $rawToken),
            'expires_at' => now()->addMinutes(30),
            'used_at' => now(),
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'token' => $rawToken,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response
            ->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath(
                'message',
                'Reset token đã được sử dụng.'
            );
    }

    public function test_expired_reset_token_cannot_be_used(): void
    {
        $user = User::factory()->create([
            'email' => 'reset-expired@example.com',
            'password' => 'OldPassword123!',
            'full_name' => 'Expired Reset User',
            'name' => 'Expired Reset User',
            'is_active' => true,
        ]);

        $rawToken = Str::random(64);

        PasswordResetToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $rawToken),
            'expires_at' => now()->subMinutes(1),
            'used_at' => null,
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'token' => $rawToken,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response
            ->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath(
                'message',
                'Reset token đã hết hạn.'
            );
    }
}