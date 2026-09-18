<?php

namespace App\Http\Controllers\Api;

use App\Contracts\AuthContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignStaffRequest;
use App\Http\Requests\StoreRequestRequest;
use App\Http\Requests\UpdateStatusRequest;
use App\Http\Resources\SupportRequestResource;
use App\Http\Responses\ApiResponse;
use App\Models\SupportRequest;
use App\Models\User;
use App\Services\RequestWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Controller xử lý request hỗ trợ sinh viên.
 *
 * Authentication:
 * - Sử dụng Sanctum token.
 *
 * Authorization:
 * - STUDENT: chỉ request của mình.
 * - STAFF: chỉ request được giao cho mình.
 * - DEPARTMENT_HEAD: request thuộc phòng mình.
 * - ADMIN: toàn bộ request.
 */
class RequestController extends Controller
{
    public function __construct(
        protected RequestWorkflowService $workflow,
        protected AuthContext $auth,
    ) {
    }

    /**
     * GET /api/requests
     */
    public function index(Request $request)
    {
        $query = SupportRequest::query()
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low')")
            ->latest();

        $role = $this->auth->role();

        if ($role === 'student') {
            $query->where(
                'student_id',
                $this->auth->userId()
            );
        } elseif ($role === 'staff') {
            $query->where(
                'assigned_to',
                $this->auth->userId()
            );
        } elseif ($role === 'department_head') {
            $query->where(
                'department_id',
                $this->auth->departmentId()
            );
        }

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->query('status')
            );
        }

        return ApiResponse::success(
            SupportRequestResource::collection(
                $query->paginate(15)
            )
        );
    }

    /**
     * GET /api/requests/{supportRequest}
     *
     * Kiểm tra quyền trước khi xem chi tiết.
     */
    public function show(SupportRequest $supportRequest)
    {
        if (! $this->canViewRequest($supportRequest)) {
            return ApiResponse::error(
                'Bạn không có quyền xem yêu cầu này.',
                403
            );
        }

        return ApiResponse::success(
            new SupportRequestResource($supportRequest)
        );
    }

    /**
     * POST /api/requests
     *
     * Chỉ STUDENT được tạo request.
     */
    public function store(StoreRequestRequest $request)
    {
        if ($this->auth->role() !== 'student') {
            return ApiResponse::error(
                'Chỉ sinh viên được tạo yêu cầu hỗ trợ.',
                403
            );
        }

        $created = $this->workflow->create(
            $request->validated(),
            $this->auth->userId()
        );

        return ApiResponse::success(
            new SupportRequestResource($created),
            status: 201
        );
    }

    /**
     * PUT /api/requests/{supportRequest}/status
     *
     * STUDENT: không được đổi status.
     *
     * STAFF:
     * - chỉ request được giao cho mình.
     *
     * DEPARTMENT_HEAD:
     * - chỉ request thuộc phòng mình.
     *
     * ADMIN:
     * - tất cả.
     */
    public function updateStatus(
        UpdateStatusRequest $request,
        SupportRequest $supportRequest
    ) {
        $role = $this->auth->role();

        if (! in_array(
            $role,
            ['staff', 'department_head', 'admin'],
            true
        )) {
            return ApiResponse::error(
                'Bạn không có quyền đổi trạng thái yêu cầu.',
                403
            );
        }

        if (! $this->canProcessRequest($supportRequest)) {
            return ApiResponse::error(
                'Bạn không có quyền xử lý yêu cầu này.',
                403
            );
        }

        $toStatus = $request->validated('status');

        /**
         * STAFF không được đóng request.
         */
        if (
            $toStatus === 'closed' &&
            $role === 'staff'
        ) {
            return ApiResponse::error(
                'Chỉ trưởng phòng hoặc admin được đóng yêu cầu.',
                403
            );
        }

        try {
            $updated = $this->workflow->changeStatus(
                $supportRequest,
                $toStatus,
                $this->auth->userId(),
                $request->validated('note'),
            );
        } catch (ValidationException $e) {
            return ApiResponse::error(
                $e->getMessage(),
                409
            );
        }

        return ApiResponse::success(
            new SupportRequestResource($updated)
        );
    }

    /**
     * PUT /api/requests/{supportRequest}/assign
     *
     * DEPARTMENT_HEAD:
     * - chỉ gán trong phòng mình.
     * - chỉ được gán STAFF cùng phòng.
     *
     * ADMIN:
     * - được gán tất cả.
     */
    public function assign(
        AssignStaffRequest $request,
        SupportRequest $supportRequest
    ) {
        $role = $this->auth->role();

        if (! in_array(
            $role,
            ['department_head', 'admin'],
            true
        )) {
            return ApiResponse::error(
                'Chỉ trưởng phòng hoặc admin được gán cán bộ xử lý.',
                403
            );
        }

        /**
         * DEPARTMENT_HEAD chỉ được assign
         * request trong department của mình.
         */
        if (
            $role === 'department_head' &&
            (int) $supportRequest->department_id !==
            (int) $this->auth->departmentId()
        ) {
            return ApiResponse::error(
                'Bạn không có quyền phân công yêu cầu ngoài phòng ban của mình.',
                403
            );
        }

        $staffId = (int) $request->validated('assigned_to');

        $staff = User::query()
            ->select([
                'id',
                'name',
                'email',
                'role',
                'department_id',
                'status',
            ])
            ->find($staffId);

        if (! $staff) {
            return ApiResponse::error(
                'Không tìm thấy cán bộ xử lý.',
                422
            );
        }

        /**
         * Chỉ STAFF mới được nhận request.
         */
        if ($staff->role !== 'STAFF') {
            return ApiResponse::error(
                'Chỉ tài khoản STAFF được gán xử lý yêu cầu.',
                422
            );
        }

        /**
         * STAFF phải đang ACTIVE.
         */
        if ($staff->status !== 'ACTIVE') {
            return ApiResponse::error(
                'Tài khoản STAFF đang bị khóa.',
                422
            );
        }

        /**
         * DEPARTMENT_HEAD chỉ được gán STAFF
         * thuộc cùng department.
         */
        if (
            $role === 'department_head' &&
            (int) $staff->department_id !==
            (int) $this->auth->departmentId()
        ) {
            return ApiResponse::error(
                'Chỉ được gán STAFF thuộc phòng ban của mình.',
                403
            );
        }

        /**
         * STAFF cũng phải cùng phòng với request.
         *
         * Áp dụng cho cả ADMIN để tránh phân công
         * request vào STAFF thuộc phòng khác ngoài nghiệp vụ.
         */
        if (
            (int) $staff->department_id !==
            (int) $supportRequest->department_id
        ) {
            return ApiResponse::error(
                'STAFF phải thuộc cùng phòng ban với yêu cầu.',
                422
            );
        }

        try {
            $updated = $this->workflow->assign(
                $supportRequest,
                $staffId,
                $this->auth->userId(),
            );
        } catch (ValidationException $e) {
            return ApiResponse::error(
                $e->getMessage(),
                409
            );
        }

        return ApiResponse::success(
            new SupportRequestResource($updated)
        );
    }

    /**
     * PUT /api/requests/{supportRequest}/cancel
     *
     * STUDENT:
     * - chỉ hủy request của mình.
     *
     * ADMIN:
     * - được hủy request.
     */
    public function cancel(
        Request $request,
        SupportRequest $supportRequest
    ) {
        $role = $this->auth->role();

        $isOwner =
            $role === 'student' &&
            (int) $supportRequest->student_id ===
            (int) $this->auth->userId();

        if (! $isOwner && $role !== 'admin') {
            return ApiResponse::error(
                'Bạn không có quyền hủy yêu cầu này.',
                403
            );
        }

        try {
            $updated = $this->workflow->cancel(
                $supportRequest,
                $this->auth->userId(),
                $request->input('reason'),
            );
        } catch (ValidationException $e) {
            return ApiResponse::error(
                $e->getMessage(),
                409
            );
        }

        return ApiResponse::success(
            new SupportRequestResource($updated)
        );
    }

    /**
     * GET /api/requests/{supportRequest}/history
     *
     * Chỉ người có quyền xem request
     * mới được xem history.
     */
    public function history(
        SupportRequest $supportRequest
    ) {
        if (! $this->canViewRequest($supportRequest)) {
            return ApiResponse::error(
                'Bạn không có quyền xem lịch sử yêu cầu này.',
                403
            );
        }

        return ApiResponse::success(
            $supportRequest
                ->statusHistories()
                ->latest('id')
                ->get()
        );
    }

    /**
     * Kiểm tra quyền xem một request.
     */
    protected function canViewRequest(
        SupportRequest $supportRequest
    ): bool {
        $role = $this->auth->role();

        if ($role === 'admin') {
            return true;
        }

        if ($role === 'student') {
            return (int) $supportRequest->student_id ===
                (int) $this->auth->userId();
        }

        if ($role === 'staff') {
            return (int) $supportRequest->assigned_to ===
                (int) $this->auth->userId();
        }

        if ($role === 'department_head') {
            return (int) $supportRequest->department_id ===
                (int) $this->auth->departmentId();
        }

        return false;
    }

    /**
     * Kiểm tra quyền xử lý request.
     *
     * ADMIN:
     * - tất cả.
     *
     * DEPARTMENT_HEAD:
     * - request cùng department.
     *
     * STAFF:
     * - request được giao cho mình.
     */
    protected function canProcessRequest(
        SupportRequest $supportRequest
    ): bool {
        $role = $this->auth->role();

        if ($role === 'admin') {
            return true;
        }

        if ($role === 'staff') {
            return (int) $supportRequest->assigned_to ===
                (int) $this->auth->userId();
        }

        if ($role === 'department_head') {
            return (int) $supportRequest->department_id ===
                (int) $this->auth->departmentId();
        }

        return false;
    }
}