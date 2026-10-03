@extends('layouts.app')
@section('title', 'Tra cứu hỗ trợ')
@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold">Tra cứu hỗ trợ</h1>
        <p class="text-slate-500 mt-1">
            Chọn loại yêu cầu để xem thời gian xử lý, thông tin cần chuẩn bị
            và câu hỏi thường gặp trước khi gửi yêu cầu.
        </p>
    </div>

    <p role="status" aria-live="polite" id="message" class="text-sm"></p>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Chọn phòng ban và loại hỗ trợ --}}
        <div class="bg-white rounded-xl border p-5 space-y-4 lg:col-span-1">
            <div>
                <label for="departmentSelect" class="block mb-1 font-medium">Phòng ban</label>
                <select id="departmentSelect" class="w-full border rounded-lg p-2">
                    <option value="">-- Tất cả phòng ban --</option>
                </select>
            </div>

            <div>
                <label for="typeSearch" class="block mb-1 font-medium">Loại hỗ trợ</label>
                <input id="typeSearch" maxlength="150" placeholder="Tìm theo tên hoặc mã"
                       class="w-full border rounded-lg p-2">
            </div>

            <ul id="typeList" class="space-y-2 max-h-[28rem] overflow-y-auto"></ul>
        </div>

        {{-- Chi tiết loại hỗ trợ --}}
        <div class="space-y-6 lg:col-span-2">
            <div id="typeDetail" hidden class="bg-white rounded-xl border p-5 space-y-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 id="typeName" class="text-lg font-semibold break-words"></h2>
                        <p id="typeDepartment" class="text-sm text-slate-500"></p>
                    </div>
                    <span id="typeSla" class="text-sm font-medium px-3 py-1 rounded-full"></span>
                </div>
                <p id="typeDescription" class="text-sm text-slate-600 whitespace-pre-line"></p>

                <div class="border-t pt-4 space-y-3">
                    <h3 class="font-semibold">Thông tin và giấy tờ cần chuẩn bị</h3>
                    <p class="text-xs text-slate-500">
                        Trường có dấu <span class="text-red-600">*</span> là bắt buộc.
                        Bấm "Kiểm tra thông tin" để biết còn thiếu gì trước khi gửi yêu cầu.
                    </p>
                    <form id="valueForm" class="space-y-3" novalidate></form>
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="button" id="checkButton"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-lg disabled:opacity-50">
                            Kiểm tra thông tin
                        </button>
                        <span id="checkResult" role="status" aria-live="polite" class="text-sm"></span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border p-5 space-y-3">
                <h2 id="faqTitle" class="font-semibold">Câu hỏi thường gặp</h2>
                <p id="faqHint" class="text-sm text-slate-500">
                    Chọn phòng ban hoặc loại hỗ trợ để xem câu hỏi thường gặp.
                </p>
                <div id="faqList" class="space-y-2"></div>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const el = id => document.getElementById(id);
    let supportTypes = [];
    let currentType = null;
    let currentFields = [];
    let typeRequest = 0;
    let faqRequest = 0;

    function notify(text, error = false) {
        el('message').textContent = text;
        el('message').className = error ? 'text-sm text-red-600' : 'text-sm text-emerald-700';
    }

    async function api(url, options = {}) {
        const response = await fetch(url, {
            ...options,
            headers: { Accept: 'application/json', ...DemoAuth.headers(), ...(options.headers || {}) }
        });
        const data = await response.json().catch(() => null);
        if (response.status === 401) throw new Error('Chưa xác thực. Hãy chọn vai trò ở thanh bên.');
        if (!response.ok) {
            const error = new Error(data?.message || `Không thể xử lý yêu cầu (${response.status}).`);
            error.errors = data?.errors || {};
            error.status = response.status;
            throw error;
        }

        
            // Danh sách phân trang được bọc trong "data" (API contract) -> mở lớp bọc.
        if (data && data.data && Array.isArray(data.data.data)) {
            return data.data;
        }
        return data;
    }




    async function loadAllPages(url) {
        const items = [];
        let page = 1, last = 1;
        do {
            const address = new URL(url, window.location.origin);
            address.searchParams.set('page', String(page));
            const result = await api(address.pathname + address.search);
            items.push(...(result.data || []));
            last = Number(result.last_page) || 1;
            page++;
        } while (page <= last);
        return items;
    }

    function slaText(days) {
        return days == null ? 'Chưa quy định thời gian xử lý' : `Xử lý dự kiến: ${days} ngày`;
    }

    /* ---------- Danh sách loại hỗ trợ ---------- */

    function renderTypes() {
        const keyword = el('typeSearch').value.trim().toLowerCase();
        const list = supportTypes.filter(item =>
            !keyword || item.name.toLowerCase().includes(keyword) || item.code.toLowerCase().includes(keyword));

        el('typeList').replaceChildren();

        if (!list.length) {
            const empty = document.createElement('li');
            empty.className = 'text-sm text-slate-500';
            empty.textContent = 'Không có loại hỗ trợ phù hợp.';
            el('typeList').appendChild(empty);
            return;
        }

        list.forEach(item => {
            const li = document.createElement('li');
            const button = document.createElement('button');
            const active = currentType && currentType.id === item.id;
            button.type = 'button';
            button.className = 'w-full text-left border rounded-lg p-3 transition '
                + (active ? 'border-indigo-500 bg-indigo-50' : 'hover:bg-slate-50');
            const name = document.createElement('div');
            name.className = 'font-medium text-sm break-words';
            name.textContent = item.name;
            const meta = document.createElement('div');
            meta.className = 'text-xs text-slate-500 mt-1';
            meta.textContent = `${item.department?.name ?? ''} · ${slaText(item.sla_days)}`;
            button.append(name, meta);
            button.addEventListener('click', () => selectType(item));
            li.appendChild(button);
            el('typeList').appendChild(li);
        });
    }

    async function loadTypes() {
        const current = ++typeRequest;
        const department = el('departmentSelect').value;
        const url = '/api/v1/catalog/support-types' + (department ? `?department_id=${department}` : '');
        el('typeList').replaceChildren();
        try {
            const list = await loadAllPages(url);
            if (current !== typeRequest) return;
            supportTypes = list;
            renderTypes();
        } catch (error) {
            if (current === typeRequest) notify(error.message, true);
        }
    }

    /* ---------- Biểu mẫu ---------- */

    function fieldInput(field) {
        const id = `field_${field.field_key}`;
        let input;

        if (field.field_type === 'textarea') {
            input = document.createElement('textarea');
            input.rows = 3;
        } else if (field.field_type === 'select') {
            input = document.createElement('select');
            input.appendChild(new Option('-- Chọn --', ''));
            (field.options || []).forEach(option => input.appendChild(new Option(option, option)));
        } else {
            input = document.createElement('input');
            input.type = { number: 'number', date: 'date', file: 'file' }[field.field_type] || 'text';
        }

        input.id = id;
        input.name = field.field_key;
        input.className = 'w-full border rounded-lg p-2 text-sm';
        return input;
    }

    function renderFields(fields) {
        const form = el('valueForm');
        form.replaceChildren();

        if (!fields.length) {
            const empty = document.createElement('p');
            empty.className = 'text-sm text-slate-500';
            empty.textContent = 'Loại hỗ trợ này không yêu cầu thông tin bổ sung.';
            form.appendChild(empty);
            el('checkButton').hidden = true;
            return;
        }

        el('checkButton').hidden = false;

        fields.forEach(field => {
            const wrapper = document.createElement('div');
            const label = document.createElement('label');
            label.htmlFor = `field_${field.field_key}`;
            label.className = 'block text-sm font-medium mb-1';
            label.textContent = field.label + (field.field_type === 'file' ? ' (tệp đính kèm)' : '');
            if (field.is_required) {
                const star = document.createElement('span');
                star.className = 'text-red-600';
                star.textContent = ' *';
                label.appendChild(star);
            }
            wrapper.appendChild(label);
            wrapper.appendChild(fieldInput(field));

            if (field.help_text) {
                const help = document.createElement('p');
                help.className = 'text-xs text-slate-500 mt-1';
                help.textContent = field.help_text;
                wrapper.appendChild(help);
            }

            const error = document.createElement('p');
            error.className = 'text-xs text-red-600 mt-1';
            error.dataset.errorFor = field.field_key;
            wrapper.appendChild(error);

            form.appendChild(wrapper);
        });
    }

    async function selectType(item) {
        currentType = item;
        renderTypes();
        el('checkResult').textContent = '';
        notify('');

        try {
            const result = await api(`/api/v1/catalog/support-types/${item.id}/form`);
            if (currentType !== item) return;

            const type = result.data.support_type;
            currentFields = result.data.fields || [];

            el('typeName').textContent = type.name;
            el('typeDepartment').textContent = type.department?.name ?? '';
            el('typeDescription').textContent = type.description || '';
            el('typeSla').textContent = slaText(type.sla_days);
            el('typeSla').className = 'text-sm font-medium px-3 py-1 rounded-full '
                + (type.sla_days == null ? 'bg-slate-100 text-slate-600' : 'bg-amber-50 text-amber-700');

            renderFields(currentFields);
            el('typeDetail').hidden = false;
        } catch (error) {
            notify(error.message, true);
        }

        loadFaqs(`/api/v1/catalog/support-types/${item.id}/faqs`, `Câu hỏi thường gặp: ${item.name}`);
    }

    async function checkValues() {
        if (!currentType) return;

        const body = new FormData();
        currentFields.forEach(field => {
            const input = el(`field_${field.field_key}`);
            if (!input) return;
            if (field.field_type === 'file') {
                if (input.files.length) body.append(`values[${field.field_key}]`, input.files[0]);
            } else if (input.value.trim() !== '') {
                body.append(`values[${field.field_key}]`, input.value.trim());
            }
        });

        el('valueForm').querySelectorAll('[data-error-for]').forEach(node => { node.textContent = ''; });
        el('checkButton').disabled = true;
        el('checkResult').textContent = 'Đang kiểm tra...';
        el('checkResult').className = 'text-sm text-slate-500';

        try {
            await api(`/api/v1/catalog/support-types/${currentType.id}/validate`, { method: 'POST', body });
            el('checkResult').textContent = 'Thông tin đã đầy đủ. Bạn có thể gửi yêu cầu.';
            el('checkResult').className = 'text-sm text-emerald-700';
        } catch (error) {
            Object.entries(error.errors || {}).forEach(([key, messages]) => {
                const fieldKey = key.replace(/^values\./, '');
                const target = el('valueForm').querySelector(`[data-error-for="${CSS.escape(fieldKey)}"]`);
                if (target) target.textContent = messages.join(' ');
            });
            el('checkResult').textContent = error.status === 422
                ? 'Thông tin còn thiếu hoặc chưa đúng, hãy xem các trường được đánh dấu.'
                : error.message;
            el('checkResult').className = 'text-sm text-red-600';
        } finally {
            el('checkButton').disabled = false;
        }
    }

    /* ---------- FAQ ---------- */

    async function loadFaqs(url, title) {
        const current = ++faqRequest;
        el('faqTitle').textContent = title;
        el('faqHint').textContent = 'Đang tải...';
        el('faqList').replaceChildren();

        try {
            const faqs = await loadAllPages(url);
            if (current !== faqRequest) return;

            el('faqHint').textContent = faqs.length
                ? 'Xem các câu trả lời dưới đây trước khi gửi để tránh tạo yêu cầu trùng.'
                : 'Chưa có câu hỏi thường gặp.';

            faqs.forEach(faq => {
                const details = document.createElement('details');
                details.className = 'border rounded-lg p-3';
                const summary = document.createElement('summary');
                summary.className = 'cursor-pointer font-medium text-sm break-words';
                summary.textContent = faq.question;
                const answer = document.createElement('p');
                answer.className = 'text-sm text-slate-600 mt-2 whitespace-pre-line break-words';
                answer.textContent = faq.answer;
                details.append(summary, answer);
                el('faqList').appendChild(details);
            });
        } catch (error) {
            if (current === faqRequest) el('faqHint').textContent = error.message;
        }
    }

    /* ---------- Khởi tạo ---------- */

    el('departmentSelect').addEventListener('change', () => {
        currentType = null;
        el('typeDetail').hidden = true;
        const select = el('departmentSelect');
        loadTypes();

        if (select.value) {
            const name = select.options[select.selectedIndex].textContent;
            loadFaqs(`/api/v1/catalog/departments/${select.value}/faqs`, `Câu hỏi thường gặp: ${name}`);
        } else {
            ++faqRequest;
            el('faqTitle').textContent = 'Câu hỏi thường gặp';
            el('faqHint').textContent = 'Chọn phòng ban hoặc loại hỗ trợ để xem câu hỏi thường gặp.';
            el('faqList').replaceChildren();
        }
    });
    el('typeSearch').addEventListener('input', renderTypes);
    el('checkButton').addEventListener('click', checkValues);

    (async () => {
        try {
            const departments = await loadAllPages('/api/v1/catalog/departments');
            departments.forEach(item => el('departmentSelect').appendChild(new Option(item.name, String(item.id))));
            await loadTypes();
        } catch (error) {
            notify(error.message, true);
        }
    })();
})();
</script>
@endsection
