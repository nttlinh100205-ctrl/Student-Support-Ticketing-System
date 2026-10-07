<?php

namespace App\Http\Controllers\Api;

use App\Contracts\AuthContext;
use App\Http\Controllers\Controller;
use App\Http\Resources\SupportRequestResource;
use App\Http\Responses\ApiResponse;
use App\Models\SupportRequest;
use App\Services\SlaService;
use Illuminate\Http\Request;

/**
 * API Controller cho SLA monitoring.
 * Cung cấp endpoint để frontend hiển thị chuông thông báo SLA
 * và dashboard ticket vi phạm/sắp quá hạn.
 */
class SlaController extends Controller
{
    public function __construct(
        protected SlaService $slaService,
        protected AuthContext $auth,
    ) {}

    /**
     * GET /api/sla/notifications
     *
     * Lấy danh sách thông báo SLA của user hiện tại.
     * Staff/department_head thấy thông báo ticket được gán cho mình.
     * Admin thấy tất cả.
     */
    public function notifications(Request $request)
    {
        $role = $this->auth->role();
        $userId = $this->auth->userId();
        $limit = min((int) $request->query('limit', 20), 100);

        if ($role === 'admin') {
            $notifications = \App\Models\SlaNotification::orderByDesc('sent_at')
                ->limit($limit)
                ->get();
        } else {
            $notifications = $this->slaService->getNotificationsForUser($userId, $limit);
        }

        return ApiResponse::success($notifications);
    }

    /**
     * GET /api/sla/tickets
     *
     * Lấy danh sách ticket đang vi phạm hoặc sắp quá hạn SLA.
     * Hỗ trợ filter: ?flag=warning|breached
     */
    public function tickets(Request $request)
    {
        $role = $this->auth->role();

        if (! in_array($role, ['staff', 'department_head', 'admin'], true)) {
            return ApiResponse::error('Chỉ cán bộ/trưởng phòng/admin được xem danh sách SLA.', 403);
        }

        $query = SupportRequest::whereIn('sla_flag', ['warning', 'breached'])
            ->whereNotIn('status', config('sla.excluded_statuses', ['resolved', 'closed', 'cancelled']));

        // Filter theo role
        if ($role === 'staff') {
            $query->where('assigned_to', $this->auth->userId());
        } elseif ($role === 'department_head') {
            $query->where('department_id', $this->auth->departmentId());
        }

        // Filter theo flag cụ thể
        if ($request->filled('flag') && in_array($request->query('flag'), ['warning', 'breached'], true)) {
            $query->where('sla_flag', $request->query('flag'));
        }

        $tickets = $query->orderByRaw("FIELD(sla_flag, 'breached', 'warning')")
            ->orderBy('sla_deadline_at', 'asc')
            ->paginate(15);

        return ApiResponse::success(SupportRequestResource::collection($tickets));
    }
}
