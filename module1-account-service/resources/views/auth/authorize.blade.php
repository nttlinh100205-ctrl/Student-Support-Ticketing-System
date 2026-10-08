<!DOCTYPE html>
<html lang="vi">
<head><meta charset="utf-8"><meta name="referrer" content="no-referrer"><title>Đăng nhập hệ thống</title></head>
<body>
<p id="message">Đang xác thực phiên đăng nhập…</p>
<script>
const params = new URLSearchParams(window.location.search);
const payload = Object.fromEntries(['service', 'state', 'challenge'].map(key => [key, params.get(key)]));
const loginUrl = '/login?' + new URLSearchParams(payload);
async function authorize() {
    const token = localStorage.getItem('access_token');
    if (!token) { window.location.replace(loginUrl); return; }
    try {
        const response = await fetch('/api/v1/auth/sso/ticket', {
            method: 'POST', headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token},
            body: JSON.stringify(payload)
        });
        if (response.status === 401) {
            localStorage.removeItem('access_token');
            localStorage.removeItem('current_user');
            window.location.replace(loginUrl); return;
        }
        const result = await response.json();
        if (!response.ok) { throw new Error(result.message || 'Không thể đăng nhập dịch vụ.'); }
        window.location.replace(result.redirect);
    } catch (error) { document.getElementById('message').textContent = error.message; }
}
authorize();
</script>
</body></html>
