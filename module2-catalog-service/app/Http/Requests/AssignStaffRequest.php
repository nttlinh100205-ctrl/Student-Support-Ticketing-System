<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // chỉ department_head/admin — kiểm tra ở Controller
    }

    public function rules(): array
    {
        return [
            // assigned_to là soft reference tới id user (staff) ở Module 1
            'assigned_to' => 'required|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'assigned_to.required' => 'Vui lòng chọn cán bộ xử lý.',
        ];
    }
}
