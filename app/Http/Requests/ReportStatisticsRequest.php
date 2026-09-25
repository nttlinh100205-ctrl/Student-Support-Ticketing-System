<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportStatisticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // quyền hạn đã chặn ở middleware EnsureCanViewReports
    }

    public function rules(): array
    {
        return [
            'department_id'   => 'nullable|integer',
            'support_type_id' => 'nullable|integer',
            'from_date'       => 'nullable|date',
            'to_date'         => 'nullable|date|after_or_equal:from_date',
        ];
    }
}