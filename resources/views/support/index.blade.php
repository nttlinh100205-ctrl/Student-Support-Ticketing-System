<!DOCTYPE html>
<html lang="vi">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Hỗ trợ sinh viên</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body class="bg-light">

<div class="container py-4">

    <div class="mb-4">

        <h2>
            Hệ thống hỗ trợ sinh viên
        </h2>

        <p class="text-muted">
            Module Trao đổi và Tài liệu
        </p>

    </div>


    <!-- THÔNG TIN YÊU CẦU -->

    <div class="card mb-4">

        <div class="card-body">

            <h5>
                Yêu cầu hỗ trợ #1
            </h5>

            <p>
                Không đăng ký được môn Công nghệ phần mềm
            </p>

        </div>

    </div>


    <div class="row">

        <!-- TRAO ĐỔI -->

        <div class="col-md-7">

            <div class="card">

                <div class="card-header">

                    <strong>
                        Trao đổi
                    </strong>

                </div>

                <div class="card-body">

                    <div id="exchangeList">
                        Đang tải dữ liệu...
                    </div>


                    <hr>


                    <h6>
                        Gửi phản hồi
                    </h6>

                    <form id="exchangeForm">

                        <div class="mb-3">

                            <textarea
                                id="message"
                                class="form-control"
                                rows="3"
                                placeholder="Nhập nội dung trao đổi..."
                            ></textarea>

                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Gửi trao đổi
                        </button>

                    </form>

                </div>

            </div>

        </div>


        <!-- TÀI LIỆU -->

        <div class="col-md-5">

            <div class="card">

                <div class="card-header">

                    <strong>
                        Tài liệu
                    </strong>

                </div>

                <div class="card-body">

                    <div id="documentList">
                        Đang tải dữ liệu...
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<script>

const API_URL = '/api';


// ========================================
// LẤY TRAO ĐỔI
// ========================================

async function loadExchanges()
{
    try {

        const response =
            await fetch(`${API_URL}/requests/1/exchanges`);

        const result =
            await response.json();

        const list =
            document.getElementById('exchangeList');

        list.innerHTML = '';

        if (result.data.length === 0) {

            list.innerHTML =
                '<p class="text-muted">Chưa có trao đổi.</p>';

            return;
        }


        result.data.forEach(exchange => {

            const role =
                exchange.sender_role === 'student'
                    ? 'Sinh viên'
                    : 'Nhân viên hỗ trợ';


            list.innerHTML += `

                <div class="border rounded p-3 mb-3">

                    <div class="d-flex justify-content-between">

                        <strong>
                            ${exchange.sender_name}
                        </strong>

                        <small class="text-muted">
                            ${role}
                        </small>

                    </div>

                    <p class="mb-1 mt-2">
                        ${exchange.message}
                    </p>

                    <small class="text-muted">
                        ${exchange.created_at}
                    </small>

                </div>

            `;

        });

    }
    catch (error) {

        console.error(error);

        document.getElementById('exchangeList').innerHTML =
            '<p class="text-danger">Không thể tải trao đổi.</p>';
    }
}


// ========================================
// LẤY TÀI LIỆU
// ========================================

async function loadDocuments()
{
    try {

        const response =
            await fetch(`${API_URL}/requests/1/documents`);

        const result =
            await response.json();

        const list =
            document.getElementById('documentList');

        list.innerHTML = '';


        result.data.forEach(documentItem => {

            list.innerHTML += `

                <div class="border rounded p-3 mb-3">

                    <h6>
                        ${documentItem.name}
                    </h6>

                    <p class="small text-muted">
                        ${documentItem.description}
                    </p>

                    <p class="small">
                        Loại:
                        ${documentItem.file_type}
                    </p>

                    <p class="small">
                        Kích thước:
                        ${documentItem.file_size}
                    </p>

                    <a
                        href="${documentItem.file_url}"
                        class="btn btn-sm btn-outline-primary"
                    >
                        Xem tài liệu
                    </a>

                </div>

            `;

        });

    }
    catch (error) {

        console.error(error);

        document.getElementById('documentList').innerHTML =
            '<p class="text-danger">Không thể tải tài liệu.</p>';
    }
}


// ========================================
// GỬI TRAO ĐỔI
// ========================================

document
    .getElementById('exchangeForm')
    .addEventListener('submit', async function(event)
    {
        event.preventDefault();


        const message =
            document.getElementById('message').value.trim();


        if (!message) {

            alert('Vui lòng nhập nội dung trao đổi.');

            return;
        }


        const response =
            await fetch(`${API_URL}/exchanges`, {

                method: 'POST',

                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },

                body: JSON.stringify({

                    request_id: 1,

                    sender_id: 101,

                    sender_name: 'Nguyễn Văn An',

                    sender_role: 'student',

                    message: message

                })

            });


        const result =
            await response.json();


        if (response.ok) {

            alert('Gửi trao đổi thành công!');

            document
                .getElementById('message')
                .value = '';

            loadExchanges();

        }
        else {

            alert(
                result.message ||
                'Có lỗi xảy ra.'
            );

        }

    });


// ========================================
// KHỞI ĐỘNG
// ========================================

loadExchanges();

loadDocuments();

</script>

</body>

</html>