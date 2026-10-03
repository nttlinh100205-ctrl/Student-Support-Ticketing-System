<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SupportTypeResource;
use App\Http\Responses\ApiResponse;
use App\Models\SupportType;
use App\Services\RequestUsage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('support_types', 'code'),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'department_id' => ['required', 'integer', 'exists:support_departments,id'],
            // Số ngày xử lý dự kiến; null = chưa quy định.
            'sla_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'is_active' => ['sometimes', 'boolean'],
        ], $this->slaMessages());

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
        Request $request,
        SupportType $supportType
    ): JsonResponse {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('support_types', 'code')->ignore($supportType->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'department_id' => ['required', 'integer', 'exists:support_departments,id'],
            'sla_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'is_active' => ['sometimes', 'boolean'],
        ], $this->slaMessages());

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

    private function slaMessages(): array
    {
        return [
            'sla_days.integer' => 'Số ngày xử lý phải là số nguyên.',
            'sla_days.min' => 'Số ngày xử lý phải từ 1 đến 365.',
            'sla_days.max' => 'Số ngày xử lý phải từ 1 đến 365.',
        ];
    }
}
