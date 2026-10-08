<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Hồ sơ cá nhân - Student Support</title>

    <link rel="stylesheet" href="/css/app.css">

    <style>
        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }

        .profile-header h2 {
            margin: 0;
        }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 13px;
        }

        .badge-role {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .badge-active {
            background: #dcfce7;
            color: #166534;
        }

        .badge-locked {
            background: #fee2e2;
            color: #991b1b;
        }

        .profile-info {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin: 20px 0;
        }

        .profile-info-item {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            min-width: 180px;
        }

        .profile-info-item strong {
            display: block;
            margin-bottom: 8px;
        }

        .admin-link {
            display: none;
            padding: 9px 14px;
            border-radius: 7px;
            background: #2563eb;
            color: white;
        }

        .admin-link:hover {
            opacity: 0.9;
            color: white;
        }

        .form-actions {
            margin-top: 20px;
        }

        .password-note {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 20px;
        }

        /*
        |--------------------------------------------------------------------------
        | Ảnh đại diện
        |--------------------------------------------------------------------------
        */

        .avatar-section {
            display: flex;
            align-items: center;
            gap: 25px;
            flex-wrap: wrap;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8fafc;
            border-radius: 10px;
        }

        .avatar-wrapper {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            overflow: hidden;
            background: #e2e8f0;
            border: 3px solid #cbd5e1;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-shrink: 0;
        }

        .avatar-preview {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
        }

        .avatar-placeholder {
            text-align: center;
            color: #64748b;
            font-size: 14px;
            padding: 10px;
        }

        .avatar-controls {
            flex: 1;
            min-width: 250px;
        }

        .avatar-controls h3 {
            margin-top: 0;
            margin-bottom: 8px;
        }

        .avatar-note {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .avatar-controls input[type="file"] {
            display: block;
            margin-bottom: 15px;
        }

        #avatarMessage {
            margin-top: 15px;
        }
    </style>
</head>

<body>

<div class="dashboard">

    <header class="topbar">

        <h1>Student Support</h1>

        <nav>

            <a
                id="adminLink"
                class="admin-link"
                href="/admin/users"
            >
                Quản lý tài khoản
            </a>

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

        <!-- ======================================================
             HỒ SƠ CÁ NHÂN
        ======================================================= -->
        <section class="card">

            <div class="profile-header">

                <div>
                    <h2>Hồ sơ cá nhân</h2>

                    <p>
                        Xem và cập nhật thông tin tài khoản của bạn.
                    </p>
                </div>

            </div>

            <div id="profileMessage"></div>


            <!-- ==================================================
                 ẢNH ĐẠI DIỆN
            =================================================== -->
            <div class="avatar-section">

                <div class="avatar-wrapper">

                    <img
                        id="avatarPreview"
                        class="avatar-preview"
                        src=""
                        alt="Ảnh đại diện"
                    >

                    <div
                        id="avatarPlaceholder"
                        class="avatar-placeholder"
                    >
                        Chưa có<br>ảnh đại diện
                    </div>

                </div>


                <div class="avatar-controls">

                    <h3>Ảnh đại diện</h3>

                    <p class="avatar-note">
                        Chấp nhận JPG, JPEG, PNG hoặc WEBP.
                        Dung lượng tối đa 2MB.
                    </p>

                    <input
                        type="file"
                        id="avatarInput"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                    >

                    <button
                        type="button"
                        class="btn-primary"
                        id="avatarUploadButton"
                    >
                        Cập nhật ảnh đại diện
                    </button>

                    <div id="avatarMessage"></div>

                </div>

            </div>


            <!-- ==================================================
                 FORM HỒ SƠ
            =================================================== -->
            <form id="profileForm">

                <div class="form-group">

                    <label for="full_name">
                        Họ và tên
                    </label>

                    <input
                        type="text"
                        id="full_name"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="email">
                        Email
                    </label>

                    <input
                        type="email"
                        id="email"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="phone">
                        Số điện thoại
                    </label>

                    <input
                        type="text"
                        id="phone"
                    >

                </div>


                <div class="profile-info">

                    <div class="profile-info-item">

                        <strong>Vai trò</strong>

                        <span
                            id="role"
                            class="badge badge-role"
                        >
                            -
                        </span>

                    </div>


                    <div class="profile-info-item">

                        <strong>Trạng thái</strong>

                        <span
                            id="status"
                            class="badge"
                        >
                            -
                        </span>

                    </div>


                    <div
                        class="profile-info-item"
                        id="departmentContainer"
                    >

                        <strong>Phòng ban</strong>

                        <span id="department">
                            Không thuộc phòng ban
                        </span>

                    </div>

                </div>


                <div class="form-actions">

                    <button
                        type="submit"
                        class="btn-primary"
                    >
                        Cập nhật hồ sơ
                    </button>

                </div>

            </form>

        </section>


        <!-- ======================================================
             ĐỔI MẬT KHẨU
        ======================================================= -->
        <section class="card">

            <h2>Đổi mật khẩu</h2>

            <p class="password-note">
                Mật khẩu mới phải có ít nhất 8 ký tự.
            </p>

            <div id="passwordMessage"></div>

            <form id="passwordForm">

                <div class="form-group">

                    <label for="current_password">
                        Mật khẩu hiện tại
                    </label>

                    <input
                        type="password"
                        id="current_password"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="new_password">
                        Mật khẩu mới
                    </label>

                    <input
                        type="password"
                        id="new_password"
                        minlength="8"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="new_password_confirmation">
                        Xác nhận mật khẩu mới
                    </label>

                    <input
                        type="password"
                        id="new_password_confirmation"
                        minlength="8"
                        required
                    >

                </div>


                <button
                    type="submit"
                    class="btn-primary"
                >
                    Đổi mật khẩu
                </button>

            </form>

        </section>

    </main>

</div>


<script>
const token =
    localStorage.getItem('access_token');

let currentUser = null;


/*
|--------------------------------------------------------------------------
| Không có JWT thì quay về đăng nhập
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
function showMessage(
    elementId,
    message,
    type = ''
) {
    const element =
        document.getElementById(elementId);

    element.className =
        type
            ? `message ${type}`
            : 'message';

    element.textContent =
        message;
}


/*
|--------------------------------------------------------------------------
| Role
|--------------------------------------------------------------------------
*/
function formatRole(role) {
    const roleNames = {
        admin: 'ADMIN',
        student: 'STUDENT',
        staff: 'STAFF',
        department_head: 'DEPARTMENT HEAD'
    };

    return roleNames[role] || role;
}


/*
|--------------------------------------------------------------------------
| Phòng ban
|--------------------------------------------------------------------------
*/
function formatDepartment(departmentId) {
    if (!departmentId) {
        return 'Không thuộc phòng ban';
    }

    if (Number(departmentId) === 1) {
        return 'Phòng Hỗ trợ Sinh viên (ID: 1)';
    }

    return `Phòng ban ID: ${departmentId}`;
}


/*
|--------------------------------------------------------------------------
| Hiển thị avatar
|--------------------------------------------------------------------------
*/
function displayAvatar(user) {
    const avatarPreview =
        document.getElementById('avatarPreview');

    const avatarPlaceholder =
        document.getElementById('avatarPlaceholder');

    if (user.avatar_url) {

        /*
         * Thêm timestamp để trình duyệt
         * không cache ảnh cũ sau khi upload.
         */
        avatarPreview.src =
            user.avatar_url +
            '?v=' +
            Date.now();

        avatarPreview.style.display =
            'block';

        avatarPlaceholder.style.display =
            'none';

    } else {

        avatarPreview.removeAttribute('src');

        avatarPreview.style.display =
            'none';

        avatarPlaceholder.style.display =
            'block';
    }
}


/*
|--------------------------------------------------------------------------
| Đổ dữ liệu user lên giao diện
|--------------------------------------------------------------------------
*/
function displayUser(user) {
    document
        .getElementById('full_name')
        .value =
        user.full_name || '';

    document
        .getElementById('email')
        .value =
        user.email || '';

    document
        .getElementById('phone')
        .value =
        user.phone || '';

    document
        .getElementById('role')
        .textContent =
        formatRole(user.role);

    const statusElement =
        document.getElementById('status');

    statusElement.textContent =
        user.status || '-';

    statusElement.className =
        user.status === 'ACTIVE'
            ? 'badge badge-active'
            : 'badge badge-locked';

    document
        .getElementById('department')
        .textContent =
        formatDepartment(
            user.department_id
        );

    displayAvatar(user);

    /*
     * Chỉ Admin mới thấy
     * Quản lý tài khoản.
     */
    if (user.role === 'admin') {
        document
            .getElementById('adminLink')
            .style.display =
            'inline-block';
    } else {
        document
            .getElementById('adminLink')
            .style.display =
            'none';
    }
}


/*
|--------------------------------------------------------------------------
| Tải hồ sơ
|--------------------------------------------------------------------------
*/
async function loadProfile() {
    try {

        const response =
            await fetch(
                '/api/profile',
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

        const result =
            await response.json();

        if (!response.ok) {

            showMessage(
                'profileMessage',
                result.message ||
                'Không thể tải hồ sơ.',
                'error'
            );

            return;
        }

        currentUser =
            result.data;

        displayUser(currentUser);

        localStorage.setItem(
            'current_user',
            JSON.stringify(currentUser)
        );

    } catch (error) {

        console.error(error);

        showMessage(
            'profileMessage',
            'Không thể kết nối đến máy chủ.',
            'error'
        );
    }
}


/*
|--------------------------------------------------------------------------
| Xem trước ảnh khi chọn file
|--------------------------------------------------------------------------
*/
document
    .getElementById('avatarInput')
    .addEventListener(
        'change',
        function () {

            const file =
                this.files[0];

            if (!file) {
                return;
            }

            const allowedTypes = [
                'image/jpeg',
                'image/png',
                'image/webp'
            ];

            if (
                !allowedTypes.includes(
                    file.type
                )
            ) {
                showMessage(
                    'avatarMessage',
                    'Ảnh chỉ được phép là JPG, JPEG, PNG hoặc WEBP.',
                    'error'
                );

                this.value = '';

                return;
            }

            if (
                file.size >
                2 * 1024 * 1024
            ) {
                showMessage(
                    'avatarMessage',
                    'Ảnh đại diện không được vượt quá 2MB.',
                    'error'
                );

                this.value = '';

                return;
            }

            const avatarPreview =
                document.getElementById(
                    'avatarPreview'
                );

            const avatarPlaceholder =
                document.getElementById(
                    'avatarPlaceholder'
                );

            avatarPreview.src =
                URL.createObjectURL(file);

            avatarPreview.style.display =
                'block';

            avatarPlaceholder.style.display =
                'none';

            showMessage(
                'avatarMessage',
                'Đã chọn ảnh. Bấm "Cập nhật ảnh đại diện" để lưu.'
            );
        }
    );


/*
|--------------------------------------------------------------------------
| Upload ảnh đại diện
|--------------------------------------------------------------------------
*/
document
    .getElementById('avatarUploadButton')
    .addEventListener(
        'click',
        async function () {

            const input =
                document.getElementById(
                    'avatarInput'
                );

            const file =
                input.files[0];

            if (!file) {

                showMessage(
                    'avatarMessage',
                    'Vui lòng chọn ảnh đại diện.',
                    'error'
                );

                return;
            }

            const button =
                document.getElementById(
                    'avatarUploadButton'
                );

            button.disabled = true;

            showMessage(
                'avatarMessage',
                'Đang tải ảnh đại diện...'
            );

            const formData =
                new FormData();

            formData.append(
                'avatar',
                file
            );

            try {

                const response =
                    await fetch(
                        '/api/profile/avatar',
                        {
                            method: 'POST',

                            headers: {
                                'Accept':
                                    'application/json',

                                'Authorization':
                                    'Bearer ' + token
                            },

                            body: formData
                        }
                    );

                if (
                    response.status === 401
                ) {
                    localStorage.clear();

                    window.location.href =
                        '/login';

                    return;
                }

                const result =
                    await response.json();

                if (!response.ok) {

                    let errorMessage =
                        result.message ||
                        'Cập nhật ảnh đại diện thất bại.';

                    if (result.errors) {

                        const firstError =
                            Object.values(
                                result.errors
                            )[0];

                        if (
                            Array.isArray(
                                firstError
                            ) &&
                            firstError.length > 0
                        ) {
                            errorMessage =
                                firstError[0];
                        }
                    }

                    showMessage(
                        'avatarMessage',
                        errorMessage,
                        'error'
                    );

                    return;
                }

                currentUser =
                    result.data;

                displayUser(
                    currentUser
                );

                localStorage.setItem(
                    'current_user',
                    JSON.stringify(
                        currentUser
                    )
                );

                input.value = '';

                showMessage(
                    'avatarMessage',
                    result.message ||
                    'Cập nhật ảnh đại diện thành công.',
                    'success'
                );

            } catch (error) {

                console.error(error);

                showMessage(
                    'avatarMessage',
                    'Không thể kết nối đến máy chủ.',
                    'error'
                );

            } finally {

                button.disabled = false;
            }
        }
    );


/*
|--------------------------------------------------------------------------
| Cập nhật hồ sơ
|--------------------------------------------------------------------------
*/
document
    .getElementById('profileForm')
    .addEventListener(
        'submit',
        async function (event) {

            event.preventDefault();

            showMessage(
                'profileMessage',
                'Đang cập nhật hồ sơ...'
            );

            try {

                const response =
                    await fetch(
                        '/api/profile',
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
                                full_name:
                                    document
                                        .getElementById(
                                            'full_name'
                                        )
                                        .value
                                        .trim(),

                                email:
                                    document
                                        .getElementById(
                                            'email'
                                        )
                                        .value
                                        .trim(),

                                phone:
                                    document
                                        .getElementById(
                                            'phone'
                                        )
                                        .value
                                        .trim() || null
                            })
                        }
                    );

                const result =
                    await response.json();

                if (
                    response.status === 401
                ) {
                    localStorage.clear();

                    window.location.href =
                        '/login';

                    return;
                }

                if (!response.ok) {

                    let errorMessage =
                        result.message ||
                        'Cập nhật hồ sơ thất bại.';

                    if (result.errors) {

                        const firstError =
                            Object.values(
                                result.errors
                            )[0];

                        if (
                            Array.isArray(
                                firstError
                            ) &&
                            firstError.length > 0
                        ) {
                            errorMessage =
                                firstError[0];
                        }
                    }

                    showMessage(
                        'profileMessage',
                        errorMessage,
                        'error'
                    );

                    return;
                }

                currentUser =
                    result.data;

                displayUser(
                    currentUser
                );

                localStorage.setItem(
                    'current_user',
                    JSON.stringify(
                        currentUser
                    )
                );

                showMessage(
                    'profileMessage',
                    result.message ||
                    'Cập nhật hồ sơ thành công.',
                    'success'
                );

            } catch (error) {

                console.error(error);

                showMessage(
                    'profileMessage',
                    'Không thể kết nối đến máy chủ.',
                    'error'
                );
            }
        }
    );


/*
|--------------------------------------------------------------------------
| Đổi mật khẩu
|--------------------------------------------------------------------------
*/
document
    .getElementById('passwordForm')
    .addEventListener(
        'submit',
        async function (event) {

            event.preventDefault();

            const currentPassword =
                document
                    .getElementById(
                        'current_password'
                    )
                    .value;

            const newPassword =
                document
                    .getElementById(
                        'new_password'
                    )
                    .value;

            const confirmation =
                document
                    .getElementById(
                        'new_password_confirmation'
                    )
                    .value;

            if (
                newPassword !==
                confirmation
            ) {

                showMessage(
                    'passwordMessage',
                    'Xác nhận mật khẩu mới không khớp.',
                    'error'
                );

                return;
            }

            showMessage(
                'passwordMessage',
                'Đang đổi mật khẩu...'
            );

            try {

                const response =
                    await fetch(
                        '/api/profile/password',
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
                                current_password:
                                    currentPassword,

                                password:
                                    newPassword,

                                password_confirmation:
                                    confirmation
                            })
                        }
                    );

                const result =
                    await response.json();

                if (
                    response.status === 401
                ) {
                    localStorage.clear();

                    window.location.href =
                        '/login';

                    return;
                }

                if (!response.ok) {

                    let errorMessage =
                        result.message ||
                        'Đổi mật khẩu thất bại.';

                    if (result.errors) {

                        const firstError =
                            Object.values(
                                result.errors
                            )[0];

                        if (
                            Array.isArray(
                                firstError
                            ) &&
                            firstError.length > 0
                        ) {
                            errorMessage =
                                firstError[0];
                        }
                    }

                    showMessage(
                        'passwordMessage',
                        errorMessage,
                        'error'
                    );

                    return;
                }

                showMessage(
                    'passwordMessage',
                    result.message ||
                    'Đổi mật khẩu thành công.',
                    'success'
                );

                document
                    .getElementById(
                        'passwordForm'
                    )
                    .reset();

            } catch (error) {

                console.error(error);

                showMessage(
                    'passwordMessage',
                    'Không thể kết nối đến máy chủ.',
                    'error'
                );
            }
        }
    );


/*
|--------------------------------------------------------------------------
| Đăng xuất
|--------------------------------------------------------------------------
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
loadProfile();
</script>

</body>
</html>