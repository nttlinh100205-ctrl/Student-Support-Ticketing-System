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
     * Danh sách tất cả tài khoản
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->select([
                'id',
                'name',
                'email',
                'phone',
                'role',
                'status',
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
     * Xem chi tiết một tài khoản
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'message' => 'Lay thong tin tai khoan thanh cong',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'status' => $user->status,
                'created_at' => $user->created_at,
            ],
        ]);
    }

    /**
     * Thay đổi quyền tài khoản
     */
    public function updateRole(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'role' => [
                'required',
                'string',
                Rule::in(['ADMIN', 'STAFF', 'STUDENT']),
            ],
        ]);

        // Không cho ADMIN tự đổi quyền của chính mình
        if ($request->user()->id === $user->id) {
            return response()->json([
                'message' => 'Khong the tu thay doi quyen cua chinh minh.',
            ], 403);
        }

        $user->update([
            'role' => $validated['role'],
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
            ],
        ]);
    }

    /**
     * Khóa hoặc mở khóa tài khoản
     */
    public function updateStatus(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'status' => [
                'required',
                'string',
                Rule::in(['ACTIVE', 'LOCKED']),
            ],
        ]);

        // Không cho ADMIN tự khóa chính mình
        if ($request->user()->id === $user->id) {
            return response()->json([
                'message' => 'Khong the tu khoa tai khoan cua chinh minh.',
            ], 403);
        }

        $user->update([
            'status' => $validated['status'],
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
            ],
        ]);
    }
}