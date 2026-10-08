<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\RequestStatusHistory;
use App\Services\RequestInbox;
use App\Services\WorkspaceDashboard;
use App\Services\WorkspaceSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkspaceController extends Controller
{
    public function __construct(private RequestInbox $inbox, private WorkspaceDashboard $dashboard) {}

    private function user(): array
    {
        return request()->attributes->get('account_user') ?? session('fake_user', ['id' => 12, 'role' => 'student', 'full_name' => 'Sinh viên', 'department_id' => null]);
    }

    public function index()
    {
        $user = $this->user();
        $view = match ($user['role']) {
            'admin' => 'admin', 'department_head' => 'manager', 'staff' => 'staff', default => 'student'
        };

        return view($view.'.dashboard', ['user' => $user] + $this->dashboard->data($user));
    }

    public function team()
    {
        $user = $this->user();

        return view('manager.team', ['user' => $user, 'team' => $this->dashboard->team($user), 'pending' => $this->inbox->apply($this->inbox->scoped($user), 'unassigned', $user)->latest()->paginate(10)]);
    }

    public function kanban(Request $request)
    {
        $user = $this->user();
        $query = $this->inbox->scoped($user);
        if ($request->filled('q')) {
            $query->where('title', 'like', '%'.$request->string('q')->toString().'%');
        }
        $tickets = $query->latest()->paginate(70);

        return view('staff.kanban', compact('user', 'tickets'));
    }

    public function ratings()
    {
        $user = $this->user();

        return view('staff.ratings', ['user' => $user, 'tickets' => $this->inbox->scoped($user)->whereNotNull('rating')->latest('rated_at')->paginate(15)]);
    }

    public function audit()
    {
        $user = $this->user();

        return view('admin.audit', ['user' => $user, 'events' => RequestStatusHistory::with('request')->latest('id')->paginate(25)]);
    }

    public function settings()
    {
        return view('admin.settings', ['user' => $this->user(), 'settings' => app(WorkspaceSettings::class)->all()]);
    }

    public function saveSettings(Request $request)
    {
        $data = $request->validate(['low' => 'required|integer|between:1,8760', 'normal' => 'required|integer|between:1,8760', 'high' => 'required|integer|between:1,8760', 'urgent' => 'required|integer|between:1,8760', 'warning' => 'required|integer|between:1,99']);
        $values = [];
        foreach (['low', 'normal', 'high', 'urgent'] as $priority) {
            $values['deadline_hours.'.$priority] = $data[$priority];
        }$values['warning_threshold_percent'] = $data['warning'];
        app(WorkspaceSettings::class)->save($values, $this->user()['id']);

        return back()->with('success', 'Đã lưu cấu hình SLA. Thời hạn mới áp dụng cho yêu cầu tạo sau này khi loại hỗ trợ chưa có SLA riêng.');
    }

    public function unassign(Request $request, int $id)
    {
        $user = $this->user();
        $data = $request->validate(['note' => 'required|string|max:1000']);
        DB::transaction(function () use ($id, $user, $data) {
            $ticket = $this->inbox->scoped($user)->lockForUpdate()->findOrFail($id);
            abort_unless(in_array($ticket->status->value, ['new', 'received'], true), 422, 'Chỉ thu hồi phân công trước khi bắt đầu xử lý.');
            $from = $ticket->status->value;
            $ticket->forceFill(['assigned_to' => null, 'assigned_at' => null, 'status' => 'new'])->save();
            RequestStatusHistory::create(['request_id' => $id, 'from_status' => $from, 'to_status' => 'new', 'changed_by' => $user['id'], 'note' => 'Thu hồi phân công: '.$data['note'], 'created_at' => now()]);
        });

        return back()->with('success','Đã thu hồi phân công. Yêu cầu trở lại danh sách chờ phân công.');
    }
}
