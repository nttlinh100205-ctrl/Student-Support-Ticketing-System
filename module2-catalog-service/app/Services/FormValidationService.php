<?php

namespace App\Services;

use App\Models\SupportType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Kiểm tra dữ liệu sinh viên nhập theo biểu mẫu của loại hỗ trợ.
 *
 * Module 3 gọi API này trước khi lưu yêu cầu. Rule được sinh động
 * từ cấu hình các trường đang bật nên không đặt trong Form Request.
 */
class FormValidationService
{
    private const MAX_FILE_SIZE_MB = 10;

    private const MESSAGES = [
        'required' => 'Vui lòng nhập :attribute.',
        'in' => ':attribute không nằm trong danh sách lựa chọn.',
        'numeric' => ':attribute phải là số.',
        'date' => ':attribute không phải ngày hợp lệ.',
        'max' => ':attribute quá dài.',
    ];

    /**
     * Trả về giá trị đã chuẩn hóa, chỉ gồm các trường có trong biểu mẫu (bỏ khóa lạ).
     * Ném ValidationException (422) kèm lỗi theo tên hiển thị của từng trường.
     *
     * Trường kiểu file nhận tệp tải lên hoặc tên / mã tệp đã tải lên Module 3.
     */
    public function validate(SupportType $supportType, array $input): array
    {
        Validator::make(
            $input,
            ['values' => ['nullable', 'array']],
            ['values.array' => 'Dữ liệu biểu mẫu không đúng định dạng.']
        )->validate();

        $fields = $supportType->fields()->where('is_active', true)->get();

        $rules = [];
        $attributes = [];

        foreach ($fields as $field) {
            $key = "values.{$field->field_key}";

            $rules[$key] = array_merge(
                [$field->is_required ? 'required' : 'nullable'],
                $this->valueRules($field->field_type, $field->options ?? [])
            );
            $attributes[$key] = $field->label;
        }

        $validated = Validator::make($input, $rules, self::MESSAGES, $attributes)->validate();

        $values = [];

        foreach ($fields as $field) {
            $value = $validated['values'][$field->field_key] ?? null;

            $values[$field->field_key] = $value instanceof UploadedFile
                ? $value->getClientOriginalName()
                : $value;
        }

        return $values;
    }

    /**
     * Quy tắc kiểm tra theo kiểu trường.
     */
    private function valueRules(string $type, array $options): array
    {
        return match ($type) {
            'number' => ['numeric'],
            'date' => ['date'],
            'select' => [Rule::in($options)],
            'textarea' => ['string', 'max:5000'],
            'file' => [function (string $attribute, $value, $fail) {
                $isValid = $value instanceof UploadedFile
                    ? $value->isValid() && $value->getSize() <= self::MAX_FILE_SIZE_MB * 1024 * 1024
                    : is_string($value) && trim($value) !== '' && mb_strlen($value) <= 255;

                if (! $isValid) {
                    $fail(':attribute phải là tệp hợp lệ (tối đa '.self::MAX_FILE_SIZE_MB.'MB).');
                }
            }],
            default => ['string', 'max:255'],
        };
    }
}
