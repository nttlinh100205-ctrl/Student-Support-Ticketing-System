<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Resources\SupportDepartmentResource;
use App\Http\Responses\ApiResponse;
use App\Models\SupportDepartment;
use App\Services\DepartmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SupportDepartmentController extends Controller
{
    public function __construct(private DepartmentService $departments) {}

    // Danh sách phòng ban: tìm kiếm, lọc và phân trang.
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $paginator = $this->departments->paginate($filters)
            ->through(fn ($department) => (new SupportDepartmentResource($department))->resolve($request));

        return ApiResponse::success($paginator);
    }

    // Thêm phòng ban.
    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $department = $this->departments->create($request->validated());

        return ApiResponse::success(
            (new SupportDepartmentResource($department))->resolve($request),
            'Thêm phòng ban thành công.',
            201
        );
    }

    // Xem chi tiết phòng ban.
    public function show(Request $request, SupportDepartment $department): JsonResponse
    {
        $department = $this->departments->withStaffCounts($department);

        return ApiResponse::success(
            (new SupportDepartmentResource($department))->resolve($request)
        );
    }

    // Sửa thông tin hoặc bật/tắt phòng ban.
    public function update(UpdateDepartmentRequest $request, SupportDepartment $department): JsonResponse
    {
        $department = $this->departments->update($department, $request->validated());

        return ApiResponse::success(
            (new SupportDepartmentResource($department))->resolve($request),
            'Cập nhật phòng ban thành công.'
        );
    }

    // Danh sách cán bộ và trưởng phòng thuộc phòng ban.
    public function staff(Request $request, SupportDepartment $department): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::in(UserRole::staffValues())],
            'status' => ['nullable', Rule::in(['ACTIVE', 'LOCKED'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        return ApiResponse::success($this->departments->paginateStaff($department, $filters));
    }

    // Sai nghiệp vụ (còn dữ liệu liên kết) trả 409, không dùng 422 vì dữ liệu gửi lên không sai.
    public function destroy(SupportDepartment $department): JsonResponse
    {
        try {
            $this->departments->delete($department);

            return ApiResponse::success(null, 'Xóa phòng ban thành công.');
        } catch (ValidationException $e) {
            return ApiResponse::error($e->getMessage(), 409);
        }
    }
}
