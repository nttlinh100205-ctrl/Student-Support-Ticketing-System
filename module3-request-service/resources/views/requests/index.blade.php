@extends('layouts.app')

@section('title', 'Danh Sách Yêu Cầu Hỗ Trợ — Cổng Dịch Vụ Sinh Viên')

@section('content')
@if(in_array($user['role'],['admin','department_head','staff']))
<div style="display:flex;justify-content:flex-end;margin-bottom:15px"><a class="uni-button secondary" href="{{ route('workspace.kanban') }}"><i class="fa-solid fa-columns"></i> Xem bảng công việc</a></div>
@endif
@if(in_array($user['role'],['admin','department_head']))
<div style="display:flex;justify-content:flex-end;margin-bottom:15px"><a class="uni-button secondary" target="_blank" rel="noopener" href="{{ route('requests.export',array_merge(request()->query(),['format'=>'print'])) }}"><i class="fa-solid fa-print"></i> In / Lưu báo cáo PDF</a></div>
@endif
@php
    $statusLabels = [
        'new' => 'Mới tạo',
        'received' => 'Đã tiếp nhận',
        'in_progress' => 'Đang xử lý',
        'waiting_info' => 'Chờ SV bổ sung',
        'resolved' => 'Chờ SV xác nhận',
        'closed' => 'Đã hoàn tất',
        'cancelled' => 'Đã hủy',
        'rejected' => 'Từ chối',
    ];
    $statusBadges = [
        'new' => 'bg-slate-100 text-slate-700 border-slate-200',
        'received' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'in_progress' => 'bg-orange-50 text-orange-700 border-orange-200',
        'waiting_info' => 'bg-amber-50 text-amber-700 border-amber-200',
        'resolved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'closed' => 'bg-slate-100 text-slate-700 border-slate-200',
        'cancelled' => 'bg-rose-50 text-rose-700 border-rose-200',
        'rejected' => 'bg-rose-50 text-rose-700 border-rose-200',
    ];

    $priorityLabels = [
        'low' => 'Thấp',
        'normal' => 'Bình thường',
        'high' => 'Cao',
        'urgent' => 'Khẩn cấp',
    ];
    $priorityBadges = [
        'low' => 'bg-slate-100 text-slate-600 border-slate-200',
        'normal' => 'bg-blue-50 text-blue-700 border-blue-200',
        'high' => 'bg-amber-50 text-amber-700 border-amber-200',
        'urgent' => 'bg-rose-50 text-rose-700 border-rose-200 font-semibold',
    ];

    $slaLabels = [
        'on_time' => 'Đúng hạn',
        'warning' => 'Sắp quá hạn',
        'breached' => 'Quá hạn',
    ];
    $slaBadges = [
        'on_time' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'warning' => 'bg-amber-50 text-amber-700 border-amber-200',
        'breached' => 'bg-rose-50 text-rose-700 border-rose-200 font-bold',
    ];

    $deptNames = config('master_data.departments', []);
    $supportTypes = config('master_data.support_types', []);

    $isHeadOrAdmin = in_array($user['role'], ['admin', 'department_head'], true);
    $hasActiveFilter = !empty($search) || !empty($statusFilter) || !empty($priorityFilter)
        || !empty($slaFlagFilter) || !empty($departmentFilter) || !empty($assignedToFilter)
        || !empty($fromFilter) || !empty($toFilter) || request()->filled('queue') || request()->filled('sort');
@endphp

{{-- Header Tiêu Đề & Nút Hành Động --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-2 mb-1">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-primary-100 text-primary-800 uppercase tracking-wide">
                @if($user['role'] === 'student')
                    Cổng dịch vụ sinh viên
                @elseif($user['role'] === 'staff')
                    Bàn làm việc cán bộ
                @elseif($user['role'] === 'department_head')
                    Quản lý phòng ban
                @else
                    Quản trị hệ thống · Toàn trường
                @endif
            </span>
            <span class="text-xs text-slate-400">·</span>
            <span class="text-xs font-medium text-slate-500">Một Cửa Hỗ Trợ</span>
        </div>
        <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
            @if($user['role'] === 'student')
                Yêu cầu của tôi
            @elseif($user['role'] === 'staff')
                Công việc của tôi
            @elseif($user['role'] === 'department_head')
                Yêu cầu của phòng ban
            @else
                Quản lý yêu cầu
            @endif
        </h2>
        <p class="text-sm text-slate-500 mt-1">
            @if($user['role'] === 'student')
                Theo dõi tiến độ, trao đổi với cán bộ và đánh giá mức độ hài lòng về kết quả xử lý.
            @elseif($user['role'] === 'staff')
                Quản lý các hồ sơ yêu cầu hỗ trợ được phân công phụ trách xử lý.
            @elseif($user['role'] === 'department_head')
                Theo dõi tiến độ, thời hạn SLA và phân công cán bộ trong phòng ban.
            @else
                Quản lý toàn bộ quy trình yêu cầu hỗ trợ sinh viên trong hệ thống nhà trường.
            @endif
        </p>
    </div>

    <div class="flex items-center gap-2.5 flex-wrap">
        {{-- Nút Xuất Báo Cáo Excel Tổng Quan - Chỉ Quản trị viên và Trưởng phòng --}}
        @if($isHeadOrAdmin)
            <a href="{{ route('requests.export', request()->query()) }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-300 hover:border-emerald-500 hover:bg-emerald-50 text-slate-700 hover:text-emerald-700 text-sm font-semibold rounded-xl transition-all shadow-subtle group"
               title="Xuất file báo cáo Excel tổng quan chuẩn định dạng học vụ">
                <svg class="w-4 h-4 text-emerald-600 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>Xuất Excel</span>
            </a>
        @endif

        {{-- Nút Gửi Yêu Cầu Mới cho Sinh Viên --}}
        @if($user['role'] === 'student')
            <a href="{{ route('requests.create') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-xl transition-all shadow-md shadow-primary-500/20 hover:shadow-lg hover:shadow-primary-500/30">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Gửi yêu cầu mới</span>
            </a>
        @endif
    </div>
</div>

{{-- 5 THẺ THỐNG KÊ SLA & CHỈ SỐ HOẠT ĐỘNG - CHỈ DÀNH CHO ADMIN VÀ TRƯỞNG PHÒNG --}}
@if($isHeadOrAdmin)
<div class="mb-6">
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">
                {{ $user['role'] === 'admin' ? 'Tổng Quan Chỉ Số Tiếp Nhận & Đánh Giá CSAT Toàn Trường' : 'Tổng Quan Chỉ Số Tiếp Nhận Phòng Ban' }}
            </h3>
        </div>
        <span class="text-[11px] font-semibold text-slate-400">
            {{ $user['role'] === 'admin' ? 'Quyền Quản trị viên' : 'Quyền Lãnh đạo đơn vị' }}
        </span>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-5 gap-3.5">
        {{-- Card 1: Tổng số yêu cầu --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-subtle">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Tổng yêu cầu</span>
                <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </span>
            </div>
            <div class="text-2xl font-extrabold text-slate-900 leading-none">
                {{ $slaStats['total'] ?? $requests->total() }}
            </div>
            <p class="text-[11px] text-slate-400 mt-1.5">Trong hệ thống</p>
        </div>

        {{-- Card 2: Đang xử lý --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-subtle">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Đang xử lý</span>
                <span class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="text-2xl font-extrabold text-amber-600 leading-none">
                {{ $slaStats['in_progress'] ?? 0 }}
            </div>
            <p class="text-[11px] text-slate-400 mt-1.5">Cần cán bộ giải quyết</p>
        </div>

        {{-- Card 3: Đạt chuẩn SLA --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-subtle">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Tỷ lệ đúng hạn SLA</span>
                <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="text-2xl font-extrabold text-emerald-600 leading-none">
                {{ $slaStats['on_time_rate'] ?? 100 }}%
            </div>
            <div class="w-full bg-slate-100 rounded-full h-1.5 mt-2 overflow-hidden">
                <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ min(100, $slaStats['on_time_rate'] ?? 100) }}%"></div>
            </div>
        </div>

        {{-- Card 4: Cảnh báo / Quá hạn SLA --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-subtle">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Cảnh báo SLA</span>
                <span class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </span>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-extrabold text-rose-600 leading-none">
                    {{ $slaStats['breached'] ?? 0 }}
                </span>
                <span class="text-xs font-semibold text-rose-600">quá hạn</span>
            </div>
            <p class="text-[11px] text-amber-600 font-medium mt-1.5">
                Sắp quá hạn: <strong>{{ $slaStats['warning'] ?? 0 }}</strong> hồ sơ
            </p>
        </div>

        {{-- Card 5: Điểm đánh giá CSAT --}}
        <div class="col-span-2 md:col-span-1 bg-white rounded-2xl border border-slate-200 p-4 shadow-subtle">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Đánh giá CSAT</span>
                <span class="w-8 h-8 rounded-lg bg-amber-50 text-amber-500 flex items-center justify-center text-sm">
                    ★
                </span>
            </div>
            <div class="flex items-baseline gap-1.5">
                <span class="text-2xl font-extrabold text-amber-500 leading-none">
                    {{ $slaStats['avg_rating'] ?? '—' }}
                </span>
                <span class="text-xs text-slate-400 font-semibold">/ 5 sao</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-1.5">
                {{ $slaStats['rated_count'] ?? 0 }} lượt đánh giá
            </p>
        </div>
    </div>
</div>
@endif

{{-- KHUNG BỘ LỌC TÌM KIẾM (GIAO DIỆN XANH TRẮNG ĐẠI HỌC) --}}
<div class="bg-white rounded-2xl border border-slate-200 shadow-subtle p-4 sm:p-5 mb-6">
    <nav class="request-queues" aria-label="Nhóm công việc">
        @foreach($queueLabels as $key => $label)
            <a href="{{ route('requests.index', ['queue' => $key]) }}" @if(request('queue', 'all') === $key) aria-current="page" @endif>{{ $label }} <strong>{{ $queueCounts[$key] }}</strong></a>
        @endforeach
    </nav>
    <form method="GET">
        <input type="hidden" name="queue" value="{{ request('queue', 'all') }}">
        <div class="request-sort"><label for="inbox-sort">Sắp xếp theo</label><select id="inbox-sort" name="sort">
            @foreach(['priority' => 'Ưu tiên cao trước', 'deadline' => 'Hạn xử lý gần nhất', 'newest' => 'Mới nhất', 'oldest' => 'Cũ nhất'] as $key => $label)
                <option value="{{ $key }}" @selected(request('sort', 'priority') === $key)>{{ $label }}</option>
            @endforeach
        </select><span>Chọn nhóm việc hoặc kết hợp bộ lọc bên dưới.</span></div>
        <details class="request-filter-panel" @if($search || $statusFilter || $priorityFilter || $slaFlagFilter || $departmentFilter || $assignedToFilter || $fromFilter || $toFilter) open @endif>
        <summary>Tìm kiếm và lọc chi tiết <span>Mã yêu cầu, trạng thái, phòng ban, thời gian</span></summary>
        <div class="space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
                {{-- Tìm kiếm từ khóa --}}
                <div class="min-w-0">
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1 font-mono">Tìm kiếm</label>
                    <div class="relative">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" name="q" value="{{ $search ?? '' }}" placeholder="Mã yêu cầu, tiêu đề..."
                               class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 focus:bg-white transition">
                    </div>
                </div>

                {{-- Trạng thái --}}
                <div class="min-w-0">
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1 font-mono">Trạng thái</label>
                    <select name="status" class="w-full border border-slate-200 bg-slate-50 rounded-xl px-3 py-2 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 focus:bg-white transition cursor-pointer">
                        <option value="">Tất cả trạng thái</option>
                        @foreach($statusLabels as $k => $v)
                            <option value="{{ $k }}" @selected(($statusFilter ?? '') === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Mức ưu tiên --}}
                <div class="min-w-0">
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1 font-mono">Mức ưu tiên</label>
                    <select name="priority" class="w-full border border-slate-200 bg-slate-50 rounded-xl px-3 py-2 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 focus:bg-white transition cursor-pointer">
                        <option value="">Tất cả ưu tiên</option>
                        @foreach($priorityLabels as $k => $v)
                            <option value="{{ $k }}" @selected(($priorityFilter ?? '') === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Tình trạng SLA --}}
                <div class="min-w-0">
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1 font-mono">Tình trạng SLA</label>
                    <select name="sla_flag" class="w-full border border-slate-200 bg-slate-50 rounded-xl px-3 py-2 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 focus:bg-white transition cursor-pointer">
                        <option value="">Tất cả tiến độ</option>
                        <option value="on_time" @selected(($slaFlagFilter ?? '') === 'on_time')>Đúng hạn</option>
                        <option value="warning" @selected(($slaFlagFilter ?? '') === 'warning')>Sắp quá hạn (Cảnh báo)</option>
                        <option value="breached" @selected(($slaFlagFilter ?? '') === 'breached')>Đã quá hạn (Vi phạm)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end pt-1">
                @if($isHeadOrAdmin)
                    {{-- Phòng ban --}}
                    <div class="min-w-0">
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1 font-mono">Phòng ban</label>
                        <select name="department_id" class="w-full border border-slate-200 bg-slate-50 rounded-xl px-3 py-2 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 focus:bg-white transition cursor-pointer">
                            <option value="">Tất cả phòng ban</option>
                            @foreach(($departments ?? []) as $id => $name)
                                <option value="{{ $id }}" @selected((string)($departmentFilter ?? '') === (string)$id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Cán bộ phụ trách (Hiển thị tên) --}}
                    <div class="min-w-0">
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1 font-mono">Cán bộ phụ trách</label>
                        <select name="assigned_to" class="w-full border border-slate-200 bg-slate-50 rounded-xl px-3 py-2 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 focus:bg-white transition cursor-pointer">
                            <option value="">Tất cả cán bộ</option>
                            @foreach(($staffNames ?? []) as $sId => $sName)
                                <option value="{{ $sId }}" @selected((string)($assignedToFilter ?? '') === (string)$sId)>
                                    {{ $sName }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- Khoảng thời gian: Từ ngày --}}
                <div class="min-w-0">
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1 font-mono">Từ ngày</label>
                    <input type="date" name="from" value="{{ $fromFilter ?? '' }}"
                           class="w-full border border-slate-200 bg-slate-50 rounded-xl px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 focus:bg-white transition">
                </div>

                {{-- Khoảng thời gian: Đến ngày --}}
                <div class="min-w-0">
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1 font-mono">Đến ngày</label>
                    <input type="date" name="to" value="{{ $toFilter ?? '' }}"
                           class="w-full border border-slate-200 bg-slate-50 rounded-xl px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 focus:bg-white transition">
                </div>

                {{-- Nút bấm Lọc và Hủy Lọc --}}
                <div class="flex items-center gap-2">
                    <button type="submit" class="flex-1 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-xl transition shadow-xs">
                        Áp dụng lọc
                    </button>
                    @if($hasActiveFilter)
                        <a href="{{ route('requests.index') }}" class="px-3 py-2 text-sm text-slate-500 hover:text-rose-600 hover:bg-rose-50 border border-slate-200 rounded-xl transition font-medium">
                            Đặt lại
                        </a>
                    @endif
                </div>
            </div>
        </div>
        </details>
    </form>
</div>

{{-- BẢNG DỮ LIỆU DANH SÁCH YÊU CẦU --}}
<div class="bg-white rounded-2xl border border-slate-200 shadow-subtle overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between flex-wrap gap-2 bg-slate-50/50">
        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-primary-600"></span>
            <h3 class="font-bold text-slate-800 text-sm uppercase tracking-wide">
                Hồ sơ yêu cầu tiếp nhận
            </h3>
            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-primary-100 text-primary-800">
                {{ $requests->total() }} hồ sơ
            </span>
        </div>

        <div class="text-xs text-slate-500">
            Hiển thị trang {{ $requests->currentPage() }} / {{ max(1, $requests->lastPage()) }}
        </div>
    </div>

    <div class="overflow-x-auto">
        <table id="request-list" class="w-full text-left text-sm border-collapse">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-600">
                    <th class="px-5 py-3.5">Mã hồ sơ</th>
                    <th class="px-5 py-3.5">Tiêu đề & Loại hỗ trợ</th>
                    <th class="px-5 py-3.5">Phòng ban</th>
                    <th class="px-5 py-3.5">Cán bộ phụ trách</th>
                    <th class="px-5 py-3.5">Trạng thái</th>
                    <th class="px-5 py-3.5">Ưu tiên</th>
                    <th class="px-5 py-3.5">Hạn SLA</th>
                    <th class="px-5 py-3.5 text-center">Đánh giá</th>
                    <th class="px-5 py-3.5 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($requests as $req)
                    @php
                        $st = $req->status instanceof \App\Enums\RequestStatus ? $req->status->value : $req->status;
                        $pr = $req->priority instanceof \App\Enums\RequestPriority ? $req->priority->value : $req->priority;
                        $sla = $req->sla_flag instanceof \App\Enums\SlaFlag ? $req->sla_flag->value : $req->sla_flag;
                        $stName = $staffNames[$req->assigned_to] ?? $allUsers[$req->assigned_to]['full_name'] ?? ($req->assigned_staff_name ?? 'Chưa có tên cán bộ');
                    @endphp
                    <tr class="transition-colors hover:bg-slate-50/80 {{ $sla === 'breached' ? 'bg-rose-50/30' : ($pr === 'urgent' ? 'bg-amber-50/20' : '') }}">
                        {{-- Mã hồ sơ --}}
                        <td data-label="Mã yêu cầu" class="px-5 py-4 whitespace-nowrap">
                            <a href="{{ route('requests.show', $req) }}" class="font-mono font-bold text-primary-700 hover:text-primary-900 transition flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-primary-600"></span>
                                <span>{{ $req->code }}</span>
                            </a>
                        </td>

                        {{-- Tiêu đề & Loại hỗ trợ --}}
                        <td data-label="Nội dung" class="px-5 py-4 max-w-xs">
                            <a href="{{ route('requests.show', $req) }}" class="font-semibold text-slate-800 hover:text-primary-700 block truncate transition">
                                {{ $req->title }}
                            </a>
                            <span class="text-xs text-slate-500 block truncate mt-0.5">
                                {{ $supportTypes[$req->support_type_id]['name'] ?? 'Loại #'.$req->support_type_id }}
                            </span>
                        </td>

                        {{-- Phòng ban --}}
                        <td data-label="Phòng ban" class="px-5 py-4 whitespace-nowrap text-xs text-slate-600 font-medium">
                            {{ $deptNames[$req->department_id] ?? 'Phòng #'.$req->department_id }}
                        </td>

                        {{-- Cán bộ phụ trách (Hiển thị TÊN đầy đủ) --}}
                        <td data-label="Cán bộ phụ trách" class="px-5 py-4 whitespace-nowrap text-xs">
                            @if($req->assigned_to)
                                <div class="inline-flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-800 flex items-center justify-center text-[10px] font-bold shrink-0">
                                        {{ mb_substr($stName, 0, 1) }}
                                    </span>
                                    <div>
                                        <span class="font-semibold text-slate-800 block truncate max-w-[130px]">{{ $stName }}</span>

                                    </div>
                                </div>
                            @else
                                <span class="text-slate-400 italic text-xs">Chưa phân công</span>
                            @endif
                        </td>

                        {{-- Trạng thái badge --}}
                        <td data-label="Trạng thái" class="px-5 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border {{ $statusBadges[$st] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                                {{ $statusLabels[$st] ?? $st }}
                            </span>
                        </td>

                        {{-- Mức ưu tiên --}}
                        <td data-label="Ưu tiên" class="px-5 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border {{ $priorityBadges[$pr] ?? 'bg-slate-100 text-slate-600 border-slate-200' }}">
                                {{ $priorityLabels[$pr] ?? $pr }}
                            </span>
                        </td>

                        {{-- Hạn xử lý SLA --}}
                        <td data-label="Hạn xử lý" class="px-5 py-4 whitespace-nowrap text-xs">
                            @if($sla === 'breached')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                    Quá hạn SLA
                                </span>
                            @elseif($sla === 'warning')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                    Sắp quá hạn
                                </span>
                            @elseif($req->sla_deadline_at)
                                <span class="text-slate-600 font-mono text-[11px]" title="Hạn: {{ $req->sla_deadline_at->format('d/m/Y H:i') }}">
                                    {{ $req->sla_deadline_at->format('d/m H:i') }}
                                </span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>

                        {{-- Đánh giá sao --}}
                        <td data-label="Đánh giá" class="px-5 py-4 whitespace-nowrap text-center text-xs">
                            @if($req->rating)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full font-bold bg-amber-50 text-amber-700 border border-amber-200" title="{{ $req->rating_comment ?? '' }}">
                                    <span>★</span>
                                    <span>{{ $req->rating }}.0</span>
                                </span>
                            @elseif($st === 'closed')
                                <span class="text-[11px] text-slate-400 italic">Chưa đánh giá</span>
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>

                        {{-- Nút xem chi tiết --}}
                        <td data-label="Thao tác" class="px-5 py-4 whitespace-nowrap text-right">
                            <a href="{{ route('requests.show', $req) }}"
                               class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-primary-600 hover:text-white transition">
                                <span>Chi tiết</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center">
                            <div class="max-w-sm mx-auto space-y-3">
                                <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </div>
                                <p class="text-base font-bold text-slate-800">Không tìm thấy yêu cầu hỗ trợ nào</p>
                                <p class="text-xs text-slate-500">
                                    @if($hasActiveFilter)
                                        Không có hồ sơ nào khớp với bộ lọc hiện tại. Thử xóa lọc để xem toàn bộ danh sách.
                                    @else
                                        Hiện tại chưa có yêu cầu hỗ trợ nào được ghi nhận trong hệ thống.
                                    @endif
                                </p>
                                @if($hasActiveFilter)
                                    <a href="{{ route('requests.index') }}" class="inline-block text-xs font-semibold text-primary-600 hover:underline">
                                        Xóa tất cả bộ lọc
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Phân trang --}}
    @if($requests->hasPages())
        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50/50">
            {{ $requests->links() }}
        </div>
    @endif
</div>
@endsection
