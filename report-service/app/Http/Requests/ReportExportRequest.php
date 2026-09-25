<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // quyền hạn đã chặn ở middleware EnsureCanViewReports
    }

    public function rules(): array
    {
        return [
            'department_id' => 'nullable|integer',
            'support_type_id' => 'nullable|integer',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ];
    }

    public function messages(): array
    {
        return [
            'department_id.integer' => 'Mã phòng ban phải là số nguyên.',
            'support_type_id.integer' => 'Mã loại yêu cầu phải là số nguyên.',
            'from_date.date' => 'Ngày bắt đầu không đúng định dạng ngày tháng.',
            'to_date.date' => 'Ngày kết thúc không đúng định dạng ngày tháng.',
            'to_date.after_or_equal' => 'Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.',
        ];
    }
}
