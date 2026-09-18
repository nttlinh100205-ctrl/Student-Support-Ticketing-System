@extends('layouts.app')

@section('title', $request->code . ' — Module 3')

@section('content')
@php
    $statusLabels = [
        'new' => 'Mới tạo', 'received' => 'Đã tiếp nhận', 'in_progress' => 'Đang xử lý',
        'resolved' => 'Chờ phản hồi SV', 'closed' => 'Đã đóng', 'cancelled' => 'Đã hủy',
    ];
    $statusColors = [
        'new' => 'bg-blue-100 text-blue-800 border-blue-200',
        'received' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
        'in_progress' => 'bg-amber-100 text-amber-800 border-amber-200',
        'resolved' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
        'closed' => 'bg-slate-100 text-slate-700 border-slate-200',
        'cancelled' => 'bg-rose-100 text-rose-800 border-rose-200',
    ];
    $priorityLabels = ['low' => 'Thấp', 'normal' => 'Bình thường', 'high' => 'Cao', 'urgent' => 'Khẩn cấp'];
    $priorityColors = [
        'low' => 'bg-slate-100 text-slate-600', 'normal' => 'bg-sky-100 text-sky-700',
        'high' => 'bg-orange-100 text-orange-700', 'urgent' => 'bg-red-100 text-red-700',
    ];

    $statusVal = $request->status instanceof \App\Enums\RequestStatus ? $request->status->value : $request->status;
    $priorityVal = $request->priority instanceof \App\Enums\RequestPriority ? $request->priority->value : $request->priority;
    $allowedNext = $transitions[$statusVal] ?? [];
    // Staff không tự đóng — chờ SV; cũng không tự gửi lại in_progress từ resolved (chỉ SV)
    if ($user['role'] === 'staff') {
        $allowedNext = array_values(array_filter($allowedNext, fn ($s) => ! in_array($s, ['closed', 'in_progress'], true) || $statusVal !== 'resolved'));
        if ($statusVal === 'resolved') {
            $allowedNext = []; // chỉ chờ SV
        }
    }
    // Head/admin khi resolved: có thể đóng giúp SV, không tự "xử lý lại"
    if (in_array($user['role'], ['department_head', 'admin'], true) && $statusVal === 'resolved') {
        $allowedNext = array_values(array_filter($allowedNext, fn ($s) => $s === 'closed'));
    }
    $isTerminal = in_array($statusVal, ['closed', 'cancelled'], true);
    $canChangeStatus = in_array($user['role'], ['staff', 'department_head', 'admin'], true);
    $canAssign = in_array($user['role'], ['department_head', 'admin'], true);
    $needsAssign = $canChangeStatus && $request->assigned_to === null && ! $isTerminal;
    // SV phản hồi khi resolved
    $canStudentFeedback = $user['role'] === 'student'
        && $request->student_id === $user['id']
        && $statusVal === 'resolved';
    // Student chỉ hủy khi new/received; admin theo TRANSITIONS
    $canCancel = $user['role'] === 'admin'
        || ($user['role'] === 'student'
            && $request->student_id === $user['id']
            && in_array($statusVal, ['new', 'received'], true));
    $canEdit = $statusVal === 'new' && (
        ($user['role'] === 'student' && $request->student_id === $user['id']) || $user['role'] === 'admin'
    );
    $canDelete = $canEdit;
@endphp

<a href="{{ route('requests.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-blue-600 mb-5">
    ← Quay lại danh sách
</a>

{{-- Header card --}}
<div class="bg-white rounded-xl border border-slate-200 p-6 mb-5">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-2 flex-wrap">
                <span class="font-mono text-sm text-blue-600 font-semibold">{{ $request->code }}</span>
                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $statusColors[$statusVal] ?? '' }}">
                    {{ $statusLabels[$statusVal] ?? $statusVal }}
                </span>
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $priorityColors[$priorityVal] ?? '' }}">
                    {{ $priorityLabels[$priorityVal] ?? $priorityVal }}
                </span>
            </div>
            <h2 class="text-xl font-bold text-slate-900">{{ $request->title }}</h2>
        </div>
        <div class="text-right text-sm text-slate-500">
            <p>Tạo lúc {{ $request->created_at?->format('d/m/Y H:i') }}</p>
            <p class="mt-0.5">Cập nhật {{ $request->updated_at?->format('d/m/Y H:i') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-5 pt-5 border-t border-slate-100 text-sm">
        <div>
            <p class="text-xs text-slate-400">Phòng ban</p>
            <p class="font-medium text-slate-800">{{ $departments[$request->department_id] ?? '#'.$request->department_id }}</p>
        </div>
        <div>
            <p class="text-xs text-slate-400">Loại hỗ trợ</p>
            <p class="font-medium text-slate-800">{{ $supportTypes[$request->support_type_id]['name'] ?? '#'.$request->support_type_id }}</p>
        </div>
        <div>
            <p class="text-xs text-slate-400">Sinh viên (ID)</p>
            <p class="font-medium text-slate-800">#{{ $request->student_id }}</p>
        </div>
        <div>
            <p class="text-xs text-slate-400">Cán bộ xử lý</p>
            <p class="font-medium text-slate-800">
                @php
                    $staffNames = [21 => 'Nguyễn Văn A', 22 => 'Phạm Minh D', 23 => 'Hoàng Thị E'];
                @endphp
                {{ $request->assigned_to ? ($staffNames[$request->assigned_to] ?? '#'.$request->assigned_to) : 'Chưa gán' }}
            </p>
        </div>
    </div>
</div>

<div class="grid md:grid-cols-3 gap-5">
    {{-- Content + Actions --}}
    <div class="md:col-span-2 space-y-5">
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-3">Nội dung yêu cầu</h3>
            <p class="text-slate-800 whitespace-pre-wrap leading-relaxed">{{ $request->content }}</p>
            @if($request->cancelled_reason)
                <div class="mt-4 p-3 bg-rose-50 border border-rose-100 rounded-lg text-sm text-rose-700">
                    <strong>Lý do hủy:</strong> {{ $request->cancelled_reason }}
                </div>
            @endif
            @if($request->attachments && $request->attachments->isNotEmpty())
                <div class="mt-5 pt-4 border-t border-slate-100">
                    <h4 class="text-sm font-semibold text-slate-500 mb-3">Ảnh đính kèm ({{ $request->attachments->count() }})</h4>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        @foreach($request->attachments as $att)
                            <a href="{{ $att->url() }}" target="_blank" rel="noopener"
                               class="group block rounded-lg overflow-hidden border border-slate-200 bg-slate-50 hover:border-indigo-300 transition">
                                <img src="{{ $att->url() }}" alt="{{ $att->original_name }}"
                                     class="w-full h-32 object-cover group-hover:opacity-90">
                                <p class="px-2 py-1.5 text-xs text-slate-500 truncate">{{ $att->original_name }}</p>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        @if(!$isTerminal)
        <div class="bg-white rounded-xl border border-slate-200 p-6 space-y-5">
            <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide">Thao tác</h3>

            {{-- Bắt buộc gán trước khi đổi trạng thái --}}
            @if($needsAssign)
                <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
                    <strong>Chưa gán cán bộ.</strong> Trưởng phòng / admin phải gán cán bộ trước khi đổi trạng thái.
                </div>
            @endif

            @if($statusVal === 'resolved' && $user['role'] === 'staff')
                <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
                    Đã gửi cho sinh viên phản hồi. Chờ sinh viên xác nhận đóng hoặc yêu cầu xử lý lại.
                </div>
            @endif

            {{-- Student feedback khi resolved --}}
            @if($canStudentFeedback)
                <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-4 space-y-3">
                    <p class="text-sm font-semibold text-emerald-900">Phản hồi kết quả xử lý</p>
                    <p class="text-sm text-emerald-800">Cán bộ đã đánh dấu xử lý xong. Bạn xác nhận đã được giải quyết chưa?</p>
                    <form method="POST" action="{{ route('requests.update-status', $request) }}" class="space-y-3">
                        @csrf
                        @method('PUT')
                        <textarea name="note" rows="2" placeholder="Góp ý (tùy chọn)..."
                                  class="w-full border border-emerald-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 resize-none bg-white"></textarea>
                        <div class="flex flex-wrap gap-2">
                            <button type="submit" name="status" value="closed"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-sm font-medium bg-emerald-600 text-white hover:bg-emerald-700 transition">
                                Đã xử lý xong — Đóng yêu cầu
                            </button>
                            <button type="submit" name="status" value="in_progress"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-sm font-medium bg-amber-100 text-amber-800 border border-amber-200 hover:bg-amber-200 transition">
                                Chưa xong — Yêu cầu xử lý lại
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            {{-- Change status (staff/head/admin) — chỉ khi đã gán --}}
            @if($canChangeStatus && count($allowedNext) > 0 && ! $needsAssign)
                <div>
                    <p class="text-sm font-medium text-slate-700 mb-2">Đổi trạng thái</p>
                    <form method="POST" action="{{ route('requests.update-status', $request) }}" class="space-y-3">
                        @csrf
                        @method('PUT')
                        <textarea name="note" rows="2" placeholder="Ghi chú (tùy chọn)..."
                                  class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                        <div class="flex flex-wrap gap-2">
                            @foreach($allowedNext as $next)
                                <button type="submit" name="status" value="{{ $next }}"
                                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-sm font-medium transition border
                                               {{ $next === 'cancelled'
                                                   ? 'bg-rose-50 text-rose-700 hover:bg-rose-100 border-rose-200'
                                                   : ($next === 'resolved'
                                                       ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border-emerald-200'
                                                       : 'bg-blue-50 text-blue-700 hover:bg-blue-100 border-blue-200') }}">
                                    {{ $statusLabels[$next] ?? $next }}
                                </button>
                            @endforeach
                        </div>
                    </form>
                </div>
            @endif

            {{-- Assign — mock staff --}}
            @if($canAssign)
                <div class="{{ $canChangeStatus || $canStudentFeedback ? 'pt-4 border-t border-slate-100' : '' }}">
                    <p class="text-sm font-medium text-slate-700 mb-2">Gán cán bộ xử lý @if($needsAssign)<span class="text-amber-600 font-normal">(bắt buộc trước)</span>@endif</p>
                    <p class="text-xs text-slate-400 mb-2">Module 1 chưa sẵn sàng — dùng danh sách cán bộ giả để test</p>
                    <form method="POST" action="{{ route('requests.assign', $request) }}" class="flex gap-2">
                        @csrf
                        @method('PUT')
                        <select name="assigned_to" required
                                class="flex-1 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Chọn cán bộ --</option>
                            <option value="21">#21 — Nguyễn Văn A (Cán bộ, Phòng Tài chính)</option>
                            <option value="22">#22 — Phạm Minh D (Cán bộ, Phòng Tài chính)</option>
                            <option value="23">#23 — Hoàng Thị E (Cán bộ, Phòng Đào tạo)</option>
                            <option value="24">#24 — Trần Văn G (Cán bộ, Phòng CSVC)</option>
                            <option value="25">#25 — Lê Thị H (Cán bộ, Phòng CSVC)</option>
                        </select>
                        <button type="submit"
                                class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                            Gán
                        </button>
                    </form>
                </div>
            @endif

            {{-- Cancel --}}
            @if($canCancel && in_array('cancelled', $allowedNext, true))
                <div class="pt-4 border-t border-slate-100" x-data="{ open: false }">
                    <button type="button" @click="open = !open" class="text-sm text-rose-600 hover:text-rose-700 font-medium">
                        Hủy yêu cầu này...
                    </button>
                    <div x-show="open" x-cloak class="mt-3">
                        <form method="POST" action="{{ route('requests.cancel', $request) }}">
                            @csrf
                            @method('PUT')
                            <textarea name="reason" rows="2" placeholder="Nhập lý do hủy..."
                                      class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm mb-2 focus:outline-none focus:ring-2 focus:ring-rose-400 resize-none"></textarea>
                            <div class="flex gap-2">
                                <button type="submit" class="px-4 py-2 bg-rose-600 text-white text-sm font-medium rounded-lg hover:bg-rose-700">
                                    Xác nhận hủy
                                </button>
                                <button type="button" @click="open = false" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 rounded-lg">
                                    Đóng
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            @if($canEdit)
                <div class="pt-4 border-t border-slate-100">
                    <p class="text-sm text-slate-500 mb-2">Yêu cầu đang <strong>Mới tạo</strong> — có thể sửa nội dung hoặc xóa.</p>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('requests.edit', $request) }}"
                           class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 transition">
                            Sửa yêu cầu
                        </a>
                        <form method="POST" action="{{ route('requests.destroy', $request) }}"
                              onsubmit="return confirm('Bạn chắc chắn muốn xóa yêu cầu này?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-sm font-medium bg-rose-600 text-white hover:bg-rose-700 transition">
                                Xóa yêu cầu
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
        @endif
    </div>

    {{-- History timeline --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-4">Lịch sử trạng thái</h3>
        @if($histories->isEmpty())
            <p class="text-sm text-slate-400">Chưa có lịch sử</p>
        @else
            <ol class="relative border-l border-slate-200 space-y-5 ml-2">
                @foreach($histories as $h)
                    <li class="ml-4">
                        <div class="absolute -left-1.5 mt-1.5 w-3 h-3 rounded-full bg-blue-500 border-2 border-white"></div>
                        <div class="flex items-center gap-2 mb-0.5 flex-wrap">
                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium {{ $statusColors[$h->to_status] ?? 'bg-slate-100' }}">
                                {{ $statusLabels[$h->to_status] ?? $h->to_status }}
                            </span>
                            @if($h->from_status)
                                <span class="text-xs text-slate-400">từ {{ $statusLabels[$h->from_status] ?? $h->from_status }}</span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 mt-1">
                            {{ \Carbon\Carbon::parse($h->created_at)->format('d/m/Y H:i') }}
                            · bởi user #{{ $h->changed_by }}
                        </p>
                        @if($h->note)
                            <p class="text-sm text-slate-600 mt-1 bg-slate-50 rounded px-2 py-1">{{ $h->note }}</p>
                        @endif
                    </li>
                @endforeach
            </ol>
        @endif
    </div>
</div>

{{-- Alpine for cancel toggle (lightweight, no build needed if CDN, but we use Vite so add Alpine or pure JS) --}}
<script>
document.querySelectorAll('[x-data]').forEach(el => {
    // Minimal Alpine-like for cancel toggle if Alpine not loaded
});
</script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endsection
