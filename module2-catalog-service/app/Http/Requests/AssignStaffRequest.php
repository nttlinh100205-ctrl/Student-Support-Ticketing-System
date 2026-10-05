<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // quyền ADMIN đã kiểm tra ở middleware role:admin
    }

    public function rules(): array
    {
        return [
            'role' => ['required', Rule::in(UserRole::staffValues())],
        ];
    }

    public function messages(): array
    {
        return [
            'role.required' => 'Vui lòng chọn vai trò.',
            'role.in' => 'Vai trò không hợp lệ.',
        ];
    }
}
