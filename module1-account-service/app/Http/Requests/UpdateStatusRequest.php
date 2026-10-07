<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // chỉ staff/department_head/admin mới được gọi — kiểm tra ở Controller
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:new,received,in_progress,resolved,closed,cancelled',
            'note' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Vui lòng chọn trạng thái mới.',
            'status.in' => 'Trạng thái không hợp lệ.',
        ];
    }
}
