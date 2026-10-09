<?php

namespace Tests\Feature;

use App\Contracts\AuthContext;
use App\Models\SupportDepartment;
use App\Services\Auth\AccountAuthContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AccountIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['account.fake' => false, 'account.url' => 'http://accounts.test']);
        $this->app->bind(AuthContext::class, AccountAuthContext::class);
        Http::preventStrayRequests();
    }

    public function test_admin_assigns_remote_user_without_a_local_account_copy(): void
    {
        $department = SupportDepartment::create(['name' => 'Phòng thật', 'code' => 'REAL', 'is_active' => true]);
        Http::fake([
            'http://accounts.test/api/v1/auth/me' => Http::response(['user' => ['id' => 1, 'name' => 'Admin', 'role' => 'ADMIN', 'status' => 'ACTIVE']]),
            'http://accounts.test/api/v1/admin/users?*' => Http::response(['data' => ['data' => [['id' => 85, 'name' => 'Staff', 'email' => 's@example.test', 'role' => 'STAFF', 'status' => 'ACTIVE', 'department_id' => null]], 'last_page' => 1]]),
            'http://accounts.test/api/v1/admin/users/85/role' => Http::response(['user' => ['id' => 85, 'role' => 'STAFF', 'department_id' => $department->id]]),
        ]);
        $this->withToken('admin-token')->putJson('/api/departments/'.$department->id.'/staff/85', ['role' => 'staff'])
            ->assertOk()->assertJsonPath('data.id', 85)->assertJsonPath('data.role', 'staff');
        $this->assertDatabaseMissing('users', ['id' => 85]);
        Http::assertSent(fn ($request) => $request->method() === 'PUT' && $request['role'] === 'STAFF' && $request['department_id'] === $department->id);
    }

    public function test_student_cannot_use_forged_admin_header_for_catalog_writes(): void
    {
        Http::fake(['http://accounts.test/*' => Http::response(['user' => ['id' => 2, 'name' => 'Student', 'role' => 'STUDENT', 'status' => 'ACTIVE']])]);
        $this->withToken('student-token')->withHeader('X-User-Role', 'admin')->postJson('/api/departments', ['name' => 'Fake'])->assertForbidden();
        Http::assertSentCount(1);
    }
}
