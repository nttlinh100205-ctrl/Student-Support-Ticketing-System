<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Quản lý tài khoản - ADMIN</title>

    <link rel="stylesheet" href="/css/app.css">

    <style>
        .action-group {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-secondary {
            padding: 7px 10px;
            border: none;
            border-radius: 5px;
            background: #2563eb;
            color: white;
            cursor: pointer;
        }

        .btn-warning {
            padding: 7px 10px;
            border: none;
            border-radius: 5px;
            background: #f59e0b;
            color: white;
            cursor: pointer;
        }

        .btn-secondary:disabled,
        .btn-warning:disabled,
        select:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .role-badge {
            font-weight: bold;
        }

        .status-active {
            color: #166534;
            font-weight: bold;
        }

        .status-locked {
            color: #991b1b;
            font-weight: bold;
        }
    </style>
</head>

<body>

<div class="dashboard">

    <header class="topbar">
        <h1>Student Support - ADMIN</h1>

        <nav>
            <a href="/profile">
                Hồ sơ
            </a>

            <button
                id="logoutButton"
                class="btn-danger"
                type="button"
            >
                Đăng xuất
            </button>
        </nav>
    </header>

    <main class="content">

        <section class="card">

            <h2>Quản lý tài khoản</h2>

            <p>
                Quản lý vai trò, phòng ban và trạng thái tài khoản
                trong hệ thống Student Support.
            </p>

            <div id="message"></div>

            <div class="table-container">

                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Họ tên</th>
                        <th>Email</th>
                        <th>Điện thoại</th>
                        <th>Role</th>
                        <th>Phòng ban</th>
                        <th>Status</th>
                        <th>Thao tác</th>
                    </tr>
                    </thead>

                    <tbody id="userTable">
                    </tbody>
                </table>

            </div>

        </section>

    </main>

</div>


<script>
const token = localStorage.getItem('access_token');

let currentAdminId = null;


/*
|--------------------------------------------------------------------------
| Danh sách phòng ban demo
|--------------------------------------------------------------------------
| Module 1 chỉ lưu department_id theo dạng soft reference.
*/
const departments = [
    {
        id: 1,
        name: 'Phòng Hỗ trợ Sinh viên',
        code: 'SUPPORT'
    }
];


/*
|--------------------------------------------------------------------------
| Không có token thì quay về login
|--------------------------------------------------------------------------
*/
if (!token) {
    window.location.href = '/login';
}


/*
|--------------------------------------------------------------------------
| Hiển thị thông báo
|--------------------------------------------------------------------------
*/
function showMessage(message, type = '') {
    const element = document.getElementById('message');

    element.className = type
        ? `message ${type}`
        : 'message';

    element.textContent = message;
}


/*
|--------------------------------------------------------------------------
| Tạo danh sách role
|--------------------------------------------------------------------------
*/
function getRoleOptions(selectedRole) {
    const roles = [
        {
            value: 'admin',
            label: 'ADMIN'
        },
        {
            value: 'department_head',
            label: 'DEPARTMENT HEAD'
        },
        {
            value: 'staff',
            label: 'STAFF'
        },
        {
            value: 'student',
            label: 'STUDENT'
        }
    ];

    return roles.map(function (role) {
        return `
            <option
                value="${role.value}"
                ${selectedRole === role.value ? 'selected' : ''}
            >
                ${role.label}
            </option>
        `;
    }).join('');
}


/*
|--------------------------------------------------------------------------
| Tạo select phòng ban
|--------------------------------------------------------------------------
*/
function createDepartmentSelect(
    userId,
    selectedDepartmentId,
    selectedRole,
    disabled
) {
    const roleDoesNotNeedDepartment =
        selectedRole === 'admin' ||
        selectedRole === 'student';

    const selectDisabled =
        disabled ||
        roleDoesNotNeedDepartment;

    let options = `
        <option value="">
            Không thuộc phòng ban
        </option>
    `;

    departments.forEach(function (department) {
        const selected =
            Number(selectedDepartmentId) === Number(department.id)
                ? 'selected'
                : '';

        options += `
            <option
                value="${department.id}"
                ${selected}
            >
                ${department.name} (${department.code})
            </option>
        `;
    });

    return `
        <select
            id="department-${userId}"
            ${selectDisabled ? 'disabled' : ''}
        >
            ${options}
        </select>
    `;
}


/*
|--------------------------------------------------------------------------
| Khi đổi role trên giao diện
|--------------------------------------------------------------------------
*/
function handleRoleChange(userId, selectElement) {
    const role = selectElement.value;

    const departmentSelect =
        document.getElementById(
            `department-${userId}`
        );

    if (!departmentSelect) {
        return;
    }

    if (
        role === 'admin' ||
        role === 'student'
    ) {
        departmentSelect.value = '';
        departmentSelect.disabled = true;
    } else {
        departmentSelect.disabled = false;
    }
}


/*
|--------------------------------------------------------------------------
| Kiểm tra tài khoản hiện tại có phải Admin hay không
|--------------------------------------------------------------------------
*/
async function checkAdmin() {
    try {
        const response = await fetch(
            '/api/profile',
            {
                method: 'GET',

                headers: {
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + token
                }
            }
        );

        if (!response.ok) {
            localStorage.clear();
            window.location.href = '/login';

            return false;
        }

        const result = await response.json();

        const user = result.data;

        if (
            !user ||
            user.role !== 'admin'
        ) {
            alert(
                'Bạn không có quyền truy cập trang ADMIN.'
            );

            window.location.href = '/profile';

            return false;
        }

        currentAdminId = Number(user.id);

        return true;

    } catch (error) {
        console.error(error);

        localStorage.clear();

        window.location.href = '/login';

        return false;
    }
}


/*
|--------------------------------------------------------------------------
| Tải danh sách tài khoản
|--------------------------------------------------------------------------
*/
async function loadUsers() {
    try {
        const response = await fetch(
            '/api/users',
            {
                method: 'GET',

                headers: {
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + token
                }
            }
        );

        if (response.status === 401) {
            localStorage.clear();

            window.location.href = '/login';

            return;
        }

        if (response.status === 403) {
            alert(
                'Bạn không có quyền quản lý tài khoản.'
            );

            window.location.href = '/profile';

            return;
        }

        const result = await response.json();

        if (!response.ok) {
            showMessage(
                result.message ||
                'Không thể tải danh sách tài khoản.',
                'error'
            );

            return;
        }

        const tbody =
            document.getElementById('userTable');

        tbody.innerHTML = '';

        const users =
            result.data?.data || [];

        if (users.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8">
                        Chưa có tài khoản.
                    </td>
                </tr>
            `;

            return;
        }

        users.forEach(function (user) {
            const row =
                document.createElement('tr');

            const isCurrentAdmin =
                Number(user.id) ===
                Number(currentAdminId);

            const statusButtonText =
                user.status === 'ACTIVE'
                    ? 'Khóa'
                    : 'Mở khóa';

            const nextStatus =
                user.status === 'ACTIVE'
                    ? 'LOCKED'
                    : 'ACTIVE';

            const statusClass =
                user.status === 'ACTIVE'
                    ? 'status-active'
                    : 'status-locked';

            row.innerHTML = `
                <td>
                    ${user.id}
                </td>

                <td>
                    ${user.full_name || ''}
                </td>

                <td>
                    ${user.email || ''}
                </td>

                <td>
                    ${user.phone || ''}
                </td>

                <td>
                    <select
                        id="role-${user.id}"
                        onchange="
                            handleRoleChange(
                                ${user.id},
                                this
                            )
                        "
                        ${isCurrentAdmin ? 'disabled' : ''}
                    >
                        ${getRoleOptions(user.role)}
                    </select>
                </td>

                <td>
                    ${createDepartmentSelect(
                        user.id,
                        user.department_id,
                        user.role,
                        isCurrentAdmin
                    )}
                </td>

                <td class="${statusClass}">
                    ${user.status}
                </td>

                <td>
                    <div class="action-group">

                        <button
                            type="button"
                            class="btn-secondary"
                            onclick="
                                changeRole(
                                    ${user.id}
                                )
                            "
                            ${isCurrentAdmin ? 'disabled' : ''}
                        >
                            Lưu quyền
                        </button>

                        <button
                            type="button"
                            class="btn-warning"
                            onclick="
                                changeStatus(
                                    ${user.id},
                                    '${nextStatus}'
                                )
                            "
                            ${isCurrentAdmin ? 'disabled' : ''}
                        >
                            ${statusButtonText}
                        </button>

                    </div>
                </td>
            `;

            tbody.appendChild(row);
        });

    } catch (error) {
        console.error(error);

        showMessage(
            'Không thể tải danh sách tài khoản.',
            'error'
        );
    }
}


/*
|--------------------------------------------------------------------------
| Thay đổi Role + department_id
|--------------------------------------------------------------------------
*/
async function changeRole(userId) {
    const roleSelect =
        document.getElementById(
            `role-${userId}`
        );

    const departmentSelect =
        document.getElementById(
            `department-${userId}`
        );

    if (!roleSelect) {
        showMessage(
            'Không tìm thấy Role.',
            'error'
        );

        return;
    }

    const role =
        roleSelect.value;

    let departmentId =
        departmentSelect
            ? departmentSelect.value
            : '';

    /*
     * Admin và Student
     * không thuộc phòng ban.
     */
    if (
        role === 'admin' ||
        role === 'student'
    ) {
        departmentId = '';
    }

    /*
     * Staff và Department Head
     * bắt buộc phải có phòng ban.
     */
    if (
        (
            role === 'department_head' ||
            role === 'staff'
        ) &&
        !departmentId
    ) {
        showMessage(
            'STAFF và DEPARTMENT HEAD phải được gán phòng ban.',
            'error'
        );

        return;
    }

    try {
        const response = await fetch(
            `/api/users/${userId}/role`,
            {
                method: 'PUT',

                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + token
                },

                body: JSON.stringify({
                    role: role,

                    department_id:
                        departmentId
                            ? Number(departmentId)
                            : null
                })
            }
        );

        const result =
            await response.json();

        if (!response.ok) {
            showMessage(
                result.message ||
                'Không thể thay đổi quyền tài khoản.',
                'error'
            );

            await loadUsers();

            return;
        }

        showMessage(
            result.message ||
            'Thay đổi quyền tài khoản thành công.',
            'success'
        );

        await loadUsers();

    } catch (error) {
        console.error(error);

        showMessage(
            'Không thể kết nối đến máy chủ.',
            'error'
        );
    }
}


/*
|--------------------------------------------------------------------------
| Khóa / mở khóa tài khoản
|--------------------------------------------------------------------------
*/
async function changeStatus(
    userId,
    status
) {
    try {
        const response = await fetch(
            `/api/users/${userId}/status`,
            {
                method: 'PUT',

                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + token
                },

                body: JSON.stringify({
                    status: status
                })
            }
        );

        const result =
            await response.json();

        if (!response.ok) {
            showMessage(
                result.message ||
                'Không thể cập nhật trạng thái tài khoản.',
                'error'
            );

            return;
        }

        showMessage(
            result.message ||
            'Cập nhật trạng thái tài khoản thành công.',
            'success'
        );

        await loadUsers();

    } catch (error) {
        console.error(error);

        showMessage(
            'Không thể kết nối đến máy chủ.',
            'error'
        );
    }
}


/*
|--------------------------------------------------------------------------
| Đăng xuất
|--------------------------------------------------------------------------
| JWT hiện tại là stateless.
| Chỉ cần xóa token phía client.
*/
document
    .getElementById('logoutButton')
    .addEventListener(
        'click',
        function () {
            localStorage.removeItem(
                'access_token'
            );

            localStorage.removeItem(
                'current_user'
            );

            window.location.href =
                '/login';
        }
    );


/*
|--------------------------------------------------------------------------
| Khởi động trang
|--------------------------------------------------------------------------
*/
(async function () {
    const isAdmin =
        await checkAdmin();

    if (isAdmin) {
        await loadUsers();
    }
})();
</script>

</body>
</html>