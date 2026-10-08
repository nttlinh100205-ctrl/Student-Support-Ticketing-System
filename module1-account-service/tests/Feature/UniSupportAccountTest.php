<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class UniSupportAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_accepts_student_code_and_remember_expiry(): void
    {
        User::factory()->create(['student_code' => 'SV2026001', 'password' => 'Password123!', 'role' => 'STUDENT', 'status' => 'ACTIVE']);
        $this->postJson('/api/v1/auth/login', ['email' => 'SV2026001', 'password' => 'Password123!', 'remember' => true])->assertOk();
        $this->assertNotNull(PersonalAccessToken::first()->expires_at);
    }

    public function test_password_reset_revokes_tokens(): void
    {
        Notification::fake();
        $user = User::factory()->create(['role' => 'STUDENT', 'status' => 'ACTIVE']);
        $user->createToken('old');
        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $this->post('/reset-password', ['token' => $notification->token, 'email' => $user->email, 'password' => 'NewPassword123!', 'password_confirmation' => 'NewPassword123!'])->assertRedirect('/login');

            return true;
        });
        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_admin_crud_rejects_student_and_preserves_deleted_user_history(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN', 'status' => 'ACTIVE']);
        $student = User::factory()->create(['role' => 'STUDENT', 'status' => 'ACTIVE']);
        $data = ['name' => 'New staff', 'email' => 'newstaff@example.test', 'password' => 'Password123!', 'role' => 'STAFF', 'department_id' => 3, 'status' => 'ACTIVE'];
        $this->actingAs($student, 'sanctum')->postJson('/api/v1/admin/users', $data)->assertForbidden();
        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/users', $data)->assertCreated();
        $staff = User::whereEmail($data['email'])->firstOrFail();
        $this->deleteJson('/api/v1/admin/users/'.$admin->id)->assertForbidden();
        $this->deleteJson('/api/v1/admin/users/'.$staff->id)->assertOk();
        $this->assertSoftDeleted('users', ['id' => $staff->id]);
    }

    public function test_authentication_views_render(): void
    {
        foreach (['/login', '/register', '/forgot-password', '/reset-password/example?email=test@example.test'] as $url) {
            $this->get($url)->assertOk()->assertSee('UniSupport');
        }
    }
}
