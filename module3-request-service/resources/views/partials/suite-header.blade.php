@php
    $suiteUser = request()->attributes->get('account_user');
    $suiteRole = $suiteUser['role'] ?? '';
    $suiteAccountUrl = rtrim(config('account.url', config('app.url')), '/');
    $suiteService = config('account.service', 'accounts');
    $suiteRoleLabel = ['admin' => 'Quản trị viên', 'student' => 'Sinh viên', 'staff' => 'Cán bộ', 'department_head' => 'Trưởng phòng'][$suiteRole] ?? 'Không gian làm việc';
@endphp
<link rel="stylesheet" href="/css/navigation.css">
<header class="suite-header">
    @if(!request()->is('login', 'register', 'sso/*'))<button class="system-menu-toggle" type="button" aria-controls="system-sidebar" aria-expanded="false" aria-label="Mở menu điều hướng">☰</button>@endif
    <a class="suite-brand" href="{{ $suiteAccountUrl }}"><svg viewBox="0 0 48 48" fill="none" aria-hidden="true"><rect width="48" height="48" rx="12" fill="white"/><path d="M13 13h22v16H23l-7 6v-6h-3V13Z" fill="#2262b8"/><path d="m18 21 4 4 8-9" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg><span><strong>Hệ thống xử lý yêu cầu sinh viên</strong><small>KẾT NỐI · TIẾP NHẬN · ĐỒNG HÀNH</small></span></a>
    @if($suiteUser)
    <div class="suite-account"><span class="suite-avatar">{{ mb_substr($suiteUser['full_name'], 0, 1) }}</span><div><strong>{{ $suiteUser['full_name'] }}</strong><small>{{ $suiteRoleLabel }}</small></div><form method="POST" action="{{ route('account.logout') }}">@csrf<button type="submit">Đăng xuất</button></form></div>
    @else
    <a href="{{ $suiteAccountUrl }}/profile" style="color:#b8cee7;font-size:12px;text-decoration:none">Tài khoản của tôi ↗</a>
    @endif
</header>
@if(!request()->is('login', 'register', 'sso/*'))
    @include('partials.suite-sidebar')
    <script src="/js/navigation.js" defer></script>
@endif
