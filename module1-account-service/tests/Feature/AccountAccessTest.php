<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Auth\JwtVerifier;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
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

    public function test_forgot_password_creates_reset_notification(): void
    {
        Notification::fake();

        $user = User::create([
            'full_name' => 'Sinh Vien Quen Mat Khau',
            'email' => 'forgot.test@university.edu.vn',
            'password' => 'password123',
            'phone' => '0900000100',
            'role' => 'student',
            'status' => 'ACTIVE',
            'department_id' => null,
        ]);

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => $user->email,
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'message',
                'Nếu email tồn tại, liên kết đặt lại mật khẩu đã được gửi.'
            );

        Notification::assertSentTo(
            $user,
            ResetPassword::class
        );
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $user = User::create([
            'full_name' => 'Sinh Vien Reset Mat Khau',
            'email' => 'reset.test@university.edu.vn',
            'password' => 'oldpassword123',
            'phone' => '0900000101',
            'role' => 'student',
            'status' => 'ACTIVE',
            'department_id' => null,
        ]);

        $token = Password::broker()
            ->createToken($user);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'NewPassword@123',
            'password_confirmation' => 'NewPassword@123',
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'message',
                'Đặt lại mật khẩu thành công.'
            );

        $this->assertTrue(
            Hash::check(
                'NewPassword@123',
                $user->fresh()->password
            )
        );
    }

    public function test_reset_password_rejects_invalid_token(): void
    {
        $user = User::create([
            'full_name' => 'Sinh Vien Token Sai',
            'email' => 'invalid.token@university.edu.vn',
            'password' => 'password123',
            'phone' => '0900000102',
            'role' => 'student',
            'status' => 'ACTIVE',
            'department_id' => null,
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'token' => 'invalid-token',
            'password' => 'NewPassword@123',
            'password_confirmation' => 'NewPassword@123',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath(
                'message',
                'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.'
            );
    }

    public function test_user_can_upload_avatar(): void
    {
        Storage::fake('public');

        $user = User::create([
            'full_name' => 'Sinh Vien Avatar',
            'email' => 'avatar.test@university.edu.vn',
            'password' => 'password123',
            'phone' => '0900000200',
            'role' => 'student',
            'status' => 'ACTIVE',
            'department_id' => null,
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('data.token');

        $imageContent = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl2ZJ0AAAAASUVORK5CYII='
        );

        $avatar = UploadedFile::fake()
            ->createWithContent(
                'avatar.png',
                $imageContent
            );

        $response = $this
            ->withHeader(
                'Authorization',
                'Bearer '.$token
            )
            ->post(
                '/api/profile/avatar',
                [
                    'avatar' => $avatar,
                ],
                [
                    'Accept' => 'application/json',
                ]
            );

        $response
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'message',
                'Cap nhat anh dai dien thanh cong.'
            );

        $avatarPath = $response->json(
            'data.avatar'
        );

        $this->assertNotEmpty(
            $avatarPath
        );

        $this->assertSame(
            $avatarPath,
            $user->fresh()->avatar
        );

        Storage::disk('public')
            ->assertExists(
                $avatarPath
            );
    }

    public function test_uploading_new_avatar_deletes_old_avatar(): void
    {
        Storage::fake('public');

        Storage::disk('public')->put(
            'avatars/old-avatar.png',
            'old-avatar-content'
        );

        $user = User::create([
            'full_name' => 'Sinh Vien Doi Avatar',
            'email' => 'replace.avatar@university.edu.vn',
            'password' => 'password123',
            'phone' => '0900000201',
            'avatar' => 'avatars/old-avatar.png',
            'role' => 'student',
            'status' => 'ACTIVE',
            'department_id' => null,
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $token = $loginResponse->json(
            'data.token'
        );

        $imageContent = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl2ZJ0AAAAASUVORK5CYII='
        );

        $newAvatar = UploadedFile::fake()
            ->createWithContent(
                'new-avatar.png',
                $imageContent
            );

        $response = $this
            ->withHeader(
                'Authorization',
                'Bearer '.$token
            )
            ->post(
                '/api/profile/avatar',
                [
                    'avatar' => $newAvatar,
                ],
                [
                    'Accept' => 'application/json',
                ]
            );

        $response
            ->assertStatus(200)
            ->assertJsonPath(
                'success',
                true
            );

        $newAvatarPath =
            $response->json(
                'data.avatar'
            );

        $this->assertNotSame(
            'avatars/old-avatar.png',
            $newAvatarPath
        );

        Storage::disk('public')
            ->assertMissing(
                'avatars/old-avatar.png'
            );

        Storage::disk('public')
            ->assertExists(
                $newAvatarPath
            );

        $this->assertSame(
            $newAvatarPath,
            $user->fresh()->avatar
        );
    }

    public function test_avatar_upload_rejects_invalid_file(): void
    {
        Storage::fake('public');

        $user = User::create([
            'full_name' => 'Sinh Vien Avatar Sai',
            'email' => 'invalid.avatar@university.edu.vn',
            'password' => 'password123',
            'phone' => '0900000202',
            'role' => 'student',
            'status' => 'ACTIVE',
            'department_id' => null,
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $token = $loginResponse->json(
            'data.token'
        );

        $invalidFile = UploadedFile::fake()
            ->create(
                'avatar.txt',
                10,
                'text/plain'
            );

        $response = $this
            ->withHeader(
                'Authorization',
                'Bearer '.$token
            )
            ->post(
                '/api/profile/avatar',
                [
                    'avatar' => $invalidFile,
                ],
                [
                    'Accept' => 'application/json',
                ]
            );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(
                'avatar'
            );

        $this->assertNull(
            $user->fresh()->avatar
        );
    }

    public function test_login_returns_must_change_password_flag(): void
    {
        $user = User::create([
            'full_name' => 'Sinh Vien Doi Mat Khau',
            'email' => 'first.login@university.edu.vn',
            'password' => 'password123',
            'phone' => '0900000300',
            'must_change_password' => true,
            'role' => 'student',
            'status' => 'ACTIVE',
            'department_id' => null,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.user.must_change_password',
                true
            );

        $this->assertNotEmpty(
            $response->json('data.token')
        );
    }

    public function test_user_must_change_password_before_accessing_profile(): void
    {
        $user = User::create([
            'full_name' => 'Sinh Vien Bi Chan Profile',
            'email' => 'blocked.profile@university.edu.vn',
            'password' => 'password123',
            'phone' => '0900000301',
            'must_change_password' => true,
            'role' => 'student',
            'status' => 'ACTIVE',
            'department_id' => null,
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $token = $loginResponse->json(
            'data.token'
        );

        $response = $this
            ->withHeader(
                'Authorization',
                'Bearer '.$token
            )
            ->getJson('/api/profile');

        $response
            ->assertStatus(403)
            ->assertExactJson([
                'success' => false,
                'message' => 'Ban phai doi mat khau truoc khi tiep tuc.',
            ]);
    }

    public function test_user_can_change_required_password_and_clear_flag(): void
    {
        $user = User::create([
            'full_name' => 'Sinh Vien Doi Password',
            'email' => 'change.required@university.edu.vn',
            'password' => 'password123',
            'phone' => '0900000302',
            'must_change_password' => true,
            'role' => 'student',
            'status' => 'ACTIVE',
            'department_id' => null,
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $token = $loginResponse->json(
            'data.token'
        );

        $response = $this
            ->withHeader(
                'Authorization',
                'Bearer '.$token
            )
            ->putJson('/api/profile/password', [
                'current_password' => 'password123',
                'password' => 'NewPassword@123',
                'password_confirmation' => 'NewPassword@123',
            ]);

        $response
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'message',
                'Doi mat khau thanh cong.'
            );

        $freshUser = $user->fresh();

        $this->assertFalse(
            $freshUser->must_change_password
        );

        $this->assertTrue(
            Hash::check(
                'NewPassword@123',
                $freshUser->password
            )
        );
    }

    public function test_user_can_access_profile_after_required_password_change(): void
    {
        $user = User::create([
            'full_name' => 'Sinh Vien Sau Doi Password',
            'email' => 'after.change@university.edu.vn',
            'password' => 'password123',
            'phone' => '0900000303',
            'must_change_password' => true,
            'role' => 'student',
            'status' => 'ACTIVE',
            'department_id' => null,
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $token = $loginResponse->json(
            'data.token'
        );

        $this
            ->withHeader(
                'Authorization',
                'Bearer '.$token
            )
            ->putJson('/api/profile/password', [
                'current_password' => 'password123',
                'password' => 'NewPassword@456',
                'password_confirmation' => 'NewPassword@456',
            ])
            ->assertStatus(200);

        $response = $this
            ->withHeader(
                'Authorization',
                'Bearer '.$token
            )
            ->getJson('/api/profile');

        $response
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.email',
                'after.change@university.edu.vn'
            )
            ->assertJsonPath(
                'data.must_change_password',
                false
            );
    }
}
