<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SupportTypeResource;
use App\Models\SupportDepartment;
use App\Models\SupportFaq;
use App\Models\SupportType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class CatalogController extends Controller
{
    /**
     * Danh sách phòng ban đang hoạt động.
     */
    public function departments(Request $request): JsonResponse
    {
        $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $departments = SupportDepartment::query()
            ->where('is_active', true)
            ->select([
                'id',
                'name',
                'code',
                'description',
            ])
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return response()->json($departments);
    }

    /**
     * Loại hỗ trợ đang hoạt động,
     * thuộc phòng ban đang hoạt động.
     */
    public function supportTypes(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:150'],
            'department_id' => [
                'nullable',
                'integer',
                'exists:support_departments,id',
            ],
        ]);

        $query = SupportType::query()
            ->where('is_active', true)
            ->whereHas('department', function ($q) {
                $q->where('is_active', true);
            })
            ->with('department:id,name,code,is_active');

        if (isset($filters['department_id'])) {
            $query->where(
                'department_id',
                $filters['department_id']
            );
        }

        $search = trim($filters['search'] ?? '');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $paginator = $query
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        $paginator->through(function ($supportType) use ($request) {
            return (new SupportTypeResource($supportType))
                ->resolve($request);
        });

        return response()->json($paginator);
    }

    /**
     * Lấy cấu hình biểu mẫu và SLA của một loại hỗ trợ.
     */
    public function form(
        Request $request,
        SupportType $supportType
    ): JsonResponse {
        $this->ensureAvailable($supportType);

        $fields = $supportType->fields()
            ->where('is_active', true)
            ->get([
                'id',
                'support_type_id',
                'field_key',
                'label',
                'field_type',
                'is_required',
                'options',
                'help_text',
                'sort_order',
            ]);

        return response()->json([
            'data' => [
                'support_type' => (
                    new SupportTypeResource($supportType)
                )->resolve($request),

                'fields' => $fields,
            ],
        ]);
    }

    /**
     * FAQ của loại hỗ trợ:
     * - FAQ chung của phòng phụ trách.
     * - FAQ riêng của loại hỗ trợ đang chọn.
     */
    public function faqs(
        Request $request,
        SupportType $supportType
    ): JsonResponse {
        $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $this->ensureAvailable($supportType);

        $faqs = SupportFaq::query()
            ->where('is_active', true)
            ->where('department_id', $supportType->department_id)
            ->where(function ($query) use ($supportType) {
                $query->whereNull('support_type_id')
                    ->orWhere('support_type_id', $supportType->id);
            })
            ->select([
                'id',
                'department_id',
                'support_type_id',
                'question',
                'answer',
                'sort_order',
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        return response()->json($faqs);
    }

    /**
     * FAQ theo phòng ban.
     * Bao gồm FAQ chung và FAQ của các loại hỗ trợ đang hoạt động.
     */
    public function departmentFaqs(
        Request $request,
        SupportDepartment $department
    ): JsonResponse {
        $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        abort_unless(
            $department->is_active,
            404,
            'Phòng ban hiện không khả dụng.'
        );

        $faqs = SupportFaq::query()
            ->where('is_active', true)
            ->where('department_id', $department->id)
            ->where(function ($query) use ($department) {
                $query->whereNull('support_type_id')
                    ->orWhereHas('supportType', function ($typeQuery) use ($department) {
                        $typeQuery->where('is_active', true)
                            ->where('department_id', $department->id);
                    });
            })
            ->select([
                'id',
                'department_id',
                'support_type_id',
                'question',
                'answer',
                'sort_order',
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        return response()->json($faqs);
    }

    /**
     * Kiểm tra dữ liệu sinh viên nhập theo biểu mẫu của loại hỗ trợ.
     *
     * Module 3 gọi API này trước khi lưu yêu cầu:
     * - 200: dữ liệu hợp lệ, trả về các giá trị đã chuẩn hóa.
     * - 422: liệt kê trường thiếu / sai theo tên hiển thị.
     *
     * Trường kiểu file nhận tệp tải lên hoặc tên / mã tệp đã tải lên Module 3.
     */
    public function validateForm(
        Request $request,
        SupportType $supportType
    ): JsonResponse {
        $this->ensureAvailable($supportType);

        $request->validate([
            'values' => ['nullable', 'array'],
        ]);

        $fields = $supportType->fields()
            ->where('is_active', true)
            ->get();

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

        $validated = $request->validate($rules, [
            'required' => 'Vui lòng nhập :attribute.',
            'in' => ':attribute không nằm trong danh sách lựa chọn.',
            'numeric' => ':attribute phải là số.',
            'date' => ':attribute không phải ngày hợp lệ.',
            'max' => ':attribute quá dài.',
        ], $attributes);

        // Chỉ giữ các trường có trong biểu mẫu, bỏ khóa lạ.
        $values = [];

        foreach ($fields as $field) {
            $value = $validated['values'][$field->field_key] ?? null;

            if ($value instanceof UploadedFile) {
                $value = $value->getClientOriginalName();
            }

            $values[$field->field_key] = $value;
        }

        return response()->json([
            'message' => 'Thông tin biểu mẫu hợp lệ.',
            'data' => [
                'support_type_id' => $supportType->id,
                'sla_days' => $supportType->sla_days,
                'values' => $values,
            ],
        ]);
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
                $valid = $value instanceof UploadedFile
                    ? $value->isValid() && $value->getSize() <= 10 * 1024 * 1024
                    : is_string($value) && trim($value) !== '' && mb_strlen($value) <= 255;

                if (! $valid) {
                    $fail(':attribute phải là tệp hợp lệ (tối đa 10MB).');
                }
            }],
            default => ['string', 'max:255'],
        };
    }

    /**
     * Chặn đọc cấu hình của loại hỗ trợ hoặc phòng ban đã tắt.
     */
    private function ensureAvailable(SupportType $supportType): void
    {
        $supportType->load('department:id,name,code,is_active');

        abort_unless(
            $supportType->is_active
                && $supportType->department
                && $supportType->department->is_active,
            404,
            'Loại hỗ trợ hiện không khả dụng.'
        );
    }
}