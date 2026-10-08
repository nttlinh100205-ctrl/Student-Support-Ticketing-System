<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Services\Auth\JwtAuthContext;
use Closure;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthMiddleware
{
    public function __construct(
        private readonly JwtAuthContext $authContext
    ) {}

    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $token =
            $request->bearerToken();

        if (
            $token === null ||
            $token === ''
        ) {
            return ApiResponse::error(
                'Token xac thuc khong duoc cung cap.',
                401
            );
        }

        try {
            $this->authContext->setToken(
                $token
            );
        } catch (RuntimeException $exception) {
            return ApiResponse::error(
                'Token xac thuc khong hop le hoac da het han.',
                401
            );
        }

        $user = User::query()->find(
            $this->authContext->userId()
        );

        if ($user === null) {
            return ApiResponse::error(
                'Token xac thuc khong hop le hoac da het han.',
                401
            );
        }

        if ($user->status !== 'ACTIVE') {
            return ApiResponse::error(
                'Tai khoan da bi khoa.',
                403
            );
        }

        if (
            (int) $user->auth_version !==
            $this->authContext->authVersion()
        ) {
            return ApiResponse::error(
                'Phien dang nhap da het hieu luc.',
                401
            );
        }

        return $next($request);
    }
}
