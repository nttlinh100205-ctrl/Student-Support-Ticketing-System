<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Đăng ký tài khoản mới.
     * Tài khoản đăng ký từ API công khai luôn là STUDENT.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
            'student_code' => ['nullable', 'string', 'max:30', 'unique:users,student_code'],
            'phone' => [
                'nullable',
                'string',
                'max:20',
            ],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'phone' => $validated['phone'] ?? null,
            'student_code' => $validated['student_code'] ?? null,
            'role' => 'STUDENT',
            'status' => 'ACTIVE',
        ]);

        $token = $user->createToken(
            'student-support-client', ['*'], $request->boolean('remember') ? now()->addDays(30) : now()->addHours(8)
        )->plainTextToken;

        return response()->json([
            'message' => 'Dang ky tai khoan thanh cong',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'status' => $user->status,
                'department_id' => $user->department_id,
            ],
            'token' => $token,
        ], 201);
    }

    /**
     * Đăng nhập.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate(['remember' => 'sometimes|boolean']);
        $validated = $request->validate([
            'email' => [
                'required',
                'string',
                'max:255',
            ],
            'password' => [
                'required',
                'string',
            ],
        ]);

        $user = User::where(
            'email',
            $validated['email']
        )->orWhere('student_code', $validated['email'])->first();

        if (
            ! $user ||
            ! Hash::check(
                $validated['password'],
                $user->password
            )
        ) {
            throw ValidationException::withMessages([
                'email' => [
                    'Email hoac mat khau khong dung.',
                ],
            ]);
        }

        if ($user->status !== 'ACTIVE') {
            return response()->json([
                'message' => 'Tai khoan dang bi khoa.',
            ], 403);
        }

        // Xóa token cũ của client hiện tại nếu muốn
        $token = $user->createToken(
            'student-support-client', ['*'], $request->boolean('remember') ? now()->addDays(30) : now()->addHours(8)
        )->plainTextToken;

        return response()->json([
            'message' => 'Dang nhap thanh cong',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'status' => $user->status,
                'department_id' => $user->department_id,
            ],
            'token' => $token,
        ]);
    }

    /**
     * Đăng xuất token hiện tại.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()
            ->currentAccessToken()
            ?->delete();

        return response()->json([
            'message' => 'Dang xuat thanh cong',
        ]);
    }

    /**
     * Thông tin user hiện tại.
     */
    public function me(Request $request): JsonResponse
    {
        abort_unless($request->user()->status === 'ACTIVE', 403, 'Tài khoản đang bị khóa.');
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'status' => $user->status,
                'department_id' => $user->department_id,
            ],
        ]);
    }
}
