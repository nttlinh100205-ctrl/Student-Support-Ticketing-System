<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Đăng nhập - Student Support</title>

    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/school.css">
<link rel="stylesheet" href="/css/suite.css">
</head>

<body class="suite-ui school-login">
@include('partials.school-brand')

<div class="auth-container">

    <section class="auth-story">
        <span class="brand-label">CỔNG HỖ TRỢ TRỰC TUYẾN</span>
        <h2>Mọi yêu cầu được lắng nghe.<br>Mỗi bước đều có người đồng hành.</h2>
        <p>Kết nối sinh viên với các phòng ban. Gửi yêu cầu, trao đổi và theo dõi quá trình hỗ trợ trong một không gian.</p>
        <div class="school-steps">
            <article class="school-step"><b>01</b><h3>Gửi yêu cầu</h3><p>Chọn phòng ban và nội dung cần được hỗ trợ.</p></article>
            <article class="school-step"><b>02</b><h3>Theo dõi xử lý</h3><p>Trao đổi, bổ sung thông tin và nhận phản hồi.</p></article>
            <article class="school-step"><b>03</b><h3>Nhận kết quả</h3><p>Xem kết quả và đánh giá chất lượng hỗ trợ.</p></article>
        </div>
        <div class="school-note">Đăng nhập bằng tài khoản của hệ thống hỗ trợ sinh viên. Mỗi vai trò có không gian làm việc và quyền truy cập riêng.</div>
    </section>

    <div class="auth-card">

        <h1>HỆ THỐNG XỬ LÝ YÊU CẦU SINH VIÊN</h1>

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
                    autocomplete="username"
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
                    autocomplete="current-password"
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

<footer class="school-login-footer"><span>Theo dõi tiến độ</span><span>Kết nối đúng phòng ban</span><span>Trao đổi trực tuyến</span></footer>
<script>
if (new URLSearchParams(location.search).has('logged_out')) { localStorage.removeItem('access_token'); localStorage.removeItem('current_user'); }

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

                const params = new URLSearchParams(location.search);
                window.location.href = params.has('service') ? '/sso/authorize?' + params.toString() : '/';

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
