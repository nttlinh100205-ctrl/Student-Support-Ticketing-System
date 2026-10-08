@extends('layouts.app')

@section('title', 'Tạo Yêu Cầu Hỗ Trợ Mới — Cổng Dịch Vụ Sinh Viên')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    {{-- Breadcrumb / Back --}}
    <a href="{{ route('requests.index') }}"
       class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-500 hover:text-primary-600 transition group">
        <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Quay lại danh sách
    </a>

    {{-- Header --}}
    <div class="border-b border-slate-200 pb-5">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary-50 border border-primary-200 text-primary-700 text-xs font-bold tracking-wide mb-3">
            <span class="w-1.5 h-1.5 rounded-full bg-primary-600"></span>
            Tiếp nhận một cửa trực tuyến · Phản hồi theo SLA
        </div>
        <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Tạo yêu cầu hỗ trợ mới</h2>
        <p class="text-sm text-slate-500 mt-1">
            {{ $copyRequest ? 'Thông tin đã được sao chép từ yêu cầu trước. Bạn có thể tinh chỉnh nội dung trước khi gửi tiếp nhận.' : 'Điền thông tin chi tiết bên dưới. Hồ sơ sẽ được tự động phân luồng tới phòng ban phụ trách theo đúng quy trình học vụ.' }}
        </p>
    </div>

    {{-- Duplicate Alert Banner --}}
    @if(session('possible_duplicates'))
        <div class="rounded-2xl border border-amber-300 bg-amber-50/90 p-5 shadow-subtle text-sm text-amber-950" role="alert">
            <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-xl bg-amber-100 border border-amber-300 flex items-center justify-center shrink-0 text-amber-700 mt-0.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="font-bold text-amber-900">Phát hiện hồ sơ đang mở có nội dung hoặc tiêu đề tương tự:</p>
                    <ul class="mt-2 space-y-1.5">
                        @foreach(session('possible_duplicates') as $duplicate)
                            <li>
                                <a class="font-semibold text-primary-700 underline hover:text-primary-900 transition inline-flex items-center gap-1.5" href="{{ route('requests.show', $duplicate) }}">
                                    <span class="font-mono text-xs font-bold text-primary-800 bg-primary-100 px-1.5 py-0.5 rounded">{{ $duplicate->code }}</span>
                                    <span>·</span>
                                    <span>{{ $duplicate->title }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-3 text-xs text-amber-800 leading-relaxed border-t border-amber-200/80 pt-2">
                        Kiểm tra danh sách trên trước khi tạo mới để tránh trùng lặp hồ sơ. Nếu bạn vẫn muốn tạo mới, hãy bấm nút "Vẫn tạo yêu cầu mới" phía dưới.
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Errors --}}
    @if($errors->any())
        <div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl p-4 text-sm shadow-subtle">
            <div class="font-bold mb-1 flex items-center gap-2">
                <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Vui lòng kiểm tra lại thông tin biểu mẫu:
            </div>
            <ul class="list-disc list-inside space-y-1 text-xs text-rose-700 ml-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <aside class="request-help"><strong>Gửi đúng thông tin, nhận hỗ trợ nhanh hơn</strong><p>Chọn phòng ban và loại hỗ trợ, mô tả vấn đề cùng kết quả mong muốn. Bạn có thể đính kèm ảnh minh chứng và theo dõi phản hồi trong chi tiết yêu cầu.</p></aside>
    {{-- Main Form Card --}}
    <form id="request-stepper" method="POST" action="{{ route('requests.store') }}" enctype="multipart/form-data"
          class="bg-white rounded-2xl border border-slate-200 shadow-subtle p-6 sm:p-8 space-y-6">
        @csrf

        <nav class="uni-stepper" aria-label="Các bước gửi yêu cầu" hidden><button type="button" data-go-step="0">1. Loại hỗ trợ</button><button type="button" data-go-step="1">2. Nội dung</button><button type="button" data-go-step="2">3. Minh chứng</button><button type="button" data-go-step="3">4. Xác nhận</button></nav>
        <section class="uni-step-panel" data-step="0" aria-label="Chọn loại hỗ trợ">
        {{-- Department --}}
        <div>
            <label for="department_id" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">
                Phòng ban tiếp nhận <span class="text-rose-500">*</span>
            </label>
            <div class="relative">
                <select name="department_id" required id="department_id"
                        class="w-full appearance-none bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 focus:bg-white transition shadow-sm font-medium pr-10"
                        onchange="filterSupportTypes()">
                    <option value="">-- Chọn phòng ban --</option>
                    @foreach($departments as $id => $name)
                        <option value="{{ $id }}" @selected(old('department_id', $copyRequest?->department_id) == $id)>{{ $name }}</option>
                    @endforeach
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3.5 text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </div>
            <p class="text-[11px] text-slate-400 mt-1.5">Lựa chọn đúng phòng ban để yêu cầu được giải quyết trong thời gian sớm nhất.</p>
        </div>

        <x-field-error name="department_id"/>{{-- Support Type --}}
        <div>
            <label for="support_type_id" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">
                Loại nghiệp vụ hỗ trợ <span class="text-rose-500">*</span>
            </label>
            <div class="relative">
                <select name="support_type_id" required id="support_type_id"
                        @disabled(!old('department_id', $copyRequest?->department_id))
                        class="w-full appearance-none bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 focus:bg-white transition shadow-sm font-medium pr-10 disabled:opacity-50 disabled:bg-slate-100 disabled:cursor-not-allowed">
                    <option value="">{{ old('department_id', $copyRequest?->department_id) ? '-- Chọn loại hỗ trợ --' : '-- Chọn phòng ban trước --' }}</option>
                    @foreach($supportTypes as $id => $type)
                        <option value="{{ $id }}" data-dept="{{ $type['department_id'] }}"
                                data-template="{{ $type['content_template'] ?? '' }}" data-description="{{ $type['description'] ?? '' }}" data-sla="{{ $type['sla_days'] ?? '' }}"
                                @selected(old('support_type_id', $copyRequest?->support_type_id) == $id)>
                            {{ $type['name'] }}
                        </option>
                    @endforeach
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3.5 text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </div>
        </div>

        <x-field-error name="support_type_id"/></section><section class="uni-step-panel" data-step="1" aria-label="Nội dung yêu cầu">
        <div id="support-guidance" class="request-help" hidden></div>
        <button type="button" id="use-content-template" class="request-secondary" hidden>Điền mẫu nội dung</button>
        {{-- Title --}}
        <div>
            <label for="request_title" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">
                Tiêu đề yêu cầu <span class="text-rose-500">*</span>
            </label>
            <input type="text" id="request_title" name="title" required minlength="10" maxlength="255" value="{{ old('title', $copyRequest?->title) }}"
                   placeholder="VD: Xin giấy xác nhận sinh viên để vay vốn ngân hàng chính sách"
                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 focus:bg-white transition shadow-sm font-medium placeholder:text-slate-400">
        </div>

        <x-field-error name="title"/>{{-- Content --}}
        <div>
            <div class="flex items-center justify-between mb-2">
                <label for="request_content" class="block text-xs font-bold uppercase tracking-wider text-slate-600">
                    Nội dung chi tiết <span class="text-rose-500">*</span>
                </label>
                <span class="text-[11px] text-slate-400 italic">Mẫu gợi ý theo loại hỗ trợ</span>
            </div>
            <textarea id="request_content" name="content" required minlength="20" rows="6" placeholder="Chọn loại hỗ trợ để xem gợi ý nội dung..."
                      class="w-full bg-slate-50 border border-slate-200 rounded-xl p-4 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 focus:bg-white transition shadow-sm resize-none leading-relaxed placeholder:text-slate-400">{{ old('content', $copyRequest?->content) }}</textarea>
        </div>

        <x-field-error name="content"/>{{-- Priority --}}
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">Mức độ ưu tiên</label>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                @foreach(['low' => ['label' => 'Thấp', 'hint' => 'Không gấp'], 'normal' => ['label' => 'Bình thường', 'hint' => 'Tiêu chuẩn SLA'], 'high' => ['label' => 'Cao', 'hint' => 'Cần xử lý sớm'], 'urgent' => ['label' => 'Khẩn cấp', 'hint' => 'Đặc biệt gấp']] as $val => $meta)
                    <label class="relative flex flex-col items-center justify-center border border-slate-200 rounded-xl p-3 text-center cursor-pointer transition bg-slate-50/50 hover:bg-slate-50 hover:border-primary-300 has-[:checked]:border-primary-600 has-[:checked]:bg-primary-50 has-[:checked]:text-primary-900 has-[:checked]:ring-1 has-[:checked]:ring-primary-600 shadow-xs">
                        <input type="radio" name="priority" value="{{ $val }}" class="sr-only"
                               @checked(old('priority', $copyRequest?->priority?->value ?? 'normal') === $val)>
                        <span class="text-sm font-bold">{{ $meta['label'] }}</span>
                        <span class="text-[11px] text-slate-500 mt-0.5">{{ $meta['hint'] }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        </section><section class="uni-step-panel" data-step="2" aria-label="Tài liệu minh chứng">
        {{-- Attachments block — hiện khi chọn CSVC (id=6) --}}
        <div id="attachments_block" class="rounded-xl border border-dashed border-slate-300 bg-slate-50/80 p-5 space-y-2">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                Ảnh minh chứng <span id="attachment-required" class="text-rose-500" hidden>*</span>
            </label>
            <p class="text-xs text-slate-500">Đính kèm tối đa 5 ảnh, mỗi ảnh tối đa 5MB (jpg, png, webp, gif). Bắt buộc có ảnh đối với phản ánh cơ sở vật chất.</p>
            <input type="file" name="attachments[]" id="attachments" accept="image/jpeg,image/png,image/webp,image/gif" multiple
                   class="w-full text-sm text-slate-900 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:bg-primary-600 file:text-white file:font-bold file:text-xs hover:file:bg-primary-700 transition cursor-pointer">
        </div>

        <x-field-error name="attachments"/><x-field-error name="attachments.*"/><div id="attachment-previews" class="uni-file-preview" aria-live="polite"></div><p class="text-sm text-slate-500">Kéo thả ảnh vào vùng đính kèm hoặc bấm chọn tệp.</p>
        </section><section class="uni-step-panel" data-step="3" aria-label="Xác nhận gửi"><div class="uni-review" id="request-review" hidden></div>
        {{-- Actions --}}
        <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row gap-3">
            @if(session('possible_duplicates'))
                <button type="submit" name="confirm_duplicate" value="1"
                        class="flex-1 bg-primary-600 hover:bg-primary-700 text-white py-3.5 px-6 rounded-xl text-sm font-bold transition shadow-md shadow-primary-500/20 flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Vẫn tạo yêu cầu mới
                </button>
            @else
                <button type="submit"
                        class="flex-1 bg-primary-600 hover:bg-primary-700 text-white py-3.5 px-6 rounded-xl text-sm font-bold transition shadow-md shadow-primary-500/20 flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    Gửi yêu cầu hỗ trợ
                </button>
            @endif
            <a href="{{ route('requests.index') }}"
               class="px-6 py-3.5 border border-slate-200 rounded-xl text-sm font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition text-center">
                Hủy bỏ
            </a>
        </div>
        </section><div class="uni-step-controls" hidden><button type="button" class="uni-button secondary" id="step-back">Quay lại</button><button type="button" class="uni-button" id="step-next">Tiếp tục →</button></div>
    </form><script src="/js/request-stepper.js" defer></script>
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
    // Phòng CSVC lấy theo danh mục đang sử dụng.
    const block = document.getElementById('attachments_block');
    const input = document.getElementById('attachments');
    input.required = deptId === String(@json(config('master_data.facilities_department_id', 6)));
    document.getElementById('attachment-required').hidden = !input.required;
    updateContentTemplate();
}

function updateContentTemplate() {
    const select = document.getElementById('support_type_id');
    const template = select.selectedOptions[0]?.dataset.template || '';
    const textarea = document.querySelector('textarea[name="content"]');

    textarea.placeholder = template || 'Mô tả vấn đề, thời gian xảy ra và kết quả bạn mong muốn (ít nhất 20 ký tự).';
    document.getElementById('use-content-template').hidden = !template;
    const guidance = document.getElementById('support-guidance');
    const selected = select.selectedOptions[0];
    guidance.textContent = [selected?.dataset.description, selected?.dataset.sla ? `Thời hạn xử lý theo danh mục: ${selected.dataset.sla} ngày kể từ khi gửi.` : ''].filter(Boolean).join(' ');
    guidance.hidden = !guidance.textContent;
}

document.getElementById('support_type_id').addEventListener('change', updateContentTemplate);
document.getElementById('use-content-template').addEventListener('click', () => {
    const textarea = document.getElementById('request_content');
    if (textarea.value.trim() && !confirm('Thay nội dung đang nhập bằng mẫu hướng dẫn?')) return;
    textarea.value = document.getElementById('support_type_id').selectedOptions[0]?.dataset.template || '';
    textarea.focus();
});
document.getElementById('attachments').addEventListener('change', (event) => {
    const input = event.target;
    input.setCustomValidity(input.files.length > 5 ? 'Chỉ được chọn tối đa 5 ảnh.' : [...input.files].some(file => file.size > 5 * 1024 * 1024) ? 'Mỗi ảnh phải nhỏ hơn hoặc bằng 5MB.' : '');
    input.reportValidity();
});
document.addEventListener('DOMContentLoaded', filterSupportTypes);
</script>
@endsection
