<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Đổi mật khẩu lần đầu - Student Support</title>

    <link rel="stylesheet" href="/css/app.css">
</head>

<body>

<div class="auth-container">

    <div class="auth-card">

        <h1>Student Support</h1>

        <h2>Đổi mật khẩu</h2>

        <p class="subtitle">
            Bạn cần đổi mật khẩu trước khi tiếp tục sử dụng hệ thống.
        </p>

        <form id="changePasswordForm">

            <div class="form-group">
                <label for="current_password">
                    Mật khẩu hiện tại
                </label>

                <input
                    type="password"
                    id="current_password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">
                    Mật khẩu mới
                </label>

                <input
                    type="password"
                    id="password"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password_confirmation">
                    Xác nhận mật khẩu mới
                </label>

                <input
                    type="password"
                    id="password_confirmation"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >
            </div>

            <button
                type="submit"
                class="btn-primary"
                id="changePasswordButton"
            >
                Đổi mật khẩu
            </button>

        </form>

        <div id="message"></div>

    </div>

</div>

<script>
const token =
    localStorage.getItem('access_token');

const currentUserRaw =
    localStorage.getItem('current_user');

let currentUser = null;

if (!token) {
    window.location.href = '/login';
}

if (currentUserRaw) {
    try {
        currentUser =
            JSON.parse(currentUserRaw);
    } catch (error) {
        console.error(error);
    }
}

/*
|--------------------------------------------------------------------------
| Nếu tài khoản không còn bị bắt buộc đổi mật khẩu
|--------------------------------------------------------------------------
*/
if (
    currentUser &&
    currentUser.must_change_password === false
) {
    if (currentUser.role === 'admin') {
        window.location.href =
            '/admin/users';
    } else {
        window.location.href =
            '/profile';
    }
}

/*
|--------------------------------------------------------------------------
| Đổi mật khẩu
|--------------------------------------------------------------------------
*/
document
    .getElementById('changePasswordForm')
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

            const password =
                document
                    .getElementById(
                        'password'
                    )
                    .value;

            const passwordConfirmation =
                document
                    .getElementById(
                        'password_confirmation'
                    )
                    .value;

            const message =
                document.getElementById(
                    'message'
                );

            const button =
                document.getElementById(
                    'changePasswordButton'
                );

            if (
                password !==
                passwordConfirmation
            ) {
                message.className =
                    'message error';

                message.textContent =
                    'Xác nhận mật khẩu mới không khớp.';

                return;
            }

            button.disabled = true;

            message.className =
                'message';

            message.textContent =
                'Đang đổi mật khẩu...';

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
                                    password,

                                password_confirmation:
                                    passwordConfirmation
                            })
                        }
                    );

                const result =
                    await response.json();

                if (response.status === 401) {
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
                            Array.isArray(firstError) &&
                            firstError.length > 0
                        ) {
                            errorMessage =
                                firstError[0];
                        }
                    }

                    message.className =
                        'message error';

                    message.textContent =
                        errorMessage;

                    return;
                }

                if (!currentUser) {
                    currentUser = {};
                }

                currentUser.must_change_password =
                    false;

                localStorage.setItem(
                    'current_user',
                    JSON.stringify(currentUser)
                );

                message.className =
                    'message success';

                message.textContent =
                    result.message ||
                    'Đổi mật khẩu thành công.';

                setTimeout(
                    function () {
                        if (
                            currentUser.role ===
                            'admin'
                        ) {
                            window.location.href =
                                '/admin/users';
                        } else {
                            window.location.href =
                                '/profile';
                        }
                    },
                    800
                );

            } catch (error) {

                console.error(error);

                message.className =
                    'message error';

                message.textContent =
                    'Không thể kết nối đến máy chủ.';

            } finally {

                button.disabled = false;
            }
        }
    );
</script>

</body>
</html>