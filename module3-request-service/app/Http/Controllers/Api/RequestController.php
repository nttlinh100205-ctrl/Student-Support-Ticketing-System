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
    ) {}

    public function index(Request $request)
    {

        $query = SupportRequest::query()
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 WHEN 'low' THEN 4 ELSE 5 END")
            ->latest();

        $role = $this->auth->role();
        if ($role === 'student') {
            $query->where('student_id', $this->auth->userId());
        } elseif ($role === 'staff') {
            $query->where('assigned_to', $this->auth->userId());
        } elseif ($role === 'department_head') {
            $query->where('department_id', $this->auth->departmentId());
        }

        // Scope dashboard totals to the verified user before applying list filters.
        $statusCounts = (clone $query)->reorder()
            ->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')
            ->pluck('aggregate', 'status')->map(fn ($count) => (int) $count)->all();

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

        $paginator = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data' => SupportRequestResource::collection($paginator->items())->resolve($request),
            'message' => null,
            'summary' => ['total' => array_sum($statusCounts), 'statuses' => $statusCounts],
            'meta' => ['current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(), 'total' => $paginator->total()],
        ]);
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
        if (! $request->boolean('confirm_duplicate')) {
            $duplicates = $this->workflow->findPotentialDuplicates($data, $this->auth->userId());
            if ($duplicates->isNotEmpty()) {
                return ApiResponse::error(
                    'Yêu cầu có thể trùng với: '.$duplicates->pluck('code')->implode(', ').'. Gửi lại với confirm_duplicate=true nếu vẫn muốn tạo.',
                    409,
                );
            }
        }

        $files = $request->file('attachments', []) ?: [];
        if (! is_array($files)) {
            $files = [$files];
        }

        $created = $this->workflow->create($data, $this->auth->userId(), $files);
        request()->attributes->set('operation_completed', true);

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
        if (! $this->canView($supportRequest)) {
            return ApiResponse::error('Bạn không có quyền đổi trạng thái yêu cầu này.', 403);
        }

        $toStatus = $request->validated('status');
        $isOwner = $role === 'student' && $supportRequest->student_id === $this->auth->userId();

        if ($role === 'student') {
            if (! $isOwner) {
                return ApiResponse::error('Bạn không có quyền đổi trạng thái yêu cầu này.', 403);
            }
            if (! in_array($supportRequest->status->value, ['resolved', 'closed'], true)
                || $toStatus !== 'in_progress') {
                return ApiResponse::error(
                    'Sinh viên chỉ được yêu cầu xử lý lại khi yêu cầu đã xử lý xong hoặc đã đóng.',
                    403
                );
            }
        } elseif (! in_array($role, ['staff', 'admin'], true)) {
            return ApiResponse::error('Bạn không có quyền đổi trạng thái yêu cầu.', 403);
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
        if (! $this->canView($supportRequest)) {
            return ApiResponse::error('Bạn không có quyền gán cán bộ cho yêu cầu này.', 403);
        }

        try {
            $updated = $this->workflow->assign(
                $supportRequest,
                (int) $request->validated('assigned_to'),
                $this->auth->userId(),
                $request->safe()->only(['sla_deadline_at', 'priority', 'note']),
            );
        } catch (ValidationException $e) {
            return ApiResponse::error($e->getMessage(), 409);
        }

        return ApiResponse::success(new SupportRequestResource($updated));
    }

    public function transfer(Request $request, SupportRequest $supportRequest)
    {
        $role = $this->auth->role();
        if ($role !== 'admin') {
            return ApiResponse::error('Chỉ admin được chuyển yêu cầu sang phòng ban khác.', 403);
        }

        $data = $request->validate([
            'department_id' => 'required|integer',
            'support_type_id' => 'required|integer',
        ]);

        try {
            $updated = $this->workflow->transfer(
                $supportRequest,
                (int) $data['department_id'],
                (int) $data['support_type_id'],
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
