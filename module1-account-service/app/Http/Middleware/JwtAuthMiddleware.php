<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
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
        $token = $request->bearerToken();

        if ($token === null || $token === '') {
            return ApiResponse::error(
                'Token xac thuc khong duoc cung cap.',
                401
            );
        }

        try {
            $this->authContext->setToken($token);
        } catch (RuntimeException $exception) {
            return ApiResponse::error(
                'Token xac thuc khong hop le hoac da het han.',
                401
            );
        }

        return $next($request);
    }
}
