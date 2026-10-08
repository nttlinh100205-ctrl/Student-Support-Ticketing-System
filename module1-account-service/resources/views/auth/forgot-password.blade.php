<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Quên mật khẩu - Student Support</title>

    <link rel="stylesheet" href="/css/app.css">
</head>

<body>

<div class="auth-container">

    <div class="auth-card">

        <h1>Student Support</h1>

        <h2>Quên mật khẩu</h2>

        <p>
            Nhập email tài khoản của bạn để nhận liên kết đặt lại mật khẩu.
        </p>

        <div id="message"></div>

        <form id="forgotPasswordForm">

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

            <button
                type="submit"
                class="btn-primary"
                id="submitButton"
            >
                Gửi liên kết đặt lại mật khẩu
            </button>

        </form>

        <p style="margin-top: 20px;">
            <a href="/login">
                Quay lại đăng nhập
            </a>
        </p>

    </div>

</div>

<script>
const form =
    document.getElementById('forgotPasswordForm');

const message =
    document.getElementById('message');

const submitButton =
    document.getElementById('submitButton');

form.addEventListener(
    'submit',
    async function (event) {

        event.preventDefault();

        const email =
            document.getElementById('email').value.trim();

        message.className = '';
        message.textContent =
            'Đang gửi liên kết đặt lại mật khẩu...';

        submitButton.disabled = true;

        try {

            const response = await fetch(
                '/api/auth/forgot-password',
                {
                    method: 'POST',

                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },

                    body: JSON.stringify({
                        email: email
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
                    'Không thể gửi liên kết đặt lại mật khẩu.';

                return;
            }

            message.className =
                'message success';

            message.textContent =
                result.message ||
                'Nếu email tồn tại, liên kết đặt lại mật khẩu đã được gửi.';

        } catch (error) {

            console.error(error);

            message.className =
                'message error';

            message.textContent =
                'Không thể kết nối đến máy chủ.';

        } finally {

            submitButton.disabled = false;
        }
    }
);
</script>

</body>
</html>