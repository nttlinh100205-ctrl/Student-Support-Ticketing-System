<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Quy tắc đặt tên: <HànhĐộng><Resource>Request — Mục 5 tài liệu.
 */
class StoreRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // quyền hạn xử lý ở Controller/Service (role = student), không chặn ở đây
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
            'department_id.required' => 'Vui lòng chọn phòng ban.',
            'support_type_id.required' => 'Vui lòng chọn loại hỗ trợ.',
        ];
    }
}
