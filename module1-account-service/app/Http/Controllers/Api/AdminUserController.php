<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    /**
     * Danh sách tất cả tài khoản.
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->with([
                'department:id,name,code',
            ])
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
            ->orderBy('id')
            ->paginate(10);

        return response()->json([
            'message' => 'Lay danh sach tai khoan thanh cong',
            'data' => $users,
        ]);
    }


    /**
     * Xem chi tiết một tài khoản.
     */
    public function show(User $user): JsonResponse
    {
        $user->load([
            'department:id,name,code',
        ]);

        return response()->json([
            'message' => 'Lay thong tin tai khoan thanh cong',
            'user' => [
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
                ] : null,
                'created_at' => $user->created_at,
            ],
        ]);
    }


    /**
     * Thay đổi quyền tài khoản.
     *
     * STAFF và DEPARTMENT_HEAD bắt buộc phải
     * thuộc một phòng ban.
     */
    public function updateRole(
        Request $request,
        User $user
    ): JsonResponse {
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


        /**
         * Không cho ADMIN tự đổi quyền của chính mình.
         */
        if ($request->user()->id === $user->id) {
            return response()->json([
                'message' =>
                    'Khong the tu thay doi quyen cua chinh minh.',
            ], 403);
        }


        /**
         * STAFF và DEPARTMENT_HEAD
         * bắt buộc phải có phòng ban.
         */
        if (
            in_array(
                $validated['role'],
                ['STAFF', 'DEPARTMENT_HEAD'],
                true
            )
            && empty($validated['department_id'])
        ) {
            return response()->json([
                'message' =>
                    'STAFF va DEPARTMENT_HEAD phai thuoc mot phong ban.',
                'errors' => [
                    'department_id' => [
                        'Vui long chon phong ban.',
                    ],
                ],
            ], 422);
        }


        /**
         * STUDENT và ADMIN không thuộc phòng ban hỗ trợ.
         */
        $departmentId = in_array(
            $validated['role'],
            ['STAFF', 'DEPARTMENT_HEAD'],
            true
        )
            ? $validated['department_id']
            : null;


        $user->update([
            'role' => $validated['role'],
            'department_id' => $departmentId,
        ]);


        $user->load([
            'department:id,name,code',
        ]);


        return response()->json([
            'message' => 'Thay doi quyen tai khoan thanh cong',

            'user' => [
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
                ] : null,
            ],
        ]);
    }


    /**
     * Khóa hoặc mở khóa tài khoản.
     */
    public function updateStatus(
        Request $request,
        User $user
    ): JsonResponse {
        $validated = $request->validate([
            'status' => [
                'required',
                'string',
                Rule::in([
                    'ACTIVE',
                    'LOCKED',
                ]),
            ],
        ]);


        /**
         * Không cho ADMIN tự khóa chính mình.
         */
        if ($request->user()->id === $user->id) {
            return response()->json([
                'message' =>
                    'Khong the tu khoa tai khoan cua chinh minh.',
            ], 403);
        }


        $user->update([
            'status' => $validated['status'],
        ]);


        $user->load([
            'department:id,name,code',
        ]);


        return response()->json([
            'message' => $validated['status'] === 'LOCKED'
                ? 'Khoa tai khoan thanh cong'
                : 'Mo khoa tai khoan thanh cong',

            'user' => [
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
                ] : null,
            ],
        ]);
    }
}