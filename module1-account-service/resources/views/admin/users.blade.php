<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Quản lý người dùng – UniSupport</title>
<link rel="stylesheet" href="/css/suite.css">
<script>try{document.documentElement.dataset.theme=localStorage.getItem('unisupport-theme')||'light'}catch(e){}</script>
</head>
<body class="suite-ui">
@include('partials.suite-header')
<main class="uni-dashboard" style="padding:30px;max-width:1500px;margin:auto" id="users-workspace" hidden>
<div class="uni-page-heading">
<div>
<span class="uni-eyebrow">QUẢN TRỊ HỆ THỐNG</span>
<h1>Quản lý người dùng</h1>
<p>Tài khoản, quyền truy cập và phòng ban của toàn trường.</p>
</div>
<button class="uni-button" id="add-user">+ Thêm tài khoản</button>
</div>
<div id="users-message" role="status">
</div>
<x-card title="Danh sách tài khoản">
<form class="uni-form uni-user-filters" id="user-filters">
<input name="q" aria-label="Tìm người dùng" placeholder="Tìm tên hoặc email…">
<select name="role" aria-label="Vai trò">
<option value="">Tất cả vai trò</option>
@foreach(['ADMIN'=>'Quản trị viên','DEPARTMENT_HEAD'=>'Trưởng phòng','STAFF'=>'Cán bộ','STUDENT'=>'Sinh viên'] as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>
@endforeach</select>
<select name="status" aria-label="Trạng thái">
<option value="">Tất cả trạng thái</option>
<option value="ACTIVE">Đang hoạt động</option>
<option value="LOCKED">Đã khóa</option>
</select>
<button class="uni-button">Tìm kiếm</button>
</form>
<div class="uni-table-scroll">
<table class="uni-table">
<thead>
<tr>
<th>Người dùng</th>
<th>Vai trò</th>
<th>Phòng ban</th>
<th>Trạng thái</th>
<th>Thao tác</th>
</tr>
</thead>
<tbody id="users-body">
</tbody>
</table>
</div>
<div id="user-pagination" class="uni-step-controls">
</div>
</x-card>
<x-modal name="user-dialog" title="Thông tin tài khoản">
<form id="user-form" class="uni-form">
<input name="id" type="hidden">
<label>Họ và tên<input name="name" required maxlength="255">
</label>
<label>Email<input name="email" type="email" required maxlength="255">
</label>
<label>Mật khẩu<input name="password" type="password" minlength="8" autocomplete="new-password">
<small>Để trống khi sửa nếu muốn giữ mật khẩu hiện tại.</small>
</label>
<label>Số điện thoại<input name="phone" maxlength="20">
</label>
<label>Vai trò<select name="role">
<option value="STUDENT">Sinh viên</option>
<option value="STAFF">Cán bộ</option>
<option value="DEPARTMENT_HEAD">Trưởng phòng</option>
<option value="ADMIN">Quản trị viên</option>
</select>
</label>
<label>Phòng ban<select name="department_id" id="user-department">
<option value="">Không thuộc phòng hỗ trợ</option>
</select>
</label>
<label>Trạng thái<select name="status">
<option value="ACTIVE">Đang hoạt động</option>
<option value="LOCKED">Đã khóa</option>
</select>
</label>
<div id="user-form-error" role="alert">
</div>
<button class="uni-button" type="submit">Lưu tài khoản</button>
</form>
</x-modal>
<x-modal name="delete-user-dialog" title="Xóa tài khoản">
<p>Tài khoản sẽ bị vô hiệu hóa đăng nhập và đưa ra khỏi danh sách. Lịch sử yêu cầu được giữ lại.</p>
<button type="button" class="uni-button" id="confirm-delete-user">Xác nhận xóa</button>
</x-modal>
</main>
<p id="users-auth-status" role="status" style="padding:30px">Đang kiểm tra quyền truy cập…</p>
<script>window.UniCatalogUrl=@json(config('ui.catalog'));</script>
<script src="/js/admin-users.js" defer>
</script>
</body>
</html>
