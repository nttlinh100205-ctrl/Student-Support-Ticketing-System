<!DOCTYPE html>
<html lang="vi">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Hồ sơ cá nhân - Student Support</title>

    <link rel="stylesheet"
          href="/css/app.css">

</head>

<body>

<div class="dashboard">

    <header class="topbar">

        <h1>
            Student Support
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
                Hồ sơ cá nhân
            </h2>

            <div id="profileMessage"></div>

            <form id="profileForm">

                <div class="form-group">

                    <label>
                        Họ và tên
                    </label>

                    <input
                        type="text"
                        id="name"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Email
                    </label>

                    <input
                        type="email"
                        id="email"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Số điện thoại
                    </label>

                    <input
                        type="text"
                        id="phone"
                    >

                </div>


                <div class="info-row">

                    <strong>
                        Vai trò:
                    </strong>

                    <span id="role">
                        -
                    </span>

                </div>


                <div class="info-row">

                    <strong>
                        Trạng thái:
                    </strong>

                    <span id="status">
                        -
                    </span>

                </div>


                <button
                    type="submit"
                    class="btn-primary">

                    Cập nhật hồ sơ

                </button>

            </form>

        </section>


        <section class="card">

            <h2>
                Đổi mật khẩu
            </h2>

            <div id="passwordMessage"></div>

            <form id="passwordForm">

                <div class="form-group">

                    <label>
                        Mật khẩu hiện tại
                    </label>

                    <input
                        type="password"
                        id="current_password"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Mật khẩu mới
                    </label>

                    <input
                        type="password"
                        id="new_password"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Xác nhận mật khẩu mới
                    </label>

                    <input
                        type="password"
                        id="new_password_confirmation"
                        required
                    >

                </div>


                <button
                    type="submit"
                    class="btn-primary">

                    Đổi mật khẩu

                </button>

            </form>

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
 * Tải thông tin hồ sơ
 */
async function loadProfile() {

    try {

        const response =
            await fetch(
                '/api/v1/profile',
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


        const data =
            await response.json();

        const user =
            data.user;


        document.getElementById('name')
            .value = user.name;


        document.getElementById('email')
            .value = user.email;


        document.getElementById('phone')
            .value = user.phone || '';


        document.getElementById('role')
            .textContent = user.role;


        document.getElementById('status')
            .textContent = user.status;


    } catch (error) {

        document.getElementById(
            'profileMessage'
        ).textContent =
            'Không thể tải hồ sơ.';

    }

}


/*
 * Cập nhật hồ sơ
 */
document
    .getElementById('profileForm')
    .addEventListener(
        'submit',
        async function(event) {

            event.preventDefault();

            const message =
                document.getElementById(
                    'profileMessage'
                );

            try {

                const response =
                    await fetch(
                        '/api/v1/profile',
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

                                name:
                                    document
                                        .getElementById(
                                            'name'
                                        )
                                        .value,

                                email:
                                    document
                                        .getElementById(
                                            'email'
                                        )
                                        .value,

                                phone:
                                    document
                                        .getElementById(
                                            'phone'
                                        )
                                        .value

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
                        'Cập nhật thất bại.';

                    return;

                }


                message.className =
                    'message success';

                message.textContent =
                    data.message;


            } catch (error) {

                message.className =
                    'message error';

                message.textContent =
                    'Không thể kết nối máy chủ.';

            }

        }
    );


/*
 * Đổi mật khẩu
 */
document
    .getElementById('passwordForm')
    .addEventListener(
        'submit',
        async function(event) {

            event.preventDefault();

            const message =
                document.getElementById(
                    'passwordMessage'
                );


            try {

                const response =
                    await fetch(
                        '/api/v1/profile/password',
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
                                    document
                                        .getElementById(
                                            'current_password'
                                        )
                                        .value,

                                password:
                                    document
                                        .getElementById(
                                            'new_password'
                                        )
                                        .value,

                                password_confirmation:
                                    document
                                        .getElementById(
                                            'new_password_confirmation'
                                        )
                                        .value

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
                        'Đổi mật khẩu thất bại.';

                    return;

                }


                message.className =
                    'message success';

                message.textContent =
                    data.message;


                document
                    .getElementById(
                        'passwordForm'
                    )
                    .reset();


            } catch (error) {

                message.className =
                    'message error';

                message.textContent =
                    'Không thể kết nối máy chủ.';

            }

        }
    );


/*
 * Đăng xuất
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


loadProfile();

</script>

</body>
</html>