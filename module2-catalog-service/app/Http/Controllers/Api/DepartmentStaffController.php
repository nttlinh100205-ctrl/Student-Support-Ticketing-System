<?php

namespace App\Http\Controllers\Api;

use App\Contracts\AuthContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignStaffRequest;
use App\Http\Responses\ApiResponse;
use App\Models\SupportDepartment;
use App\Models\User;
use App\Services\AccountDirectory;
use App\Services\DepartmentStaffService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Gán / chuyển cán bộ giữa các phòng ban.
 */
class DepartmentStaffController extends Controller
{
    public function __construct(private DepartmentStaffService $staff) {}

    /**
     * Tài khoản có thể gán vào phòng ban (trừ ADMIN).
     */
    public function candidates(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        return ApiResponse::success(config('account.fake') ? $this->staff->paginateCandidates($filters) : app(AccountDirectory::class)->paginate($filters));
    }

    /**
     * Gán tài khoản vào phòng ban với vai trò cán bộ / trưởng phòng.
     */
    public function assign(
        AssignStaffRequest $request,
        AuthContext $auth,
        SupportDepartment $department,
        int $user
    ): JsonResponse {
        if (! config('account.fake')) {
            return ApiResponse::success(app(AccountDirectory::class)->assign($user, $department, $request->validated('role')));
        }
        $user = User::findOrFail($user);
        try {
            $user = $this->staff->assign($auth->userId(), $department, $user, $request->validated('role'));

            return ApiResponse::success(
                $user->only(['id', 'name', 'email', 'role', 'status', 'department_id', 'department']),
                'Gán cán bộ vào phòng ban thành công.'
            );
        } catch (AuthorizationException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        } catch (ValidationException $e) {
            return ApiResponse::error($e->getMessage(), 409);
        }
    }
}
