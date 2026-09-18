<?php

namespace App\Http\Controllers\Web;

use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Models\SupportRequest;
use App\Services\RequestWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;


class RequestWebController extends Controller
{
    public function __construct(
        protected RequestWorkflowService $workflow,
    ) {
    }

    /** Lấy user giả từ session (mặc định student). */
    protected function currentUser(): array
    {
        return Session::get('fake_user', [
            'id' => 12,
            'role' => 'student',
            'department_id' => null,
            'full_name' => 'Trần Thị B',
            'email' => 'sv001@university.edu.vn',
        ]);
    }

    public function switchRole(Request $request)
    {
        $users = $this->demoUsers();
        $id = (int) $request->input('user_id');
        $user = collect($users)->firstWhere('id', $id);
        if ($user) {
            Session::put('fake_user', $user);
        }

        return back();
    }

    public function index(Request $request)
    {
        $user = $this->currentUser();
        // Khẩn cấp lên đầu: urgent > high > normal > low (MySQL FIELD)
        $query = SupportRequest::query()
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low')")
            ->latest();

      
        if ($user['role'] === 'student') {
            $query->where('student_id', $user['id']);
        } elseif ($user['role'] === 'staff') {
            $query->where('assigned_to', $user['id']);
        } elseif ($user['role'] === 'department_head') {
            $query->where('department_id', $user['department_id']);
        }
        // admin: không filter

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->query('priority'));
        }

        if ($request->filled('department_id') && in_array($user['role'], ['admin', 'department_head'], true)) {
            $query->where('department_id', (int) $request->query('department_id'));
        }

        if ($request->filled('assigned_to') && in_array($user['role'], ['admin', 'department_head'], true)) {
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

        $requests = $query->paginate(15)->withQueryString();

        return view('requests.index', [
            'requests' => $requests,
            'user' => $user,
            'statusFilter' => $request->query('status', ''),
            'priorityFilter' => $request->query('priority', ''),
            'departmentFilter' => $request->query('department_id', ''),
            'assignedToFilter' => $request->query('assigned_to', ''),
            'fromFilter' => $request->query('from', ''),
            'toFilter' => $request->query('to', ''),
            'search' => $request->query('q', ''),
            'departments' => $this->departments(),
            'demoUsers' => $this->demoUsers(),
        ]);
    }

    public function create()
    {
        $user = $this->currentUser();
        if ($user['role'] !== 'student') {
            return redirect()->route('requests.index')
                ->with('error', 'Chỉ sinh viên được tạo yêu cầu hỗ trợ.');
        }

        return view('requests.create', [
            'user' => $user,
            'departments' => $this->departments(),
            'supportTypes' => $this->supportTypes(),
            'demoUsers' => $this->demoUsers(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $this->currentUser();
        if ($user['role'] !== 'student') {
            return back()->with('error', 'Chỉ sinh viên được tạo yêu cầu hỗ trợ.');
        }

        $isFacilities = (int) $request->input('department_id') === 6;

        $data = $request->validate([
            'department_id' => 'required|integer',
            'support_type_id' => 'required|integer',
            'title' => 'required|string|min:10|max:255',
            'content' => 'required|string|min:20',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'attachments' => ($isFacilities ? 'required' : 'nullable').'|array|max:5',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,webp,gif|max:5120',
        ], [
            'title.required' => 'Vui lòng nhập tiêu đề yêu cầu.',
            'title.min' => 'Tiêu đề phải có ít nhất 10 ký tự.',
            'content.required' => 'Vui lòng nhập nội dung yêu cầu.',
            'content.min' => 'Nội dung phải có ít nhất 20 ký tự.',
            'department_id.required' => 'Vui lòng chọn phòng ban.',
            'support_type_id.required' => 'Vui lòng chọn loại hỗ trợ.',
            'attachments.required' => 'Phản ánh Cơ sở vật chất cần đính kèm ít nhất 1 ảnh.',
            'attachments.max' => 'Tối đa 5 ảnh đính kèm.',
            'attachments.*.mimes' => 'Chỉ chấp nhận ảnh: jpg, jpeg, png, webp, gif.',
            'attachments.*.max' => 'Mỗi ảnh tối đa 5MB.',
        ]);

        if (! $this->supportTypeBelongsToDepartment((int) $data['support_type_id'], (int) $data['department_id'])) {
            return back()->withInput()->with('error', 'Loại hỗ trợ không thuộc phòng ban đã chọn.');
        }

        $files = $request->file('attachments', []) ?: [];
        if (! is_array($files)) {
            $files = [$files];
        }

        unset($data['attachments']);
        $created = $this->workflow->create($data, $user['id'], $files);

        return redirect()->route('requests.show', $created)
            ->with('success', 'Đã tạo yêu cầu thành công: '.$created->code);
    }

    public function show(SupportRequest $supportRequest)
    {
        $user = $this->currentUser();

        if (! $this->canView($supportRequest, $user)) {
            return redirect()->route('requests.index')
                ->with('error', 'Bạn không có quyền xem yêu cầu này.');
        }

        $histories = $supportRequest->statusHistories()->latest('id')->get();
        $supportRequest->load('attachments');

        return view('requests.show', [
            'request' => $supportRequest,
            'histories' => $histories,
            'user' => $user,
            'departments' => $this->departments(),
            'supportTypes' => $this->supportTypes(),
            'transitions' => RequestWorkflowService::TRANSITIONS,
            'demoUsers' => $this->demoUsers(),
        ]);
    }

    public function edit(SupportRequest $supportRequest)
    {
        $user = $this->currentUser();
        $isOwner = $user['role'] === 'student' && $supportRequest->student_id === $user['id'];
        $statusVal = $supportRequest->status instanceof RequestStatus
            ? $supportRequest->status->value
            : $supportRequest->status;

        if (! $isOwner && $user['role'] !== 'admin') {
            return redirect()->route('requests.show', $supportRequest)
                ->with('error', 'Bạn không có quyền sửa yêu cầu này.');
        }

        if ($statusVal !== 'new') {
            return redirect()->route('requests.show', $supportRequest)
                ->with('error', 'Chỉ được sửa yêu cầu ở trạng thái "Mới tạo".');
        }

        return view('requests.edit', [
            'request' => $supportRequest,
            'user' => $user,
            'departments' => $this->departments(),
            'supportTypes' => $this->supportTypes(),
            'demoUsers' => $this->demoUsers(),
        ]);
    }

    public function update(Request $request, SupportRequest $supportRequest)
    {
        $user = $this->currentUser();
        $isOwner = $user['role'] === 'student' && $supportRequest->student_id === $user['id'];

        if (! $isOwner && $user['role'] !== 'admin') {
            return back()->with('error', 'Bạn không có quyền sửa yêu cầu này.');
        }

        // Chỉ title + content — không cho đổi phòng ban / loại hỗ trợ / priority
        $data = $request->validate([
            'title' => 'required|string|min:10|max:255',
            'content' => 'required|string|min:20',
        ], [
            'title.required' => 'Vui lòng nhập tiêu đề yêu cầu.',
            'title.min' => 'Tiêu đề phải có ít nhất 10 ký tự.',
            'content.required' => 'Vui lòng nhập nội dung yêu cầu.',
            'content.min' => 'Nội dung phải có ít nhất 20 ký tự.',
        ]);

        try {
            $this->workflow->update($supportRequest, $data, $user['id']);
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('requests.show', $supportRequest)
            ->with('success', 'Đã cập nhật yêu cầu thành công.');
    }

    public function updateStatus(Request $request, SupportRequest $supportRequest)
    {
        $user = $this->currentUser();
        $data = $request->validate([
            'status' => 'required|in:new,received,in_progress,resolved,closed,cancelled',
            'note' => 'nullable|string|max:1000',
        ]);

        $toStatus = $data['status'];
        $isOwner = $user['role'] === 'student' && $supportRequest->student_id === $user['id'];
        $statusVal = $supportRequest->status instanceof RequestStatus
            ? $supportRequest->status->value
            : $supportRequest->status;

        if ($user['role'] === 'student') {
            if (! $isOwner) {
                return back()->with('error', 'Bạn không có quyền đổi trạng thái yêu cầu này.');
            }
            if ($statusVal !== 'resolved' || ! in_array($toStatus, ['closed', 'in_progress'], true)) {
                return back()->with('error', 'Sinh viên chỉ được xác nhận đóng hoặc yêu cầu xử lý lại khi đang chờ phản hồi.');
            }
        } elseif (! in_array($user['role'], ['staff', 'department_head', 'admin'], true)) {
            return back()->with('error', 'Bạn không có quyền đổi trạng thái yêu cầu.');
        } elseif ($toStatus === 'closed' && $user['role'] === 'staff') {
            return back()->with('error', 'Cán bộ không tự đóng. Đánh dấu chờ phản hồi SV; sinh viên hoặc trưởng phòng sẽ xác nhận.');
        }

        try {
            $this->workflow->changeStatus(
                $supportRequest,
                $toStatus,
                $user['id'],
                $data['note'] ?? null,
            );
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã cập nhật trạng thái.');
    }

    public function assign(Request $request, SupportRequest $supportRequest)
    {
        $user = $this->currentUser();
        if (! in_array($user['role'], ['department_head', 'admin'], true)) {
            return back()->with('error', 'Chỉ trưởng phòng/admin được gán cán bộ xử lý.');
        }

        $data = $request->validate([
            'assigned_to' => 'required|integer',
        ]);

        try {
            $this->workflow->assign($supportRequest, (int) $data['assigned_to'], $user['id']);
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã gán cán bộ xử lý.');
    }

    public function cancel(Request $request, SupportRequest $supportRequest)
    {
        $user = $this->currentUser();
        $isOwner = $user['role'] === 'student' && $supportRequest->student_id === $user['id'];

        if (! $isOwner && $user['role'] !== 'admin') {
            return back()->with('error', 'Bạn không có quyền hủy yêu cầu này.');
        }

        try {
            $this->workflow->cancel(
                $supportRequest,
                $user['id'],
                $request->input('reason'),
                asStudent: $user['role'] === 'student',
            );
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã hủy yêu cầu.');
    }

    public function destroy(SupportRequest $supportRequest)
    {
        $user = $this->currentUser();
        $isOwner = $user['role'] === 'student' && $supportRequest->student_id === $user['id'];

        if (! $isOwner && $user['role'] !== 'admin') {
            return back()->with('error', 'Bạn không có quyền xóa yêu cầu này.');
        }

        try {
            $this->workflow->delete($supportRequest);
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('requests.index')
            ->with('success', 'Đã xóa yêu cầu thành công.');
    }

    /**
     * Quyền xem chi tiết — đồng bộ với filter danh sách index.
     */
    protected function canView(SupportRequest $supportRequest, array $user): bool
    {
        return match ($user['role']) {
            'student' => $supportRequest->student_id === $user['id'],
            'staff' => $supportRequest->assigned_to === $user['id'],
            'department_head' => $supportRequest->department_id === $user['department_id'],
            'admin' => true,
            default => false,
        };
    }

    protected function demoUsers(): array
    {
        return [
            [
                'id' => 12,
                'role' => 'student',
                'department_id' => null,
                'full_name' => 'Trần Thị B',
                'email' => 'sv001@university.edu.vn',
            ],
            [
                'id' => 21,
                'role' => 'staff',
                'department_id' => 3,
                'full_name' => 'Nguyễn Văn A',
                'email' => 'canbo01@university.edu.vn',
            ],
            [
                'id' => 31,
                'role' => 'department_head',
                'department_id' => 3,
                'full_name' => 'Lê Thị C',
                'email' => 'truongphong@university.edu.vn',
            ],
            [
                'id' => 1,
                'role' => 'admin',
                'department_id' => null,
                'full_name' => 'Admin Hệ thống',
                'email' => 'admin@university.edu.vn',
            ],
        ];
    }

    /** Mock data từ Module 2 — nguồn: config/master_data.php */
    protected function departments(): array
    {
        return config('master_data.departments', []);
    }

    protected function supportTypes(): array
    {
        return config('master_data.support_types', []);
    }

    protected function supportTypeBelongsToDepartment(int $supportTypeId, int $departmentId): bool
    {
        $types = $this->supportTypes();
        if (! isset($types[$supportTypeId])) {
            return false;
        }

        return (int) $types[$supportTypeId]['department_id'] === $departmentId;
    }
}
