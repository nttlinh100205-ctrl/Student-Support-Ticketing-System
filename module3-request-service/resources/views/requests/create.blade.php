@extends('layouts.app')

@section('title', 'Tạo yêu cầu mới — Module 3')

@section('content')
<div class="max-w-2xl">
    <a href="{{ route('requests.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-blue-600 mb-5">
        ← Quay lại danh sách
    </a>

    <h2 class="text-2xl font-bold text-slate-900 mb-1">Tạo yêu cầu hỗ trợ mới</h2>
    <p class="text-sm text-slate-500 mb-6">Điền thông tin bên dưới. Yêu cầu sẽ được gửi đến phòng ban tương ứng.</p>

    @if($errors->any())
        <div class="bg-rose-50 border border-rose-200 text-rose-700 rounded-lg p-3 mb-5 text-sm">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('requests.store') }}" enctype="multipart/form-data"
          class="bg-white rounded-xl border border-slate-200 p-6 space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">
                Phòng ban nhận yêu cầu <span class="text-rose-500">*</span>
            </label>
            <select name="department_id" required id="department_id"
                    class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    onchange="filterSupportTypes()">
                <option value="">-- Chọn phòng ban --</option>
                @foreach($departments as $id => $name)
                    <option value="{{ $id }}" @selected(old('department_id') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">
                Loại hỗ trợ <span class="text-rose-500">*</span>
            </label>
            <select name="support_type_id" required id="support_type_id"
                    class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">-- Chọn loại hỗ trợ --</option>
                @foreach($supportTypes as $id => $type)
                    <option value="{{ $id }}" data-dept="{{ $type['department_id'] }}"
                            @selected(old('support_type_id') == $id)>
                        {{ $type['name'] }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">
                Tiêu đề <span class="text-rose-500">*</span>
            </label>
            <input type="text" name="title" required maxlength="255" value="{{ old('title') }}"
                   placeholder="VD: Xin xác nhận sinh viên để vay vốn"
                   class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">
                Nội dung chi tiết <span class="text-rose-500">*</span>
            </label>
            <textarea name="content" required rows="5" placeholder="Mô tả rõ nhu cầu hỗ trợ của bạn..."
                      class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none">{{ old('content') }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Mức ưu tiên</label>
            <div class="flex gap-3">
                @foreach(['low' => 'Thấp', 'normal' => 'Bình thường', 'high' => 'Cao', 'urgent' => 'Khẩn cấp'] as $val => $label)
                    <label class="flex-1 text-center border rounded-lg py-2 text-sm cursor-pointer transition has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 has-[:checked]:text-blue-700 has-[:checked]:font-medium border-slate-200 text-slate-600 hover:border-slate-300">
                        <input type="radio" name="priority" value="{{ $val }}" class="sr-only"
                               @checked(old('priority', 'normal') === $val)>
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Ảnh đính kèm — hiện khi chọn Phòng Cơ sở vật chất (id=6) --}}
        <div id="attachments_block" class="hidden">
            <label class="block text-sm font-medium text-slate-700 mb-1.5">
                Ảnh đính kèm <span class="text-rose-500">*</span>
            </label>
            <p class="text-xs text-slate-500 mb-2">Phản ánh CSVC cần ảnh minh họa (tối đa 5 ảnh, mỗi ảnh ≤ 5MB: jpg, png, webp).</p>
            <input type="file" name="attachments[]" id="attachments" accept="image/jpeg,image/png,image/webp,image/gif" multiple
                   class="w-full text-sm text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 file:font-medium hover:file:bg-indigo-100">
        </div>

        <div class="pt-2 flex gap-3">
            <button type="submit"
                    class="flex-1 bg-blue-600 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-blue-700 transition shadow-sm">
                Gửi yêu cầu
            </button>
            <a href="{{ route('requests.index') }}"
               class="px-5 py-2.5 border border-slate-200 rounded-lg text-sm text-slate-600 hover:bg-slate-50 transition">
                Hủy
            </a>
        </div>
    </form>
</div>

<script>
function filterSupportTypes() {
    const deptId = document.getElementById('department_id').value;
    const select = document.getElementById('support_type_id');
    Array.from(select.options).forEach((opt, i) => {
        if (i === 0) return;
        const show = !deptId || opt.dataset.dept === deptId;
        opt.hidden = !show;
        if (!show && opt.selected) opt.selected = false;
    });
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
}
document.addEventListener('DOMContentLoaded', filterSupportTypes);
</script>
@endsection
