<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupportTypeFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // quyền ADMIN đã kiểm tra ở middleware role:admin
    }

    public function rules(): array
    {
        return [
            'field_key' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z][a-z0-9_]*$/',
                // Mã trường chỉ cần duy nhất trong cùng một loại hỗ trợ; khi sửa thì bỏ qua chính trường đó.
                Rule::unique('support_type_fields', 'field_key')
                    ->where('support_type_id', $this->route('supportType')->id)
                    ->ignore($this->route('field')),
            ],
            'label' => ['required', 'string', 'max:150'],
            'field_type' => ['required', Rule::in(['text', 'textarea', 'number', 'date', 'select', 'file'])],
            'is_required' => ['sometimes', 'boolean'],
            // Chỉ kiểu select mới có danh sách lựa chọn; kiểu khác thì bỏ qua options.
            'options' => ['exclude_unless:field_type,select', 'required_if:field_type,select', 'array', 'min:1', 'max:50'],
            'options.*' => ['required', 'string', 'max:150', 'distinct'],
            'help_text' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'field_key.required' => 'Vui lòng nhập mã trường.',
            'field_key.regex' => 'Mã trường bắt đầu bằng chữ thường, chỉ gồm chữ thường, số và dấu gạch dưới.',
            'field_key.unique' => 'Mã trường đã tồn tại trong loại hỗ trợ này.',
            'label.required' => 'Vui lòng nhập tên hiển thị.',
            'field_type.required' => 'Vui lòng chọn kiểu trường.',
            'field_type.in' => 'Kiểu trường không hợp lệ.',
            'options.required_if' => 'Kiểu danh sách chọn phải có ít nhất một lựa chọn.',
            'options.array' => 'Các lựa chọn phải được gửi dưới dạng danh sách.',
            'options.min' => 'Cần ít nhất một lựa chọn.',
            'options.max' => 'Chỉ được cấu hình tối đa 50 lựa chọn.',
            'options.*.required' => 'Lựa chọn không được để trống.',
            'options.*.distinct' => 'Các lựa chọn không được trùng nhau.',
        ];
    }

    /**
     * Dữ liệu đã kiểm tra, kèm options đã chuẩn hóa:
     * kiểu select giữ danh sách lựa chọn, kiểu khác thì options = null.
     *
     * Khoảng trắng thừa đã được middleware TrimStrings của Laravel cắt sẵn,
     * nên nhãn hoặc lựa chọn chỉ gồm dấu cách sẽ bị rule required chặn.
     */
    public function fieldData(): array
    {
        $data = $this->validated();

        $data['options'] = $data['field_type'] === 'select'
            ? array_values($data['options'])
            : null;

        return $data;
    }
}
