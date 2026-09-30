<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Services\Auth\AuthService;
use App\Services\Auth\Jwt\JwtIssuer;
use App\Services\Auth\PasswordResetService;
use App\Services\Auth\Token\RefreshTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\AvatarUploadRequest;
use App\Services\Auth\AvatarService;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly JwtIssuer $jwtIssuer,
        private readonly RefreshTokenService $refreshTokenService,
        private readonly PasswordResetService $passwordResetService,
private readonly AvatarService $avatarService,
    ) {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->register($request);

            return ApiResponse::success(
                $result,
                'Đăng ký tài khoản thành công.',
                201
            );
        } catch (RuntimeException $e) {
            return ApiResponse::error(
                $e->getMessage(),
                400
            );
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login($request);

            return ApiResponse::success(
                $result,
                'Đăng nhập thành công.'
            );
        } catch (RuntimeException $e) {
            return ApiResponse::error(
                $e->getMessage(),
                401
            );
        }
    }

    public function refresh(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'refresh_token' => [
                    'required',
                    'string',
                ],
            ]);

            $current = $this->refreshTokenService->findUsable(
                $validated['refresh_token']
            );

            $rotated = $this->refreshTokenService->rotate(
                current: $current,
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
                deviceName: $request->header('X-Device-Name'),
            );

            return ApiResponse::success(
                [
                    'access_token' => $this->jwtIssuer->issue(
                        $current->user
                    ),
                    'token_type' => 'Bearer',
                    'expires_in' => (int) config('jwt.ttl', 900),
                    'refresh_token' => $rotated['token'],
                    'refresh_expires_in' => (int) config(
                        'jwt.refresh_ttl',
                        604800
                    ),
                ],
                'Refresh token thành công.'
            );
        } catch (RuntimeException $e) {
            return ApiResponse::error(
                $e->getMessage(),
                401
            );
        }
    }

    public function logout(Request $request): JsonResponse
    {
        $refreshToken = $request
            ->string('refresh_token')
            ->toString();

        if ($refreshToken !== '') {
            $this->refreshTokenService->revoke($refreshToken);
        }

        return ApiResponse::success(
            null,
            'Đăng xuất thành công.'
        );
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $payload = $request->attributes->get('jwt');

        if (
            !is_array($payload) ||
            !isset($payload['sub'])
        ) {
            return ApiResponse::error(
                'Không xác định được người dùng.',
                401
            );
        }

        $user = User::query()->find(
            (int) $payload['sub']
        );

        if ($user === null) {
            return ApiResponse::error(
                'Người dùng không tồn tại.',
                401
            );
        }

        $this->refreshTokenService->revokeAllForUser($user);

        return ApiResponse::success(
            null,
            'Đã đăng xuất khỏi tất cả thiết bị.'
        );
    }

    public function forgotPassword(
        ForgotPasswordRequest $request
    ): JsonResponse {
        try {
            $email = $request
                ->string('email')
                ->toString();

            $this->passwordResetService->requestReset(
                email: $email,
                ipAddress: $request->ip(),
            );

            return ApiResponse::success(
                null,
                'Nếu email tồn tại, hướng dẫn đặt lại mật khẩu đã được gửi.'
            );
        } catch (RuntimeException $e) {
            return ApiResponse::error(
                $e->getMessage(),
                400
            );
        }
    }

    public function resetPassword(
        ResetPasswordRequest $request
    ): JsonResponse {
        try {
            $user = $this->passwordResetService->resetPassword(
                email: $request->string('email')->toString(),
                rawToken: $request->string('token')->toString(),
                newPassword: $request->string('password')->toString(),
                ipAddress: $request->ip(),
            );

            return ApiResponse::success(
                [
                    'user' => $user,
                ],
                'Đặt lại mật khẩu thành công.'
            );
        } catch (RuntimeException $e) {
            return ApiResponse::error(
                $e->getMessage(),
                400
            );
        }
    }
public function changePassword(
    ChangePasswordRequest $request
): JsonResponse {
    try {
        $payload = $request->attributes->get('jwt');

        if (
            !is_array($payload) ||
            !isset($payload['sub'])
        ) {
            return ApiResponse::error(
                'Không xác định được người dùng.',
                401
            );
        }

        $user = User::query()->find(
            (int) $payload['sub']
        );

        if ($user === null) {
            return ApiResponse::error(
                'Người dùng không tồn tại.',
                401
            );
        }

        $updatedUser = $this->authService->changePassword(
            user: $user,
            currentPassword: $request
                ->string('current_password')
                ->toString(),
            newPassword: $request
                ->string('new_password')
                ->toString(),
        );

        return ApiResponse::success(
            [
                'user' => $updatedUser,
                'must_change_password' => false,
            ],
            'Đổi mật khẩu thành công.'
        );
    } catch (RuntimeException $e) {
        return ApiResponse::error(
            $e->getMessage(),
            400
        );
    }
}
public function uploadAvatar(
    AvatarUploadRequest $request
): JsonResponse {
    try {
        $payload = $request->attributes->get('jwt');

        if (
            !is_array($payload) ||
            !isset($payload['sub'])
        ) {
            return ApiResponse::error(
                'Không xác định được người dùng.',
                401
            );
        }

        $user = User::query()->find(
            (int) $payload['sub']
        );

        if ($user === null) {
            return ApiResponse::error(
                'Người dùng không tồn tại.',
                401
            );
        }

        $updatedUser = $this->avatarService->upload(
            user: $user,
            file: $request->file('avatar'),
        );

        return ApiResponse::success(
            [
                'user' => $updatedUser,
                'avatar_path' => $updatedUser->avatar_path,
            ],
            'Cập nhật ảnh đại diện thành công.'
        );
    } catch (\Throwable $e) {
        return ApiResponse::error(
            $e->getMessage(),
            400
        );
    }
}
}