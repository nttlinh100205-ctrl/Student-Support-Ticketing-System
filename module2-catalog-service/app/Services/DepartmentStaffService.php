<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\SupportDepartment;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

/**
 * Gán / chuyển cán bộ giữa các phòng ban.
 *
 * Tài khoản do Module 1 quản lý; Module 2 chỉ đổi phòng ban
 * và vai trò cán bộ (staff / department_head) trong phòng ban.
 */
class DepartmentStaffService
{
    /**
     * Tài khoản có thể gán vào phòng ban (trừ ADMIN), tìm theo tên / email.
     */
    public function paginateCandidates(array $filters): LengthAwarePaginator
    {
        $query = User::query()
            ->select(['id', 'name', 'email', 'role', 'status', 'department_id'])
            ->with('department:id,name,code,is_active')
            ->where('role', '!=', UserRole::Admin->value);

        $search = trim($filters['search'] ?? '');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('name')
            ->orderBy('id')
            ->paginate(50)
            ->withQueryString();
    }

    /**
     * Gán tài khoản vào phòng ban với vai trò cán bộ / trưởng phòng;
     * đang ở phòng khác thì được chuyển sang phòng này.
     *
     * Ném AuthorizationException khi tự đổi phòng ban của chính mình (Controller trả 403),
     * ValidationException khi sai nghiệp vụ (Controller trả 409).
     */
    public function assign(int $actorId, SupportDepartment $department, User $user, string $role): User
    {
        if ($actorId === (int) $user->id) {
            throw new AuthorizationException('Không thể tự thay đổi phòng ban của chính mình.');
        }

        if ($user->role === UserRole::Admin->value) {
            throw ValidationException::withMessages(['user' => 'Không thể gán tài khoản ADMIN vào phòng ban.']);
        }

        // Cho phép giữ nguyên phòng cũ dù phòng đó đã ngừng hoạt động.
        $isKeepingDepartment = (int) $user->department_id === (int) $department->id;

        if (! $department->is_active && ! $isKeepingDepartment) {
            throw ValidationException::withMessages([
                'department' => 'Không thể gán cán bộ vào phòng ban đã ngừng hoạt động.',
            ]);
        }

        $user->update([
            'role' => $role,
            'department_id' => $department->id,
        ]);

        return $user->load('department:id,name,code,is_active');
    }
}
