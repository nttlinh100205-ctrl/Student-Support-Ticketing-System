<?php

namespace App\Http\Controllers\Web;

use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Models\SupportRequest;
use App\Services\RequestWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

/**
 * UI Controller (Blade) — dùng chung RequestWorkflowService với API.
 * Auth giả lập qua Session để test không cần header (tiện cho trình duyệt).
 */
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
        // Khẩn cấp lên đầu: urgent > high > normal > low, rồi mới nhất
        $query = SupportRequest::query()
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low')")
            ->latest();

        // Phân quyền xem danh sách:
        // - student: chỉ yêu cầu của mình
        // - staff: chỉ yêu cầu được gán cho mình (assigned_to)
        // - department_head: tất cả yêu cầu của phòng ban
        // - admin: tất cả yêu cầu
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

        $requests = $query->paginate(15)->withQueryString();

        return view('requests.index', [
            'requests' => $requests,
            'user' => $user,
            'statusFilter' => $request->query('status', ''),
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

        $data = $request->validate([
            'department_id' => 'required|integer',
            'support_type_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'priority' => 'nullable|in:low,normal,high,urgent',
        ], [
            'title.required' => 'Vui lòng nhập tiêu đề yêu cầu.',
            'department_id.required' => 'Vui lòng chọn phòng ban.',
            'support_type_id.required' => 'Vui lòng chọn loại hỗ trợ.',
        ]);

        $created = $this->workflow->create($data, $user['id']);

        return redirect()->route('requests.show', $created)
            ->with('success', 'Đã tạo yêu cầu thành công: '.$created->code);
    }

    public function show(SupportRequest $supportRequest)
    {
        $user = $this->currentUser();
        $histories = $supportRequest->statusHistories()->latest('id')->get();

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

    public function updateStatus(Request $request, SupportRequest $supportRequest)
    {
        $user = $this->currentUser();
        if (! in_array($user['role'], ['staff', 'department_head', 'admin'], true)) {
            return back()->with('error', 'Bạn không có quyền đổi trạng thái yêu cầu.');
        }

        $data = $request->validate([
            'status' => 'required|in:new,received,in_progress,resolved,closed,cancelled',
            'note' => 'nullable|string|max:1000',
        ]);

        // Chỉ trưởng phòng / admin được đóng yêu cầu (closed)
        if ($data['status'] === 'closed' && $user['role'] === 'staff') {
            return back()->with('error', 'Chỉ trưởng phòng hoặc admin được đóng yêu cầu.');
        }

        try {
            $this->workflow->changeStatus(
                $supportRequest,
                $data['status'],
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
            );
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã hủy yêu cầu.');
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

    /** Mock data từ Module 2 */
    protected function departments(): array
    {
        return [
            1 => 'Phòng Đào tạo',
            2 => 'Phòng Công tác Sinh viên',
            3 => 'Phòng Tài chính – Kế toán',
            4 => 'Thư viện',
            5 => 'Trung tâm Hỗ trợ Sinh viên',
        ];
    }

    protected function supportTypes(): array
    {
        return [
            1 => ['name' => 'Xác nhận sinh viên', 'department_id' => 1],
            2 => ['name' => 'Xin bảng điểm', 'department_id' => 1],
            3 => ['name' => 'Hỗ trợ học bổng', 'department_id' => 2],
            4 => ['name' => 'Tư vấn tâm lý', 'department_id' => 5],
            5 => ['name' => 'Hỗ trợ học phí / vay vốn', 'department_id' => 3],
            6 => ['name' => 'Mượn tài liệu / phòng học', 'department_id' => 4],
            7 => ['name' => 'Khiếu nại / phản ánh', 'department_id' => 2],
        ];
    }
}
