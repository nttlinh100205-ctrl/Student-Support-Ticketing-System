<?php

namespace App\Services\Auth;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\Auth\Audit\LoginAuditService;
use App\Services\Auth\Jwt\JwtIssuer;
use App\Services\Auth\Lockout\LoginLockoutService;
use App\Services\Auth\Token\RefreshTokenService;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AuthService
{
    public function __construct(
        private readonly JwtIssuer $jwtIssuer,
        private readonly LoginLockoutService $lockoutService,
        private readonly LoginAuditService $auditService,
        private readonly RefreshTokenService $refreshTokenService,
    ) {
    }

    /**
     * Register a new student account.
     *
     * @return array<string, mixed>
     */
    public function register(RegisterRequest $request): array
    {
        $fullName = $request->string('full_name')->toString();
        $email = strtolower(
            $request->string('email')->toString()
        );

        $user = User::create([
            'name' => $fullName,
            'full_name' => $fullName,
            'email' => $email,
            'password' => $request->string('password')->toString(),
            'role' => 'student',
            'department_id' => null,
            'avatar_path' => null,
            'is_active' => true,
            'must_change_password' => false,
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);

        return [
            'user' => $user,
        ];
    }

    /**
     * Authenticate a user and issue an access token
     * together with a refresh token.
     *
     * @return array<string, mixed>
     */
    public function login(LoginRequest $request): array
    {
        $email = strtolower(
            $request->string('email')->toString()
        );

        $password = $request->string('password')->toString();

        $user = User::query()
            ->where('email', $email)
            ->first();

        if ($user === null) {
            $this->auditService->log(
                user: null,
                email: $email,
                event: 'LOGIN',
                status: 'FAILED',
                failureReason: 'INVALID_CREDENTIALS'
            );

            throw new RuntimeException(
                'Email hoặc mật khẩu không đúng.'
            );
        }

        if (!$user->is_active) {
            $this->auditService->log(
                user: $user,
                email: $email,
                event: 'LOGIN',
                status: 'BLOCKED',
                failureReason: 'ACCOUNT_INACTIVE'
            );

            throw new RuntimeException(
                'Tài khoản đang bị vô hiệu hóa.'
            );
        }

        if ($this->lockoutService->isLocked($user)) {
            $this->auditService->log(
                user: $user,
                email: $email,
                event: 'LOGIN',
                status: 'BLOCKED',
                failureReason: 'ACCOUNT_LOCKED'
            );

            throw new RuntimeException(
                'Tài khoản đang bị khóa tạm thời.'
            );
        }

        if (!Hash::check($password, $user->password)) {
            $locked = $this->lockoutService->recordFailure($user);

            $this->auditService->log(
                user: $user,
                email: $email,
                event: 'LOGIN',
                status: $locked ? 'BLOCKED' : 'FAILED',
                failureReason: $locked
                    ? 'TOO_MANY_FAILED_ATTEMPTS'
                    : 'INVALID_CREDENTIALS'
            );

            if ($locked) {
                throw new RuntimeException(
                    'Tài khoản đã bị khóa tạm thời do đăng nhập sai quá nhiều lần.'
                );
            }

            throw new RuntimeException(
                'Email hoặc mật khẩu không đúng.'
            );
        }

        // Đăng nhập đúng → reset trạng thái lockout.
        $this->lockoutService->reset($user);

        // Tạo Access JWT.
        $accessToken = $this->jwtIssuer->issue($user);

        // Tạo Refresh Token và chỉ lưu hash trong DB.
        $refreshToken = $this->refreshTokenService->create(
            user: $user,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            deviceName: $request->header('X-Device-Name'),
        );

        // Ghi audit log đăng nhập thành công.
        $this->auditService->log(
            user: $user,
            email: $email,
            event: 'LOGIN',
            status: 'SUCCESS'
        );

        return [
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => (int) config('jwt.ttl', 900),

            'refresh_token' => $refreshToken['token'],
            'refresh_expires_in' => (int) config(
                'jwt.refresh_ttl',
                604800
            ),

            'must_change_password' => (bool) $user->must_change_password,

            'user' => $user->fresh(),
        ];
    }
public function changePassword(
    User $user,
    string $currentPassword,
    string $newPassword,
): User {
    if (!Hash::check($currentPassword, $user->password)) {
        throw new RuntimeException(
            'Mật khẩu hiện tại không đúng.'
        );
    }

    if ($currentPassword === $newPassword) {
        throw new RuntimeException(
            'Mật khẩu mới phải khác mật khẩu hiện tại.'
        );
    }

    $user->forceFill([
        'password' => $newPassword,
        'must_change_password' => false,
        'failed_login_attempts' => 0,
        'locked_until' => null,
    ])->save();

    // Đổi mật khẩu phải đăng xuất các phiên đăng nhập khác.
    $this->refreshTokenService->revokeAllForUser($user);

    return $user->fresh();
}
}