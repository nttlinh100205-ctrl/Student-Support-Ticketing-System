<?php

namespace App\Http\Middleware;

use App\Contracts\AuthContextInterface;
use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;

class EnsureIsStudent
{
    public function __construct(private AuthContextInterface $auth) {}

    public function handle(Request $request, Closure $next)
    {
        $role = $this->auth->role();

        if ($role !== 'student') {
            return ApiResponse::error('Chỉ sinh viên mới có quyền thực hiện đánh giá yêu cầu.', 403);
        }

        return $next($request);
    }
}
