<?php

namespace App\Http\Middleware;

use App\Services\Auth\Jwt\JwtVerifier;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class VerifyJwt
{
    public function __construct(
        private readonly JwtVerifier $jwtVerifier
    ) {
    }

    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $header = $request->header('Authorization');

        if (
            !$header ||
            !preg_match('/^Bearer\s+(.+)$/i', $header, $matches)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Thiếu JWT Bearer token.',
            ], 401);
        }

        try {
            $payload = $this->jwtVerifier->verify($matches[1]);

            $request->attributes->set('jwt', $payload);
        } catch (Throwable) {
            return response()->json([
                'success' => false,
                'message' => 'JWT không hợp lệ hoặc đã hết hạn.',
            ], 401);
        }

        return $next($request);
    }
}