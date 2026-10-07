<?php

namespace App\Http\Controllers\Web;

use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Models\CommentAttachment;
use App\Models\SupportRequest;
use App\Models\TicketComment;
use App\Services\CommentService;
use App\Services\RequestWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;


class RequestWebController extends Controller
{
    public function __construct(
        protected RequestWorkflowService $workflow,
        protected CommentService $commentService,
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

    public function create(Request $request)
    {
        $user = $this->currentUser();
        if ($user['role'] !== 'student') {
            return redirect()->route('requests.index')
                ->with('error', 'Chỉ sinh viên được tạo yêu cầu hỗ trợ.');
        }

        $copyRequest = $request->filled('copy_from')
            ? SupportRequest::query()->whereKey((int) $request->query('copy_from'))
                ->where('student_id', $user['id'])->first()
            : null;

        return view('requests.create', [
            'user' => $user,
            'copyRequest' => $copyRequest,
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

        if (! $request->boolean('confirm_duplicate')) {
            $duplicates = $this->workflow->findPotentialDuplicates($data, $user['id']);
            if ($duplicates->isNotEmpty()) {
                return back()->withInput()->with('possible_duplicates', $duplicates);
            }
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
        $statusVal = $supportRequest->status instanceof RequestStatus
            ? $supportRequest->status->value
            : $supportRequest->status;
        $canRate = $user['role'] === 'student'
            && $supportRequest->student_id === $user['id']
            && $statusVal === RequestStatus::Closed->value
            && $supportRequest->rating === null;

        // Load comments (SV chỉ thấy comment công khai)
        $comments = $this->commentService->listComments(
            $supportRequest,
            $user['role'],
            perPage: 50,
        );

        return view('requests.show', [
            'request' => $supportRequest,
            'canRate' => $canRate,
            'histories' => $histories,
            'comments' => $comments,
            'user' => $user,
            'departments' => $this->departments(),
            'supportTypes' => $this->supportTypes(),
            'transitions' => RequestWorkflowService::TRANSITIONS,
            'demoUsers' => $this->demoUsers(),
            'replyTemplates' => config('master_data.reply_templates', []),
        ]);
    }

    public function copy(SupportRequest $supportRequest)
    {
        $user = $this->currentUser();
        if ($user['role'] !== 'student' || $supportRequest->student_id !== $user['id']) {
            return redirect()->route('requests.index')
                ->with('error', 'Chỉ sinh viên tạo yêu cầu mới được sao chép yêu cầu này.');
        }

        return redirect()->route('requests.create', ['copy_from' => $supportRequest->id]);
    }

    public function rate(Request $request, SupportRequest $supportRequest)
    {
        $user = $this->currentUser();
        $statusVal = $supportRequest->status instanceof RequestStatus
            ? $supportRequest->status->value
            : $supportRequest->status;

        if ($user['role'] !== 'student' || $supportRequest->student_id !== $user['id']) {
            return back()->with('error', 'Chỉ sinh viên gửi yêu cầu mới được đánh giá.');
        }

        if ($statusVal !== RequestStatus::Closed->value) {
            return back()->with('error', 'Chỉ yêu cầu đã hoàn tất mới được đánh giá.');
        }

        $data = $request->validate([
            'rating' => 'required|integer|between:1,5',
            'rating_comment' => 'nullable|string|max:1000',
        ], [
            'rating.required' => 'Vui lòng chọn mức đánh giá.',
            'rating.between' => 'Mức đánh giá phải từ 1 đến 5 sao.',
            'rating_comment.max' => 'Nhận xét tối đa 1000 ký tự.',
        ]);

        $updated = SupportRequest::query()
            ->whereKey($supportRequest->id)
            ->where('student_id', $user['id'])
            ->where('status', RequestStatus::Closed->value)
            ->whereNull('rating')
            ->update([
                'rating' => $data['rating'],
                'rating_comment' => $data['rating_comment'] ?? null,
                'rated_at' => now(),
            ]);

        if (! $updated) {
            return back()->with('error', 'Yêu cầu này đã được đánh giá hoặc không còn đủ điều kiện.');
        }

        return back()->with('success', 'Cảm ơn bạn đã đánh giá kết quả hỗ trợ.');
    }

    public function previewCommentAttachment(
        SupportRequest $supportRequest,
        TicketComment $comment,
        CommentAttachment $commentAttachment,
    ) {
        $user = $this->currentUser();

        if (! $this->canView($supportRequest, $user)
            || $comment->request_id !== $supportRequest->id
            || $commentAttachment->comment_id !== $comment->id
            || ($user['role'] === 'student' && $comment->is_internal)) {
            abort(404);
        }

        $disk = Storage::disk('local');
        abort_unless($disk->exists($commentAttachment->path), 404);

        $path = $disk->path($commentAttachment->path);
        $mimeType = $commentAttachment->mime_type ?: 'application/octet-stream';
        $headers = [
            'Content-Type' => $mimeType,
            'X-Content-Type-Options' => 'nosniff',
        ];
        $inlineTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf', 'text/plain'];

        if (in_array($mimeType, $inlineTypes, true)) {
            return response()->file($path, $headers);
        }

        return response()->download($path, $commentAttachment->original_name, $headers);
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
        if (! $this->canView($supportRequest, $user)) {
            return back()->with('error', 'Bạn không có quyền đổi trạng thái yêu cầu này.');
        }

        $data = $request->validate([
            'status' => 'required|in:new,received,in_progress,waiting_info,resolved,closed,cancelled',
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
            if (! in_array($statusVal, ['resolved', 'closed'], true) || $toStatus !== 'in_progress') {
                return back()->with('error', 'Sinh viên chỉ được yêu cầu xử lý lại khi yêu cầu đã xử lý xong hoặc đã đóng.');
            }
        } elseif (! in_array($user['role'], ['staff', 'admin'], true)) {
            return back()->with('error', 'Bạn không có quyền đổi trạng thái yêu cầu.');
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
        if (! $this->canView($supportRequest, $user)) {
            return back()->with('error', 'Bạn không có quyền gán cán bộ cho yêu cầu này.');
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

    public function transfer(Request $request, SupportRequest $supportRequest)
    {
        $user = $this->currentUser();
        if ($user['role'] !== 'admin') {
            return back()->with('error', 'Chỉ admin được chuyển yêu cầu sang phòng ban khác.');
        }

        $data = $request->validate([
            'department_id' => ['required', 'integer', Rule::in(array_keys($this->departments()))],
            'support_type_id' => 'required|integer',
        ]);

        try {
            $this->workflow->transfer(
                $supportRequest,
                (int) $data['department_id'],
                (int) $data['support_type_id'],
                $user['id'],
            );
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('requests.index')->with('success', 'Đã chuyển yêu cầu sang phòng ban mới.');
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

    /*
    |--------------------------------------------------------------------------
    | Comment Thread (Trao đổi)
    |--------------------------------------------------------------------------
    */

    public function storeComment(Request $request, SupportRequest $supportRequest)
    {
        $user = $this->currentUser();

        if (! $this->canView($supportRequest, $user)) {
            return back()->with('error', 'Bạn không có quyền bình luận vào yêu cầu này.');
        }

        $data = $request->validate([
            'body'          => 'required|string|max:5000',
            'is_internal'   => 'sometimes|boolean',
            'attachments'   => 'sometimes|array|max:5',
            'attachments.*' => 'file|max:10240',
        ], [
            'body.required'      => 'Nội dung bình luận không được để trống.',
            'body.max'           => 'Nội dung bình luận tối đa 5000 ký tự.',
            'attachments.max'    => 'Chỉ được đính kèm tối đa 5 file.',
            'attachments.*.max'  => 'Mỗi file đính kèm không quá 10 MB.',
        ]);

        $files = $request->file('attachments', []) ?: [];
        if (! is_array($files)) {
            $files = [$files];
        }

        try {
            $this->commentService->addComment(
                ticket:   $supportRequest,
                data:     $data,
                userId:   $user['id'],
                userName: $user['full_name'],
                userRole: $user['role'],
                files:    $files,
            );
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return back()->with('success', 'Đã thêm bình luận.');
    }

    public function destroyComment(SupportRequest $supportRequest, TicketComment $comment)
    {
        $user = $this->currentUser();

        // Đảm bảo comment thuộc ticket
        if ($comment->request_id !== $supportRequest->id) {
            return back()->with('error', 'Bình luận không thuộc yêu cầu này.');
        }

        try {
            $this->commentService->deleteComment(
                $comment,
                $user['id'],
                $user['role'],
            );
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã xóa bình luận.');
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
                'id' => 22,
                'role' => 'staff',
                'department_id' => 3,
                'full_name' => 'Phạm Minh D',
                'email' => 'canbo02@university.edu.vn',
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
