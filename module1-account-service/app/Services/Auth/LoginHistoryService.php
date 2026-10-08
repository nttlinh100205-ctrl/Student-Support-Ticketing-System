<?php

namespace App\Services\Auth;

use App\Models\LoginHistory;
use App\Models\User;

class LoginHistoryService
{
    public function recordSuccess(
        User $user,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void {
        LoginHistory::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'success' => true,
            'failure_reason' => null,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    public function recordFailure(
        ?User $user,
        string $email,
        string $reason,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void {
        LoginHistory::create([
            'user_id' => $user?->id,
            'email' => $email,
            'success' => false,
            'failure_reason' => $reason,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }
}
