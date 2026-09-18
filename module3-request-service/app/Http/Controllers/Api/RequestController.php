<?php

namespace App\Http\Controllers\Api;

use App\Contracts\AuthContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignStaffRequest;
use App\Http\Requests\StoreRequestRequest;
use App\Http\Requests\UpdateRequestRequest;
use App\Http\Requests\UpdateStatusRequest;
use App\Http\Resources\SupportRequestResource;
use App\Http\Responses\ApiResponse;
use App\Models\SupportRequest;
use App\Services\RequestWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;


class RequestController extends Controller
{
    public function __construct(
        protected RequestWorkflowService $workflow,
        protected AuthContext $auth,
    ) {
    }

  
    public function index(Request $request)
    {
        
        $query = SupportRequest::query()
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low')")
            ->latest();

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

        if ($request->filled('priority')) {
            $query->where('priority', $request->query('priority'));
        }

        if ($request->filled('department_id') && in_array($role, ['admin', 'department_head'], true)) {
            $query->where('department_id', (int) $request->query('department_id'));
        }

        if ($request->filled('assigned_to') && in_array($role, ['admin', 'department_head'], true)) {
            $query->where('assigned_to', (int) $request->query('assigned_to'));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->query('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->query('to'));
        }

        if ($request->filled('q')) {
            $q = $request->query('q');
            $query->where(function ($builder) use ($q) {
                $builder->where('code', 'like', "%{$q}%")
                    ->orWhere('title', 'like', "%{$q}%")
                    ->orWhere('content', 'like', "%{$q}%");
            });
        }

        return ApiResponse::success(SupportRequestResource::collection($query->paginate(15)));
    }

    /** GET /api/requests/{supportRequest} */
    public function show(SupportRequest $supportRequest)
    {
        if (! $this->canView($supportRequest)) {
            return ApiResponse::error('Bạn không có quyền xem yêu cầu này.', 403);
        }

        $supportRequest->load('attachments');

        return ApiResponse::success(new SupportRequestResource($supportRequest));
    }

   
    public function store(StoreRequestRequest $request)
    {
        if ($this->auth->role() !== 'student') {
            return ApiResponse::error('Chỉ sinh viên được tạo yêu cầu hỗ trợ.', 403);
        }

        $data = $request->safe()->except(['attachments']);
        $files = $request->file('attachments', []) ?: [];
        if (! is_array($files)) {
            $files = [$files];
        }

        $created = $this->workflow->create($data, $this->auth->userId(), $files);

        return ApiResponse::success(new SupportRequestResource($created), status: 201);
    }

    
    public function update(UpdateRequestRequest $request, SupportRequest $supportRequest)
    {
        $isOwner = $this->auth->role() === 'student' && $supportRequest->student_id === $this->auth->userId();

        if (! $isOwner && $this->auth->role() !== 'admin') {
            return ApiResponse::error('Bạn không có quyền sửa yêu cầu này.', 403);
        }

        try {
            $updated = $this->workflow->update(
                $supportRequest,
                $request->validated(),
                $this->auth->userId(),
            );
        } catch (ValidationException $e) {
            return ApiResponse::error($e->getMessage(), 409);
        }

        return ApiResponse::success(new SupportRequestResource($updated));
    }

  
    public function updateStatus(UpdateStatusRequest $request, SupportRequest $supportRequest)
    {
        $role = $this->auth->role();
        $toStatus = $request->validated('status');
        $isOwner = $role === 'student' && $supportRequest->student_id === $this->auth->userId();

        if ($role === 'student') {
            if (! $isOwner) {
                return ApiResponse::error('Bạn không có quyền đổi trạng thái yêu cầu này.', 403);
            }
            // SV chỉ phản hồi khi đang chờ (resolved)
            if ($supportRequest->status->value !== 'resolved'
                || ! in_array($toStatus, ['closed', 'in_progress'], true)) {
                return ApiResponse::error(
                    'Sinh viên chỉ được xác nhận đóng hoặc yêu cầu xử lý lại khi yêu cầu đang chờ phản hồi.',
                    403
                );
            }
        } elseif (! in_array($role, ['staff', 'department_head', 'admin'], true)) {
            return ApiResponse::error('Bạn không có quyền đổi trạng thái yêu cầu.', 403);
        } else {
            // Staff không tự đóng — chờ SV phản hồi (head/admin vẫn được đóng)
            if ($toStatus === 'closed' && $role === 'staff') {
                return ApiResponse::error(
                    'Cán bộ không tự đóng yêu cầu. Hãy đánh dấu "Chờ phản hồi SV"; sinh viên hoặc trưởng phòng sẽ xác nhận đóng.',
                    403
                );
            }
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
                asStudent: $this->auth->role() === 'student',
            );
        } catch (ValidationException $e) {
            return ApiResponse::error($e->getMessage(), 409);
        }

        return ApiResponse::success(new SupportRequestResource($updated));
    }

    public function destroy(SupportRequest $supportRequest)
    {
        $isOwner = $this->auth->role() === 'student' && $supportRequest->student_id === $this->auth->userId();

        if (! $isOwner && $this->auth->role() !== 'admin') {
            return ApiResponse::error('Bạn không có quyền xóa yêu cầu này.', 403);
        }

        try {
            $this->workflow->delete($supportRequest);
        } catch (ValidationException $e) {
            return ApiResponse::error($e->getMessage(), 409);
        }

        return ApiResponse::success(null, 'Đã xóa yêu cầu thành công.');
    }

    public function history(SupportRequest $supportRequest)
    {
        if (! $this->canView($supportRequest)) {
            return ApiResponse::error('Bạn không có quyền xem lịch sử yêu cầu này.', 403);
        }

        return ApiResponse::success($supportRequest->statusHistories()->latest('id')->get());
    }

    /**
     * Quyền xem chi tiết / lịch sử — đồng bộ với filter danh sách index:
     * - student: chỉ yêu cầu của mình
     * - staff: chỉ yêu cầu được gán cho mình
     * - department_head: yêu cầu thuộc phòng ban mình
     * - admin: tất cả
     */
    protected function canView(SupportRequest $supportRequest): bool
    {
        return match ($this->auth->role()) {
            'student' => $supportRequest->student_id === $this->auth->userId(),
            'staff' => $supportRequest->assigned_to === $this->auth->userId(),
            'department_head' => $supportRequest->department_id === $this->auth->departmentId(),
            'admin' => true,
            default => false,
        };
    }
}
