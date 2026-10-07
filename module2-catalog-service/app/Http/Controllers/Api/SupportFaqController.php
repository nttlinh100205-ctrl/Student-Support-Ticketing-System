<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFaqRequest;
use App\Http\Requests\UpdateFaqRequest;
use App\Http\Requests\UpdateStatusRequest;
use App\Http\Responses\ApiResponse;
use App\Models\SupportFaq;
use App\Services\FaqService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportFaqController extends Controller
{
    public function __construct(private FaqService $faqs) {}

    /**
     * Danh sách FAQ dành cho ADMIN.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'integer', 'exists:support_departments,id'],
            'support_type_id' => ['nullable', 'integer', 'exists:support_types,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return ApiResponse::success($this->faqs->paginate($filters));
    }

    /**
     * Thêm câu hỏi thường gặp.
     */
    public function store(StoreFaqRequest $request): JsonResponse
    {
        $faq = $this->faqs->create($request->faqData());

        return ApiResponse::success($faq, 'Thêm câu hỏi thường gặp thành công.', 201);
    }

    /**
     * Xem chi tiết FAQ.
     */
    public function show(SupportFaq $faq): JsonResponse
    {
        return ApiResponse::success($this->faqs->withRelations($faq));
    }

    /**
     * Cập nhật nội dung và phạm vi áp dụng.
     */
    public function update(UpdateFaqRequest $request, SupportFaq $faq): JsonResponse
    {
        $faq = $this->faqs->update($faq, $request->faqData());

        return ApiResponse::success($faq, 'Cập nhật câu hỏi thường gặp thành công.');
    }

    /**
     * Bật/tắt FAQ.
     */
    public function updateStatus(UpdateStatusRequest $request, SupportFaq $faq): JsonResponse
    {
        $faq = $this->faqs->updateStatus($faq, $request->boolean('is_active'));

        return ApiResponse::success(
            $faq,
            $faq->is_active ? 'Đã bật câu hỏi thường gặp.' : 'Đã ẩn câu hỏi thường gặp.'
        );
    }
}
