<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SupportTypeResource;
use App\Models\SupportType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupportTypeController extends Controller
{
    /**
     * Danh sách loại hỗ trợ.
     *
     * Hỗ trợ:
     * - tìm kiếm theo tên / mã
     * - lọc theo phòng ban
     * - lọc trạng thái
     * - phân trang
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:150',
            ],

            'department_id' => [
                'nullable',
                'integer',
                'exists:support_departments,id',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | QUERY
        |--------------------------------------------------------------------------
        |
        | Load phòng ban để SupportTypeResource có thể trả:
        |
        | department:
        |   id
        |   name
        |   code
        |   is_active
        |
        */
        $query = SupportType::query()
            ->with([
                'department:id,name,code,is_active',
            ]);

        /*
        |--------------------------------------------------------------------------
        | TÌM KIẾM
        |--------------------------------------------------------------------------
        */
        $search = trim(
            $filters['search'] ?? ''
        );

        if ($search !== '') {
            $query->where(
                function ($q) use ($search) {
                    $q->where(
                        'name',
                        'like',
                        "%{$search}%"
                    )
                        ->orWhere(
                            'code',
                            'like',
                            "%{$search}%"
                        );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | LỌC PHÒNG BAN
        |--------------------------------------------------------------------------
        */
        if (
            array_key_exists(
                'department_id',
                $filters
            )
        ) {
            $query->where(
                'department_id',
                $filters['department_id']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | LỌC TRẠNG THÁI
        |--------------------------------------------------------------------------
        */
        if (
            array_key_exists(
                'is_active',
                $filters
            )
        ) {
            $query->where(
                'is_active',
                $filters['is_active']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | PHÂN TRANG
        |--------------------------------------------------------------------------
        */
        $paginator = $query
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | API RESOURCE
        |--------------------------------------------------------------------------
        |
        | Dùng through() để vẫn giữ nguyên cấu trúc JSON:
        |
        | current_page
        | data
        | last_page
        | per_page
        | total
        |
        | nên support-types.blade.php không phải sửa lại.
        |
        */
        $paginator->through(
            function ($supportType) use ($request) {
                return (
                    new SupportTypeResource(
                        $supportType
                    )
                )->resolve($request);
            }
        );

        return response()->json(
            $paginator
        );
    }

    /**
     * Thêm loại hỗ trợ.
     */
    public function store(
        Request $request
    ): JsonResponse {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9_-]+$/',

                Rule::unique(
                    'support_types',
                    'code'
                ),
            ],

            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'department_id' => [
                'required',
                'integer',
                'exists:support_departments,id',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ]);

        /*
         * Nếu không truyền trạng thái
         * thì mặc định loại hỗ trợ hoạt động.
         */
        $data['is_active'] =
            $data['is_active'] ?? true;

        $supportType =
            SupportType::create(
                $data
            );

        /*
         * Load phòng ban để Resource trả
         * đầy đủ thông tin relation.
         */
        $supportType->load(
            'department:id,name,code,is_active'
        );

        return response()->json([
            'message' => 'Thêm loại hỗ trợ thành công.',

            'data' => (
                    new SupportTypeResource(
                        $supportType
                    )
                )->resolve($request),

        ], 201);
    }

    /**
     * Xem chi tiết loại hỗ trợ.
     */
    public function show(
        Request $request,
        SupportType $supportType
    ): JsonResponse {
        $supportType->load(
            'department:id,name,code,is_active'
        );

        return response()->json([
            'data' => (
                    new SupportTypeResource(
                        $supportType
                    )
                )->resolve($request),
        ]);
    }

    /**
     * Cập nhật loại hỗ trợ.
     *
     * Đồng thời dùng để:
     * - sửa tên
     * - sửa mã
     * - đổi phòng phụ trách
     * - sửa mô tả
     * - bật / tắt hoạt động
     */
    public function update(
        Request $request,
        SupportType $supportType
    ): JsonResponse {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9_-]+$/',

                Rule::unique(
                    'support_types',
                    'code'
                )->ignore(
                    $supportType->id
                ),
            ],

            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'department_id' => [
                'required',
                'integer',
                'exists:support_departments,id',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ]);

        $supportType->update(
            $data
        );

        $supportType = $supportType
            ->fresh();

        $supportType->load(
            'department:id,name,code,is_active'
        );

        return response()->json([
            'message' => 'Cập nhật loại hỗ trợ thành công.',

            'data' => (
                    new SupportTypeResource(
                        $supportType
                    )
                )->resolve($request),
        ]);
    }
}
