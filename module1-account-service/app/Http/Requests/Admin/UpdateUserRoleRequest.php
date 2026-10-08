<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => [
                'required',
                'string',
                Rule::in([
                    'student',
                    'staff',
                    'department_head',
                    'admin',
                ]),
            ],
            'department_id' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ];
    }
}
