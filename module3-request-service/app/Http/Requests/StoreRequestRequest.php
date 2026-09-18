<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'department_id' => 'required|integer',
            'support_type_id' => 'required|integer',
            'title' => 'required|string|min:10|max:255',
            'content' => 'required|string|min:20',
            'priority' => 'nullable|in:low,normal,high,urgent',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Vui lòng nhập tiêu đề yêu cầu.',
            'title.min' => 'Tiêu đề phải có ít nhất 10 ký tự.',
            'content.required' => 'Vui lòng nhập nội dung yêu cầu.',
            'content.min' => 'Nội dung phải có ít nhất 20 ký tự.',
            'department_id.required' => 'Vui lòng chọn phòng ban.',
            'support_type_id.required' => 'Vui lòng chọn loại hỗ trợ.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $typeId = (int) $this->input('support_type_id');
            $deptId = (int) $this->input('department_id');
            $types = config('master_data.support_types', []);

            if ($typeId && isset($types[$typeId])) {
                if ((int) $types[$typeId]['department_id'] !== $deptId) {
                    $v->errors()->add(
                        'support_type_id',
                        'Loại hỗ trợ không thuộc phòng ban đã chọn.'
                    );
                }
            } elseif ($typeId) {
                $v->errors()->add('support_type_id', 'Loại hỗ trợ không hợp lệ.');
            }
        });
    }
}
