<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DevelopmentAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DevelopmentAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_accounts_can_log_in_and_access_protected_endpoints(): void
    {
        config(['development.account_password' => 'Local-test-password!', 'development.department_id' => 42]);
        $this->seed(DatabaseSeeder::class);
        foreach (['admin' => 'ADMIN', 'student' => 'STUDENT', 'staff' => 'STAFF', 'head' => 'DEPARTMENT_HEAD'] as $name => $role) {
            $login = $this->postJson('/api/v1/auth/login', ['email' => $name.'@support.test', 'password' => 'Local-test-password!']);
            $login->assertOk()->assertJsonPath('user.role', $role);
            $this->app['auth']->forgetGuards();
            $this->withToken($login->json('token'))->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('user.role', $role);
            $this->app['auth']->forgetGuards();
            $this->withToken($login->json('token'))->getJson('/api/v1/admin/users')->assertStatus($role === 'ADMIN' ? 200 : 403);
            $this->app['auth']->forgetGuards();
        }
        $this->assertDatabaseCount('users', 4);
        $this->assertDatabaseCount('requests', 0);
    }

    public function test_reseeding_preserves_existing_password_role_and_status(): void
    {
        config(['development.account_password' => 'Local-test-password!', 'development.department_id' => null]);
        $this->seed(DatabaseSeeder::class);
        User::where('email', 'admin@support.test')->firstOrFail()->update(['password' => 'Changed-password!', 'status' => 'LOCKED', 'role' => 'STUDENT']);
        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'admin@support.test')->firstOrFail();
        $this->assertTrue(Hash::check('Changed-password!', $user->password));
        $this->assertSame('LOCKED', $user->status);
        $this->assertSame('STUDENT', $user->role);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_development_seeding_is_rejected_in_production(): void
    {
        $this->app->instance('env', 'production');
        $this->expectException(\RuntimeException::class);
        (new DevelopmentAccountSeeder)->run();
    }
}
