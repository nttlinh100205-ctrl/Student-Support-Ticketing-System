<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Quản lý tài khoản - ADMIN</title>

    <link rel="stylesheet" href="/css/app.css">

<style>
    .dashboard,
    .content,
    .card,
    #accountPanel {
        min-width: 0;
    }

    .account-nav {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 14px;
    }

    .table-container {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
    }

    .account-table {
        width: 100%;
        min-width: 1100px;
        table-layout: auto;
        border-collapse: collapse;
    }

    .account-table th,
    .account-table td {
        padding: 12px;
        text-align: left;
        vertical-align: top;
        border-bottom: 1px solid #e2e8f0;
        word-break: normal;
    }

    .account-table th {
        white-space: nowrap;
        background: #f8fafc;
    }

    /* Cột tài khoản: đủ rộng để tên và email dễ đọc. */
    .account-table th:nth-child(2),
    .account-table td:nth-child(2) {
        min-width: 230px;
    }

    /* Cột điện thoại. */
    .account-table th:nth-child(3),
    .account-table td:nth-child(3) {
        min-width: 120px;
    }

    /* Cột trạng thái. */
    .account-table th:nth-child(6),
    .account-table td:nth-child(6) {
        min-width: 110px;
        white-space: nowrap;
    }

    .account-table select {
        width: 100%;
        min-width: 145px;
        padding: 8px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: white;
    }

    .account-table .department-select {
        min-width: 260px;
        max-width: 340px;
    }

    .account-email {
        margin-top: 5px;
        font-size: 13px;
        color: #64748b;
        white-space: nowrap;
        overflow-wrap: normal;
        word-break: normal;
    }

    .account-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        min-width: 180px;
    }

    .account-button {
        padding: 8px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: white;
        color: #0f172a;
        white-space: nowrap;
        cursor: pointer;
    }

    .account-button.primary {
        background: #2563eb;
        border-color: #2563eb;
        color: white;
    }

    .account-pagination {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-top: 20px;
    }

    .account-table select:disabled,
    .account-button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    #message {
        margin: 14px 0;
    }

    #message.error {
        color: #b91c1c;
    }

    #message.success {
        color: #047857;
    }

    #accountPanel[hidden] {
        display: none !important;
    }
</style>
</head>

<body>
<div class="dashboard">
    <header class="topbar">
        <h1>Student Support - ADMIN</h1>

        <nav class="account-nav">
            <a href="/admin/departments">Phòng ban</a>
            <a href="/admin/support-types">Loại hỗ trợ</a>
            <a href="/admin/department-staff">Cán bộ theo phòng ban</a>
            <a href="/profile">Hồ sơ</a>

            <button id="logoutButton" type="button" class="btn-danger">
                Đăng xuất
            </button>
        </nav>
    </header>

    <main class="content">
        <section class="card">
            <h2>Quản lý tài khoản</h2>

            <p>
                Quản lý vai trò, phòng ban và trạng thái tài khoản.
            </p>

            <div id="message" role="status" aria-live="polite"></div>

            <div id="accountPanel" hidden>
                <button
                    id="reloadButton"
                    type="button"
                    class="account-button">
                    Tải lại danh sách
                </button>

                <div class="table-container" style="overflow-x:auto; margin-top:16px;">
                    <table class="account-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tài khoản</th>
                                <th>Điện thoại</th>
                                <th>Vai trò</th>
                                <th>Phòng ban</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="userTable"></tbody>
                    </table>
                </div>

                <div class="account-pagination">
                    <button
                        id="previousButton"
                        type="button"
                        class="account-button"
                        disabled>
                        Trang trước
                    </button>

                    <span id="pageInfo" role="status"></span>

                    <button
                        id="nextButton"
                        type="button"
                        class="account-button"
                        disabled>
                        Trang sau
                    </button>
                </div>
            </div>
        </section>
    </main>
</div>

<script>
(() => {
    const el = id => document.getElementById(id);
    const token = localStorage.getItem('access_token');

    const roles = {
        ADMIN: 'Admin',
        DEPARTMENT_HEAD: 'Trưởng phòng',
        STAFF: 'Cán bộ',
        STUDENT: 'Sinh viên'
    };

    let currentAdminId = null;
    let departments = [];
    let currentPage = 1;
    let lastPage = 1;
    let busy = false;

    function showMessage(text, type = '') {
        el('message').className = type ? `message ${type}` : 'message';
        el('message').textContent = text;
    }

    function clearLogin() {
        localStorage.removeItem('access_token');
        localStorage.removeItem('current_user');
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
            clearLogin();
            window.location.href = '/login';
            throw new Error('Phiên đăng nhập đã hết hạn.');
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

        return data;
    }

    function needsDepartment(role) {
        return role === 'STAFF' || role === 'DEPARTMENT_HEAD';
    }

    function updateNavigation() {
        el('previousButton').disabled = busy || currentPage <= 1;
        el('nextButton').disabled = busy || currentPage >= lastPage;
        el('reloadButton').disabled = busy;
    }

    function setBusy(value) {
        busy = value;
        updateNavigation();

        // Ngăn chỉnh sửa và gửi lặp trong lúc đang lưu/tải.
        el('userTable').querySelectorAll('button, select').forEach(control => {
            control.disabled =
                value || control.dataset.locked === '1';
        });
    }

    function tableMessage(text) {
        const row = document.createElement('tr');
        const cell = document.createElement('td');

        cell.colSpan = 7;
        cell.textContent = text;
        cell.style.textAlign = 'center';

        row.appendChild(cell);
        el('userTable').replaceChildren(row);
    }

    async function loadDepartments() {
        const items = [];
        let page = 1;
        let pages = 1;

        do {
            const result = await api(
                `/api/v1/admin/departments?page=${page}`
            );

            if (!result || !Array.isArray(result.data)) {
                throw new Error('Danh sách phòng ban không đúng định dạng.');
            }

            items.push(...result.data);
            pages = Number(result.last_page) || 1;
            page++;
        } while (page <= pages);

        departments = items;
    }

    function fillDepartmentSelect(select, user, role, selectedId) {
        const isSelf = Number(user.id) === currentAdminId;
        const required = needsDepartment(role);

        select.replaceChildren(
            new Option(
                required
                    ? '-- Chọn phòng ban --'
                    : 'Không thuộc phòng ban',
                ''
            )
        );

        if (required) {
            const choices = [...departments];

            // Giữ phòng hiện tại nếu API danh sách chưa có phòng đó.
            if (
                user.department &&
                !choices.some(
                    item => Number(item.id) === Number(user.department.id)
                )
            ) {
                choices.push(user.department);
            }

            choices.forEach(department => {
                const isExisting =
                    Number(department.id) === Number(user.department_id)
                    && needsDepartment(user.role);

                const active =
                    department.is_active === true ||
                    Number(department.is_active) === 1;

                const option = new Option(
                    `${department.code} - ${department.name}` +
                    (active ? '' : ' (Ngừng hoạt động)'),
                    String(department.id)
                );

                // Không cho gán mới vào phòng ngừng hoạt động.
                // Vẫn cho giữ phòng cũ của cán bộ hiện tại.
                option.disabled = !active && !isExisting;
                select.appendChild(option);
            });

            const wanted = String(selectedId ?? '');
            const selectedOption = [...select.options].find(
                option => option.value === wanted && !option.disabled
            );

            select.value = selectedOption ? wanted : '';
        }

        select.dataset.locked = isSelf || !required ? '1' : '0';
        select.disabled = busy || select.dataset.locked === '1';
    }

    function appendTextCell(row, text) {
        const cell = document.createElement('td');
        cell.textContent = text ?? '';
        row.appendChild(cell);
        return cell;
    }

    function renderUsers(users) {
        el('userTable').replaceChildren();

        if (!users.length) {
            tableMessage('Chưa có tài khoản.');
            return;
        }

        users.forEach(user => {
            const isSelf = Number(user.id) === currentAdminId;
            const row = document.createElement('tr');

            appendTextCell(row, user.id);

            const identityCell = document.createElement('td');
            const name = document.createElement('div');
            const email = document.createElement('div');

            name.textContent =
                (user.name || '') + (isSelf ? ' (Bạn)' : '');

            email.textContent = user.email || '';
            email.className = 'account-email';

            identityCell.append(name, email);
            row.appendChild(identityCell);

            appendTextCell(row, user.phone || '—');

            const roleCell = document.createElement('td');
            const roleSelect = document.createElement('select');

            roleSelect.setAttribute('aria-label', `Vai trò của ${user.name}`);
            roleSelect.dataset.locked = isSelf ? '1' : '0';
            roleSelect.disabled = busy || isSelf;

            Object.entries(roles).forEach(([value, label]) => {
                roleSelect.appendChild(new Option(label, value));
            });

            roleSelect.value = user.role;
            roleCell.appendChild(roleSelect);
            row.appendChild(roleCell);

            const departmentCell = document.createElement('td');
            const departmentSelect = document.createElement('select');

            departmentSelect.className = 'department-select';
            departmentSelect.setAttribute(
                'aria-label',
                `Phòng ban của ${user.name}`
            );

            fillDepartmentSelect(
                departmentSelect,
                user,
                user.role,
                user.department_id
            );

            roleSelect.addEventListener('change', () => {
                const previous =
                    departmentSelect.value || user.department_id;

                fillDepartmentSelect(
                    departmentSelect,
                    user,
                    roleSelect.value,
                    previous
                );
            });

            departmentCell.appendChild(departmentSelect);
            row.appendChild(departmentCell);

            appendTextCell(
                row,
                user.status === 'ACTIVE' ? 'Hoạt động' : 'Bị khóa'
            );

            const actionCell = document.createElement('td');
            const actions = document.createElement('div');
            actions.className = 'account-actions';

            const saveButton = document.createElement('button');
            saveButton.type = 'button';
            saveButton.className = 'account-button primary';
            saveButton.textContent = 'Lưu quyền';
            saveButton.dataset.locked = isSelf ? '1' : '0';
            saveButton.disabled = busy || isSelf;

            saveButton.addEventListener('click', () => {
                const role = roleSelect.value;
                const departmentId = needsDepartment(role)
                    ? departmentSelect.value
                    : '';

                if (needsDepartment(role) && !departmentId) {
                    showMessage(
                        'Cán bộ và trưởng phòng phải được chọn phòng ban.',
                        'error'
                    );
                    return;
                }

                saveChange(
                    `/api/v1/admin/users/${user.id}/role`,
                    {
                        role,
                        department_id: departmentId
                            ? Number(departmentId)
                            : null
                    }
                );
            });

            const statusButton = document.createElement('button');
            statusButton.type = 'button';
            statusButton.className = 'account-button';
            statusButton.textContent =
                user.status === 'ACTIVE' ? 'Khóa' : 'Mở khóa';

            statusButton.dataset.locked = isSelf ? '1' : '0';
            statusButton.disabled = busy || isSelf;

            statusButton.addEventListener('click', () => {
                saveChange(
                    `/api/v1/admin/users/${user.id}/status`,
                    {
                        status: user.status === 'ACTIVE'
                            ? 'LOCKED'
                            : 'ACTIVE'
                    }
                );
            });

            actions.append(saveButton, statusButton);
            actionCell.appendChild(actions);
            row.appendChild(actionCell);
            el('userTable').appendChild(row);
        });
    }

    async function loadUsers(page = 1) {
        const result = await api(`/api/v1/admin/users?page=${page}`);
        const paginator = result?.data;

        if (!paginator || !Array.isArray(paginator.data)) {
            throw new Error('Danh sách tài khoản không đúng định dạng.');
        }

        lastPage = Number(paginator.last_page) || 1;

        if (page > lastPage) {
            return loadUsers(lastPage);
        }

        currentPage = Number(paginator.current_page) || page;

        renderUsers(paginator.data);

        el('pageInfo').textContent =
            `Trang ${currentPage}/${lastPage} — ` +
            `${paginator.total ?? paginator.data.length} tài khoản`;
    }

    async function refreshPage(page = currentPage) {
        if (busy) {
            return;
        }

        setBusy(true);
        showMessage('Đang tải dữ liệu...');

        try {
            await loadDepartments();
            await loadUsers(page);
            showMessage('');
        } catch (error) {
            showMessage(error.message, 'error');
        } finally {
            setBusy(false);
        }
    }

    async function saveChange(url, payload) {
        if (busy) {
            return;
        }

        setBusy(true);
        showMessage('Đang lưu...');

        try {
            const result = await api(url, {
                method: 'PUT',
                body: JSON.stringify(payload)
            });

            try {
                await loadUsers(currentPage);
                showMessage(
                    result?.message || 'Cập nhật thành công.',
                    'success'
                );
            } catch (error) {
                // Thay đổi đã lưu, nhưng dữ liệu hiển thị cần tải lại.
                tableMessage('Đã lưu. Hãy bấm Tải lại danh sách.');
                showMessage(
                    `Đã lưu thay đổi nhưng chưa tải lại được danh sách: ${error.message}`,
                    'error'
                );
            }
        } catch (error) {
            showMessage(error.message, 'error');
        } finally {
            setBusy(false);
        }
    }

    el('previousButton').addEventListener('click', () => {
        if (currentPage > 1) {
            refreshPage(currentPage - 1);
        }
    });

    el('nextButton').addEventListener('click', () => {
        if (currentPage < lastPage) {
            refreshPage(currentPage + 1);
        }
    });

    el('reloadButton').addEventListener('click', () => {
        refreshPage(currentPage);
    });

    el('logoutButton').addEventListener('click', async () => {
        const button = el('logoutButton');
        button.disabled = true;

        try {
            await api('/api/v1/auth/logout', {
                method: 'POST'
            });

            clearLogin();
            window.location.href = '/login';
        } catch (error) {
            showMessage(error.message, 'error');
            button.disabled = false;
        }
    });

    async function init() {
        if (!token) {
            window.location.href = '/login';
            return;
        }

        showMessage('Đang kiểm tra tài khoản...');

        try {
            const result = await api('/api/v1/auth/me');

            if (result?.user?.role !== 'ADMIN') {
                showMessage('Trang này chỉ dành cho ADMIN.', 'error');
                return;
            }

            currentAdminId = Number(result.user.id);
            el('accountPanel').hidden = false;

            await refreshPage(1);
        } catch (error) {
            showMessage(error.message, 'error');
        }
    }

    init();
})();
</script>
</body>
</html>