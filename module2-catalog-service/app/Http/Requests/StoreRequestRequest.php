<?php

namespace App\Http\Requests;

use App\Models\SupportDepartment;
use App\Models\SupportType;
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
            'department_id' => [
                'required',
                'integer',
            ],

            'support_type_id' => [
                'required',
                'integer',
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'content' => [
                'required',
                'string',
            ],

            'priority' => [
                'nullable',
                'in:low,normal,high,urgent',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {

                if (
                    $validator->errors()->has('department_id')
                    || $validator->errors()->has('support_type_id')
                ) {
                    return;
                }

                $departmentId =
                    (int) $this->input('department_id');

                $supportTypeId =
                    (int) $this->input('support_type_id');

                /*
                 * Phòng ban phải tồn tại
                 * và đang hoạt động.
                 */
                $departmentExists =
                    SupportDepartment::query()
                        ->whereKey($departmentId)
                        ->where('is_active', true)
                        ->exists();

                if (! $departmentExists) {
                    $validator->errors()->add(
                        'department_id',
                        'Phòng ban không tồn tại hoặc đã ngừng hoạt động.'
                    );

                    return;
                }

                /*
                 * Loại hỗ trợ phải:
                 * - tồn tại
                 * - đang hoạt động
                 * - thuộc đúng phòng ban đã chọn
                 */
                $supportTypeExists =
                    SupportType::query()
                        ->whereKey($supportTypeId)
                        ->where(
                            'department_id',
                            $departmentId
                        )
                        ->where(
                            'is_active',
                            true
                        )
                        ->exists();

                if (! $supportTypeExists) {
                    $validator->errors()->add(
                        'support_type_id',
                        'Loại hỗ trợ không tồn tại, đã ngừng hoạt động hoặc không thuộc phòng ban đã chọn.'
                    );
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Vui lòng nhập tiêu đề yêu cầu.',

            'department_id.required' => 'Vui lòng chọn phòng ban.',

            'support_type_id.required' => 'Vui lòng chọn loại hỗ trợ.',

            'content.required' => 'Vui lòng nhập nội dung yêu cầu.',
        ];
    }
}
