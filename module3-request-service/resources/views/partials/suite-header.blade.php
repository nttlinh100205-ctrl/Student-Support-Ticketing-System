@php
    $suiteUser = request()->attributes->get('account_user');
    $suiteRole = $suiteUser['role'] ?? '';
    $suiteAccountUrl = rtrim(config('account.url', config('app.url')), '/');
    $suiteService = config('account.service', 'accounts');
    $suiteRoleLabel = ['admin' => 'Quản trị viên', 'student' => 'Sinh viên', 'staff' => 'Cán bộ', 'department_head' => 'Trưởng phòng'][$suiteRole] ?? 'Không gian làm việc';
@endphp
<link rel="stylesheet" href="/css/navigation.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<link rel="stylesheet" href="/css/unisupport.css">
<script src="/js/unisupport.js" defer></script>
<header class="suite-header">
    @if(!request()->is('login', 'register', 'sso/*', '*password*'))<button class="system-menu-toggle" type="button" aria-controls="system-sidebar" aria-expanded="false" aria-label="Mở menu điều hướng">☰</button>@endif
    <a class="suite-brand" href="{{ $suiteAccountUrl }}"><img src="/images/unisupport.svg" width="44" height="44" alt="Logo UniSupport"><span><strong>UniSupport</strong><small>CỔNG HỖ TRỢ SINH VIÊN</small></span></a>
    @if(!request()->is('login', 'register', 'sso/*', '*password*'))
    <form class="uni-global-search" action="{{ rtrim(config('ui.requests'), '/') }}/requests"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input name="q" aria-label="Tìm yêu cầu" placeholder="Tìm mã hoặc nội dung yêu cầu…"></form>
    @endif
    <div class="uni-top-tools"><button type="button" id="theme-toggle" class="uni-icon-button" aria-label="Bật chế độ tối" title="Đổi giao diện"><i class="fa-solid fa-moon" aria-hidden="true"></i></button>
    @if(!request()->is('login', 'register', 'sso/*', '*password*'))
    <details class="uni-notifications"><summary aria-label="Thông báo"><i class="fa-regular fa-bell" aria-hidden="true"></i><span class="uni-notification-dot"></span></summary><div class="uni-notification-panel"><strong>Thông báo yêu cầu</strong><div id="uni-notification-list" data-url="{{ rtrim(config('ui.requests'), '/') }}/api/requests" data-web="{{ rtrim(config('ui.requests'), '/') }}/requests">Mở để xem các cập nhật gần đây.</div></div></details>
    @endif</div>
    @if($suiteUser)
    <details class="uni-account-menu"><summary><span class="suite-avatar">{{ mb_substr($suiteUser['full_name'], 0, 1) }}</span><span class="uni-account-name">{{ $suiteUser['full_name'] }}<small>{{ $suiteRoleLabel }}</small></span><i class="fa-solid fa-chevron-down"></i></summary><div class="uni-account-panel"><a href="{{ $suiteAccountUrl }}/profile">Hồ sơ cá nhân</a><a href="{{ $suiteAccountUrl }}/profile#passwordForm">Đổi mật khẩu</a><form method="POST" action="{{ route('account.logout') }}">@csrf<button type="submit">Đăng xuất</button></form></div></details>
    @else
    @if(!request()->is('login','register','sso/*','*password*'))
    <details class="uni-account-menu"><summary><span class="suite-avatar"><i class="fa-regular fa-user"></i></span><span class="uni-account-name" data-account-name>Tài khoản của tôi</span><i class="fa-solid fa-chevron-down"></i></summary><div class="uni-account-panel"><a href="{{ $suiteAccountUrl }}/profile">Hồ sơ cá nhân</a><a href="{{ $suiteAccountUrl }}/profile#passwordForm">Đổi mật khẩu</a><button type="button" data-account-logout>Đăng xuất</button></div></details>
    @endif
    @endif
</header>
@if(!request()->is('login', 'register', 'sso/*', '*password*'))
    @include('partials.suite-sidebar')
    <script src="/js/navigation.js" defer></script>
@endif

@if(!request()->is('login', 'register', 'sso/*', '*password*'))
<script src="/js/ai-assistant.js" data-endpoint="{{ rtrim(config('ui.requests'), '/') }}/api/ai/chat" defer></script>
@endif
