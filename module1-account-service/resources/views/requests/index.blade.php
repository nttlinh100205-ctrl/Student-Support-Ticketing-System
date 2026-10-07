@extends('layouts.app')

@section('title', 'Danh sách yêu cầu — Module 3')

@section('content')
@php
    $statusLabels = [
        'new' => 'Mới tạo', 'received' => 'Đã tiếp nhận', 'in_progress' => 'Đang xử lý',
        'resolved' => 'Đã xử lý xong', 'closed' => 'Đã đóng', 'cancelled' => 'Đã hủy',
    ];
    $statusColors = [
        'new' => 'bg-sky-50 text-sky-700 ring-sky-200',
        'received' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
        'in_progress' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'resolved' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'closed' => 'bg-slate-100 text-slate-600 ring-slate-200',
        'cancelled' => 'bg-rose-50 text-rose-700 ring-rose-200',
    ];
    $priorityLabels = ['low' => 'Thấp', 'normal' => 'Bình thường', 'high' => 'Cao', 'urgent' => 'Khẩn cấp'];
    $priorityColors = [
        'low' => 'bg-slate-100 text-slate-600',
        'normal' => 'bg-sky-50 text-sky-700',
        'high' => 'bg-orange-50 text-orange-700',
        'urgent' => 'bg-red-100 text-red-700 ring-1 ring-red-200',
    ];
    $deptNames = [
        1 => 'Phòng Đào tạo', 2 => 'Phòng Công tác Sinh viên', 3 => 'Phòng Tài chính – Kế toán',
        4 => 'Thư viện', 5 => 'Trung tâm Hỗ trợ Sinh viên',
    ];

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

<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
    <div>
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Danh sách yêu cầu</h2>
        <p class="text-sm text-slate-500 mt-1">
            @if($user['role'] === 'student')
                Các yêu cầu bạn đã gửi
            @elseif($user['role'] === 'staff')
                Yêu cầu được gán cho bạn xử lý
            @elseif($user['role'] === 'department_head')
                Tất cả yêu cầu của phòng ban bạn phụ trách
            @else
                Tất cả yêu cầu trong hệ thống
            @endif
        </p>
    </div>
    @if($user['role'] === 'student')
        <a href="{{ route('requests.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition shadow-md shadow-indigo-600/20">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tạo yêu cầu
        </a>
    @endif
</div>

{{-- Filter + search --}}
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4 mb-5 flex flex-wrap items-center gap-3">
    <form method="GET" class="flex flex-wrap items-center gap-2 flex-1">
        <div class="relative flex-1 min-w-[200px] max-w-sm">
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" name="q" value="{{ $search ?? '' }}" placeholder="Tìm mã, tiêu đề, nội dung..."
                   class="w-full pl-9 pr-3 py-2 bg-slate-50 border-0 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <select name="status" class="border-0 bg-slate-50 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
            <option value="">Tất cả trạng thái</option>
            @foreach($statusLabels as $k => $v)
                <option value="{{ $k }}" @selected($statusFilter === $k)>{{ $v }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 bg-slate-800 text-white text-sm font-medium rounded-lg hover:bg-slate-900 transition">
            Tìm
        </button>
        @if(!empty($search) || !empty($statusFilter))
            <a href="{{ route('requests.index') }}" class="text-sm text-slate-500 hover:text-indigo-600 px-2">Xóa lọc</a>
        @endif
    </form>
    <span class="text-sm text-slate-400">
        <strong class="text-slate-700">{{ $requests->total() }}</strong> yêu cầu
    </span>
</div>

{{-- Khung riêng: yêu cầu KHẨN CẤP --}}
@if($urgentItems->isNotEmpty())
<div class="mb-5 rounded-2xl border-2 border-red-200 bg-red-50/60 overflow-hidden shadow-sm">
    <div class="px-5 py-3 border-b border-red-200/80 flex items-center gap-2 bg-red-50">
        <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
        <h3 class="text-sm font-bold text-red-800 uppercase tracking-wide">Yêu cầu khẩn cấp</h3>
        <span class="ml-auto text-xs font-semibold text-red-600 bg-white/80 px-2.5 py-0.5 rounded-full ring-1 ring-red-200">
            {{ $urgentItems->count() }}
        </span>
    </div>
    <div class="divide-y divide-red-100">
        @foreach($urgentItems as $r)
            @php
                $statusVal = $r->status instanceof \App\Enums\RequestStatus ? $r->status->value : $r->status;
            @endphp
            <a href="{{ route('requests.show', $r) }}"
               class="flex flex-wrap items-center gap-4 px-5 py-3.5 hover:bg-red-50/80 transition">
                <span class="font-mono text-sm font-semibold text-red-700">{{ $r->code }}</span>
                <span class="flex-1 min-w-0">
                    <span class="block font-medium text-slate-900 truncate">{{ $r->title }}</span>
                    <span class="text-xs text-slate-500">{{ $deptNames[$r->department_id] ?? 'Phòng #'.$r->department_id }}</span>
                </span>
                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusColors[$statusVal] ?? 'bg-slate-100' }}">
                    {{ $statusLabels[$statusVal] ?? $statusVal }}
                </span>
                <span class="text-xs text-slate-500 whitespace-nowrap">{{ $r->created_at?->format('d/m/Y H:i') }}</span>
            </a>
        @endforeach
    </div>
</div>
@endif

{{-- Danh sách còn lại (không gồm khẩn cấp trên trang này) --}}
@if($requests->isEmpty())
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-16 text-center">
        <p class="text-slate-600 font-medium">Chưa có yêu cầu nào</p>
        <p class="text-sm text-slate-400 mt-1">Danh sách trống theo bộ lọc hiện tại</p>
    </div>
@elseif($normalItems->isEmpty() && $urgentItems->isNotEmpty())
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-8 text-center text-sm text-slate-400">
        Không còn yêu cầu khác ngoài mục khẩn cấp phía trên.
    </div>
@else
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50/80 border-b border-slate-100 text-left text-slate-500 text-xs uppercase tracking-wider">
                    <th class="px-5 py-3.5 font-semibold">Mã</th>
                    <th class="px-5 py-3.5 font-semibold">Tiêu đề</th>
                    <th class="px-5 py-3.5 font-semibold">Ưu tiên</th>
                    <th class="px-5 py-3.5 font-semibold">Trạng thái</th>
                    <th class="px-5 py-3.5 font-semibold">Ngày tạo</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($normalItems as $r)
                    @php
                        $statusVal = $r->status instanceof \App\Enums\RequestStatus ? $r->status->value : $r->status;
                        $priorityVal = $r->priority instanceof \App\Enums\RequestPriority ? $r->priority->value : $r->priority;
                    @endphp
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-5 py-4">
                            <a href="{{ route('requests.show', $r) }}" class="font-mono text-indigo-600 hover:text-indigo-800 font-semibold text-[13px]">
                                {{ $r->code }}
                            </a>
                        </td>
                        <td class="px-5 py-4">
                            <a href="{{ route('requests.show', $r) }}" class="text-slate-900 hover:text-indigo-600 font-medium">
                                {{ $r->title }}
                            </a>
                            <p class="text-xs text-slate-400 mt-0.5">{{ $deptNames[$r->department_id] ?? 'Phòng #'.$r->department_id }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $priorityColors[$priorityVal] ?? 'bg-slate-100' }}">
                                {{ $priorityLabels[$priorityVal] ?? $priorityVal }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusColors[$statusVal] ?? 'bg-slate-100' }}">
                                {{ $statusLabels[$statusVal] ?? $statusVal }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-slate-500 whitespace-nowrap text-[13px]">
                            {{ $r->created_at?->format('d/m/Y H:i') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if($requests->hasPages())
            <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/50">
                {{ $requests->links() }}
            </div>
        @endif
    </div>
@endif
@endsection
