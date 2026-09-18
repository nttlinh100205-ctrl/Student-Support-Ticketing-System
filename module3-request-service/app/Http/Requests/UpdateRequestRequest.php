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
            'department_id' => 'required|integer',
            'support_type_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'priority' => 'nullable|in:low,normal,high,urgent',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Vui lòng nhập tiêu đề yêu cầu.',
            'content.required' => 'Vui lòng nhập nội dung yêu cầu.',
            'department_id.required' => 'Vui lòng chọn phòng ban.',
            'support_type_id.required' => 'Vui lòng chọn loại hỗ trợ.',
        ];
    }
}
