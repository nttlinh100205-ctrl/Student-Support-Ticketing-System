<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;

/**
 * Middleware kiểm tra header giả (X-User-Id, X-User-Role) có tồn tại
 * trước khi vào Controller — thay cho việc verify JWT thật (Mục 3.5).
 *
 * TODO: khi Module 1 xong, thay bằng middleware verify JWT thật
 * (kiểm tra chữ ký bằng public key) rồi mới đổi AuthContextServiceProvider.
 */
class FakeAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->hasHeader('X-User-Id') || ! $request->hasHeader('X-User-Role')) {
            return ApiResponse::error(
                'Thiếu thông tin xác thực (X-User-Id / X-User-Role).',
                401
            );
        }

        return $next($request);
    }
}
