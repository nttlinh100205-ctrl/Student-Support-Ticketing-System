<?php

namespace App\Services\Auth\Audit;

use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Http\Request;

class LoginAuditService
{
    public function log(
        ?User $user,
        ?string $email,
        string $event,
        string $status,
        ?string $failureReason = null
    ): LoginLog {
        $request = request();

        return LoginLog::create([
            'user_id' => $user?->id,
            'email' => $email,
            'event' => $event,
            'status' => $status,
            'failure_reason' => $failureReason,
            'ip_address' => $request instanceof Request
                ? $request->ip()
                : null,
            'user_agent' => $request instanceof Request
                ? $request->userAgent()
                : null,
            'logged_at' => now(),
        ]);
    }
}