<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFaqRequest;
use App\Http\Requests\UpdateFaqRequest;
use App\Http\Requests\UpdateStatusRequest;
use App\Http\Responses\ApiResponse;
use App\Models\SupportFaq;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportFaqController extends Controller
{
    /**
     * Danh sách FAQ dành cho ADMIN.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:255'],
            'department_id' => [
                'nullable',
                'integer',
                'exists:support_departments,id',
            ],
            'support_type_id' => [
                'nullable',
                'integer',
                'exists:support_types,id',
            ],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $query = SupportFaq::query()->with([
            'department:id,name,code,is_active',
            'supportType:id,name,code,department_id,is_active',
        ]);

        $search = trim($filters['search'] ?? '');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('question', 'like', "%{$search}%")
                    ->orWhere('answer', 'like', "%{$search}%");
            });
        }

        if (isset($filters['department_id'])) {
            $query->where(
                'department_id',
                $filters['department_id']
            );
        }

        if (isset($filters['support_type_id'])) {
            $query->where(
                'support_type_id',
                $filters['support_type_id']
            );
        }

        if (isset($filters['is_active'])) {
            $query->where(
                'is_active',
                $filters['is_active']
            );
        }

        return ApiResponse::success(
            $query->orderBy('sort_order')
                ->orderBy('id')
                ->paginate(10)
                ->withQueryString()
        );
    }

    /**
     * Thêm câu hỏi thường gặp.
     */
    public function store(StoreFaqRequest $request): JsonResponse
    {
        $data = $request->faqData();

        $data['is_active'] = $data['is_active'] ?? true;
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $faq = SupportFaq::create($data);

        $this->loadRelations($faq);

        return ApiResponse::success($faq, 'Thêm câu hỏi thường gặp thành công.', 201);
    }

    /**
     * Xem chi tiết FAQ.
     */
    public function show(SupportFaq $faq): JsonResponse
    {
        $this->loadRelations($faq);

        return ApiResponse::success($faq);
    }

    /**
     * Cập nhật nội dung và phạm vi áp dụng.
     */
    public function update(
        UpdateFaqRequest $request,
        SupportFaq $faq
    ): JsonResponse {
        $faq->update($request->faqData());
        $faq->refresh();

        $this->loadRelations($faq);

        return ApiResponse::success($faq, 'Cập nhật câu hỏi thường gặp thành công.');
    }

    /**
     * Bật/tắt FAQ.
     */
    public function updateStatus(
        UpdateStatusRequest $request,
        SupportFaq $faq
    ): JsonResponse {
        $faq->update($request->validated());
        $faq->refresh();

        $this->loadRelations($faq);

        return ApiResponse::success(
            $faq,
            $faq->is_active
                ? 'Đã bật câu hỏi thường gặp.'
                : 'Đã ẩn câu hỏi thường gặp.'
        );
    }

    private function loadRelations(SupportFaq $faq): void
    {
        $faq->load([
            'department:id,name,code,is_active',
            'supportType:id,name,code,department_id,is_active',
        ]);
    }
}
