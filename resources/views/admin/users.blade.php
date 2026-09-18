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
    localStorage.getItem(
        'access_token'
    );


if (!token) {

    window.location.href =
        '/login';

}


/*
 * Kiểm tra Admin
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


        if (data.user.role !== 'ADMIN') {

            alert(
                'Bạn không có quyền truy cập trang ADMIN.'
            );

            window.location.href =
                '/profile';

            return false;

        }


        return true;


    } catch (error) {

        localStorage.clear();

        window.location.href =
            '/login';

        return false;

    }

}


/*
 * Tải danh sách User
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


        const tbody =
            document.getElementById(
                'userTable'
            );


        tbody.innerHTML = '';


        data.data.data.forEach(
            function(user) {

                const row =
                    document.createElement(
                        'tr'
                    );


                const statusButton =
                    user.status === 'ACTIVE'
                        ? 'Khóa'
                        : 'Mở khóa';


                const nextStatus =
                    user.status === 'ACTIVE'
                        ? 'LOCKED'
                        : 'ACTIVE';


                row.innerHTML = `

                    <td>
                        ${user.id}
                    </td>

                    <td>
                        ${user.name}
                    </td>

                    <td>
                        ${user.email}
                    </td>

                    <td>
                        ${user.phone || ''}
                    </td>

                    <td>

                        <select
                            onchange="
                                changeRole(
                                    ${user.id},
                                    this.value
                                )
                            ">

                            <option
                                value="STUDENT"
                                ${user.role === 'STUDENT'
                                    ? 'selected'
                                    : ''}>
                                STUDENT
                            </option>

                            <option
                                value="STAFF"
                                ${user.role === 'STAFF'
                                    ? 'selected'
                                    : ''}>
                                STAFF
                            </option>

                            <option
                                value="ADMIN"
                                ${user.role === 'ADMIN'
                                    ? 'selected'
                                    : ''}>
                                ADMIN
                            </option>

                        </select>

                    </td>

                    <td>
                        ${user.status}
                    </td>

                    <td>

                        <button
                            class="btn-small"
                            onclick="
                                changeStatus(
                                    ${user.id},
                                    '${nextStatus}'
                                )
                            ">

                            ${statusButton}

                        </button>

                    </td>

                `;


                tbody.appendChild(row);

            }
        );


    } catch (error) {

        document.getElementById(
            'message'
        ).textContent =
            'Không thể tải danh sách tài khoản.';

    }

}


/*
 * Đổi Role
 */
async function changeRole(
    userId,
    role
) {

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
                        role: role
                    })
                }
            );


        const data =
            await response.json();


        const message =
            document.getElementById(
                'message'
            );


        if (!response.ok) {

            message.className =
                'message error';

            message.textContent =
                data.message ||
                'Không thể thay đổi role.';

            loadUsers();

            return;

        }


        message.className =
            'message success';

        message.textContent =
            data.message;


        loadUsers();


    } catch (error) {

        alert(
            'Không thể kết nối đến máy chủ.'
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


        const message =
            document.getElementById(
                'message'
            );


        if (!response.ok) {

            message.className =
                'message error';

            message.textContent =
                data.message ||
                'Không thể cập nhật trạng thái.';

            return;

        }


        message.className =
            'message success';

        message.textContent =
            data.message;


        loadUsers();


    } catch (error) {

        alert(
            'Không thể kết nối đến máy chủ.'
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