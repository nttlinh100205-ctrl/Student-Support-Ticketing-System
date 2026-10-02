@extends('layouts.app')

@section('title', 'Quản lý loại hỗ trợ')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold">Quản lý loại hỗ trợ</h1>
        <a href="{{ route('admin.support-type-fields') }}"
        class="inline-block mt-3 px-4 py-2 bg-indigo-600 text-white rounded-lg">
        Cấu hình biểu mẫu
    </a>
        <p class="mt-1 text-slate-500">
            Danh mục các loại yêu cầu hỗ trợ sinh viên
        </p>
    </div>

    <p id="message" role="status" aria-live="polite" class="text-sm"></p>

    <div id="catalogContent" hidden class="space-y-6">
        {{-- FORM THÊM / SỬA --}}
        <form id="supportTypeForm"
              class="bg-white rounded-xl border p-5 space-y-4">

            <h2 id="formTitle" class="font-semibold">
                Thêm loại hỗ trợ
            </h2>

            <input type="hidden" id="supportTypeId">

            <div>
                <label for="supportTypeName" class="block mb-1">
                    Tên loại hỗ trợ
                </label>
                <input id="supportTypeName"
                       required
                       maxlength="150"
                       class="w-full border rounded-lg p-2"
                       placeholder="Ví dụ: Xác nhận sinh viên">
            </div>

            <div>
                <label for="supportTypeCode" class="block mb-1">
                    Mã loại hỗ trợ
                </label>
                <input id="supportTypeCode"
                       required
                       maxlength="50"
                       pattern="[A-Za-z0-9_\-]+"
                       class="w-full border rounded-lg p-2"
                       placeholder="Ví dụ: XNSV">

                <p class="mt-1 text-xs text-slate-500">
                    Dùng chữ không dấu, số, dấu gạch ngang hoặc gạch dưới.
                </p>
            </div>

            <div>
                <label for="supportTypeDepartment" class="block mb-1">
                    Phòng phụ trách
                </label>
                <select id="supportTypeDepartment"
                        required
                        class="w-full border rounded-lg p-2">
                    <option value="">-- Chọn phòng ban --</option>
                </select>
            </div>

            <div>
                <label for="supportTypeSlaDays" class="block mb-1">
                    Số ngày xử lý (SLA)
                </label>
                <input id="supportTypeSlaDays"
                       type="number"
                       min="1"
                       max="365"
                       step="1"
                       aria-describedby="slaHelp"
                       class="w-full border rounded-lg p-2"
                       placeholder="Ví dụ: 3">

                <p id="slaHelp" class="mt-1 text-xs text-slate-500">
                    Nhập từ 1 đến 365 ngày, tính cả cuối tuần.
                    Để trống nếu chưa cấu hình thời gian xử lý.
                </p>
            </div>

            <div>
                <label for="supportTypeDescription" class="block mb-1">
                    Mô tả
                </label>
                <textarea id="supportTypeDescription"
                          maxlength="2000"
                          rows="3"
                          class="w-full border rounded-lg p-2"
                          placeholder="Nhập mô tả loại hỗ trợ"></textarea>
            </div>

            <label class="flex items-center gap-2">
                <input id="supportTypeActive" type="checkbox" checked>
                Đang hoạt động
            </label>

            <div class="flex flex-wrap gap-2">
                <button id="saveButton"
                        type="submit"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg disabled:opacity-50">
                    Lưu loại hỗ trợ
                </button>

                <button id="resetButton"
                        type="button"
                        class="px-4 py-2 border rounded-lg disabled:opacity-50">
                    Nhập mới
                </button>
            </div>
        </form>

        {{-- DANH SÁCH --}}
        <div class="bg-white rounded-xl border p-5 space-y-4">
            <h2 class="font-semibold">Danh sách loại hỗ trợ</h2>

            <form id="searchForm" class="grid gap-3 sm:grid-cols-2">
                <input id="search"
                       maxlength="150"
                       aria-label="Tìm theo tên hoặc mã"
                       class="min-w-0 border rounded-lg p-2"
                       placeholder="Tìm theo tên hoặc mã">

                <select id="departmentFilter"
                        aria-label="Lọc phòng ban"
                        class="min-w-0 border rounded-lg p-2">
                    <option value="">Tất cả phòng ban</option>
                </select>

                <select id="activeFilter"
                        aria-label="Lọc trạng thái"
                        class="border rounded-lg p-2">
                    <option value="">Tất cả trạng thái</option>
                    <option value="1">Đang hoạt động</option>
                    <option value="0">Ngừng hoạt động</option>
                </select>

                <div class="flex flex-wrap gap-2">
                    <button type="submit"
                            class="px-4 py-2 bg-slate-800 text-white rounded-lg">
                        Tìm kiếm
                    </button>
                    <button id="clearFilters"
                            type="button"
                            class="px-4 py-2 border rounded-lg">
                        Bỏ lọc
                    </button>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full text-sm" style="min-width: 720px">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="p-3 text-left">Mã</th>
                            <th class="p-3 text-left">Tên loại hỗ trợ</th>
                            <th class="p-3 text-left">Phòng phụ trách</th>
                            <th class="p-3 text-left">SLA</th>
                            <th class="p-3 text-left">Trạng thái</th>
                            <th class="p-3 text-left">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="supportTypeRows"></tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 text-sm">
                <button id="previousButton"
                        type="button"
                        disabled
                        class="px-3 py-2 border rounded-lg disabled:opacity-40">
                    Trang trước
                </button>

                <span id="pageInfo" role="status">Đang tải...</span>

                <button id="nextButton"
                        type="button"
                        disabled
                        class="px-3 py-2 border rounded-lg disabled:opacity-40">
                    Trang sau
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const el = id => document.getElementById(id);
    const apiUrl = '/api/v1/admin/support-types';
    const departmentApiUrl = '/api/v1/admin/departments';

    let currentPage = 1;
    let lastPage = 1;
    let listRequest = 0;
    let editRequest = 0;
    let loading = false;
    let saving = false;
    let filters = {};

    function notify(text, isError = false) {
        el('message').textContent = text;
        el('message').className = isError
            ? 'text-sm text-red-600'
            : 'text-sm text-emerald-700';
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
            el('catalogContent').hidden = true;
            throw new Error('Bạn không có quyền quản lý loại hỗ trợ.');
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

    async function loadAllPages(url) {
        const items = [];
        let page = 1;
        let totalPages = 1;

        do {
            const address = new URL(url, window.location.origin);
            address.searchParams.set('page', String(page));

            const result = await api(
                address.pathname + address.search
            );

            if (!Array.isArray(result.data)) {
                throw new Error('Danh sách phòng ban không đúng định dạng.');
            }

            items.push(...result.data);
            totalPages = Number(result.last_page) || 1;
            page++;
        } while (page <= totalPages);

        return items;
    }

    async function loadDepartments() {
        const departments = await loadAllPages(departmentApiUrl);
        const formSelect = el('supportTypeDepartment');
        const filterSelect = el('departmentFilter');

        formSelect.replaceChildren(
            new Option('-- Chọn phòng ban --', '')
        );

        filterSelect.replaceChildren(
            new Option('Tất cả phòng ban', '')
        );

        departments.forEach(department => {
            const status = department.is_active
                ? ''
                : ' (Ngừng hoạt động)';

            const label =
                `${department.code} - ${department.name}${status}`;

            formSelect.appendChild(
                new Option(label, String(department.id))
            );

            filterSelect.appendChild(
                new Option(label, String(department.id))
            );
        });
    }

    function resetForm() {
        editRequest++;
        el('supportTypeForm').reset();
        el('supportTypeId').value = '';
        el('supportTypeSlaDays').value = '';
        el('supportTypeActive').checked = true;
        el('formTitle').textContent = 'Thêm loại hỗ trợ';
        el('saveButton').textContent = 'Lưu loại hỗ trợ';
    }

    function updatePagination() {
        el('previousButton').disabled =
            loading || currentPage <= 1;

        el('nextButton').disabled =
            loading || currentPage >= lastPage;
    }

    function tableMessage(text) {
        const row = document.createElement('tr');
        const cell = document.createElement('td');

        cell.colSpan = 6;
        cell.className = 'p-4 text-center text-slate-500';
        cell.textContent = text;

        row.appendChild(cell);
        el('supportTypeRows').replaceChildren(row);
    }

    function renderRows(items) {
        el('supportTypeRows').replaceChildren();

        if (items.length === 0) {
            tableMessage('Không có loại hỗ trợ phù hợp.');
            return;
        }

        items.forEach(item => {
            const row = document.createElement('tr');
            row.className = 'border-t align-top';

            let departmentText = 'Chưa có phòng phụ trách';

            if (item.department) {
                departmentText = item.department.name;

                if (!item.department.is_active) {
                    departmentText += ' (Ngừng hoạt động)';
                }
            }

            const slaText = item.sla_days == null
                ? 'Chưa cấu hình'
                : `${item.sla_days} ngày`;

            const values = [
                item.code,
                item.name,
                departmentText,
                slaText,
                item.is_active
                    ? 'Đang hoạt động'
                    : 'Ngừng hoạt động'
            ];

            values.forEach(value => {
                const cell = document.createElement('td');
                cell.className = 'p-3 break-words';
                cell.textContent = value ?? '';
                row.appendChild(cell);
            });

            const actionCell = document.createElement('td');
            actionCell.className = 'p-3';

            const editButton = document.createElement('button');
            editButton.type = 'button';
            editButton.textContent = 'Sửa';
            editButton.className = 'text-indigo-600 hover:underline';

            editButton.addEventListener('click', () => {
                if (!saving) {
                    editSupportType(item.id);
                }
            });

            actionCell.appendChild(editButton);
            row.appendChild(actionCell);
            el('supportTypeRows').appendChild(row);
        });
    }

    async function loadSupportTypes(page = 1) {
        const request = ++listRequest;

        loading = true;
        updatePagination();
        tableMessage('Đang tải danh sách...');
        el('pageInfo').textContent = 'Đang tải...';

        try {
            const params = new URLSearchParams({
                page: String(page),
                ...filters
            });

            const result = await api(`${apiUrl}?${params}`);

            if (request !== listRequest) {
                return;
            }

            if (!Array.isArray(result.data)) {
                throw new Error('Danh sách loại hỗ trợ không đúng định dạng.');
            }

            lastPage = Number(result.last_page) || 1;

            if (page > lastPage) {
                return await loadSupportTypes(lastPage);
            }

            currentPage = Number(result.current_page) || page;
            renderRows(result.data);

            el('pageInfo').textContent =
                `Trang ${currentPage}/${lastPage} — ` +
                `${result.total ?? result.data.length} loại hỗ trợ`;
        } catch (error) {
            if (request !== listRequest) {
                return;
            }

            currentPage = 1;
            lastPage = 1;

            tableMessage('Không tải được danh sách. Bấm Tìm kiếm để thử lại.');
            el('pageInfo').textContent = 'Tải dữ liệu thất bại.';
            notify(error.message, true);
        } finally {
            if (request === listRequest) {
                loading = false;
                updatePagination();
            }
        }
    }

    async function editSupportType(id) {
        const request = ++editRequest;

        try {
            const result = await api(`${apiUrl}/${id}`);

            if (request !== editRequest || saving) {
                return;
            }

            const item = result.data;

            if (!item) {
                throw new Error('Không tìm thấy thông tin loại hỗ trợ.');
            }

            const select = el('supportTypeDepartment');

            if (
                item.department_id &&
                ![...select.options].some(
                    option => option.value === String(item.department_id)
                )
            ) {
                select.appendChild(
                    new Option(
                        item.department?.name ||
                        `Phòng ban #${item.department_id}`,
                        String(item.department_id)
                    )
                );
            }

            el('supportTypeId').value = item.id;
            el('supportTypeName').value = item.name ?? '';
            el('supportTypeCode').value = item.code ?? '';
            select.value = String(item.department_id ?? '');
            el('supportTypeSlaDays').value = item.sla_days ?? '';
            el('supportTypeDescription').value = item.description ?? '';
            el('supportTypeActive').checked = Boolean(item.is_active);

            el('formTitle').textContent = 'Sửa loại hỗ trợ';
            el('saveButton').textContent = 'Cập nhật loại hỗ trợ';

            notify('');

            el('supportTypeForm').scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        } catch (error) {
            if (request === editRequest) {
                notify(error.message, true);
            }
        }
    }

    el('supportTypeForm').addEventListener('submit', async event => {
        event.preventDefault();

        if (saving) {
            return;
        }

        if (!el('supportTypeForm').reportValidity()) {
            return;
        }

        const id = el('supportTypeId').value;
        const slaValue = el('supportTypeSlaDays').value.trim();
        const slaDays = slaValue === '' ? null : Number(slaValue);

        if (
            slaDays !== null &&
            (!Number.isInteger(slaDays) || slaDays < 1 || slaDays > 365)
        ) {
            notify('Số ngày xử lý phải là số nguyên từ 1 đến 365.', true);
            el('supportTypeSlaDays').focus();
            return;
        }

        const payload = {
            name: el('supportTypeName').value.trim(),
            code: el('supportTypeCode').value.trim(),
            department_id: Number(el('supportTypeDepartment').value),
            sla_days: slaDays,
            description: el('supportTypeDescription').value.trim() || null,
            is_active: el('supportTypeActive').checked
        };

        if (!payload.name || !payload.code || !payload.department_id) {
            notify('Vui lòng nhập tên, mã và chọn phòng phụ trách.', true);
            return;
        }

        saving = true;
        editRequest++;
        el('saveButton').disabled = true;
        el('resetButton').disabled = true;

        try {
            const result = await api(
                id ? `${apiUrl}/${id}` : apiUrl,
                {
                    method: id ? 'PUT' : 'POST',
                    body: JSON.stringify(payload)
                }
            );

            resetForm();

            notify(
                result.message ||
                (id
                    ? 'Cập nhật loại hỗ trợ thành công.'
                    : 'Thêm loại hỗ trợ thành công.')
            );

            await loadSupportTypes(currentPage);
        } catch (error) {
            notify(error.message, true);
        } finally {
            saving = false;
            el('saveButton').disabled = false;
            el('resetButton').disabled = false;
        }
    });

    el('resetButton').addEventListener('click', () => {
        if (saving) {
            return;
        }

        resetForm();
        notify('');
    });

    function applyFilters() {
        filters = {};

        const search = el('search').value.trim();
        const department = el('departmentFilter').value;
        const active = el('activeFilter').value;

        if (search !== '') {
            filters.search = search;
        }

        if (department !== '') {
            filters.department_id = department;
        }

        if (active !== '') {
            filters.is_active = active;
        }

        notify('');
        loadSupportTypes(1);
    }

    el('searchForm').addEventListener('submit', event => {
        event.preventDefault();
        applyFilters();
    });

    el('departmentFilter').addEventListener('change', applyFilters);
    el('activeFilter').addEventListener('change', applyFilters);

    el('clearFilters').addEventListener('click', () => {
        el('searchForm').reset();
        applyFilters();
    });

    el('previousButton').addEventListener('click', () => {
        if (!loading && currentPage > 1) {
            loadSupportTypes(currentPage - 1);
        }
    });

    el('nextButton').addEventListener('click', () => {
        if (!loading && currentPage < lastPage) {
            loadSupportTypes(currentPage + 1);
        }
    });

    async function init() {
        notify('Đang tải dữ liệu...');

        try {

            if (DemoAuth.user().role !== 'admin') {
                notify('Trang này chỉ dành cho ADMIN.', true);
                return;
            }

            await loadDepartments();

            el('catalogContent').hidden = false;
            notify('');

            await loadSupportTypes();
        } catch (error) {
            notify(error.message, true);
        }
    }

    init();
})();
</script>
@endsection