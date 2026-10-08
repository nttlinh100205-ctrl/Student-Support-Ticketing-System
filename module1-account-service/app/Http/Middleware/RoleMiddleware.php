<?php

namespace App\Http\Middleware;

use App\Contracts\AuthContext;
use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function __construct(
        private readonly AuthContext $authContext
    ) {}

    public function handle(
        Request $request,
        Closure $next,
        ...$roles
    ): Response {
        $currentRole = strtolower(
            $this->authContext->role()
        );

        $allowedRoles = array_map(
            'strtolower',
            $roles
        );

        if (! in_array($currentRole, $allowedRoles, true)) {
            return ApiResponse::error(
                'Ban khong co quyen truy cap chuc nang nay.',
                403
            );
        }

        return $next($request);
    }
}
