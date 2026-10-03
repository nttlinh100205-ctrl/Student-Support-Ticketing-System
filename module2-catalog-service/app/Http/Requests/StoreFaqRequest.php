<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // quyền ADMIN đã kiểm tra ở middleware role:admin
    }

    public function rules(): array
    {
        return [
            'department_id' => ['required', 'integer', 'exists:support_departments,id'],
            // Loại hỗ trợ (nếu chọn) phải thuộc phòng ban đã chọn.
            'support_type_id' => [
                'nullable',
                'integer',
                Rule::exists('support_types', 'id')->where('department_id', $this->input('department_id')),
            ],
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:10000'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
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
        ];
    }

    /**
     * Dữ liệu đã kiểm tra. Không chọn loại hỗ trợ nghĩa là FAQ chung của phòng ban
     * (support_type_id = null) — kể cả khi sửa một FAQ đang gắn với loại hỗ trợ.
     */
    public function faqData(): array
    {
        $data = $this->validated();

        $data['support_type_id'] = $data['support_type_id'] ?? null;

        return $data;
    }
}
