<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function register(
        RegisterRequest $request
    ): JsonResponse {
        $data = $this->authService->register(
            $request->validated()
        );

        return ApiResponse::success(
            $data,
            'Dang ky tai khoan thanh cong.',
            201
        );
    }

    public function login(
        LoginRequest $request
    ): JsonResponse {
        try {
            $data = $this->authService->login(
                $request->validated(),
                $request->ip(),
                $request->userAgent()
            );

            return ApiResponse::success(
                $data
            );
        } catch (RuntimeException $exception) {
            $message =
                $exception->getMessage();

            if (
                $message ===
                'Tai khoan da bi khoa.'
            ) {
                $status = 403;
            } elseif (
                $message ===
                'Tai khoan tam thoi bi khoa do dang nhap sai qua nhieu lan.'
            ) {
                $status = 429;
            } else {
                $status = 401;
            }

            return ApiResponse::error(
                $message,
                $status
            );
        }
    }

    public function refresh(
        RefreshTokenRequest $request
    ): JsonResponse {
        try {
            $data = $this->authService->refresh(
                $request->validated(),
                $request->ip(),
                $request->userAgent()
            );

            return ApiResponse::success(
                $data,
                'Lam moi token thanh cong.'
            );
        } catch (RuntimeException $exception) {
            return ApiResponse::error(
                $exception->getMessage(),
                401
            );
        }
    }

    public function logoutAll(): JsonResponse
    {
        try {
            $this->authService->logoutAll();

            return ApiResponse::success(
                null,
                'Dang xuat tat ca thiet bi thanh cong.'
            );
        } catch (RuntimeException $exception) {
            return ApiResponse::error(
                $exception->getMessage(),
                404
            );
        }
    }
}
