@php
    $catalogBase = rtrim(config('ui.catalog'), '/');
    $requestsBase = rtrim(config('ui.requests'), '/');
    $groups = [
        ['catalog', 'Danh mục & tổ chức', [
            [$catalogBase.'/catalog', 'Tra cứu hỗ trợ', ''],
            [$catalogBase.'/admin/departments', 'Phòng ban', 'admin'],
            [$catalogBase.'/admin/support-types', 'Loại hỗ trợ & SLA', 'admin'],
            [$catalogBase.'/admin/support-type-fields', 'Biểu mẫu theo loại', 'admin'],
            [$catalogBase.'/admin/department-staff', 'Cán bộ theo phòng ban', 'admin'],
            [$catalogBase.'/admin/faqs', 'Câu hỏi thường gặp', 'admin'],
        ], ''],
        ['requests', 'Yêu cầu hỗ trợ', [
            [$requestsBase.'/requests', 'Danh sách yêu cầu', ''],
            [$requestsBase.'/kanban', 'Bảng công việc', 'admin department_head staff'],
            [$requestsBase.'/team', 'Cán bộ & phân công', 'admin department_head'],
            [$requestsBase.'/ratings', 'Đánh giá của sinh viên', 'admin department_head staff'],
            [$requestsBase.'/admin/audit', 'Nhật ký xử lý', 'admin'],
            [$requestsBase.'/admin/settings', 'Cấu hình xử lý', 'admin'],
            [$requestsBase.'/requests/create', 'Tạo yêu cầu mới', 'student'],
            [$requestsBase.'/requests?queue=waiting_info', 'Cần bổ sung thông tin', 'student'],
            [$requestsBase.'/requests?queue=resolved', 'Chờ xác nhận kết quả', 'student'],
            [$requestsBase.'/requests?queue=unrated', 'Đánh giá hỗ trợ', 'student'],
            [$requestsBase.'/requests?queue=unassigned', 'Chờ phân công', 'admin department_head'],
            [$requestsBase.'/requests?queue=new', 'Chờ tiếp nhận', 'admin department_head staff'],
            [$requestsBase.'/requests?status=in_progress', 'Đang xử lý', 'admin department_head staff'],
            [$requestsBase.'/requests?queue=overdue', 'Yêu cầu quá hạn', 'admin department_head staff'],
            [$requestsBase.'/requests/export', 'Xuất báo cáo Excel', 'admin department_head'],
        ], ''],
        ['news', 'Tin tức & tài liệu', [[rtrim(config('ui.news'), '/').'/', 'Bảng tin & tài liệu', '']], ''],
        ['reports', 'Báo cáo & đánh giá', [[rtrim(config('ui.reports'), '/').'/', 'Thống kê hiệu suất', 'admin department_head staff']], 'admin department_head staff'],
        ['accounts', 'Tài khoản', [
            [$suiteAccountUrl.'/profile', 'Hồ sơ & mật khẩu', ''],
            [$suiteAccountUrl.'/admin/users', 'Quản lý người dùng', 'admin'],
        ], ''],
    ];
@endphp
<button class="system-menu-backdrop" type="button" aria-label="Đóng menu" hidden></button>
<aside id="system-sidebar" class="system-sidebar" data-role="{{ $suiteRole }}" data-service="{{ $suiteService }}" aria-label="Menu hệ thống" tabindex="-1">
    <div class="system-menu-heading"><span>KHÔNG GIAN LÀM VIỆC</span><button type="button" class="system-menu-close" aria-label="Đóng menu">×</button></div>
    <nav aria-label="Điều hướng chính">
        <a class="system-overview" href="{{ $suiteAccountUrl }}/"><span><x-nav-icon name="home"/></span> Trang chủ & tin mới</a>
        <a class="system-overview" href="{{ $requestsBase }}/dashboard"><span><x-nav-icon name="reports"/></span> Tổng quan</a>
        @foreach($groups as [$key, $label, $items, $roles])
            <details class="system-menu-group" data-group="{{ $key }}" data-roles="{{ $roles }}" @if($roles && !in_array($suiteRole, explode(' ', $roles), true)) hidden @endif @if($suiteService === $key) open @endif>
                <summary><span class="system-group-icon" aria-hidden="true"><x-nav-icon :name="$key"/></span>{{ $label }}<span class="system-chevron" aria-hidden="true">›</span></summary>
                <div class="system-submenu">
                    @foreach($items as [$href, $text, $itemRoles])
                        <a href="{{ $href }}" data-roles="{{ $itemRoles }}" @if($itemRoles && !in_array($suiteRole, explode(' ', $itemRoles), true)) hidden @endif><x-nav-icon :name="(['Phòng ban'=>'catalog','Cán bộ theo phòng ban'=>'accounts','Cán bộ & phân công'=>'accounts','Quản lý người dùng'=>'accounts','Hồ sơ & mật khẩu'=>'accounts','Tạo yêu cầu mới'=>'create','Bảng công việc'=>'kanban','Nhật ký xử lý'=>'file','Cấu hình xử lý'=>'settings','Loại hỗ trợ & SLA'=>'settings','Biểu mẫu theo loại'=>'file','Câu hỏi thường gặp'=>'help','Đánh giá của sinh viên'=>'star','Đánh giá hỗ trợ'=>'star','Yêu cầu quá hạn'=>'clock','Đang xử lý'=>'clock','Chờ xác nhận kết quả'=>'check','Cần bổ sung thông tin'=>'file','Xuất báo cáo Excel'=>'reports','Bảng tin & tài liệu'=>'news','Thống kê hiệu suất'=>'reports'])[$text] ?? $key"/><span>{{ $text }}</span></a>
                    @endforeach
                </div>
            </details>
        @endforeach
    </nav>
    <div class="system-menu-footer">
        <a href="{{ $suiteAccountUrl }}/profile"><span id="avatar" class="system-user-avatar">{{ mb_substr($suiteUser['full_name'] ?? 'U', 0, 1) }}</span><span><strong id="user-name">{{ $suiteUser['full_name'] ?? 'Tài khoản của bạn' }}</strong><small id="user-role">{{ $suiteRoleLabel }}</small></span></a>
        @if($suiteService === 'accounts')<button id="logout" type="button">Đăng xuất ↗</button>@endif
        <p id="system-menu-status" role="status" hidden></p>
    </div>
</aside>
