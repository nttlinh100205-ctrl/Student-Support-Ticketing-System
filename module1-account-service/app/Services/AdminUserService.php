<?php

namespace App\Services;

use App\Contracts\AuthContext;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RuntimeException;

class AdminUserService
{
    public function __construct(
        private readonly AuthContext $authContext
    ) {}

    public function index(): LengthAwarePaginator
    {
        return User::query()
            ->select([
                'id',
                'full_name',
                'email',
                'phone',
                'role',
                'status',
                'department_id',
                'created_at',
            ])
            ->orderBy('id')
            ->paginate(10);
    }

    public function show(User $user): array
    {
        return $this->userData($user);
    }

    public function updateRole(
        User $user,
        array $data
    ): array {
        if ($this->authContext->userId() === (int) $user->id) {
            throw new RuntimeException(
                'Khong the tu thay doi quyen cua chinh minh.'
            );
        }

        $requiresDepartment = in_array(
            $data['role'],
            ['staff', 'department_head'],
            true
        );

        if (
            $requiresDepartment &&
            empty($data['department_id'])
        ) {
            throw new RuntimeException(
                'STAFF va DEPARTMENT_HEAD phai thuoc mot phong ban.'
            );
        }

        $user->update([
            'role' => $data['role'],
            'department_id' => $requiresDepartment
                ? (int) $data['department_id']
                : null,
        ]);

        return $this->userData($user->fresh());
    }

    public function updateStatus(
        User $user,
        string $status
    ): array {
        if ($this->authContext->userId() === (int) $user->id) {
            throw new RuntimeException(
                'Khong the tu khoa tai khoan cua chinh minh.'
            );
        }

        $user->update([
            'status' => $status,
        ]);

        return $this->userData($user->fresh());
    }

    private function userData(User $user): array
    {
        return [
            'id' => (int) $user->id,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => strtolower($user->role),
            'status' => $user->status,
            'department_id' => $user->department_id !== null
                ? (int) $user->department_id
                : null,
            'created_at' => $user->created_at,
        ];
    }
}
