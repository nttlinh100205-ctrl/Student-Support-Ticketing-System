<?php

namespace App\Http\Controllers\Web;

use App\Enums\RequestPriority;
use App\Enums\RequestStatus;
use App\Enums\SlaFlag;
use App\Http\Controllers\Controller;
use App\Models\CommentAttachment;
use App\Models\SupportRequest;
use App\Models\TicketComment;
use App\Services\CommentService;
use App\Services\ImageStorage;
use App\Services\RequestInbox;
use App\Services\RequestWorkflowService;
use App\Services\ServiceClient;
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
        protected RequestInbox $inbox,
    ) {}

    /** Lấy user giả từ session (mặc định student). */
    protected function currentUser(): array
    {
        if (! config('account.fake')) {
            return request()->attributes->get('account_user');
        }

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
        abort_unless(config('account.fake'), 404);
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
        $query = $this->buildFilteredQuery($request, $user);
        $requests = $query->paginate(15)->withQueryString();
        $slaStats = $this->computeSlaStatistics($user);

        return view('requests.index', [
            'requests' => $requests,
            'user' => $user,
            'statusFilter' => $request->query('status', ''),
            'priorityFilter' => $request->query('priority', ''),
            'slaFlagFilter' => $request->query('sla_flag', ''),
            'departmentFilter' => $request->query('department_id', ''),
            'assignedToFilter' => $request->query('assigned_to', ''),
            'fromFilter' => $request->query('from', ''),
            'toFilter' => $request->query('to', ''),
            'search' => $request->query('q', ''),
            'departments' => $this->departments(),
            'demoUsers' => $this->demoUsers(),
            'staffNames' => $this->staffNames(),
            'allUsers' => $this->allUsers($requests->pluck('student_id')->merge($requests->pluck('assigned_to'))->all()),
            'slaStats' => $slaStats,
            'queueLabels' => $this->inbox->labels($user),
            'queueCounts' => $this->inbox->counts($user),
        ]);
    }

    public function export(Request $request)
    {
        $user = $this->currentUser();
        if (! in_array($user['role'], ['admin', 'department_head'], true)) {
            return redirect()->route('requests.index')
                ->with('error', 'Chỉ Quản trị viên và Lãnh đạo đơn vị mới có quyền xem và xuất báo cáo tổng quan.');
        }

        $query = $this->buildFilteredQuery($request, $user);
        $requests = $query->get();

        $departments = $this->departments();
        $supportTypes = $this->supportTypes();
        $staffNames = $this->staffNames();
        $allUsers = $this->allUsers($requests->pluck('student_id')->merge($requests->pluck('assigned_to'))->all());

        $format = $request->query('format', 'excel');

        // Xuất file CSV thuần nếu có yêu cầu format=csv
        if ($format === 'csv') {
            $filename = 'danh-sach-yeu-cau-sv-'.now()->format('Ymd-His').'.csv';

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
            ];

            $callback = function () use ($requests, $departments, $supportTypes, $staffNames, $allUsers) {
                $handle = fopen('php://output', 'w');
                fwrite($handle, "\xEF\xBB\xBF");

                fputcsv($handle, [
                    'Mã yêu cầu',
                    'Tiêu đề',
                    'Họ tên sinh viên',
                    'Phòng ban',
                    'Loại hỗ trợ',
                    'Mức ưu tiên',
                    'Trạng thái',
                    'Cán bộ phụ trách',
                    'Hạn xử lý SLA',
                    'Tình trạng SLA',
                    'Đánh giá (Sao)',
                    'Nhận xét đánh giá',
                    'Thời gian tạo',
                    'Thời gian giải quyết',
                    'Thời gian đóng',
                ]);

                $statusLabels = [
                    'new' => 'Mới tạo',
                    'received' => 'Đã tiếp nhận',
                    'in_progress' => 'Đang xử lý',
                    'waiting_info' => 'Chờ bổ sung',
                    'resolved' => 'Đã giải quyết',
                    'closed' => 'Đã đóng',
                    'cancelled' => 'Đã hủy',
                    'rejected' => 'Từ chối',
                ];

                $priorityLabels = ['low' => 'Thấp', 'normal' => 'Bình thường', 'high' => 'Cao', 'urgent' => 'Khẩn cấp'];
                $slaLabels = ['on_time' => 'Đúng hạn', 'warning' => 'Sắp quá hạn', 'breached' => 'Quá hạn'];

                foreach ($requests as $req) {
                    $statusVal = $req->status instanceof RequestStatus ? $req->status->value : $req->status;
                    $priorityVal = $req->priority instanceof RequestPriority ? $req->priority->value : $req->priority;
                    $slaVal = $req->sla_flag instanceof SlaFlag ? $req->sla_flag->value : $req->sla_flag;

                    $studentName = $allUsers[$req->student_id]['full_name'] ?? 'Chưa có tên sinh viên';
                    $staffName = $req->assigned_to ? ($staffNames[$req->assigned_to] ?? $allUsers[$req->assigned_to]['full_name'] ?? 'Chưa có tên cán bộ') : 'Chưa phân công';

                    fputcsv($handle, [
                        $req->code,
                        $req->title,
                        $studentName,
                        $departments[$req->department_id] ?? ('Phòng #'.$req->department_id),
                        $supportTypes[$req->support_type_id]['name'] ?? ('Loại #'.$req->support_type_id),
                        $priorityLabels[$priorityVal] ?? $priorityVal,
                        $statusLabels[$statusVal] ?? $statusVal,
                        $staffName,
                        $req->sla_deadline_at ? $req->sla_deadline_at->format('d/m/Y H:i') : '',
                        $slaLabels[$slaVal] ?? $slaVal,
                        $req->rating ? ($req->rating.'/5 sao') : 'Chưa đánh giá',
                        $req->rating_comment ?? '',
                        $req->created_at ? $req->created_at->format('d/m/Y H:i') : '',
                        $req->resolved_at ? $req->resolved_at->format('d/m/Y H:i') : '',
                        $req->closed_at ? $req->closed_at->format('d/m/Y H:i') : '',
                    ]);
                }

                fclose($handle);
            };

            return response()->stream($callback, 200, $headers);
        }

        // Xuất file Báo Cáo Excel Tổng Quan chuẩn định dạng học vụ (.xls)
        $slaStats = $this->computeSlaStatistics($user);
        $starDistribution = $this->computeStarDistribution($user);
        $byDepartment = $this->computeDepartmentBreakdown($user);

        $filename = 'Bao-cao-tong-quan-yeu-cau-ho-tro-'.now()->format('Ymd-His').'.xls';
        $content = view('reports.requests_excel', [
            'requests' => $requests,
            'slaStats' => $slaStats,
            'starDistribution' => $starDistribution,
            'byDepartment' => $byDepartment,
            'user' => $user,
            'departments' => $departments,
            'supportTypes' => $supportTypes,
            'staffNames' => $staffNames,
            'allUsers' => $allUsers,
        ])->render();

        if ($format === 'print') {
            $toolbar = '<div class="print-toolbar"><button onclick="window.print()">In / Lưu PDF</button><p>Chọn máy in hoặc Lưu dưới dạng PDF trong hộp thoại in.</p></div><style>@media print{.print-toolbar{display:none}}@page{size:A4 landscape;margin:12mm}.print-toolbar{padding:20px;font-family:Arial}</style>';

            return response(str_replace('<body>', '<body>'.$toolbar, $content))->header('Cache-Control', 'private, no-store');
        }

        return response("\xEF\xBB\xBF".$content, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }

    protected function buildFilteredQuery(Request $request, array $user)
    {
        $query = $this->inbox->scoped($user);
        $this->inbox->apply($query, (string) $request->query('queue', 'all'), $user);
        match ($request->query('sort', 'priority')) {
            'newest' => $query->orderByDesc('created_at'),
            'oldest' => $query->orderBy('created_at'),
            'deadline' => $query->orderByRaw('sla_deadline_at IS NULL')->orderBy('sla_deadline_at'),
            default => $query->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 WHEN 'low' THEN 4 ELSE 5 END"),
        };
        $query->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->query('priority'));
        }

        if ($request->filled('sla_flag')) {
            $query->where('sla_flag', $request->query('sla_flag'));
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

        return $query;
    }

    protected function computeSlaStatistics(array $user): array
    {
        $baseQuery = SupportRequest::query();
        if ($user['role'] === 'student') {
            $baseQuery->where('student_id', $user['id']);
        } elseif ($user['role'] === 'staff') {
            $baseQuery->where('assigned_to', $user['id']);
        } elseif ($user['role'] === 'department_head') {
            $baseQuery->where('department_id', $user['department_id']);
        }

        $total = (clone $baseQuery)->count();
        $inProgress = (clone $baseQuery)->whereIn('status', ['new', 'received', 'in_progress', 'waiting_info'])->count();
        $completed = (clone $baseQuery)->whereIn('status', ['resolved', 'closed'])->count();

        $warningCount = (clone $baseQuery)->where('sla_flag', 'warning')
            ->whereNotIn('status', ['resolved', 'closed', 'cancelled', 'rejected'])->count();
        $breachedCount = (clone $baseQuery)->where('sla_flag', 'breached')->count();

        $onTimeCount = max(0, $total - $breachedCount);
        $onTimeRate = $total > 0 ? round(($onTimeCount / $total) * 100, 1) : 100.0;

        $avgRating = (clone $baseQuery)->whereNotNull('rating')->avg('rating');
        $ratedCount = (clone $baseQuery)->whereNotNull('rating')->count();

        return [
            'total' => $total,
            'in_progress' => $inProgress,
            'completed' => $completed,
            'warning' => $warningCount,
            'breached' => $breachedCount,
            'on_time_rate' => $onTimeRate,
            'avg_rating' => $avgRating ? round((float) $avgRating, 1) : null,
            'rated_count' => $ratedCount,
        ];
    }

    protected function computeStarDistribution(array $user): array
    {
        $baseQuery = SupportRequest::query();
        if ($user['role'] === 'student') {
            $baseQuery->where('student_id', $user['id']);
        } elseif ($user['role'] === 'staff') {
            $baseQuery->where('assigned_to', $user['id']);
        } elseif ($user['role'] === 'department_head') {
            $baseQuery->where('department_id', $user['department_id']);
        }

        $dist = [];
        for ($s = 5; $s >= 1; $s--) {
            $dist[$s] = (clone $baseQuery)->where('rating', $s)->count();
        }

        return $dist;
    }

    protected function computeDepartmentBreakdown(array $user): array
    {
        $baseQuery = SupportRequest::query();
        if ($user['role'] === 'student') {
            $baseQuery->where('student_id', $user['id']);
        } elseif ($user['role'] === 'staff') {
            $baseQuery->where('assigned_to', $user['id']);
        } elseif ($user['role'] === 'department_head') {
            $baseQuery->where('department_id', $user['department_id']);
        }

        $departments = $this->departments();
        $breakdown = [];

        foreach ($departments as $deptId => $deptName) {
            $deptQuery = (clone $baseQuery)->where('department_id', $deptId);
            $dTotal = (clone $deptQuery)->count();

            if ($dTotal > 0 || in_array($user['role'], ['admin', 'department_head'], true)) {
                $avg = (clone $deptQuery)->whereNotNull('rating')->avg('rating');
                $breakdown[$deptId] = [
                    'name' => $deptName,
                    'total' => $dTotal,
                    'in_progress' => (clone $deptQuery)->whereIn('status', ['new', 'received', 'in_progress', 'waiting_info'])->count(),
                    'completed' => (clone $deptQuery)->whereIn('status', ['resolved', 'closed'])->count(),
                    'breached' => (clone $deptQuery)->where('sla_flag', 'breached')->count(),
                    'avg_rating' => $avg ? round((float) $avg, 1) : null,
                    'rated_count' => (clone $deptQuery)->whereNotNull('rating')->count(),
                ];
            }
        }

        return $breakdown;
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

        $isFacilities = (int) $request->input('department_id') === (int) config('master_data.facilities_department_id', 6);

        $data = $request->validate([
            'department_id' => 'required|integer',
            'support_type_id' => 'required|integer',
            'form_values' => 'nullable|array|max:100',
            'form_files' => 'nullable|array|max:20',
            'form_files.*' => 'file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,webp,gif|max:10240',
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
        $request->attributes->set('operation_completed', true);

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
            'staffNames' => $this->staffNames(),
            'allUsers' => $this->allUsers([$supportRequest->student_id, $supportRequest->assigned_to, ...$histories->pluck('changed_by')->all()]),
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
            'rating_attitude' => 'nullable|integer|between:1,5',
            'rating_speed' => 'nullable|integer|between:1,5',
            'rating_quality' => 'nullable|integer|between:1,5',
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
                'rating_attitude' => $data['rating_attitude'] ?? null,
                'rating_speed' => $data['rating_speed'] ?? null,
                'rating_quality' => $data['rating_quality'] ?? null,
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

        if (app(ImageStorage::class)->isCloud($commentAttachment->path)) {
            return app(ImageStorage::class)->response($commentAttachment->path, $commentAttachment->original_name);
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
            'status' => 'required|in:new,received,in_progress,waiting_info,resolved,closed,cancelled,rejected',
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
            'sla_deadline_at' => 'nullable|date|after:now',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'note' => 'nullable|string|max:1000',
        ]);

        try {
            $this->workflow->assign($supportRequest, (int) $data['assigned_to'], $user['id'], $data);
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
            'body' => 'required|string|max:5000',
            'is_internal' => 'sometimes|boolean',
            'attachments' => 'sometimes|array|max:5',
            'attachments.*' => 'file|max:10240',
        ], [
            'body.required' => 'Nội dung bình luận không được để trống.',
            'body.max' => 'Nội dung bình luận tối đa 5000 ký tự.',
            'attachments.max' => 'Chỉ được đính kèm tối đa 5 file.',
            'attachments.*.max' => 'Mỗi file đính kèm không quá 10 MB.',
        ]);

        $files = $request->file('attachments', []) ?: [];
        if (! is_array($files)) {
            $files = [$files];
        }

        try {
            $this->commentService->addComment(
                ticket: $supportRequest,
                data: $data,
                userId: $user['id'],
                userName: $user['full_name'],
                userRole: $user['role'],
                files: $files,
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
        $users = config('master_data.users');
        if (is_array($users) && ! empty($users)) {
            return array_values($users);
        }

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

    public function staffNames(): array
    {
        $staff = config('master_data.staff', []);
        $names = [];
        foreach ($staff as $id => $item) {
            $names[(int) $id] = is_array($item) ? ($item['full_name'] ?? 'Chưa có tên cán bộ') : (string) $item;
        }

        foreach ($this->demoUsers() as $u) {
            if ($u['role'] === 'staff' && ! isset($names[$u['id']])) {
                $names[$u['id']] = $u['full_name'];
            }
        }

        return $names;
    }

    public function allUsers(array $ids = []): array
    {
        $users = config('master_data.users', []);
        $map = [];
        foreach ($users as $id => $item) {
            $map[(int) $id] = $item;
        }
        foreach ($this->demoUsers() as $u) {
            if (! isset($map[$u['id']])) {
                $map[$u['id']] = $u;
            }
        }

        if (! config('account.fake')) {
            $missing = array_values(array_filter(array_unique($ids), fn ($id) => $id && ! isset($map[$id])));
            foreach (array_chunk($missing, 100) as $batch) {
                $rows = app(ServiceClient::class)->get(config('account.url'), '/api/v1/directory/names', ['ids' => $batch])['data'] ?? [];
                foreach ($rows as $person) {
                    $map[(int) $person['id']] = ['id' => (int) $person['id'], 'full_name' => $person['name'], 'role' => strtolower($person['role'])];
                }
            }
        }

        return $map;
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
