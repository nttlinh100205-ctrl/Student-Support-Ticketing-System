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
        if (! in_array($this->auth->role(), ['staff', 'admin'], true)) {
            return ApiResponse::error('Ban khong co quyen xem bao cao.', 403);
        }

        return $next($request);
    }
}