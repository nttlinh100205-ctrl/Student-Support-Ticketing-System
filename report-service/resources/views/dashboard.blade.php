<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo &amp; Đánh giá — Hệ thống Hỗ trợ Sinh viên</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:opsz,wght@8..60,500;8..60,600;8..60,700&family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --paper: #f6f3ec;
            --panel: #fbf9f4;
            --ink: #1c2436;
            --ink-soft: #5a6274;
            --rule: #ddd6c4;
            --rule-soft: #e8e2d2;
            --brass: #8a6a34;
            --brass-dark: #6d5228;
            --sage: #4c6e52;
            --sage-bg: rgba(76, 110, 82, 0.1);
            --clay: #a15a22;
            --clay-bg: rgba(161, 90, 34, 0.1);
            --indigo: #37517a;
            --indigo-bg: rgba(55, 81, 122, 0.1);
            --rose: #99493f;
            --rose-bg: rgba(153, 73, 63, 0.1);
            --gold: #a9781f;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'IBM Plex Sans', sans-serif;
            background-color: var(--paper);
            color: var(--ink);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        h1, h2, h3, .headline {
            font-family: 'Source Serif 4', serif;
        }

        a { color: inherit; }

        /* ---------- Masthead ---------- */
        header {
            background: var(--panel);
            border-bottom: 2px solid var(--ink);
            padding: 0.95rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-mark {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background: var(--ink);
            color: var(--paper);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Source Serif 4', serif;
            font-weight: 700;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .brand-text .system-name {
            font-size: 0.72rem;
            color: var(--ink-soft);
            font-weight: 500;
            line-height: 1.3;
        }

        .brand-text h1 {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--ink);
            line-height: 1.3;
        }

        /* Account switcher, styled as a real "signed in as" chip */
        .account-switcher {
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid var(--rule);
            border-radius: 999px;
            padding: 5px 14px 5px 6px;
            background: var(--paper);
        }

        .avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--brass);
            color: var(--panel);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.72rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        .account-switcher select {
            appearance: none;
            -webkit-appearance: none;
            background: transparent;
            border: none;
            color: var(--ink);
            font-weight: 600;
            font-size: 0.84rem;
            font-family: inherit;
            outline: none;
            cursor: pointer;
            padding-right: 18px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%235a6274' stroke-width='1.4' fill='none' fill-rule='evenodd'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right center;
        }

        .account-switcher select option {
            font-weight: 500;
        }

        /* ---------- Secondary nav ---------- */
        .tab-bar {
            background: var(--panel);
            border-bottom: 1px solid var(--rule);
            padding: 0 2rem;
            display: flex;
            gap: 1.75rem;
        }

        .tab-bar span {
            display: inline-block;
            padding: 0.85rem 0.1rem;
            font-size: 0.86rem;
            font-weight: 500;
            color: var(--ink-soft);
            border-bottom: 2px solid transparent;
        }

        .tab-bar span.active {
            color: var(--ink);
            font-weight: 600;
            border-bottom-color: var(--brass);
        }

        .tab-bar span.disabled {
            color: #bdb6a2;
            cursor: default;
        }

        /* ---------- Layout ---------- */
        .container {
            max-width: 1320px;
            margin: 0 auto;
            padding: 2rem;
            width: 100%;
            flex: 1;
        }

        .page-heading {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 1.5rem;
        }

        .page-heading h2 {
            font-size: 1.4rem;
            font-weight: 600;
        }

        .page-heading .last-sync {
            font-size: 0.8rem;
            color: var(--ink-soft);
        }

        /* ---------- Filter strip ---------- */
        .filter-bar {
            border-top: 1px solid var(--rule);
            border-bottom: 1px solid var(--rule);
            padding: 1.1rem 0;
            margin-bottom: 2rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            align-items: flex-end;
        }

        .filter-item {
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex: 1;
            min-width: 170px;
        }

        .filter-item label {
            font-size: 0.78rem;
            font-weight: 500;
            color: var(--ink-soft);
        }

        .filter-item input, .filter-item select {
            background: transparent;
            border: none;
            border-bottom: 1px solid var(--rule);
            color: var(--ink);
            padding: 6px 2px;
            font-size: 0.92rem;
            font-family: inherit;
            outline: none;
            transition: border-color 0.2s;
        }

        .filter-item input:focus, .filter-item select:focus {
            border-bottom-color: var(--brass);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 9px 18px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            border: 1px solid transparent;
            transition: background 0.15s, border-color 0.15s;
            text-decoration: none;
            height: 40px;
            font-family: inherit;
        }

        .btn-primary {
            background: var(--ink);
            color: var(--paper);
        }

        .btn-primary:hover {
            background: var(--indigo);
        }

        .btn-secondary {
            background: transparent;
            color: var(--ink);
            border-color: var(--rule);
        }

        .btn-secondary:hover {
            border-color: var(--ink);
        }

        /* ---------- KPI summary strip ---------- */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            margin-bottom: 2.25rem;
            border-top: 1px solid var(--rule);
        }

        .stat-card {
            padding: 1.1rem 1.4rem 0.4rem;
            border-right: 1px solid var(--rule-soft);
            border-bottom: 1px solid var(--rule);
        }

        .stat-card:last-child {
            border-right: none;
        }

        .stat-title {
            font-size: 0.78rem;
            color: var(--ink-soft);
            font-weight: 500;
        }

        .stat-value {
            font-family: 'Source Serif 4', serif;
            font-size: 2.1rem;
            font-weight: 600;
            color: var(--ink);
            line-height: 1.25;
            display: block;
            margin: 2px 0;
        }

        .stat-sub {
            font-size: 0.78rem;
            color: var(--brass-dark);
            display: block;
            padding-bottom: 0.9rem;
        }

        /* ---------- Panels ---------- */
        .grid-2col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.75rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 900px) {
            .grid-2col {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: var(--panel);
            border: 1px solid var(--rule);
            border-radius: 6px;
            padding: 1.4rem 1.5rem;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.1rem;
            padding-bottom: 0.8rem;
            border-bottom: 1px solid var(--rule);
        }

        .card-title {
            font-family: 'Source Serif 4', serif;
            font-size: 1.05rem;
            font-weight: 600;
            color: var(--ink);
        }

        .card-title-sub {
            font-size: 0.78rem;
            color: var(--ink-soft);
            font-weight: 400;
            display: block;
            margin-top: 2px;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        th {
            text-align: left;
            padding: 10px 12px;
            color: var(--ink-soft);
            font-weight: 600;
            border-bottom: 1px solid var(--ink);
            font-size: 0.78rem;
        }

        td {
            padding: 10px 12px;
            border-bottom: 1px solid var(--rule-soft);
        }

        tr:hover td {
            background: rgba(138, 106, 52, 0.05);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 9px;
            border-radius: 3px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-resolved { background: var(--sage-bg); color: var(--sage); }
        .status-in_progress { background: var(--indigo-bg); color: var(--indigo); }
        .status-new { background: var(--clay-bg); color: var(--clay); }
        .status-received { background: rgba(138, 106, 52, 0.12); color: var(--brass-dark); }
        .status-cancelled { background: var(--rose-bg); color: var(--rose); }

        .star-rating {
            color: var(--gold);
            font-size: 1rem;
            letter-spacing: 1px;
        }

        .empty-state {
            text-align: center;
            color: var(--ink-soft);
            padding: 1.4rem 0;
        }

        /* ---------- Footer ---------- */
        footer {
            border-top: 1px solid var(--rule);
            padding: 1.25rem 2rem;
            font-size: 0.78rem;
            color: var(--ink-soft);
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

    </style>
</head>
<body>

    <header>
        <div class="brand">
            <div class="brand-mark">HT</div>
            <div class="brand-text">
                <div class="system-name">Hệ thống Hỗ trợ Sinh viên</div>
                <h1>Báo cáo &amp; Đánh giá</h1>
            </div>
        </div>

        <div class="account-switcher">
            <span class="avatar" id="accountAvatar">—</span>
            <select id="roleSelector" onchange="switchRole()">
                <option value="admin" data-id="1" data-dept="" data-name="Nguyễn Văn Quản"> Quản trị viên</option>
                <option value="department_head" data-id="5" data-dept="3" data-name="Lê Thị Hương">Trưởng phòng CTSV</option>
                <option value="staff" data-id="12" data-dept="1" data-name="Phạm Văn Đức">Cán bộ Phòng Đào tạo</option>
            </select>
        </div>
    </header>

    <nav class="tab-bar">
        <span class="active">Tổng quan</span>
        <span class="disabled" title="Sắp ra mắt">Yêu cầu hỗ trợ</span>
        <span class="disabled" title="Sắp ra mắt">Đánh giá</span>
        <span class="disabled" title="Sắp ra mắt">Cấu hình</span>
    </nav>

    <div class="container">

        <div class="page-heading">
            <h2>Tổng quan báo cáo</h2>
            <span class="last-sync" id="lastSync">Đang tải dữ liệu...</span>
        </div>

        <!-- Filter Controls -->
        <div class="filter-bar">
            <div class="filter-item">
                <label>Phòng ban</label>
                <select id="deptFilter">
                    <option value="">Tất cả phòng ban</option>
                    <option value="1">Phòng Đào tạo</option>
                    <option value="2">Phòng Kế hoạch - Tài chính</option>
                    <option value="3">Phòng Công tác Sinh viên</option>
                    <option value="4">Phòng Quản lý Khoa học</option>
                    <option value="5">Trung tâm Khảo thí</option>
                </select>
            </div>

            <div class="filter-item">
                <label>Loại yêu cầu</label>
                <select id="typeFilter">
                    <option value="">Tất cả loại yêu cầu</option>
                    <option value="1">Cấp lại thẻ sinh viên</option>
                    <option value="2">Xin xác nhận sinh viên</option>
                    <option value="3">Đăng ký học phần bổ sung</option>
                    <option value="4">Xác nhận nộp học phí</option>
                    <option value="5">Phúc khảo bài thi</option>
                    <option value="6">Giới thiệu thực tập tốt nghiệp</option>
                </select>
            </div>

            <div class="filter-item">
                <label>Từ ngày</label>
                <input type="date" id="fromDate" value="2026-09-01">
            </div>

            <div class="filter-item">
                <label>Đến ngày</label>
                <input type="date" id="toDate" value="2026-09-30">
            </div>

            <button class="btn btn-primary" onclick="loadDashboardData()">Lọc dữ liệu</button>
            <button class="btn btn-secondary" onclick="exportCsv()">Xuất CSV</button>
        </div>

        <!-- KPI Summary Strip -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-title">Tổng số yêu cầu</span>
                <span class="stat-value" id="kpiTotal">—</span>
                <span class="stat-sub" id="kpiResolvedRate">Tỷ lệ hoàn thành: —%</span>
            </div>
            <div class="stat-card">
                <span class="stat-title">Thời gian xử lý trung bình</span>
                <span class="stat-value" id="kpiAvgHours">—</span>
                <span class="stat-sub">Tính từ lúc tạo đến khi giải quyết</span>
            </div>
            <div class="stat-card">
                <span class="stat-title">Điểm đánh giá trung bình</span>
                <span class="stat-value" id="kpiAvgRating" style="color: var(--gold);">— ⭐</span>
                <span class="stat-sub" id="kpiTotalRatings">Tổng số lượt đánh giá: —</span>
            </div>
            <div class="stat-card">
                <span class="stat-title">Trạng thái nổi bật</span>
                <span class="stat-value" id="kpiTopStatus" style="font-size: 1.4rem;">—</span>
                <span class="stat-sub" id="kpiPendingCount">Đang xử lý / chờ tiếp nhận: —</span>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid-2col">
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Phân bổ theo trạng thái</span>
                </div>
                <div style="height: 260px; position: relative;">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title">Xu hướng yêu cầu theo ngày</span>
                </div>
                <div style="height: 260px; position: relative;">
                    <canvas id="timeChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Department & Support Type Breakdowns -->
        <div class="grid-2col">
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Thống kê theo phòng ban</span>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Phòng ban</th>
                                <th>Số lượng</th>
                                <th>Tỷ lệ</th>
                            </tr>
                        </thead>
                        <tbody id="deptTableBody">
                            <tr><td colspan="3" class="empty-state">Đang tải...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title">Thống kê theo loại yêu cầu</span>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Loại yêu cầu</th>
                                <th>Số lượng</th>
                            </tr>
                        </thead>
                        <tbody id="typeTableBody">
                            <tr><td colspan="2" class="empty-state">Đang tải...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Ratings (read-only feed) -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <div>
                    <span class="card-title">Phản hồi từ sinh viên</span>
                    <span class="card-title-sub">Đánh giá sinh viên gửi sau khi yêu cầu được xử lý xong</span>
                </div>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Mã yêu cầu</th>
                            <th>Sinh viên</th>
                            <th>Phòng ban</th>
                            <th>Mức đánh giá</th>
                            <th>Nhận xét</th>
                            <th>Thời gian</th>
                        </tr>
                    </thead>
                    <tbody id="ratingsTableBody">
                        <tr><td colspan="6" class="empty-state">Đang tải danh sách đánh giá...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <footer>
        <span>Hệ thống Hỗ trợ Sinh viên · Dịch vụ Báo cáo &amp; Đánh giá</span>
        <span>Phiên bản nội bộ 1.0</span>
    </footer>

    <script>
        let statusChartInstance = null;
        let timeChartInstance = null;

        function initials(name) {
            const parts = name.trim().split(/\s+/);
            if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
            return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
        }

        function getAuthHeaders() {
            const selector = document.getElementById('roleSelector');
            const selectedOpt = selector.options[selector.selectedIndex];
            const role = selectedOpt.value;
            const userId = selectedOpt.getAttribute('data-id') || 1;
            const deptId = selectedOpt.getAttribute('data-dept') || '';

            const headers = {
                'X-User-Id': userId,
                'X-User-Role': role,
                'Accept': 'application/json'
            };
            if (deptId) {
                headers['X-Department-Id'] = deptId;
            }
            return headers;
        }

        function updateAccountChip() {
            const selector = document.getElementById('roleSelector');
            const opt = selector.options[selector.selectedIndex];
            const name = opt.getAttribute('data-name') || opt.textContent;
            document.getElementById('accountAvatar').textContent = initials(name);
        }

        function switchRole() {
            updateAccountChip();
            loadDashboardData();
            loadRatingsList();
        }

        async function loadDashboardData() {
            const dept = document.getElementById('deptFilter').value;
            const type = document.getElementById('typeFilter').value;
            const from = document.getElementById('fromDate').value;
            const to = document.getElementById('toDate').value;

            const params = new URLSearchParams();
            if (dept) params.append('department_id', dept);
            if (type) params.append('support_type_id', type);
            if (from) params.append('from_date', from);
            if (to) params.append('to_date', to);

            try {
                const response = await fetch(`/api/reports/statistics?${params.toString()}`, { headers: getAuthHeaders() });
                const result = await response.json();

                if (result.success && result.data) {
                    renderDashboard(result.data);
                    const now = new Date();
                    document.getElementById('lastSync').innerText = `Cập nhật lúc ${now.toLocaleTimeString('vi-VN')}`;
                } else {
                    document.getElementById('lastSync').innerText = 'Không lấy được dữ liệu';
                    alert(result.message || 'Lỗi lấy dữ liệu báo cáo');
                }
            } catch (err) {
                console.error(err);
                document.getElementById('lastSync').innerText = 'Lỗi kết nối máy chủ';
            }
        }

        function renderDashboard(data) {
            // KPI
            document.getElementById('kpiTotal').innerText = Number(data.total_requests || 0).toLocaleString('vi-VN');
            const resolvedCount = (data.by_status?.resolved || 0) + (data.by_status?.closed || 0);
            const resolvedRate = data.total_requests > 0 ? Math.round((resolvedCount / data.total_requests) * 100) : 0;
            document.getElementById('kpiResolvedRate').innerText = `Tỷ lệ hoàn thành: ${resolvedRate}% (${resolvedCount}/${data.total_requests})`;

            document.getElementById('kpiAvgHours').innerText = data.avg_processing_hours !== null && data.avg_processing_hours !== undefined
                ? `${Number(data.avg_processing_hours).toLocaleString('vi-VN')}h`
                : 'N/A';

            if (data.ratings_summary) {
                document.getElementById('kpiAvgRating').innerText = `${data.ratings_summary.average_rating || 0} ⭐`;
                document.getElementById('kpiTotalRatings').innerText = `Tổng số lượt đánh giá: ${data.ratings_summary.total_ratings || 0}`;
            }

            const pending = (data.by_status?.new || 0) + (data.by_status?.received || 0) + (data.by_status?.in_progress || 0);
            document.getElementById('kpiPendingCount').innerText = `Đang xử lý / chờ tiếp nhận: ${pending}`;
            document.getElementById('kpiTopStatus').innerText = resolvedCount >= pending ? 'Xử lý tốt' : 'Cần tăng tốc';

            // Charts
            renderStatusChart(data.by_status || {});
            renderTimeChart(data.requests_over_time || []);

            // Tables
            renderDeptTable(data.by_department || [], data.total_requests);
            renderTypeTable(data.by_support_type || []);
        }

        function renderStatusChart(statusData) {
            const ctx = document.getElementById('statusChart').getContext('2d');
            const labels = ['Mới', 'Đã tiếp nhận', 'Đang xử lý', 'Đã giải quyết', 'Đã hủy'];
            const values = [
                statusData.new || 0,
                statusData.received || 0,
                statusData.in_progress || 0,
                statusData.resolved || 0,
                statusData.cancelled || 0
            ];

            if (statusChartInstance) statusChartInstance.destroy();

            statusChartInstance = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: ['#a15a22', '#8a6a34', '#37517a', '#4c6e52', '#99493f'],
                        borderColor: '#fbf9f4',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'right', labels: { color: '#5a6274', font: { family: 'IBM Plex Sans' } } }
                    }
                }
            });
        }

        function renderTimeChart(timeData) {
            const ctx = document.getElementById('timeChart').getContext('2d');
            const labels = timeData.map(d => d.date);
            const values = timeData.map(d => d.total);

            if (timeChartInstance) timeChartInstance.destroy();

            timeChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Số yêu cầu',
                        data: values,
                        borderColor: '#8a6a34',
                        backgroundColor: 'rgba(138, 106, 52, 0.12)',
                        fill: true,
                        tension: 0.25,
                        pointBackgroundColor: '#8a6a34',
                        pointRadius: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#e8e2d2' }, ticks: { color: '#5a6274' } },
                        x: { grid: { color: '#e8e2d2' }, ticks: { color: '#5a6274' } }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }

        function renderDeptTable(departments, total) {
            const tbody = document.getElementById('deptTableBody');
            if (!departments.length) {
                tbody.innerHTML = '<tr><td colspan="3" class="empty-state">Không có dữ liệu</td></tr>';
                return;
            }
            tbody.innerHTML = departments.map(d => {
                const pct = total > 0 ? Math.round((d.total / total) * 100) : 0;
                return `
                    <tr>
                        <td style="font-weight: 600;">${d.department_name}</td>
                        <td><span style="font-weight: 700; color: var(--indigo);">${Number(d.total).toLocaleString('vi-VN')}</span> yêu cầu</td>
                        <td>${pct}%</td>
                    </tr>
                `;
            }).join('');
        }

        function renderTypeTable(types) {
            const tbody = document.getElementById('typeTableBody');
            if (!types.length) {
                tbody.innerHTML = '<tr><td colspan="2" class="empty-state">Không có dữ liệu</td></tr>';
                return;
            }
            tbody.innerHTML = types.map(t => `
                <tr>
                    <td style="font-weight: 600;">${t.name}</td>
                    <td><span style="font-weight: 700; color: var(--sage);">${Number(t.total).toLocaleString('vi-VN')}</span> yêu cầu</td>
                </tr>
            `).join('');
        }

        async function loadRatingsList() {
            try {
                const res = await fetch('/api/ratings', { headers: getAuthHeaders() });
                const json = await res.json();
                const tbody = document.getElementById('ratingsTableBody');

                if (json.success && json.data && json.data.data) {
                    const ratings = json.data.data;
                    if (!ratings.length) {
                        tbody.innerHTML = '<tr><td colspan="6" class="empty-state">Chưa có đánh giá nào</td></tr>';
                        return;
                    }
                    tbody.innerHTML = ratings.map(r => `
                        <tr>
                            <td><strong style="color: var(--indigo);">#${r.request_id}</strong></td>
                            <td>Sinh viên #${r.student_id}</td>
                            <td>Phòng ban #${r.department_id || 'N/A'}</td>
                            <td><span class="star-rating">${'⭐'.repeat(r.rating)}</span></td>
                            <td>${r.comment || '<em>Không có nhận xét</em>'}</td>
                            <td style="color: var(--ink-soft); font-size: 0.8rem;">${new Date(r.created_at).toLocaleString('vi-VN')}</td>
                        </tr>
                    `).join('');
                }
            } catch (err) {
                console.error(err);
            }
        }

        function exportCsv() {
            const dept = document.getElementById('deptFilter').value;
            const type = document.getElementById('typeFilter').value;
            const from = document.getElementById('fromDate').value;
            const to = document.getElementById('toDate').value;

            const params = new URLSearchParams();
            if (dept) params.append('department_id', dept);
            if (type) params.append('support_type_id', type);
            if (from) params.append('from_date', from);
            if (to) params.append('to_date', to);

            window.location.href = `/api/reports/export?${params.toString()}`;
        }

        // Init
        window.addEventListener('DOMContentLoaded', () => {
            updateAccountChip();
            loadDashboardData();
            loadRatingsList();
        });
    </script>
</body>
</html>