@extends('layouts.app')

@section('title', 'Quản lý phòng ban')

@section('content')
<div class="space-y-6">
       <div>
        <h1 class="text-2xl font-bold">Quản lý phòng ban</h1>
        <p class="text-slate-500 mt-1">Danh mục và tổ chức</p>
    </div>

    <p id="message" role="status" class="text-sm"></p>

    <div id="catalogContent" hidden class="space-y-6">
        <form id="departmentForm"
              class="bg-white rounded-xl border p-5 space-y-4">
            <h2 id="formTitle" class="font-semibold">Thêm phòng ban</h2>

            <input type="hidden" id="departmentId">

            <div>
                <label for="departmentName" class="block text-sm mb-1">
                    Tên phòng ban
                </label>
                <input id="departmentName" required maxlength="100"
                       class="w-full border rounded-lg p-2">
            </div>

            <div>
                <label for="departmentCode" class="block text-sm mb-1">
                    Mã phòng ban
                </label>
                <input id="departmentCode" required maxlength="50"
                       pattern="[A-Za-z0-9_-]+"
                       class="w-full border rounded-lg p-2">
                <p class="text-xs text-slate-500 mt-1">
                    Dùng chữ không dấu, số, dấu gạch ngang hoặc gạch dưới.
                </p>
            </div>

            <div>
                <label for="departmentDescription"
                       class="block text-sm mb-1">Mô tả</label>
                <textarea id="departmentDescription" maxlength="2000"
                          class="w-full border rounded-lg p-2"></textarea>
            </div>

            <label class="flex items-center gap-2">
                <input type="checkbox" id="departmentActive" checked>
                Đang hoạt động
            </label>

            <div class="flex gap-2">
                <button id="saveButton" type="submit"
                        class="bg-brand-600 text-white rounded-lg px-4 py-2">
                    Lưu phòng ban
                </button>
                <button id="resetButton" type="button"
                        class="border rounded-lg px-4 py-2">
                    Nhập mới
                </button>
            </div>
        </form>

        <div class="bg-white rounded-xl border p-5">
            <form id="searchForm" class="flex flex-wrap gap-2 mb-4">
                <input id="search" placeholder="Tìm tên hoặc mã phòng ban"
                       class="border rounded-lg p-2 flex-1">

                <select id="activeFilter" class="border rounded-lg p-2">
                    <option value="">Tất cả trạng thái</option>
                    <option value="1">Đang hoạt động</option>
                    <option value="0">Ngừng hoạt động</option>
                </select>

                <button class="bg-slate-800 text-white rounded-lg px-4 py-2">
                    Tìm kiếm
                </button>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="p-3">Mã</th>
                            <th class="p-3">Tên phòng ban</th>
                            <th class="p-3">Cán bộ</th>
                            <th class="p-3">Trưởng phòng</th>
                            <th class="p-3">Trạng thái</th>
                            <th class="p-3">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="departmentRows"></tbody>
                </table>
            </div>

            <div class="flex items-center justify-between mt-4 gap-2">
                <button id="previousButton" type="button"
                        class="border rounded-lg px-3 py-1 disabled:opacity-40">
                    Trang trước
                </button>
                <span id="pageInfo" class="text-sm"></span>
                <button id="nextButton" type="button"
                        class="border rounded-lg px-3 py-1 disabled:opacity-40">
                    Trang sau
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const el = id => document.getElementById(id);
    const apiUrl = '/api/v1/admin/departments';
    const token = localStorage.getItem('access_token');

    let currentPage = 1;
    let lastPage = 1;
    let searchValue = '';
    let activeValue = '';

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
                Authorization: `Bearer ${token}`,
            },
        });

        const data = await response.json();

        if (response.status === 401) {
            localStorage.removeItem('access_token');
            localStorage.removeItem('current_user');
            window.location.href = '/login';
            throw new Error('Phiên đăng nhập đã hết hạn.');
        }

        if (response.status === 403) {
            el('catalogContent').hidden = true;
            throw new Error('Bạn không có quyền quản lý phòng ban.');
        }

        if (!response.ok) {
            const errors = data.errors
                ? Object.values(data.errors).flat().join(' ')
                : data.message;

            throw new Error(errors || 'Không thể xử lý yêu cầu.');
        }

        return data;
    }

    function resetForm() {
        el('departmentForm').reset();
        el('departmentId').value = '';
        el('departmentActive').checked = true;
        el('formTitle').textContent = 'Thêm phòng ban';
    }

    function editDepartment(department) {
        el('departmentId').value = department.id;
        el('departmentName').value = department.name;
        el('departmentCode').value = department.code;
        el('departmentDescription').value = department.description ?? '';
        el('departmentActive').checked = department.is_active;
        el('formTitle').textContent = 'Sửa phòng ban';

        el('departmentForm').scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        });
    }

    async function loadDepartments(page = 1) {
        el('previousButton').disabled = true;
        el('nextButton').disabled = true;

        const params = new URLSearchParams({
            page: String(page),
            search: searchValue,
        });

        if (activeValue !== '') {
            params.set('is_active', activeValue);
        }

        const result = await api(`${apiUrl}?${params}`);

        currentPage = result.current_page;
        lastPage = result.last_page;

        const tbody = el('departmentRows');
        tbody.replaceChildren();

        result.data.forEach(department => {
            const row = document.createElement('tr');
            row.className = 'border-t';

            const values = [
                department.code,
                department.name,
                department.staff_count,
                department.heads_count,
                department.is_active ? 'Đang hoạt động' : 'Ngừng hoạt động',
            ];

            values.forEach(value => {
                const cell = document.createElement('td');
                cell.className = 'p-3';
                cell.textContent = value;
                row.appendChild(cell);
            });

            const actionCell = document.createElement('td');
            actionCell.className = 'p-3';

            const editButton = document.createElement('button');
            editButton.type = 'button';
            editButton.textContent = 'Sửa';
            editButton.className = 'text-brand-700 font-medium';
            editButton.addEventListener('click', () => {
                editDepartment(department);
            });

            actionCell.appendChild(editButton);
            row.appendChild(actionCell);
            tbody.appendChild(row);
        });

        if (result.data.length === 0) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 6;
            cell.className = 'p-5 text-center text-slate-500';
            cell.textContent = 'Không có phòng ban phù hợp.';
            row.appendChild(cell);
            tbody.appendChild(row);
        }

        el('pageInfo').textContent =
            `Trang ${currentPage}/${lastPage} — ${result.total} phòng ban`;

        el('previousButton').disabled = currentPage <= 1;
        el('nextButton').disabled = currentPage >= lastPage;
    }

    el('departmentForm').addEventListener('submit', async event => {
        event.preventDefault();
        el('saveButton').disabled = true;
        notify('Đang lưu...');

        const id = el('departmentId').value;
        const body = {
            name: el('departmentName').value.trim(),
            code: el('departmentCode').value.trim(),
            description: el('departmentDescription').value.trim(),
            is_active: el('departmentActive').checked,
        };

        try {
            const result = await api(id ? `${apiUrl}/${id}` : apiUrl, {
                method: id ? 'PUT' : 'POST',
                body: JSON.stringify(body),
            });

            resetForm();
            notify(result.message);

            try {
                await loadDepartments(1);
            } catch (error) {
                notify(`Đã lưu, nhưng tải lại danh sách thất bại: ${error.message}`, true);
            }
        } catch (error) {
            notify(error.message, true);
        } finally {
            el('saveButton').disabled = false;
        }
    });

    el('resetButton').addEventListener('click', resetForm);

    el('searchForm').addEventListener('submit', event => {
        event.preventDefault();
        searchValue = el('search').value.trim();
        activeValue = el('activeFilter').value;
        loadDepartments(1).catch(error => notify(error.message, true));
    });

    el('previousButton').addEventListener('click', () => {
        loadDepartments(currentPage - 1)
            .catch(error => notify(error.message, true));
    });

    el('nextButton').addEventListener('click', () => {
        loadDepartments(currentPage + 1)
            .catch(error => notify(error.message, true));
    });

    async function init() {
        if (!token) {
            window.location.href = '/login';
            return;
        }

        try {
            // Kiểm tra tài khoản thật qua API.
            const result = await api('/api/v1/auth/me');

            if (result.user.role !== 'ADMIN') {
                notify('Trang này chỉ dành cho ADMIN.', true);
                return;
            }

            el('catalogContent').hidden = false;
            await loadDepartments();
        } catch (error) {
            notify(error.message, true);
        }
    }

    init();
})();
</script>
@endsection