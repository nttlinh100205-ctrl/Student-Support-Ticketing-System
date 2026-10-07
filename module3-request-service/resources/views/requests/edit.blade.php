@extends('layouts.app')

@section('title', 'Sửa ' . $request->code . ' — Module 3')

@section('content')
@php
    $priorityVal = $request->priority instanceof \App\Enums\RequestPriority
        ? $request->priority->value
        : $request->priority;
    $priorityLabels = ['low' => 'Thấp', 'normal' => 'Bình thường', 'high' => 'Cao', 'urgent' => 'Khẩn cấp'];
@endphp
<div class="max-w-3xl mx-auto space-y-6">
    {{-- Breadcrumb / Back --}}
    <a href="{{ route('requests.show', $request) }}"
       class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-textMuted hover:text-wood-800 transition group">
        <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Quay lại chi tiết
    </a>

    {{-- Header --}}
    <div class="border-b border-borderWarm/80 pb-5">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-gold-50 border border-gold-200/60 text-gold-700 text-xs font-medium tracking-wide mb-3">
            <span class="w-1.5 h-1.5 rounded-full bg-gold-500"></span>
            Chỉnh sửa yêu cầu đang mở
        </div>
        <div class="flex items-center gap-3">
            <span class="font-mono text-sm font-bold text-gold-600 bg-gold-50/80 px-2.5 py-1 rounded-lg border border-gold-200/60">{{ $request->code }}</span>
            <h2 class="text-3xl font-serif font-bold text-ink-900 tracking-tight">Sửa yêu cầu {{ $request->code }}</h2>
        </div>
        <p class="text-sm text-textMuted mt-1">
            Chỉ cho phép cập nhật <strong>tiêu đề</strong> và <strong>nội dung</strong> khi yêu cầu ở trạng thái <strong>Mới tạo</strong>. Phòng ban và loại hỗ trợ được giữ nguyên nhằm đảm bảo luồng phân công.
        </p>
    </div>

    {{-- Errors --}}
    @if($errors->any())
        <div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl p-4 text-sm shadow-warm">
            <div class="font-semibold mb-1 flex items-center gap-2">
                <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Vui lòng kiểm tra lại thông tin:
            </div>
            <ul class="list-disc list-inside space-y-1 text-xs text-rose-700 ml-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Locked Metadata (Read-only) --}}
    <div class="bg-paper/70 rounded-2xl border border-borderWarm p-5 grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm shadow-warm">
        <div>
            <p class="text-xs uppercase tracking-wider font-semibold text-textMuted mb-0.5">Phòng ban</p>
            <p class="font-semibold text-ink-900">{{ $departments[$request->department_id] ?? '#'.$request->department_id }}</p>
        </div>
        <div>
            <p class="text-xs uppercase tracking-wider font-semibold text-textMuted mb-0.5">Loại hỗ trợ</p>
            <p class="font-semibold text-ink-900">{{ $supportTypes[$request->support_type_id]['name'] ?? '#'.$request->support_type_id }}</p>
        </div>
        <div>
            <p class="text-xs uppercase tracking-wider font-semibold text-textMuted mb-0.5">Mức ưu tiên</p>
            <p class="font-semibold text-ink-900">{{ $priorityLabels[$priorityVal] ?? $priorityVal }}</p>
        </div>
    </div>

    {{-- Main Edit Form Card --}}
    <form method="POST" action="{{ route('requests.update', $request) }}" class="bg-white rounded-2xl border border-borderWarm shadow-warm p-6 sm:p-8 space-y-6">
        @csrf
        @method('PUT')

        {{-- Title --}}
        <div>
            <label for="request_title" class="block text-xs font-semibold uppercase tracking-wider text-textMuted mb-2">
                Tiêu đề <span class="text-rose-500">*</span>
            </label>
            <input type="text" id="request_title" name="title" required maxlength="255" minlength="10"
                   value="{{ old('title', $request->title) }}"
                   placeholder="VD: Xin xác nhận sinh viên để vay vốn"
                   class="w-full bg-paper/60 border border-borderWarm rounded-xl px-4 py-3 text-sm text-ink-900 focus:outline-none focus:ring-2 focus:ring-gold-500/30 focus:border-gold-500 focus:bg-white transition shadow-sm font-medium">
        </div>

        {{-- Content --}}
        <div>
            <label for="request_content" class="block text-xs font-semibold uppercase tracking-wider text-textMuted mb-2">
                Nội dung chi tiết <span class="text-rose-500">*</span>
            </label>
            <textarea id="request_content" name="content" required rows="6" minlength="20" placeholder="Mô tả rõ nhu cầu hỗ trợ của bạn..."
                      class="w-full bg-paper/60 border border-borderWarm rounded-xl p-4 text-sm text-ink-900 focus:outline-none focus:ring-2 focus:ring-gold-500/30 focus:border-gold-500 focus:bg-white transition shadow-sm resize-none leading-relaxed">{{ old('content', $request->content) }}</textarea>
        </div>

        {{-- Actions --}}
        <div class="pt-4 border-t border-borderWarm/60 flex flex-col sm:flex-row gap-3">
            <button type="submit"
                    class="flex-1 bg-gradient-to-r from-wood-800 to-wood-700 text-white py-3.5 px-6 rounded-xl text-sm font-semibold hover:from-wood-900 hover:to-wood-800 transition shadow-warm-md flex items-center justify-center gap-2">
                <svg class="w-4 h-4 text-gold-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Lưu thay đổi
            </button>
            <a href="{{ route('requests.show', $request) }}"
               class="px-6 py-3.5 border border-borderWarm rounded-xl text-sm font-medium text-textMuted hover:text-ink-900 hover:bg-paper transition text-center">
                Hủy
            </a>
        </div>
    </form>
</div>
@endsection

