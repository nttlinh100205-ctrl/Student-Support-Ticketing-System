<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'request_id' => 'required|integer',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'request_id.required' => 'Vui lòng cung cấp mã yêu cầu cần đánh giá.',
            'request_id.integer' => 'Mã yêu cầu phải là số nguyên.',
            'rating.required' => 'Vui lòng chọn mức độ đánh giá (từ 1 đến 5 sao).',
            'rating.integer' => 'Mức độ đánh giá phải là số nguyên.',
            'rating.min' => 'Đánh giá tối thiểu là 1 sao.',
            'rating.max' => 'Đánh giá tối đa là 5 sao.',
            'comment.max' => 'Nhận xét không được vượt quá 1000 ký tự.',
        ];
    }
}
