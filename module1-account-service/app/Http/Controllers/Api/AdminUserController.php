<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Services\AdminUserService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class AdminUserController extends Controller
{
    public function __construct(
        private readonly AdminUserService $adminUserService
    ) {}

    public function index(): JsonResponse
    {
        return ApiResponse::success(
            $this->adminUserService->index()
        );
    }

    public function show(User $user): JsonResponse
    {
        return ApiResponse::success(
            $this->adminUserService->show($user)
        );
    }

    public function updateRole(
        UpdateUserRoleRequest $request,
        User $user
    ): JsonResponse {
        try {
            $data = $this->adminUserService->updateRole(
                $user,
                $request->validated()
            );

            return ApiResponse::success(
                $data,
                'Thay doi quyen tai khoan thanh cong.'
            );
        } catch (RuntimeException $exception) {
            $status = $exception->getMessage()
                === 'Khong the tu thay doi quyen cua chinh minh.'
                ? 403
                : 422;

            return ApiResponse::error(
                $exception->getMessage(),
                $status
            );
        }
    }

    public function updateStatus(
        UpdateUserStatusRequest $request,
        User $user
    ): JsonResponse {
        try {
            $status = $request->validated('status');

            $data = $this->adminUserService->updateStatus(
                $user,
                $status
            );

            $message = $status === 'LOCKED'
                ? 'Khoa tai khoan thanh cong.'
                : 'Mo khoa tai khoan thanh cong.';

            return ApiResponse::success(
                $data,
                $message
            );
        } catch (RuntimeException $exception) {
            return ApiResponse::error(
                $exception->getMessage(),
                403
            );
        }
    }
}
