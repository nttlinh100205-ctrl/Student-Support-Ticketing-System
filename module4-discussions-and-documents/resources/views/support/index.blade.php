<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Trang tin tức</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet">

    <style>

        body {
            margin: 0;
            background: #eef5ff;
            font-family: Arial, sans-serif;
            color: #26364d;
        }

        .topbar {
            background: #203d82;
            color: white;
            padding: 15px 30px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: white;
            color: #168a47;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }

        .topbar-title {
            font-size: 20px;
            font-weight: 700;
        }

        .container-news {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 15px 50px;
        }

        .panel {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 8px 25px rgba(40, 70, 120, .08);
        }

        .page-title {
            color: #173b86;
            font-size: 30px;
            font-weight: 800;
        }

        .role-box {
            background: #f5f8ff;
            border: 1px solid #dce5f5;
            border-radius: 15px;
            padding: 15px;
        }

        .category-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .category-btn {
            border: none;
            background: #eef2f8;
            color: #4d5c70;
            padding: 10px 18px;
            border-radius: 25px;
            font-weight: 600;
        }

        .category-btn.active {
            background: #2861e8;
            color: white;
        }

        .news-card {
            background: white;
            border-radius: 18px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 6px 20px rgba(40, 70, 120, .08);
            border-left: 5px solid #2861e8;
            transition: .2s;
        }

        .news-card:hover {
            transform: translateY(-2px);
        }

        .news-title {
            font-size: 19px;
            font-weight: 700;
            color: #202b3c;
        }

        .news-meta {
            font-size: 13px;
            color: #7d8a9d;
            margin-top: 8px;
        }

        .news-content {
            margin-top: 12px;
            color: #526176;
            line-height: 1.6;
            white-space: pre-line;
        }

        .badge-room {
            background: #e8f0ff;
            color: #2255c7;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
        }

        .file-box {
            margin-top: 15px;
            padding: 12px;
            background: #f6f8fc;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .file-box a {
            text-decoration: none;
            font-weight: 600;
        }

        .empty {
            background: white;
            border-radius: 18px;
            padding: 60px;
            text-align: center;
            color: #7c8798;
        }

        .btn-main {
            background: #2861e8;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 10px 18px;
            font-weight: 700;
        }

        .btn-main:hover {
            background: #1c4fc7;
            color: white;
        }

        .modal-content {
            border: none;
            border-radius: 18px;
        }

        .modal-header {
            background: #203d82;
            color: white;
        }

        .current-file {
            background: #f1f5fb;
            padding: 10px;
            border-radius: 10px;
            margin-top: 10px;
        }

        .department-notice {
            background: #eaf2ff;
            border: 1px solid #cbdcff;
            color: #2453b8;
            border-radius: 10px;
            padding: 10px 12px;
            margin-top: 12px;
            display: none;
        }

        .preview-modal .modal-dialog {
            max-width: 1100px;
        }

        .preview-body {
            min-height: 500px;
            background: #f5f7fb;
            padding: 20px;
            overflow: auto;
        }

        .preview-image {
            display: block;
            max-width: 100%;
            max-height: 70vh;
            margin: auto;
            border-radius: 8px;
        }

        .preview-pdf {
            width: 100%;
            height: 75vh;
            border: none;
            background: white;
        }

        .docx-preview {
            background: white;
            max-width: 900px;
            margin: auto;
            padding: 40px;
            min-height: 500px;
            line-height: 1.7;
        }

        .docx-preview img {
            max-width: 100%;
        }

        .excel-preview {
            background: white;
            overflow: auto;
            max-height: 70vh;
        }

        .excel-preview table {
            width: 100%;
            border-collapse: collapse;
        }

        .excel-preview td,
        .excel-preview th {
            border: 1px solid #d8dee9;
            padding: 8px 10px;
            white-space: nowrap;
        }

        .excel-preview th {
            background: #edf3ff;
        }

        .preview-loading {
            min-height: 450px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }

        .preview-error {
            min-height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            text-align: center;
        }

        @media(max-width: 768px) {

            .topbar {
                padding: 12px 15px;
            }

            .topbar-title {
                font-size: 16px;
            }

            .page-title {
                font-size: 24px;
            }

            .container-news {
                margin-top: 15px;
            }

            .panel {
                padding: 18px;
            }

            .empty {
                padding: 40px 20px;
            }

            .docx-preview {
                padding: 20px;
            }

        }

    </style>

</head>

<body>

<div class="topbar">

    <div class="logo">
        DH
    </div>

    <div class="topbar-title">
        Hệ thống tin tức
    </div>

</div>

<div class="container-news">

    <div class="panel">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

            <div>

                <div class="page-title">
                    <i class="bi bi-newspaper"></i>
                    Tin tức
                </div>

                <div class="text-muted">
                    Thông báo và tin tức từ nhà trường,
                    các phòng ban và sinh viên.
                </div>

            </div>

            <button
                class="btn-main"
                onclick="openCreateModal()">

                <i class="bi bi-plus-circle"></i>
                Đăng tin

            </button>

        </div>

        <hr>

        <div class="role-box mb-4">

            <label class="form-label fw-bold">
                Vai trò đang sử dụng
            </label>

            <select
                id="currentRole"
                class="form-select"
                onchange="changeRole()">

                <option value="admin">Admin</option>
                <option value="department">Phòng ban</option>
                <option value="student">Sinh viên</option>

            </select>

            <div
                id="departmentBox"
                class="mt-3"
                style="display:none;">

                <label class="form-label fw-bold">
                    Phòng ban
                </label>

                <select
                    id="currentDepartment"
                    class="form-select"
                    onchange="changeDepartment()">

                    <option value="dao_tao">Phòng Đào tạo</option>
                    <option value="y_te">Phòng Y tế</option>
                    <option value="vat_chat">Phòng Vật chất</option>
                    <option value="ke_toan">Phòng Kế toán</option>

                </select>

            </div>

            <div
                id="roleDescription"
                class="small text-muted mt-2">
            </div>

            <div
                id="departmentNotice"
                class="department-notice">
            </div>

        </div>

        <div class="row g-3">

            <div class="col-md-8">

                <input
                    type="text"
                    id="keyword"
                    class="form-control form-control-lg"
                    placeholder="Tìm kiếm tin tức..."
                    onkeydown="searchEnter(event)">

            </div>

            <div class="col-md-4">

                <button
                    class="btn-main w-100 h-100"
                    onclick="loadNews()">

                    <i class="bi bi-search"></i>
                    Tìm kiếm

                </button>

            </div>

        </div>

        <div
            id="categoryButtons"
            class="category-buttons mt-4">

            <button
                class="category-btn active"
                data-category="all"
                onclick="selectCategory(this)">
                Tất cả
            </button>

            <button
                class="category-btn"
                data-category="dao_tao"
                onclick="selectCategory(this)">
                Đào tạo
            </button>

            <button
                class="category-btn"
                data-category="y_te"
                onclick="selectCategory(this)">
                Y tế
            </button>

            <button
                class="category-btn"
                data-category="vat_chat"
                onclick="selectCategory(this)">
                Vật chất
            </button>

            <button
                class="category-btn"
                data-category="ke_toan"
                onclick="selectCategory(this)">
                Kế toán
            </button>

            <button
                class="category-btn"
                data-category="hoat_dong_sinh_vien"
                onclick="selectCategory(this)">
                Hoạt động sinh viên
            </button>

            <button
                class="category-btn"
                data-category="nha_truong"
                onclick="selectCategory(this)">
                Tin nhà trường
            </button>

        </div>

    </div>

    <div id="newsContainer">

        <div class="empty">

            <div class="spinner-border text-primary"></div>

            <div class="mt-3">
                Đang tải tin tức...
            </div>

        </div>

    </div>

</div>

<!-- MODAL THÊM / SỬA -->

<div
    class="modal fade"
    id="newsModal"
    tabindex="-1">

    <div class="modal-dialog modal-lg">

        <div class="modal-content">

            <div class="modal-header">

                <h5
                    class="modal-title"
                    id="modalTitle">
                    Đăng tin mới
                </h5>

                <button
                    type="button"
                    class="btn-close btn-close-white"
                    data-bs-dismiss="modal">
                </button>

            </div>

            <div class="modal-body">

                <form
                    id="newsForm"
                    enctype="multipart/form-data">

                    <input
                        type="hidden"
                        id="newsId">

                    <div class="mb-3">

                        <label class="form-label fw-bold">
                            Tiêu đề
                        </label>

                        <input
                            type="text"
                            id="newsTitle"
                            class="form-control"
                            required
                            maxlength="255"
                            placeholder="Nhập tiêu đề tin tức">

                    </div>

                    <div class="mb-3">

                        <label class="form-label fw-bold">
                            Chuyên mục
                        </label>

                        <select
                            id="newsCategory"
                            class="form-select"
                            required>

                            <option value="dao_tao">Phòng Đào tạo</option>
                            <option value="y_te">Phòng Y tế</option>
                            <option value="vat_chat">Phòng Vật chất</option>
                            <option value="ke_toan">Phòng Kế toán</option>
                            <option value="hoat_dong_sinh_vien">
                                Hoạt động sinh viên
                            </option>
                            <option value="nha_truong">
                                Tin nhà trường
                            </option>

                        </select>

                        <div
                            id="categoryLockedNotice"
                            class="form-text text-primary"
                            style="display:none;">

                            <i class="bi bi-lock-fill"></i>

                            Phòng ban chỉ được đăng tin thuộc phòng mình.

                        </div>

                    </div>

                    <div class="mb-3">

                        <label class="form-label fw-bold">
                            Nội dung
                        </label>

                        <textarea
                            id="newsContent"
                            class="form-control"
                            rows="7"
                            required
                            placeholder="Nhập nội dung tin tức...">
                        </textarea>

                    </div>

                    <div class="mb-3">

                        <label class="form-label fw-bold">
                            File đính kèm
                        </label>

                        <input
                            type="file"
                            id="newsFile"
                            class="form-control"
                            accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx">

                        <div class="form-text">

                            Có thể tải hình ảnh hoặc tài liệu:

                            <strong>
                                JPG, JPEG, PNG, WEBP, PDF,
                                DOC, DOCX, XLS, XLSX
                            </strong>.

                            Không bắt buộc phải có hình ảnh.

                            Dung lượng tối đa <strong>10MB</strong>.

                        </div>

                        <div
                            id="currentFile"
                            class="current-file"
                            style="display:none;">
                        </div>

                    </div>

                    <div class="form-check mb-3">

                        <input
                            type="checkbox"
                            id="newsPinned"
                            class="form-check-input">

                        <label
                            class="form-check-label"
                            for="newsPinned">
                            Ghim tin lên đầu
                        </label>

                    </div>

                    <div class="text-end">

                        <button
                            type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">
                            Hủy
                        </button>

                        <button
                            type="submit"
                            class="btn-main">

                            <i class="bi bi-save"></i>
                            Lưu tin

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

</div>

<!-- MODAL XEM FILE -->

<div
    class="modal fade preview-modal"
    id="filePreviewModal"
    tabindex="-1">

    <div class="modal-dialog modal-xl modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">

                <h5
                    class="modal-title"
                    id="filePreviewTitle">
                    Xem file
                </h5>

                <button
                    type="button"
                    class="btn-close btn-close-white"
                    data-bs-dismiss="modal">
                </button>

            </div>

            <div
                class="modal-body preview-body"
                id="filePreviewBody">
            </div>

        </div>

    </div>

</div>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

<script
    src="https://cdn.jsdelivr.net/npm/mammoth@1.8.0/mammoth.browser.min.js">
</script>

<script
    src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js">
</script>

<script>

const USERS = {

    admin: {
        id: 999,
        name: 'Quản trị viên',
        role: 'admin'
    },

    departments: {

        dao_tao: {
            id: 201,
            name: 'Nguyễn Văn Minh',
            role: 'department',
            department: 'dao_tao'
        },

        y_te: {
            id: 202,
            name: 'Lê Thị Hoa',
            role: 'department',
            department: 'y_te'
        },

        vat_chat: {
            id: 203,
            name: 'Phạm Văn Nam',
            role: 'department',
            department: 'vat_chat'
        },

        ke_toan: {
            id: 204,
            name: 'Đỗ Thị Lan',
            role: 'department',
            department: 'ke_toan'
        }

    },

    student: {
        id: 1,
        name: 'Nguyễn Văn An',
        role: 'student'
    }

};

const ALLOWED_FILE_EXTENSIONS = [
    'jpg',
    'jpeg',
    'png',
    'webp',
    'pdf',
    'doc',
    'docx',
    'xls',
    'xlsx'
];

const MAX_FILE_SIZE = 10 * 1024 * 1024;

let selectedCategory = 'all';

let editingId = null;

let newsCache = [];

function getCurrentUser()
{
    const role =
        document.getElementById('currentRole').value;

    if (role === 'admin') {
        return USERS.admin;
    }

    if (role === 'student') {
        return USERS.student;
    }

    const department =
        document.getElementById('currentDepartment').value;

    return USERS.departments[department];
}

function changeRole()
{
    const role =
        document.getElementById('currentRole').value;

    const departmentBox =
        document.getElementById('departmentBox');

    if (role === 'department') {

        departmentBox.style.display = 'block';

        selectedCategory =
            document.getElementById('currentDepartment').value;

    } else {

        departmentBox.style.display = 'none';

        selectedCategory = 'all';
    }

    updateCategoryButtons();

    updateRoleDescription();

    loadNews();
}

function changeDepartment()
{
    const user = getCurrentUser();

    if (user && user.department) {
        selectedCategory = user.department;
    }

    updateCategoryButtons();

    updateRoleDescription();

    loadNews();
}

function updateRoleDescription()
{
    const role =
        document.getElementById('currentRole').value;

    const description =
        document.getElementById('roleDescription');

    const notice =
        document.getElementById('departmentNotice');

    if (role === 'admin') {

        description.innerHTML = `
            <i class="bi bi-shield-check"></i>
            Admin có quyền thêm, sửa, xóa và tìm kiếm toàn bộ tin tức.
        `;

        notice.style.display = 'none';

        return;
    }

    if (role === 'department') {

        const user = getCurrentUser();

        description.innerHTML = `
            <i class="bi bi-building"></i>
            ${escapeHtml(user.name)}
            đang quản lý tin của
            <strong>
                ${escapeHtml(getCategoryName(user.department))}
            </strong>.
            Có thể thêm, sửa, xóa và tìm kiếm tin của phòng mình.
        `;

        notice.style.display = 'block';

        notice.innerHTML = `
            <i class="bi bi-funnel-fill"></i>
            Đang hiển thị riêng tin của
            <strong>
                ${escapeHtml(getCategoryName(user.department))}
            </strong>.
            Các phòng ban khác và tin sinh viên sẽ không hiển thị.
        `;

        return;
    }

    notice.style.display = 'none';

    description.innerHTML = `
        <i class="bi bi-person"></i>
        Sinh viên có thể xem toàn bộ tin tức.
        Sinh viên chỉ có quyền sửa/xóa tin do chính mình đăng.
    `;
}

function updateCategoryButtons()
{
    const role =
        document.getElementById('currentRole').value;

    const buttons =
        document.querySelectorAll('.category-btn');

    const department =
        document.getElementById('currentDepartment').value;

    buttons.forEach(function(button) {

        const category = button.dataset.category;

        button.classList.remove('active');

        if (role === 'department') {

            if (category === department) {

                button.style.display = 'inline-block';
                button.classList.add('active');

            } else {

                button.style.display = 'none';

            }

            return;
        }

        button.style.display = 'inline-block';

        if (category === selectedCategory) {
            button.classList.add('active');
        }

    });

    if (role === 'department') {
        selectedCategory = department;
    }
}

function selectCategory(button)
{
    const role =
        document.getElementById('currentRole').value;

    if (role === 'department') {

        const user = getCurrentUser();

        if (button.dataset.category !== user.department) {
            return;
        }
    }

    document
        .querySelectorAll('.category-btn')
        .forEach(function(btn) {
            btn.classList.remove('active');
        });

    button.classList.add('active');

    selectedCategory = button.dataset.category;

    loadNews();
}

async function parseJsonResponse(response)
{
    const contentType =
        response.headers.get('content-type') || '';

    if (!contentType.includes('application/json')) {

        const text = await response.text();

        throw new Error(
            'Server trả về dữ liệu không phải JSON. HTTP ' +
            response.status +
            '. ' +
            text.substring(0, 300)
        );
    }

    const result = await response.json();

    if (!response.ok || result.success === false) {

        let message =
            result.message ||
            'Không thể xử lý yêu cầu.';

        if (result.errors) {

            const errors =
                Object.values(result.errors).flat();

            if (errors.length) {
                message = errors.join('\n');
            }
        }

        throw new Error(message);
    }

    return result;
}

async function loadNews()
{
    const container =
        document.getElementById('newsContainer');

    container.innerHTML = `
        <div class="empty">
            <div class="spinner-border text-primary"></div>
            <div class="mt-3">
                Đang tải tin tức...
            </div>
        </div>
    `;

    const role =
        document.getElementById('currentRole').value;

    const user = getCurrentUser();

    if (role === 'department') {

        selectedCategory = user.department;

        updateCategoryButtons();
    }

    const keyword =
        document.getElementById('keyword').value.trim();

    const params =
        new URLSearchParams();

    if (selectedCategory !== 'all') {

        params.append(
            'category',
            selectedCategory
        );
    }

    if (keyword) {

        params.append(
            'keyword',
            keyword
        );
    }

    let url;

    if (role === 'admin') {

        url = '/api/admin/news';

    } else {

        url = '/api/news';
    }

    try {

        const response =
            await fetch(
                url +
                (
                    params.toString()
                        ? '?' + params.toString()
                        : ''
                )
            );

        const result =
            await parseJsonResponse(response);

        newsCache =
            Array.isArray(result.data)
                ? result.data
                : [];

        /*
         * PHÒNG BAN:
         * Chỉ hiển thị:
         * - owner_role = department
         * - category = đúng phòng
         *
         * Không khóa owner_id ở đây để toàn bộ tin
         * thuộc phòng đó đều hiển thị.
         */

        if (role === 'department') {

            newsCache =
                newsCache.filter(function(item) {

                    const sameRole =
                        String(
                            item.owner_role || ''
                        ).toLowerCase() === 'department';

                    const sameCategory =
                        String(
                            item.category || ''
                        ).toLowerCase() ===
                        String(
                            user.department
                        ).toLowerCase();

                    return sameRole && sameCategory;
                });
        }

        if (
            role === 'admin' &&
            selectedCategory !== 'all'
        ) {

            newsCache =
                newsCache.filter(function(item) {

                    return String(
                        item.category || ''
                    ).toLowerCase() ===
                    String(
                        selectedCategory
                    ).toLowerCase();

                });
        }

        if (
            role === 'student' &&
            selectedCategory !== 'all'
        ) {

            newsCache =
                newsCache.filter(function(item) {

                    return String(
                        item.category || ''
                    ).toLowerCase() ===
                    String(
                        selectedCategory
                    ).toLowerCase();

                });
        }

        renderNews(newsCache);

    } catch (error) {

        container.innerHTML = `
            <div class="empty">

                <i
                    class="bi bi-exclamation-triangle"
                    style="font-size:45px;">
                </i>

                <h5 class="mt-3">
                    Không tải được tin tức
                </h5>

                <p>
                    ${escapeHtml(error.message)}
                </p>

            </div>
        `;
    }
}

function renderNews(list)
{
    const container =
        document.getElementById('newsContainer');

    if (!list.length) {

        container.innerHTML = `
            <div class="empty">

                <i
                    class="bi bi-inbox"
                    style="font-size:45px;">
                </i>

                <h5 class="mt-3">
                    Không có tin tức
                </h5>

                <p>
                    Chưa có dữ liệu phù hợp.
                </p>

            </div>
        `;

        return;
    }

    const user = getCurrentUser();

    const role = user.role;

    let html = '';

    list.forEach(function(news) {

        const canEdit =
            role === 'admin' ||
            Number(news.owner_id) === Number(user.id);

        const canDelete = canEdit;

        let fileHtml = '';

        if (
            news.file_path ||
            news.file_url ||
            news.download_url
        ) {

            const fileUrl =
                news.file_url ||
                (
                    '/api/news/' +
                    Number(news.id) +
                    '/file'
                );

            const downloadUrl =
                news.download_url ||
                (
                    '/api/news/' +
                    Number(news.id) +
                    '/download'
                );

            const fileIcon =
                getFileIcon(
                    news.file_name,
                    news.file_type
                );

            fileHtml = `
                <div class="file-box">

                    <i
                        class="${fileIcon.icon}"
                        style="font-size:22px;">
                    </i>

                    <strong>
                        ${escapeHtml(
                            news.file_name ||
                            'File đính kèm'
                        )}
                    </strong>

                    <button
                        type="button"
                        class="btn btn-sm btn-outline-primary ms-2"
                        onclick="openFilePreview(
                            '${escapeJs(fileUrl)}',
                            '${escapeJs(news.file_name || 'File đính kèm')}',
                            '${escapeJs(news.file_type || '')}'
                        )">

                        <i class="bi bi-eye"></i>
                        Xem

                    </button>

                    <a
                        href="${escapeHtml(downloadUrl)}"
                        class="btn btn-sm btn-outline-success">

                        <i class="bi bi-download"></i>
                        Tải xuống

                    </a>

                </div>
            `;
        }

        html += `
            <div class="news-card">

                <div class="d-flex justify-content-between gap-3">

                    <div style="flex:1;">

                        <div class="news-title">

                            ${
                                news.is_pinned
                                ?
                                `
                                <i class="bi bi-pin-fill text-danger"></i>
                                `
                                :
                                ''
                            }

                            ${escapeHtml(news.title)}

                        </div>

                        <div class="news-meta">

                            <span class="badge-room">
                                ${escapeHtml(
                                    news.category_name ||
                                    getCategoryName(news.category)
                                )}
                            </span>

                            <span class="ms-2">
                                <i class="bi bi-person"></i>
                                ${escapeHtml(
                                    news.owner_name || ''
                                )}
                            </span>

                            <span class="ms-2">
                                <i class="bi bi-calendar"></i>
                                ${formatDate(
                                    news.published_at ||
                                    news.created_at
                                )}
                            </span>

                        </div>

                        <div class="news-content">
                            ${escapeHtml(news.content)}
                        </div>

                        ${fileHtml}

                    </div>

                    <div class="text-nowrap">

                        ${
                            canEdit
                            ?
                            `
                            <button
                                class="btn btn-sm btn-outline-primary"
                                onclick="editNews(${Number(news.id)})"
                                title="Sửa">

                                <i class="bi bi-pencil"></i>

                            </button>
                            `
                            :
                            ''
                        }

                        ${
                            canDelete
                            ?
                            `
                            <button
                                class="btn btn-sm btn-outline-danger"
                                onclick="deleteNews(${Number(news.id)})"
                                title="Xóa">

                                <i class="bi bi-trash"></i>

                            </button>
                            `
                            :
                            ''
                        }

                    </div>

                </div>

            </div>
        `;
    });

    container.innerHTML = html;
}

function getFileIcon(fileName, fileType)
{
    const name =
        String(fileName || '').toLowerCase();

    const type =
        String(fileType || '').toLowerCase();

    if (
        type === 'application/pdf' ||
        name.endsWith('.pdf')
    ) {

        return {
            icon: 'bi bi-file-earmark-pdf-fill text-danger'
        };
    }

    if (
        name.endsWith('.doc') ||
        name.endsWith('.docx') ||
        type.includes('word')
    ) {

        return {
            icon: 'bi bi-file-earmark-word-fill text-primary'
        };
    }

    if (
        name.endsWith('.xls') ||
        name.endsWith('.xlsx') ||
        type.includes('excel') ||
        type.includes('spreadsheet')
    ) {

        return {
            icon: 'bi bi-file-earmark-excel-fill text-success'
        };
    }

    if (
        name.endsWith('.jpg') ||
        name.endsWith('.jpeg') ||
        name.endsWith('.png') ||
        name.endsWith('.webp') ||
        type.startsWith('image/')
    ) {

        return {
            icon: 'bi bi-file-earmark-image-fill text-info'
        };
    }

    return {
        icon: 'bi bi-file-earmark-fill text-secondary'
    };
}

function openCreateModal()
{
    editingId = null;

    document
        .getElementById('modalTitle')
        .textContent = 'Đăng tin mới';

    document
        .getElementById('newsForm')
        .reset();

    document
        .getElementById('newsId')
        .value = '';

    document
        .getElementById('newsFile')
        .value = '';

    document
        .getElementById('currentFile')
        .style.display = 'none';

    document
        .getElementById('currentFile')
        .innerHTML = '';

    const user = getCurrentUser();

    const category =
        document.getElementById('newsCategory');

    const lockedNotice =
        document.getElementById('categoryLockedNotice');

    if (user.role === 'admin') {

        category.disabled = false;

        lockedNotice.style.display = 'none';

    } else if (user.role === 'department') {

        category.value = user.department;

        category.disabled = true;

        lockedNotice.style.display = 'block';

    } else {

        category.value = 'hoat_dong_sinh_vien';

        category.disabled = false;

        lockedNotice.style.display = 'none';
    }

    const modal =
        bootstrap.Modal.getOrCreateInstance(
            document.getElementById('newsModal')
        );

    modal.show();
}

function editNews(id)
{
    const news =
        newsCache.find(function(item) {

            return Number(item.id) === Number(id);

        });

    if (!news) {

        alert('Không tìm thấy tin.');

        return;
    }

    const user = getCurrentUser();

    if (user.role === 'department') {

        const validCategory =
            String(news.category || '') ===
            String(user.department);

        if (!validCategory) {

            alert(
                'Bạn chỉ được sửa tin của phòng mình.'
            );

            return;
        }
    }

    const canEdit =
        user.role === 'admin' ||
        Number(news.owner_id) === Number(user.id);

    if (!canEdit) {

        alert(
            'Bạn không có quyền sửa tin này.'
        );

        return;
    }

    editingId = Number(id);

    document
        .getElementById('modalTitle')
        .textContent = 'Chỉnh sửa tin tức';

    document
        .getElementById('newsId')
        .value = news.id;

    document
        .getElementById('newsTitle')
        .value = news.title || '';

    document
        .getElementById('newsContent')
        .value = news.content || '';

    document
        .getElementById('newsCategory')
        .value = news.category || '';

    document
        .getElementById('newsPinned')
        .checked =
        Boolean(Number(news.is_pinned));

    const category =
        document.getElementById('newsCategory');

    const lockedNotice =
        document.getElementById(
            'categoryLockedNotice'
        );

    if (user.role === 'department') {

        category.value = user.department;

        category.disabled = true;

        lockedNotice.style.display = 'block';

    } else {

        category.disabled = false;

        lockedNotice.style.display = 'none';
    }

    const currentFile =
        document.getElementById('currentFile');

    if (
        news.file_path ||
        news.file_url ||
        news.download_url
    ) {

        currentFile.style.display = 'block';

        const fileIcon =
            getFileIcon(
                news.file_name,
                news.file_type
            );

        const fileUrl =
            news.file_url ||
            (
                '/api/news/' +
                Number(news.id) +
                '/file'
            );

        const downloadUrl =
            news.download_url ||
            (
                '/api/news/' +
                Number(news.id) +
                '/download'
            );

        currentFile.innerHTML = `
            <i
                class="${fileIcon.icon}"
                style="font-size:20px;">
            </i>

            File hiện tại:

            <strong>
                ${escapeHtml(news.file_name || '')}
            </strong>

            <div class="mt-2">

                <button
                    type="button"
                    class="btn btn-sm btn-outline-primary"
                    onclick="openFilePreview(
                        '${escapeJs(fileUrl)}',
                        '${escapeJs(news.file_name || 'File đính kèm')}',
                        '${escapeJs(news.file_type || '')}'
                    )">

                    <i class="bi bi-eye"></i>
                    Xem

                </button>

                <a
                    href="${escapeHtml(downloadUrl)}"
                    class="btn btn-sm btn-outline-success">

                    <i class="bi bi-download"></i>
                    Tải xuống

                </a>

            </div>
        `;

    } else {

        currentFile.style.display = 'none';

        currentFile.innerHTML = '';
    }

    document
        .getElementById('newsFile')
        .value = '';

    const modal =
        bootstrap.Modal.getOrCreateInstance(
            document.getElementById('newsModal')
        );

    modal.show();
}

function validateFile(file)
{
    if (!file) {
        return {
            valid: true
        };
    }

    if (file.size > MAX_FILE_SIZE) {

        return {
            valid: false,
            message: 'File không được vượt quá 10MB.'
        };
    }

    const fileName =
        String(file.name || '').toLowerCase();

    const parts =
        fileName.split('.');

    if (parts.length < 2) {

        return {
            valid: false,
            message: 'File phải có phần mở rộng hợp lệ.'
        };
    }

    const extension =
        parts.pop().toLowerCase();

    if (
        !ALLOWED_FILE_EXTENSIONS.includes(extension)
    ) {

        return {
            valid: false,
            message:
                'File không được hỗ trợ. Chỉ cho phép JPG, JPEG, PNG, WEBP, PDF, DOC, DOCX, XLS và XLSX.'
        };
    }

    return {
        valid: true
    };
}

document
    .getElementById('newsForm')
    .addEventListener(
        'submit',
        async function(event)
        {
            event.preventDefault();

            const user = getCurrentUser();

            const title =
                document
                    .getElementById('newsTitle')
                    .value
                    .trim();

            const content =
                document
                    .getElementById('newsContent')
                    .value
                    .trim();

            let category =
                document
                    .getElementById('newsCategory')
                    .value;

            if (user.role === 'department') {
                category = user.department;
            }

            const file =
                document
                    .getElementById('newsFile')
                    .files[0];

            const fileValidation =
                validateFile(file);

            if (!fileValidation.valid) {

                alert(
                    fileValidation.message
                );

                return;
            }

            const formData =
                new FormData();

            formData.append(
                'title',
                title
            );

            formData.append(
                'content',
                content
            );

            formData.append(
                'category',
                category
            );

            formData.append(
                'owner_id',
                user.id
            );

            formData.append(
                'owner_name',
                user.name
            );

            formData.append(
                'owner_role',
                user.role
            );

            formData.append(
                'status',
                'published'
            );

            formData.append(
                'is_pinned',
                document
                    .getElementById('newsPinned')
                    .checked
                    ? '1'
                    : '0'
            );

            if (file) {

                formData.append(
                    'file',
                    file
                );
            }

            let url = '/api/news';

            if (editingId) {

                url =
                    '/api/news/' +
                    editingId;

                formData.append(
                    '_method',
                    'PUT'
                );
            }

            try {

                const response =
                    await fetch(
                        url,
                        {
                            method: 'POST',
                            body: formData
                        }
                    );

                const result =
                    await parseJsonResponse(response);

                alert(
                    result.message ||
                    'Lưu tin thành công.'
                );

                bootstrap.Modal
                    .getInstance(
                        document.getElementById(
                            'newsModal'
                        )
                    )
                    .hide();

                document
                    .getElementById('newsForm')
                    .reset();

                document
                    .getElementById('newsFile')
                    .value = '';

                document
                    .getElementById('currentFile')
                    .style.display = 'none';

                document
                    .getElementById('currentFile')
                    .innerHTML = '';

                editingId = null;

                loadNews();

            } catch (error) {

                alert(error.message);
            }
        }
    );

document
    .getElementById('newsFile')
    .addEventListener(
        'change',
        function()
        {
            const file = this.files[0];

            const validation =
                validateFile(file);

            if (!validation.valid) {

                alert(validation.message);

                this.value = '';
            }
        }
    );

async function deleteNews(id)
{
    const user = getCurrentUser();

    const news =
        newsCache.find(function(item) {

            return Number(item.id) === Number(id);

        });

    if (!news) {
        return;
    }

    if (user.role === 'department') {

        if (
            String(news.category || '') !==
            String(user.department)
        ) {

            alert(
                'Bạn chỉ được xóa tin của phòng mình.'
            );

            return;
        }
    }

    const canDelete =
        user.role === 'admin' ||
        Number(news.owner_id) === Number(user.id);

    if (!canDelete) {

        alert(
            'Bạn không có quyền xóa tin này.'
        );

        return;
    }

    if (
        !confirm(
            'Bạn có chắc chắn muốn xóa tin này?'
        )
    ) {
        return;
    }

    try {

        const params =
            new URLSearchParams();

        params.append(
            'owner_id',
            user.id
        );

        params.append(
            'owner_role',
            user.role
        );

        if (user.role === 'department') {

            params.append(
                'department',
                user.department
            );
        }

        const response =
            await fetch(
                '/api/news/' + id,
                {
                    method: 'DELETE',
                    headers: {
                        'Content-Type':
                            'application/x-www-form-urlencoded'
                    },
                    body:
                        params.toString()
                }
            );

        const result =
            await parseJsonResponse(response);

        alert(
            result.message ||
            'Xóa thành công.'
        );

        loadNews();

    } catch (error) {

        alert(error.message);
    }
}

function searchEnter(event)
{
    if (event.key === 'Enter') {
        loadNews();
    }
}

function getCategoryName(category)
{
    const names = {

        dao_tao:
            'Phòng Đào tạo',

        y_te:
            'Phòng Y tế',

        vat_chat:
            'Phòng Vật chất',

        ke_toan:
            'Phòng Kế toán',

        hoat_dong_sinh_vien:
            'Hoạt động sinh viên',

        nha_truong:
            'Tin nhà trường'
    };

    return names[category] || category;
}

function escapeHtml(value)
{
    const div =
        document.createElement('div');

    div.textContent =
        value ?? '';

    return div.innerHTML;
}

function escapeJs(value)
{
    return String(value ?? '')
        .replace(/\\/g, '\\\\')
        .replace(/'/g, "\\'")
        .replace(/"/g, '&quot;')
        .replace(/\r/g, '\\r')
        .replace(/\n/g, '\\n');
}

function formatDate(value)
{
    if (!value) {
        return '';
    }

    const date =
        new Date(
            String(value)
                .replace(' ', 'T')
        );

    if (
        Number.isNaN(
            date.getTime()
        )
    ) {
        return value;
    }

    return date.toLocaleDateString(
        'vi-VN',
        {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        }
    );
}

/*
|--------------------------------------------------------------------------
| XEM FILE TRỰC TIẾP
|--------------------------------------------------------------------------
*/

async function openFilePreview(
    fileUrl,
    fileName,
    fileType
)
{
    const modalElement =
        document.getElementById(
            'filePreviewModal'
        );

    const title =
        document.getElementById(
            'filePreviewTitle'
        );

    const body =
        document.getElementById(
            'filePreviewBody'
        );

    title.textContent =
        fileName || 'Xem file';

    body.innerHTML = `
        <div class="preview-loading">

            <div
                class="spinner-border text-primary">
            </div>

            <div class="mt-3">
                Đang mở file...
            </div>

        </div>
    `;

    const modal =
        bootstrap.Modal.getOrCreateInstance(
            modalElement
        );

    modal.show();

    const lowerName =
        String(fileName || '').toLowerCase();

    const lowerType =
        String(fileType || '').toLowerCase();

    try {

        /*
        |--------------------------------------------------------------------------
        | ẢNH
        |--------------------------------------------------------------------------
        */

        if (
            lowerType.startsWith('image/') ||
            lowerName.endsWith('.jpg') ||
            lowerName.endsWith('.jpeg') ||
            lowerName.endsWith('.png') ||
            lowerName.endsWith('.webp')
        ) {

            body.innerHTML = `
                <img
                    src="${escapeHtml(fileUrl)}"
                    class="preview-image"
                    alt="${escapeHtml(fileName)}"
                >
            `;

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | PDF
        |--------------------------------------------------------------------------
        */

        if (
            lowerType === 'application/pdf' ||
            lowerName.endsWith('.pdf')
        ) {

            body.innerHTML = `
                <iframe
                    src="${escapeHtml(fileUrl)}"
                    class="preview-pdf"
                    title="${escapeHtml(fileName)}">
                </iframe>
            `;

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | DOCX
        |--------------------------------------------------------------------------
        */

        if (
            lowerName.endsWith('.docx') ||
            lowerType.includes(
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            )
        ) {

            if (
                typeof mammoth === 'undefined'
            ) {

                throw new Error(
                    'Không tải được thư viện đọc Word DOCX.'
                );
            }

            const response =
                await fetch(fileUrl);

            if (!response.ok) {

                throw new Error(
                    'Không thể tải file Word.'
                );
            }

            const arrayBuffer =
                await response.arrayBuffer();

            const result =
                await mammoth.convertToHtml({
                    arrayBuffer:
                        arrayBuffer
                });

            body.innerHTML = `
                <div class="docx-preview">

                    ${result.value}

                    ${
                        result.messages &&
                        result.messages.length
                        ?
                        `
                        <hr>

                        <div class="small text-muted">

                            Một số thành phần định dạng
                            trong file Word có thể không
                            hiển thị giống hoàn toàn Word.

                        </div>
                        `
                        :
                        ''
                    }

                </div>
            `;

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | XLS / XLSX
        |--------------------------------------------------------------------------
        */

        if (
            lowerName.endsWith('.xls') ||
            lowerName.endsWith('.xlsx') ||
            lowerType.includes('spreadsheet') ||
            lowerType.includes('excel')
        ) {

            if (
                typeof XLSX === 'undefined'
            ) {

                throw new Error(
                    'Không tải được thư viện đọc Excel.'
                );
            }

            const response =
                await fetch(fileUrl);

            if (!response.ok) {

                throw new Error(
                    'Không thể tải file Excel.'
                );
            }

            const arrayBuffer =
                await response.arrayBuffer();

            const workbook =
                XLSX.read(
                    arrayBuffer,
                    {
                        type: 'array'
                    }
                );

            const sheetName =
                workbook.SheetNames[0];

            const worksheet =
                workbook.Sheets[sheetName];

            const table =
                XLSX.utils.sheet_to_html(
                    worksheet,
                    {
                        editable: false
                    }
                );

            body.innerHTML = `
                <div class="excel-preview">

                    <div class="p-3 fw-bold">
                        Sheet: ${escapeHtml(sheetName)}
                    </div>

                    ${table}

                </div>
            `;

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | DOC cũ
        |--------------------------------------------------------------------------
        */

        if (
            lowerName.endsWith('.doc')
        ) {

            body.innerHTML = `
                <div class="preview-error">

                    <i
                        class="bi bi-file-earmark-word"
                        style="font-size:70px;color:#2861e8;">
                    </i>

                    <h4 class="mt-3">
                        File Word .doc
                    </h4>

                    <p>
                        Trình duyệt không hỗ trợ xem trực tiếp
                        định dạng Word .doc cũ.
                    </p>

                    <a
                        href="${escapeHtml(
                            '/api/news/' +
                            extractNewsIdFromFileUrl(fileUrl) +
                            '/download'
                        )}"
                        class="btn btn-primary">

                        <i class="bi bi-download"></i>
                        Tải xuống để mở bằng Word

                    </a>

                </div>
            `;

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | FILE KHÁC
        |--------------------------------------------------------------------------
        */

        body.innerHTML = `
            <div class="preview-error">

                <i
                    class="bi bi-file-earmark"
                    style="font-size:70px;">
                </i>

                <h4 class="mt-3">
                    Không thể xem trực tiếp file này
                </h4>

                <p>
                    Bạn có thể tải file xuống để mở.
                </p>

                <a
                    href="${escapeHtml(fileUrl)}"
                    target="_blank"
                    class="btn btn-primary">

                    <i class="bi bi-download"></i>
                    Mở / tải file

                </a>

            </div>
        `;

    } catch (error) {

        body.innerHTML = `
            <div class="preview-error">

                <i
                    class="bi bi-exclamation-triangle text-warning"
                    style="font-size:60px;">
                </i>

                <h4 class="mt-3">
                    Không thể xem file
                </h4>

                <p>
                    ${escapeHtml(error.message)}
                </p>

                <a
                    href="${escapeHtml(fileUrl)}"
                    target="_blank"
                    class="btn btn-primary">

                    <i class="bi bi-download"></i>
                    Mở file

                </a>

            </div>
        `;
    }
}

function extractNewsIdFromFileUrl(fileUrl)
{
    const match =
        String(fileUrl).match(
            /\/api\/news\/(\d+)\/file/
        );

    if (match) {
        return match[1];
    }

    return '';
}

document.addEventListener(
    'DOMContentLoaded',
    function()
    {
        updateCategoryButtons();

        updateRoleDescription();

        loadNews();
    }
);

</script>

</body>

</html>