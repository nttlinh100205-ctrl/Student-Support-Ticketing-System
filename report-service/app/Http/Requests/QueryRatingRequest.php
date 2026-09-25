<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QueryRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'department_id' => 'nullable|integer',
            'support_type_id' => 'nullable|integer',
            'rating' => 'nullable|integer|min:1|max:5',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'department_id.integer' => 'Mã phòng ban phải là số nguyên.',
            'support_type_id.integer' => 'Mã loại yêu cầu phải là số nguyên.',
            'rating.integer' => 'Mức đánh giá phải là số nguyên.',
            'from_date.date' => 'Ngày bắt đầu không hợp lệ.',
            'to_date.date' => 'Ngày kết thúc không hợp lệ.',
            'to_date.after_or_equal' => 'Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.',
        ];
    }
}
