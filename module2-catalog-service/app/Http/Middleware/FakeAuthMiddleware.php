<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kiểm tra header giả (X-User-Id, X-User-Role) thay cho JWT thật.
 *
 * TODO: khi Module 1 xong, thay bằng middleware verify JWT.
 */
class FakeAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasHeader('X-User-Id') || ! $request->hasHeader('X-User-Role')) {
            return ApiResponse::error('Thiếu thông tin xác thực (X-User-Id / X-User-Role).', 401);
        }

        return $next($request);
    }
}
