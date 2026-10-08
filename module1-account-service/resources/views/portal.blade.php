<!DOCTYPE html>
<html lang="vi">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Hỗ trợ sinh viên</title>
<style>body{font-family:system-ui;background:#f4f7fb;color:#172b4d;max-width:1000px;margin:60px auto;padding:24px}nav{display:flex;gap:12px;flex-wrap:wrap}a{display:block;background:white;padding:22px;border-radius:12px;color:#245bc4;text-decoration:none;border:1px solid #dce4f0}p{color:#51617a}</style></head>
<body><h1>Cổng hỗ trợ sinh viên</h1><p id="identity">Đang kiểm tra đăng nhập…</p>
<nav>
<a href="/profile">Hồ sơ cá nhân</a>
@foreach(['catalog' => 'Danh mục hỗ trợ', 'requests' => 'Yêu cầu hỗ trợ', 'news' => 'Tin tức và tài liệu', 'reports' => 'Báo cáo và đánh giá'] as $key => $label)
<a href="{{ config('portal.services.'.$key) }}">{{ $label }}</a>
@endforeach
<a href="/admin/users" id="admin" hidden>Quản lý tài khoản</a>
</nav>
<script>
(async () => {
    const token = localStorage.getItem('access_token');
    if (!token) { location.replace('/login'); return; }
    try {
        const response = await fetch('/api/v1/auth/me', {headers: {'Accept':'application/json','Authorization':'Bearer '+token}});
        if (!response.ok) { location.replace('/login'); return; }
        const {user} = await response.json();
        document.getElementById('identity').textContent = user.name;
        document.getElementById('admin').hidden = user.role !== 'ADMIN';
    } catch (_) { document.getElementById('identity').textContent = 'Không thể kết nối dịch vụ tài khoản.'; }
})();
</script></body></html>
