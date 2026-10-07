@extends('layouts.app')

@section('title', $request->code . ' — Module 3')

@section('content')
@php
    $statusLabels = [
        'new' => 'Mới tạo', 'received' => 'Đã tiếp nhận', 'in_progress' => 'Đang xử lý',
        'waiting_info' => 'Chờ bổ sung',
        'resolved' => 'Chờ phản hồi SV', 'closed' => 'Đã đóng', 'cancelled' => 'Đã hủy',
    ];
    $statusColors = [
        'new' => 'bg-blue-100 text-blue-800 border-blue-200',
        'received' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
        'in_progress' => 'bg-amber-100 text-amber-800 border-amber-200',
        'waiting_info' => 'bg-orange-100 text-orange-800 border-orange-200',
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
    $slaFlag = $request->sla_flag instanceof \App\Enums\SlaFlag ? $request->sla_flag->value : $request->sla_flag;
    $allowedNext = $transitions[$statusVal] ?? [];
    $hasStudentReply = $request->hasStudentReplySinceResolution();
    if ($user['role'] === 'staff') {
        $allowedNext = $statusVal === 'resolved' && $hasStudentReply
            ? ['closed']
            : array_values(array_filter($allowedNext, fn ($s) => $s !== 'closed'));
    }
    if ($statusVal === 'resolved' && $user['role'] === 'admin') {
        $allowedNext = $hasStudentReply ? ['closed'] : [];
    }
    if ($user['role'] === 'department_head') {
        $allowedNext = [];
    }
    $isTerminal = in_array($statusVal, ['closed', 'cancelled'], true);
    $isStudentOwner = $user['role'] === 'student' && $request->student_id === $user['id'];
    $canReopen = $statusVal === 'closed' && ($isStudentOwner || $user['role'] === 'admin');
    $canStudentRework = $statusVal === 'resolved' && $isStudentOwner;
    $canChangeStatus = in_array($user['role'], ['staff', 'admin'], true);
    $canAssign = in_array($user['role'], ['department_head', 'admin'], true);
    $canTransfer = $user['role'] === 'admin';
    $needsAssign = $canChangeStatus && $request->assigned_to === null && ! $isTerminal;
    $canStudentReply = $isStudentOwner && in_array($statusVal, ['waiting_info', 'resolved'], true);
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
                    $staffNames = [
                        21 => 'Nguyễn Văn A', 22 => 'Phạm Minh D',
                    ];
                @endphp
                {{ $request->assigned_to ? ($staffNames[$request->assigned_to] ?? '#'.$request->assigned_to) : 'Chưa gán' }}
            </p>
        </div>
    </div>
</div>

@if($user['role'] === 'student' && $request->student_id === $user['id'])
    <a href="{{ route('requests.copy', $request) }}" class="mb-5 inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
        Sao chép yêu cầu
    </a>
@endif

@if($request->sla_deadline_at)
    <div class="mb-5 rounded-lg border px-4 py-3 text-sm {{ $slaFlag === 'breached' ? 'border-rose-200 bg-rose-50 text-rose-800' : ($slaFlag === 'warning' ? 'border-amber-200 bg-amber-50 text-amber-800' : 'border-slate-200 bg-white text-slate-700') }}">
        <strong>{{ $slaFlag === 'breached' ? 'Đã quá hạn SLA' : ($slaFlag === 'warning' ? 'Sắp quá hạn SLA' : 'Hạn xử lý SLA') }}</strong>
        <span class="ml-1">{{ $request->sla_deadline_at->format('d/m/Y H:i') }}</span>
        @if($slaFlag !== 'breached' && $request->slaRemainingHours() !== null)
            <span class="ml-2">Còn {{ max(0, $request->slaRemainingHours()) }} giờ</span>
        @endif
    </div>
@endif

@if($canReopen)
    <form method="POST" action="{{ route('requests.update-status', $request) }}" class="mb-5">
        @csrf
        @method('PUT')
        <button type="submit" name="status" value="in_progress"
                class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm font-medium text-blue-800 hover:bg-blue-100">
            {{ $isStudentOwner ? 'Yêu cầu xử lý lại' : 'Mở lại yêu cầu' }}
        </button>
    </form>
@endif

@if($canStudentRework)
    <form method="POST" action="{{ route('requests.update-status', $request) }}" class="mb-5">
        @csrf
        @method('PUT')
        <button type="submit" name="status" value="in_progress"
                class="inline-flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-medium text-amber-800 hover:bg-amber-100">
            Yêu cầu xử lý lại
        </button>
    </form>
@endif

@if($canRate || $request->rating)
    <section class="bg-white rounded-xl border border-slate-200 p-5 sm:p-6 mb-5" aria-labelledby="request-rating-title">
        <h3 id="request-rating-title" class="text-sm font-semibold text-slate-700">Đánh giá kết quả hỗ trợ</h3>
        @if($canRate)
            <p class="mt-1 text-sm text-slate-500">Yêu cầu đã hoàn tất. Đánh giá của bạn giúp cải thiện chất lượng hỗ trợ.</p>
            <form method="POST" action="{{ route('requests.rating.store', $request) }}" class="mt-4 space-y-4">
                @csrf
                <fieldset>
                    <legend class="text-sm font-medium text-slate-700 mb-2">Mức độ hài lòng</legend>
                    <div class="flex items-center gap-1" x-data="{ rating: {{ (int) old('rating', 0) }} }" role="radiogroup" aria-label="Chọn mức độ hài lòng từ 1 đến 5 sao">
                        @foreach([1 => 'Rất chưa hài lòng', 2 => 'Chưa hài lòng', 3 => 'Bình thường', 4 => 'Hài lòng', 5 => 'Rất hài lòng'] as $score => $label)
                            <label class="cursor-pointer rounded focus-within:outline-none focus-within:ring-2 focus-within:ring-amber-500" title="{{ $label }}">
                                <input class="peer sr-only" type="radio" name="rating" value="{{ $score }}" x-model.number="rating" @checked((int) old('rating') === $score) aria-label="{{ $score }} sao: {{ $label }}" required>
                                <span class="block text-4xl leading-none transition-colors"
                                      aria-hidden="true"
                                      :class="rating >= {{ $score }} ? 'text-amber-500' : 'text-slate-300'"
                                      x-text="rating >= {{ $score }} ? '★' : '☆'">☆</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Chọn từ 1 đến 5 sao</p>
                    @error('rating')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </fieldset>
                <div>
                    <label for="rating-comment" class="block text-sm font-medium text-slate-700 mb-1">Nhận xét <span class="font-normal text-slate-400">(không bắt buộc)</span></label>
                    <textarea id="rating-comment" name="rating_comment" rows="3" maxlength="1000" placeholder="Chia sẻ thêm về trải nghiệm hỗ trợ..."
                              class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200">{{ old('rating_comment') }}</textarea>
                    @error('rating_comment')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700">
                    Gửi đánh giá
                </button>
            </form>
        @else
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <span class="font-semibold text-amber-600" aria-label="{{ $request->rating }} trên 5 sao">{{ str_repeat('★', (int) $request->rating) }}{{ str_repeat('☆', 5 - (int) $request->rating) }}</span>
                <span class="text-sm font-medium text-slate-700">{{ $request->rating }}/5</span>
                @if($request->rated_at)<span class="text-xs text-slate-400">{{ $request->rated_at->format('d/m/Y') }}</span>@endif
            </div>
            @if($request->rating_comment)
                <p class="mt-2 text-sm text-slate-600 whitespace-pre-wrap">{{ $request->rating_comment }}</p>
            @endif
        @endif
    </section>
@endif

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

            @if($statusVal === 'resolved' && $user['role'] === 'staff' && ! $hasStudentReply)
                <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
                    Đã gửi kết quả xử lý. Chờ sinh viên phản hồi trước khi đóng yêu cầu.
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

            @if($canTransfer)
                <div class="pt-4 border-t border-slate-100">
                    <p class="text-sm font-medium text-slate-700 mb-2">Chuyển phòng ban</p>
                    <form method="POST" action="{{ route('requests.transfer', $request) }}" class="space-y-2">
                        @csrf
                        @method('PUT')
                        <select name="department_id" id="transfer-department" required onchange="filterTransferSupportTypes()"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Chọn phòng ban nhận --</option>
                            @foreach($departments as $departmentId => $departmentName)
                                @if($departmentId !== $request->department_id)
                                    <option value="{{ $departmentId }}">{{ $departmentName }}</option>
                                @endif
                            @endforeach
                        </select>
                        <div class="flex gap-2">
                            <select name="support_type_id" id="transfer-support-type" required
                                    class="min-w-0 flex-1 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">-- Chọn loại hỗ trợ --</option>
                                @foreach($supportTypes as $supportTypeId => $supportType)
                                    <option value="{{ $supportTypeId }}" data-dept="{{ $supportType['department_id'] }}">{{ $supportType['name'] }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Chuyển</button>
                        </div>
                    </form>
                </div>
            @endif

            {{-- Assign — mock staff --}}
            @if($canAssign)
                <div class="{{ $canChangeStatus ? 'pt-4 border-t border-slate-100' : '' }}">
                    <p class="text-sm font-medium text-slate-700 mb-2">Gán cán bộ xử lý @if($needsAssign)<span class="text-amber-600 font-normal">(bắt buộc trước)</span>@endif</p>
                    <p class="text-xs text-slate-400 mb-2">Module 1 chưa sẵn sàng — dùng danh sách cán bộ giả để test</p>
                    <form method="POST" action="{{ route('requests.assign', $request) }}" class="flex gap-2">
                        @csrf
                        @method('PUT')
                        <select name="assigned_to" required
                                class="flex-1 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Chọn cán bộ --</option>
                            @foreach($demoUsers as $staff)
                                @if($staff['role'] === 'staff' && in_array($staff['id'], config("master_data.staff_by_department.{$request->department_id}", []), true))
                                    <option value="{{ $staff['id'] }}" @selected($request->assigned_to === $staff['id'])>#{{ $staff['id'] }} — {{ $staff['full_name'] }}</option>
                                @endif
                            @endforeach
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
                            · {{ $h->changed_by === null ? 'bởi hệ thống' : 'bởi user #'.$h->changed_by }}
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

@if($statusVal === 'waiting_info' && $user['role'] === 'student' && $request->student_id === $user['id'])
    <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
        Cán bộ đang chờ bạn bổ sung thông tin. Vui lòng liên hệ trực tiếp phòng ban phụ trách hoặc theo dõi các ghi chú phản hồi từ cán bộ trong lịch sử trạng thái.
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- Comment Thread --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<div class="mt-5 bg-white rounded-xl border border-slate-200 p-6">
    <div class="flex items-center justify-between mb-5">
        <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            Trao đổi
            <span class="inline-flex items-center justify-center bg-slate-100 text-slate-600 text-xs font-medium rounded-full px-2 py-0.5">
                {{ $comments->total() }}
            </span>
        </h3>
    </div>

    {{-- Form nhập comment --}}
    @if(!$isTerminal && ($user['role'] !== 'student' || $canStudentReply))
    <form method="POST" action="{{ route('requests.comments.store', $request) }}" enctype="multipart/form-data" class="mb-6">
        @csrf
        <div class="flex gap-3">
            {{-- Avatar --}}
            <div class="shrink-0">
                <div class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold
                    {{ $user['role'] === 'admin'
                        ? 'bg-purple-100 text-purple-700'
                        : 'bg-emerald-100 text-emerald-700' }}">
                    {{ mb_substr($user['full_name'], 0, 1) }}
                </div>
            </div>

            <div class="flex-1 space-y-2">
                @if(in_array($user['role'], ['staff', 'department_head', 'admin'], true))
                    <select aria-label="Chọn câu trả lời mẫu" onchange="document.getElementById('comment-body').value = this.value; this.value = ''"
                            class="w-full border border-slate-200 rounded-lg bg-white px-3 py-2 text-sm text-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Chèn câu trả lời mẫu...</option>
                        @foreach($replyTemplates as $templateKey => $template)
                            <option value="{{ $template }}">{{ ['received' => 'Đã tiếp nhận', 'need_info' => 'Yêu cầu bổ sung', 'in_progress' => 'Đang xử lý', 'resolved' => 'Đã xử lý'][$templateKey] ?? $templateKey }}</option>
                        @endforeach
                    </select>
                @endif
                <textarea id="comment-body" name="body" rows="3" required
                          placeholder="Nhập nội dung trao đổi..."
                          class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none transition"
                >{{ old('body') }}</textarea>

                <div class="flex flex-wrap items-center gap-3">
                    {{-- Upload file --}}
                    <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-slate-600 bg-slate-50 border border-slate-200 hover:bg-slate-100 cursor-pointer transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        Đính kèm file
                        <input type="file" name="attachments[]" multiple class="hidden" id="comment-files" onchange="updateFileLabel(this)">
                    </label>
                    <span id="file-count" class="text-xs text-slate-400"></span>

                    {{-- Checkbox nội bộ — chỉ staff/head/admin --}}
                    @if(in_array($user['role'], ['staff', 'department_head', 'admin']))
                    <label class="inline-flex items-center gap-1.5 text-xs text-amber-700 cursor-pointer ml-auto">
                        <input type="checkbox" name="is_internal" value="1"
                               class="rounded border-amber-300 text-amber-600 focus:ring-amber-500 w-3.5 h-3.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        Nội bộ (SV không thấy)
                    </label>
                    @endif

                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 transition shadow-sm ml-auto">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        Gửi
                    </button>
                </div>
            </div>
        </div>
    </form>
    @else
    <div class="mb-6 rounded-lg bg-slate-50 border border-slate-200 px-4 py-3 text-sm text-slate-500">
        @if($isTerminal)
            Yêu cầu đã {{ $statusVal === 'closed' ? 'đóng' : 'hủy' }} — không thể thêm trao đổi mới.
        @else
            Bạn có thể phản hồi khi yêu cầu đang chờ bổ sung hoặc chờ xác nhận kết quả.
        @endif
    </div>
    @endif

    {{-- Danh sách comment --}}
    @if($comments->isEmpty())
        <div class="text-center py-8">
            <svg class="w-12 h-12 mx-auto text-slate-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            <p class="text-sm text-slate-400">Chưa có trao đổi nào</p>
            <p class="text-xs text-slate-300 mt-1">Hãy bắt đầu cuộc trao đổi đầu tiên</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($comments as $comment)
                @php
                    $isOwn = $comment->user_id === $user['id'];
                    $roleColors = [
                        'student' => 'bg-blue-100 text-blue-700',
                        'staff' => 'bg-emerald-100 text-emerald-700',
                        'department_head' => 'bg-indigo-100 text-indigo-700',
                        'admin' => 'bg-purple-100 text-purple-700',
                    ];
                    $roleBadge = [
                        'student' => 'Sinh viên',
                        'staff' => 'Cán bộ',
                        'department_head' => 'Trưởng phòng',
                        'admin' => 'Admin',
                    ];
                @endphp
                <div class="flex gap-3 group {{ $comment->is_internal ? 'relative' : '' }}">
                    {{-- Avatar --}}
                    <div class="shrink-0">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold {{ $roleColors[$comment->user_role] ?? 'bg-slate-100 text-slate-600' }}">
                            {{ mb_substr($comment->user_name ?? '?', 0, 1) }}
                        </div>
                    </div>

                    {{-- Nội dung --}}
                    <div class="flex-1 min-w-0">
                        <div class="rounded-xl px-4 py-3 {{ $comment->is_internal
                            ? 'bg-amber-50/80 border border-amber-200/60'
                            : ($isOwn ? 'bg-blue-50/60 border border-blue-100' : 'bg-slate-50 border border-slate-100') }}">

                            {{-- Header --}}
                            <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                                <span class="text-sm font-semibold text-slate-900">{{ $comment->user_name ?? 'User #'.$comment->user_id }}</span>
                                <span class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-medium {{ $roleColors[$comment->user_role] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ $roleBadge[$comment->user_role] ?? $comment->user_role }}
                                </span>
                                @if($comment->is_internal)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 text-amber-700 px-1.5 py-0.5 text-[10px] font-medium">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                        Nội bộ
                                    </span>
                                @endif
                                <span class="text-[11px] text-slate-400 ml-auto">{{ $comment->created_at->format('d/m/Y H:i') }}</span>
                            </div>

                            {{-- Body --}}
                            <p class="text-sm text-slate-700 whitespace-pre-wrap leading-relaxed">{{ $comment->body }}</p>

                            {{-- File đính kèm --}}
                            @if($comment->attachments && $comment->attachments->isNotEmpty())
                                <div class="mt-3 pt-2 border-t {{ $comment->is_internal ? 'border-amber-200/50' : 'border-slate-100' }}">
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($comment->attachments as $att)
                                            <a href="{{ route('requests.comments.attachments.preview', ['supportRequest' => $request, 'comment' => $comment, 'commentAttachment' => $att]) }}" target="_blank" rel="noopener"
                                               class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-medium bg-white border border-slate-200 text-slate-600 hover:border-blue-300 hover:text-blue-600 transition shadow-sm">
                                                @if($att->isImage())
                                                    <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                @else
                                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                @endif
                                                <span class="max-w-[150px] truncate">{{ $att->original_name }}</span>
                                                <span class="text-[10px] text-slate-400">{{ number_format($att->size / 1024, 0) }}KB</span>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Nút xóa (chủ comment hoặc admin) --}}
                        @if($isOwn || $user['role'] === 'admin')
                            <div class="mt-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <form method="POST" action="{{ route('requests.comments.destroy', [$request, $comment]) }}"
                                      onsubmit="return confirm('Bạn chắc chắn muốn xóa bình luận này?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-[11px] text-rose-400 hover:text-rose-600 transition">
                                        Xóa bình luận
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Phân trang --}}
        @if($comments->hasPages())
            <div class="mt-4 pt-4 border-t border-slate-100">
                {{ $comments->links() }}
            </div>
        @endif
    @endif
</div>

<script>
function updateFileLabel(input) {
    const count = input.files.length;
    document.getElementById('file-count').textContent =
        count > 0 ? count + ' file đã chọn' : '';
}
function filterTransferSupportTypes() {
    const departmentId = document.getElementById('transfer-department').value;
    const select = document.getElementById('transfer-support-type');
    Array.from(select.options).forEach((option, index) => {
        if (index === 0) return;
        option.hidden = Boolean(departmentId) && option.dataset.dept !== departmentId;
        if (option.hidden && option.selected) option.selected = false;
    });
}
</script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endsection
