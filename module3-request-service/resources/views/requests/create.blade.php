@extends('layouts.app')

@section('title', 'Tạo yêu cầu mới — Module 3')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    {{-- Breadcrumb / Back --}}
    <a href="{{ route('requests.index') }}"
       class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-textMuted hover:text-wood-800 transition group">
        <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Quay lại danh sách
    </a>

    {{-- Header --}}
    <div class="border-b border-borderWarm/80 pb-5">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-gold-50 border border-gold-200/60 text-gold-700 text-xs font-medium tracking-wide mb-3">
            <span class="w-1.5 h-1.5 rounded-full bg-gold-500"></span>
            Biểu mẫu trực tuyến · Phản hồi nhanh
        </div>
        <h2 class="text-3xl font-serif font-bold text-ink-900 tracking-tight">Tạo yêu cầu hỗ trợ mới</h2>
        <p class="text-sm text-textMuted mt-1">
            {{ $copyRequest ? 'Thông tin đã được sao chép từ yêu cầu trước. Bạn có thể tinh chỉnh trước khi gửi.' : 'Điền thông tin chi tiết bên dưới. Yêu cầu sẽ được chuyển đến cán bộ phòng ban phụ trách theo đúng quy trình chuẩn.' }}
        </p>
    </div>

    {{-- Duplicate Alert Banner --}}
    @if(session('possible_duplicates'))
        <div class="rounded-2xl border border-amber-300/80 bg-gradient-to-r from-amber-50 to-orange-50/50 p-5 shadow-warm text-sm text-amber-950" role="alert">
            <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-xl bg-amber-100 border border-amber-300 flex items-center justify-center shrink-0 text-amber-700 mt-0.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-amber-900">Có yêu cầu đang mở với tiêu đề tương tự:</p>
                    <ul class="mt-2 space-y-1.5">
                        @foreach(session('possible_duplicates') as $duplicate)
                            <li>
                                <a class="font-medium text-wood-800 underline hover:text-wood-950 transition inline-flex items-center gap-1.5" href="{{ route('requests.show', $duplicate) }}">
                                    <span class="font-mono text-xs font-bold text-gold-700">{{ $duplicate->code }}</span>
                                    <span>·</span>
                                    <span>{{ $duplicate->title }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-3 text-xs text-amber-800/90 leading-relaxed border-t border-amber-200/60 pt-2">
                        Kiểm tra danh sách trên trước khi tiếp tục. Nếu vẫn cần yêu cầu mới, hãy chọn nút xác nhận bên dưới.
                    </p>
                </div>
            </div>
        </div>
    @endif

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

    {{-- Main Form Card --}}
    <form method="POST" action="{{ route('requests.store') }}" enctype="multipart/form-data"
          class="bg-white rounded-2xl border border-borderWarm shadow-warm p-6 sm:p-8 space-y-6">
        @csrf

        {{-- Department --}}
        <div>
            <label for="department_id" class="block text-xs font-semibold uppercase tracking-wider text-textMuted mb-2">
                Phòng ban nhận yêu cầu <span class="text-rose-500">*</span>
            </label>
            <div class="relative">
                <select name="department_id" required id="department_id"
                        class="w-full appearance-none bg-paper/60 border border-borderWarm rounded-xl px-4 py-3 text-sm text-ink-900 focus:outline-none focus:ring-2 focus:ring-gold-500/30 focus:border-gold-500 focus:bg-white transition shadow-sm font-medium pr-10"
                        onchange="filterSupportTypes()">
                    <option value="">-- Chọn phòng ban --</option>
                    @foreach($departments as $id => $name)
                        <option value="{{ $id }}" @selected(old('department_id', $copyRequest?->department_id) == $id)>{{ $name }}</option>
                    @endforeach
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3.5 text-textMuted">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </div>
            <p class="text-[11px] text-textMuted mt-1.5">Lựa chọn đúng phòng ban để yêu cầu được giải quyết trong thời gian sớm nhất.</p>
        </div>

        {{-- Support Type --}}
        <div>
            <label for="support_type_id" class="block text-xs font-semibold uppercase tracking-wider text-textMuted mb-2">
                Loại hỗ trợ <span class="text-rose-500">*</span>
            </label>
            <div class="relative">
                <select name="support_type_id" required id="support_type_id"
                        @disabled(!old('department_id', $copyRequest?->department_id))
                        class="w-full appearance-none bg-paper/60 border border-borderWarm rounded-xl px-4 py-3 text-sm text-ink-900 focus:outline-none focus:ring-2 focus:ring-gold-500/30 focus:border-gold-500 focus:bg-white transition shadow-sm font-medium pr-10 disabled:opacity-50 disabled:bg-stone-100 disabled:cursor-not-allowed">
                    <option value="">{{ old('department_id', $copyRequest?->department_id) ? '-- Chọn loại hỗ trợ --' : '-- Chọn phòng ban trước --' }}</option>
                    @foreach($supportTypes as $id => $type)
                        <option value="{{ $id }}" data-dept="{{ $type['department_id'] }}"
                                data-template="{{ $type['content_template'] ?? '' }}"
                                @selected(old('support_type_id', $copyRequest?->support_type_id) == $id)>
                            {{ $type['name'] }}
                        </option>
                    @endforeach
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3.5 text-textMuted">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </div>
        </div>

        {{-- Title --}}
        <div>
            <label for="request_title" class="block text-xs font-semibold uppercase tracking-wider text-textMuted mb-2">
                Tiêu đề yêu cầu <span class="text-rose-500">*</span>
            </label>
            <input type="text" id="request_title" name="title" required maxlength="255" value="{{ old('title', $copyRequest?->title) }}"
                   placeholder="VD: Xin xác nhận sinh viên để vay vốn"
                   class="w-full bg-paper/60 border border-borderWarm rounded-xl px-4 py-3 text-sm text-ink-900 focus:outline-none focus:ring-2 focus:ring-gold-500/30 focus:border-gold-500 focus:bg-white transition shadow-sm font-medium placeholder:text-stone-400">
        </div>

        {{-- Content --}}
        <div>
            <div class="flex items-center justify-between mb-2">
                <label for="request_content" class="block text-xs font-semibold uppercase tracking-wider text-textMuted">
                    Nội dung chi tiết <span class="text-rose-500">*</span>
                </label>
                <span class="text-[11px] text-textMuted italic">Tự động gợi ý theo loại hỗ trợ</span>
            </div>
            <textarea id="request_content" name="content" required rows="6" placeholder="Chọn loại hỗ trợ để xem gợi ý nội dung..."
                      class="w-full bg-paper/60 border border-borderWarm rounded-xl p-4 text-sm text-ink-900 focus:outline-none focus:ring-2 focus:ring-gold-500/30 focus:border-gold-500 focus:bg-white transition shadow-sm resize-none leading-relaxed placeholder:text-stone-400">{{ old('content', $copyRequest?->content) }}</textarea>
        </div>

        {{-- Priority --}}
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-textMuted mb-2">Mức độ ưu tiên</label>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                @foreach(['low' => ['label' => 'Thấp', 'hint' => 'Không gấp'], 'normal' => ['label' => 'Bình thường', 'hint' => 'Tiêu chuẩn'], 'high' => ['label' => 'Cao', 'hint' => 'Cần sớm'], 'urgent' => ['label' => 'Khẩn cấp', 'hint' => 'Đặc biệt gấp']] as $val => $meta)
                    <label class="relative flex flex-col items-center justify-center border border-borderWarm rounded-xl p-3 text-center cursor-pointer transition bg-paper/40 hover:bg-paper hover:border-wood-400 has-[:checked]:border-wood-700 has-[:checked]:bg-wood-50 has-[:checked]:text-wood-900 has-[:checked]:ring-1 has-[:checked]:ring-wood-700 shadow-sm">
                        <input type="radio" name="priority" value="{{ $val }}" class="sr-only"
                               @checked(old('priority', $copyRequest?->priority?->value ?? 'normal') === $val)>
                        <span class="text-sm font-semibold">{{ $meta['label'] }}</span>
                        <span class="text-[11px] text-textMuted mt-0.5">{{ $meta['hint'] }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Attachments block — hiện khi chọn CSVC (id=6) --}}
        <div id="attachments_block" class="hidden rounded-xl border border-dashed border-borderWarm bg-paper/50 p-5 space-y-2">
            <label class="block text-xs font-semibold uppercase tracking-wider text-textMuted">
                Ảnh đính kèm thực tế <span class="text-rose-500">*</span>
            </label>
            <p class="text-xs text-textMuted">Phản ánh CSVC cần ảnh minh họa hiện trường (tối đa 5 ảnh, mỗi ảnh ≤ 5MB: jpg, png, webp).</p>
            <input type="file" name="attachments[]" id="attachments" accept="image/jpeg,image/png,image/webp,image/gif" multiple
                   class="w-full text-sm text-ink-900 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:bg-wood-700 file:text-white file:font-medium file:text-xs hover:file:bg-wood-800 transition cursor-pointer">
        </div>

        {{-- Actions --}}
        <div class="pt-4 border-t border-borderWarm/60 flex flex-col sm:flex-row gap-3">
            @if(session('possible_duplicates'))
                <button type="submit" name="confirm_duplicate" value="1"
                        class="flex-1 bg-gradient-to-r from-wood-800 to-wood-700 text-white py-3.5 px-6 rounded-xl text-sm font-semibold hover:from-wood-900 hover:to-wood-800 transition shadow-warm-md flex items-center justify-center gap-2">
                    <svg class="w-4 h-4 text-gold-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Vẫn tạo yêu cầu mới
                </button>
            @else
                <button type="submit"
                        class="flex-1 bg-gradient-to-r from-wood-800 to-wood-700 text-white py-3.5 px-6 rounded-xl text-sm font-semibold hover:from-wood-900 hover:to-wood-800 transition shadow-warm-md flex items-center justify-center gap-2">
                    <svg class="w-4 h-4 text-gold-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    Gửi yêu cầu
                </button>
            @endif
            <a href="{{ route('requests.index') }}"
               class="px-6 py-3.5 border border-borderWarm rounded-xl text-sm font-medium text-textMuted hover:text-ink-900 hover:bg-paper transition text-center">
                Hủy bỏ
            </a>
        </div>
    </form>
</div>

<script>
function filterSupportTypes() {
    const deptId = document.getElementById('department_id').value;
    const select = document.getElementById('support_type_id');
    const previousType = select.value;
    select.disabled = !deptId;
    select.options[0].textContent = deptId ? '-- Chọn loại hỗ trợ --' : '-- Chọn phòng ban trước --';
    Array.from(select.options).forEach((opt, i) => {
        if (i === 0) return;
        const show = Boolean(deptId) && opt.dataset.dept === deptId;
        opt.hidden = !show;
        if (!show && opt.selected) opt.selected = false;
    });
    if (select.value !== previousType) {
        document.querySelector('textarea[name="content"]').value = '';
    }
    // Phòng CSVC (id=6) → hiện upload ảnh
    const block = document.getElementById('attachments_block');
    const input = document.getElementById('attachments');
    if (deptId === '6') {
        block.classList.remove('hidden');
        input.required = true;
    } else {
        block.classList.add('hidden');
        input.required = false;
        input.value = '';
    }
    updateContentTemplate();
}

function updateContentTemplate() {
    const select = document.getElementById('support_type_id');
    const template = select.selectedOptions[0]?.dataset.template || '';
    const textarea = document.querySelector('textarea[name="content"]');

    textarea.placeholder = template || 'Chọn loại hỗ trợ để xem gợi ý nội dung...';
}

document.getElementById('support_type_id').addEventListener('change', () => {
    const textarea = document.querySelector('textarea[name="content"]');
    textarea.value = '';
    updateContentTemplate();
});
document.addEventListener('DOMContentLoaded', filterSupportTypes);
</script>
@endsection

