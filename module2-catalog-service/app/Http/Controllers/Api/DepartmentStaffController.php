<?php

namespace App\Http\Controllers\Api;

use App\Contracts\AuthContext;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\SupportDepartment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Gán / chuyển cán bộ giữa các phòng ban.
 *
 * Tài khoản do Module 1 quản lý; Module 2 chỉ đổi phòng ban
 * và vai trò cán bộ (STAFF / DEPARTMENT_HEAD) trong phòng ban.
 */
class DepartmentStaffController extends Controller
{
    /**
     * Tài khoản có thể gán vào phòng ban (trừ ADMIN).
     */
    public function candidates(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = User::query()
            ->select(['id', 'name', 'email', 'role', 'status', 'department_id'])
            ->with('department:id,name,code,is_active')
            ->where('role', '!=', 'ADMIN');

        $search = trim($filters['search'] ?? '');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return ApiResponse::success(
            $query->orderBy('name')
                ->orderBy('id')
                ->paginate(50)
                ->withQueryString()
        );
    }

    /**
     * Gán tài khoản vào phòng ban với vai trò cán bộ / trưởng phòng.
     * Nếu tài khoản đang ở phòng khác thì được chuyển sang phòng này.
     */
    public function assign(
        Request $request,
        AuthContext $auth,
        SupportDepartment $department,
        User $user
    ): JsonResponse {
        if ($auth->userId() === (int) $user->id) {
            return ApiResponse::error('Không thể tự thay đổi phòng ban của chính mình.', 403);
        }

        if ($user->role === 'ADMIN') {
            return ApiResponse::error('Không thể gán tài khoản ADMIN vào phòng ban.', 409);
        }

        $data = $request->validate([
            'role' => ['required', Rule::in(['STAFF', 'DEPARTMENT_HEAD'])],
        ], [
            'role.required' => 'Vui lòng chọn vai trò.',
            'role.in' => 'Vai trò không hợp lệ.',
        ]);

        // Cho phép giữ nguyên phòng cũ dù phòng đó đã ngừng hoạt động.
        $keepingDepartment = (int) $user->department_id === (int) $department->id;

        if (! $department->is_active && ! $keepingDepartment) {
            return ApiResponse::error('Không thể gán cán bộ vào phòng ban đã ngừng hoạt động.', 409);
        }

        $user->update([
            'role' => $data['role'],
            'department_id' => $department->id,
        ]);

        $user->load('department:id,name,code,is_active');

        return ApiResponse::success(
            $user->only(['id', 'name', 'email', 'role', 'status', 'department_id', 'department']),
            'Gán cán bộ vào phòng ban thành công.'
        );
    }
}
