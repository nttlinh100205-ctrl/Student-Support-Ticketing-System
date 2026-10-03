<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\SupportFaq;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
    public function store(Request $request): JsonResponse
    {
        $data = $this->validateData($request);

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
        Request $request,
        SupportFaq $faq
    ): JsonResponse {
        $data = $this->validateData($request);

        $faq->update($data);
        $faq->refresh();

        $this->loadRelations($faq);

        return ApiResponse::success($faq, 'Cập nhật câu hỏi thường gặp thành công.');
    }

    /**
     * Bật/tắt FAQ.
     */
    public function updateStatus(
        Request $request,
        SupportFaq $faq
    ): JsonResponse {
        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $faq->update($data);
        $faq->refresh();

        $this->loadRelations($faq);

        return ApiResponse::success(
            $faq,
            $faq->is_active
                ? 'Đã bật câu hỏi thường gặp.'
                : 'Đã ẩn câu hỏi thường gặp.'
        );
    }

    /**
     * Kiểm tra nội dung.
     *
     * Loại hỗ trợ được chọn phải thuộc phòng ban đã chọn.
     */
    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'department_id' => [
                'required',
                'integer',
                'exists:support_departments,id',
            ],

            'support_type_id' => [
                'nullable',
                'integer',
                Rule::exists('support_types', 'id')
                    ->where(function ($query) use ($request) {
                        $query->where(
                            'department_id',
                            $request->input('department_id')
                        );
                    }),
            ],

            'question' => [
                'required',
                'string',
                'max:255',
            ],

            'answer' => [
                'required',
                'string',
                'max:10000',
            ],

            'sort_order' => [
                'sometimes',
                'integer',
                'min:0',
                'max:65535',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ], [
            'department_id.required' => 'Vui lòng chọn phòng ban.',
            'department_id.exists' => 'Phòng ban không tồn tại.',
            'support_type_id.exists' => 'Loại hỗ trợ không tồn tại hoặc không thuộc phòng ban đã chọn.',
            'question.required' => 'Vui lòng nhập câu hỏi.',
            'question.max' => 'Câu hỏi không được vượt quá 255 ký tự.',
            'answer.required' => 'Vui lòng nhập câu trả lời.',
            'answer.max' => 'Câu trả lời không được vượt quá 10.000 ký tự.',
            'sort_order.integer' => 'Thứ tự hiển thị phải là số nguyên.',
            'sort_order.min' => 'Thứ tự hiển thị không được nhỏ hơn 0.',
            'sort_order.max' => 'Thứ tự hiển thị không được vượt quá 65535.',
        ]);

        $data['question'] = trim($data['question']);
        $data['answer'] = trim($data['answer']);

        $errors = [];

        if ($data['question'] === '') {
            $errors['question'] = [
                'Câu hỏi không được để trống.',
            ];
        }

        if ($data['answer'] === '') {
            $errors['answer'] = [
                'Câu trả lời không được để trống.',
            ];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        // Không chọn loại hỗ trợ: FAQ chung của phòng ban.
        $data['support_type_id'] =
            $data['support_type_id'] ?? null;

        return $data;
    }

    private function loadRelations(SupportFaq $faq): void
    {
        $faq->load([
            'department:id,name,code,is_active',
            'supportType:id,name,code,department_id,is_active',
        ]);
    }
}
