<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Kiểm tra quyền của người dùng.
     *
     * Ví dụ:
     * ->middleware('role:ADMIN')
     * ->middleware('role:ADMIN,STAFF')
     */
    public function handle(
        Request $request,
        Closure $next,
        ...$roles
    ): Response {
        $user = $request->user();

        // Chưa đăng nhập
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Tài khoản không có quyền được yêu cầu
        if (!in_array($user->role, $roles, true)) {
            return response()->json([
                'message' => 'Ban khong co quyen truy cap chuc nang nay.',
            ], 403);
        }

        return $next($request);
    }
}