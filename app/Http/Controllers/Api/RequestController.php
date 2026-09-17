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
use App\Services\RequestWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Controller CHỈ điều phối: nhận request, gọi Service, trả response.
 * Toàn bộ business logic nằm ở RequestWorkflowService (Mục 4 Coding Convention).
 */
class RequestController extends Controller
{
    public function __construct(
        protected RequestWorkflowService $workflow,
        protected AuthContext $auth,
    ) {
    }

    /**
     * GET /api/requests — student chỉ thấy yêu cầu của mình,
     * staff/department_head/admin thấy theo phòng ban.
     */
    public function index(Request $request)
    {
        // Khẩn cấp lên đầu: urgent > high > normal > low
        $query = SupportRequest::query()
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low')")
            ->latest();

        // Phân quyền xem danh sách
        $role = $this->auth->role();
        if ($role === 'student') {
            $query->where('student_id', $this->auth->userId());
        } elseif ($role === 'staff') {
            $query->where('assigned_to', $this->auth->userId());
        } elseif ($role === 'department_head') {
            $query->where('department_id', $this->auth->departmentId());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        return ApiResponse::success(SupportRequestResource::collection($query->paginate(15)));
    }

    /** GET /api/requests/{supportRequest} */
    public function show(SupportRequest $supportRequest)
    {
        return ApiResponse::success(new SupportRequestResource($supportRequest));
    }

    /** POST /api/requests — chỉ role student được tạo yêu cầu. */
    public function store(StoreRequestRequest $request)
    {
        if ($this->auth->role() !== 'student') {
            return ApiResponse::error('Chỉ sinh viên được tạo yêu cầu hỗ trợ.', 403);
        }

        $created = $this->workflow->create($request->validated(), $this->auth->userId());

        return ApiResponse::success(new SupportRequestResource($created), status: 201);
    }

    /** PUT /api/requests/{supportRequest}/status */
    public function updateStatus(UpdateStatusRequest $request, SupportRequest $supportRequest)
    {
        if (! in_array($this->auth->role(), ['staff', 'department_head', 'admin'], true)) {
            return ApiResponse::error('Bạn không có quyền đổi trạng thái yêu cầu.', 403);
        }

        $toStatus = $request->validated('status');

        // Chỉ trưởng phòng / admin được đóng yêu cầu (closed)
        if ($toStatus === 'closed' && $this->auth->role() === 'staff') {
            return ApiResponse::error('Chỉ trưởng phòng hoặc admin được đóng yêu cầu.', 403);
        }

        try {
            $updated = $this->workflow->changeStatus(
                $supportRequest,
                $toStatus,
                $this->auth->userId(),
                $request->validated('note'),
            );
        } catch (ValidationException $e) {
            return ApiResponse::error($e->getMessage(), 409);
        }

        return ApiResponse::success(new SupportRequestResource($updated));
    }

    /** PUT /api/requests/{supportRequest}/assign */
    public function assign(AssignStaffRequest $request, SupportRequest $supportRequest)
    {
        if (! in_array($this->auth->role(), ['department_head', 'admin'], true)) {
            return ApiResponse::error('Chỉ trưởng phòng/admin được gán cán bộ xử lý.', 403);
        }

        try {
            $updated = $this->workflow->assign(
                $supportRequest,
                (int) $request->validated('assigned_to'),
                $this->auth->userId(),
            );
        } catch (ValidationException $e) {
            return ApiResponse::error($e->getMessage(), 409);
        }

        return ApiResponse::success(new SupportRequestResource($updated));
    }

    /** PUT /api/requests/{supportRequest}/cancel */
    public function cancel(Request $request, SupportRequest $supportRequest)
    {
        $isOwner = $this->auth->role() === 'student' && $supportRequest->student_id === $this->auth->userId();

        if (! $isOwner && $this->auth->role() !== 'admin') {
            return ApiResponse::error('Bạn không có quyền hủy yêu cầu này.', 403);
        }

        try {
            $updated = $this->workflow->cancel(
                $supportRequest,
                $this->auth->userId(),
                $request->input('reason'),
            );
        } catch (ValidationException $e) {
            return ApiResponse::error($e->getMessage(), 409);
        }

        return ApiResponse::success(new SupportRequestResource($updated));
    }

    /** GET /api/requests/{supportRequest}/history — phục vụ Module 5 (Report). */
    public function history(SupportRequest $supportRequest)
    {
        return ApiResponse::success($supportRequest->statusHistories()->latest('id')->get());
    }
}
