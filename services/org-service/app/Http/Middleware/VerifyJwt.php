<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use App\Services\Auth\JwtVerifier;
use Closure;
use Illuminate\Http\Request;
use Throwable;

class VerifyJwt
{
    public function __construct(
        protected JwtVerifier $verifier
    ) {
    }

    public function handle(
        Request $request,
        Closure $next
    ) {
        $header = $request->header(
            'Authorization',
            ''
        );

        if (
            !preg_match(
                '/^Bearer\s+(.+)$/i',
                $header,
                $matches
            )
        ) {
            return ApiResponse::error(
                'Thiếu JWT Bearer token.',
                401
            );
        }

        try {
            $claims = $this->verifier->verify(
                $matches[1]
            );

            $request->attributes->set(
                'jwt.claims',
                $claims
            );
        } catch (Throwable) {
            return ApiResponse::error(
                'JWT không hợp lệ hoặc đã hết hạn.',
                401
            );
        }

        return $next($request);
    }
}