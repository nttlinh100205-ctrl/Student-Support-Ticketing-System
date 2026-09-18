<!DOCTYPE html>
<html lang="vi">
<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Đăng ký - Student Support</title>

    <link rel="stylesheet"
          href="/css/app.css">

</head>

<body>

<div class="auth-container">

    <div class="auth-card">

        <h1>Student Support</h1>

        <h2>Đăng ký tài khoản</h2>

        <p class="subtitle">
            Tạo tài khoản sinh viên mới
        </p>

        <form id="registerForm">

            <div class="form-group">

                <label for="name">
                    Họ và tên
                </label>

                <input
                    type="text"
                    id="name"
                    placeholder="Nguyễn Văn A"
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
                    placeholder="example@gmail.com"
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
                    placeholder="0901234567"
                >

            </div>

            <div class="form-group">

                <label for="password">
                    Mật khẩu
                </label>

                <input
                    type="password"
                    id="password"
                    placeholder="Ít nhất 8 ký tự"
                    required
                >

            </div>

            <div class="form-group">

                <label for="password_confirmation">
                    Xác nhận mật khẩu
                </label>

                <input
                    type="password"
                    id="password_confirmation"
                    placeholder="Nhập lại mật khẩu"
                    required
                >

            </div>

            <button
                type="submit"
                class="btn-primary">

                Đăng ký

            </button>

        </form>

        <div id="message"></div>

        <p class="switch-page">

            Đã có tài khoản?

            <a href="/login">
                Đăng nhập
            </a>

        </p>

    </div>

</div>


<script>

document
    .getElementById('registerForm')
    .addEventListener('submit', async function(event) {

        event.preventDefault();

        const message =
            document.getElementById('message');

        const body = {

            name:
                document
                    .getElementById('name')
                    .value
                    .trim(),

            email:
                document
                    .getElementById('email')
                    .value
                    .trim(),

            phone:
                document
                    .getElementById('phone')
                    .value
                    .trim(),

            password:
                document
                    .getElementById('password')
                    .value,

            password_confirmation:
                document
                    .getElementById(
                        'password_confirmation'
                    )
                    .value

        };

        message.className = 'message';

        message.textContent =
            'Đang tạo tài khoản...';

        try {

            const response =
                await fetch(
                    '/api/v1/auth/register',
                    {
                        method: 'POST',

                        headers: {
                            'Content-Type':
                                'application/json',

                            'Accept':
                                'application/json'
                        },

                        body: JSON.stringify(body)
                    }
                );

            const data =
                await response.json();

            if (!response.ok) {

                message.className =
                    'message error';

                message.textContent =
                    data.message ||
                    'Đăng ký thất bại.';

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
                'Đăng ký thành công!';

            setTimeout(function() {

                window.location.href =
                    '/profile';

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