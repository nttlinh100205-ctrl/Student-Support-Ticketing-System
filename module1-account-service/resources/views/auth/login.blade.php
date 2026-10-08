<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Đăng nhập - Student Support</title>

    <link rel="stylesheet" href="/css/app.css">
</head>

<body>

<div class="auth-container">

    <div class="auth-card">

        <h1>Student Support</h1>

        <h2>Đăng nhập</h2>

        <p class="subtitle">
            Hệ thống tiếp nhận và xử lý yêu cầu hỗ trợ sinh viên
        </p>

        <form id="loginForm">

            <div class="form-group">
                <label for="email">
                    Email
                </label>

                <input
                    type="email"
                    id="email"
                    placeholder="Nhập email"
                    autocomplete="email"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">
                    Mật khẩu
                </label>

                <input
                    type="password"
                    id="password"
                    placeholder="Nhập mật khẩu"
                    autocomplete="current-password"
                    required
                >
            </div>

            <p style="text-align: right; margin-top: -5px;">
                <a href="/forgot-password">
                    Quên mật khẩu?
                </a>
            </p>

            <button
                type="submit"
                class="btn-primary"
                id="loginButton"
            >
                Đăng nhập
            </button>

        </form>

        <div id="message"></div>

        <p class="switch-page">
            Chưa có tài khoản?
            <a href="/register">
                Đăng ký
            </a>
        </p>

    </div>

</div>

<script>
document
    .getElementById('loginForm')
    .addEventListener(
        'submit',
        async function (event) {

            event.preventDefault();

            const email =
                document
                    .getElementById('email')
                    .value
                    .trim();

            const password =
                document
                    .getElementById('password')
                    .value;

            const message =
                document
                    .getElementById('message');

            const button =
                document
                    .getElementById(
                        'loginButton'
                    );

            button.disabled = true;

            message.className =
                'message';

            message.textContent =
                'Đang đăng nhập...';

            try {

                const response =
                    await fetch(
                        '/api/auth/login',
                        {
                            method: 'POST',

                            headers: {
                                'Content-Type':
                                    'application/json',

                                'Accept':
                                    'application/json'
                            },

                            body: JSON.stringify({
                                email: email,
                                password: password
                            })
                        }
                    );

                const result =
                    await response.json();

                if (!response.ok) {

                    message.className =
                        'message error';

                    message.textContent =
                        result.message ||
                        'Đăng nhập thất bại.';

                    return;
                }

                const token =
                    result.data.token;

                const user =
                    result.data.user;

                localStorage.setItem(
                    'access_token',
                    token
                );

                localStorage.setItem(
                    'current_user',
                    JSON.stringify(user)
                );

                message.className =
                    'message success';

                if (
                    user.must_change_password
                ) {
                    message.textContent =
                        'Đăng nhập thành công. Bạn cần đổi mật khẩu trước khi tiếp tục.';

                    setTimeout(
                        function () {
                            window.location.href =
                                '/change-password';
                        },
                        500
                    );

                    return;
                }

                message.textContent =
                    'Đăng nhập thành công!';

                setTimeout(
                    function () {
                        if (
                            user.role === 'admin'
                        ) {
                            window.location.href =
                                '/admin/users';
                        } else {
                            window.location.href =
                                '/profile';
                        }
                    },
                    500
                );

            } catch (error) {

                console.error(error);

                message.className =
                    'message error';

                message.textContent =
                    'Có lỗi xảy ra khi kết nối đến máy chủ.';

            } finally {

                button.disabled = false;
            }
        }
    );
</script>

</body>
</html>