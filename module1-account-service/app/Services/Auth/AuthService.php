<?php

namespace App\Services\Auth;

use App\Contracts\AuthContext;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AuthService
{
    private const MAX_FAILED_LOGIN_ATTEMPTS = 5;

    private const TEMPORARY_LOCK_MINUTES = 15;

    private const FAILURE_INVALID_CREDENTIALS =
        'INVALID_CREDENTIALS';

    private const FAILURE_ACCOUNT_LOCKED =
        'ACCOUNT_LOCKED';

    private const FAILURE_TEMPORARILY_LOCKED =
        'TEMPORARILY_LOCKED';

    public function __construct(
        private readonly JwtIssuer $jwtIssuer,
        private readonly RefreshTokenService $refreshTokenService,
        private readonly AuthContext $authContext,
        private readonly LoginHistoryService $loginHistoryService
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

        /*
         * Email khong ton tai.
         */
        if ($user === null) {
            $this->loginHistoryService->recordFailure(
                null,
                $data['email'],
                self::FAILURE_INVALID_CREDENTIALS,
                $ipAddress,
                $userAgent
            );

            throw new RuntimeException(
                'Email hoac mat khau khong dung.'
            );
        }

        /*
         * Tai khoan bi Admin khoa thu cong.
         */
        if ($user->status !== 'ACTIVE') {
            $this->loginHistoryService->recordFailure(
                $user,
                $data['email'],
                self::FAILURE_ACCOUNT_LOCKED,
                $ipAddress,
                $userAgent
            );

            throw new RuntimeException(
                'Tai khoan da bi khoa.'
            );
        }

        /*
         * Tai khoan dang bi khoa tam thoi.
         */
        if (
            $user->locked_until !== null &&
            $user->locked_until->isFuture()
        ) {
            $this->loginHistoryService->recordFailure(
                $user,
                $data['email'],
                self::FAILURE_TEMPORARILY_LOCKED,
                $ipAddress,
                $userAgent
            );

            throw new RuntimeException(
                'Tai khoan tam thoi bi khoa do dang nhap sai qua nhieu lan.'
            );
        }

        /*
         * Neu het thoi gian khoa thi tu dong mo khoa.
         */
        if (
            $user->locked_until !== null &&
            $user->locked_until->isPast()
        ) {
            $user->update([
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ]);

            $user->refresh();
        }

        /*
         * Mat khau sai.
         */
        if (
            ! Hash::check(
                $data['password'],
                $user->password
            )
        ) {
            $failedAttempts =
                $user->failed_login_attempts + 1;

            /*
             * Sai lan thu 5.
             */
            if (
                $failedAttempts >=
                self::MAX_FAILED_LOGIN_ATTEMPTS
            ) {
                $user->update([
                    'failed_login_attempts' => self::MAX_FAILED_LOGIN_ATTEMPTS,

                    'locked_until' => now()->addMinutes(
                        self::TEMPORARY_LOCK_MINUTES
                    ),
                ]);

                $this->loginHistoryService->recordFailure(
                    $user,
                    $data['email'],
                    self::FAILURE_TEMPORARILY_LOCKED,
                    $ipAddress,
                    $userAgent
                );

                throw new RuntimeException(
                    'Tai khoan tam thoi bi khoa do dang nhap sai qua nhieu lan.'
                );
            }

            /*
             * Sai nhung chua du 5 lan.
             */
            $user->update([
                'failed_login_attempts' => $failedAttempts,
            ]);

            $this->loginHistoryService->recordFailure(
                $user,
                $data['email'],
                self::FAILURE_INVALID_CREDENTIALS,
                $ipAddress,
                $userAgent
            );

            throw new RuntimeException(
                'Email hoac mat khau khong dung.'
            );
        }

        /*
         * Dang nhap dung thi reset bo dem dang nhap sai.
         */
        if (
            $user->failed_login_attempts > 0 ||
            $user->locked_until !== null
        ) {
            $user->update([
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ]);

            $user->refresh();
        }

        /*
         * Chi ghi SUCCESS sau khi tao duoc token.
         */
        $payload = $this->createAuthPayload(
            $user,
            $ipAddress,
            $userAgent
        );

        $this->loginHistoryService->recordSuccess(
            $user,
            $ipAddress,
            $userAgent
        );

        return $payload;
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

            'user' => $this->userData(
                $user
            ),
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
            $user->increment(
                'auth_version'
            );

            $this->refreshTokenService
                ->revokeAllForUser(
                    $user
                );
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

            'user' => $this->userData(
                $user
            ),
        ];
    }

    private function userData(User $user): array
    {
        return [
            'id' => (int) $user->id,

            'full_name' => $user->full_name,

            'email' => $user->email,

            'role' => strtolower(
                $user->role
            ),

            'must_change_password' => (bool) $user->must_change_password,
        ];
    }
}
