<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Không gian làm việc · Student Support</title>
<link rel="stylesheet" href="/css/workspace.css">
<link rel="stylesheet" href="/css/school.css">
<script id="services" type="application/json">{!! json_encode(config('portal.services'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) !!}</script>
<script src="/js/workspace.js" defer></script>
</head>
<body>
@include('partials.school-brand')
<div class="workspace">
<aside class="sidebar">
<a class="brand" href="/"><span class="brand-mark">✓</span><span>Yêu cầu sinh viên<small>KẾT NỐI & ĐỒNG HÀNH</small></span></a>
<p class="nav-label">KHÔNG GIAN LÀM VIỆC</p>
<nav id="navigation" aria-label="Điều hướng chính"><a class="active" href="/">Tổng quan</a></nav>
<div class="sidebar-note">● Một tài khoản, mọi hỗ trợ<p>Kết nối sinh viên với đúng phòng ban, theo dõi đến khi hoàn tất.</p></div>
<a class="profile-link" href="/profile"><span id="avatar" class="avatar">…</span><span><strong id="user-name">Đang xác thực…</strong><small id="user-role">Tài khoản của bạn</small></span></a>
<button id="logout" class="logout" type="button">Đăng xuất ↗</button>
</aside>
<main><header class="workspace-top"><span>Cổng hỗ trợ sinh viên <span class="muted">/ Tổng quan</span></span><span id="today"></span></header>
<div id="identity-error" class="notice" role="alert" hidden></div>
<div id="dashboard" hidden>
<section class="welcome"><div><p id="role-eyebrow" class="eyebrow"></p><h1 id="heading"></h1><p id="description"></p></div><a id="primary-action" class="button" href="#"></a></section>
<section class="hero"><div><span class="hero-tag">STUDENT SUPPORT / KHÔNG GIAN CỦA BẠN</span><h2 id="hero-title"></h2><p id="hero-description"></p><a id="hero-link" href="#">Bắt đầu ngay →</a></div><div class="hero-art" aria-hidden="true"><div class="orbit"></div><div class="paper"><span>YÊU CẦU HỖ TRỢ</span><i></i><i></i><i></i><b>✓ Được kết nối</b></div><span class="floating">✦</span></div></section>
<section class="metrics" aria-label="Thống kê yêu cầu">
<a class="metric" data-status="" href="#"><span>Tổng yêu cầu</span><strong id="count-all">—</strong><small id="scope-label"></small></a>
<a class="metric" data-status="new" href="#"><span>Mới tiếp nhận</span><strong id="count-new">—</strong><small>Chờ tiếp nhận và phân công</small></a>
<a class="metric" data-status="in_progress" href="#"><span>Đang xử lý</span><strong id="count-progress">—</strong><small>Đang được hỗ trợ</small></a>
<a class="metric" data-status="resolved" href="#"><span>Đã giải quyết</span><strong id="count-resolved">—</strong><small>Chờ xác nhận hoàn tất</small></a>
</section>
<p id="stats-feedback" class="stats-feedback" role="status">Đang tải số liệu từ dịch vụ yêu cầu…</p>
<section><div class="section-title"><h2>Tiện ích của bạn</h2><span>Truy cập nhanh</span></div><div id="actions" class="action-grid"></div></section>
<section class="panel"><div class="section-title"><div><h2 id="requests-heading">Yêu cầu cần theo dõi</h2><p>Ưu tiên yêu cầu khẩn cấp, sau đó đến yêu cầu mới nhất.</p></div><a id="view-all" href="#">Xem tất cả →</a></div>
<form id="search-form" class="filters"><label class="search"><span>Tìm yêu cầu</span><input id="search" placeholder="Mã yêu cầu hoặc tiêu đề…" type="search"></label><label><span>Trạng thái</span><select id="status"><option value="">Tất cả trạng thái</option><option value="new">Mới tạo</option><option value="received">Đã tiếp nhận</option><option value="in_progress">Đang xử lý</option><option value="waiting_info">Chờ bổ sung</option><option value="resolved">Đã giải quyết</option><option value="closed">Đã đóng</option><option value="cancelled">Đã hủy</option></select></label><button class="button secondary" type="submit">Tìm kiếm</button></form>
<div id="request-message" class="empty" role="status">Đang tải yêu cầu…</div>
<div class="table-scroll"><table id="request-table" hidden><thead><tr><th>Yêu cầu</th><th>Trạng thái</th><th>Mức ưu tiên</th><th>Ngày tạo</th><th></th></tr></thead><tbody id="request-rows"></tbody></table></div>
<div class="panel-footer"><span id="result-count"></span><button id="refresh" type="button" class="text-button">↻ Làm mới</button></div></section>
<footer class="workspace-footer">Student Support <span>Hỗ trợ đúng người · Theo dõi rõ ràng</span></footer>
</div></main></div></body></html>
