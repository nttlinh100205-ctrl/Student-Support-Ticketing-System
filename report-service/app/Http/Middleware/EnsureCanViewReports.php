<?php

namespace App\Http\Middleware;

use App\Contracts\AuthContextInterface;
use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;

class EnsureCanViewReports
{
    public function __construct(private AuthContextInterface $auth) {}

    public function handle(Request $request, Closure $next)
    {
        $role = $this->auth->role();

        if (! $role || ! in_array($role, ['staff', 'department_head', 'admin'], true)) {
            return ApiResponse::error('Bạn không có quyền xem báo cáo và thống kê.', 403);
        }

        return $next($request);
    }
}
