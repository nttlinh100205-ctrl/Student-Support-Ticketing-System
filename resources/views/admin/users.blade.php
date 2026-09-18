<!DOCTYPE html>
<html lang="vi">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Quản lý tài khoản - ADMIN
    </title>

    <link rel="stylesheet"
          href="/css/app.css">

</head>

<body>

<div class="dashboard">

    <header class="topbar">

        <h1>
            Student Support - ADMIN
        </h1>

        <nav>

            <a href="/profile">
                Hồ sơ
            </a>

            <button
                id="logoutButton"
                class="btn-danger">

                Đăng xuất

            </button>

        </nav>

    </header>


    <main class="content">

        <section class="card">

            <h2>
                Quản lý tài khoản
            </h2>

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

const token =
    localStorage.getItem('access_token');

let currentAdminId = null;


/*
 * Danh sách phòng ban hiện tại.
 *
 * Hiện hệ thống của bạn đang có:
 * ID 1 - SUPPORT - Phòng Hỗ trợ Sinh viên
 *
 * Khi sau này có API quản lý phòng ban,
 * danh sách này có thể chuyển sang tải
 * động từ backend.
 */
const departments = [
    {
        id: 1,
        name: 'Phòng Hỗ trợ Sinh viên',
        code: 'SUPPORT'
    }
];


/*
 * Nếu không có token
 */
if (!token) {

    window.location.href = '/login';

}


/*
 * Hiển thị thông báo
 */
function showMessage(message, type = '') {

    const element =
        document.getElementById('message');

    element.className =
        type ? `message ${type}` : 'message';

    element.textContent =
        message;

}


/*
 * Tạo danh sách Role
 */
function getRoleOptions(selectedRole, disabled) {

    const roles = [
        'ADMIN',
        'DEPARTMENT_HEAD',
        'STAFF',
        'STUDENT'
    ];

    return roles.map(function(role) {

        return `
            <option
                value="${role}"
                ${selectedRole === role ? 'selected' : ''}
                ${disabled ? 'disabled' : ''}>

                ${role}

            </option>
        `;

    }).join('');

}


/*
 * Tạo danh sách phòng ban
 */
function getDepartmentOptions(
    selectedDepartmentId,
    selectedRole,
    disabled
) {

    const departmentDisabled =
        disabled ||
        selectedRole === 'ADMIN' ||
        selectedRole === 'STUDENT';


    let html = `

        <option value="">
            Không thuộc phòng ban
        </option>

    `;


    departments.forEach(function(department) {

        html += `

            <option
                value="${department.id}"
                ${
                    Number(selectedDepartmentId) ===
                    Number(department.id)
                        ? 'selected'
                        : ''
                }>

                ${department.name}
                (${department.code})

            </option>

        `;

    });


    /*
     * Khi ADMIN hoặc STUDENT,
     * bắt buộc không thuộc phòng ban.
     */
    if (
        selectedRole === 'ADMIN' ||
        selectedRole === 'STUDENT'
    ) {

        html = `

            <option value="" selected>
                Không thuộc phòng ban
            </option>

        `;

    }


    return `
        <select
            id="department-${arguments[0]}"
            ${departmentDisabled ? 'disabled' : ''}
            onchange="
                handleRoleChange(
                    ${arguments[0]},
                    this
                )
            ">

            ${html}

        </select>
    `;
}


/*
 * Bản an toàn hơn để tạo select phòng ban.
 */
function createDepartmentSelect(
    userId,
    selectedDepartmentId,
    selectedRole,
    disabled
) {

    const departmentDisabled =
        disabled ||
        selectedRole === 'ADMIN' ||
        selectedRole === 'STUDENT';


    let options = `
        <option value="">
            Không thuộc phòng ban
        </option>
    `;


    if (
        selectedRole !== 'ADMIN' &&
        selectedRole !== 'STUDENT'
    ) {

        departments.forEach(function(department) {

            const selected =
                Number(selectedDepartmentId) ===
                Number(department.id)
                    ? 'selected'
                    : '';

            options += `
                <option
                    value="${department.id}"
                    ${selected}>

                    ${department.name}
                    (${department.code})

                </option>
            `;

        });

    }


    return `
        <select
            id="department-${userId}"
            ${departmentDisabled ? 'disabled' : ''}>

            ${options}

        </select>
    `;

}


/*
 * Khi đổi Role trong giao diện
 *
 * ADMIN và STUDENT:
 *    không có phòng ban
 *
 * DEPARTMENT_HEAD và STAFF:
 *    có thể chọn phòng ban
 */
function handleRoleChange(
    userId,
    selectElement
) {

    const role =
        selectElement.value;

    const departmentSelect =
        document.getElementById(
            `department-${userId}`
        );


    if (!departmentSelect) {
        return;
    }


    if (
        role === 'ADMIN' ||
        role === 'STUDENT'
    ) {

        departmentSelect.value = '';

        departmentSelect.disabled = true;

    } else {

        departmentSelect.disabled = false;

    }

}


/*
 * Kiểm tra tài khoản hiện tại có phải ADMIN không
 */
async function checkAdmin() {

    try {

        const response =
            await fetch(
                '/api/v1/auth/me',
                {
                    method: 'GET',

                    headers: {
                        'Accept':
                            'application/json',

                        'Authorization':
                            'Bearer ' + token
                    }
                }
            );


        if (!response.ok) {

            localStorage.clear();

            window.location.href =
                '/login';

            return false;

        }


        const data =
            await response.json();


        if (
            !data.user ||
            data.user.role !== 'ADMIN'
        ) {

            alert(
                'Bạn không có quyền truy cập trang ADMIN.'
            );

            window.location.href =
                '/profile';

            return false;

        }


        currentAdminId =
            Number(data.user.id);


        return true;


    } catch (error) {

        localStorage.clear();

        window.location.href =
            '/login';

        return false;

    }

}


/*
 * Tải danh sách tài khoản
 */
async function loadUsers() {

    try {

        const response =
            await fetch(
                '/api/v1/admin/users',
                {
                    method: 'GET',

                    headers: {
                        'Accept':
                            'application/json',

                        'Authorization':
                            'Bearer ' + token
                    }
                }
            );


        if (response.status === 401) {

            localStorage.clear();

            window.location.href =
                '/login';

            return;

        }


        if (response.status === 403) {

            alert(
                'Bạn không có quyền quản lý tài khoản.'
            );

            window.location.href =
                '/profile';

            return;

        }


        const data =
            await response.json();


        if (!response.ok) {

            showMessage(
                data.message ||
                'Không thể tải danh sách tài khoản.',
                'error'
            );

            return;

        }


        const tbody =
            document.getElementById(
                'userTable'
            );


        tbody.innerHTML = '';


        const users =
            data.data?.data || [];


        users.forEach(function(user) {

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


            const roleDisabled =
                isCurrentAdmin;


            const statusDisabled =
                isCurrentAdmin;


            row.innerHTML = `

                <td>
                    ${user.id}
                </td>

                <td>
                    ${user.name || ''}
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
                        ${roleDisabled ? 'disabled' : ''}>

                        ${getRoleOptions(
                            user.role,
                            false
                        )}

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

                <td>
                    ${user.status}
                </td>

                <td>

                    <button
                        class="btn-small"
                        onclick="
                            changeRole(
                                ${user.id}
                            )
                        "
                        ${roleDisabled ? 'disabled' : ''}>

                        Lưu quyền

                    </button>


                    <button
                        class="btn-small"
                        onclick="
                            changeStatus(
                                ${user.id},
                                '${nextStatus}'
                            )
                        "
                        ${statusDisabled ? 'disabled' : ''}>

                        ${statusButtonText}

                    </button>

                </td>

            `;


            tbody.appendChild(row);

        });


    } catch (error) {

        showMessage(
            'Không thể tải danh sách tài khoản.',
            'error'
        );

    }

}


/*
 * Thay đổi Role + Phòng ban
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
     * ADMIN và STUDENT không thuộc phòng ban
     */
    if (
        role === 'ADMIN' ||
        role === 'STUDENT'
    ) {

        departmentId = '';

    }


    /*
     * DEPARTMENT_HEAD và STAFF
     * bắt buộc phải có phòng ban
     */
    if (
        (
            role === 'DEPARTMENT_HEAD' ||
            role === 'STAFF'
        ) &&
        !departmentId
    ) {

        showMessage(
            'DEPARTMENT_HEAD và STAFF phải được gán phòng ban.',
            'error'
        );

        return;

    }


    try {

        const response =
            await fetch(
                `/api/v1/admin/users/${userId}/role`,
                {
                    method: 'PUT',

                    headers: {
                        'Content-Type':
                            'application/json',

                        'Accept':
                            'application/json',

                        'Authorization':
                            'Bearer ' + token
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


        const data =
            await response.json();


        if (!response.ok) {

            showMessage(
                data.message ||
                'Không thể thay đổi quyền tài khoản.',
                'error'
            );

            await loadUsers();

            return;

        }


        showMessage(
            data.message ||
            'Thay đổi quyền tài khoản thành công.',
            'success'
        );


        await loadUsers();


    } catch (error) {

        showMessage(
            'Không thể kết nối đến máy chủ.',
            'error'
        );

    }

}


/*
 * Khóa / mở khóa tài khoản
 */
async function changeStatus(
    userId,
    status
) {

    try {

        const response =
            await fetch(
                `/api/v1/admin/users/${userId}/status`,
                {
                    method: 'PUT',

                    headers: {
                        'Content-Type':
                            'application/json',

                        'Accept':
                            'application/json',

                        'Authorization':
                            'Bearer ' + token
                    },

                    body: JSON.stringify({
                        status: status
                    })
                }
            );


        const data =
            await response.json();


        if (!response.ok) {

            showMessage(
                data.message ||
                'Không thể cập nhật trạng thái tài khoản.',
                'error'
            );

            return;

        }


        showMessage(
            data.message ||
            'Cập nhật trạng thái tài khoản thành công.',
            'success'
        );


        await loadUsers();


    } catch (error) {

        showMessage(
            'Không thể kết nối đến máy chủ.',
            'error'
        );

    }

}


/*
 * Logout
 */
document
    .getElementById('logoutButton')
    .addEventListener(
        'click',
        async function() {

            try {

                await fetch(
                    '/api/v1/auth/logout',
                    {
                        method: 'POST',

                        headers: {
                            'Accept':
                                'application/json',

                            'Authorization':
                                'Bearer ' + token
                        }
                    }
                );

            } finally {

                localStorage.clear();

                window.location.href =
                    '/login';

            }

        }
    );


/*
 * Khởi động trang
 */
(async function() {

    const isAdmin =
        await checkAdmin();


    if (isAdmin) {

        await loadUsers();

    }

})();

</script>

</body>

</html>