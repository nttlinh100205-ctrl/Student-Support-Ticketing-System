<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // quyền ADMIN đã kiểm tra ở middleware role:admin
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9_-]+$/',
                // Khi sửa: bỏ qua chính phòng ban đang sửa. Khi thêm: route không có {department} nên không bỏ qua gì.
                Rule::unique('support_departments', 'code')->ignore($this->route('department')),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên phòng ban.',
            'name.max' => 'Tên phòng ban không được vượt quá 100 ký tự.',
            'code.required' => 'Vui lòng nhập mã phòng ban.',
            'code.max' => 'Mã phòng ban không được vượt quá 50 ký tự.',
            'code.regex' => 'Mã phòng ban chỉ gồm chữ, số, dấu gạch ngang và gạch dưới.',
            'code.unique' => 'Mã phòng ban đã tồn tại.',
            'description.max' => 'Mô tả không được vượt quá 2000 ký tự.',
        ];
    }
}
