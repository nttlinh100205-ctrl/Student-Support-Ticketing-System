<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupportTypeRequest;
use App\Http\Requests\UpdateSupportTypeRequest;
use App\Http\Resources\SupportTypeResource;
use App\Http\Responses\ApiResponse;
use App\Models\SupportType;
use App\Services\SupportTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SupportTypeController extends Controller
{
    public function __construct(private SupportTypeService $supportTypes) {}

    /**
     * Danh sách loại hỗ trợ: tìm theo tên / mã, lọc phòng ban,
     * lọc trạng thái và phân trang.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'department_id' => ['nullable', 'integer', 'exists:support_departments,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $paginator = $this->supportTypes->paginate($filters)
            ->through(fn ($supportType) => (new SupportTypeResource($supportType))->resolve($request));

        return ApiResponse::success($paginator);
    }

    /**
     * Thêm loại hỗ trợ.
     */
    public function store(StoreSupportTypeRequest $request): JsonResponse
    {
        $supportType = $this->supportTypes->create($request->validated());

        return ApiResponse::success(
            (new SupportTypeResource($supportType))->resolve($request),
            'Thêm loại hỗ trợ thành công.',
            201
        );
    }

    /**
     * Xem chi tiết loại hỗ trợ.
     */
    public function show(Request $request, SupportType $supportType): JsonResponse
    {
        $supportType = $this->supportTypes->withDepartment($supportType);

        return ApiResponse::success(
            (new SupportTypeResource($supportType))->resolve($request)
        );
    }

    /**
     * Cập nhật loại hỗ trợ: tên, mã, phòng phụ trách, mô tả, SLA, bật / tắt.
     */
    public function update(UpdateSupportTypeRequest $request, SupportType $supportType): JsonResponse
    {
        $supportType = $this->supportTypes->update($supportType, $request->validated());

        return ApiResponse::success(
            (new SupportTypeResource($supportType))->resolve($request),
            'Cập nhật loại hỗ trợ thành công.'
        );
    }

    /**
     * Xóa loại hỗ trợ. Đã có yêu cầu sử dụng thì sai nghiệp vụ nên trả 409.
     */
    public function destroy(SupportType $supportType): JsonResponse
    {
        try {
            $this->supportTypes->delete($supportType);

            return ApiResponse::success(null, 'Xóa loại hỗ trợ thành công.');
        } catch (ValidationException $e) {
            return ApiResponse::error($e->getMessage(), 409);
        }
    }
}
