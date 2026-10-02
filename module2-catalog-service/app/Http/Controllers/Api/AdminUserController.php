<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportDepartment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    /**
     * Danh sách tài khoản.
     * Giữ cấu trúc data.data để tương thích các trang hiện tại.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $users = User::query()
            ->select([
                'id',
                'name',
                'email',
                'phone',
                'role',
                'status',
                'department_id',
                'created_at',
            ])
            ->with('department:id,name,code,is_active')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        return response()->json([
            'message' => 'Lấy danh sách tài khoản thành công.',
            'data' => $users,
        ]);
    }

    /**
     * Xem chi tiết tài khoản.
     */
    public function show(User $user): JsonResponse
    {
        $user->load('department:id,name,code,is_active');

        return response()->json([
            'message' => 'Lấy thông tin tài khoản thành công.',
            'user' => $this->userData($user),
        ]);
    }

    /**
     * Thay đổi vai trò và phòng ban.
     */
    public function updateRole(
        Request $request,
        User $user
    ): JsonResponse {
        // Không cho ADMIN tự đổi quyền của chính mình.
        if ((int) $request->user()->id === (int) $user->id) {
            return response()->json([
                'message' => 'Không thể tự thay đổi quyền của chính mình.',
            ], 403);
        }

        $validated = $request->validate([
            'role' => [
                'required',
                'string',
                Rule::in([
                    'ADMIN',
                    'DEPARTMENT_HEAD',
                    'STAFF',
                    'STUDENT',
                ]),
            ],
            'department_id' => [
                'nullable',
                'integer',
                'exists:support_departments,id',
            ],
        ]);

        $needsDepartment = in_array(
            $validated['role'],
            ['STAFF', 'DEPARTMENT_HEAD'],
            true
        );

        if ($needsDepartment && empty($validated['department_id'])) {
            return response()->json([
                'message' => 'Cán bộ và trưởng phòng phải thuộc một phòng ban.',
                'errors' => [
                    'department_id' => [
                        'Vui lòng chọn phòng ban.',
                    ],
                ],
            ], 422);
        }

        // ADMIN và STUDENT không thuộc phòng ban hỗ trợ.
        $departmentId = $needsDepartment
            ? (int) $validated['department_id']
            : null;

        if ($departmentId !== null) {
            $department = SupportDepartment::query()
                ->find($departmentId);

            if (!$department) {
                return response()->json([
                    'message' => 'Phòng ban không còn tồn tại.',
                    'errors' => [
                        'department_id' => [
                            'Vui lòng tải lại danh sách phòng ban.',
                        ],
                    ],
                ], 422);
            }

            // Cho phép giữ nguyên phòng cũ đã ngừng hoạt động.
            $keepingExistingDepartment =
                (int) $user->department_id === $departmentId
                && in_array(
                    $user->role,
                    ['STAFF', 'DEPARTMENT_HEAD'],
                    true
                );

            if (!$department->is_active && !$keepingExistingDepartment) {
                return response()->json([
                    'message' => 'Không thể gán cán bộ vào phòng ban đã ngừng hoạt động.',
                    'errors' => [
                        'department_id' => [
                            'Vui lòng chọn phòng ban đang hoạt động.',
                        ],
                    ],
                ], 422);
            }
        }

        $user->update([
            'role' => $validated['role'],
            'department_id' => $departmentId,
        ]);

        $user->refresh();
        $user->load('department:id,name,code,is_active');

        return response()->json([
            'message' => 'Cập nhật vai trò và phòng ban thành công.',
            'user' => $this->userData($user),
        ]);
    }

    /**
     * Khóa hoặc mở khóa tài khoản.
     */
    public function updateStatus(
        Request $request,
        User $user
    ): JsonResponse {
        if ((int) $request->user()->id === (int) $user->id) {
            return response()->json([
                'message' => 'Không thể tự thay đổi trạng thái tài khoản của chính mình.',
            ], 403);
        }

        $validated = $request->validate([
            'status' => [
                'required',
                'string',
                Rule::in(['ACTIVE', 'LOCKED']),
            ],
        ]);

        $user->update([
            'status' => $validated['status'],
        ]);

        if ($validated['status'] === 'LOCKED') {
         $user->tokens()->delete();
        }

        $user->refresh();
        $user->load('department:id,name,code,is_active');

        return response()->json([
            'message' => $validated['status'] === 'LOCKED'
                ? 'Khóa tài khoản thành công.'
                : 'Mở khóa tài khoản thành công.',
            'user' => $this->userData($user),
        ]);
    }

    /**
     * Chỉ trả các thông tin tài khoản cần thiết.
     */
    private function userData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'status' => $user->status,
            'department_id' => $user->department_id,
            'department' => $user->department ? [
                'id' => $user->department->id,
                'name' => $user->department->name,
                'code' => $user->department->code,
                'is_active' => (bool) $user->department->is_active,
            ] : null,
            'created_at' => $user->created_at,
        ];
    }
}