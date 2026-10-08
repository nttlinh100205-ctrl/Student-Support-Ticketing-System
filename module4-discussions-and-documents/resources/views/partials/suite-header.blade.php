@php
    $suiteUser = request()->attributes->get('account_user');
    $suiteRole = $suiteUser['role'] ?? '';
    $suiteAccountUrl = rtrim(config('account.url', config('app.url')), '/');
    $suiteService = config('account.service', 'accounts');
    $suiteRoleLabel = ['admin' => 'Quản trị viên', 'student' => 'Sinh viên', 'staff' => 'Cán bộ', 'department_head' => 'Trưởng phòng'][$suiteRole] ?? 'Không gian làm việc';
@endphp
<header class="suite-header">
    <a class="suite-brand" href="{{ $suiteAccountUrl }}"><svg viewBox="0 0 48 48" fill="none" aria-hidden="true"><rect width="48" height="48" rx="12" fill="white"/><path d="M13 13h22v16H23l-7 6v-6h-3V13Z" fill="#2262b8"/><path d="m18 21 4 4 8-9" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg><span><strong>Hệ thống xử lý yêu cầu sinh viên</strong><small>KẾT NỐI · TIẾP NHẬN · ĐỒNG HÀNH</small></span></a>
    @if($suiteUser)
    <div class="suite-account"><span class="suite-avatar">{{ mb_substr($suiteUser['full_name'], 0, 1) }}</span><div><strong>{{ $suiteUser['full_name'] }}</strong><small>{{ $suiteRoleLabel }}</small></div><form method="POST" action="{{ route('account.logout') }}">@csrf<button type="submit">Đăng xuất</button></form></div>
    @else
    <a href="{{ $suiteAccountUrl }}/profile" style="color:#b8cee7;font-size:12px;text-decoration:none">Tài khoản của tôi ↗</a>
    @endif
</header>
@if(!request()->is('login', 'register', 'sso/*'))
<nav class="suite-nav" aria-label="Điều hướng hệ thống">
    <a href="{{ $suiteAccountUrl }}" @if($suiteService === 'accounts') aria-current="page" @endif>Tổng quan</a>
    <a href="{{ config('ui.catalog') }}/catalog" @if($suiteService === 'catalog') aria-current="page" @endif>Danh mục & tổ chức</a>
    <a href="{{ config('ui.requests') }}/requests" @if($suiteService === 'requests') aria-current="page" @endif>Yêu cầu hỗ trợ</a>
    <a href="{{ config('ui.news') }}" @if($suiteService === 'news') aria-current="page" @endif>Tin tức & tài liệu</a>
    @if(in_array($suiteRole, ['admin', 'staff', 'department_head']))
    <a href="{{ config('ui.reports') }}" @if($suiteService === 'reports') aria-current="page" @endif>Báo cáo & đánh giá</a>
    @endif
    @if($suiteRole === 'admin')<a href="{{ $suiteAccountUrl }}/admin/users">Quản lý tài khoản</a>@endif
</nav>
@endif
