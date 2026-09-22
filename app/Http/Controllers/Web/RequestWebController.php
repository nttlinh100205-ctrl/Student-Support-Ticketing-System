<?php

namespace App\Http\Controllers\Web;

use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Models\SupportDepartment;
use App\Models\SupportRequest;
use App\Models\SupportType;
use App\Services\RequestWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

/**
 * UI Controller (Blade)
 * Dùng chung RequestWorkflowService với API.
 *
 * Auth hiện đang giả lập qua Session để phục vụ test giao diện.
 */
class RequestWebController extends Controller
{
    public function __construct(
        protected RequestWorkflowService $workflow,
    ) {
    }

    /**
     * Lấy user giả từ session.
     * Mặc định là sinh viên.
     */
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

    /**
     * Đổi role giả lập để test giao diện.
     */
    public function switchRole(Request $request)
    {
        $users = $this->demoUsers();

        $id = (int) $request->input('user_id');

        $user = collect($users)
            ->firstWhere('id', $id);

        if ($user) {
            Session::put(
                'fake_user',
                $user
            );
        }

        return back();
    }

    /**
     * Danh sách yêu cầu.
     */
    public function index(Request $request)
    {
        $user = $this->currentUser();

        $query = SupportRequest::query()
            ->orderByRaw(
                "FIELD(priority, 'urgent', 'high', 'normal', 'low')"
            )
            ->latest();

        /*
        |--------------------------------------------------------------------------
        | PHÂN QUYỀN DANH SÁCH
        |--------------------------------------------------------------------------
        |
        | student:
        | Chỉ thấy yêu cầu của mình.
        |
        | staff:
        | Chỉ thấy yêu cầu được giao cho mình.
        |
        | department_head:
        | Thấy các yêu cầu của phòng ban.
        |
        | admin:
        | Thấy toàn bộ yêu cầu.
        |
        */

        if ($user['role'] === 'student') {

            $query->where(
                'student_id',
                $user['id']
            );

        } elseif ($user['role'] === 'staff') {

            $query->where(
                'assigned_to',
                $user['id']
            );

        } elseif ($user['role'] === 'department_head') {

            $query->where(
                'department_id',
                $user['department_id']
            );
        }

        /*
         * ADMIN không filter.
         */

        if ($request->filled('status')) {

            $query->where(
                'status',
                $request->query('status')
            );
        }

        $requests = $query
            ->paginate(15)
            ->withQueryString();

        return view('requests.index', [
            'requests' => $requests,
            'user' => $user,

            'statusFilter' =>
                $request->query(
                    'status',
                    ''
                ),

            'demoUsers' =>
                $this->demoUsers(),
        ]);
    }

    /**
     * Form tạo yêu cầu.
     */
    public function create()
    {
        $user = $this->currentUser();

        if ($user['role'] !== 'student') {

            return redirect()
                ->route('requests.index')
                ->with(
                    'error',
                    'Chỉ sinh viên được tạo yêu cầu hỗ trợ.'
                );
        }

        return view('requests.create', [

            'user' => $user,

            // Chỉ lấy phòng đang hoạt động.
            'departments' =>
                $this->departments(),

            // Chỉ lấy loại hỗ trợ đang hoạt động.
            'supportTypes' =>
                $this->supportTypes(),

            'demoUsers' =>
                $this->demoUsers(),
        ]);
    }

    /**
     * Lưu yêu cầu mới.
     */
    public function store(Request $request)
    {
        $user = $this->currentUser();

        if ($user['role'] !== 'student') {

            return back()
                ->with(
                    'error',
                    'Chỉ sinh viên được tạo yêu cầu hỗ trợ.'
                );
        }

        $data = $request->validate(
            [
                'department_id' => [
                    'required',
                    'integer',
                    'exists:support_departments,id',
                ],

                'support_type_id' => [
                    'required',
                    'integer',
                    'exists:support_types,id',
                ],

                'title' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'content' => [
                    'required',
                    'string',
                ],

                'priority' => [
                    'nullable',
                    'in:low,normal,high,urgent',
                ],
            ],
            [
                'title.required' =>
                    'Vui lòng nhập tiêu đề yêu cầu.',

                'department_id.required' =>
                    'Vui lòng chọn phòng ban.',

                'department_id.exists' =>
                    'Phòng ban không tồn tại.',

                'support_type_id.required' =>
                    'Vui lòng chọn loại hỗ trợ.',

                'support_type_id.exists' =>
                    'Loại hỗ trợ không tồn tại.',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | KIỂM TRA PHÒNG BAN ĐANG HOẠT ĐỘNG
        |--------------------------------------------------------------------------
        */

        $departmentExists =
            SupportDepartment::query()
                ->whereKey(
                    $data['department_id']
                )
                ->where(
                    'is_active',
                    true
                )
                ->exists();

        if (! $departmentExists) {

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Phòng ban đã ngừng hoạt động hoặc không tồn tại.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | KIỂM TRA LOẠI HỖ TRỢ
        |--------------------------------------------------------------------------
        |
        | Loại hỗ trợ phải:
        |
        | - tồn tại
        | - đang hoạt động
        | - thuộc đúng phòng ban người dùng đã chọn
        |
        */

        $supportType =
            SupportType::query()
                ->whereKey(
                    $data['support_type_id']
                )
                ->where(
                    'department_id',
                    $data['department_id']
                )
                ->where(
                    'is_active',
                    true
                )
                ->first();

        if (! $supportType) {

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Loại hỗ trợ không thuộc phòng ban đã chọn hoặc đã ngừng hoạt động.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | TẠO YÊU CẦU
        |--------------------------------------------------------------------------
        */

        $created =
            $this->workflow->create(
                $data,
                $user['id']
            );

        return redirect()
            ->route(
                'requests.show',
                $created
            )
            ->with(
                'success',
                'Đã tạo yêu cầu thành công: '
                . $created->code
            );
    }

    /**
     * Xem chi tiết yêu cầu.
     */
    public function show(
        SupportRequest $supportRequest
    ) {
        $user = $this->currentUser();

        $histories =
            $supportRequest
                ->statusHistories()
                ->latest('id')
                ->get();

        return view('requests.show', [

            'request' =>
                $supportRequest,

            'histories' =>
                $histories,

            'user' =>
                $user,

            /*
             * Ở trang chi tiết lấy cả dữ liệu đã ngừng hoạt động.
             *
             * Lý do:
             * Yêu cầu cũ có thể đang tham chiếu đến một phòng ban
             * hoặc loại hỗ trợ sau này bị tắt.
             */
            'departments' =>
                $this->departments(false),

            'supportTypes' =>
                $this->supportTypes(false),

            'transitions' =>
                RequestWorkflowService::TRANSITIONS,

            'demoUsers' =>
                $this->demoUsers(),
        ]);
    }

    /**
     * Cập nhật trạng thái yêu cầu.
     */
    public function updateStatus(
        Request $request,
        SupportRequest $supportRequest
    ) {
        $user = $this->currentUser();

        if (
            ! in_array(
                $user['role'],
                [
                    'staff',
                    'department_head',
                    'admin',
                ],
                true
            )
        ) {
            return back()
                ->with(
                    'error',
                    'Bạn không có quyền đổi trạng thái yêu cầu.'
                );
        }

        $data = $request->validate([
            'status' => [
                'required',
                'in:new,received,in_progress,resolved,closed,cancelled',
            ],

            'note' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        /*
         * Chỉ trưởng phòng hoặc admin được đóng yêu cầu.
         */
        if (
            $data['status'] === 'closed'
            && $user['role'] === 'staff'
        ) {
            return back()
                ->with(
                    'error',
                    'Chỉ trưởng phòng hoặc admin được đóng yêu cầu.'
                );
        }

        try {

            $this->workflow->changeStatus(

                $supportRequest,

                $data['status'],

                $user['id'],

                $data['note'] ?? null,
            );

        } catch (ValidationException $e) {

            return back()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }

        return back()
            ->with(
                'success',
                'Đã cập nhật trạng thái.'
            );
    }

    /**
     * Gán cán bộ xử lý.
     */
    public function assign(
        Request $request,
        SupportRequest $supportRequest
    ) {
        $user = $this->currentUser();

        if (
            ! in_array(
                $user['role'],
                [
                    'department_head',
                    'admin',
                ],
                true
            )
        ) {
            return back()
                ->with(
                    'error',
                    'Chỉ trưởng phòng/admin được gán cán bộ xử lý.'
                );
        }

        $data = $request->validate([
            'assigned_to' => [
                'required',
                'integer',
            ],
        ]);

        try {

            $this->workflow->assign(

                $supportRequest,

                (int) $data['assigned_to'],

                $user['id']
            );

        } catch (ValidationException $e) {

            return back()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }

        return back()
            ->with(
                'success',
                'Đã gán cán bộ xử lý.'
            );
    }

    /**
     * Hủy yêu cầu.
     */
    public function cancel(
        Request $request,
        SupportRequest $supportRequest
    ) {
        $user = $this->currentUser();

        $isOwner =
            $user['role'] === 'student'
            && $supportRequest->student_id
                === $user['id'];

        if (
            ! $isOwner
            && $user['role'] !== 'admin'
        ) {
            return back()
                ->with(
                    'error',
                    'Bạn không có quyền hủy yêu cầu này.'
                );
        }

        try {

            $this->workflow->cancel(

                $supportRequest,

                $user['id'],

                $request->input('reason'),
            );

        } catch (ValidationException $e) {

            return back()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }

        return back()
            ->with(
                'success',
                'Đã hủy yêu cầu.'
            );
    }

    /**
     * User giả để test role trên giao diện.
     */
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

    /**
     * ============================================================
     * MODULE 2 - PHÒNG BAN
     * ============================================================
     *
     * Trước đây dữ liệu được hardcode.
     *
     * Hiện tại lấy trực tiếp từ bảng support_departments.
     *
     * $activeOnly = true:
     * Chỉ lấy phòng đang hoạt động.
     *
     * $activeOnly = false:
     * Lấy cả phòng đã ngừng hoạt động để hiển thị dữ liệu lịch sử.
     */
    protected function departments(
        bool $activeOnly = true
    ): array {
        $query =
            SupportDepartment::query();

        if ($activeOnly) {

            $query->where(
                'is_active',
                true
            );
        }

        return $query
            ->orderBy('name')
            ->pluck(
                'name',
                'id'
            )
            ->toArray();
    }

    /**
     * ============================================================
     * MODULE 2 - LOẠI HỖ TRỢ
     * ============================================================
     *
     * Trước đây:
     *
     * [
     *   1 => [
     *      'name' => 'Xác nhận sinh viên',
     *      'department_id' => 1
     *   ]
     * ]
     *
     * Hiện tại lấy trực tiếp từ bảng support_types.
     */
    protected function supportTypes(
        bool $activeOnly = true
    ): array {
        $query =
            SupportType::query();

        if ($activeOnly) {

            $query->where(
                'is_active',
                true
            );
        }

        return $query
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'code',
                'department_id',
                'is_active',
            ])
            ->mapWithKeys(
                function (
                    SupportType $supportType
                ) {
                    return [

                        $supportType->id => [

                            'id' =>
                                $supportType->id,

                            'name' =>
                                $supportType->name,

                            'code' =>
                                $supportType->code,

                            'department_id' =>
                                $supportType
                                    ->department_id,

                            'is_active' =>
                                (bool)
                                $supportType
                                    ->is_active,
                        ],
                    ];
                }
            )
            ->toArray();
    }
}