<?php

namespace App\Services;

use App\Models\SupportType;
use App\Models\SupportTypeField;
use Illuminate\Database\Eloquent\Collection;

/**
 * Nghiệp vụ biểu mẫu theo loại hỗ trợ: các trường sinh viên cần nhập khi gửi yêu cầu.
 */
class SupportTypeFieldService
{
    /**
     * Các trường của loại hỗ trợ, có thể lọc theo trạng thái bật / tắt.
     */
    public function list(SupportType $supportType, array $filters): Collection
    {
        $query = $supportType->fields();

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->get();
    }

    /**
     * Thêm trường; mặc định không bắt buộc, đang bật và đứng đầu danh sách.
     */
    public function create(SupportType $supportType, array $data): SupportTypeField
    {
        $data['is_required'] = $data['is_required'] ?? false;
        $data['is_active'] = $data['is_active'] ?? true;
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $supportType->fields()->create($data);
    }

    public function update(SupportTypeField $field, array $data): SupportTypeField
    {
        $field->update($data);

        return $field->fresh();
    }

    public function updateStatus(SupportTypeField $field, bool $isActive): SupportTypeField
    {
        $field->update(['is_active' => $isActive]);

        return $field->fresh();
    }

    /**
     * Không cho truy cập trường thuộc loại hỗ trợ khác (trả 404).
     */
    public function ensureBelongsToType(SupportType $supportType, SupportTypeField $field): void
    {
        abort_unless(
            (int) $field->support_type_id === (int) $supportType->id,
            404,
            'Không tìm thấy trường trong loại hỗ trợ này.'
        );
    }
}
