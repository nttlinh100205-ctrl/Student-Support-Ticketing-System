<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Báo Cáo & Đánh Giá - Module 5</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --bg: #0b0f19;
            --card-bg: rgba(23, 32, 54, 0.7);
            --card-border: rgba(255, 255, 255, 0.08);
            --primary: #3b82f6;
            --primary-glow: rgba(59, 130, 246, 0.3);
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
            --sidebar-bg: #0f172a;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        header {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--card-border);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .logo-group {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .badge-module {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .user-switcher {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255, 255, 255, 0.05);
            padding: 6px 14px;
            border-radius: 12px;
            border: 1px solid var(--card-border);
        }

        .user-switcher select {
            background: transparent;
            color: #60a5fa;
            border: none;
            font-weight: 600;
            font-size: 0.9rem;
            outline: none;
            cursor: pointer;
        }

        .user-switcher select option {
            background: #1e293b;
            color: #fff;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
            width: 100%;
            flex: 1;
        }

        .filter-bar {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: flex-end;
            backdrop-filter: blur(8px);
        }

        .filter-item {
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex: 1;
            min-width: 180px;
        }

        .filter-item label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-item input, .filter-item select {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--card-border);
            color: #fff;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 0.9rem;
            outline: none;
            transition: all 0.2s;
        }

        .filter-item input:focus, .filter-item select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-glow);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            text-decoration: none;
            height: 42px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: #fff;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #059669, #10b981);
            color: #fff;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-success:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 10px;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(8px);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
        }

        .stat-title {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 600;
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 800;
            color: #fff;
            line-height: 1.1;
        }

        .stat-sub {
            font-size: 0.8rem;
            color: #60a5fa;
        }

        .grid-2col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 900px) {
            .grid-2col {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 1.5rem;
            backdrop-filter: blur(8px);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--card-border);
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #fff;
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
            padding: 12px 14px;
            color: var(--text-muted);
            font-weight: 600;
            border-bottom: 1px solid var(--card-border);
            font-size: 0.8rem;
            text-transform: uppercase;
        }

        td {
            padding: 12px 14px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        }

        tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-resolved { background: rgba(16, 185, 129, 0.15); color: #34d399; }
        .status-in_progress { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
        .status-new { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }
        .status-received { background: rgba(139, 92, 246, 0.15); color: #a78bfa; }
        .status-cancelled { background: rgba(239, 68, 68, 0.15); color: #f87171; }

        .star-rating {
            color: #fbbf24;
            font-size: 1.1rem;
            letter-spacing: 2px;
        }

        .tab-btn-group {
            display: flex;
            gap: 8px;
            margin-bottom: 1.5rem;
        }

        .tab-btn {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--card-border);
            color: var(--text-muted);
            padding: 8px 16px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.2s;
        }

        .tab-btn.active {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }

        .raw-json {
            background: #060911;
            border: 1px solid var(--card-border);
            padding: 1rem;
            border-radius: 10px;
            font-family: 'Fira Code', monospace;
            font-size: 0.8rem;
            color: #38bdf8;
            max-height: 320px;
            overflow-y: auto;
        }

        .rating-form-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 1rem;
        }

        .rating-form-group input, .rating-form-group select, .rating-form-group textarea {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--card-border);
            color: #fff;
            padding: 10px 14px;
            border-radius: 10px;
            outline: none;
            font-size: 0.9rem;
        }

        .rating-form-group textarea {
            min-height: 80px;
            resize: vertical;
        }
    </style>
</head>
<body>

    <header>
        <div class="logo-group">
            <span class="badge-module">Module 5</span>
            <h1 style="font-size: 1.25rem; font-weight: 800; color: #fff;">Đánh Giá & Báo Cáo Service (:8005)</h1>
        </div>

        <div class="user-switcher">
            <span style="font-size: 0.8rem; color: var(--text-muted);">Đang giả lập vai trò:</span>
            <select id="roleSelector" onchange="switchRole()">
                <option value="admin" data-id="1" data-dept="">👑 Quản trị viên (Admin)</option>
                <option value="department_head" data-id="5" data-dept="3">👔 Trưởng phòng CTSV (Department Head)</option>
                <option value="staff" data-id="12" data-dept="1">👨‍💼 Cán bộ Phòng Đào tạo (Staff)</option>
                <option value="student" data-id="12" data-dept="">🎓 Sinh viên: Trần Thị B (Student #12)</option>
            </select>
        </div>
    </header>

    <div class="container">
        
        <!-- Filter Controls -->
        <div class="filter-bar">
            <div class="filter-item">
                <label>Phòng ban</label>
                <select id="deptFilter">
                    <option value="">-- Tất cả phòng ban --</option>
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
                    <option value="">-- Tất cả loại yêu cầu --</option>
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

            <button class="btn btn-primary" onclick="loadDashboardData()">
                🔍 Lọc dữ liệu
            </button>

            <button class="btn btn-success" onclick="exportCsv()">
                📥 Xuất CSV (Excel)
            </button>
        </div>

        <!-- KPI Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-title">TỔNG SỐ YÊU CẦU</span>
                <span class="stat-value" id="kpiTotal">--</span>
                <span class="stat-sub" id="kpiResolvedRate">Tỷ lệ hoàn thành: --%</span>
            </div>
            <div class="stat-card">
                <span class="stat-title">THỜI GIAN XỬ LÝ TB</span>
                <span class="stat-value" id="kpiAvgHours">--</span>
                <span class="stat-sub">Tính theo giờ từ lúc tạo đến giải quyết</span>
            </div>
            <div class="stat-card">
                <span class="stat-title">ĐIỂM ĐÁNH GIÁ TRUNG BÌNH</span>
                <span class="stat-value" id="kpiAvgRating" style="color: #fbbf24;">⭐ --</span>
                <span class="stat-sub" id="kpiTotalRatings">Tổng số lượt đánh giá: --</span>
            </div>
            <div class="stat-card">
                <span class="stat-title">TRẠNG THÁI NỔI BẬT</span>
                <span class="stat-value" id="kpiTopStatus" style="font-size: 1.5rem; color: #60a5fa;">--</span>
                <span class="stat-sub" id="kpiPendingCount">Đang xử lý / chờ tiếp nhận: --</span>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="grid-2col">
            <div class="card">
                <div class="card-header">
                    <span class="card-title">📊 Phân bổ theo Trạng thái</span>
                </div>
                <div style="height: 260px; position: relative;">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title">📈 Xu hướng Yêu cầu theo Ngày</span>
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
                    <span class="card-title">🏢 Thống kê theo Phòng ban</span>
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
                            <tr><td colspan="3" style="text-align: center; color: var(--text-muted);">Đang tải...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title">📑 Thống kê theo Loại yêu cầu</span>
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
                            <tr><td colspan="2" style="text-align: center; color: var(--text-muted);">Đang tải...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Interactive Student Rating Simulator & Live Ratings List -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <span class="card-title">⭐ Đánh giá & Phản hồi từ Sinh viên</span>
                <button class="btn btn-primary" style="height: 34px; font-size: 0.8rem;" onclick="toggleRatingModal()">
                    + Gửi đánh giá thử nghiệm
                </button>
            </div>

            <!-- Form gửi đánh giá test -->
            <div id="ratingModal" style="display: none; background: rgba(15, 23, 42, 0.9); padding: 1.25rem; border-radius: 12px; margin-bottom: 1.5rem; border: 1px solid var(--primary-glow);">
                <h4 style="color: #60a5fa; margin-bottom: 0.75rem; font-size: 0.95rem;">Sinh viên gửi đánh giá cho yêu cầu đã xong (Test API: POST /api/ratings)</h4>
                <div class="grid-2col">
                    <div class="rating-form-group">
                        <label style="font-size: 0.8rem; color: var(--text-muted);">Chọn yêu cầu hỗ trợ:</label>
                        <select id="formRequestId">
                            <option value="101">YC-2026-000101 (Xin xác nhận vay vốn - Resolved - SV #12)</option>
                            <option value="103">YC-2026-000103 (Xin bảng điểm tạm thời - Resolved - SV #14)</option>
                            <option value="109">YC-2026-000109 (Tạm hoãn nghĩa vụ QS - Resolved - SV #20)</option>
                        </select>
                    </div>
                    <div class="rating-form-group">
                        <label style="font-size: 0.8rem; color: var(--text-muted);">Số sao đánh giá (1-5 ⭐):</label>
                        <select id="formRatingStar">
                            <option value="5">⭐⭐⭐⭐⭐ (5 sao - Rất hài lòng)</option>
                            <option value="4">⭐⭐⭐⭐ (4 sao - Hài lòng)</option>
                            <option value="3">⭐⭐⭐ (3 sao - Bình thường)</option>
                            <option value="2">⭐⭐ (2 sao - Chưa hài lòng)</option>
                            <option value="1">⭐ (1 sao - Rất không hài lòng)</option>
                        </select>
                    </div>
                </div>
                <div class="rating-form-group">
                    <label style="font-size: 0.8rem; color: var(--text-muted);">Ý kiến nhận xét:</label>
                    <textarea id="formRatingComment" placeholder="Nhập cảm nhận về thái độ phục vụ và tốc độ hỗ trợ..."></textarea>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 1rem;">
                    <button class="btn btn-primary" onclick="submitRating()">Gửi đánh giá</button>
                    <button class="btn" style="background: rgba(255,255,255,0.1); color: #fff;" onclick="toggleRatingModal()">Hủy</button>
                </div>
            </div>

            <!-- Bảng danh sách đánh giá -->
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
                        <tr><td colspan="6" style="text-align: center; color: var(--text-muted);">Đang tải danh sách đánh giá...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Raw API Response Viewer -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">🔍 Live API Response (API Contract Inspector)</span>
                <span id="apiEndpointLabel" style="font-size: 0.8rem; color: #38bdf8; font-family: monospace;">GET /api/reports/statistics</span>
            </div>
            <pre class="raw-json" id="rawJson">Đang chờ tải dữ liệu API...</pre>
        </div>

    </div>

    <script>
        let statusChartInstance = null;
        let timeChartInstance = null;

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

        function switchRole() {
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

            const url = `/api/reports/statistics?${params.toString()}`;
            document.getElementById('apiEndpointLabel').innerText = `GET ${url}`;

            try {
                const response = await fetch(url, { headers: getAuthHeaders() });
                const result = await response.json();
                document.getElementById('rawJson').innerText = JSON.stringify(result, null, 2);

                if (result.success && result.data) {
                    renderDashboard(result.data);
                } else {
                    alert(result.message || 'Lỗi lấy dữ liệu báo cáo');
                }
            } catch (err) {
                console.error(err);
                document.getElementById('rawJson').innerText = 'Lỗi kết nối API: ' + err.message;
            }
        }

        function renderDashboard(data) {
            // KPI
            document.getElementById('kpiTotal').innerText = data.total_requests;
            const resolvedCount = (data.by_status?.resolved || 0) + (data.by_status?.closed || 0);
            const resolvedRate = data.total_requests > 0 ? Math.round((resolvedCount / data.total_requests) * 100) : 0;
            document.getElementById('kpiResolvedRate').innerText = `Tỷ lệ hoàn thành: ${resolvedRate}% (${resolvedCount}/${data.total_requests})`;

            document.getElementById('kpiAvgHours').innerText = data.avg_processing_hours !== null ? `${data.avg_processing_hours}h` : 'N/A';

            if (data.ratings_summary) {
                document.getElementById('kpiAvgRating').innerText = `⭐ ${data.ratings_summary.average_rating || 0}`;
                document.getElementById('kpiTotalRatings').innerText = `Tổng số lượt đánh giá: ${data.ratings_summary.total_ratings || 0}`;
            }

            const pending = (data.by_status?.new || 0) + (data.by_status?.received || 0) + (data.by_status?.in_progress || 0);
            document.getElementById('kpiPendingCount').innerText = `Đang xử lý / chờ tiếp nhận: ${pending}`;
            document.getElementById('kpiTopStatus').innerText = resolvedCount >= pending ? 'Xử lý tốt (Đã giải quyết nhiều)' : 'Cần tăng tốc xử lý';

            // Charts
            renderStatusChart(data.by_status || {});
            renderTimeChart(data.requests_over_time || []);

            // Tables
            renderDeptTable(data.by_department || [], data.total_requests);
            renderTypeTable(data.by_support_type || []);
        }

        function renderStatusChart(statusData) {
            const ctx = document.getElementById('statusChart').getContext('2d');
            const labels = ['Mới (New)', 'Đã tiếp nhận', 'Đang xử lý', 'Đã giải quyết', 'Đã hủy'];
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
                        backgroundColor: ['#f59e0b', '#8b5cf6', '#3b82f6', '#10b981', '#ef4444'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'right', labels: { color: '#9ca3af', font: { family: 'Plus Jakarta Sans' } } }
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
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true,
                        tension: 0.3,
                        pointBackgroundColor: '#60a5fa',
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#9ca3af' } },
                        x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#9ca3af' } }
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
                tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; color: var(--text-muted);">Không có dữ liệu</td></tr>';
                return;
            }
            tbody.innerHTML = departments.map(d => {
                const pct = total > 0 ? Math.round((d.total / total) * 100) : 0;
                return `
                    <tr>
                        <td style="font-weight: 600; color: #fff;">${d.department_name}</td>
                        <td><span style="font-weight: 700; color: #60a5fa;">${d.total}</span> yêu cầu</td>
                        <td>${pct}%</td>
                    </tr>
                `;
            }).join('');
        }

        function renderTypeTable(types) {
            const tbody = document.getElementById('typeTableBody');
            if (!types.length) {
                tbody.innerHTML = '<tr><td colspan="2" style="text-align:center; color: var(--text-muted);">Không có dữ liệu</td></tr>';
                return;
            }
            tbody.innerHTML = types.map(t => `
                <tr>
                    <td style="font-weight: 600; color: #fff;">${t.name}</td>
                    <td><span style="font-weight: 700; color: #34d399;">${t.total}</span> yêu cầu</td>
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
                        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; color: var(--text-muted);">Chưa có đánh giá nào</td></tr>';
                        return;
                    }
                    tbody.innerHTML = ratings.map(r => `
                        <tr>
                            <td><strong style="color: #60a5fa;">#${r.request_id}</strong></td>
                            <td>Sinh viên #${r.student_id}</td>
                            <td>Phòng ban #${r.department_id || 'N/A'}</td>
                            <td><span class="star-rating">${'⭐'.repeat(r.rating)}</span></td>
                            <td style="color: #e2e8f0;">${r.comment || '<em>Không có nhận xét</em>'}</td>
                            <td style="color: var(--text-muted); font-size: 0.8rem;">${new Date(r.created_at).toLocaleString('vi-VN')}</td>
                        </tr>
                    `).join('');
                }
            } catch (err) {
                console.error(err);
            }
        }

        function toggleRatingModal() {
            const modal = document.getElementById('ratingModal');
            modal.style.display = modal.style.display === 'none' ? 'block' : 'none';
        }

        async function submitRating() {
            const reqId = document.getElementById('formRequestId').value;
            const rating = document.getElementById('formRatingStar').value;
            const comment = document.getElementById('formRatingComment').value;

            // Student headers
            const headers = {
                'X-User-Id': 12,
                'X-User-Role': 'student',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            };

            try {
                const res = await fetch('/api/ratings', {
                    method: 'POST',
                    headers: headers,
                    body: JSON.stringify({
                        request_id: parseInt(reqId),
                        rating: parseInt(rating),
                        comment: comment
                    })
                });
                const json = await res.json();
                if (json.success) {
                    alert('✅ ' + (json.message || 'Gửi đánh giá thành công!'));
                    toggleRatingModal();
                    loadDashboardData();
                    loadRatingsList();
                } else {
                    alert('❌ Lỗi: ' + (json.message || 'Không thể gửi đánh giá'));
                }
            } catch (err) {
                alert('Lỗi kết nối: ' + err.message);
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
            loadDashboardData();
            loadRatingsList();
        });
    </script>
</body>
</html>
