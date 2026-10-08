<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Responses\ApiResponse;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ProfileService $profileService
    ) {}

    public function show(): JsonResponse
    {
        try {
            return ApiResponse::success(
                $this->profileService->show()
            );
        } catch (RuntimeException $exception) {
            return ApiResponse::error(
                $exception->getMessage(),
                404
            );
        }
    }

    public function update(
        UpdateProfileRequest $request
    ): JsonResponse {
        try {
            $data = $this->profileService->update(
                $request->validated()
            );

            return ApiResponse::success(
                $data,
                'Cap nhat ho so thanh cong.'
            );
        } catch (RuntimeException $exception) {
            return ApiResponse::error(
                $exception->getMessage(),
                404
            );
        }
    }

    public function updatePassword(
        UpdatePasswordRequest $request
    ): JsonResponse {
        try {
            $this->profileService->updatePassword(
                $request->validated()
            );

            return ApiResponse::success(
                null,
                'Doi mat khau thanh cong.'
            );
        } catch (RuntimeException $exception) {
            $status = $exception->getMessage()
                === 'Mat khau hien tai khong dung.'
                ? 422
                : 404;

            return ApiResponse::error(
                $exception->getMessage(),
                $status
            );
        }
    }
}
