<?php

namespace Tests\Feature;

use App\Models\RefreshToken;
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
            'ver',
            'role',
            'department_id',
            'email',
            'full_name',
            'iat',
            'exp',
        ], array_keys($claims));

        $this->assertSame(1, $claims['sub']);
        $this->assertSame(1, $claims['ver']);
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

    public function test_login_returns_refresh_token(): void
    {
        $user = User::create([
            'full_name' => 'Sinh Vien Refresh Token',
            'email' => 'refresh.login@university.edu.vn',
            'password' => 'password123',
            'phone' => '0900000400',
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
                'data.expires_in',
                3600
            );

        $accessToken = $response->json(
            'data.token'
        );

        $refreshToken = $response->json(
            'data.refresh_token'
        );

        $this->assertNotEmpty(
            $accessToken
        );

        $this->assertNotEmpty(
            $refreshToken
        );

        $this->assertDatabaseHas(
            'refresh_tokens',
            [
                'user_id' => $user->id,
                'token_hash' => hash(
                    'sha256',
                    $refreshToken
                ),
                'revoked_at' => null,
            ]
        );
    }

    public function test_refresh_token_is_rotated_and_old_token_cannot_be_reused(): void
    {
        $user = User::create([
            'full_name' => 'Sinh Vien Rotate Refresh',
            'email' => 'rotate.refresh@university.edu.vn',
            'password' => 'password123',
            'phone' => '0900000401',
            'role' => 'student',
            'status' => 'ACTIVE',
            'department_id' => null,
        ]);

        $loginResponse = $this->postJson(
            '/api/auth/login',
            [
                'email' => $user->email,
                'password' => 'password123',
            ]
        );

        $oldRefreshToken =
            $loginResponse->json(
                'data.refresh_token'
            );

        $refreshResponse = $this->postJson(
            '/api/auth/refresh',
            [
                'refresh_token' => $oldRefreshToken,
            ]
        );

        $refreshResponse
            ->assertStatus(200)
            ->assertJsonPath(
                'success',
                true
            )
            ->assertJsonPath(
                'message',
                'Lam moi token thanh cong.'
            );

        $newAccessToken =
            $refreshResponse->json(
                'data.token'
            );

        $newRefreshToken =
            $refreshResponse->json(
                'data.refresh_token'
            );

        $this->assertNotEmpty(
            $newAccessToken
        );

        $this->assertNotEmpty(
            $newRefreshToken
        );

        $this->assertNotSame(
            $oldRefreshToken,
            $newRefreshToken
        );

        $oldTokenRecord =
            RefreshToken::query()
                ->where(
                    'token_hash',
                    hash(
                        'sha256',
                        $oldRefreshToken
                    )
                )
                ->first();

        $this->assertNotNull(
            $oldTokenRecord
        );

        $this->assertNotNull(
            $oldTokenRecord->revoked_at
        );

        $this->assertDatabaseHas(
            'refresh_tokens',
            [
                'user_id' => $user->id,
                'token_hash' => hash(
                    'sha256',
                    $newRefreshToken
                ),
                'revoked_at' => null,
            ]
        );

        /*
         * Refresh token cu khong duoc dung lai.
         */
        $reuseResponse = $this->postJson(
            '/api/auth/refresh',
            [
                'refresh_token' => $oldRefreshToken,
            ]
        );

        $reuseResponse
            ->assertStatus(401)
            ->assertJsonPath(
                'success',
                false
            )
            ->assertJsonPath(
                'message',
                'Refresh token khong hop le hoac da het han.'
            );

        /*
         * Access token moi van hoat dong.
         */
        $this
            ->withHeader(
                'Authorization',
                'Bearer '.$newAccessToken
            )
            ->getJson('/api/profile')
            ->assertStatus(200)
            ->assertJsonPath(
                'success',
                true
            );
    }

    public function test_logout_all_invalidates_all_access_and_refresh_tokens(): void
    {
        $user = User::create([
            'full_name' => 'Sinh Vien Logout All',
            'email' => 'logout.all@university.edu.vn',
            'password' => 'password123',
            'phone' => '0900000402',
            'role' => 'student',
            'status' => 'ACTIVE',
            'department_id' => null,
        ]);

        /*
         * Gia lap thiet bi thu nhat.
         */
        $deviceOneLogin = $this->postJson(
            '/api/auth/login',
            [
                'email' => $user->email,
                'password' => 'password123',
            ]
        );

        $accessTokenOne =
            $deviceOneLogin->json(
                'data.token'
            );

        $refreshTokenOne =
            $deviceOneLogin->json(
                'data.refresh_token'
            );

        /*
         * Gia lap thiet bi thu hai.
         */
        $deviceTwoLogin = $this->postJson(
            '/api/auth/login',
            [
                'email' => $user->email,
                'password' => 'password123',
            ]
        );

        $accessTokenTwo =
            $deviceTwoLogin->json(
                'data.token'
            );

        $refreshTokenTwo =
            $deviceTwoLogin->json(
                'data.refresh_token'
            );

        /*
         * Logout tat ca thiet bi tu thiet bi 1.
         */
        $logoutResponse = $this
            ->withHeader(
                'Authorization',
                'Bearer '.$accessTokenOne
            )
            ->postJson(
                '/api/auth/logout-all'
            );

        $logoutResponse
            ->assertStatus(200)
            ->assertJsonPath(
                'success',
                true
            )
            ->assertJsonPath(
                'message',
                'Dang xuat tat ca thiet bi thanh cong.'
            );

        /*
         * auth_version phai tang tu 1 len 2.
         */
        $this->assertSame(
            2,
            $user->fresh()->auth_version
        );

        /*
         * Access token thiet bi 1 bi vo hieu hoa.
         */
        $this
            ->withHeader(
                'Authorization',
                'Bearer '.$accessTokenOne
            )
            ->getJson('/api/profile')
            ->assertStatus(401)
            ->assertJsonPath(
                'message',
                'Phien dang nhap da het hieu luc.'
            );

        /*
         * Access token thiet bi 2 cung bi vo hieu hoa.
         */
        $this
            ->withHeader(
                'Authorization',
                'Bearer '.$accessTokenTwo
            )
            ->getJson('/api/profile')
            ->assertStatus(401)
            ->assertJsonPath(
                'message',
                'Phien dang nhap da het hieu luc.'
            );

        /*
         * Refresh token cua thiet bi 1 khong con dung duoc.
         */
        $this
            ->postJson(
                '/api/auth/refresh',
                [
                    'refresh_token' => $refreshTokenOne,
                ]
            )
            ->assertStatus(401)
            ->assertJsonPath(
                'success',
                false
            );

        /*
         * Refresh token cua thiet bi 2 cung khong con dung duoc.
         */
        $this
            ->postJson(
                '/api/auth/refresh',
                [
                    'refresh_token' => $refreshTokenTwo,
                ]
            )
            ->assertStatus(401)
            ->assertJsonPath(
                'success',
                false
            );

        $this->assertSame(
            0,
            RefreshToken::query()
                ->where(
                    'user_id',
                    $user->id
                )
                ->whereNull(
                    'revoked_at'
                )
                ->count()
        );
    }

    public function test_failed_login_increases_failed_attempt_counter(): void
    {
        $user = User::create([
            'full_name' => 'Sinh Vien Login Sai',
            'email' => 'failed.attempt@university.edu.vn',
            'password' => 'password123',
            'phone' => '0900000500',
            'role' => 'student',
            'status' => 'ACTIVE',
            'department_id' => null,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response
            ->assertStatus(401)
            ->assertJsonPath(
                'success',
                false
            )
            ->assertJsonPath(
                'message',
                'Email hoac mat khau khong dung.'
            );

        $freshUser = $user->fresh();

        $this->assertSame(
            1,
            $freshUser->failed_login_attempts
        );

        $this->assertNull(
            $freshUser->locked_until
        );
    }

    public function test_account_is_temporarily_locked_after_five_failed_logins(): void
    {
        $user = User::create([
            'full_name' => 'Sinh Vien Khoa Tam Thoi',
            'email' => 'temporary.lock@university.edu.vn',
            'password' => 'password123',
            'phone' => '0900000501',
            'role' => 'student',
            'status' => 'ACTIVE',
            'department_id' => null,
        ]);

        for ($attempt = 1; $attempt <= 4; $attempt++) {
            $this
                ->postJson('/api/auth/login', [
                    'email' => $user->email,
                    'password' => 'wrong-password',
                ])
                ->assertStatus(401);
        }

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response
            ->assertStatus(429)
            ->assertJsonPath(
                'success',
                false
            )
            ->assertJsonPath(
                'message',
                'Tai khoan tam thoi bi khoa do dang nhap sai qua nhieu lan.'
            );

        $freshUser = $user->fresh();

        $this->assertSame(
            5,
            $freshUser->failed_login_attempts
        );

        $this->assertNotNull(
            $freshUser->locked_until
        );

        $this->assertTrue(
            $freshUser->locked_until->isFuture()
        );
    }

    public function test_correct_password_is_rejected_while_account_is_temporarily_locked(): void
    {
        $user = User::create([
            'full_name' => 'Sinh Vien Dang Bi Khoa',
            'email' => 'locked.correct.password@university.edu.vn',
            'password' => 'password123',
            'phone' => '0900000502',
            'failed_login_attempts' => 5,
            'locked_until' => now()->addMinutes(15),
            'role' => 'student',
            'status' => 'ACTIVE',
            'department_id' => null,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response
            ->assertStatus(429)
            ->assertJsonPath(
                'success',
                false
            )
            ->assertJsonPath(
                'message',
                'Tai khoan tam thoi bi khoa do dang nhap sai qua nhieu lan.'
            );

        $freshUser = $user->fresh();

        $this->assertSame(
            5,
            $freshUser->failed_login_attempts
        );

        $this->assertNotNull(
            $freshUser->locked_until
        );
    }

    public function test_account_is_automatically_unlocked_after_lock_time_expires(): void
    {
        $user = User::create([
            'full_name' => 'Sinh Vien Het Khoa',
            'email' => 'expired.lock@university.edu.vn',
            'password' => 'password123',
            'phone' => '0900000503',
            'failed_login_attempts' => 5,
            'locked_until' => now()->addMinutes(15),
            'role' => 'student',
            'status' => 'ACTIVE',
            'department_id' => null,
        ]);

        /*
         * Gia lap thoi gian troi qua 16 phut.
         */
        $this->travel(16)->minutes();

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonPath(
                'success',
                true
            );

        $this->assertNotEmpty(
            $response->json('data.token')
        );

        $this->assertNotEmpty(
            $response->json(
                'data.refresh_token'
            )
        );

        $freshUser = $user->fresh();

        $this->assertSame(
            0,
            $freshUser->failed_login_attempts
        );

        $this->assertNull(
            $freshUser->locked_until
        );
    }
}
