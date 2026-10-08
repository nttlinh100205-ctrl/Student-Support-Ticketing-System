<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Đặt lại mật khẩu</title>

    <link rel="stylesheet" href="/css/app.css">
</head>

<body>

<div class="auth-container">

    <div class="auth-card">

        <h2>Đặt lại mật khẩu</h2>

        <div id="message"></div>

        <form id="resetPasswordForm">

            <input
                type="hidden"
                id="token"
                value="{{ $token }}"
            >

            <div class="form-group">
                <label for="email">
                    Email
                </label>

                <input
                    type="email"
                    id="email"
                    value="{{ $email }}"
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
                    required
                >
            </div>

            <button type="submit">
                Đặt lại mật khẩu
            </button>

        </form>

        <p>
            <a href="/login">
                Quay lại đăng nhập
            </a>
        </p>

    </div>

</div>

<script>
const form =
    document.getElementById('resetPasswordForm');

const message =
    document.getElementById('message');

form.addEventListener(
    'submit',
    async function (event) {
        event.preventDefault();

        message.textContent = '';

        const response = await fetch(
            '/api/auth/reset-password',
            {
                method: 'POST',

                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },

                body: JSON.stringify({
                    token:
                        document.getElementById('token').value,

                    email:
                        document.getElementById('email').value,

                    password:
                        document.getElementById('password').value,

                    password_confirmation:
                        document.getElementById(
                            'password_confirmation'
                        ).value
                })
            }
        );

        const result =
            await response.json();

        if (!response.ok) {
            message.textContent =
                result.message ||
                'Không thể đặt lại mật khẩu.';

            return;
        }

        message.textContent =
            result.message ||
            'Đặt lại mật khẩu thành công.';

        setTimeout(function () {
            window.location.href = '/login';
        }, 1500);
    }
);
</script>

</body>
</html>