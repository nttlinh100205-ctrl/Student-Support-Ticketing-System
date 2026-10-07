<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
class UpdateRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // quyền hạn xử lý ở Controller/Service
    }

    public function rules(): array
    {
        return [
            'department_id' => 'prohibited',
            'support_type_id' => 'prohibited',
            'priority' => 'prohibited',
            'title' => 'required|string|min:10|max:255',
            'content' => 'required|string|min:20',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Vui lòng nhập tiêu đề yêu cầu.',
            'title.min' => 'Tiêu đề phải có ít nhất 10 ký tự.',
            'content.required' => 'Vui lòng nhập nội dung yêu cầu.',
            'content.min' => 'Nội dung phải có ít nhất 20 ký tự.',
            'department_id.prohibited' => 'Không thể đổi phòng ban khi cập nhật yêu cầu.',
            'support_type_id.prohibited' => 'Không thể đổi loại hỗ trợ khi cập nhật yêu cầu.',
            'priority.prohibited' => 'Không thể đổi mức ưu tiên khi cập nhật yêu cầu.',
        ];
    }
}
