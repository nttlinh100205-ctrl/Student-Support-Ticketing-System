<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Password\PasswordResetService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class PasswordResetController extends Controller
{
    public function __construct(
        private readonly PasswordResetService $passwordResetService
    ) {}

    public function forgot(
        ForgotPasswordRequest $request
    ): JsonResponse {
        $this->passwordResetService->sendResetLink(
            $request->validated()
        );

        return ApiResponse::success(
            null,
            'Nếu email tồn tại, liên kết đặt lại mật khẩu đã được gửi.'
        );
    }

    public function reset(
        ResetPasswordRequest $request
    ): JsonResponse {
        try {
            $this->passwordResetService->resetPassword(
                $request->validated()
            );

            return ApiResponse::success(
                null,
                'Đặt lại mật khẩu thành công.'
            );
        } catch (RuntimeException $exception) {
            return ApiResponse::error(
                $exception->getMessage(),
                422
            );
        }
    }
}
