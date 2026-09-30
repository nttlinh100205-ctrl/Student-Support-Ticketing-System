<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationLockoutTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::create([
            'name' => 'Lockout Test',
            'full_name' => 'Lockout Test',
            'email' => 'lockout@example.com',
            'password' => 'Password123!',
            'role' => 'student',
            'department_id' => null,
            'avatar_path' => null,
            'is_active' => true,
            'must_change_password' => false,
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);
    }

    private function login(string $password)
    {
        return $this->postJson('/api/auth/login', [
            'email' => 'lockout@example.com',
            'password' => $password,
        ]);
    }

    public function test_account_is_not_locked_after_four_failed_attempts(): void
    {
        $this->createUser();

        for ($i = 1; $i <= 4; $i++) {
            $response = $this->login('WrongPassword123!');

            $response->assertStatus(401);
        }

        $user = User::where(
            'email',
            'lockout@example.com'
        )->firstOrFail();

        $this->assertSame(4, $user->failed_login_attempts);
        $this->assertNull($user->locked_until);
    }

    public function test_account_is_locked_after_fifth_failed_attempt(): void
    {
        $this->createUser();

        for ($i = 1; $i <= 5; $i++) {
            $response = $this->login('WrongPassword123!');

            $response->assertStatus(401);
        }

        $user = User::where(
            'email',
            'lockout@example.com'
        )->firstOrFail();

        $this->assertSame(5, $user->failed_login_attempts);
        $this->assertNotNull($user->locked_until);
        $this->assertTrue($user->locked_until->isFuture());
    }

    public function test_correct_password_is_rejected_while_account_is_locked(): void
    {
        $user = $this->createUser();

        $user->update([
            'failed_login_attempts' => 5,
            'locked_until' => now()->addMinutes(15),
        ]);

        $response = $this->login('Password123!');

        $response
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Tài khoản đang bị khóa tạm thời.',
            ]);
    }

    public function test_account_is_unlocked_after_lockout_expires(): void
    {
        $user = $this->createUser();

        $user->update([
            'failed_login_attempts' => 5,
            'locked_until' => now()->subMinute(),
        ]);

        $response = $this->login('Password123!');

        $response->assertOk();

        $user->refresh();

        $this->assertSame(0, $user->failed_login_attempts);
        $this->assertNull($user->locked_until);
    }

    public function test_login_logs_are_created_for_failed_and_successful_login(): void
    {
        $user = $this->createUser();

        $this->login('WrongPassword123!')
            ->assertStatus(401);

        $this->login('Password123!')
            ->assertOk();

        $this->assertDatabaseHas('login_logs', [
            'user_id' => $user->id,
            'email' => 'lockout@example.com',
            'event' => 'LOGIN',
            'status' => 'FAILED',
        ]);

        $this->assertDatabaseHas('login_logs', [
            'user_id' => $user->id,
            'email' => 'lockout@example.com',
            'event' => 'LOGIN',
            'status' => 'SUCCESS',
        ]);
    }
}