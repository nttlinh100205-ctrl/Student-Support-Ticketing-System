<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,docx', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Vui lòng chọn file đính kèm.',
            'file.file' => 'File đính kèm không hợp lệ.',
            'file.mimes' => 'Chỉ chấp nhận file PDF, JPG, JPEG, PNG, DOCX.',
            'file.max' => 'File đính kèm tối đa 10MB.',
        ];
    }
}
