<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Module 5: Báo cáo Thống kê &amp; Đánh giá Hiệu suất — Hỗ trợ Sinh viên</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --bg-body: #f4f6fb;
            --bg-card: #ffffff;
            --bg-card-subtle: #f8fafc;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --text-light: #94a3b8;
            --border: #e2e8f0;
            --border-hover: #cbd5e1;
            
            --primary: #2563eb;
            --primary-light: #eff6ff;
            --primary-dark: #1d4ed8;
            
            --success: #10b981;
            --success-light: #ecfdf5;
            --success-dark: #059669;
            
            --warning: #f59e0b;
            --warning-light: #fffbeb;
            --warning-dark: #d97706;
            
            --danger: #ef4444;
            --danger-light: #fef2f2;
            --danger-dark: #dc2626;
            
            --indigo: #6366f1;
            --indigo-light: #eef2ff;
            
            --gold: #f59e0b;
            
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.07), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -4px rgba(0, 0, 0, 0.04);
            
            --radius-sm: 6px;
            --radius: 12px;
            --radius-lg: 16px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            line-height: 1.5;
        }

        h1, h2, h3, h4, .font-heading {
            font-family: 'Space Grotesk', sans-serif;
            letter-spacing: -0.02em;
        }

        /* ---------- Header / Navigation ---------- */
        header {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            padding: 0.85rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: var(--shadow-sm);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg, #2563eb, #6366f1);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.1rem;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);
        }

        .brand-text .badge-mod {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            background: var(--primary-light);
            color: var(--primary);
            padding: 2px 7px;
            border-radius: 999px;
            display: inline-block;
            margin-bottom: 2px;
        }

        .brand-text h1 {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-main);
            line-height: 1.2;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .account-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--bg-card-subtle);
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 4px 14px 4px 6px;
        }

        .avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .account-badge select {
            border: none;
            background: transparent;
            font-family: inherit;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-main);
            outline: none;
            cursor: pointer;
        }

        /* ---------- Main Container ---------- */
        .container {
            max-width: 1440px;
            margin: 0 auto;
            padding: 1.75rem 2rem 3rem;
            width: 100%;
            flex: 1;
        }

        .top-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .top-row h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .top-row p {
            font-size: 0.86rem;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .sync-info {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.82rem;
            color: var(--text-muted);
            background: #fff;
            padding: 6px 12px;
            border-radius: 20px;
            border: 1px solid var(--border);
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            background-color: var(--success);
            border-radius: 50%;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
            70% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        /* ---------- Filter Panel ---------- */
        .filter-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.75rem;
            box-shadow: var(--shadow-sm);
        }

        .presets-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 1.1rem;
            padding-bottom: 0.9rem;
            border-bottom: 1px solid var(--border);
            flex-wrap: wrap;
        }

        .presets-label {
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            margin-right: 4px;
        }

        .preset-btn {
            background: var(--bg-card-subtle);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 5px 12px;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .preset-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: var(--primary-light);
        }

        .preset-btn.active {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .filter-group label {
            font-size: 0.76rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .filter-group input, .filter-group select {
            background: var(--bg-card-subtle);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 8px 12px;
            font-size: 0.88rem;
            font-family: inherit;
            color: var(--text-main);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            width: 100%;
        }

        .filter-group input:focus, .filter-group select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
            background: #fff;
        }

        .btn-group {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 8px 16px;
            border-radius: var(--radius-sm);
            font-size: 0.86rem;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.15s ease;
            text-decoration: none;
            font-family: inherit;
            height: 38px;
            white-space: nowrap;
        }

        .btn-primary {
            background: var(--primary);
            color: #fff;
        }
        .btn-primary:hover {
            background: var(--primary-dark);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }

        .btn-outline {
            background: #fff;
            border-color: var(--border);
            color: var(--text-main);
        }
        .btn-outline:hover {
            border-color: var(--border-hover);
            background: var(--bg-card-subtle);
        }

        .btn-success {
            background: var(--success-light);
            border-color: rgba(16, 185, 129, 0.3);
            color: var(--success-dark);
        }
        .btn-success:hover {
            background: var(--success);
            color: #fff;
        }

        .btn-danger {
            background: var(--danger-light);
            border-color: rgba(239, 68, 68, 0.3);
            color: var(--danger-dark);
        }
        .btn-danger:hover {
            background: var(--danger);
            color: #fff;
        }

        /* ---------- KPI Strip Cards ---------- */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .kpi-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.35rem 1.4rem;
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .kpi-card.blue::before { background: linear-gradient(90deg, #2563eb, #3b82f6); }
        .kpi-card.green::before { background: linear-gradient(90deg, #10b981, #34d399); }
        .kpi-card.gold::before { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
        .kpi-card.red::before { background: linear-gradient(90deg, #ef4444, #f87171); }
        .kpi-card.indigo::before { background: linear-gradient(90deg, #6366f1, #818cf8); }

        .kpi-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .kpi-title {
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .kpi-icon-wrap {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .kpi-icon-wrap.blue { background: var(--primary-light); color: var(--primary); }
        .kpi-icon-wrap.green { background: var(--success-light); color: var(--success); }
        .kpi-icon-wrap.gold { background: var(--warning-light); color: var(--warning); }
        .kpi-icon-wrap.red { background: var(--danger-light); color: var(--danger); }
        .kpi-icon-wrap.indigo { background: var(--indigo-light); color: var(--indigo); }

        .kpi-val {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 2.1rem;
            font-weight: 700;
            color: var(--text-main);
            line-height: 1.15;
            margin-bottom: 6px;
        }

        .kpi-desc {
            font-size: 0.8rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
        }

        .badge-pill.success { background: var(--success-light); color: var(--success-dark); }
        .badge-pill.danger { background: var(--danger-light); color: var(--danger-dark); }
        .badge-pill.warning { background: var(--warning-light); color: var(--warning-dark); }
        .badge-pill.info { background: var(--primary-light); color: var(--primary); }

        /* ---------- Chart Grids ---------- */
        .chart-grid-2 {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .chart-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 1024px) {
            .chart-grid-2, .chart-grid-3 {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
            padding-bottom: 0.85rem;
            border-bottom: 1px solid var(--border);
        }

        .card-title-group h3 {
            font-size: 1.08rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .card-title-group p {
            font-size: 0.78rem;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .chart-container {
            position: relative;
            width: 100%;
            height: 280px;
        }

        /* ---------- Tables & Rankings ---------- */
        .tables-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.75rem;
            margin-bottom: 2rem;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
            text-align: left;
        }

        th {
            padding: 10px 14px;
            color: var(--text-muted);
            font-weight: 700;
            font-size: 0.74rem;
            text-transform: uppercase;
            border-bottom: 1px solid var(--border);
            background: var(--bg-card-subtle);
        }

        td {
            padding: 12px 14px;
            border-bottom: 1px solid var(--border);
            color: var(--text-main);
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background-color: #f8fafc;
        }

        .rank-badge {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.8rem;
        }

        .rank-1 { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .rank-2 { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .rank-3 { background: #ffedd5; color: #c2410c; border: 1px solid #fed7aa; }
        .rank-default { background: var(--bg-card-subtle); color: var(--text-muted); }

        .progress-bar-wrap {
            width: 100%;
            min-width: 90px;
            height: 7px;
            background: #e2e8f0;
            border-radius: 999px;
            overflow: hidden;
            margin-top: 4px;
        }

        .progress-bar-fill {
            height: 100%;
            border-radius: 999px;
            transition: width 0.4s ease;
        }

        .bg-blue { background: #2563eb; }
        .bg-green { background: #10b981; }
        .bg-amber { background: #f59e0b; }
        .bg-red { background: #ef4444; }

        /* ---------- SLA Overdue Alert Box ---------- */
        .alert-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: var(--radius);
            padding: 1.2rem 1.4rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }

        .alert-box-icon {
            font-size: 1.5rem;
            color: var(--danger);
        }

        .alert-box-content h4 {
            font-size: 0.96rem;
            font-weight: 700;
            color: #991b1b;
            margin-bottom: 3px;
        }

        .alert-box-content p {
            font-size: 0.82rem;
            color: #b91c1c;
        }

        /* ---------- Star Ratings ---------- */
        .star-rating {
            color: var(--gold);
            font-size: 0.95rem;
            letter-spacing: 2px;
        }

        /* ---------- Footer ---------- */
        footer {
            background: #fff;
            border-top: 1px solid var(--border);
            padding: 1.25rem 2rem;
            font-size: 0.8rem;
            color: var(--text-muted);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: auto;
        }
    </style>
</head>
<body>

    <header>
        <div class="brand">
            <div class="brand-icon">M5</div>
            <div class="brand-text">
                <span class="badge-mod">Phân hệ 5 · Báo cáo &amp; Đánh giá</span>
                <h1>Trung tâm Điều hành &amp; Thống kê Hiệu suất</h1>
            </div>
        </div>

        <div class="header-actions">
            <div class="account-badge">
                <div class="avatar" id="userAvatar">AD</div>
                <select id="roleSelector" onchange="switchRole()">
                    <option value="admin" data-id="1" data-dept="" data-name="Nguyễn Văn Quản">Quản trị viên (Toàn quyền)</option>
                    <option value="department_head" data-id="5" data-dept="3" data-name="Lê Thị Hương">Trưởng phòng CTSV</option>
                    <option value="staff" data-id="101" data-dept="1" data-name="Phạm Văn Đức">Cán bộ Phòng Đào tạo</option>
                </select>
            </div>
        </div>
    </header>

    <div class="container">

        <!-- Top Title & Sync Info -->
        <div class="top-row">
            <div>
                <h2>Báo cáo Tổng hợp &amp; Phân tích SLA</h2>
                <p>Theo dõi thời gian thực khối lượng yêu cầu, chất lượng xử lý, tỷ lệ quá hạn và mức độ hài lòng của sinh viên.</p>
            </div>
            <div class="sync-info">
                <span class="pulse-dot"></span>
                <span id="lastSyncTime">Đang đồng bộ dữ liệu...</span>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="filter-card">
            <!-- Fast Presets -->
            <div class="presets-row">
                <span class="presets-label">Khoảng thời gian:</span>
                <button class="preset-btn active" onclick="setPreset('all', this)">Tất cả</button>
                <button class="preset-btn" onclick="setPreset('today', this)">Hôm nay</button>
                <button class="preset-btn" onclick="setPreset('last7days', this)">7 ngày qua</button>
                <button class="preset-btn" onclick="setPreset('last30days', this)">30 ngày qua</button>
                <button class="preset-btn" onclick="setPreset('september', this)">Tháng 9/2026</button>
                <button class="preset-btn" onclick="setPreset('thisQuarter', this)">Quý 3/2026</button>
            </div>

            <!-- Filter Inputs -->
            <div class="filter-grid">
                <div class="filter-group">
                    <label>Từ ngày</label>
                    <input type="date" id="fromDate" value="2026-09-01">
                </div>
                <div class="filter-group">
                    <label>Đến ngày</label>
                    <input type="date" id="toDate" value="2026-09-30">
                </div>
                <div class="filter-group">
                    <label>Phòng ban</label>
                    <select id="deptFilter" onchange="loadDashboardData()">
                        <option value="">Tất cả phòng ban</option>
                        <option value="1">Phòng Đào tạo (PDT)</option>
                        <option value="2">Phòng Kế hoạch - Tài chính (PKHTC)</option>
                        <option value="3">Phòng Công tác Sinh viên (PCTSV)</option>
                        <option value="4">Phòng Quản lý Khoa học (PQLKH)</option>
                        <option value="5">Trung tâm Khảo thí (TTKT)</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Loại yêu cầu</label>
                    <select id="typeFilter" onchange="loadDashboardData()">
                        <option value="">Tất cả loại yêu cầu</option>
                        <option value="1">Cấp lại thẻ sinh viên (SLA 24h)</option>
                        <option value="2">Xin xác nhận sinh viên (SLA 48h)</option>
                        <option value="3">Đăng ký học phần bổ sung (SLA 72h)</option>
                        <option value="4">Xác nhận nộp học phí (SLA 24h)</option>
                        <option value="5">Phúc khảo bài thi (SLA 120h)</option>
                        <option value="6">Giới thiệu thực tập tốt nghiệp (SLA 48h)</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Cán bộ phụ trách</label>
                    <select id="staffFilter" onchange="loadDashboardData()">
                        <option value="">Tất cả cán bộ</option>
                        <option value="101">Phạm Văn Đức (Phòng Đào tạo)</option>
                        <option value="102">Trần Thị Mai (Phòng Đào tạo)</option>
                        <option value="103">Nguyễn Hoàng Nam (Phòng TC-KT)</option>
                        <option value="104">Lê Thị Hương (Trưởng phòng CTSV)</option>
                        <option value="105">Vũ Hải Yến (Phòng CTSV)</option>
                        <option value="106">Đặng Tiến Dũng (Phòng QLKH)</option>
                        <option value="107">Bùi Quốc Huy (TT Khảo thí)</option>
                    </select>
                </div>
                <div class="btn-group">
                    <button class="btn btn-primary" onclick="loadDashboardData()">
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        Lọc dữ liệu
                    </button>
                    <button class="btn btn-success" onclick="exportExcel()">
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Xuất Excel
                    </button>
                    <button class="btn btn-danger" onclick="exportPdf()">
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        Xuất PDF
                    </button>
                </div>
            </div>
        </div>

        <!-- KPI Cards Strip -->
        <div class="kpi-grid">
            <!-- KPI 1: Tổng số yêu cầu -->
            <div class="kpi-card blue">
                <div class="kpi-header">
                    <span class="kpi-title">Tổng số yêu cầu</span>
                    <div class="kpi-icon-wrap blue">📥</div>
                </div>
                <div class="kpi-val" id="kpiTotal">0</div>
                <div class="kpi-desc">
                    <span class="badge-pill success" id="kpiResolvedRate">0% hoàn tất</span>
                    <span>(<span id="kpiResolvedCount">0</span> đã giải quyết)</span>
                </div>
            </div>

            <!-- KPI 2: Tỉ lệ Quá hạn & Đúng hạn SLA -->
            <div class="kpi-card green" id="kpiSlaCard">
                <div class="kpi-header">
                    <span class="kpi-title">Tỉ lệ tuân thủ SLA</span>
                    <div class="kpi-icon-wrap green">⏱️</div>
                </div>
                <div class="kpi-val" id="kpiSlaCompliance">100%</div>
                <div class="kpi-desc">
                    <span class="badge-pill danger" id="kpiSlaOverdue">Quá hạn: 0% (0 YC)</span>
                    <span>Đúng hạn: <strong id="kpiSlaOnTime">0</strong></span>
                </div>
            </div>

            <!-- KPI 3: Điểm hài lòng trung bình (CSAT) -->
            <div class="kpi-card gold">
                <div class="kpi-header">
                    <span class="kpi-title">Điểm hài lòng (CSAT)</span>
                    <div class="kpi-icon-wrap gold">⭐</div>
                </div>
                <div class="kpi-val" id="kpiCsat" style="color: var(--gold);">5.0 ⭐</div>
                <div class="kpi-desc">
                    <span class="badge-pill warning" id="kpiRatingVotes">0 lượt đánh giá</span>
                    <span>Thang điểm 5.0</span>
                </div>
            </div>

            <!-- KPI 4: Thời gian xử lý trung bình -->
            <div class="kpi-card indigo">
                <div class="kpi-header">
                    <span class="kpi-title">Thời gian xử lý TB</span>
                    <div class="kpi-icon-wrap indigo">⚡</div>
                </div>
                <div class="kpi-val" id="kpiAvgHours">0h</div>
                <div class="kpi-desc">
                    <span class="badge-pill info">Chuẩn SLA</span>
                    <span>Từ lúc tạo đến khi giải quyết</span>
                </div>
            </div>

            <!-- KPI 5: Khối lượng công việc đang xử lý -->
            <div class="kpi-card red">
                <div class="kpi-header">
                    <span class="kpi-title">Đang xử lý / Tồn đọng</span>
                    <div class="kpi-icon-wrap red">🔥</div>
                </div>
                <div class="kpi-val" id="kpiActiveWorkload">0</div>
                <div class="kpi-desc">
                    <span class="badge-pill danger" id="kpiPendingStatus">Cần tập trung</span>
                    <span>Yêu cầu đang tiến hành</span>
                </div>
            </div>
        </div>

        <!-- SLA Alert banner if overdue tickets exist -->
        <div id="slaAlertBox" class="alert-box" style="display: none;">
            <div class="alert-box-icon">⚠️</div>
            <div class="alert-box-content">
                <h4>Cảnh báo vi phạm thời gian cam kết SLA</h4>
                <p id="slaAlertText">Hệ thống ghi nhận một số yêu cầu hỗ trợ đã vượt quá khung thời gian cam kết SLA định mức. Vui lòng ưu tiên xử lý ngay.</p>
            </div>
        </div>

        <!-- HÀNG BIỂU ĐỒ 1: Biểu đồ đường (Xu hướng theo thời gian) & Biểu đồ tròn (Trạng thái) -->
        <div class="chart-grid-2">
            <!-- Biểu đồ Đường -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title-group">
                        <h3>Biểu đồ đường: Xu hướng tiếp nhận yêu cầu theo thời gian</h3>
                        <p>Số lượng yêu cầu sinh viên gửi qua các ngày trong kỳ báo cáo</p>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="timeLineChart"></canvas>
                </div>
            </div>

            <!-- Biểu đồ Tròn 1: Phân bổ Trạng thái -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title-group">
                        <h3>Biểu đồ tròn: Trạng thái yêu cầu</h3>
                        <p>Phân bổ tỷ lệ theo các bước quy trình</p>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="statusDonutChart"></canvas>
                </div>
            </div>
        </div>

        <!-- HÀNG BIỂU ĐỒ 2: Biểu đồ cột (Khối lượng từng cán bộ) & Biểu đồ tròn (SLA & Đánh giá) -->
        <div class="chart-grid-3">
            <!-- Biểu đồ Cột 1: Khối lượng công việc từng cán bộ -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title-group">
                        <h3>Biểu đồ cột: Khối lượng công việc cán bộ</h3>
                        <p>Số việc đang xử lý so với đã hoàn thành</p>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="staffBarChart"></canvas>
                </div>
            </div>

            <!-- Biểu đồ Tròn 2: Tỷ lệ Đúng hạn vs Quá hạn SLA -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title-group">
                        <h3>Biểu đồ tròn: Tỷ lệ đạt SLA</h3>
                        <p>Tuân thủ cam kết thời gian định mức</p>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="slaDonutChart"></canvas>
                </div>
            </div>

            <!-- Biểu đồ Cột 2: Phân bổ Mức độ Hài lòng (Stars) -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title-group">
                        <h3>Biểu đồ cột: Phân bổ sao đánh giá</h3>
                        <p>Đánh giá chất lượng từ sinh viên (1 - 5 ⭐)</p>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="starsBarChart"></canvas>
                </div>
            </div>
        </div>

        <!-- BẢNG XẾP HẠNG CÁN BỘ & HIỆU SUẤT XỬ LÝ -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <div class="card-title-group">
                    <h3>Bảng xếp hạng Cán bộ theo Hiệu suất &amp; Mức độ Hài lòng</h3>
                    <p>Đánh giá dựa trên tỷ lệ giải quyết (40%), tuân thủ SLA (35%) và điểm hài lòng CSAT (25%)</p>
                </div>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">Hạng</th>
                            <th>Cán bộ phụ trách</th>
                            <th>Phòng ban</th>
                            <th style="text-align: center;">Đang xử lý</th>
                            <th style="text-align: center;">Đã hoàn thành</th>
                            <th style="text-align: center;">Tổng việc</th>
                            <th style="width: 140px;">Tiến độ</th>
                            <th style="text-align: center;">Đạt SLA</th>
                            <th style="text-align: center;">Điểm CSAT</th>
                            <th style="text-align: center;">Điểm Tổng</th>
                            <th style="text-align: center;">Xếp loại</th>
                        </tr>
                    </thead>
                    <tbody id="staffRankingTableBody">
                        <tr><td colspan="11" style="text-align:center; padding: 2rem; color: var(--text-muted);">Đang tải bảng xếp hạng cán bộ...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- BẢNG XẾP HẠNG PHÒNG BAN & KHỐI LƯỢNG CÔNG VIỆC -->
        <div class="chart-grid-2" style="margin-bottom: 2rem;">
            <!-- Bảng xếp hạng Phòng ban -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title-group">
                        <h3>Bảng xếp hạng Phòng ban</h3>
                        <p>So sánh năng suất và sự hài lòng giữa các đơn vị</p>
                    </div>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 40px; text-align: center;">Hạng</th>
                                <th>Phòng ban</th>
                                <th style="text-align: center;">Tiếp nhận</th>
                                <th style="text-align: center;">Đã xong</th>
                                <th style="text-align: center;">SLA</th>
                                <th style="text-align: center;">CSAT</th>
                                <th style="text-align: center;">Điểm</th>
                            </tr>
                        </thead>
                        <tbody id="deptRankingTableBody">
                            <tr><td colspan="7" style="text-align:center; padding: 1.5rem; color: var(--text-muted);">Đang tải...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Bảng Khối lượng công việc từng cán bộ (Workload Monitor) -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title-group">
                        <h3>Giám sát Khối lượng Công việc (Workload Monitor)</h3>
                        <p>Cảnh báo mức độ tải công việc để điều phối nhân sự</p>
                    </div>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Cán bộ</th>
                                <th style="text-align: center;">Đang làm</th>
                                <th style="text-align: center;">Đã xong</th>
                                <th style="text-align: center;">Tổng tải</th>
                                <th style="text-align: center;">Trạng thái tải</th>
                            </tr>
                        </thead>
                        <tbody id="workloadTableBody">
                            <tr><td colspan="5" style="text-align:center; padding: 1.5rem; color: var(--text-muted);">Đang tải...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- PHẢN HỒI VÀ ĐÁNH GIÁ TỪ SINH VIÊN (FEED) -->
        <div class="card">
            <div class="card-header">
                <div class="card-title-group">
                    <h3>Phản hồi &amp; Đánh giá từ Sinh viên</h3>
                    <p>Ý kiến đóng góp thực tế của sinh viên sau khi yêu cầu được hỗ trợ</p>
                </div>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 120px;">Mã Yêu Cầu</th>
                            <th>Sinh viên</th>
                            <th>Phòng ban</th>
                            <th>Mức đánh giá</th>
                            <th>Nhận xét chi tiết</th>
                            <th>Thời gian gửi</th>
                        </tr>
                    </thead>
                    <tbody id="ratingsFeedBody">
                        <tr><td colspan="6" style="text-align:center; padding: 2rem; color: var(--text-muted);">Đang tải danh sách phản hồi...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <footer>
        <span>Hệ thống Tiếp nhận &amp; Xử lý Yêu cầu Hỗ trợ Sinh viên · Module 5 (Report &amp; Analytics Service)</span>
        <span>Phiên bản Enterprise 2.5 · Hỗ trợ xuất Excel, PDF &amp; Báo cáo SLA</span>
    </footer>

    <!-- JAVASCRIPT LOGIC & CHARTS -->
    <script>
        // Chart instances
        let timeLineChartInstance = null;
        let statusDonutChartInstance = null;
        let staffBarChartInstance = null;
        let slaDonutChartInstance = null;
        let starsBarChartInstance = null;

        function getAuthHeaders() {
            const selector = document.getElementById('roleSelector');
            const opt = selector.options[selector.selectedIndex];
            const role = opt.value;
            const userId = opt.getAttribute('data-id') || 1;
            const deptId = opt.getAttribute('data-dept') || '';

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
            const selector = document.getElementById('roleSelector');
            const opt = selector.options[selector.selectedIndex];
            const name = opt.getAttribute('data-name') || opt.textContent;
            
            // initials
            const parts = name.trim().split(/\s+/);
            const initials = parts.length === 1 ? parts[0].slice(0, 2) : (parts[0][0] + parts[parts.length - 1][0]);
            document.getElementById('userAvatar').innerText = initials.toUpperCase();

            loadDashboardData();
            loadRatingsFeed();
        }

        function setPreset(preset, btnElement) {
            document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
            if (btnElement) btnElement.classList.add('active');

            const fromInput = document.getElementById('fromDate');
            const toInput = document.getElementById('toDate');
            const now = new Date();

            const formatDate = (d) => d.toISOString().split('T')[0];

            if (preset === 'all') {
                fromInput.value = '';
                toInput.value = '';
            } else if (preset === 'today') {
                const todayStr = formatDate(now);
                fromInput.value = todayStr;
                toInput.value = todayStr;
            } else if (preset === 'last7days') {
                const past = new Date();
                past.setDate(now.getDate() - 7);
                fromInput.value = formatDate(past);
                toInput.value = formatDate(now);
            } else if (preset === 'last30days') {
                const past = new Date();
                past.setDate(now.getDate() - 30);
                fromInput.value = formatDate(past);
                toInput.value = formatDate(now);
            } else if (preset === 'september') {
                fromInput.value = '2026-09-01';
                toInput.value = '2026-09-30';
            } else if (preset === 'thisQuarter') {
                fromInput.value = '2026-07-01';
                toInput.value = '2026-09-30';
            }

            loadDashboardData();
        }

        async function loadDashboardData() {
            const dept = document.getElementById('deptFilter').value;
            const type = document.getElementById('typeFilter').value;
            const staff = document.getElementById('staffFilter').value;
            const from = document.getElementById('fromDate').value;
            const to = document.getElementById('toDate').value;

            const params = new URLSearchParams();
            if (dept) params.append('department_id', dept);
            if (type) params.append('support_type_id', type);
            if (staff) params.append('staff_id', staff);
            if (from) params.append('from_date', from);
            if (to) params.append('to_date', to);

            try {
                const response = await fetch(`/api/reports/statistics?${params.toString()}`, {
                    headers: getAuthHeaders()
                });
                const result = await response.json();

                if (result.success && result.data) {
                    renderDashboard(result.data);
                    document.getElementById('lastSyncTime').innerText = `Đã cập nhật lúc ${new Date().toLocaleTimeString('vi-VN')}`;
                } else {
                    document.getElementById('lastSyncTime').innerText = 'Không tải được số liệu';
                    console.error('Error in response:', result);
                }
            } catch (err) {
                console.error(err);
                document.getElementById('lastSyncTime').innerText = 'Lỗi kết nối API';
            }
        }

        function renderDashboard(data) {
            // 1. KPI Cards
            const total = data.total_requests || 0;
            document.getElementById('kpiTotal').innerText = Number(total).toLocaleString('vi-VN');

            const resolvedCount = (data.by_status?.resolved || 0) + (data.by_status?.closed || 0);
            const resolvedRate = total > 0 ? Math.round((resolvedCount / total) * 100) : 0;
            document.getElementById('kpiResolvedRate').innerText = `${resolvedRate}% hoàn tất`;
            document.getElementById('kpiResolvedCount').innerText = resolvedCount;

            // SLA Metrics
            const sla = data.sla_metrics || {};
            const compliance = sla.sla_compliance_rate !== undefined ? sla.sla_compliance_rate : 100;
            const overdue = sla.sla_overdue_rate !== undefined ? sla.sla_overdue_rate : 0;
            const overdueCount = sla.overdue_count || 0;
            const onTimeCount = sla.on_time_count || 0;

            document.getElementById('kpiSlaCompliance').innerText = `${compliance}%`;
            document.getElementById('kpiSlaOverdue').innerText = `Quá hạn: ${overdue}% (${overdueCount} YC)`;
            document.getElementById('kpiSlaOnTime').innerText = `${onTimeCount} YC`;

            // SLA Alert Box
            const alertBox = document.getElementById('slaAlertBox');
            if (overdueCount > 0) {
                alertBox.style.display = 'flex';
                document.getElementById('slaAlertText').innerText = `Hệ thống ghi nhận ${overdueCount} yêu cầu (${overdue}%) đã vượt quá thời gian cam kết SLA. Vui lòng kiểm tra các cán bộ liên quan để giải quyết kịp thời.`;
            } else {
                alertBox.style.display = 'none';
            }

            // CSAT
            const csat = data.ratings_summary?.average_rating || 0;
            document.getElementById('kpiCsat').innerText = `${csat} ⭐`;
            document.getElementById('kpiRatingVotes').innerText = `${data.ratings_summary?.total_ratings || 0} lượt đánh giá`;

            // Avg hours
            document.getElementById('kpiAvgHours').innerText = data.avg_processing_hours !== null && data.avg_processing_hours !== undefined
                ? `${data.avg_processing_hours}h`
                : 'N/A';

            // Active workload
            const inProgress = (data.by_status?.in_progress || 0) + (data.by_status?.received || 0);
            const newRequests = data.by_status?.new || 0;
            const activeTotal = inProgress + newRequests;
            document.getElementById('kpiActiveWorkload').innerText = activeTotal;
            document.getElementById('kpiPendingStatus').innerText = activeTotal > 3 ? 'Tải cao' : 'Bình thường';

            // 2. Render Charts
            renderTimeLineChart(data.requests_over_time || []);
            renderStatusDonutChart(data.by_status || {});
            renderStaffBarChart(data.staff_workloads || []);
            renderSlaDonutChart(onTimeCount, overdueCount);
            renderStarsBarChart(data.ratings_summary?.by_stars || {});

            // 3. Render Tables
            renderStaffRankings(data.staff_rankings || []);
            renderDepartmentRankings(data.department_rankings || []);
            renderStaffWorkloads(data.staff_workloads || []);
        }

        /* ---------- CHARTS IMPLEMENTATION ---------- */

        // 1. Biểu đồ đường: Xu hướng theo ngày
        function renderTimeLineChart(timeData) {
            const ctx = document.getElementById('timeLineChart').getContext('2d');
            const labels = timeData.map(d => {
                const parts = d.date.split('-');
                return `${parts[2]}/${parts[1]}`;
            });
            const values = timeData.map(d => d.total);

            if (timeLineChartInstance) timeLineChartInstance.destroy();

            timeLineChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Số lượng yêu cầu',
                        data: values,
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.08)',
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.35,
                        pointBackgroundColor: '#2563eb',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' },
                            ticks: { precision: 0, font: { family: 'Plus Jakarta Sans' } }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { family: 'Plus Jakarta Sans' } }
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            padding: 10,
                            cornerRadius: 8,
                            titleFont: { family: 'Plus Jakarta Sans' },
                            bodyFont: { family: 'Plus Jakarta Sans' }
                        }
                    }
                }
            });
        }

        // 2. Biểu đồ tròn: Trạng thái
        function renderStatusDonutChart(statusData) {
            const ctx = document.getElementById('statusDonutChart').getContext('2d');
            const labels = ['Mới', 'Đã tiếp nhận', 'Đang xử lý', 'Đã giải quyết', 'Đã hủy'];
            const values = [
                statusData.new || 0,
                statusData.received || 0,
                statusData.in_progress || 0,
                statusData.resolved || 0,
                statusData.cancelled || 0
            ];

            if (statusDonutChartInstance) statusDonutChartInstance.destroy();

            statusDonutChartInstance = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: ['#f59e0b', '#3b82f6', '#6366f1', '#10b981', '#ef4444'],
                        borderWidth: 3,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                padding: 12,
                                font: { size: 11, family: 'Plus Jakarta Sans' }
                            }
                        }
                    }
                }
            });
        }

        // 3. Biểu đồ cột: Khối lượng công việc cán bộ
        function renderStaffBarChart(workloads) {
            const ctx = document.getElementById('staffBarChart').getContext('2d');
            const labels = workloads.map(w => w.staff_name);
            const inProgressData = workloads.map(w => w.in_progress);
            const resolvedData = workloads.map(w => w.resolved);

            if (staffBarChartInstance) staffBarChartInstance.destroy();

            staffBarChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Đang xử lý',
                            data: inProgressData,
                            backgroundColor: '#f59e0b',
                            borderRadius: 4
                        },
                        {
                            label: 'Đã giải quyết',
                            data: resolvedData,
                            backgroundColor: '#10b981',
                            borderRadius: 4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            stacked: true,
                            grid: { display: false },
                            ticks: {
                                font: { size: 10, family: 'Plus Jakarta Sans' },
                                maxRotation: 25
                            }
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            ticks: { precision: 0 },
                            grid: { color: '#f1f5f9' }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 12, font: { size: 11 } }
                        }
                    }
                }
            });
        }

        // 4. Biểu đồ tròn: Đạt SLA vs Quá hạn
        function renderSlaDonutChart(onTime, overdue) {
            const ctx = document.getElementById('slaDonutChart').getContext('2d');

            if (slaDonutChartInstance) slaDonutChartInstance.destroy();

            slaDonutChartInstance = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Đúng hạn SLA', 'Quá hạn SLA'],
                    datasets: [{
                        data: [onTime, overdue],
                        backgroundColor: ['#10b981', '#ef4444'],
                        borderWidth: 3,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 12, padding: 12, font: { size: 11 } }
                        }
                    }
                }
            });
        }

        // 5. Biểu đồ cột: Phân bổ sao đánh giá
        function renderStarsBarChart(byStars) {
            const ctx = document.getElementById('starsBarChart').getContext('2d');
            const labels = ['1 ⭐', '2 ⭐', '3 ⭐', '4 ⭐', '5 ⭐'];
            const values = [
                byStars[1] || 0,
                byStars[2] || 0,
                byStars[3] || 0,
                byStars[4] || 0,
                byStars[5] || 0
            ];

            if (starsBarChartInstance) starsBarChartInstance.destroy();

            starsBarChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Số lượt đánh giá',
                        data: values,
                        backgroundColor: ['#ef4444', '#f97316', '#f59e0b', '#3b82f6', '#10b981'],
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { grid: { display: false } },
                        y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f1f5f9' } }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }

        /* ---------- TABLES IMPLEMENTATION ---------- */

        function renderStaffRankings(rankings) {
            const tbody = document.getElementById('staffRankingTableBody');
            if (!rankings.length) {
                tbody.innerHTML = '<tr><td colspan="11" style="text-align:center; padding: 2rem;">Không có dữ liệu cán bộ.</td></tr>';
                return;
            }

            tbody.innerHTML = rankings.map(s => {
                const rankClass = s.rank === 1 ? 'rank-1' : (s.rank === 2 ? 'rank-2' : (s.rank === 3 ? 'rank-3' : 'rank-default'));
                const medal = s.rank === 1 ? '🥇' : (s.rank === 2 ? '🥈' : (s.rank === 3 ? '🥉' : s.rank));
                
                const tierBadge = s.tier === 'Xuất sắc' ? 'badge-pill success' :
                                 (s.tier === 'Tốt' ? 'badge-pill info' :
                                 (s.tier === 'Đạt' ? 'badge-pill warning' : 'badge-pill danger'));

                const inProg = s.total_assigned - s.resolved_count;

                return `
                    <tr>
                        <td style="text-align: center;">
                            <span class="rank-badge ${rankClass}">${medal}</span>
                        </td>
                        <td>
                            <strong>${s.staff_name}</strong>
                        </td>
                        <td style="color: var(--text-muted); font-size: 0.82rem;">${s.department_name}</td>
                        <td style="text-align: center; color: var(--warning-dark); font-weight: 600;">${inProg}</td>
                        <td style="text-align: center; color: var(--success-dark); font-weight: 600;">${s.resolved_count}</td>
                        <td style="text-align: center; font-weight: 700;">${s.total_assigned}</td>
                        <td>
                            <div style="font-size: 0.76rem; font-weight: 600; color: var(--text-muted);">${s.completion_rate}%</div>
                            <div class="progress-bar-wrap">
                                <div class="progress-bar-fill bg-blue" style="width: ${s.completion_rate}%"></div>
                            </div>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge-pill ${s.sla_rate >= 90 ? 'success' : (s.sla_rate >= 75 ? 'info' : 'warning')}">
                                ${s.sla_rate}%
                            </span>
                        </td>
                        <td style="text-align: center; font-weight: 700; color: var(--gold);">
                            ${s.csat} ⭐
                        </td>
                        <td style="text-align: center; font-family: 'Space Grotesk', sans-serif; font-weight: 700; font-size: 1rem; color: var(--primary);">
                            ${s.performance_score}
                        </td>
                        <td style="text-align: center;">
                            <span class="${tierBadge}">${s.tier}</span>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function renderDepartmentRankings(rankings) {
            const tbody = document.getElementById('deptRankingTableBody');
            if (!rankings.length) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding: 1.5rem;">Không có dữ liệu</td></tr>';
                return;
            }

            tbody.innerHTML = rankings.map(d => {
                const rankClass = d.rank === 1 ? 'rank-1' : (d.rank === 2 ? 'rank-2' : (d.rank === 3 ? 'rank-3' : 'rank-default'));
                const medal = d.rank === 1 ? '🥇' : (d.rank === 2 ? '🥈' : (d.rank === 3 ? '🥉' : d.rank));

                return `
                    <tr>
                        <td style="text-align: center;">
                            <span class="rank-badge ${rankClass}">${medal}</span>
                        </td>
                        <td><strong>${d.department_name}</strong></td>
                        <td style="text-align: center;">${d.total_assigned}</td>
                        <td style="text-align: center; color: var(--success); font-weight: 600;">${d.resolved_count}</td>
                        <td style="text-align: center;"><span class="badge-pill ${d.sla_rate >= 85 ? 'success' : 'warning'}">${d.sla_rate}%</span></td>
                        <td style="text-align: center; color: var(--gold); font-weight: 600;">${d.csat} ⭐</td>
                        <td style="text-align: center; font-weight: 700; color: var(--primary);">${d.performance_score}</td>
                    </tr>
                `;
            }).join('');
        }

        function renderStaffWorkloads(workloads) {
            const tbody = document.getElementById('workloadTableBody');
            if (!workloads.length) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 1.5rem;">Không có dữ liệu</td></tr>';
                return;
            }

            tbody.innerHTML = workloads.map(w => {
                const statusBadge = w.workload_status === 'Quá tải' ? 'badge-pill danger' :
                                   (w.workload_status === 'Cao' ? 'badge-pill warning' :
                                   (w.workload_status === 'Bình thường' ? 'badge-pill success' : 'badge-pill info'));

                return `
                    <tr>
                        <td>
                            <strong>${w.staff_name}</strong>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">${w.department_name}</div>
                        </td>
                        <td style="text-align: center; font-weight: 700; color: var(--warning-dark);">${w.in_progress}</td>
                        <td style="text-align: center; color: var(--success-dark);">${w.resolved}</td>
                        <td style="text-align: center; font-weight: 700;">${w.total}</td>
                        <td style="text-align: center;"><span class="${statusBadge}">${w.workload_status}</span></td>
                    </tr>
                `;
            }).join('');
        }

        async function loadRatingsFeed() {
            try {
                const res = await fetch('/api/ratings', { headers: getAuthHeaders() });
                const json = await res.json();
                const tbody = document.getElementById('ratingsFeedBody');

                if (json.success && json.data && json.data.data) {
                    const ratings = json.data.data;
                    if (!ratings.length) {
                        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding: 2rem;">Chưa có phản hồi nào.</td></tr>';
                        return;
                    }
                    tbody.innerHTML = ratings.map(r => `
                        <tr>
                            <td><strong style="color: var(--primary);">#${r.request_id}</strong></td>
                            <td>Sinh viên #${r.student_id}</td>
                            <td>Phòng ban #${r.department_id || 'N/A'}</td>
                            <td><span class="star-rating">${'⭐'.repeat(r.rating)}</span></td>
                            <td>${r.comment || '<span style="color: var(--text-light); font-style: italic;">Không có nhận xét</span>'}</td>
                            <td style="color: var(--text-muted); font-size: 0.8rem;">${new Date(r.created_at).toLocaleString('vi-VN')}</td>
                        </tr>
                    `).join('');
                }
            } catch (err) {
                console.error(err);
            }
        }

        function exportExcel() {
            const dept = document.getElementById('deptFilter').value;
            const type = document.getElementById('typeFilter').value;
            const staff = document.getElementById('staffFilter').value;
            const from = document.getElementById('fromDate').value;
            const to = document.getElementById('toDate').value;

            const params = new URLSearchParams();
            if (dept) params.append('department_id', dept);
            if (type) params.append('support_type_id', type);
            if (staff) params.append('staff_id', staff);
            if (from) params.append('from_date', from);
            if (to) params.append('to_date', to);

            window.location.href = `/api/reports/export?${params.toString()}`;
        }

        function exportPdf() {
            const dept = document.getElementById('deptFilter').value;
            const type = document.getElementById('typeFilter').value;
            const staff = document.getElementById('staffFilter').value;
            const from = document.getElementById('fromDate').value;
            const to = document.getElementById('toDate').value;

            const params = new URLSearchParams();
            if (dept) params.append('department_id', dept);
            if (type) params.append('support_type_id', type);
            if (staff) params.append('staff_id', staff);
            if (from) params.append('from_date', from);
            if (to) params.append('to_date', to);

            window.open(`/reports/export-pdf?${params.toString()}`, '_blank');
        }

        // Init
        window.addEventListener('DOMContentLoaded', () => {
            switchRole();
        });
    </script>
</body>
</html>