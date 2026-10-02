<?php

namespace App\Http\Middleware;

use App\Contracts\AuthContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function __construct(protected AuthContext $auth)
    {
    }

    /**
     * Kiểm tra vai trò người gọi API.
     *
     * Ví dụ:
     * ->middleware('role:admin')
     * ->middleware('role:admin,staff')
     */
    public function handle(
        Request $request,
        Closure $next,
        ...$roles
    ): Response {
        $roles = array_map('strtolower', $roles);

        if (! in_array($this->auth->role(), $roles, true)) {
            return response()->json([
                'message' => 'Bạn không có quyền truy cập chức năng này.',
            ], 403);
        }

        return $next($request);
    }
}
