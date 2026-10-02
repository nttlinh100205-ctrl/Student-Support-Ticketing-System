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
                    required
                >

            </div>

            <button
                type="submit"
                class="btn-primary">

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
    .addEventListener('submit', async function(event) {

        event.preventDefault();

        const email =
            document.getElementById('email')
                .value.trim();

        const password =
            document.getElementById('password')
                .value;

        const message =
            document.getElementById('message');

        message.className = 'message';

        message.textContent =
            'Đang đăng nhập...';

        try {

            const response = await fetch(
                '/api/v1/auth/login',
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

            const data =
                await response.json();

            if (!response.ok) {

                message.className =
                    'message error';

                message.textContent =
                    data.message ||
                    'Đăng nhập thất bại.';

                return;
            }

            localStorage.setItem(
                'access_token',
                data.token
            );

            localStorage.setItem(
                'current_user',
                JSON.stringify(data.user)
            );

            message.className =
                'message success';

            message.textContent =
                'Đăng nhập thành công!';

            setTimeout(function() {

                if (data.user.role === 'ADMIN') {

                    window.location.href =
                        '/admin/users';

                } else {

                    window.location.href =
                        '/profile';

                }

            }, 500);

        } catch (error) {

            message.className =
                'message error';

            message.textContent =
                'Không thể kết nối đến máy chủ.';

        }

    });

</script>

</body>
</html>