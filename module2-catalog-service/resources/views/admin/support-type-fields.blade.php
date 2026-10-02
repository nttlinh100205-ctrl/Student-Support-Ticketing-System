@extends('layouts.app')

@section('title', 'Cấu hình biểu mẫu hỗ trợ')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold">Cấu hình biểu mẫu hỗ trợ</h1>
        <p class="mt-1 text-slate-500">
            Thiết lập thông tin và giấy tờ cần cung cấp cho từng loại hỗ trợ.
        </p>
        <a href="{{ url('/admin/support-types') }}"
           class="inline-block mt-3 text-indigo-600 hover:underline">
            ← Quay lại loại hỗ trợ
        </a>
    </div>

    <p id="message" role="status" aria-live="polite" class="text-sm"></p>

    <div id="pageContent" hidden class="space-y-6">
        <div class="bg-white border rounded-xl p-5">
            <label for="typeSelect" class="block font-medium mb-2">
                Loại hỗ trợ
            </label>

            <select id="typeSelect" class="w-full border rounded-lg p-2">
                <option value="">-- Chọn loại hỗ trợ --</option>
            </select>
        </div>

        <div id="editorContent" hidden class="space-y-6">
            <form id="fieldForm" class="bg-white border rounded-xl p-5">
                <fieldset id="formFields" class="space-y-4">
                    <h2 id="formTitle" class="font-semibold text-lg">
                        Thêm trường biểu mẫu
                    </h2>

                    <input id="fieldId" type="hidden">

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="fieldLabel" class="block mb-1">
                                Tên hiển thị
                            </label>
                            <input id="fieldLabel"
                                   required
                                   maxlength="150"
                                   class="w-full border rounded-lg p-2"
                                   placeholder="Ví dụ: Mã sinh viên">
                        </div>

                        <div>
                            <label for="fieldKey" class="block mb-1">
                                Mã trường
                            </label>
                            <input id="fieldKey"
                                   required
                                   maxlength="50"
                                   pattern="[a-z][a-z0-9_]*"
                                   class="w-full border rounded-lg p-2"
                                   placeholder="Ví dụ: student_code">
                            <p class="mt-1 text-xs text-slate-500">
                                Bắt đầu bằng chữ thường; chỉ dùng chữ thường,
                                số và dấu gạch dưới.
                            </p>
                        </div>

                        <div>
                            <label for="fieldType" class="block mb-1">
                                Kiểu trường
                            </label>
                            <select id="fieldType"
                                    class="w-full border rounded-lg p-2">
                                <option value="text">Văn bản ngắn</option>
                                <option value="textarea">Văn bản dài</option>
                                <option value="number">Số</option>
                                <option value="date">Ngày</option>
                                <option value="select">Danh sách chọn</option>
                                <option value="file">Tệp đính kèm</option>
                            </select>
                        </div>

                        <div>
                            <label for="sortOrder" class="block mb-1">
                                Thứ tự hiển thị
                            </label>
                            <input id="sortOrder"
                                   type="number"
                                   min="0"
                                   max="65535"
                                   step="1"
                                   value="0"
                                   required
                                   class="w-full border rounded-lg p-2">
                            <p class="mt-1 text-xs text-slate-500">
                                Số nhỏ được hiển thị trước.
                            </p>
                        </div>
                    </div>

                    <div id="optionsBox" hidden>
                        <label for="fieldOptions" class="block mb-1">
                            Các lựa chọn
                        </label>
                        <textarea id="fieldOptions"
                                  rows="4"
                                  class="w-full border rounded-lg p-2"
                                  placeholder="Học kỳ 1&#10;Học kỳ 2&#10;Học kỳ hè"></textarea>
                        <p class="mt-1 text-xs text-slate-500">
                            Mỗi dòng là một lựa chọn, tối đa 50 lựa chọn.
                        </p>
                    </div>

                    <div>
                        <label for="helpText" class="block mb-1">
                            Hướng dẫn nhập
                        </label>
                        <textarea id="helpText"
                                  rows="2"
                                  maxlength="500"
                                  class="w-full border rounded-lg p-2"
                                  placeholder="Ví dụ: Nhập mã sinh viên trên thẻ của bạn"></textarea>
                    </div>

                    <div class="flex flex-wrap gap-5">
                        <label class="flex items-center gap-2">
                            <input id="isRequired" type="checkbox">
                            Bắt buộc cung cấp
                        </label>

                        <label class="flex items-center gap-2">
                            <input id="isActive" type="checkbox" checked>
                            Đang hoạt động
                        </label>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button id="saveButton"
                                type="submit"
                                class="bg-indigo-600 text-white px-4 py-2 rounded-lg disabled:opacity-50">
                            Lưu trường
                        </button>

                        <button id="resetButton"
                                type="button"
                                class="border px-4 py-2 rounded-lg disabled:opacity-50">
                            Nhập mới
                        </button>
                    </div>
                </fieldset>
            </form>

            <div class="bg-white border rounded-xl p-5 space-y-4">
                <div class="flex flex-wrap justify-between items-center gap-3">
                    <h2 class="font-semibold text-lg">
                        Các trường đã cấu hình
                    </h2>

                    <button id="reloadButton"
                            type="button"
                            class="border px-3 py-2 rounded-lg disabled:opacity-50">
                        Tải lại
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm" style="min-width: 780px">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="p-3 text-left">Thứ tự</th>
                                <th class="p-3 text-left">Tên / Mã trường</th>
                                <th class="p-3 text-left">Kiểu</th>
                                <th class="p-3 text-left">Bắt buộc</th>
                                <th class="p-3 text-left">Trạng thái</th>
                                <th class="p-3 text-left">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="fieldRows"></tbody>
                    </table>
                </div>

                <p id="fieldCount" class="text-sm text-slate-500"></p>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const el = id => document.getElementById(id);
    const token = localStorage.getItem('access_token');
    const typeApi = '/api/v1/admin/support-types';

    const typeNames = {
        text: 'Văn bản ngắn',
        textarea: 'Văn bản dài',
        number: 'Số',
        date: 'Ngày',
        select: 'Danh sách chọn',
        file: 'Tệp đính kèm'
    };

    let selectedTypeId = '';
    let busy = false;

    function notify(text, error = false) {
        el('message').textContent = text;
        el('message').className = error
            ? 'text-sm text-red-600'
            : 'text-sm text-emerald-700';
    }

    function setBusy(value) {
        busy = value;

        el('typeSelect').disabled = value;
        el('formFields').disabled = value;
        el('reloadButton').disabled = value;

        el('fieldRows').querySelectorAll('button').forEach(button => {
            button.disabled = value;
        });
    }

    async function run(action) {
        if (busy) return;

        setBusy(true);

        try {
            await action();
        } catch (error) {
            notify(error.message, true);
        } finally {
            setBusy(false);
        }
    }

    async function api(url, options = {}) {
        const response = await fetch(url, {
            ...options,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                Authorization: `Bearer ${token}`,
                ...(options.headers || {})
            }
        });

        if (response.status === 401) {
            localStorage.removeItem('access_token');
            localStorage.removeItem('current_user');
            window.location.href = '/login';
            throw new Error('Phiên đăng nhập đã hết hạn.');
        }

        if (response.status === 403) {
            el('pageContent').hidden = true;
            throw new Error('Trang này chỉ dành cho ADMIN.');
        }

        const data = await response.json().catch(() => null);

        if (!response.ok) {
            const details = Object.values(data?.errors || {})
                .flat()
                .join(' ');

            throw new Error(
                details ||
                data?.message ||
                `Không thể xử lý yêu cầu (${response.status}).`
            );
        }

        if (!data) {
            throw new Error('Máy chủ trả về dữ liệu không hợp lệ.');
        }

        return data;
    }

    function fieldsUrl() {
        return `${typeApi}/${selectedTypeId}/fields`;
    }

    async function loadTypes() {
        const items = [];
        let page = 1;
        let lastPage = 1;

        do {
            const result = await api(`${typeApi}?page=${page}`);

            if (!Array.isArray(result.data)) {
                throw new Error('Danh sách loại hỗ trợ không đúng định dạng.');
            }

            items.push(...result.data);
            lastPage = Number(result.last_page) || 1;
            page++;
        } while (page <= lastPage);

        el('typeSelect').replaceChildren(
            new Option('-- Chọn loại hỗ trợ --', '')
        );

        items.forEach(item => {
            const status = item.is_active
                ? ''
                : ' (Ngừng hoạt động)';

            el('typeSelect').appendChild(
                new Option(
                    `${item.code} - ${item.name}${status}`,
                    String(item.id)
                )
            );
        });

        return items.length;
    }

    function updateOptionsVisibility() {
        const isSelect = el('fieldType').value === 'select';

        el('optionsBox').hidden = !isSelect;
        el('fieldOptions').required = isSelect;
        el('fieldOptions').disabled = !isSelect;
    }

    function resetForm() {
        el('fieldForm').reset();
        el('fieldId').value = '';
        el('isActive').checked = true;
        el('formTitle').textContent = 'Thêm trường biểu mẫu';
        el('saveButton').textContent = 'Lưu trường';
        updateOptionsVisibility();
    }

    function tableMessage(text) {
        const row = document.createElement('tr');
        const cell = document.createElement('td');

        cell.colSpan = 6;
        cell.className = 'p-4 text-center text-slate-500';
        cell.textContent = text;

        row.appendChild(cell);
        el('fieldRows').replaceChildren(row);
    }

    function addCell(row, text) {
        const cell = document.createElement('td');
        cell.className = 'p-3';
        cell.textContent = text;
        row.appendChild(cell);
        return cell;
    }

    function renderRows(items) {
        el('fieldRows').replaceChildren();
        el('fieldCount').textContent = `${items.length} trường biểu mẫu`;

        if (items.length === 0) {
            tableMessage('Chưa có trường nào. Hãy thêm trường ở phía trên.');
            return;
        }

        items.forEach(item => {
            const row = document.createElement('tr');
            row.className = 'border-t align-top';

            addCell(row, item.sort_order);

            const nameCell = addCell(row, '');
            const label = document.createElement('div');
            label.className = 'font-medium';
            label.textContent = item.label;

            const key = document.createElement('div');
            key.className = 'text-xs text-slate-500 mt-1';
            key.textContent = item.field_key;

            nameCell.append(label, key);

            const kindCell = addCell(
                row,
                typeNames[item.field_type] || item.field_type
            );

            if (item.field_type === 'select') {
                const options = document.createElement('p');
                options.className = 'text-xs text-slate-500 mt-1';
                options.textContent = (item.options || []).join(', ');
                kindCell.appendChild(options);
            }

            addCell(row, item.is_required ? 'Có' : 'Không');
            addCell(
                row,
                item.is_active ? 'Đang hoạt động' : 'Ngừng hoạt động'
            );

            const actions = addCell(row, '');
            const container = document.createElement('div');
            container.className = 'flex flex-wrap gap-3';

            const editButton = document.createElement('button');
            editButton.type = 'button';
            editButton.textContent = 'Sửa';
            editButton.className =
                'text-indigo-600 hover:underline disabled:opacity-50';
            editButton.disabled = busy;

            editButton.addEventListener('click', () => {
                run(() => editField(item.id));
            });

            const statusButton = document.createElement('button');
            statusButton.type = 'button';
            statusButton.textContent = item.is_active ? 'Tắt' : 'Bật';
            statusButton.className =
                'text-slate-600 hover:underline disabled:opacity-50';
            statusButton.disabled = busy;

            statusButton.addEventListener('click', () => {
                run(async () => {
                    const result = await api(
                        `${fieldsUrl()}/${item.id}/status`,
                        {
                            method: 'PUT',
                            body: JSON.stringify({
                                is_active: !item.is_active
                            })
                        }
                    );

                    if (el('fieldId').value === String(item.id)) {
                        resetForm();
                    }

                    notify(result.message);

                    try {
                        await loadFields();
                    } catch (error) {
                        notify(
                            `${result.message} Không tải lại được danh sách: ${error.message}`,
                            true
                        );
                    }
                });
            });

            container.append(editButton, statusButton);
            actions.appendChild(container);
            el('fieldRows').appendChild(row);
        });
    }

    async function loadFields() {
        tableMessage('Đang tải...');
        el('fieldCount').textContent = '';

        try {
            const result = await api(fieldsUrl());

            if (!Array.isArray(result.data)) {
                throw new Error('Danh sách trường không đúng định dạng.');
            }

            renderRows(result.data);
        } catch (error) {
            tableMessage('Không tải được dữ liệu. Bấm Tải lại để thử lại.');
            throw error;
        }
    }

    async function editField(id) {
        notify('Đang tải thông tin trường...');

        const result = await api(`${fieldsUrl()}/${id}`);
        const item = result.data;

        if (!item) {
            throw new Error('Không tìm thấy trường biểu mẫu.');
        }

        el('fieldId').value = item.id;
        el('fieldLabel').value = item.label ?? '';
        el('fieldKey').value = item.field_key ?? '';
        el('fieldType').value = item.field_type;
        el('sortOrder').value = item.sort_order ?? 0;
        el('fieldOptions').value = (item.options || []).join('\n');
        el('helpText').value = item.help_text ?? '';
        el('isRequired').checked = Boolean(item.is_required);
        el('isActive').checked = Boolean(item.is_active);

        updateOptionsVisibility();

        el('formTitle').textContent = 'Sửa trường biểu mẫu';
        el('saveButton').textContent = 'Cập nhật trường';

        notify('');

        el('fieldForm').scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }

    el('fieldType').addEventListener(
        'change',
        updateOptionsVisibility
    );

    el('typeSelect').addEventListener('change', () => {
        run(async () => {
            selectedTypeId = el('typeSelect').value;

            resetForm();
            notify('');
            el('editorContent').hidden = !selectedTypeId;

            if (selectedTypeId) {
                await loadFields();
            }
        });
    });

    el('resetButton').addEventListener('click', () => {
        if (busy) return;
        resetForm();
        notify('');
    });

    el('reloadButton').addEventListener('click', () => {
        run(async () => {
            notify('');
            await loadFields();
        });
    });

    el('fieldForm').addEventListener('submit', event => {
        event.preventDefault();

        if (busy || !selectedTypeId) return;
        if (!el('fieldForm').reportValidity()) return;

        const kind = el('fieldType').value;
        const order = Number(el('sortOrder').value);

        const options = kind === 'select'
            ? el('fieldOptions').value
                .split(/\r?\n/)
                .map(value => value.trim())
                .filter(value => value !== '')
            : null;

        const payload = {
            label: el('fieldLabel').value.trim(),
            field_key: el('fieldKey').value.trim(),
            field_type: kind,
            sort_order: order,
            options,
            help_text: el('helpText').value.trim() || null,
            is_required: el('isRequired').checked,
            is_active: el('isActive').checked
        };

        if (!payload.label) {
            notify('Vui lòng nhập tên hiển thị.', true);
            return;
        }

        if (!Number.isInteger(order) || order < 0 || order > 65535) {
            notify('Thứ tự phải là số nguyên từ 0 đến 65535.', true);
            return;
        }

        if (kind === 'select') {
            if (options.length < 1 || options.length > 50) {
                notify('Vui lòng nhập từ 1 đến 50 lựa chọn.', true);
                return;
            }

            if (options.some(value => value.length > 150)) {
                notify('Mỗi lựa chọn không được quá 150 ký tự.', true);
                return;
            }

            if (new Set(options).size !== options.length) {
                notify('Các lựa chọn không được trùng nhau.', true);
                return;
            }
        }

        const id = el('fieldId').value;

        run(async () => {
            const result = await api(
                id ? `${fieldsUrl()}/${id}` : fieldsUrl(),
                {
                    method: id ? 'PUT' : 'POST',
                    body: JSON.stringify(payload)
                }
            );

            resetForm();
            notify(result.message || 'Đã lưu trường biểu mẫu.');

            try {
                await loadFields();
            } catch (error) {
                notify(
                    `Đã lưu trường nhưng chưa tải lại được danh sách: ${error.message}`,
                    true
                );
            }
        });
    });

    async function init() {
        if (!token) {
            window.location.href = '/login';
            return;
        }

        await run(async () => {
            notify('Đang tải dữ liệu...');

            const result = await api('/api/v1/auth/me');

            if (result.user?.role !== 'ADMIN') {
                notify('Trang này chỉ dành cho ADMIN.', true);
                return;
            }

            const count = await loadTypes();
            el('pageContent').hidden = false;

            notify(
                count
                    ? 'Chọn loại hỗ trợ để cấu hình biểu mẫu.'
                    : 'Chưa có loại hỗ trợ. Hãy tạo loại hỗ trợ trước.'
            );

            updateOptionsVisibility();
        });
    }

    init();
})();
</script>
@endsection