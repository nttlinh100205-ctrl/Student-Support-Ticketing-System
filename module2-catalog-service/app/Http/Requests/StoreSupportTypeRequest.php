<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupportTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // quyền ADMIN đã kiểm tra ở middleware role:admin
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9_-]+$/',
                // Khi sửa: bỏ qua chính loại hỗ trợ đang sửa.
                Rule::unique('support_types', 'code')->ignore($this->route('supportType')),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'department_id' => ['required', 'integer', 'exists:support_departments,id'],
            // Số ngày xử lý dự kiến; null = chưa quy định.
            'sla_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên loại hỗ trợ.',
            'name.max' => 'Tên loại hỗ trợ không được vượt quá 150 ký tự.',
            'code.required' => 'Vui lòng nhập mã loại hỗ trợ.',
            'code.max' => 'Mã loại hỗ trợ không được vượt quá 50 ký tự.',
            'code.regex' => 'Mã loại hỗ trợ chỉ gồm chữ, số, dấu gạch ngang và gạch dưới.',
            'code.unique' => 'Mã loại hỗ trợ đã tồn tại.',
            'description.max' => 'Mô tả không được vượt quá 2000 ký tự.',
            'department_id.required' => 'Vui lòng chọn phòng ban phụ trách.',
            'department_id.exists' => 'Phòng ban không tồn tại.',
            'sla_days.integer' => 'Số ngày xử lý phải là số nguyên.',
            'sla_days.min' => 'Số ngày xử lý phải từ 1 đến 365.',
            'sla_days.max' => 'Số ngày xử lý phải từ 1 đến 365.',
        ];
    }
}
