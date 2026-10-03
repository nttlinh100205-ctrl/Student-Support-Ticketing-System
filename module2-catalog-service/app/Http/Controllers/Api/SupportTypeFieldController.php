<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupportTypeFieldRequest;
use App\Http\Requests\UpdateStatusRequest;
use App\Http\Requests\UpdateSupportTypeFieldRequest;
use App\Http\Responses\ApiResponse;
use App\Models\SupportType;
use App\Models\SupportTypeField;
use App\Services\SupportTypeFieldService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportTypeFieldController extends Controller
{
    public function __construct(private SupportTypeFieldService $fields) {}

    /**
     * Danh sách trường của một loại hỗ trợ.
     */
    public function index(Request $request, SupportType $supportType): JsonResponse
    {
        $filters = $request->validate([
            'is_active' => ['nullable', 'boolean'],
        ]);

        return ApiResponse::success($this->fields->list($supportType, $filters));
    }

    /**
     * Thêm trường vào biểu mẫu.
     */
    public function store(StoreSupportTypeFieldRequest $request, SupportType $supportType): JsonResponse
    {
        $field = $this->fields->create($supportType, $request->fieldData());

        return ApiResponse::success($field, 'Thêm trường biểu mẫu thành công.', 201);
    }

    /**
     * Xem chi tiết trường.
     */
    public function show(SupportType $supportType, SupportTypeField $field): JsonResponse
    {
        $this->fields->ensureBelongsToType($supportType, $field);

        return ApiResponse::success($field);
    }

    /**
     * Sửa cấu hình trường.
     */
    public function update(
        UpdateSupportTypeFieldRequest $request,
        SupportType $supportType,
        SupportTypeField $field
    ): JsonResponse {
        $this->fields->ensureBelongsToType($supportType, $field);

        $field = $this->fields->update($field, $request->fieldData());

        return ApiResponse::success($field, 'Cập nhật trường biểu mẫu thành công.');
    }

    /**
     * Bật/tắt trường mà không cần gửi lại toàn bộ thông tin.
     */
    public function updateStatus(
        UpdateStatusRequest $request,
        SupportType $supportType,
        SupportTypeField $field
    ): JsonResponse {
        $this->fields->ensureBelongsToType($supportType, $field);

        $field = $this->fields->updateStatus($field, $request->boolean('is_active'));

        return ApiResponse::success(
            $field,
            $field->is_active ? 'Đã bật trường biểu mẫu.' : 'Đã tắt trường biểu mẫu.'
        );
    }
}
