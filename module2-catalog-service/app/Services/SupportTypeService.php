<?php

namespace App\Services;

use App\Models\SupportType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

/**
 * Nghiệp vụ loại hỗ trợ (kèm SLA): tra cứu, thêm / sửa / xóa.
 */
class SupportTypeService
{
    /**
     * Danh sách loại hỗ trợ: tìm theo tên / mã, lọc phòng ban, lọc trạng thái.
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        // Load phòng ban để SupportTypeResource trả kèm thông tin department.
        $query = SupportType::query()->with('department:id,name,code,is_active');

        $search = trim($filters['search'] ?? '');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if (array_key_exists('department_id', $filters)) {
            $query->where('department_id', $filters['department_id']);
        }

        if (array_key_exists('is_active', $filters)) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('name')
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * Thêm loại hỗ trợ; không truyền trạng thái thì mặc định đang hoạt động.
     */
    public function create(array $data): SupportType
    {
        $data['is_active'] = $data['is_active'] ?? true;

        return $this->withDepartment(SupportType::create($data));
    }

    public function update(SupportType $supportType, array $data): SupportType
    {
        $supportType->update($data);

        return $this->withDepartment($supportType->refresh());
    }

    /**
     * Nạp phòng ban phụ trách để SupportTypeResource trả về.
     */
    public function withDepartment(SupportType $supportType): SupportType
    {
        return $supportType->load('department:id,name,code,is_active');
    }

    /**
     * Không cho xóa nếu đã có yêu cầu hỗ trợ sử dụng, để tránh mất liên kết lịch sử.
     * Ném ValidationException — Controller cần catch để trả 409.
     */
    public function delete(SupportType $supportType): void
    {
        if (RequestUsage::usesSupportType($supportType->id)) {
            throw ValidationException::withMessages([
                'support_type' => 'Không thể xóa loại hỗ trợ vì đã có yêu cầu sử dụng loại hỗ trợ này.',
            ]);
        }

        $supportType->delete();
    }
}
