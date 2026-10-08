@extends('layouts.app')

@section('title', $request->code . ' — Chi Tiết Yêu Cầu Hỗ Trợ')

@section('content')
@php
    $statusLabels = [
        'new' => 'Mới tạo',
        'received' => 'Đã tiếp nhận',
        'in_progress' => 'Đang xử lý',
        'waiting_info' => 'Chờ SV bổ sung',
        'resolved' => 'Chờ SV xác nhận',
        'closed' => 'Đã hoàn tất',
        'cancelled' => 'Đã hủy',
    ];
    $statusColors = [
        'new' => 'bg-sky-50 text-sky-700 border-sky-200',
        'received' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'in_progress' => 'bg-blue-50 text-blue-700 border-blue-200',
        'waiting_info' => 'bg-amber-50 text-amber-700 border-amber-200',
        'resolved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'closed' => 'bg-slate-100 text-slate-700 border-slate-200',
        'cancelled' => 'bg-rose-50 text-rose-700 border-rose-200',
    ];
    $priorityLabels = [
        'low' => 'Thấp',
        'normal' => 'Bình thường',
        'high' => 'Cao',
        'urgent' => 'Khẩn cấp',
    ];
    $priorityColors = [
        'low' => 'bg-slate-100 text-slate-600 border-slate-200',
        'normal' => 'bg-blue-50 text-blue-700 border-blue-200',
        'high' => 'bg-amber-50 text-amber-700 border-amber-200',
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
    @include('requests.partials.next-step')
    {{-- Breadcrumb / Back --}}
    <div class="flex items-center justify-between flex-wrap gap-4">
        <a href="{{ route('requests.index') }}"
           class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-500 hover:text-primary-600 transition group">
            <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Quay lại danh sách
        </a>

        @if($user['role'] === 'student' && $request->student_id === $user['id'])
            <a href="{{ route('requests.copy', $request) }}"
               class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-primary-600 transition shadow-subtle">
                <svg class="w-3.5 h-3.5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
                Sao chép yêu cầu
            </a>
        @endif
    </div>

    {{-- Header Card --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-7 shadow-subtle">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-2">
                <div class="flex items-center gap-2.5 flex-wrap">
                    <span class="font-mono text-sm font-bold text-primary-700 bg-primary-50 px-3 py-1 rounded-lg border border-primary-200 shadow-xs">
                        {{ $request->code }}
                    </span>
                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $statusColors[$statusVal] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                        {{ $statusLabels[$statusVal] ?? $statusVal }}
                    </span>
                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $priorityColors[$priorityVal] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                        Ưu tiên: {{ $priorityLabels[$priorityVal] ?? $priorityVal }}
                    </span>
                </div>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-snug">
                    {{ $request->title }}
                </h2>
            </div>
            <div class="text-right text-xs text-slate-500 shrink-0">
                <p>Khởi tạo: <span class="font-semibold text-slate-800">{{ $request->created_at?->format('d/m/Y H:i') }}</span></p>
                <p class="mt-1">Cập nhật: <span class="font-semibold text-slate-800">{{ $request->updated_at?->format('d/m/Y H:i') }}</span></p>
            </div>
        </div>

        {{-- Metadata Row --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5 mt-6 pt-5 border-t border-slate-100 text-sm">
            <div class="bg-slate-50/80 rounded-xl p-3.5 border border-slate-200/80">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-0.5">Phòng ban tiếp nhận</p>
                <p class="font-bold text-slate-900 truncate">{{ $departments[$request->department_id] ?? '#'.$request->department_id }}</p>
            </div>
            <div class="bg-slate-50/80 rounded-xl p-3.5 border border-slate-200/80">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-0.5">Loại hỗ trợ</p>
                <p class="font-bold text-slate-900 truncate">{{ $supportTypes[$request->support_type_id]['name'] ?? '#'.$request->support_type_id }}</p>
            </div>
            <div class="bg-slate-50/80 rounded-xl p-3.5 border border-slate-200/80">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-0.5">Sinh viên yêu cầu</p>
                <p class="font-bold text-slate-900 truncate">
                    @if(isset($allUsers[$request->student_id]))
                        {{ $allUsers[$request->student_id]['full_name'] }}
                        <span class="text-xs text-slate-500 font-mono font-normal">(#{{ $request->student_id }})</span>
                    @else
                        Mã SV #{{ $request->student_id }}
                    @endif
                </p>
            </div>
            <div class="bg-slate-50/80 rounded-xl p-3.5 border border-slate-200/80">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-0.5">Cán bộ phụ trách</p>
                <div class="font-bold text-slate-900 truncate">
                    @if($request->assigned_to)
                        @php
                            $stName = $staffNames[$request->assigned_to] ?? ($request->assigned_staff_name ?? ('Cán bộ #' . $request->assigned_to));
                        @endphp
                        <div class="inline-flex items-center gap-1.5" title="Mã cán bộ: #{{ $request->assigned_to }}">
                            <span class="w-5 h-5 rounded-full bg-primary-100 text-primary-700 text-[10px] font-extrabold inline-flex items-center justify-center shrink-0 border border-primary-200">
                                {{ mb_substr($stName, 0, 1) }}
                            </span>
                            <span class="truncate">{{ $stName }}</span>
                            <span class="text-xs text-slate-400 font-mono font-normal">(#{{ $request->assigned_to }})</span>
                        </div>
                    @else
                        <span class="text-slate-400 italic font-normal">Chưa phân công</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- SLA Banner --}}
    @if($request->sla_deadline_at)
        <div class="rounded-2xl border px-5 py-4 text-sm shadow-subtle flex items-center justify-between flex-wrap gap-3 {{ $slaFlag === 'breached' ? 'border-rose-200 bg-rose-50 text-rose-900' : ($slaFlag === 'warning' ? 'border-amber-200 bg-amber-50 text-amber-900' : 'border-slate-200 bg-white text-slate-800') }}">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 {{ $slaFlag === 'breached' ? 'bg-rose-100 text-rose-700' : ($slaFlag === 'warning' ? 'bg-amber-100 text-amber-700' : 'bg-primary-50 text-primary-600') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <strong>{{ $slaFlag === 'breached' ? 'Đã quá thời hạn SLA' : ($slaFlag === 'warning' ? 'Cảnh báo: Sắp quá hạn SLA' : 'Thời hạn cam kết giải quyết (SLA)') }}</strong>
                    <span class="ml-1.5 font-semibold">{{ $request->sla_deadline_at->format('d/m/Y H:i') }}</span>
                </div>
            </div>
            @if($slaFlag !== 'breached' && $request->slaRemainingHours() !== null)
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-white/90 border border-current shadow-xs">
                    Còn {{ max(0, $request->slaRemainingHours()) }} giờ làm việc
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
                    class="inline-flex items-center gap-2 rounded-xl border border-primary-300 bg-primary-50 px-5 py-3 text-sm font-bold text-primary-800 hover:bg-primary-100 transition shadow-subtle">
                <svg class="w-4 h-4 text-primary-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                {{ $isStudentOwner ? 'Yêu cầu xử lý lại' : 'Mở lại yêu cầu' }}
            </button>
        </form>
    @endif

    @if($canStudentRework)
        <form method="POST" action="{{ route('requests.update-status', $request) }}">
            @csrf
            @method('PUT')
            <button type="submit" name="status" value="in_progress"
                    class="inline-flex items-center gap-2 rounded-xl border border-amber-300 bg-amber-50 px-5 py-3 text-sm font-bold text-amber-900 hover:bg-amber-100 transition shadow-subtle">
                <svg class="w-4 h-4 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Yêu cầu xử lý lại
            </button>
        </form>
    @endif

    {{-- ĐÁNH GIÁ SAO (CSAT RATING SECTION) --}}
    @if($canRate || $request->rating)
        <section class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-7 shadow-subtle" aria-labelledby="request-rating-title">
            <div class="flex items-center gap-2 mb-2">
                <span class="text-amber-400 text-xl">★</span>
                <h3 id="request-rating-title" tabindex="-1" class="text-lg font-bold text-slate-900">Đánh giá chất lượng phục vụ (CSAT)</h3>
            </div>
            @if($canRate)
                <p class="text-xs text-slate-500 mb-4">Hồ sơ đã hoàn tất. Đánh giá của sinh viên giúp nâng cao chất lượng dịch vụ của các phòng ban trong trường.</p>
                <form method="POST" action="{{ route('requests.rating.store', $request) }}" class="space-y-4">
                    @csrf
                    <fieldset>
                        <legend class="text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">Mức độ hài lòng từ 1 đến 5 sao</legend>
                        <div class="flex items-center gap-2" x-data="{ rating: {{ (int) old('rating', 0) }}, hoverRating: 0 }" role="radiogroup" aria-label="Chọn mức độ hài lòng từ 1 đến 5 sao">
                            @foreach([1 => '1 sao - Rất chưa hài lòng', 2 => '2 sao - Chưa hài lòng', 3 => '3 sao - Bình thường', 4 => '4 sao - Hài lòng', 5 => '5 sao - Rất hài lòng'] as $score => $label)
                                <label class="cursor-pointer rounded-lg p-1 transition-transform hover:scale-110 focus-within:outline-none focus-within:ring-2 focus-within:ring-primary-500"
                                       @mouseenter="hoverRating = {{ $score }}"
                                       @mouseleave="hoverRating = 0"
                                       title="{{ $label }}">
                                    <input class="peer sr-only" type="radio" name="rating" value="{{ $score }}" x-model.number="rating" @checked((int) old('rating') === $score) aria-label="{{ $label }}" required>
                                    <span class="block text-4xl leading-none transition-colors select-none"
                                          aria-hidden="true"
                                          :class="(hoverRating ? hoverRating >= {{ $score }} : rating >= {{ $score }}) ? 'text-amber-400 drop-shadow-sm' : 'text-slate-200 hover:text-amber-200'"
                                          x-text="(hoverRating ? hoverRating >= {{ $score }} : rating >= {{ $score }}) ? '★' : '☆'">☆</span>
                                </label>
                            @endforeach
                            <span class="ml-2 text-xs font-semibold text-slate-600" x-show="rating > 0" x-text="['', 'Rất chưa hài lòng', 'Chưa hài lòng', 'Bình thường', 'Hài lòng', 'Rất hài lòng'][rating]"></span>
                        </div>
                        <p class="mt-1 text-[11px] text-slate-400">Chọn số sao tương ứng với trải nghiệm phục vụ của bạn</p>
                        @error('rating')<p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>@enderror
                    </fieldset>
                    <div>
                        <label for="rating-comment" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                            Góp ý chi tiết <span class="font-normal lowercase text-slate-400">(không bắt buộc)</span>
                        </label>
                        <textarea id="rating-comment" name="rating_comment" rows="3" maxlength="1000" placeholder="Chia sẻ thêm về trải nghiệm giải quyết hồ sơ hoặc thái độ phục vụ..."
                                  class="w-full bg-slate-50 rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 focus:bg-white transition resize-none">{{ old('rating_comment') }}</textarea>
                        @error('rating_comment')<p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary-600 px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-white transition hover:bg-primary-700 shadow-subtle">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Gửi đánh giá dịch vụ
                    </button>
                </form>
            @else
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <span class="font-bold text-2xl text-amber-400 tracking-wider" aria-label="{{ $request->rating }} trên 5 sao">{{ str_repeat('★', (int) $request->rating) }}{{ str_repeat('☆', 5 - (int) $request->rating) }}</span>
                    <span class="text-sm font-bold text-slate-800 bg-amber-50 px-3 py-1 rounded-lg border border-amber-200">{{ $request->rating }} / 5 sao</span>
                    @if($request->rated_at)<span class="text-xs text-slate-500">Đã gửi đánh giá lúc {{ $request->rated_at->format('d/m/Y H:i') }}</span>@endif
                </div>
                @if($request->rating_comment)
                    <div class="mt-3.5 bg-slate-50 rounded-xl p-4 border border-slate-200 text-sm text-slate-800 leading-relaxed">
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Nhận xét từ sinh viên:</p>
                        <p class="whitespace-pre-wrap">{{ $request->rating_comment }}</p>
                    </div>
                @endif
            @endif
        </section>
    @endif

    {{-- Main Grid: Left (Content + Actions + Comments), Right (Timeline) --}}
    <div class="grid md:grid-cols-3 gap-6">
        {{-- Left 2 Cols: Content + Actions --}}
        <div class="md:col-span-2 space-y-6">
            {{-- Ticket Content Card --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-7 shadow-subtle">
                <div class="flex items-center gap-2 mb-4 border-b border-slate-100 pb-3">
                    <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <h3 class="text-base font-bold text-slate-900 uppercase tracking-wide">Nội dung chi tiết yêu cầu</h3>
                </div>
                <p class="text-slate-800 whitespace-pre-wrap leading-relaxed font-sans text-sm sm:text-base">{{ $request->content }}</p>

                @if($request->cancelled_reason)
                    <div class="mt-5 p-4 bg-rose-50 border border-rose-200 rounded-xl text-sm text-rose-800">
                        <strong class="font-bold">Lý do hủy yêu cầu:</strong> {{ $request->cancelled_reason }}
                    </div>
                @endif

                @if($request->attachments && $request->attachments->isNotEmpty())
                    <div class="mt-6 pt-5 border-t border-slate-100">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Tệp đính kèm / Ảnh hiện trường ({{ $request->attachments->count() }})</h4>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            @foreach($request->attachments as $att)
                                <a href="{{ $att->url() }}" target="_blank" rel="noopener"
                                   class="group block rounded-xl overflow-hidden border border-slate-200 bg-slate-50 hover:border-primary-500 transition shadow-xs">
                                    <img src="{{ $att->url() }}" alt="{{ $att->original_name }}"
                                         class="w-full h-32 object-cover group-hover:scale-105 transition duration-300">
                                    <p class="px-2.5 py-1.5 text-[11px] font-medium text-slate-600 truncate group-hover:text-primary-700">{{ $att->original_name }}</p>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Actions Card (if not terminal) --}}
            @if(!$isTerminal)
            <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-7 shadow-subtle space-y-6">
                <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                    <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <h3 class="text-base font-bold text-slate-900 uppercase tracking-wide">Quy trình xử lý hồ sơ</h3>
                </div>

                {{-- Bắt buộc gán trước khi đổi trạng thái --}}
                @if($needsAssign)
                    <div class="rounded-xl bg-amber-50 border border-amber-200 p-4 text-sm text-amber-900 flex items-start gap-3">
                        <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <div>
                            <strong>Chưa phân công cán bộ:</strong> Trưởng phòng hoặc Quản trị viên cần phân công cán bộ phụ trách trước khi chuyển tiếp trạng thái xử lý.
                        </div>
                    </div>
                @endif

                @if($statusVal === 'resolved' && $user['role'] === 'staff' && ! $hasStudentReply)
                    <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-900 flex items-start gap-3">
                        <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div>
                            <strong>Đã gửi kết quả xử lý:</strong> Chờ sinh viên phản hồi trước khi đóng yêu cầu.
                        </div>
                    </div>
                @endif

                {{-- Change status (staff/admin) --}}
                @if($canChangeStatus && count($allowedNext) > 0 && ! $needsAssign)
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">Chuyển tiếp trạng thái tiến độ</p>
                        <form method="POST" action="{{ route('requests.update-status', $request) }}" class="space-y-3">
                            @csrf
                            @method('PUT')
                            <textarea id="workflow-note" name="note" rows="2" placeholder="Ghi chú nội dung tiến độ cập nhật (tùy chọn)..."
                                      class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 focus:bg-white transition resize-none"></textarea>
                            <div class="flex flex-wrap gap-2.5">
                                @foreach($allowedNext as $next)
                                    <button type="submit" name="status" value="{{ $next }}"
                                            class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold tracking-wide uppercase transition border shadow-xs
                                                   {{ $next === 'cancelled'
                                                       ? 'bg-rose-50 text-rose-800 hover:bg-rose-100 border-rose-200'
                                                       : ($next === 'resolved'
                                                           ? 'bg-emerald-600 text-white hover:bg-emerald-700 border-emerald-600'
                                                           : 'bg-primary-50 text-primary-800 hover:bg-primary-100 border-primary-200') }}">
                                        {{ $statusLabels[$next] ?? $next }}
                                    </button>
                                @endforeach
                            </div>
                        </form>
                    </div>
                @endif

                {{-- Transfer department (admin) --}}
                @if($canTransfer)
                    <div class="pt-5 border-t border-slate-100">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">Chuyển phòng ban điều phối</p>
                        <form method="POST" action="{{ route('requests.transfer', $request) }}" class="space-y-3">
                            @csrf
                            @method('PUT')
                            <div class="relative">
                                <select name="department_id" id="transfer-department" required onchange="filterTransferSupportTypes()"
                                        class="w-full appearance-none bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 font-medium pr-10">
                                    <option value="">-- Chọn phòng ban nhận chuyển tiếp --</option>
                                    @foreach($departments as $departmentId => $departmentName)
                                        @if($departmentId !== $request->department_id)
                                            <option value="{{ $departmentId }}">{{ $departmentName }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3.5 text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>
                            <div class="flex gap-2.5">
                                <div class="relative flex-1">
                                    <select name="support_type_id" id="transfer-support-type" required
                                            class="w-full appearance-none bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 font-medium pr-10">
                                        <option value="">-- Chọn loại hỗ trợ theo phòng ban mới --</option>
                                        @foreach($supportTypes as $supportTypeId => $supportType)
                                            <option value="{{ $supportTypeId }}" data-dept="{{ $supportType['department_id'] }}">{{ $supportType['name'] }}</option>
                                        @endforeach
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3.5 text-slate-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                </div>
                                <button type="submit" class="rounded-xl bg-primary-600 px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-white hover:bg-primary-700 transition shadow-subtle shrink-0">
                                    Điều phối
                                </button>
                            </div>
                        </form>
                    </div>
                @endif

                {{-- Assign staff --}}
                @if($canAssign)
                    <div class="{{ $canChangeStatus ? 'pt-5 border-t border-slate-100' : '' }}">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">
                            Phân công cán bộ phụ trách @if($needsAssign)<span class="text-amber-700 font-bold lowercase">(bắt buộc trước)</span>@endif
                        </p>
                        <p class="text-[11px] text-slate-400 mb-3">Lựa chọn cán bộ chuyên trách của phòng ban để giải quyết yêu cầu</p>
                        <form method="POST" action="{{ route('requests.assign', $request) }}" class="flex gap-2.5">
                            @csrf
                            @method('PUT')
                            <div class="relative flex-1">
                                <select id="assignment-staff" name="assigned_to" required
                                        class="w-full appearance-none bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 font-medium pr-10">
                                    <option value="">-- Chọn cán bộ phụ trách --</option>
                                    @foreach($demoUsers as $staff)
                                        @if($staff['role'] === 'staff' && in_array($staff['id'], config("master_data.staff_by_department.{$request->department_id}", []), true))
                                            <option value="{{ $staff['id'] }}" @selected($request->assigned_to === $staff['id'])>{{ $staff['full_name'] }} (CB #{{ $staff['id'] }})</option>
                                        @endif
                                    @endforeach
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3.5 text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>
                            <button type="submit"
                                    class="px-5 py-2.5 bg-primary-600 text-white text-xs font-bold uppercase tracking-wider rounded-xl hover:bg-primary-700 transition shadow-subtle shrink-0">
                                Lưu phân công
                            </button>
                        </form>
                    </div>
                @endif

                {{-- Cancel Ticket --}}
                @if($canCancel && in_array('cancelled', $allowedNext, true))
                    <div class="pt-5 border-t border-slate-100" x-data="{ open: false }">
                        <button type="button" @click="open = !open" class="text-xs font-bold uppercase tracking-wider text-rose-600 hover:text-rose-800 transition flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Hủy yêu cầu hỗ trợ...
                        </button>
                        <div x-show="open" x-cloak class="mt-3 bg-rose-50/70 p-4 rounded-xl border border-rose-200">
                            <form method="POST" action="{{ route('requests.cancel', $request) }}">
                                @csrf
                                @method('PUT')
                                <textarea name="reason" rows="2" placeholder="Nhập lý do hủy yêu cầu..."
                                          class="w-full bg-white border border-rose-200 rounded-xl px-3.5 py-2 text-sm mb-2.5 focus:outline-none focus:ring-2 focus:ring-rose-400 resize-none"></textarea>
                                <div class="flex gap-2">
                                    <button type="submit" class="px-4 py-2 bg-rose-600 text-white text-xs font-bold uppercase tracking-wider rounded-lg hover:bg-rose-700 transition">
                                        Xác nhận hủy
                                    </button>
                                    <button type="button" @click="open = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-white rounded-lg border border-slate-200 transition">
                                        Đóng lại
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif

                {{-- Edit / Delete (when new) --}}
                @if($canEdit)
                    <div class="pt-5 border-t border-slate-100">
                        <p class="text-xs text-slate-500 mb-3">Yêu cầu đang ở trạng thái <strong>Mới tạo</strong> — bạn có thể hiệu chỉnh nội dung hoặc rút lại hồ sơ.</p>
                        <div class="flex flex-wrap gap-2.5">
                            <a href="{{ route('requests.edit', $request) }}"
                               class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider bg-primary-600 text-white hover:bg-primary-700 transition shadow-subtle">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Sửa yêu cầu
                            </a>
                            <form method="POST" action="{{ route('requests.destroy', $request) }}"
                                  onsubmit="return confirm('Bạn chắc chắn muốn xóa yêu cầu này?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 transition">
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
                <div class="rounded-2xl border border-amber-300 bg-amber-50/90 p-5 text-sm text-amber-950 shadow-subtle flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="leading-relaxed">
                        Cán bộ phụ trách đang chờ bạn bổ sung thêm thông tin. Vui lòng phản hồi chi tiết bên dưới qua mục trao đổi hoặc theo dõi các ghi chú từ cán bộ trong tiến độ xử lý.
                    </p>
                </div>
            @endif
        </div>

        {{-- Right Column: Status Timeline --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-7 shadow-subtle h-fit">
            <div class="flex items-center gap-2 border-b border-slate-100 pb-3 mb-5">
                <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <h3 class="text-base font-bold text-slate-900 uppercase tracking-wide">Lịch sử tiến độ</h3>
            </div>
            @if($histories->isEmpty())
                <p class="text-xs text-slate-400 italic">Chưa có lịch sử cập nhật</p>
            @else
                <ol class="relative border-l-2 border-slate-200 space-y-6 ml-2.5">
                    @foreach($histories as $h)
                        <li class="ml-5">
                            <div class="absolute -left-[9px] mt-1.5 w-4 h-4 rounded-full bg-primary-600 border-2 border-white ring-2 ring-primary-100 shadow-xs"></div>
                            <div class="flex items-center gap-2 mb-1 flex-wrap">
                                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-bold {{ $statusColors[$h->to_status] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                                    {{ $statusLabels[$h->to_status] ?? $h->to_status }}
                                </span>
                                @if($h->from_status)
                                    <span class="text-[11px] text-slate-400">từ {{ $statusLabels[$h->from_status] ?? $h->from_status }}</span>
                                @endif
                            </div>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                {{ \Carbon\Carbon::parse($h->created_at)->format('d/m/Y H:i') }}
                                · <span class="text-slate-800 font-semibold">
                                    @if($h->changed_by === null)
                                        Hệ thống tự động
                                    @elseif(isset($allUsers[$h->changed_by]))
                                        {{ $allUsers[$h->changed_by]['full_name'] }}
                                        <span class="text-slate-400 font-normal text-[10px]">({{ match($allUsers[$h->changed_by]['role'] ?? '') {
                                            'student' => 'Sinh viên',
                                            'staff' => 'Cán bộ',
                                            'department_head' => 'Trưởng phòng',
                                            'admin' => 'Quản trị viên',
                                            default => 'User #'.$h->changed_by,
                                        } }})</span>
                                    @else
                                        User #{{ $h->changed_by }}
                                    @endif
                                </span>
                            </p>
                            @if($h->note)
                                <p class="text-xs text-slate-800 mt-2 bg-slate-50 rounded-xl p-3 border border-slate-200 leading-relaxed">{{ $h->note }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
    </div>

    {{-- Comment Thread --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-subtle">
        <div class="flex items-center justify-between mb-6 border-b border-slate-100 pb-4">
            <h3 class="text-xl font-extrabold text-slate-900 flex items-center gap-2.5">
                <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                Trao đổi & Phản hồi
                <span class="inline-flex items-center justify-center bg-primary-50 border border-primary-200 text-primary-700 text-xs font-bold font-mono rounded-full px-2.5 py-0.5">
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
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xs font-extrabold shadow-subtle border border-slate-200
                        {{ $user['role'] === 'admin'
                            ? 'bg-slate-900 text-white'
                            : ($user['role'] === 'staff' ? 'bg-primary-600 text-white' : ($user['role'] === 'department_head' ? 'bg-indigo-600 text-white' : 'bg-emerald-600 text-white')) }}">
                        {{ mb_substr($user['full_name'], 0, 1) }}
                    </div>
                </div>

                <div class="flex-1 space-y-3">
                    @if(in_array($user['role'], ['staff', 'department_head', 'admin'], true))
                        <div class="relative">
                            <select aria-label="Chọn câu trả lời mẫu chuẩn trường học" onchange="document.getElementById('comment-body').value = this.value; this.value = ''"
                                    class="w-full appearance-none border border-slate-200 rounded-xl bg-slate-50 px-4 py-2.5 text-xs text-slate-600 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 font-medium pr-10">
                                <option value="">Chèn câu trả lời mẫu theo tiêu chuẩn một cửa...</option>
                                @foreach($replyTemplates as $templateKey => $template)
                                    <option value="{{ $template }}">{{ ['received' => 'Đã tiếp nhận hồ sơ', 'need_info' => 'Yêu cầu bổ sung giấy tờ', 'in_progress' => 'Đang xử lý nghiệp vụ', 'resolved' => 'Đã hoàn tất kết quả'][$templateKey] ?? $templateKey }}</option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3.5 text-slate-400">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </div>
                    @endif
                    <textarea id="comment-body" name="body" rows="3" required
                              placeholder="Nhập nội dung trao đổi, hướng dẫn hoặc thắc mắc..."
                              class="w-full bg-slate-50 border border-slate-200 rounded-xl p-4 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 focus:bg-white resize-none transition leading-relaxed shadow-sm"
                    >{{ old('body') }}</textarea>

                    <div class="flex flex-wrap items-center gap-3">
                        {{-- Upload file --}}
                        <label class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-600 bg-slate-50 border border-slate-200 hover:bg-white hover:text-primary-600 cursor-pointer transition shadow-xs">
                            <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                            Đính kèm tệp tin
                            <input type="file" name="attachments[]" multiple class="hidden" id="comment-files" onchange="updateFileLabel(this)">
                        </label>
                        <span id="file-count" class="text-xs text-slate-500 italic font-medium"></span>

                        {{-- Checkbox nội bộ — chỉ staff/head/admin --}}
                        @if(in_array($user['role'], ['staff', 'department_head', 'admin']))
                        <label class="inline-flex items-center gap-1.5 text-xs font-medium text-amber-900 bg-amber-50 px-3 py-1.5 rounded-xl border border-amber-200 cursor-pointer ml-auto">
                            <input type="checkbox" name="is_internal" value="1"
                                    class="rounded border-amber-300 text-amber-600 focus:ring-amber-500 w-3.5 h-3.5">
                            <svg class="w-3.5 h-3.5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            Ghi chú nội bộ (SV không thấy)
                        </label>
                        @endif

                        <button type="submit"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider bg-primary-600 text-white hover:bg-primary-700 transition shadow-subtle ml-auto">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                            Gửi phản hồi
                        </button>
                    </div>
                </div>
            </div>
        </form>
        @else
        <div class="mb-8 rounded-2xl bg-slate-50 border border-slate-200 px-5 py-4 text-xs font-medium text-slate-500">
            @if($isTerminal)
                Yêu cầu đã {{ $statusVal === 'closed' ? 'hoàn tất' : 'hủy' }} — không thể gửi thêm trao đổi.
            @else
                Bạn có thể phản hồi khi yêu cầu đang ở trạng thái chờ bổ sung thông tin hoặc chờ xác nhận kết quả.
            @endif
        </div>
        @endif

        {{-- Danh sách comment --}}
        @if($comments->isEmpty())
            <div class="text-center py-10 border border-dashed border-slate-200 rounded-2xl bg-slate-50/50">
                <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                <p class="text-sm font-bold text-slate-700">Chưa có nội dung trao đổi nào</p>
                <p class="text-xs text-slate-400 mt-1">Cán bộ và sinh viên có thể trao đổi trực tiếp tại đây để xử lý hồ sơ nhanh chóng.</p>
            </div>
        @else
            <div class="space-y-5">
                @foreach($comments as $comment)
                    @php
                        $isOwn = $comment->user_id === $user['id'];
                        $roleColors = [
                            'student' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            'staff' => 'bg-primary-50 text-primary-700 border-primary-200',
                            'department_head' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                            'admin' => 'bg-slate-800 text-white border-slate-800',
                        ];
                        $roleBadge = [
                            'student' => 'Sinh viên',
                            'staff' => 'Cán bộ',
                            'department_head' => 'Trưởng phòng',
                            'admin' => 'Quản trị viên',
                        ];
                    @endphp
                    <div class="flex gap-3.5 group {{ $comment->is_internal ? 'relative' : '' }}">
                        {{-- Avatar --}}
                        <div class="shrink-0 mt-0.5">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-bold border border-slate-200 shadow-xs
                                {{ $comment->user_role === 'admin'
                                    ? 'bg-slate-900 text-white'
                                    : ($comment->user_role === 'staff' ? 'bg-primary-600 text-white' : ($comment->user_role === 'department_head' ? 'bg-indigo-600 text-white' : 'bg-emerald-600 text-white')) }}">
                                {{ mb_substr($comment->user_name ?? '?', 0, 1) }}
                            </div>
                        </div>

                        {{-- Nội dung --}}
                        <div class="flex-1 min-w-0">
                            <div class="rounded-2xl p-4 sm:p-5 transition shadow-xs {{ $comment->is_internal
                                ? 'bg-amber-50/90 border border-amber-200 shadow-subtle'
                                : ($isOwn ? 'bg-primary-50/40 border border-primary-200/60' : 'bg-slate-50 border border-slate-200') }}">

                                {{-- Header --}}
                                <div class="flex items-center gap-2 mb-2 flex-wrap">
                                    <span class="text-sm font-bold text-slate-900">{{ $comment->user_name ?? 'User #'.$comment->user_id }}</span>
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold border {{ $roleColors[$comment->user_role] ?? 'bg-slate-100 text-slate-600 border-slate-200' }}">
                                        {{ $roleBadge[$comment->user_role] ?? $comment->user_role }}
                                    </span>
                                    @if($comment->is_internal)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 text-amber-900 border border-amber-300 px-2 py-0.5 text-[10px] font-bold">
                                            <svg class="w-3 h-3 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                            Nội bộ
                                        </span>
                                    @endif
                                    <span class="text-[11px] text-slate-400 ml-auto">{{ $comment->created_at->format('d/m/Y H:i') }}</span>
                                </div>

                                {{-- Body --}}
                                <p class="text-sm text-slate-800 whitespace-pre-wrap leading-relaxed font-sans">{{ $comment->body }}</p>

                                {{-- File đính kèm --}}
                                @if($comment->attachments && $comment->attachments->isNotEmpty())
                                    <div class="mt-3.5 pt-3 border-t {{ $comment->is_internal ? 'border-amber-200' : 'border-slate-200' }}">
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($comment->attachments as $att)
                                                <a href="{{ route('requests.comments.attachments.preview', ['supportRequest' => $request, 'comment' => $comment, 'commentAttachment' => $att]) }}" target="_blank" rel="noopener"
                                                   class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-semibold bg-white border border-slate-200 text-slate-800 hover:border-primary-500 hover:text-primary-700 transition shadow-xs">
                                                    @if($att->isImage())
                                                        <svg class="w-3.5 h-3.5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                    @else
                                                        <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                    @endif
                                                    <span class="max-w-[160px] truncate">{{ $att->original_name }}</span>
                                                    <span class="text-[10px] text-slate-400 font-mono">({{ number_format($att->size / 1024, 0) }}KB)</span>
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
                                        <button type="submit" class="text-[11px] font-bold text-rose-600 hover:text-rose-800 transition">
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
                <div class="mt-6 pt-5 border-t border-slate-100">
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

@endsection
