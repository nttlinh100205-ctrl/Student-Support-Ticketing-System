<?php

namespace App\Services\Auth;

use App\Contracts\AuthContext;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AuthService
{
    public function __construct(
        private readonly JwtIssuer $jwtIssuer,
        private readonly RefreshTokenService $refreshTokenService,
        private readonly AuthContext $authContext
    ) {}

    public function register(array $data): array
    {
        $user = User::create([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'must_change_password' => false,
            'role' => 'student',
            'status' => 'ACTIVE',
            'department_id' => null,
        ]);

        return [
            'user' => $this->userData($user),
        ];
    }

    public function login(
        array $data,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): array {
        $user = User::query()
            ->where('email', $data['email'])
            ->first();

        if (
            $user === null ||
            ! Hash::check(
                $data['password'],
                $user->password
            )
        ) {
            throw new RuntimeException(
                'Email hoac mat khau khong dung.'
            );
        }

        if ($user->status !== 'ACTIVE') {
            throw new RuntimeException(
                'Tai khoan da bi khoa.'
            );
        }

        return $this->createAuthPayload(
            $user,
            $ipAddress,
            $userAgent
        );
    }

    public function refresh(
        array $data,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): array {
        $result =
            $this->refreshTokenService->rotate(
                $data['refresh_token'],
                $ipAddress,
                $userAgent
            );

        /** @var User $user */
        $user = $result['user'];

        return [
            'token' => $this->jwtIssuer->issue(
                $user
            ),
            'refresh_token' => $result['refresh_token'],
            'expires_in' => (int) config(
                'jwt.ttl',
                3600
            ),
            'user' => $this->userData($user),
        ];
    }

    public function logoutAll(): void
    {
        $user = User::query()->find(
            $this->authContext->userId()
        );

        if ($user === null) {
            throw new RuntimeException(
                'Tai khoan khong ton tai.'
            );
        }

        DB::transaction(function () use ($user) {
            /*
             * Tang auth_version de tat ca JWT access token
             * dang ton tai lap tuc het hieu luc.
             */
            $user->increment(
                'auth_version'
            );

            $this->refreshTokenService
                ->revokeAllForUser($user);
        });
    }

    private function createAuthPayload(
        User $user,
        ?string $ipAddress,
        ?string $userAgent
    ): array {
        return [
            'token' => $this->jwtIssuer->issue(
                $user
            ),

            'refresh_token' => $this->refreshTokenService->issue(
                $user,
                $ipAddress,
                $userAgent
            ),

            'expires_in' => (int) config(
                'jwt.ttl',
                3600
            ),

            'user' => $this->userData($user),
        ];
    }

    private function userData(User $user): array
    {
        return [
            'id' => (int) $user->id,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'role' => strtolower($user->role),
            'must_change_password' => (bool) $user->must_change_password,
        ];
    }
}
