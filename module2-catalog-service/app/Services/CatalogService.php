<?php

namespace App\Services;

use App\Models\SupportDepartment;
use App\Models\SupportFaq;
use App\Models\SupportType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Tra cứu danh mục cho sinh viên và Module 3: chỉ trả dữ liệu đang hoạt động.
 */
class CatalogService
{
    private const FAQ_COLUMNS = ['id', 'department_id', 'support_type_id', 'question', 'answer', 'sort_order'];

    private const FIELD_COLUMNS = [
        'id', 'support_type_id', 'field_key', 'label', 'field_type',
        'is_required', 'options', 'help_text', 'sort_order',
    ];

    public function paginateDepartments(): LengthAwarePaginator
    {
        return SupportDepartment::query()
            ->where('is_active', true)
            ->select(['id', 'name', 'code', 'description'])
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();
    }

    /**
     * Loại hỗ trợ đang hoạt động, thuộc phòng ban đang hoạt động.
     */
    public function paginateSupportTypes(array $filters): LengthAwarePaginator
    {
        $query = SupportType::query()
            ->where('is_active', true)
            ->whereHas('department', fn ($q) => $q->where('is_active', true))
            ->with('department:id,name,code,is_active');

        if (isset($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        $search = trim($filters['search'] ?? '');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('name')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();
    }

    /**
     * Các trường đang bật của biểu mẫu.
     */
    public function activeFields(SupportType $supportType): Collection
    {
        return $supportType->fields()
            ->where('is_active', true)
            ->get(self::FIELD_COLUMNS);
    }

    /**
     * FAQ của loại hỗ trợ: FAQ chung của phòng phụ trách + FAQ riêng của loại đang chọn.
     */
    public function paginateSupportTypeFaqs(SupportType $supportType): LengthAwarePaginator
    {
        $query = SupportFaq::query()
            ->where('is_active', true)
            ->where('department_id', $supportType->department_id)
            ->where(function ($q) use ($supportType) {
                $q->whereNull('support_type_id')
                    ->orWhere('support_type_id', $supportType->id);
            });

        return $this->paginateFaqs($query);
    }

    /**
     * FAQ theo phòng ban: FAQ chung và FAQ của các loại hỗ trợ đang hoạt động.
     */
    public function paginateDepartmentFaqs(SupportDepartment $department): LengthAwarePaginator
    {
        $query = SupportFaq::query()
            ->where('is_active', true)
            ->where('department_id', $department->id)
            ->where(function ($q) use ($department) {
                $q->whereNull('support_type_id')
                    ->orWhereHas('supportType', function ($typeQuery) use ($department) {
                        $typeQuery->where('is_active', true)
                            ->where('department_id', $department->id);
                    });
            });

        return $this->paginateFaqs($query);
    }

    /**
     * Chặn đọc cấu hình của loại hỗ trợ đã tắt hoặc thuộc phòng ban đã tắt (trả 404).
     */
    public function ensureAvailable(SupportType $supportType): void
    {
        $supportType->load('department:id,name,code,is_active');

        abort_unless(
            $supportType->is_active && $supportType->department?->is_active,
            404,
            'Loại hỗ trợ hiện không khả dụng.'
        );
    }

    public function ensureDepartmentAvailable(SupportDepartment $department): void
    {
        abort_unless($department->is_active, 404, 'Phòng ban hiện không khả dụng.');
    }

    private function paginateFaqs($query): LengthAwarePaginator
    {
        return $query->select(self::FAQ_COLUMNS)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();
    }
}
