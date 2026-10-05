@extends('layouts.app')
@section('title', 'Quản lý cán bộ theo phòng ban')
@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold">
            Quản lý cán bộ theo phòng ban
        </h1>
        <p class="text-slate-500 mt-1">
            Xem, gán và chuyển cán bộ giữa các phòng ban
        </p>
    </div>
    <p role="status" aria-live="polite" id="message" class="text-sm"></p>
    <div id="staffContent" hidden class="space-y-6">
        {{-- Chọn phòng ban --}}
        <div class="bg-white rounded-xl border p-5">
            <label class="block mb-2 font-medium">
                Phòng ban
            </label>
            <select
                id="departmentSelect"
                class="w-full border rounded-lg p-2">
                <option value="">
                    -- Chọn phòng ban --
                </option>
            </select>
        </div>
        {{-- Danh sách cán bộ --}}
        <div class="bg-white rounded-xl border p-5 space-y-4">
            <h2 class="font-semibold">
                Danh sách cán bộ
            </h2>
            <form id="filterForm" class="grid gap-3 sm:grid-cols-2">
    <input id="staffSearch" maxlength="100" aria-label="Tìm cán bộ" placeholder="Tìm tên hoặc email" class="border rounded-lg p-2 min-w-0">
    <select id="staffRole" aria-label="Lọc vai trò" class="border rounded-lg p-2">
        <option value="">Tất cả vai trò</option><option value="staff">Cán bộ</option><option value="department_head">Trưởng phòng</option>
    </select>
    <select id="staffStatus" aria-label="Lọc trạng thái" class="border rounded-lg p-2">
        <option value="">Tất cả trạng thái</option><option value="ACTIVE">Hoạt động</option><option value="LOCKED">Bị khóa</option>
    </select>
    <div class="flex gap-2"><button class="bg-slate-800 text-white rounded-lg px-3 py-2">Tìm kiếm</button><button type="button" id="clearFilters" class="border rounded-lg px-3 py-2">Bỏ lọc</button></div>
</form>
<div class="overflow-x-auto">
                <table class="w-full text-sm table-fixed">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="text-left p-3">Cán bộ / Email</th>
                            <th class="text-left p-3">Vai trò</th>
                            <th class="text-left p-3">Trạng thái</th>
                            <th class="text-left p-3">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="staffRows">
                    </tbody>
                </table>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3 text-sm">
                <button type="button" id="prevPage" disabled class="border rounded-lg px-3 py-2 disabled:opacity-40">Trang trước</button>
                <span id="pageInfo" role="status">Chọn phòng ban để xem danh sách.</span>
                <button type="button" id="nextPage" disabled class="border rounded-lg px-3 py-2 disabled:opacity-40">Trang sau</button>
            </div>
        </div>
        {{-- Gán / chuyển cán bộ --}}
        <div class="bg-white rounded-xl border p-5 space-y-4">
            <h2 class="font-semibold">
                Gán hoặc chuyển cán bộ
            </h2>
            <div>
                <label class="block mb-1">
                    Tài khoản
                </label>
                <select
                    id="userSelect"
                    class="w-full border rounded-lg p-2">
                    <option value="">
                        -- Chọn tài khoản --
                    </option>
                </select>
            </div>
            <div>
                <label class="block mb-1">
                    Vai trò
                </label>
                <select
                    id="roleSelect"
                    class="w-full border rounded-lg p-2">
                    <option value="staff">
                        Cán bộ
                    </option>
                    <option value="department_head">
                        Trưởng phòng
                    </option>
                </select>
            </div>
            <button
                type="button"
                id="assignButton"
                class="px-4 py-2 bg-indigo-600 text-white rounded-lg">
                Gán vào phòng ban
            </button>
        </div>
    </div>
</div>
<script>
(() => {
    const el = id => document.getElementById(id);
    let page = 1, lastPage = 1, requestId = 0, loading = false;
    let filters = {};
    let users = [];

    function notify(text, error = false) {
        el('message').textContent = text;
        el('message').className = error ? 'text-sm text-red-600' : 'text-sm text-emerald-700';
    }
    async function api(url, options = {}) {
        const response = await fetch(url, {
            ...options,
            headers: { Accept: 'application/json', 'Content-Type': 'application/json',
                ...DemoAuth.headers(), ...(options.headers || {}) }
        });
        if (response.status === 401) {
            throw new Error('Chưa xác thực. Hãy chọn vai trò ở thanh bên.');
        }
        const data = await response.json().catch(() => null);
        if (!response.ok) {
            const details = Object.values(data?.errors || {}).flat().join(' ');
            throw new Error(details || data?.message || `Không thể xử lý yêu cầu (${response.status}).`);
        }
        if (!data) throw new Error('Máy chủ trả về dữ liệu không hợp lệ.');

                    // Danh sách phân trang được bọc trong "data" (API contract) -> mở lớp bọc.
        if (data && data.data && Array.isArray(data.data.data)) {
            return data.data;
        }
        return data;
    }




    function paginator(result) {
        const value = Array.isArray(result.data) ? result : result.data;
        if (!value || !Array.isArray(value.data)) throw new Error('Dữ liệu danh sách không đúng định dạng.');
        return value;
    }
    async function loadAllPages(url) {
        const items = [];
        let current = 1, last = 1;
        do {
            const address = new URL(url, window.location.origin);
            address.searchParams.set('page', String(current));
            const result = paginator(await api(address.pathname + address.search));
            items.push(...result.data);
            last = Number(result.last_page) || 1;
            current++;
        } while (current <= last);
        return items;
    }
    async function loadDepartments() {
        const list = await loadAllPages('/api/departments');
        el('departmentSelect').replaceChildren(new Option('-- Chọn phòng ban --', ''));
        list.forEach(item => el('departmentSelect').appendChild(new Option(
            `${item.code} - ${item.name}${item.is_active ? '' : ' (Ngừng hoạt động)'}`, String(item.id)
        )));
    }
    async function loadUsers() {
        const list = await loadAllPages('/api/staff-candidates');
        users = list.filter(user => user.role !== 'admin');
        const select = el('userSelect'), previous = select.value;
        select.replaceChildren(new Option('-- Chọn tài khoản --', ''));
        users.forEach(user => {
            const department = user.department ? user.department.code : 'Chưa có phòng ban';
            select.appendChild(new Option(`${user.name} - ${user.email} | ${department}${user.status === 'LOCKED' ? ' | Bị khóa' : ''}`, String(user.id)));
        });
        if (users.some(user => String(user.id) === previous)) select.value = previous;
    }
    function navigation() {
        const unavailable = loading || !el('departmentSelect').value;
        el('prevPage').disabled = unavailable || page <= 1;
        el('nextPage').disabled = unavailable || page >= lastPage;
    }
    function tableMessage(text) {
        const row = document.createElement('tr'), cell = document.createElement('td');
        cell.colSpan = 4;
        cell.className = 'p-4 text-center text-slate-500';
        cell.textContent = text;
        row.appendChild(cell);
        el('staffRows').replaceChildren(row);
    }
    function renderStaff(list) {
        el('staffRows').replaceChildren();
        if (!list.length) {
            tableMessage('Không có cán bộ phù hợp với phòng ban và bộ lọc đã chọn.');
            return;
        }
        list.forEach(user => {
            const row = document.createElement('tr');
            row.className = 'border-t align-top';
            const identity = document.createElement('td');
            identity.className = 'p-2 [overflow-wrap:anywhere]';
            const name = document.createElement('div'), email = document.createElement('div');
            name.textContent = user.name;
            name.className = 'font-medium';
            email.textContent = user.email;
            email.className = 'text-xs text-slate-500 mt-1';
            identity.append(name, email);
            row.appendChild(identity);
            [user.role === 'department_head' ? 'Trưởng phòng' : 'Cán bộ',
                ({ ACTIVE: 'Hoạt động', LOCKED: 'Bị khóa' })[user.status] || user.status
            ].forEach(text => {
                const cell = document.createElement('td');
                cell.className = 'p-2 break-words';
                cell.textContent = text;
                row.appendChild(cell);
            });
            const cell = document.createElement('td'), button = document.createElement('button');
            cell.className = 'p-2';
            button.type = 'button';
            button.textContent = 'Chọn';
            button.className = 'text-indigo-600 hover:underline';
            button.addEventListener('click', () => {
                el('userSelect').value = String(user.id);
                el('roleSelect').value = user.role;
                el('userSelect').focus();
            });
            cell.appendChild(button);
            row.appendChild(cell);
            el('staffRows').appendChild(row);
        });
    }
    async function loadStaff(targetPage = 1) {
        const currentRequest = ++requestId;
        const department = el('departmentSelect').value;
        page = targetPage;
        loading = true;
        navigation();
        if (!department) {
            loading = false; lastPage = 1;
            tableMessage('Vui lòng chọn phòng ban.');
            el('pageInfo').textContent = 'Chọn phòng ban để xem danh sách.';
            navigation();
            return;
        }
        tableMessage('Đang tải danh sách...');
        el('pageInfo').textContent = 'Đang tải...';
        try {
            const params = new URLSearchParams({ page: String(targetPage), ...filters });
            const result = paginator(await api(`/api/departments/${department}/staff?${params}`));
            if (currentRequest !== requestId) return;
            lastPage = Number(result.last_page) || 1;
            if (targetPage > lastPage) return await loadStaff(lastPage);
            page = Number(result.current_page) || targetPage;
            renderStaff(result.data);
            el('pageInfo').textContent = `Trang ${page}/${lastPage} — ${result.total ?? result.data.length} cán bộ`;
        } catch (error) {
            if (currentRequest !== requestId) return;
            lastPage = 1;
            tableMessage('Không tải được danh sách. Hãy thử tìm kiếm lại.');
            el('pageInfo').textContent = 'Tải dữ liệu thất bại.';
            notify(error.message, true);
        } finally {
            if (currentRequest === requestId) { loading = false; navigation(); }
        }
    }
    function applyFilters() {
        filters = {};
        const values = { search: el('staffSearch').value.trim(), role: el('staffRole').value, status: el('staffStatus').value };
        Object.entries(values).forEach(([key, value]) => { if (value) filters[key] = value; });
        notify('');
        loadStaff(1);
    }
    el('filterForm').addEventListener('submit', event => { event.preventDefault(); applyFilters(); });
    el('staffRole').addEventListener('change', applyFilters);
    el('staffStatus').addEventListener('change', applyFilters);
    el('clearFilters').addEventListener('click', () => { el('filterForm').reset(); applyFilters(); });
    el('departmentSelect').addEventListener('change', applyFilters);
    el('prevPage').addEventListener('click', () => loadStaff(page - 1));
    el('nextPage').addEventListener('click', () => loadStaff(page + 1));
    el('userSelect').addEventListener('change', () => {
        const user = users.find(item => String(item.id) === el('userSelect').value);
        el('roleSelect').value = user?.role === 'department_head' ? 'department_head' : 'staff';
    });
    el('assignButton').addEventListener('click', async () => {
        const userId = el('userSelect').value, departmentId = el('departmentSelect').value;
        if (!departmentId || !userId) { notify('Vui lòng chọn phòng ban và tài khoản.', true); return; }
        const button = el('assignButton');
        button.disabled = true;
        button.textContent = 'Đang lưu...';
        try {
            const result = await api(`/api/departments/${departmentId}/staff/${userId}`, {
                method: 'PUT', body: JSON.stringify({ role: el('roleSelect').value })
            });
            notify(result.message || 'Gán cán bộ thành công.');
            await loadStaff(1);
            try { await loadUsers(); }
            catch (error) { notify(`Đã lưu thay đổi, nhưng chưa tải lại được danh sách tài khoản: ${error.message}`, true); }
        } catch (error) { notify(error.message, true); }
        finally { button.disabled = false; button.textContent = 'Gán vào phòng ban'; }
    });
    async function init() {
        try {
            if (DemoAuth.user().role !== 'admin') { notify('Trang này chỉ dành cho ADMIN.', true); return; }
            await Promise.all([loadDepartments(), loadUsers()]);
            el('staffContent').hidden = false;
            await loadStaff();
        } catch (error) { notify(error.message, true); }
    }
    init();
})();
</script>
@endsection
