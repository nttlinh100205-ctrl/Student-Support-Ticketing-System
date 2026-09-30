<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(
        Request $request,
        Closure $next,
        ...$roles
    ) {
        $role = strtolower(
            (string) data_get(
                $request->attributes->get(
                    'jwt.claims',
                    []
                ),
                'role'
            )
        );

        $allowedRoles = array_map(
            'strtolower',
            $roles
        );

        if (
            !in_array(
                $role,
                $allowedRoles,
                true
            )
        ) {
            return ApiResponse::error(
                'Bạn không có quyền truy cập.',
                403
            );
        }

        return $next($request);
    }
}