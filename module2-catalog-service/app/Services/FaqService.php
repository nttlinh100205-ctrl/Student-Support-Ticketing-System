<?php

namespace App\Services;

use App\Models\SupportFaq;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Nghiệp vụ câu hỏi thường gặp (FAQ) phía quản trị.
 */
class FaqService
{
    private const RELATIONS = [
        'department:id,name,code,is_active',
        'supportType:id,name,code,department_id,is_active',
    ];

    /**
     * Danh sách FAQ: tìm trong câu hỏi / câu trả lời, lọc phòng ban, loại hỗ trợ, trạng thái.
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = SupportFaq::query()->with(self::RELATIONS);

        $search = trim($filters['search'] ?? '');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('question', 'like', "%{$search}%")
                    ->orWhere('answer', 'like', "%{$search}%");
            });
        }

        foreach (['department_id', 'support_type_id', 'is_active'] as $column) {
            if (isset($filters[$column])) {
                $query->where($column, $filters[$column]);
            }
        }

        return $query->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * Thêm FAQ; mặc định đang hiển thị và đứng đầu danh sách.
     */
    public function create(array $data): SupportFaq
    {
        $data['is_active'] = $data['is_active'] ?? true;
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $this->withRelations(SupportFaq::create($data));
    }

    public function update(SupportFaq $faq, array $data): SupportFaq
    {
        $faq->update($data);

        return $this->withRelations($faq->refresh());
    }

    public function updateStatus(SupportFaq $faq, bool $isActive): SupportFaq
    {
        $faq->update(['is_active' => $isActive]);

        return $this->withRelations($faq->refresh());
    }

    /**
     * Nạp phòng ban và loại hỗ trợ để trang quản trị hiển thị.
     */
    public function withRelations(SupportFaq $faq): SupportFaq
    {
        return $faq->load(self::RELATIONS);
    }
}
