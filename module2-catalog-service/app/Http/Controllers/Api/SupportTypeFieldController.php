<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupportTypeFieldRequest;
use App\Http\Requests\UpdateStatusRequest;
use App\Http\Requests\UpdateSupportTypeFieldRequest;
use App\Http\Responses\ApiResponse;
use App\Models\SupportType;
use App\Models\SupportTypeField;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        return ApiResponse::success($query->get());
    }

    /**
     * Thêm trường vào biểu mẫu.
     */
    public function store(
        StoreSupportTypeFieldRequest $request,
        SupportType $supportType
    ): JsonResponse {
        $data = $request->fieldData();

        $data['is_required'] = $data['is_required'] ?? false;
        $data['is_active'] = $data['is_active'] ?? true;
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $field = $supportType->fields()->create($data);

        return ApiResponse::success($field, 'Thêm trường biểu mẫu thành công.', 201);
    }

    /**
     * Xem chi tiết trường.
     */
    public function show(
        SupportType $supportType,
        SupportTypeField $field
    ): JsonResponse {
        $this->ensureBelongsToType($supportType, $field);

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
        $this->ensureBelongsToType($supportType, $field);

        $field->update($request->fieldData());

        return ApiResponse::success($field->fresh(), 'Cập nhật trường biểu mẫu thành công.');
    }

    /**
     * Bật/tắt trường mà không cần gửi lại toàn bộ thông tin.
     */
    public function updateStatus(
        UpdateStatusRequest $request,
        SupportType $supportType,
        SupportTypeField $field
    ): JsonResponse {
        $this->ensureBelongsToType($supportType, $field);

        $field->update($request->validated());

        return ApiResponse::success(
            $field->fresh(),
            $field->is_active
                ? 'Đã bật trường biểu mẫu.'
                : 'Đã tắt trường biểu mẫu.'
        );
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
}
