<?php

namespace App\Services;

use App\Models\SupportDepartment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

/**
 * Nghiệp vụ phòng ban: tra cứu, thêm / sửa / xóa và danh sách cán bộ.
 */
class DepartmentService
{
    /**
     * Danh sách phòng ban có tìm kiếm theo tên / mã, lọc trạng thái, kèm số cán bộ.
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = SupportDepartment::query()->withCount(['staff', 'heads']);

        $search = trim($filters['search'] ?? '');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // isset vẫn nhận giá trị 0, nhưng bỏ qua null.
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('name')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * Thêm phòng ban; không truyền trạng thái thì mặc định đang hoạt động.
     */
    public function create(array $data): SupportDepartment
    {
        $data['is_active'] = $data['is_active'] ?? true;

        return $this->withStaffCounts(SupportDepartment::create($data));
    }

    public function update(SupportDepartment $department, array $data): SupportDepartment
    {
        $department->update($data);

        return $this->withStaffCounts($department->refresh());
    }

    /**
     * Nạp số cán bộ / trưởng phòng để SupportDepartmentResource trả về.
     */
    public function withStaffCounts(SupportDepartment $department): SupportDepartment
    {
        return $department->loadCount(['staff', 'heads']);
    }

    /**
     * Cán bộ và trưởng phòng thuộc phòng ban, có tìm kiếm và lọc vai trò / trạng thái.
     */
    public function paginateStaff(SupportDepartment $department, array $filters): LengthAwarePaginator
    {
        $query = $department->users()
            ->select(['id', 'name', 'email', 'role', 'status', 'department_id'])
            ->whereIn('role', ['STAFF', 'DEPARTMENT_HEAD']);

        $search = trim($filters['search'] ?? '');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('name')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * Chỉ xóa phòng ban khi không còn dữ liệu liên kết. Ném ValidationException
     * nếu còn tài khoản / loại hỗ trợ / yêu cầu — Controller cần catch để trả 409.
     */
    public function delete(SupportDepartment $department): void
    {
        $reason = $this->deleteBlockedReason($department);

        if ($reason !== null) {
            throw ValidationException::withMessages(['department' => $reason]);
        }

        $department->delete();
    }

    private function deleteBlockedReason(SupportDepartment $department): ?string
    {
        if ($department->users()->exists()) {
            return 'Không thể xóa phòng ban vì vẫn có tài khoản thuộc phòng ban này.';
        }

        if ($department->supportTypes()->exists()) {
            return 'Không thể xóa phòng ban vì vẫn có loại hỗ trợ liên kết.';
        }

        if (RequestUsage::usesDepartment($department->id)) {
            return 'Không thể xóa phòng ban vì đã có yêu cầu hỗ trợ liên quan. Hãy chuyển sang ngừng hoạt động.';
        }

        return null;
    }
}
