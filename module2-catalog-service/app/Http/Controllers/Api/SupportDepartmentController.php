<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SupportDepartmentResource;
use App\Http\Responses\ApiResponse;
use App\Models\SupportDepartment;
use App\Services\RequestUsage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupportDepartmentController extends Controller
{
    // Danh sách phòng ban: tìm kiếm, lọc và phân trang.
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = SupportDepartment::query()
            ->withCount(['staff', 'heads']);

        $search = trim($filters['search'] ?? '');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // isset vẫn nhận giá trị 0, nhưng bỏ qua null.
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        $paginator = $query
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        $paginator->through(function ($department) use ($request) {
            return (new SupportDepartmentResource($department))
                ->resolve($request);
        });

        return ApiResponse::success($paginator);
    }

    // Thêm phòng ban.
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('support_departments', 'code'),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['is_active'] = $data['is_active'] ?? true;

        $department = SupportDepartment::create($data);
        $department->loadCount(['staff', 'heads']);

        return ApiResponse::success(
            (new SupportDepartmentResource($department))->resolve($request),
            'Thêm phòng ban thành công.',
            201
        );
    }

    // Xem chi tiết phòng ban.
    public function show(
        Request $request,
        SupportDepartment $department
    ): JsonResponse {
        $department->loadCount(['staff', 'heads']);

        return ApiResponse::success(
            (new SupportDepartmentResource($department))->resolve($request)
        );
    }

    // Sửa thông tin hoặc bật/tắt phòng ban.
    public function update(
        Request $request,
        SupportDepartment $department
    ): JsonResponse {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('support_departments', 'code')
                    ->ignore($department->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $department->update($data);
        $department->refresh();
        $department->loadCount(['staff', 'heads']);

        return ApiResponse::success(
            (new SupportDepartmentResource($department))->resolve($request),
            'Cập nhật phòng ban thành công.'
        );
    }

    // Danh sách cán bộ và trưởng phòng thuộc phòng ban.
    public function staff(
        Request $request,
        SupportDepartment $department
    ): JsonResponse {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => [
                'nullable',
                Rule::in(['STAFF', 'DEPARTMENT_HEAD']),
            ],
            'status' => [
                'nullable',
                Rule::in(['ACTIVE', 'LOCKED']),
            ],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = $department->users()
            ->select([
                'id',
                'name',
                'email',
                'role',
                'status',
                'department_id',
            ])
            ->whereIn('role', ['STAFF', 'DEPARTMENT_HEAD']);

        $search = trim($filters['search'] ?? '');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return ApiResponse::success(
            $query
                ->orderBy('name')
                ->orderBy('id')
                ->paginate(10)
                ->withQueryString()
        );
    }

    // Chỉ xóa phòng ban khi không còn dữ liệu liên kết.
    // Sai nghiệp vụ trả 409 (dữ liệu gửi lên không sai nên không dùng 422).
    public function destroy(
        SupportDepartment $department
    ): JsonResponse {
        if ($department->users()->exists()) {
            return ApiResponse::error(
                'Không thể xóa phòng ban vì vẫn có tài khoản thuộc phòng ban này.',
                409
            );
        }

        if ($department->supportTypes()->exists()) {
            return ApiResponse::error(
                'Không thể xóa phòng ban vì vẫn có loại hỗ trợ liên kết.',
                409
            );
        }

        if (RequestUsage::usesDepartment($department->id)) {
            return ApiResponse::error(
                'Không thể xóa phòng ban vì đã có yêu cầu hỗ trợ liên quan. Hãy chuyển sang ngừng hoạt động.',
                409
            );
        }

        $department->delete();

        return ApiResponse::success(null, 'Xóa phòng ban thành công.');
    }
}
