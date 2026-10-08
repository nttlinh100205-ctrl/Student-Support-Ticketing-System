<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Đăng ký - Student Support</title>

    <link rel="stylesheet" href="/css/app.css">
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
                <label for="full_name">
                    Họ và tên
                </label>

                <input
                    type="text"
                    id="full_name"
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
                    placeholder="example@university.edu.vn"
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
                    minlength="8"
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
                    minlength="8"
                    required
                >
            </div>

            <button
                type="submit"
                class="btn-primary"
                id="registerButton"
            >
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
const form =
    document.getElementById('registerForm');

const message =
    document.getElementById('message');

const registerButton =
    document.getElementById('registerButton');


function showMessage(text, type = '') {
    message.className =
        type
            ? `message ${type}`
            : 'message';

    message.textContent = text;
}


function getFirstValidationError(errors) {
    if (!errors) {
        return null;
    }

    const keys =
        Object.keys(errors);

    if (keys.length === 0) {
        return null;
    }

    const firstError =
        errors[keys[0]];

    if (Array.isArray(firstError)) {
        return firstError[0];
    }

    return firstError;
}


form.addEventListener(
    'submit',
    async function (event) {
        event.preventDefault();

        const fullName =
            document
                .getElementById('full_name')
                .value
                .trim();

        const email =
            document
                .getElementById('email')
                .value
                .trim();

        const phone =
            document
                .getElementById('phone')
                .value
                .trim();

        const password =
            document
                .getElementById('password')
                .value;

        const passwordConfirmation =
            document
                .getElementById(
                    'password_confirmation'
                )
                .value;


        /*
        |--------------------------------------------------------------------------
        | Kiểm tra phía giao diện
        |--------------------------------------------------------------------------
        */
        if (
            password !==
            passwordConfirmation
        ) {
            showMessage(
                'Xác nhận mật khẩu không khớp.',
                'error'
            );

            return;
        }


        if (password.length < 8) {
            showMessage(
                'Mật khẩu phải có ít nhất 8 ký tự.',
                'error'
            );

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Chuẩn bị request
        |--------------------------------------------------------------------------
        */
        const body = {
            full_name: fullName,
            email: email,
            phone: phone || null,
            password: password,
            password_confirmation:
                passwordConfirmation
        };


        showMessage(
            'Đang tạo tài khoản...'
        );

        registerButton.disabled = true;

        registerButton.textContent =
            'Đang đăng ký...';


        try {
            const response =
                await fetch(
                    '/api/auth/register',
                    {
                        method: 'POST',

                        headers: {
                            'Content-Type':
                                'application/json',

                            'Accept':
                                'application/json'
                        },

                        body:
                            JSON.stringify(body)
                    }
                );


            const result =
                await response.json();


            /*
            |--------------------------------------------------------------------------
            | Đăng ký thất bại
            |--------------------------------------------------------------------------
            */
            if (!response.ok) {
                const validationError =
                    getFirstValidationError(
                        result.errors
                    );

                showMessage(
                    validationError ||
                    result.message ||
                    'Đăng ký thất bại.',
                    'error'
                );

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Đăng ký thành công
            |--------------------------------------------------------------------------
            | API register hiện tại không cấp JWT.
            | Người dùng đăng ký xong sẽ đăng nhập để nhận token.
            */
            showMessage(
                result.message ||
                'Đăng ký tài khoản thành công!',
                'success'
            );


            form.reset();


            setTimeout(function () {
                window.location.href =
                    '/login';
            }, 1200);


        } catch (error) {
            console.error(error);

            showMessage(
                'Không thể kết nối đến máy chủ.',
                'error'
            );

        } finally {
            registerButton.disabled = false;

            registerButton.textContent =
                'Đăng ký';
        }
    }
);
</script>

</body>
</html>