<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SupportTypeResource;
use App\Http\Responses\ApiResponse;
use App\Models\SupportDepartment;
use App\Models\SupportType;
use App\Services\CatalogService;
use App\Services\FormValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Tra cứu danh mục (mọi tài khoản đã xác thực): sinh viên xem trước khi gửi yêu cầu,
 * Module 3 gọi để lấy SLA, biểu mẫu và kiểm tra dữ liệu biểu mẫu.
 */
class CatalogController extends Controller
{
    private const PAGE_RULE = ['page' => ['nullable', 'integer', 'min:1']];

    public function __construct(
        private CatalogService $catalog,
        private FormValidationService $formValidation,
    ) {}

    /**
     * Danh sách phòng ban đang hoạt động.
     */
    public function departments(Request $request): JsonResponse
    {
        $request->validate(self::PAGE_RULE);

        return ApiResponse::success($this->catalog->paginateDepartments());
    }

    /**
     * Loại hỗ trợ đang hoạt động, thuộc phòng ban đang hoạt động.
     */
    public function supportTypes(Request $request): JsonResponse
    {
        $filters = $request->validate(self::PAGE_RULE + [
            'search' => ['nullable', 'string', 'max:150'],
            'department_id' => ['nullable', 'integer', 'exists:support_departments,id'],
        ]);

        $paginator = $this->catalog->paginateSupportTypes($filters)
            ->through(fn ($supportType) => (new SupportTypeResource($supportType))->resolve($request));

        return ApiResponse::success($paginator);
    }

    /**
     * Lấy cấu hình biểu mẫu và SLA của một loại hỗ trợ.
     */
    public function form(Request $request, SupportType $supportType): JsonResponse
    {
        $this->catalog->ensureAvailable($supportType);

        return ApiResponse::success([
            'support_type' => (new SupportTypeResource($supportType))->resolve($request),
            'fields' => $this->catalog->activeFields($supportType),
        ]);
    }

    /**
     * FAQ của loại hỗ trợ: FAQ chung của phòng phụ trách + FAQ riêng của loại đang chọn.
     */
    public function faqs(Request $request, SupportType $supportType): JsonResponse
    {
        $request->validate(self::PAGE_RULE);

        $this->catalog->ensureAvailable($supportType);

        return ApiResponse::success($this->catalog->paginateSupportTypeFaqs($supportType));
    }

    /**
     * FAQ theo phòng ban: FAQ chung và FAQ của các loại hỗ trợ đang hoạt động.
     */
    public function departmentFaqs(Request $request, SupportDepartment $department): JsonResponse
    {
        $request->validate(self::PAGE_RULE);

        $this->catalog->ensureDepartmentAvailable($department);

        return ApiResponse::success($this->catalog->paginateDepartmentFaqs($department));
    }

    /**
     * Kiểm tra dữ liệu biểu mẫu cho Module 3:
     * - 200: dữ liệu hợp lệ, trả về các giá trị đã chuẩn hóa.
     * - 422: liệt kê trường thiếu / sai theo tên hiển thị.
     */
    public function validateForm(Request $request, SupportType $supportType): JsonResponse
    {
        $this->catalog->ensureAvailable($supportType);

        $values = $this->formValidation->validate($supportType, $request->all());

        return ApiResponse::success([
            'support_type_id' => $supportType->id,
            'sla_days' => $supportType->sla_days,
            'values' => $values,
        ], 'Thông tin biểu mẫu hợp lệ.');
    }
}
