<?php

namespace App\Http\Middleware;

use App\Contracts\AuthContext;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChangedMiddleware
{
    public function __construct(
        private readonly AuthContext $authContext
    ) {}

    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = User::query()->find(
            $this->authContext->userId()
        );

        if ($user === null) {
            return ApiResponse::error(
                'Tai khoan khong ton tai.',
                404
            );
        }

        if ($user->must_change_password) {
            return ApiResponse::error(
                'Ban phai doi mat khau truoc khi tiep tuc.',
                403
            );
        }

        return $next($request);
    }
}
