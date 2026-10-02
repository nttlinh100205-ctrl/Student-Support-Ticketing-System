<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportType;
use App\Models\SupportTypeField;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SupportTypeFieldController extends Controller
{
    /**
     * Danh sách trường của một loại hỗ trợ.
     */
    public function index(
        Request $request,
        SupportType $supportType
    ): JsonResponse {
        $filters = $request->validate([
            'is_active' => ['nullable', 'boolean'],
        ]);

        $query = $supportType->fields();

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return response()->json([
            'data' => $query->get(),
        ]);
    }

    /**
     * Thêm trường vào biểu mẫu.
     */
    public function store(
        Request $request,
        SupportType $supportType
    ): JsonResponse {
        $data = $this->validateData($request, $supportType);

        $data['is_required'] = $data['is_required'] ?? false;
        $data['is_active'] = $data['is_active'] ?? true;
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $field = $supportType->fields()->create($data);

        return response()->json([
            'message' => 'Thêm trường biểu mẫu thành công.',
            'data' => $field,
        ], 201);
    }

    /**
     * Xem chi tiết trường.
     */
    public function show(
        SupportType $supportType,
        SupportTypeField $field
    ): JsonResponse {
        $this->ensureBelongsToType($supportType, $field);

        return response()->json([
            'data' => $field,
        ]);
    }

    /**
     * Sửa cấu hình trường.
     */
    public function update(
        Request $request,
        SupportType $supportType,
        SupportTypeField $field
    ): JsonResponse {
        $this->ensureBelongsToType($supportType, $field);

        $data = $this->validateData(
            $request,
            $supportType,
            $field
        );

        $field->update($data);

        return response()->json([
            'message' => 'Cập nhật trường biểu mẫu thành công.',
            'data' => $field->fresh(),
        ]);
    }

    /**
     * Bật/tắt trường mà không cần gửi lại toàn bộ thông tin.
     */
    public function updateStatus(
        Request $request,
        SupportType $supportType,
        SupportTypeField $field
    ): JsonResponse {
        $this->ensureBelongsToType($supportType, $field);

        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $field->update($data);

        return response()->json([
            'message' => $field->is_active
                ? 'Đã bật trường biểu mẫu.'
                : 'Đã tắt trường biểu mẫu.',
            'data' => $field->fresh(),
        ]);
    }

    /**
     * Không cho truy cập trường thuộc loại hỗ trợ khác.
     */
    private function ensureBelongsToType(
        SupportType $supportType,
        SupportTypeField $field
    ): void {
        abort_unless(
            (int) $field->support_type_id === (int) $supportType->id,
            404,
            'Không tìm thấy trường trong loại hỗ trợ này.'
        );
    }

    /**
     * Kiểm tra và chuẩn hóa cấu hình.
     */
    private function validateData(
        Request $request,
        SupportType $supportType,
        ?SupportTypeField $field = null
    ): array {
        $uniqueKey = Rule::unique(
            'support_type_fields',
            'field_key'
        )->where('support_type_id', $supportType->id);

        if ($field !== null) {
            $uniqueKey->ignore($field->id);
        }

        $data = $request->validate([
            'field_key' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z][a-z0-9_]*$/',
                $uniqueKey,
            ],

            'label' => [
                'required',
                'string',
                'max:150',
            ],

            'field_type' => [
                'required',
                Rule::in([
                    'text',
                    'textarea',
                    'number',
                    'date',
                    'select',
                    'file',
                ]),
            ],

            'is_required' => [
                'sometimes',
                'boolean',
            ],

            'options' => [
                'exclude_unless:field_type,select',
                'required_if:field_type,select',
                'array',
                'min:1',
                'max:50',
            ],

            'options.*' => [
                'required',
                'string',
                'max:150',
                'distinct',
            ],

            'help_text' => [
                'nullable',
                'string',
                'max:500',
            ],

            'sort_order' => [
                'sometimes',
                'integer',
                'min:0',
                'max:65535',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ], [
            'field_key.required' => 'Vui lòng nhập mã trường.',
            'field_key.regex' =>
                'Mã trường bắt đầu bằng chữ thường, chỉ gồm chữ thường, số và dấu gạch dưới.',
            'field_key.unique' =>
                'Mã trường đã tồn tại trong loại hỗ trợ này.',
            'label.required' => 'Vui lòng nhập tên hiển thị.',
            'field_type.required' => 'Vui lòng chọn kiểu trường.',
            'field_type.in' => 'Kiểu trường không hợp lệ.',
            'options.required_if' =>
                'Kiểu danh sách chọn phải có ít nhất một lựa chọn.',
            'options.array' =>
                'Các lựa chọn phải được gửi dưới dạng danh sách.',
            'options.min' => 'Cần ít nhất một lựa chọn.',
            'options.max' => 'Chỉ được cấu hình tối đa 50 lựa chọn.',
            'options.*.required' => 'Lựa chọn không được để trống.',
            'options.*.distinct' => 'Các lựa chọn không được trùng nhau.',
        ]);

        $data['label'] = trim($data['label']);

        if ($data['label'] === '') {
            throw ValidationException::withMessages([
                'label' => ['Tên hiển thị không được để trống.'],
            ]);
        }

        if ($data['field_type'] === 'select') {
            $options = array_map(
                fn ($value) => trim($value),
                $data['options']
            );

            if (in_array('', $options, true)) {
                throw ValidationException::withMessages([
                    'options' => ['Lựa chọn không được để trống.'],
                ]);
            }

            if (
                count($options) !==
                count(array_unique($options, SORT_STRING))
            ) {
                throw ValidationException::withMessages([
                    'options' => ['Các lựa chọn không được trùng nhau.'],
                ]);
            }

            $data['options'] = array_values($options);
        } else {
            $data['options'] = null;
        }

        return $data;
    }
}