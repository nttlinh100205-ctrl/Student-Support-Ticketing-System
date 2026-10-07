@extends('layouts.app')

@section('title', 'Sửa ' . $request->code . ' — Module 3')

@section('content')
@php
    $priorityVal = $request->priority instanceof \App\Enums\RequestPriority
        ? $request->priority->value
        : $request->priority;
    $priorityLabels = ['low' => 'Thấp', 'normal' => 'Bình thường', 'high' => 'Cao', 'urgent' => 'Khẩn cấp'];
@endphp
<div class="max-w-2xl">
    <a href="{{ route('requests.show', $request) }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-blue-600 mb-5">
        ← Quay lại chi tiết
    </a>

    <h2 class="text-2xl font-bold text-slate-900 mb-1">Sửa yêu cầu {{ $request->code }}</h2>
    <p class="text-sm text-slate-500 mb-6">
        Chỉ sửa <strong>tiêu đề</strong> và <strong>nội dung</strong> khi còn trạng thái <strong>Mới tạo</strong>.
        Phòng ban / loại hỗ trợ không đổi (tránh lệch cán bộ xử lý).
    </p>

    @if($errors->any())
        <div class="bg-rose-50 border border-rose-200 text-rose-700 rounded-lg p-3 mb-5 text-sm">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Thông tin khóa (chỉ xem) --}}
    <div class="bg-slate-50 rounded-xl border border-slate-200 p-4 mb-5 grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
        <div>
            <p class="text-xs text-slate-400">Phòng ban</p>
            <p class="font-medium text-slate-800">{{ $departments[$request->department_id] ?? '#'.$request->department_id }}</p>
        </div>
        <div>
            <p class="text-xs text-slate-400">Loại hỗ trợ</p>
            <p class="font-medium text-slate-800">{{ $supportTypes[$request->support_type_id]['name'] ?? '#'.$request->support_type_id }}</p>
        </div>
        <div>
            <p class="text-xs text-slate-400">Mức ưu tiên</p>
            <p class="font-medium text-slate-800">{{ $priorityLabels[$priorityVal] ?? $priorityVal }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('requests.update', $request) }}" class="bg-white rounded-xl border border-slate-200 p-6 space-y-5">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">
                Tiêu đề <span class="text-rose-500">*</span>
            </label>
            <input type="text" name="title" required maxlength="255" minlength="10"
                   value="{{ old('title', $request->title) }}"
                   placeholder="VD: Xin xác nhận sinh viên để vay vốn"
                   class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">
                Nội dung chi tiết <span class="text-rose-500">*</span>
            </label>
            <textarea name="content" required rows="5" minlength="20" placeholder="Mô tả rõ nhu cầu hỗ trợ của bạn..."
                      class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none">{{ old('content', $request->content) }}</textarea>
        </div>

        <div class="pt-2 flex gap-3">
            <button type="submit"
                    class="flex-1 bg-blue-600 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-blue-700 transition shadow-sm">
                Lưu thay đổi
            </button>
            <a href="{{ route('requests.show', $request) }}"
               class="px-5 py-2.5 border border-slate-200 rounded-lg text-sm text-slate-600 hover:bg-slate-50 transition">
                Hủy
            </a>
        </div>
    </form>
</div>
@endsection
