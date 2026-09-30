<?php

namespace App\Services\Auth\Lockout;

use App\Models\User;
use Carbon\Carbon;

class LoginLockoutService
{
    private const MAX_ATTEMPTS = 5;

    private const LOCKOUT_MINUTES = 15;

    public function isLocked(User $user): bool
    {
        if ($user->locked_until === null) {
            return false;
        }

        if ($user->locked_until->isFuture()) {
            return true;
        }

        $user->forceFill([
            'locked_until' => null,
            'failed_login_attempts' => 0,
        ])->save();

        return false;
    }

    public function recordFailure(User $user): bool
    {
        $attempts = $user->failed_login_attempts + 1;

        if ($attempts >= self::MAX_ATTEMPTS) {
            $user->forceFill([
                'failed_login_attempts' => $attempts,
                'locked_until' => Carbon::now()->addMinutes(
                    self::LOCKOUT_MINUTES
                ),
            ])->save();

            return true;
        }

        $user->forceFill([
            'failed_login_attempts' => $attempts,
        ])->save();

        return false;
    }

    public function reset(User $user): void
    {
        $user->forceFill([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ])->save();
    }

    public function maxAttempts(): int
    {
        return self::MAX_ATTEMPTS;
    }

    public function lockoutMinutes(): int
    {
        return self::LOCKOUT_MINUTES;
    }
}