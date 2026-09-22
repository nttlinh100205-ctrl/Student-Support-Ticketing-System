<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SupportDepartmentResource;
use App\Models\SupportDepartment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupportDepartmentController extends Controller
{
    /**
     * Danh sách phòng ban.
     *
     * Hỗ trợ:
     * - tìm kiếm theo tên / mã
     * - lọc trạng thái
     * - phân trang
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        $query = SupportDepartment::query()
            ->withCount([
                'staff',
                'heads',
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
        | DÙNG API RESOURCE
        |--------------------------------------------------------------------------
        |
        | through() giúp giữ nguyên cấu trúc:
        |
        | current_page
        | data
        | last_page
        | total
        |
        | nên frontend hiện tại không bị hỏng.
        |
        */
        $paginator->through(
            function ($department) use ($request) {
                return (
                    new SupportDepartmentResource(
                        $department
                    )
                )->resolve($request);
            }
        );

        return response()->json(
            $paginator
        );
    }

    /**
     * Thêm phòng ban.
     */
    public function store(
        Request $request
    ): JsonResponse {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9_-]+$/',

                Rule::unique(
                    'support_departments',
                    'code'
                ),
            ],

            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ]);

        /*
         * Nếu client không truyền trạng thái
         * thì mặc định phòng ban hoạt động.
         */
        $data['is_active'] =
            $data['is_active'] ?? true;

        $department =
            SupportDepartment::create(
                $data
            );

        /*
         * Load count để Resource trả về
         * staff_count và heads_count ngay sau khi thêm.
         */
        $department->loadCount([
            'staff',
            'heads',
        ]);

        return response()->json([
            'message' => 'Thêm phòng ban thành công.',

            'data' => (
                    new SupportDepartmentResource(
                        $department
                    )
                )->resolve($request),

        ], 201);
    }

    /**
     * Xem chi tiết phòng ban.
     */
    public function show(
        Request $request,
        SupportDepartment $department
    ): JsonResponse {
        $department->loadCount([
            'staff',
            'heads',
        ]);

        return response()->json([
            'data' => (
                    new SupportDepartmentResource(
                        $department
                    )
                )->resolve($request),
        ]);
    }

    /**
     * Cập nhật phòng ban.
     *
     * Đồng thời dùng để:
     * - sửa tên
     * - sửa mã
     * - sửa mô tả
     * - bật / tắt hoạt động
     */
    public function update(
        Request $request,
        SupportDepartment $department
    ): JsonResponse {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9_-]+$/',

                Rule::unique(
                    'support_departments',
                    'code'
                )->ignore(
                    $department->id
                ),
            ],

            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ]);

        $department->update(
            $data
        );

        $department = $department
            ->fresh();

        $department->loadCount([
            'staff',
            'heads',
        ]);

        return response()->json([
            'message' => 'Cập nhật phòng ban thành công.',

            'data' => (
                    new SupportDepartmentResource(
                        $department
                    )
                )->resolve($request),
        ]);
    }

    /**
     * Danh sách cán bộ và trưởng phòng
     * thuộc một phòng ban.
     *
     * Hỗ trợ:
     * - tìm kiếm tên / email
     * - lọc role
     * - lọc trạng thái
     * - phân trang
     */
    public function staff(
        Request $request,
        SupportDepartment $department
    ): JsonResponse {
        $filters = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

            'role' => [
                'nullable',
                Rule::in([
                    'STAFF',
                    'DEPARTMENT_HEAD',
                ]),
            ],

            'status' => [
                'nullable',
                Rule::in([
                    'ACTIVE',
                    'INACTIVE',
                ]),
            ],
        ]);

        $query = $department
            ->users()
            ->select([
                'id',
                'name',
                'email',
                'role',
                'status',
                'department_id',
            ])
            ->whereIn(
                'role',
                [
                    'STAFF',
                    'DEPARTMENT_HEAD',
                ]
            );

        /*
        |--------------------------------------------------------------------------
        | TÌM KIẾM CÁN BỘ
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
                            'email',
                            'like',
                            "%{$search}%"
                        );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | LỌC ROLE
        |--------------------------------------------------------------------------
        */
        if (
            ! empty(
                $filters['role']
            )
        ) {
            $query->where(
                'role',
                $filters['role']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | LỌC TRẠNG THÁI
        |--------------------------------------------------------------------------
        */
        if (
            ! empty(
                $filters['status']
            )
        ) {
            $query->where(
                'status',
                $filters['status']
            );
        }

        return response()->json(
            $query
                ->orderBy('name')
                ->paginate(10)
                ->withQueryString()
        );
    }

    /**
     * Xóa phòng ban.
     *
     * Không cho phép xóa nếu phòng ban
     * vẫn còn dữ liệu liên kết.
     *
     * Trong thực tế nên ưu tiên is_active = false
     * thay vì xóa dữ liệu.
     */
    public function destroy(
        SupportDepartment $department
    ): JsonResponse {
        /*
         * Không cho xóa nếu còn cán bộ /
         * trưởng phòng thuộc phòng ban.
         */
        if (
            $department
                ->users()
                ->exists()
        ) {
            return response()->json([
                'message' => 'Không thể xóa phòng ban vì đang có cán bộ thuộc phòng ban này.',
            ], 422);
        }

        /*
         * Không cho xóa nếu còn loại hỗ trợ.
         */
        if (
            $department
                ->supportTypes()
                ->exists()
        ) {
            return response()->json([
                'message' => 'Không thể xóa phòng ban vì đang có loại hỗ trợ liên kết.',
            ], 422);
        }

        $department->delete();

        return response()->json([
            'message' => 'Xóa phòng ban thành công.',
        ]);
    }
}
