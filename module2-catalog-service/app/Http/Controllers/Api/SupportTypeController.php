<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupportTypeRequest;
use App\Http\Requests\UpdateSupportTypeRequest;
use App\Http\Resources\SupportTypeResource;
use App\Http\Responses\ApiResponse;
use App\Models\SupportType;
use App\Services\RequestUsage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportTypeController extends Controller
{
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

        // Load phòng ban để SupportTypeResource trả kèm thông tin department.
        $query = SupportType::query()
            ->with(['department:id,name,code,is_active']);

        $search = trim($filters['search'] ?? '');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if (array_key_exists('department_id', $filters)) {
            $query->where('department_id', $filters['department_id']);
        }

        if (array_key_exists('is_active', $filters)) {
            $query->where('is_active', $filters['is_active']);
        }

        $paginator = $query
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $paginator->through(function ($supportType) use ($request) {
            return (new SupportTypeResource($supportType))->resolve($request);
        });

        return ApiResponse::success($paginator);
    }

    /**
     * Thêm loại hỗ trợ.
     */
    public function store(StoreSupportTypeRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Không truyền trạng thái thì mặc định đang hoạt động.
        $data['is_active'] = $data['is_active'] ?? true;

        $supportType = SupportType::create($data);
        $supportType->load('department:id,name,code,is_active');

        return ApiResponse::success(
            (new SupportTypeResource($supportType))->resolve($request),
            'Thêm loại hỗ trợ thành công.',
            201
        );
    }

    /**
     * Xem chi tiết loại hỗ trợ.
     */
    public function show(
        Request $request,
        SupportType $supportType
    ): JsonResponse {
        $supportType->load('department:id,name,code,is_active');

        return ApiResponse::success(
            (new SupportTypeResource($supportType))->resolve($request)
        );
    }

    /**
     * Cập nhật loại hỗ trợ: tên, mã, phòng phụ trách, mô tả, SLA, bật / tắt.
     */
    public function update(
        UpdateSupportTypeRequest $request,
        SupportType $supportType
    ): JsonResponse {
        $data = $request->validated();

        $supportType->update($data);

        $supportType = $supportType->fresh();
        $supportType->load('department:id,name,code,is_active');

        return ApiResponse::success(
            (new SupportTypeResource($supportType))->resolve($request),
            'Cập nhật loại hỗ trợ thành công.'
        );
    }

    /**
     * Xóa loại hỗ trợ. Không cho xóa nếu đã có yêu cầu hỗ trợ sử dụng,
     * để tránh mất liên kết lịch sử — sai nghiệp vụ nên trả 409.
     */
    public function destroy(SupportType $supportType): JsonResponse
    {
        if (RequestUsage::usesSupportType($supportType->id)) {
            return ApiResponse::error(
                'Không thể xóa loại hỗ trợ vì đã có yêu cầu sử dụng loại hỗ trợ này.',
                409
            );
        }

        $supportType->delete();

        return ApiResponse::success(null, 'Xóa loại hỗ trợ thành công.');
    }
}
