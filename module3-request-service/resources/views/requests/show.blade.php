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
        'new' => 'bg-wood-100 text-wood-800 border-wood-200',
        'received' => 'bg-gold-100 text-gold-900 border-gold-200',
        'in_progress' => 'bg-amber-100 text-amber-900 border-amber-200',
        'waiting_info' => 'bg-orange-100 text-orange-900 border-orange-200',
        'resolved' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
        'closed' => 'bg-stone-150 text-stone-700 border-stone-300',
        'cancelled' => 'bg-rose-100 text-rose-800 border-rose-200',
    ];
    $priorityLabels = ['low' => 'Thấp', 'normal' => 'Bình thường', 'high' => 'Cao', 'urgent' => 'Khẩn cấp'];
    $priorityColors = [
        'low' => 'bg-stone-100 text-stone-600 border-stone-200',
        'normal' => 'bg-paper text-wood-800 border-borderWarm',
        'high' => 'bg-amber-50 text-amber-800 border-amber-200',
        'urgent' => 'bg-rose-50 text-rose-700 border-rose-200 font-semibold',
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
    $canCancel = $user['role'] === 'admin'
        || ($user['role'] === 'student'
            && $request->student_id === $user['id']
            && in_array($statusVal, ['new', 'received'], true));
    $canEdit = $statusVal === 'new' && (
        ($user['role'] === 'student' && $request->student_id === $user['id']) || $user['role'] === 'admin'
    );
    $canDelete = $canEdit;
@endphp

<div class="space-y-6">
    {{-- Breadcrumb / Back --}}
    <div class="flex items-center justify-between flex-wrap gap-4">
        <a href="{{ route('requests.index') }}"
           class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-textMuted hover:text-wood-800 transition group">
            <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Quay lại danh sách
        </a>

        @if($user['role'] === 'student' && $request->student_id === $user['id'])
            <a href="{{ route('requests.copy', $request) }}"
               class="inline-flex items-center gap-2 rounded-xl border border-borderWarm bg-white px-4 py-2 text-xs font-semibold text-ink-900 hover:bg-paper transition shadow-warm">
                <svg class="w-3.5 h-3.5 text-gold-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                Sao chép yêu cầu
            </a>
        @endif
    </div>

    {{-- Header Card --}}
    <div class="bg-white rounded-2xl border border-borderWarm p-6 sm:p-7 shadow-warm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-2">
                <div class="flex items-center gap-2.5 flex-wrap">
                    <span class="font-mono text-sm font-bold text-gold-700 bg-gold-50/80 px-3 py-1 rounded-lg border border-gold-200/60 shadow-xs">{{ $request->code }}</span>
                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $statusColors[$statusVal] ?? 'bg-paper text-ink-900 border-borderWarm' }}">
                        {{ $statusLabels[$statusVal] ?? $statusVal }}
                    </span>
                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $priorityColors[$priorityVal] ?? 'bg-paper text-ink-900 border-borderWarm' }}">
                        Ưu tiên: {{ $priorityLabels[$priorityVal] ?? $priorityVal }}
                    </span>
                </div>
                <h2 class="text-2xl sm:text-3xl font-serif font-bold text-ink-900 tracking-tight leading-snug">{{ $request->title }}</h2>
            </div>
            <div class="text-right text-xs text-textMuted shrink-0">
                <p>Khởi tạo: <span class="font-medium text-ink-900">{{ $request->created_at?->format('d/m/Y H:i') }}</span></p>
                <p class="mt-1">Cập nhật: <span class="font-medium text-ink-900">{{ $request->updated_at?->format('d/m/Y H:i') }}</span></p>
            </div>
        </div>

        {{-- Metadata Row --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-6 pt-5 border-t border-borderWarm/70 text-sm">
            <div class="bg-paper/50 rounded-xl p-3 border border-borderWarm/50">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-textMuted mb-0.5">Phòng ban</p>
                <p class="font-semibold text-ink-900 truncate">{{ $departments[$request->department_id] ?? '#'.$request->department_id }}</p>
            </div>
            <div class="bg-paper/50 rounded-xl p-3 border border-borderWarm/50">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-textMuted mb-0.5">Loại hỗ trợ</p>
                <p class="font-semibold text-ink-900 truncate">{{ $supportTypes[$request->support_type_id]['name'] ?? '#'.$request->support_type_id }}</p>
            </div>
            <div class="bg-paper/50 rounded-xl p-3 border border-borderWarm/50">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-textMuted mb-0.5">Sinh viên yêu cầu</p>
                <p class="font-semibold text-ink-900">Mã SV #{{ $request->student_id }}</p>
            </div>
            <div class="bg-paper/50 rounded-xl p-3 border border-borderWarm/50">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-textMuted mb-0.5">Cán bộ phụ trách</p>
                <p class="font-semibold text-ink-900 truncate">
                    @php
                        $staffNames = [21 => 'Nguyễn Văn A', 22 => 'Phạm Minh D'];
                    @endphp
                    {{ $request->assigned_to ? ($staffNames[$request->assigned_to] ?? '#'.$request->assigned_to) : 'Chưa gán' }}
                </p>
            </div>
        </div>
    </div>

    {{-- SLA Banner --}}
    @if($request->sla_deadline_at)
        <div class="rounded-2xl border px-5 py-4 text-sm shadow-warm flex items-center justify-between flex-wrap gap-3 {{ $slaFlag === 'breached' ? 'border-rose-200 bg-rose-50 text-rose-900' : ($slaFlag === 'warning' ? 'border-amber-200 bg-amber-50 text-amber-900' : 'border-borderWarm bg-white text-ink-900') }}">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 {{ $slaFlag === 'breached' ? 'bg-rose-100 text-rose-700' : ($slaFlag === 'warning' ? 'bg-amber-100 text-amber-700' : 'bg-gold-50 text-gold-700') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <strong>{{ $slaFlag === 'breached' ? 'Đã quá hạn SLA' : ($slaFlag === 'warning' ? 'Sắp quá hạn SLA' : 'Hạn xử lý SLA') }}</strong>
                    <span class="ml-1.5 font-medium">{{ $request->sla_deadline_at->format('d/m/Y H:i') }}</span>
                </div>
            </div>
            @if($slaFlag !== 'breached' && $request->slaRemainingHours() !== null)
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-white/80 border border-current shadow-xs">
                    Còn {{ max(0, $request->slaRemainingHours()) }} giờ
                </span>
            @endif
        </div>
    @endif

    {{-- Reopen / Rework Actions --}}
    @if($canReopen)
        <form method="POST" action="{{ route('requests.update-status', $request) }}">
            @csrf
            @method('PUT')
            <button type="submit" name="status" value="in_progress"
                    class="inline-flex items-center gap-2 rounded-xl border border-wood-300 bg-wood-50 px-5 py-3 text-sm font-semibold text-wood-900 hover:bg-wood-100 transition shadow-warm">
                <svg class="w-4 h-4 text-wood-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                {{ $isStudentOwner ? 'Yêu cầu xử lý lại' : 'Mở lại yêu cầu' }}
            </button>
        </form>
    @endif

    @if($canStudentRework)
        <form method="POST" action="{{ route('requests.update-status', $request) }}">
            @csrf
            @method('PUT')
            <button type="submit" name="status" value="in_progress"
                    class="inline-flex items-center gap-2 rounded-xl border border-amber-300 bg-amber-50 px-5 py-3 text-sm font-semibold text-amber-900 hover:bg-amber-100 transition shadow-warm">
                <svg class="w-4 h-4 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Yêu cầu xử lý lại
            </button>
        </form>
    @endif

    {{-- Rating Section --}}
    @if($canRate || $request->rating)
        <section class="bg-white rounded-2xl border border-borderWarm p-6 sm:p-7 shadow-warm" aria-labelledby="request-rating-title">
            <div class="flex items-center gap-2 mb-2">
                <span class="text-gold-500 text-lg">★</span>
                <h3 id="request-rating-title" class="font-serif text-lg font-bold text-ink-900">Đánh giá kết quả hỗ trợ</h3>
            </div>
            @if($canRate)
                <p class="text-xs text-textMuted mb-4">Yêu cầu đã hoàn tất. Đánh giá của bạn giúp nâng cao chất lượng phục vụ của nhà trường.</p>
                <form method="POST" action="{{ route('requests.rating.store', $request) }}" class="space-y-4">
                    @csrf
                    <fieldset>
                        <legend class="text-xs font-semibold uppercase tracking-wider text-textMuted mb-2">Mức độ hài lòng</legend>
                        <div class="flex items-center gap-1.5" x-data="{ rating: {{ (int) old('rating', 0) }} }" role="radiogroup" aria-label="Chọn mức độ hài lòng từ 1 đến 5 sao">
                            @foreach([1 => 'Rất chưa hài lòng', 2 => 'Chưa hài lòng', 3 => 'Bình thường', 4 => 'Hài lòng', 5 => 'Rất hài lòng'] as $score => $label)
                                <label class="cursor-pointer rounded-lg p-1 focus-within:outline-none focus-within:ring-2 focus-within:ring-gold-500" title="{{ $label }}">
                                    <input class="peer sr-only" type="radio" name="rating" value="{{ $score }}" x-model.number="rating" @checked((int) old('rating') === $score) aria-label="{{ $score }} sao: {{ $label }}" required>
                                    <span class="block text-4xl leading-none transition-colors select-none"
                                          aria-hidden="true"
                                          :class="rating >= {{ $score }} ? 'text-gold-500' : 'text-stone-300 hover:text-gold-300'"
                                          x-text="rating >= {{ $score }} ? '★' : '☆'">☆</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="mt-1 text-[11px] text-textMuted">Chọn từ 1 đến 5 sao</p>
                        @error('rating')<p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>@enderror
                    </fieldset>
                    <div>
                        <label for="rating-comment" class="block text-xs font-semibold uppercase tracking-wider text-textMuted mb-1.5">
                            Nhận xét <span class="font-normal lowercase text-stone-400">(không bắt buộc)</span>
                        </label>
                        <textarea id="rating-comment" name="rating_comment" rows="3" maxlength="1000" placeholder="Chia sẻ thêm về trải nghiệm hỗ trợ của bạn..."
                                  class="w-full bg-paper/60 rounded-xl border border-borderWarm px-4 py-3 text-sm text-ink-900 focus:outline-none focus:ring-2 focus:ring-gold-500/30 focus:border-gold-500 focus:bg-white transition resize-none">{{ old('rating_comment') }}</textarea>
                        @error('rating_comment')<p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-wood-800 px-5 py-2.5 text-xs font-semibold uppercase tracking-wider text-white transition hover:bg-wood-900 shadow-warm">
                        Gửi đánh giá
                    </button>
                </form>
            @else
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <span class="font-bold text-2xl text-gold-500 tracking-wider" aria-label="{{ $request->rating }} trên 5 sao">{{ str_repeat('★', (int) $request->rating) }}{{ str_repeat('☆', 5 - (int) $request->rating) }}</span>
                    <span class="text-sm font-bold text-ink-900 bg-paper px-2.5 py-1 rounded-lg border border-borderWarm">{{ $request->rating }} / 5 sao</span>
                    @if($request->rated_at)<span class="text-xs text-textMuted">Đã đánh giá lúc {{ $request->rated_at->format('d/m/Y') }}</span>@endif
                </div>
                @if($request->rating_comment)
                    <p class="mt-3 text-sm text-ink-800 bg-paper/70 rounded-xl p-3.5 border border-borderWarm whitespace-pre-wrap leading-relaxed">{{ $request->rating_comment }}</p>
                @endif
            @endif
        </section>
    @endif

    {{-- Main Grid: Left (Content + Actions + Comments), Right (Timeline) --}}
    <div class="grid md:grid-cols-3 gap-6">
        {{-- Left 2 Cols: Content + Actions --}}
        <div class="md:col-span-2 space-y-6">
            {{-- Ticket Content Card --}}
            <div class="bg-white rounded-2xl border border-borderWarm p-6 sm:p-7 shadow-warm">
                <div class="flex items-center gap-2 mb-4 border-b border-borderWarm/70 pb-3">
                    <svg class="w-4 h-4 text-gold-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <h3 class="font-serif text-lg font-bold text-ink-900 uppercase tracking-wide">Nội dung yêu cầu</h3>
                </div>
                <p class="text-ink-900 whitespace-pre-wrap leading-relaxed font-sans text-sm sm:text-base">{{ $request->content }}</p>

                @if($request->cancelled_reason)
                    <div class="mt-5 p-4 bg-rose-50 border border-rose-200 rounded-xl text-sm text-rose-800">
                        <strong>Lý do hủy:</strong> {{ $request->cancelled_reason }}
                    </div>
                @endif

                @if($request->attachments && $request->attachments->isNotEmpty())
                    <div class="mt-6 pt-5 border-t border-borderWarm/70">
                        <h4 class="text-xs font-semibold uppercase tracking-wider text-textMuted mb-3">Ảnh đính kèm ({{ $request->attachments->count() }})</h4>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            @foreach($request->attachments as $att)
                                <a href="{{ $att->url() }}" target="_blank" rel="noopener"
                                   class="group block rounded-xl overflow-hidden border border-borderWarm bg-paper/60 hover:border-gold-500 transition shadow-xs">
                                    <img src="{{ $att->url() }}" alt="{{ $att->original_name }}"
                                         class="w-full h-32 object-cover group-hover:scale-105 transition duration-300">
                                    <p class="px-2.5 py-1.5 text-[11px] font-medium text-textMuted truncate group-hover:text-ink-900">{{ $att->original_name }}</p>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Actions Card (if not terminal) --}}
            @if(!$isTerminal)
            <div class="bg-white rounded-2xl border border-borderWarm p-6 sm:p-7 shadow-warm space-y-6">
                <div class="flex items-center gap-2 border-b border-borderWarm/70 pb-3">
                    <svg class="w-4 h-4 text-gold-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <h3 class="font-serif text-lg font-bold text-ink-900 uppercase tracking-wide">Thao tác</h3>
                </div>

                {{-- Bắt buộc gán trước khi đổi trạng thái --}}
                @if($needsAssign)
                    <div class="rounded-xl bg-amber-50 border border-amber-200 p-4 text-sm text-amber-900 flex items-start gap-3">
                        <svg class="w-5 h-5 text-amber-700 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <div>
                            <strong>Chưa gán cán bộ.</strong> Trưởng phòng / admin phải gán cán bộ trước khi đổi trạng thái.
                        </div>
                    </div>
                @endif

                @if($statusVal === 'resolved' && $user['role'] === 'staff' && ! $hasStudentReply)
                    <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-900 flex items-start gap-3">
                        <svg class="w-5 h-5 text-emerald-700 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div>
                            Đã gửi kết quả xử lý. Chờ sinh viên phản hồi trước khi đóng yêu cầu.
                        </div>
                    </div>
                @endif

                {{-- Change status (staff/admin) --}}
                @if($canChangeStatus && count($allowedNext) > 0 && ! $needsAssign)
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-textMuted mb-2">Đổi trạng thái xử lý</p>
                        <form method="POST" action="{{ route('requests.update-status', $request) }}" class="space-y-3">
                            @csrf
                            @method('PUT')
                            <textarea name="note" rows="2" placeholder="Ghi chú cập nhật tiến độ (tùy chọn)..."
                                      class="w-full bg-paper/60 border border-borderWarm rounded-xl px-4 py-2.5 text-sm text-ink-900 focus:outline-none focus:ring-2 focus:ring-gold-500/30 focus:border-gold-500 focus:bg-white transition resize-none"></textarea>
                            <div class="flex flex-wrap gap-2.5">
                                @foreach($allowedNext as $next)
                                    <button type="submit" name="status" value="{{ $next }}"
                                            class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-semibold tracking-wide uppercase transition border shadow-xs
                                                   {{ $next === 'cancelled'
                                                       ? 'bg-rose-50 text-rose-800 hover:bg-rose-100 border-rose-200'
                                                       : ($next === 'resolved'
                                                           ? 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border-emerald-200'
                                                           : 'bg-paper text-wood-900 hover:bg-wood-50 border-borderWarm hover:border-wood-400') }}">
                                        {{ $statusLabels[$next] ?? $next }}
                                    </button>
                                @endforeach
                            </div>
                        </form>
                    </div>
                @endif

                {{-- Transfer department (admin) --}}
                @if($canTransfer)
                    <div class="pt-5 border-t border-borderWarm/70">
                        <p class="text-xs font-semibold uppercase tracking-wider text-textMuted mb-2">Chuyển phòng ban điều phối</p>
                        <form method="POST" action="{{ route('requests.transfer', $request) }}" class="space-y-3">
                            @csrf
                            @method('PUT')
                            <div class="relative">
                                <select name="department_id" id="transfer-department" required onchange="filterTransferSupportTypes()"
                                        class="w-full appearance-none bg-paper/60 border border-borderWarm rounded-xl px-4 py-2.5 text-sm text-ink-900 focus:outline-none focus:ring-2 focus:ring-gold-500/30 focus:border-gold-500 font-medium pr-10">
                                    <option value="">-- Chọn phòng ban nhận --</option>
                                    @foreach($departments as $departmentId => $departmentName)
                                        @if($departmentId !== $request->department_id)
                                            <option value="{{ $departmentId }}">{{ $departmentName }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3.5 text-textMuted">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>
                            <div class="flex gap-2.5">
                                <div class="relative flex-1">
                                    <select name="support_type_id" id="transfer-support-type" required
                                            class="w-full appearance-none bg-paper/60 border border-borderWarm rounded-xl px-4 py-2.5 text-sm text-ink-900 focus:outline-none focus:ring-2 focus:ring-gold-500/30 focus:border-gold-500 font-medium pr-10">
                                        <option value="">-- Chọn loại hỗ trợ --</option>
                                        @foreach($supportTypes as $supportTypeId => $supportType)
                                            <option value="{{ $supportTypeId }}" data-dept="{{ $supportType['department_id'] }}">{{ $supportType['name'] }}</option>
                                        @endforeach
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3.5 text-textMuted">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                </div>
                                <button type="submit" class="rounded-xl bg-wood-800 px-5 py-2.5 text-xs font-semibold uppercase tracking-wider text-white hover:bg-wood-900 transition shadow-warm shrink-0">
                                    Chuyển
                                </button>
                            </div>
                        </form>
                    </div>
                @endif

                {{-- Assign staff --}}
                @if($canAssign)
                    <div class="{{ $canChangeStatus ? 'pt-5 border-t border-borderWarm/70' : '' }}">
                        <p class="text-xs font-semibold uppercase tracking-wider text-textMuted mb-1">
                            Gán cán bộ xử lý @if($needsAssign)<span class="text-amber-700 font-bold lowercase">(bắt buộc trước)</span>@endif
                        </p>
                        <p class="text-[11px] text-textMuted mb-3">Module 1 chưa sẵn sàng — dùng danh sách cán bộ mẫu để phân công</p>
                        <form method="POST" action="{{ route('requests.assign', $request) }}" class="flex gap-2.5">
                            @csrf
                            @method('PUT')
                            <div class="relative flex-1">
                                <select name="assigned_to" required
                                        class="w-full appearance-none bg-paper/60 border border-borderWarm rounded-xl px-4 py-2.5 text-sm text-ink-900 focus:outline-none focus:ring-2 focus:ring-gold-500/30 focus:border-gold-500 font-medium pr-10">
                                    <option value="">-- Chọn cán bộ --</option>
                                    @foreach($demoUsers as $staff)
                                        @if($staff['role'] === 'staff' && in_array($staff['id'], config("master_data.staff_by_department.{$request->department_id}", []), true))
                                            <option value="{{ $staff['id'] }}" @selected($request->assigned_to === $staff['id'])>#{{ $staff['id'] }} — {{ $staff['full_name'] }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3.5 text-textMuted">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>
                            <button type="submit"
                                    class="px-5 py-2.5 bg-wood-800 text-white text-xs font-semibold uppercase tracking-wider rounded-xl hover:bg-wood-900 transition shadow-warm shrink-0">
                                Gán
                            </button>
                        </form>
                    </div>
                @endif

                {{-- Cancel Ticket --}}
                @if($canCancel && in_array('cancelled', $allowedNext, true))
                    <div class="pt-5 border-t border-borderWarm/70" x-data="{ open: false }">
                        <button type="button" @click="open = !open" class="text-xs font-semibold uppercase tracking-wider text-rose-700 hover:text-rose-900 transition flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Hủy yêu cầu này...
                        </button>
                        <div x-show="open" x-cloak class="mt-3 bg-rose-50/60 p-4 rounded-xl border border-rose-200">
                            <form method="POST" action="{{ route('requests.cancel', $request) }}">
                                @csrf
                                @method('PUT')
                                <textarea name="reason" rows="2" placeholder="Nhập lý do hủy yêu cầu..."
                                          class="w-full bg-white border border-rose-200 rounded-xl px-3.5 py-2 text-sm mb-2.5 focus:outline-none focus:ring-2 focus:ring-rose-400 resize-none"></textarea>
                                <div class="flex gap-2">
                                    <button type="submit" class="px-4 py-2 bg-rose-700 text-white text-xs font-semibold uppercase tracking-wider rounded-lg hover:bg-rose-800 transition">
                                        Xác nhận hủy
                                    </button>
                                    <button type="button" @click="open = false" class="px-4 py-2 text-xs font-medium text-textMuted hover:bg-white rounded-lg border border-borderWarm transition">
                                        Đóng
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif

                {{-- Edit / Delete (when new) --}}
                @if($canEdit)
                    <div class="pt-5 border-t border-borderWarm/70">
                        <p class="text-xs text-textMuted mb-3">Yêu cầu đang <strong>Mới tạo</strong> — bạn có thể hiệu chỉnh nội dung hoặc xóa bỏ.</p>
                        <div class="flex flex-wrap gap-2.5">
                            <a href="{{ route('requests.edit', $request) }}"
                               class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-semibold uppercase tracking-wider bg-wood-800 text-white hover:bg-wood-900 transition shadow-warm">
                                <svg class="w-3.5 h-3.5 text-gold-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Sửa yêu cầu
                            </a>
                            <form method="POST" action="{{ route('requests.destroy', $request) }}"
                                  onsubmit="return confirm('Bạn chắc chắn muốn xóa yêu cầu này?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-semibold uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Xóa yêu cầu
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
            @endif

            @if($statusVal === 'waiting_info' && $user['role'] === 'student' && $request->student_id === $user['id'])
                <div class="rounded-2xl border border-amber-300 bg-amber-50/80 p-5 text-sm text-amber-950 shadow-warm flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-700 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="leading-relaxed">
                        Cán bộ đang chờ bạn bổ sung thông tin. Vui lòng phản hồi chi tiết bên dưới qua mục trao đổi hoặc theo dõi các ghi chú từ cán bộ trong lịch sử trạng thái.
                    </p>
                </div>
            @endif
        </div>

        {{-- Right Column: Status Timeline --}}
        <div class="bg-white rounded-2xl border border-borderWarm p-6 sm:p-7 shadow-warm h-fit">
            <div class="flex items-center gap-2 border-b border-borderWarm/70 pb-3 mb-5">
                <svg class="w-4 h-4 text-gold-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <h3 class="font-serif text-lg font-bold text-ink-900 uppercase tracking-wide">Lịch sử trạng thái</h3>
            </div>
            @if($histories->isEmpty())
                <p class="text-xs text-textMuted italic">Chưa có lịch sử cập nhật</p>
            @else
                <ol class="relative border-l-2 border-borderWarm space-y-6 ml-2.5">
                    @foreach($histories as $h)
                        <li class="ml-5">
                            <div class="absolute -left-[9px] mt-1.5 w-4 h-4 rounded-full bg-gold-500 border-2 border-white shadow-xs"></div>
                            <div class="flex items-center gap-2 mb-1 flex-wrap">
                                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-semibold {{ $statusColors[$h->to_status] ?? 'bg-paper text-ink-900 border-borderWarm' }}">
                                    {{ $statusLabels[$h->to_status] ?? $h->to_status }}
                                </span>
                                @if($h->from_status)
                                    <span class="text-[11px] text-textMuted">từ {{ $statusLabels[$h->from_status] ?? $h->from_status }}</span>
                                @endif
                            </div>
                            <p class="text-[11px] text-textMuted mt-0.5">
                                {{ \Carbon\Carbon::parse($h->created_at)->format('d/m/Y H:i') }}
                                · <span class="text-ink-800 font-medium">{{ $h->changed_by === null ? 'Hệ thống tự động' : 'User #'.$h->changed_by }}</span>
                            </p>
                            @if($h->note)
                                <p class="text-xs text-ink-900 mt-2 bg-paper/80 rounded-xl p-2.5 border border-borderWarm/80 leading-relaxed">{{ $h->note }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
    </div>

    {{-- Comment Thread --}}
    <div class="bg-white rounded-2xl border border-borderWarm p-6 sm:p-8 shadow-warm">
        <div class="flex items-center justify-between mb-6 border-b border-borderWarm/70 pb-4">
            <h3 class="font-serif text-2xl font-bold text-ink-900 flex items-center gap-2.5">
                <svg class="w-5 h-5 text-gold-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                Trao đổi
                <span class="inline-flex items-center justify-center bg-gold-50 border border-gold-200/80 text-gold-800 text-xs font-bold font-mono rounded-full px-2.5 py-0.5">
                    {{ $comments->total() }}
                </span>
            </h3>
        </div>

        {{-- Form nhập comment --}}
        @if(!$isTerminal && ($user['role'] !== 'student' || $canStudentReply))
        <form method="POST" action="{{ route('requests.comments.store', $request) }}" enctype="multipart/form-data" class="mb-8">
            @csrf
            <div class="flex items-start gap-3.5">
                {{-- Avatar --}}
                <div class="shrink-0 mt-1">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xs font-bold shadow-warm border border-borderWarm
                        {{ $user['role'] === 'admin'
                            ? 'bg-wood-800 text-white'
                            : ($user['role'] === 'staff' ? 'bg-gold-500 text-white' : 'bg-paper text-wood-900') }}">
                        {{ mb_substr($user['full_name'], 0, 1) }}
                    </div>
                </div>

                <div class="flex-1 space-y-3">
                    @if(in_array($user['role'], ['staff', 'department_head', 'admin'], true))
                        <div class="relative">
                            <select aria-label="Chọn câu trả lời mẫu" onchange="document.getElementById('comment-body').value = this.value; this.value = ''"
                                    class="w-full appearance-none border border-borderWarm rounded-xl bg-paper/60 px-4 py-2.5 text-xs text-textMuted focus:outline-none focus:ring-2 focus:ring-gold-500/30 font-medium pr-10">
                                <option value="">Chèn câu trả lời mẫu theo tiêu chuẩn...</option>
                                @foreach($replyTemplates as $templateKey => $template)
                                    <option value="{{ $template }}">{{ ['received' => 'Đã tiếp nhận', 'need_info' => 'Yêu cầu bổ sung', 'in_progress' => 'Đang xử lý', 'resolved' => 'Đã xử lý'][$templateKey] ?? $templateKey }}</option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3.5 text-textMuted">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </div>
                    @endif
                    <textarea id="comment-body" name="body" rows="3" required
                              placeholder="Nhập nội dung trao đổi..."
                              class="w-full bg-paper/60 border border-borderWarm rounded-xl p-4 text-sm text-ink-900 focus:outline-none focus:ring-2 focus:ring-gold-500/30 focus:border-gold-500 focus:bg-white resize-none transition leading-relaxed shadow-sm"
                    >{{ old('body') }}</textarea>

                    <div class="flex flex-wrap items-center gap-3">
                        {{-- Upload file --}}
                        <label class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-textMuted bg-paper/60 border border-borderWarm hover:bg-white hover:text-ink-900 cursor-pointer transition shadow-xs">
                            <svg class="w-4 h-4 text-gold-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                            Đính kèm tệp tin
                            <input type="file" name="attachments[]" multiple class="hidden" id="comment-files" onchange="updateFileLabel(this)">
                        </label>
                        <span id="file-count" class="text-xs text-textMuted italic font-medium"></span>

                        {{-- Checkbox nội bộ — chỉ staff/head/admin --}}
                        @if(in_array($user['role'], ['staff', 'department_head', 'admin']))
                        <label class="inline-flex items-center gap-1.5 text-xs font-medium text-amber-900 bg-amber-50 px-3 py-1.5 rounded-xl border border-amber-200 cursor-pointer ml-auto">
                            <input type="checkbox" name="is_internal" value="1"
                                   class="rounded border-amber-300 text-gold-600 focus:ring-gold-500 w-3.5 h-3.5">
                            <svg class="w-3.5 h-3.5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            Nội bộ (SV không thấy)
                        </label>
                        @endif

                        <button type="submit"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-semibold uppercase tracking-wider bg-wood-800 text-white hover:bg-wood-900 transition shadow-warm ml-auto">
                            <svg class="w-3.5 h-3.5 text-gold-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                            Gửi
                        </button>
                    </div>
                </div>
            </div>
        </form>
        @else
        <div class="mb-8 rounded-2xl bg-paper/60 border border-borderWarm px-5 py-4 text-xs font-medium text-textMuted">
            @if($isTerminal)
                Yêu cầu đã {{ $statusVal === 'closed' ? 'đóng' : 'hủy' }} — không thể thêm trao đổi mới.
            @else
                Bạn có thể phản hồi khi yêu cầu đang chờ bổ sung hoặc chờ xác nhận kết quả.
            @endif
        </div>
        @endif

        {{-- Danh sách comment --}}
        @if($comments->isEmpty())
            <div class="text-center py-10 border border-dashed border-borderWarm rounded-2xl bg-paper/30">
                <svg class="w-12 h-12 mx-auto text-stone-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                <p class="text-sm font-semibold text-ink-900">Chưa có trao đổi nào</p>
                <p class="text-xs text-textMuted mt-1">Bắt đầu cuộc trao đổi đầu tiên để giải đáp thắc mắc</p>
            </div>
        @else
            <div class="space-y-5">
                @foreach($comments as $comment)
                    @php
                        $isOwn = $comment->user_id === $user['id'];
                        $roleColors = [
                            'student' => 'bg-wood-100 text-wood-900 border-wood-200',
                            'staff' => 'bg-gold-100 text-gold-900 border-gold-200',
                            'department_head' => 'bg-amber-100 text-amber-900 border-amber-200',
                            'admin' => 'bg-stone-800 text-white border-stone-800',
                        ];
                        $roleBadge = [
                            'student' => 'Sinh viên',
                            'staff' => 'Cán bộ',
                            'department_head' => 'Trưởng phòng',
                            'admin' => 'Admin',
                        ];
                    @endphp
                    <div class="flex gap-3.5 group {{ $comment->is_internal ? 'relative' : '' }}">
                        {{-- Avatar --}}
                        <div class="shrink-0 mt-0.5">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-bold border border-borderWarm shadow-xs
                                {{ $comment->user_role === 'admin'
                                    ? 'bg-wood-900 text-white'
                                    : ($comment->user_role === 'staff' ? 'bg-gold-500 text-white' : 'bg-paper text-wood-900') }}">
                                {{ mb_substr($comment->user_name ?? '?', 0, 1) }}
                            </div>
                        </div>

                        {{-- Nội dung --}}
                        <div class="flex-1 min-w-0">
                            <div class="rounded-2xl p-4 sm:p-5 transition shadow-xs {{ $comment->is_internal
                                ? 'bg-amber-50/80 border border-amber-200/80 shadow-warm'
                                : ($isOwn ? 'bg-wood-50/50 border border-wood-200/60' : 'bg-paper/70 border border-borderWarm') }}">

                                {{-- Header --}}
                                <div class="flex items-center gap-2 mb-2 flex-wrap">
                                    <span class="text-sm font-bold text-ink-900">{{ $comment->user_name ?? 'User #'.$comment->user_id }}</span>
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold border {{ $roleColors[$comment->user_role] ?? 'bg-paper text-textMuted border-borderWarm' }}">
                                        {{ $roleBadge[$comment->user_role] ?? $comment->user_role }}
                                    </span>
                                    @if($comment->is_internal)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 text-amber-900 border border-amber-300 px-2 py-0.5 text-[10px] font-semibold">
                                            <svg class="w-3 h-3 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                            Nội bộ
                                        </span>
                                    @endif
                                    <span class="text-[11px] text-textMuted ml-auto">{{ $comment->created_at->format('d/m/Y H:i') }}</span>
                                </div>

                                {{-- Body --}}
                                <p class="text-sm text-ink-900 whitespace-pre-wrap leading-relaxed font-sans">{{ $comment->body }}</p>

                                {{-- File đính kèm --}}
                                @if($comment->attachments && $comment->attachments->isNotEmpty())
                                    <div class="mt-3.5 pt-3 border-t {{ $comment->is_internal ? 'border-amber-200/70' : 'border-borderWarm/70' }}">
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($comment->attachments as $att)
                                                <a href="{{ route('requests.comments.attachments.preview', ['supportRequest' => $request, 'comment' => $comment, 'commentAttachment' => $att]) }}" target="_blank" rel="noopener"
                                                   class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-semibold bg-white border border-borderWarm text-ink-900 hover:border-gold-500 hover:text-gold-700 transition shadow-xs">
                                                    @if($att->isImage())
                                                        <svg class="w-3.5 h-3.5 text-gold-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                    @else
                                                        <svg class="w-3.5 h-3.5 text-wood-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                    @endif
                                                    <span class="max-w-[160px] truncate">{{ $att->original_name }}</span>
                                                    <span class="text-[10px] text-textMuted font-mono">({{ number_format($att->size / 1024, 0) }}KB)</span>
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
                                        <button type="submit" class="text-[11px] font-semibold text-rose-600 hover:text-rose-800 transition">
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
                <div class="mt-6 pt-5 border-t border-borderWarm/70">
                    {{ $comments->links() }}
                </div>
            @endif
        @endif
    </div>
</div>

<script>
function updateFileLabel(input) {
    const count = input.files.length;
    document.getElementById('file-count').textContent =
        count > 0 ? count + ' tệp tin đã chọn' : '';
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
