<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate dữ liệu khi tạo comment mới.
 *
 * - body: bắt buộc, tối đa 5000 ký tự.
 * - is_internal: tùy chọn, chỉ staff/head/admin được đặt.
 * - attachments: tùy chọn, tối đa 5 file, mỗi file ≤ 10 MB.
 */
class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Quyền kiểm tra ở controller
    }

    public function rules(): array
    {
        return [
            'body'          => ['required', 'string', 'max:5000'],
            'is_internal'   => ['sometimes', 'boolean'],
            'attachments'   => ['sometimes', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240'], // 10 MB
        ];
    }

    public function messages(): array
    {
        return [
            'body.required'      => 'Nội dung bình luận không được để trống.',
            'body.max'           => 'Nội dung bình luận tối đa 5000 ký tự.',
            'attachments.max'    => 'Chỉ được đính kèm tối đa 5 file.',
            'attachments.*.max'  => 'Mỗi file đính kèm không quá 10 MB.',
            'attachments.*.file' => 'File đính kèm không hợp lệ.',
        ];
    }
}
