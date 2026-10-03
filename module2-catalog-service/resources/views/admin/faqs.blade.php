@extends('layouts.app')

@section('title', 'Quản lý câu hỏi thường gặp')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold">Quản lý câu hỏi thường gặp</h1>
        <p class="mt-1 text-slate-500">
            Cấu hình FAQ theo phòng ban và loại hỗ trợ.
        </p>
    </div>

    <p id="message" role="status" aria-live="polite"></p>

    <div id="pageContent" hidden class="space-y-6">
        <form id="faqForm" class="bg-white border rounded-xl p-5">
            <fieldset id="formFields" class="space-y-4">
                <h2 id="formTitle" class="font-semibold text-lg">
                    Thêm câu hỏi
                </h2>

                <input id="faqId" type="hidden">

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="departmentId" class="block mb-1">
                            Phòng ban
                        </label>
                        <select id="departmentId"
                                required
                                class="w-full border rounded-lg p-2">
                            <option value="">-- Chọn phòng ban --</option>
                        </select>
                    </div>

                    <div>
                        <label for="supportTypeId" class="block mb-1">
                            Loại hỗ trợ
                        </label>
                        <select id="supportTypeId"
                                class="w-full border rounded-lg p-2">
                            <option value="">FAQ chung của phòng ban</option>
                        </select>
                        <p class="mt-1 text-xs text-slate-500">
                            Không chọn loại hỗ trợ nếu câu hỏi áp dụng chung.
                        </p>
                    </div>
                </div>

                <div>
                    <label for="question" class="block mb-1">
                        Câu hỏi
                    </label>
                    <input id="question"
                           required
                           maxlength="255"
                           class="w-full border rounded-lg p-2"
                           placeholder="Ví dụ: Điều chỉnh học phần cần giấy tờ gì?">
                </div>

                <div>
                    <label for="answer" class="block mb-1">
                        Câu trả lời
                    </label>
                    <textarea id="answer"
                              required
                              maxlength="10000"
                              rows="5"
                              class="w-full border rounded-lg p-2"
                              placeholder="Nhập nội dung hướng dẫn sinh viên"></textarea>
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
                </div>

                <label class="flex items-center gap-2">
                    <input id="isActive" type="checkbox" checked>
                    Đang hoạt động
                </label>

                <div class="flex flex-wrap gap-2">
                    <button id="saveButton"
                            type="submit"
                            class="bg-indigo-600 text-white px-4 py-2 rounded-lg disabled:opacity-50">
                        Lưu câu hỏi
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
            <h2 class="font-semibold text-lg">Danh sách FAQ</h2>

            <form id="searchForm">
                <fieldset id="filterFields"
                          class="grid gap-3 md:grid-cols-2">
                    <input id="search"
                           maxlength="255"
                           aria-label="Tìm câu hỏi hoặc câu trả lời"
                           class="border rounded-lg p-2 min-w-0"
                           placeholder="Tìm câu hỏi hoặc câu trả lời">

                    <select id="departmentFilter"
                            aria-label="Lọc phòng ban"
                            class="border rounded-lg p-2 min-w-0">
                        <option value="">Tất cả phòng ban</option>
                    </select>

                    <select id="typeFilter"
                            aria-label="Lọc loại hỗ trợ"
                            class="border rounded-lg p-2 min-w-0">
                        <option value="">Tất cả loại hỗ trợ</option>
                    </select>

                    <select id="activeFilter"
                            aria-label="Lọc trạng thái"
                            class="border rounded-lg p-2">
                        <option value="">Tất cả trạng thái</option>
                        <option value="1">Đang hoạt động</option>
                        <option value="0">Đã ẩn</option>
                    </select>

                    <div class="flex gap-2">
                        <button type="submit"
                                class="bg-slate-800 text-white px-4 py-2 rounded-lg">
                            Tìm kiếm
                        </button>
                        <button id="clearFilters"
                                type="button"
                                class="border px-4 py-2 rounded-lg">
                            Bỏ lọc
                        </button>
                    </div>
                </fieldset>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full text-sm" style="min-width: 800px">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="p-3 text-left">Thứ tự</th>
                            <th class="p-3 text-left">Câu hỏi / Câu trả lời</th>
                            <th class="p-3 text-left">Phạm vi</th>
                            <th class="p-3 text-left">Trạng thái</th>
                            <th class="p-3 text-left">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="faqRows"></tbody>
                </table>
            </div>

            <div class="flex flex-wrap justify-between items-center gap-3">
                <button id="previousButton"
                        type="button"
                        disabled
                        class="border px-3 py-2 rounded-lg disabled:opacity-40">
                    Trang trước
                </button>

                <span id="pageInfo" class="text-sm"></span>

                <button id="nextButton"
                        type="button"
                        disabled
                        class="border px-3 py-2 rounded-lg disabled:opacity-40">
                    Trang sau
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const el = id => document.getElementById(id);
    const apiUrl = '/api/v1/admin/faqs';

    let departments = [];
    let supportTypes = [];
    let currentPage = 1;
    let lastPage = 1;
    let busy = false;
    let filters = {};

    function notify(text, error = false) {
        el('message').textContent = text;
        el('message').className = error
            ? 'text-sm text-red-600'
            : 'text-sm text-emerald-700';
    }

    function updatePagination() {
        el('previousButton').disabled = busy || currentPage <= 1;
        el('nextButton').disabled = busy || currentPage >= lastPage;
    }

    function setBusy(value) {
        busy = value;
        el('formFields').disabled = value;
        el('filterFields').disabled = value;

        el('faqRows').querySelectorAll('button').forEach(button => {
            button.disabled = value;
        });

        updatePagination();
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
                ...DemoAuth.headers(),
                ...(options.headers || {})
            }
        });

        if (response.status === 401) {
            throw new Error('Chưa xác thực. Hãy chọn vai trò ở thanh bên.');
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
                    // Danh sách phân trang được bọc trong "data" (API contract) -> mở lớp bọc.
        if (data && data.data && Array.isArray(data.data.data)) {
            return data.data;
        }

        return data;
    }




    async function loadAllPages(url) {
        const items = [];
        let page = 1;
        let totalPages = 1;

        do {
            const result = await api(`${url}?page=${page}`);

            if (!Array.isArray(result.data)) {
                throw new Error('Danh mục không đúng định dạng.');
            }

            items.push(...result.data);
            totalPages = Number(result.last_page) || 1;
            page++;
        } while (page <= totalPages);

        return items;
    }

    function optionLabel(item) {
        return `${item.code} - ${item.name}` +
            (item.is_active ? '' : ' (Ngừng hoạt động)');
    }

    function fillDepartments() {
        el('departmentId').replaceChildren(
            new Option('-- Chọn phòng ban --', '')
        );

        el('departmentFilter').replaceChildren(
            new Option('Tất cả phòng ban', '')
        );

        departments.forEach(item => {
            el('departmentId').appendChild(
                new Option(optionLabel(item), String(item.id))
            );

            el('departmentFilter').appendChild(
                new Option(optionLabel(item), String(item.id))
            );
        });
    }

    function fillTypes(selectId, departmentId, forFilter = false) {
        const select = el(selectId);

        select.replaceChildren(
            new Option(
                forFilter
                    ? 'Tất cả loại hỗ trợ'
                    : 'FAQ chung của phòng ban',
                ''
            )
        );

        const items = supportTypes.filter(item => {
            if (!departmentId) return forFilter;

            return String(item.department_id) === String(departmentId);
        });

        items.forEach(item => {
            select.appendChild(
                new Option(optionLabel(item), String(item.id))
            );
        });
    }

    function resetForm() {
        el('faqForm').reset();
        el('faqId').value = '';
        el('isActive').checked = true;
        el('formTitle').textContent = 'Thêm câu hỏi';
        el('saveButton').textContent = 'Lưu câu hỏi';
        fillTypes('supportTypeId', '');
    }

    function tableMessage(text) {
        const row = document.createElement('tr');
        const cell = document.createElement('td');

        cell.colSpan = 5;
        cell.className = 'p-4 text-center text-slate-500';
        cell.textContent = text;

        row.appendChild(cell);
        el('faqRows').replaceChildren(row);
    }

    function addCell(row, text) {
        const cell = document.createElement('td');
        cell.className = 'p-3';
        cell.textContent = text;
        row.appendChild(cell);
        return cell;
    }

    function renderRows(items) {
        el('faqRows').replaceChildren();

        if (items.length === 0) {
            tableMessage('Không có câu hỏi phù hợp.');
            return;
        }

        items.forEach(item => {
            const row = document.createElement('tr');
            row.className = 'border-t align-top';

            addCell(row, item.sort_order);

            const contentCell = addCell(row, '');
            contentCell.style.minWidth = '260px';

            const question = document.createElement('p');
            question.className = 'font-medium';
            question.textContent = item.question;

            const details = document.createElement('details');
            details.className = 'mt-2';

            const summary = document.createElement('summary');
            summary.className = 'cursor-pointer text-indigo-600';
            summary.textContent = 'Xem câu trả lời';

            const answer = document.createElement('p');
            answer.className =
                'mt-2 whitespace-pre-wrap break-words text-slate-600';
            answer.textContent = item.answer;

            details.append(summary, answer);
            contentCell.append(question, details);

            const scopeCell = addCell(
                row,
                item.department?.name || 'Không tìm thấy phòng ban'
            );

            const type = document.createElement('p');
            type.className = 'mt-1 text-xs text-slate-500';
            type.textContent = item.support_type_id
                ? (item.support_type?.name || 'Không tìm thấy loại hỗ trợ')
                : 'FAQ chung của phòng ban';

            scopeCell.appendChild(type);

            addCell(
                row,
                item.is_active ? 'Đang hoạt động' : 'Đã ẩn'
            );

            const actions = addCell(row, '');
            const buttons = document.createElement('div');
            buttons.className = 'flex flex-wrap gap-3';

            const edit = document.createElement('button');
            edit.type = 'button';
            edit.textContent = 'Sửa';
            edit.disabled = busy;
            edit.className =
                'text-indigo-600 hover:underline disabled:opacity-50';

            edit.addEventListener('click', () => {
                run(() => editFaq(item.id));
            });

            const status = document.createElement('button');
            status.type = 'button';
            status.textContent = item.is_active ? 'Ẩn' : 'Bật';
            status.disabled = busy;
            status.className =
                'text-slate-600 hover:underline disabled:opacity-50';

            status.addEventListener('click', () => {
                run(async () => {
                    const result = await api(
                        `${apiUrl}/${item.id}/status`,
                        {
                            method: 'PUT',
                            body: JSON.stringify({
                                is_active: !item.is_active
                            })
                        }
                    );

                    if (el('faqId').value === String(item.id)) {
                        resetForm();
                    }

                    await reloadAfterSave(result.message);
                });
            });

            buttons.append(edit, status);
            actions.appendChild(buttons);
            el('faqRows').appendChild(row);
        });
    }

    async function loadFaqs(page = 1) {
        tableMessage('Đang tải...');
        el('pageInfo').textContent = 'Đang tải...';

        try {
            const params = new URLSearchParams({
                ...filters,
                page: String(page)
            });

            const result = await api(`${apiUrl}?${params}`);

            if (!Array.isArray(result.data)) {
                throw new Error('Danh sách FAQ không đúng định dạng.');
            }

            lastPage = Number(result.last_page) || 1;

            if (page > lastPage) {
                return await loadFaqs(lastPage);
            }

            currentPage = Number(result.current_page) || page;
            renderRows(result.data);

            el('pageInfo').textContent =
                `Trang ${currentPage}/${lastPage} — ${result.total} câu hỏi`;
        } catch (error) {
            currentPage = 1;
            lastPage = 1;
            tableMessage('Không tải được danh sách. Bấm Tìm kiếm để thử lại.');
            el('pageInfo').textContent = 'Tải dữ liệu thất bại.';
            throw error;
        }
    }

    async function reloadAfterSave(message) {
        notify(message || 'Đã lưu thay đổi.');

        try {
            await loadFaqs(currentPage);
        } catch (error) {
            notify(
                `Đã lưu thay đổi nhưng chưa tải lại được danh sách: ${error.message}`,
                true
            );
        }
    }

    async function editFaq(id) {
        const result = await api(`${apiUrl}/${id}`);
        const item = result.data;

        if (!item) {
            throw new Error('Không tìm thấy câu hỏi.');
        }

        // Cập nhật danh mục trước khi điền dữ liệu sửa.
        departments = await loadAllPages('/api/v1/admin/departments');
        supportTypes = await loadAllPages('/api/v1/admin/support-types');

        const oldDepartmentFilter = el('departmentFilter').value;
        const oldTypeFilter = el('typeFilter').value;

        fillDepartments();
        el('departmentFilter').value = oldDepartmentFilter;
        fillTypes('typeFilter', oldDepartmentFilter, true);
        el('typeFilter').value = oldTypeFilter;

        el('departmentId').value = String(item.department_id);
        fillTypes('supportTypeId', item.department_id);

        // Không âm thầm đổi FAQ riêng thành FAQ chung nếu loại đã chuyển phòng.
        if (
            item.support_type_id &&
            ![...el('supportTypeId').options].some(
                option => option.value === String(item.support_type_id)
            )
        ) {
            const option = new Option(
                `${item.support_type?.name || 'Loại hỗ trợ cũ'} (cần chọn lại)`,
                String(item.support_type_id)
            );

            option.disabled = true;
            el('supportTypeId').appendChild(option);
        }

        el('supportTypeId').value =
            String(item.support_type_id ?? '');

        el('faqId').value = item.id;
        el('question').value = item.question ?? '';
        el('answer').value = item.answer ?? '';
        el('sortOrder').value = item.sort_order ?? 0;
        el('isActive').checked = Boolean(item.is_active);

        el('formTitle').textContent = 'Sửa câu hỏi';
        el('saveButton').textContent = 'Cập nhật câu hỏi';

        notify('');

        el('faqForm').scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }

    el('departmentId').addEventListener('change', () => {
        fillTypes('supportTypeId', el('departmentId').value);
    });

    el('departmentFilter').addEventListener('change', () => {
        fillTypes('typeFilter', el('departmentFilter').value, true);
    });

    el('resetButton').addEventListener('click', () => {
        if (busy) return;
        resetForm();
        notify('');
    });

    el('faqForm').addEventListener('submit', event => {
        event.preventDefault();

        if (busy || !el('faqForm').reportValidity()) return;

        const id = el('faqId').value;
        const typeId = el('supportTypeId').value;

        const payload = {
            department_id: Number(el('departmentId').value),
            support_type_id: typeId === '' ? null : Number(typeId),
            question: el('question').value.trim(),
            answer: el('answer').value.trim(),
            sort_order: Number(el('sortOrder').value),
            is_active: el('isActive').checked
        };

        if (!payload.question || !payload.answer) {
            notify('Vui lòng nhập câu hỏi và câu trả lời.', true);
            return;
        }

        if (
            !Number.isInteger(payload.sort_order) ||
            payload.sort_order < 0 ||
            payload.sort_order > 65535
        ) {
            notify('Thứ tự phải là số nguyên từ 0 đến 65535.', true);
            return;
        }

        if (
            typeId &&
            !supportTypes.some(item =>
                String(item.id) === typeId &&
                Number(item.department_id) === payload.department_id
            )
        ) {
            notify(
                'Vui lòng chọn loại hỗ trợ thuộc phòng ban đã chọn, hoặc chọn FAQ chung.',
                true
            );
            return;
        }

        run(async () => {
            const result = await api(
                id ? `${apiUrl}/${id}` : apiUrl,
                {
                    method: id ? 'PUT' : 'POST',
                    body: JSON.stringify(payload)
                }
            );

            resetForm();
            await reloadAfterSave(result.message);
        });
    });

    function readFilters() {
        filters = {};

        const mapping = {
            search: 'search',
            departmentFilter: 'department_id',
            typeFilter: 'support_type_id',
            activeFilter: 'is_active'
        };

        Object.entries(mapping).forEach(([id, key]) => {
            const value = el(id).value.trim();
            if (value !== '') filters[key] = value;
        });
    }

    el('searchForm').addEventListener('submit', event => {
        event.preventDefault();

        run(async () => {
            readFilters();
            notify('');
            await loadFaqs(1);
        });
    });

    el('clearFilters').addEventListener('click', () => {
        run(async () => {
            el('searchForm').reset();
            fillTypes('typeFilter', '', true);
            filters = {};
            notify('');
            await loadFaqs(1);
        });
    });

    el('previousButton').addEventListener('click', () => {
        if (currentPage > 1) {
            run(() => loadFaqs(currentPage - 1));
        }
    });

    el('nextButton').addEventListener('click', () => {
        if (currentPage < lastPage) {
            run(() => loadFaqs(currentPage + 1));
        }
    });

    async function init() {
        await run(async () => {
            notify('Đang tải dữ liệu...');


            if (DemoAuth.user().role !== 'admin') {
                notify('Trang này chỉ dành cho ADMIN.', true);
                return;
            }

            departments = await loadAllPages('/api/v1/admin/departments');
            supportTypes = await loadAllPages('/api/v1/admin/support-types');

            fillDepartments();
            fillTypes('supportTypeId', '');
            fillTypes('typeFilter', '', true);

            el('pageContent').hidden = false;
            notify('');

            await loadFaqs();
        });
    }

    init();
})();
</script>
@endsection