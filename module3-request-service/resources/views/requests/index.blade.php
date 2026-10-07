@extends('layouts.app')

@section('title', 'Danh sách yêu cầu — Module 3')

@section('content')
@php
    $statusLabels = [
        'new' => 'Mới tạo', 'received' => 'Đã tiếp nhận', 'in_progress' => 'Đang xử lý',
        'waiting_info' => 'Chờ bổ sung',
        'resolved' => 'Chờ phản hồi SV', 'closed' => 'Đã đóng', 'cancelled' => 'Đã hủy',
    ];
    $statusColors = [
        'new' => 'bg-[#EBF3FA] text-[#2C5282] border-[#C3D9ED]',
        'received' => 'bg-[#F5EFEB] text-[#7A5A44] border-[#E2D5C9]',
        'in_progress' => 'bg-[#FFF6E5] text-[#9C6E1E] border-[#F4DCAC]',
        'waiting_info' => 'bg-[#FDF0EB] text-[#B85D36] border-[#F5D5C6]',
        'resolved' => 'bg-[#EDF5EE] text-[#2D6A4F] border-[#CDE5CF]',
        'closed' => 'bg-[#F2ECE7] text-[#6B5E55] border-[#DED6CF]',
        'cancelled' => 'bg-[#FAECEB] text-[#A84440] border-[#F1C8C6]',
    ];
    $slaLabels = ['warning' => 'Sắp quá hạn', 'breached' => 'Quá hạn'];
    $slaColors = [
        'warning' => 'bg-[#FEF9EE] text-[#B45309] border border-[#FDE68A]',
        'breached' => 'bg-[#FEF2F2] text-[#B91C1C] border border-[#FECACA]'
    ];
    $priorityLabels = ['low' => 'Thấp', 'normal' => 'Bình thường', 'high' => 'Cao', 'urgent' => 'Khẩn cấp'];
    $priorityColors = [
        'low' => 'bg-wood-100 text-ink-muted border border-wood-200',
        'normal' => 'bg-wood-50 text-wood-700 border border-wood-200',
        'high' => 'bg-[#FFF7ED] text-[#C2410C] border border-[#FFEDD5]',
        'urgent' => 'bg-[#FEF2F2] text-[#DC2626] border border-[#FEE2E2] font-semibold',
    ];
    $deptNames = config('master_data.departments', []);

    $allItems = $requests->getCollection();
    $urgentItems = $allItems->filter(function ($r) {
        $p = $r->priority instanceof \App\Enums\RequestPriority ? $r->priority->value : $r->priority;
        return $p === 'urgent';
    });
    $normalItems = $allItems->reject(function ($r) {
        $p = $r->priority instanceof \App\Enums\RequestPriority ? $r->priority->value : $r->priority;
        return $p === 'urgent';
    });
@endphp

{{-- Header Banner & Actions --}}
<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-6 pb-4 border-b border-borderWarm/70">
    <div>
        <span class="text-xs font-bold text-gold-600 uppercase tracking-widest font-mono">Quản lý tiếp nhận</span>
        <h2 class="text-3xl font-serif font-bold text-wood-800 tracking-tight mt-1">Danh sách yêu cầu hỗ trợ</h2>
        <p class="text-sm text-ink-muted mt-1 font-sans">
            @if($user['role'] === 'student')
                Các yêu cầu bạn đã gửi tới các phòng ban chức năng
            @elseif($user['role'] === 'staff')
                Hồ sơ và yêu cầu được giao cho bạn trực tiếp xử lý
            @elseif($user['role'] === 'department_head')
                Toàn bộ hồ sơ yêu cầu thuộc phạm vi phòng ban phụ trách
            @else
                Quản trị toàn bộ quy trình yêu cầu trong hệ thống
            @endif
        </p>
    </div>
    @if($user['role'] === 'student')
        <a href="{{ route('requests.create') }}"
           class="inline-flex w-full sm:w-auto items-center justify-center gap-2.5 px-5 py-2.5 bg-wood-800 hover:bg-gold-600 text-white text-sm font-semibold rounded-xl transition-all duration-200 shadow-warm hover:shadow-warm-lg hover:-translate-y-0.5 border border-wood-700">
            <svg class="w-4 h-4 text-gold-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Tạo yêu cầu mới</span>
        </a>
    @endif
</div>

{{-- Thống kê nhanh phong cách thẻ sản phẩm mộc Nội Thất Tinh Hoa --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-3.5 mb-6">
    <div class="bg-white rounded-2xl border border-borderWarm p-4 shadow-warm-sm flex items-center gap-3.5">
        <div class="w-10 h-10 rounded-xl bg-wood-50 border border-wood-200 flex items-center justify-center text-wood-700 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <div>
            <p class="text-[11px] font-bold uppercase tracking-wider text-ink-muted">Tổng số</p>
            <p class="text-2xl font-serif font-bold text-wood-800 leading-none mt-0.5">{{ $requests->total() }}</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-borderWarm p-4 shadow-warm-sm flex items-center gap-3.5">
        <div class="w-10 h-10 rounded-xl bg-[#FFF6E5] border border-[#F4DCAC] flex items-center justify-center text-[#9C6E1E] shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <p class="text-[11px] font-bold uppercase tracking-wider text-ink-muted">Đang xử lý</p>
            <p class="text-2xl font-serif font-bold text-[#9C6E1E] leading-none mt-0.5">
                {{ $allItems->whereIn('status', ['new', 'received', 'in_progress'])->count() }}
            </p>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-borderWarm p-4 shadow-warm-sm flex items-center gap-3.5">
        <div class="w-10 h-10 rounded-xl bg-[#EDF5EE] border border-[#CDE5CF] flex items-center justify-center text-[#2D6A4F] shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <p class="text-[11px] font-bold uppercase tracking-wider text-ink-muted">Hoàn tất/Đóng</p>
            <p class="text-2xl font-serif font-bold text-[#2D6A4F] leading-none mt-0.5">
                {{ $allItems->whereIn('status', ['resolved', 'closed'])->count() }}
            </p>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-borderWarm p-4 shadow-warm-sm flex items-center gap-3.5">
        <div class="w-10 h-10 rounded-xl bg-[#FEF2F2] border border-[#FEE2E2] flex items-center justify-center text-[#DC2626] shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <div>
            <p class="text-[11px] font-bold uppercase tracking-wider text-ink-muted">Khẩn cấp/SLA</p>
            <p class="text-2xl font-serif font-bold text-[#DC2626] leading-none mt-0.5">
                {{ $allItems->where('sla_flag', 'breached')->count() + $urgentItems->count() }}
            </p>
        </div>
    </div>
</div>

{{-- Khung Bộ Lọc & Tìm Kiếm Phong Cách Sang Trọng --}}
@php
    $isHeadOrAdmin = in_array($user['role'], ['admin', 'department_head'], true);
    $hasActiveFilter = !empty($search) || !empty($statusFilter) || !empty($priorityFilter)
        || !empty($departmentFilter) || !empty($assignedToFilter) || !empty($fromFilter) || !empty($toFilter);
@endphp

<div class="bg-white rounded-2xl border border-borderWarm shadow-warm p-4 sm:p-5 mb-6">
    <form method="GET">
        @if(! $isHeadOrAdmin)
            {{-- Student / Staff: 1 hàng gọn gàng --}}
            <div class="flex flex-wrap items-end gap-3">
                <div class="relative flex-1 min-w-[200px] max-w-sm">
                    <label class="block text-xs font-semibold text-ink-muted uppercase tracking-wider mb-1.5 font-mono">Tìm kiếm</label>
                    <div class="relative">
                        <svg class="w-4 h-4 text-gold-600 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" name="q" value="{{ $search ?? '' }}" placeholder="Mã yêu cầu, tiêu đề, nội dung..."
                               class="w-full pl-10 pr-3.5 py-2.5 bg-paper border border-borderWarm rounded-xl text-sm text-ink placeholder:text-ink-light focus:outline-none focus:ring-2 focus:ring-gold-500/40 focus:border-gold-500 transition">
                    </div>
                </div>

                <div class="w-[150px]">
                    <label class="block text-xs font-semibold text-ink-muted uppercase tracking-wider mb-1.5 font-mono">Trạng thái</label>
                    <select name="status" class="w-full border border-borderWarm bg-paper rounded-xl px-3 py-2.5 text-sm font-medium text-ink focus:outline-none focus:ring-2 focus:ring-gold-500/40 focus:border-gold-500 cursor-pointer transition">
                        <option value="">Tất cả</option>
                        @foreach($statusLabels as $k => $v)
                            <option value="{{ $k }}" @selected(($statusFilter ?? '') === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="w-[130px]">
                    <label class="block text-xs font-semibold text-ink-muted uppercase tracking-wider mb-1.5 font-mono">Ưu tiên</label>
                    <select name="priority" class="w-full border border-borderWarm bg-paper rounded-xl px-3 py-2.5 text-sm font-medium text-ink focus:outline-none focus:ring-2 focus:ring-gold-500/40 focus:border-gold-500 cursor-pointer transition">
                        <option value="">Tất cả</option>
                        @foreach($priorityLabels as $k => $v)
                            <option value="{{ $k }}" @selected(($priorityFilter ?? '') === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="w-[145px]">
                    <label class="block text-xs font-semibold text-ink-muted uppercase tracking-wider mb-1.5 font-mono">Từ ngày</label>
                    <input type="date" name="from" value="{{ $fromFilter ?? '' }}"
                           class="w-full border border-borderWarm bg-paper rounded-xl px-3 py-2 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-gold-500/40 focus:border-gold-500 transition">
                </div>

                <div class="w-[145px]">
                    <label class="block text-xs font-semibold text-ink-muted uppercase tracking-wider mb-1.5 font-mono">Đến ngày</label>
                    <input type="date" name="to" value="{{ $toFilter ?? '' }}"
                           class="w-full border border-borderWarm bg-paper rounded-xl px-3 py-2 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-gold-500/40 focus:border-gold-500 transition">
                </div>

                <div class="flex items-center gap-2 pb-0.5">
                    <button type="submit" class="px-4 py-2.5 bg-wood-800 text-gold-300 hover:bg-wood-700 text-sm font-semibold rounded-xl transition shadow-warm border border-wood-700">
                        Lọc dữ liệu
                    </button>
                    @if($hasActiveFilter)
                        <a href="{{ route('requests.index') }}" class="px-3 py-2.5 text-sm text-ink-muted hover:text-wood-800 font-medium transition">
                            Xóa lọc
                        </a>
                    @endif
                </div>

                <span class="text-xs text-ink-muted ml-auto self-center pb-0.5 font-mono">
                    Tìm thấy <strong class="text-wood-800 font-serif text-base">{{ $requests->total() }}</strong> yêu cầu
                </span>
            </div>
        @else
            {{-- Head / Admin: Grid chia 2 hàng chuyên nghiệp --}}
            <div class="space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
                    <div class="relative min-w-0">
                        <label class="block text-xs font-semibold text-ink-muted uppercase tracking-wider mb-1 font-mono">Tìm kiếm</label>
                        <div class="relative">
                            <svg class="w-3.5 h-3.5 text-gold-600 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input type="text" name="q" value="{{ $search ?? '' }}" placeholder="Mã yêu cầu, tiêu đề..."
                                   class="w-full pl-9 pr-3 py-2 bg-paper border border-borderWarm rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-gold-500/40 focus:border-gold-500 transition">
                        </div>
                    </div>

                    <div class="min-w-0">
                        <label class="block text-xs font-semibold text-ink-muted uppercase tracking-wider mb-1 font-mono">Trạng thái</label>
                        <select name="status" class="w-full border border-borderWarm bg-paper rounded-xl px-3 py-2 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-gold-500/40 cursor-pointer transition">
                            <option value="">Tất cả</option>
                            @foreach($statusLabels as $k => $v)
                                <option value="{{ $k }}" @selected(($statusFilter ?? '') === $k)>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="min-w-0">
                        <label class="block text-xs font-semibold text-ink-muted uppercase tracking-wider mb-1 font-mono">Ưu tiên</label>
                        <select name="priority" class="w-full border border-borderWarm bg-paper rounded-xl px-3 py-2 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-gold-500/40 cursor-pointer transition">
                            <option value="">Tất cả</option>
                            @foreach($priorityLabels as $k => $v)
                                <option value="{{ $k }}" @selected(($priorityFilter ?? '') === $k)>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="min-w-0">
                        <label class="block text-xs font-semibold text-ink-muted uppercase tracking-wider mb-1 font-mono">Phòng ban</label>
                        <select name="department_id" class="w-full border border-borderWarm bg-paper rounded-xl px-3 py-2 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-gold-500/40 cursor-pointer transition">
                            <option value="">Tất cả phòng ban</option>
                            @foreach(($departments ?? []) as $id => $name)
                                <option value="{{ $id }}" @selected((string)($departmentFilter ?? '') === (string)$id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end pt-1">
                    <div class="min-w-0">
                        <label class="block text-xs font-semibold text-ink-muted uppercase tracking-wider mb-1 font-mono">Cán bộ (ID)</label>
                        <input type="number" name="assigned_to" value="{{ $assignedToFilter ?? '' }}" placeholder="Ví dụ: 21"
                               class="w-full border border-borderWarm bg-paper rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold-500/40 transition">
                    </div>

                    <div class="min-w-0">
                        <label class="block text-xs font-semibold text-ink-muted uppercase tracking-wider mb-1 font-mono">Từ ngày</label>
                        <input type="date" name="from" value="{{ $fromFilter ?? '' }}"
                               class="w-full border border-borderWarm bg-paper rounded-xl px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-gold-500/40 transition">
                    </div>

                    <div class="min-w-0">
                        <label class="block text-xs font-semibold text-ink-muted uppercase tracking-wider mb-1 font-mono">Đến ngày</label>
                        <input type="date" name="to" value="{{ $toFilter ?? '' }}"
                               class="w-full border border-borderWarm bg-paper rounded-xl px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-gold-500/40 transition">
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="submit" class="flex-1 py-2 bg-wood-800 text-gold-300 hover:bg-wood-700 text-sm font-semibold rounded-xl transition shadow-warm border border-wood-700">
                            Áp dụng lọc
                        </button>
                        @if($hasActiveFilter)
                            <a href="{{ route('requests.index') }}" class="px-3 py-2 text-sm text-ink-muted hover:text-wood-800 transition">
                                Đặt lại
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </form>
</div>

{{-- Bảng danh sách Ticket phong cách tinh hoa --}}
<div class="bg-white rounded-2xl border border-borderWarm shadow-warm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-wood-50/70 border-b border-borderWarm text-[11px] font-bold text-ink-muted uppercase tracking-widest font-mono">
                <tr>
                    <th class="px-5 py-3.5">Mã yêu cầu</th>
                    <th class="px-5 py-3.5">Tiêu đề & Dịch vụ</th>
                    <th class="px-5 py-3.5">Phòng ban</th>
                    <th class="px-5 py-3.5">Trạng thái</th>
                    <th class="px-5 py-3.5">Mức ưu tiên</th>
                    <th class="px-5 py-3.5">Cán bộ phụ trách</th>
                    <th class="px-5 py-3.5">Thời hạn SLA</th>
                    <th class="px-5 py-3.5 text-right">Chi tiết</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-borderWarm/60">
                @forelse($requests as $req)
                    @php
                        $st = $req->status instanceof \App\Enums\RequestStatus ? $req->status->value : $req->status;
                        $pr = $req->priority instanceof \App\Enums\RequestPriority ? $req->priority->value : $req->priority;
                        $sla = $req->sla_flag instanceof \App\Enums\SlaFlag ? $req->sla_flag->value : $req->sla_flag;
                        $isUrgent = $pr === 'urgent';
                        $isHigh = $pr === 'high';
                    @endphp
                    <tr class="transition-colors hover:bg-cream/40 {{ $isUrgent ? 'urgent-row' : ($isHigh ? 'high-row' : '') }}">
                        {{-- Code --}}
                        <td class="px-5 py-4 whitespace-nowrap">
                            <a href="{{ route('requests.show', $req) }}" class="font-mono font-bold text-wood-800 hover:text-gold-600 transition flex items-center gap-1.5">
                                <span class="text-gold-500">◈</span>
                                <span>{{ $req->code }}</span>
                            </a>
                        </td>

                        {{-- Title & Type --}}
                        <td class="px-5 py-4 max-w-xs">
                            <a href="{{ route('requests.show', $req) }}" class="font-medium text-wood-900 hover:text-gold-700 block truncate transition">
                                {{ $req->title }}
                            </a>
                            <span class="text-xs text-ink-muted block truncate mt-0.5">
                                {{ $supportTypes[$req->support_type_id]['name'] ?? 'Loại #'.$req->support_type_id }}
                            </span>
                        </td>

                        {{-- Department --}}
                        <td class="px-5 py-4 whitespace-nowrap text-xs text-ink-muted font-medium">
                            {{ $deptNames[$req->department_id] ?? 'Phòng #'.$req->department_id }}
                        </td>

                        {{-- Status Badge --}}
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border {{ $statusColors[$st] ?? 'bg-wood-100 text-ink-muted border-wood-200' }}">
                                {{ $statusLabels[$st] ?? $st }}
                            </span>
                        </td>

                        {{-- Priority Badge --}}
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $priorityColors[$pr] ?? '' }}">
                                {{ $priorityLabels[$pr] ?? $pr }}
                            </span>
                        </td>

                        {{-- Assignee --}}
                        <td class="px-5 py-4 whitespace-nowrap text-xs text-ink">
                            @if($req->assigned_to)
                                <span class="inline-flex items-center gap-1.5 text-wood-800 font-medium">
                                    <span class="w-5 h-5 rounded-full bg-wood-200 flex items-center justify-center text-[10px] font-bold text-wood-700">
                                        {{ $req->assigned_to }}
                                    </span>
                                    <span>CB #{{ $req->assigned_to }}</span>
                                </span>
                            @else
                                <span class="text-ink-light italic">Chưa phân công</span>
                            @endif
                        </td>

                        {{-- SLA Status --}}
                        <td class="px-5 py-4 whitespace-nowrap text-xs">
                            @if($sla && isset($slaLabels[$sla]))
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full font-semibold {{ $slaColors[$sla] ?? '' }}">
                                    <span>{{ $slaLabels[$sla] }}</span>
                                </span>
                            @elseif($req->sla_deadline_at)
                                <span class="text-ink-muted font-mono text-[11px]" title="Hạn: {{ $req->sla_deadline_at->format('d/m/Y H:i') }}">
                                    {{ $req->sla_deadline_at->format('d/m H:i') }}
                                </span>
                            @else
                                <span class="text-ink-light">—</span>
                            @endif
                        </td>

                        {{-- Action link --}}
                        <td class="px-5 py-4 whitespace-nowrap text-right">
                            <a href="{{ route('requests.show', $req) }}"
                               class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-borderWarm bg-paper hover:bg-cream text-wood-800 text-xs font-semibold transition shadow-warm-sm">
                                <span>Xem</span>
                                <span class="text-gold-600">→</span>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-5 py-12 text-center text-ink-muted">
                            <div class="max-w-sm mx-auto">
                                <div class="w-12 h-12 rounded-2xl bg-cream border border-borderWarm mx-auto flex items-center justify-center text-gold-600 mb-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                </div>
                                <h3 class="font-serif text-lg font-bold text-wood-800">Không tìm thấy yêu cầu nào</h3>
                                <p class="text-xs text-ink-muted mt-1">Chưa có yêu cầu nào phù hợp với bộ lọc hiện tại của bạn.</p>
                                @if($hasActiveFilter)
                                    <a href="{{ route('requests.index') }}" class="inline-block mt-3 text-xs font-semibold text-gold-700 underline">Xóa các bộ lọc tìm kiếm</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination Bar --}}
    @if($requests->hasPages())
        <div class="p-4 border-t border-borderWarm bg-wood-50/40">
            {{ $requests->links() }}
        </div>
    @endif
</div>
@endsection
