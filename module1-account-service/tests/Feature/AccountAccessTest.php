<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Auth\JwtVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_and_receive_valid_jwt(): void
    {
        User::create([
            'full_name' => 'Quan Tri Vien',
            'email' => 'admin@university.edu.vn',
            'password' => 'password123',
            'phone' => '0900000001',
            'role' => 'admin',
            'status' => 'ACTIVE',
            'department_id' => null,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@university.edu.vn',
            'password' => 'password123',
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', 1)
            ->assertJsonPath('data.user.full_name', 'Quan Tri Vien')
            ->assertJsonPath('data.user.email', 'admin@university.edu.vn')
            ->assertJsonPath('data.user.role', 'admin')
            ->assertJsonPath('message', null);

        $token = $response->json('data.token');

        $this->assertNotEmpty($token);

        $claims = app(JwtVerifier::class)->verify($token);

        $this->assertSame([
            'sub',
            'role',
            'department_id',
            'email',
            'full_name',
            'iat',
            'exp',
        ], array_keys($claims));

        $this->assertSame(1, $claims['sub']);
        $this->assertSame('admin', $claims['role']);
        $this->assertNull($claims['department_id']);
        $this->assertSame('admin@university.edu.vn', $claims['email']);
        $this->assertSame('Quan Tri Vien', $claims['full_name']);
        $this->assertSame(3600, $claims['exp'] - $claims['iat']);
    }

    public function test_student_cannot_access_admin_user_list(): void
    {
        User::create([
            'full_name' => 'Sinh Vien Test',
            'email' => 'student@university.edu.vn',
            'password' => 'password123',
            'phone' => '0900000002',
            'role' => 'student',
            'status' => 'ACTIVE',
            'department_id' => null,
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'student@university.edu.vn',
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('data.token');

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/users');

        $response
            ->assertStatus(403)
            ->assertExactJson([
                'success' => false,
                'message' => 'Ban khong co quyen truy cap chuc nang nay.',
            ]);
    }

    public function test_profile_requires_jwt_token(): void
    {
        $response = $this->getJson('/api/profile');

        $response
            ->assertStatus(401)
            ->assertExactJson([
                'success' => false,
                'message' => 'Token xac thuc khong duoc cung cap.',
            ]);
    }

    public function test_profile_rejects_invalid_jwt_token(): void
    {
        $response = $this
            ->withHeader('Authorization', 'Bearer abc.def.xyz')
            ->getJson('/api/profile');

        $response
            ->assertStatus(401)
            ->assertExactJson([
                'success' => false,
                'message' => 'Token xac thuc khong hop le hoac da het han.',
            ]);
    }

    public function test_invalid_user_status_returns_api_response_validation_error(): void
    {
        $admin = User::create([
            'full_name' => 'Quan Tri Vien',
            'email' => 'admin@university.edu.vn',
            'password' => 'password123',
            'phone' => '0900000001',
            'role' => 'admin',
            'status' => 'ACTIVE',
            'department_id' => null,
        ]);

        $student = User::create([
            'full_name' => 'Sinh Vien Test',
            'email' => 'student@university.edu.vn',
            'password' => 'password123',
            'phone' => '0900000002',
            'role' => 'student',
            'status' => 'ACTIVE',
            'department_id' => null,
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('data.token');

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/users/'.$student->id.'/status', [
                'status' => 'ABC',
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Du lieu khong hop le.')
            ->assertJsonValidationErrors('status');
    }

    public function test_user_not_found_returns_api_response_404(): void
    {
        $admin = User::create([
            'full_name' => 'Quan Tri Vien',
            'email' => 'admin@university.edu.vn',
            'password' => 'password123',
            'phone' => '0900000001',
            'role' => 'admin',
            'status' => 'ACTIVE',
            'department_id' => null,
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('data.token');

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/users/999999');

        $response
            ->assertStatus(404)
            ->assertExactJson([
                'success' => false,
                'message' => 'Khong tim thay tai nguyen.',
            ]);
    }

    public function test_locked_account_cannot_login(): void
    {
        User::create([
            'full_name' => 'Sinh Vien Bi Khoa',
            'email' => 'locked@university.edu.vn',
            'password' => 'password123',
            'phone' => '0900000099',
            'role' => 'student',
            'status' => 'LOCKED',
            'department_id' => null,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'locked@university.edu.vn',
            'password' => 'password123',
        ]);

        $response
            ->assertStatus(403)
            ->assertExactJson([
                'success' => false,
                'message' => 'Tai khoan da bi khoa.',
            ]);
    }

    public function test_public_registration_creates_active_student_account(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'full_name' => 'Sinh Vien Dang Ky',
            'email' => 'register.test@university.edu.vn',
            'phone' => '0900000003',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.full_name', 'Sinh Vien Dang Ky')
            ->assertJsonPath('data.user.email', 'register.test@university.edu.vn')
            ->assertJsonPath('data.user.role', 'student')
            ->assertJsonPath('message', 'Dang ky tai khoan thanh cong.');

        $this->assertDatabaseHas('users', [
            'email' => 'register.test@university.edu.vn',
            'role' => 'student',
            'status' => 'ACTIVE',
            'department_id' => null,
        ]);
    }

    public function test_admin_can_change_student_role_to_staff_with_department(): void
    {
        $admin = User::create([
            'full_name' => 'Quan Tri Vien',
            'email' => 'admin@university.edu.vn',
            'password' => 'password123',
            'phone' => '0900000001',
            'role' => 'admin',
            'status' => 'ACTIVE',
            'department_id' => null,
        ]);

        $student = User::create([
            'full_name' => 'Sinh Vien Test',
            'email' => 'student@university.edu.vn',
            'password' => 'password123',
            'phone' => '0900000002',
            'role' => 'student',
            'status' => 'ACTIVE',
            'department_id' => null,
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('data.token');

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/users/'.$student->id.'/role', [
                'role' => 'staff',
                'department_id' => 1,
            ]);

        $response
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.role', 'staff')
            ->assertJsonPath('data.department_id', 1)
            ->assertJsonPath('message', 'Thay doi quyen tai khoan thanh cong.');

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'role' => 'staff',
            'department_id' => 1,
        ]);
    }

    public function test_staff_role_requires_department_id(): void
    {
        $admin = User::create([
            'full_name' => 'Quan Tri Vien',
            'email' => 'admin@university.edu.vn',
            'password' => 'password123',
            'phone' => '0900000001',
            'role' => 'admin',
            'status' => 'ACTIVE',
            'department_id' => null,
        ]);

        $student = User::create([
            'full_name' => 'Sinh Vien Test',
            'email' => 'student@university.edu.vn',
            'password' => 'password123',
            'phone' => '0900000002',
            'role' => 'student',
            'status' => 'ACTIVE',
            'department_id' => null,
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('data.token');

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/users/'.$student->id.'/role', [
                'role' => 'staff',
            ]);

        $response
            ->assertStatus(422)
            ->assertExactJson([
                'success' => false,
                'message' => 'STAFF va DEPARTMENT_HEAD phai thuoc mot phong ban.',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'role' => 'student',
            'department_id' => null,
        ]);
    }

    public function test_admin_cannot_lock_own_account(): void
    {
        $admin = User::create([
            'full_name' => 'Quan Tri Vien',
            'email' => 'admin@university.edu.vn',
            'password' => 'password123',
            'phone' => '0900000001',
            'role' => 'admin',
            'status' => 'ACTIVE',
            'department_id' => null,
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('data.token');

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/users/'.$admin->id.'/status', [
                'status' => 'LOCKED',
            ]);

        $response
            ->assertStatus(403)
            ->assertExactJson([
                'success' => false,
                'message' => 'Khong the tu khoa tai khoan cua chinh minh.',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role' => 'admin',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_admin_cannot_change_own_role(): void
    {
        $admin = User::create([
            'full_name' => 'Quan Tri Vien',
            'email' => 'admin@university.edu.vn',
            'password' => 'password123',
            'phone' => '0900000001',
            'role' => 'admin',
            'status' => 'ACTIVE',
            'department_id' => null,
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('data.token');

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/users/'.$admin->id.'/role', [
                'role' => 'student',
                'department_id' => null,
            ]);

        $response
            ->assertStatus(403)
            ->assertExactJson([
                'success' => false,
                'message' => 'Khong the tu thay doi quyen cua chinh minh.',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role' => 'admin',
            'status' => 'ACTIVE',
        ]);
    }
}
