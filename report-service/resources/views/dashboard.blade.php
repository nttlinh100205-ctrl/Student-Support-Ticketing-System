<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống Báo cáo &amp; Thống kê Hỗ trợ Sinh viên</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            /* Bright, Clean & Modern University Palette */
            --bg-body: #f8fafc;
            --bg-card: #ffffff;
            --bg-card-subtle: #f1f5f9;
            --bg-card-hover: #f8fafc;
            
            --text-main: #0f172a;
            --text-muted: #475569;
            --text-light: #94a3b8;
            
            --border: #e2e8f0;
            --border-subtle: #f1f5f9;
            --border-focus: #3b82f6;
            
            --primary: #2563eb;
            --primary-gradient: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
            --primary-light: #eff6ff;
            --primary-dark: #1d4ed8;
            
            --success: #059669;
            --success-light: #ecfdf5;
            --success-gradient: linear-gradient(135deg, #059669 0%, #10b981 100%);
            
            --warning: #d97706;
            --warning-light: #fffbeb;
            --warning-gradient: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);
            
            --danger: #dc2626;
            --danger-light: #fef2f2;
            --danger-gradient: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
            
            --indigo: #4f46e5;
            --indigo-light: #eef2ff;
            --indigo-gradient: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            
            --cyan: #0284c7;
            --cyan-light: #f0f9ff;
            
            --gold: #f59e0b;
            
            --shadow-xs: 0 1px 2px 0 rgba(15, 23, 42, 0.04);
            --shadow-sm: 0 2px 4px 0 rgba(15, 23, 42, 0.05);
            --shadow-md: 0 4px 12px -1px rgba(15, 23, 42, 0.07), 0 2px 4px -2px rgba(15, 23, 42, 0.04);
            --shadow-lg: 0 10px 25px -3px rgba(15, 23, 42, 0.08), 0 4px 6px -4px rgba(15, 23, 42, 0.04);
            
            --radius-sm: 8px;
            --radius: 14px;
            --radius-lg: 20px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-body);
            background-image: radial-gradient(at 0% 0%, rgba(239, 246, 255, 0.7) 0px, transparent 50%),
                              radial-gradient(at 100% 0%, rgba(236, 253, 245, 0.6) 0px, transparent 50%);
            background-attachment: fixed;
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        h1, h2, h3, h4, .font-heading {
            font-family: 'Space Grotesk', sans-serif;
            letter-spacing: -0.02em;
        }

        /* ---------- Masthead / Navigation ---------- */
        header {
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border);
            padding: 0.9rem 2.25rem;
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
            gap: 14px;
        }

        .brand-logo {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: var(--primary-gradient);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.25rem;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3);
            flex-shrink: 0;
        }

        .brand-text .sub-title {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--primary);
            background: var(--primary-light);
            padding: 2px 8px;
            border-radius: 999px;
            display: inline-block;
            margin-bottom: 3px;
        }

        .brand-text h1 {
            font-size: 1.18rem;
            font-weight: 700;
            color: var(--text-main);
            line-height: 1.2;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .account-chip {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 5px 16px 5px 6px;
            box-shadow: var(--shadow-xs);
            transition: all 0.2s ease;
        }

        .account-chip:hover {
            border-color: var(--primary);
            box-shadow: var(--shadow-sm);
        }

        .avatar-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--indigo-gradient);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .account-chip select {
            border: none;
            background: transparent;
            font-family: inherit;
            font-size: 0.86rem;
            font-weight: 600;
            color: var(--text-main);
            outline: none;
            cursor: pointer;
        }

        /* ---------- Main Container ---------- */
        .container {
            max-width: 1440px;
            margin: 0 auto;
            padding: 2rem 2.25rem 3.5rem;
            width: 100%;
            flex: 1;
        }

        .page-intro {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 1.75rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-intro h2 {
            font-size: 1.65rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .page-intro p {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-top: 3px;
        }

        .sync-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text-muted);
            background: #ffffff;
            padding: 7px 14px;
            border-radius: 999px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-xs);
        }

        .live-dot {
            width: 8px;
            height: 8px;
            background-color: var(--success);
            border-radius: 50%;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.2);
            animation: pulseDot 2s infinite ease-in-out;
        }

        @keyframes pulseDot {
            0% { box-shadow: 0 0 0 0 rgba(5, 150, 105, 0.5); }
            70% { box-shadow: 0 0 0 6px rgba(5, 150, 105, 0); }
            100% { box-shadow: 0 0 0 0 rgba(5, 150, 105, 0); }
        }

        /* ---------- Filter Panel (Bright Card) ---------- */
        .filter-panel {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.4rem 1.6rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-md);
        }

        .presets-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 1.25rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
            flex-wrap: wrap;
        }

        .presets-label {
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-right: 6px;
        }

        .preset-btn {
            background: var(--bg-card-subtle);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 6px 14px;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        .preset-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: var(--primary-light);
            transform: translateY(-1px);
        }

        .preset-btn.active {
            background: var(--primary-gradient);
            color: #ffffff;
            border-color: transparent;
            box-shadow: 0 3px 8px rgba(37, 99, 235, 0.28);
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1.1rem;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .filter-group label {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .filter-group input, .filter-group select {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 9px 12px;
            font-size: 0.88rem;
            font-family: inherit;
            color: var(--text-main);
            outline: none;
            transition: all 0.2s ease;
            width: 100%;
        }

        .filter-group input:focus, .filter-group select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .btn-group {
            display: flex;
            gap: 9px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 9px 18px;
            border-radius: var(--radius-sm);
            font-size: 0.86rem;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.2s ease;
            text-decoration: none;
            font-family: inherit;
            height: 40px;
            white-space: nowrap;
        }

        .btn-primary {
            background: var(--primary-gradient);
            color: #ffffff;
            box-shadow: 0 3px 10px rgba(37, 99, 235, 0.25);
        }
        .btn-primary:hover {
            background: var(--primary-dark);
            box-shadow: 0 5px 15px rgba(37, 99, 235, 0.35);
            transform: translateY(-1px);
        }

        .btn-excel {
            background: #ffffff;
            border: 1px solid #10b981;
            color: #059669;
        }
        .btn-excel:hover {
            background: var(--success-gradient);
            color: #ffffff;
            border-color: transparent;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
            transform: translateY(-1px);
        }

        .btn-pdf {
            background: #ffffff;
            border: 1px solid #ef4444;
            color: #dc2626;
        }
        .btn-pdf:hover {
            background: var(--danger-gradient);
            color: #ffffff;
            border-color: transparent;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
            transform: translateY(-1px);
        }

        /* ---------- KPI Summary Cards (Bright & Elevated) ---------- */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.35rem;
            margin-bottom: 2.25rem;
        }

        .kpi-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.4rem 1.5rem;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .kpi-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .kpi-card.blue::before { background: var(--primary-gradient); }
        .kpi-card.green::before { background: var(--success-gradient); }
        .kpi-card.gold::before { background: var(--warning-gradient); }
        .kpi-card.indigo::before { background: var(--indigo-gradient); }
        .kpi-card.red::before { background: var(--danger-gradient); }

        .kpi-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .kpi-title {
            font-size: 0.76rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .kpi-icon-badge {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .kpi-icon-badge.blue { background: var(--primary-light); color: var(--primary); }
        .kpi-icon-badge.green { background: var(--success-light); color: var(--success); }
        .kpi-icon-badge.gold { background: var(--warning-light); color: var(--warning); }
        .kpi-icon-badge.indigo { background: var(--indigo-light); color: var(--indigo); }
        .kpi-icon-badge.red { background: var(--danger-light); color: var(--danger); }

        .kpi-val {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--text-main);
            line-height: 1.15;
            margin-bottom: 8px;
        }

        .kpi-footer {
            font-size: 0.82rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 9px;
            border-radius: 999px;
            font-size: 0.74rem;
            font-weight: 700;
        }

        .badge-pill.success { background: var(--success-light); color: var(--success); }
        .badge-pill.danger { background: var(--danger-light); color: var(--danger); }
        .badge-pill.warning { background: var(--warning-light); color: var(--warning); }
        .badge-pill.info { background: var(--primary-light); color: var(--primary); }

        /* ---------- SLA Warning Banner ---------- */
        .sla-alert-banner {
            background: linear-gradient(90deg, #fef2f2 0%, #fff1f2 100%);
            border: 1px solid #fecaca;
            border-radius: var(--radius);
            padding: 1.25rem 1.5rem;
            margin-bottom: 2.25rem;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: var(--shadow-sm);
        }

        .sla-alert-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #fee2e2;
            color: var(--danger);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .sla-alert-content h4 {
            font-size: 0.98rem;
            font-weight: 700;
            color: #991b1b;
            margin-bottom: 2px;
        }

        .sla-alert-content p {
            font-size: 0.84rem;
            color: #b91c1c;
        }

        /* ---------- Card & Chart Containers ---------- */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.6rem;
            box-shadow: var(--shadow-md);
            margin-bottom: 2rem;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.35rem;
            padding-bottom: 0.9rem;
            border-bottom: 1px solid var(--border);
        }

        .card-title-group h3 {
            font-size: 1.12rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .card-title-group p {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .chart-grid-2 {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.6rem;
            margin-bottom: 2rem;
        }

        .chart-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.6rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 1024px) {
            .chart-grid-2, .chart-grid-3 {
                grid-template-columns: 1fr;
            }
        }

        .chart-wrap {
            position: relative;
            width: 100%;
            height: 290px;
        }

        /* ---------- Tables & Rankings ---------- */
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
            padding: 12px 16px;
            color: var(--text-muted);
            font-weight: 700;
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: 1px solid var(--border);
            background: var(--bg-card-subtle);
        }

        td {
            padding: 13px 16px;
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
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.82rem;
        }

        .rank-1 { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .rank-2 { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .rank-3 { background: #ffedd5; color: #c2410c; border: 1px solid #fed7aa; }
        .rank-default { background: var(--bg-card-subtle); color: var(--text-muted); }

        .progress-track {
            width: 100%;
            min-width: 90px;
            height: 8px;
            background: #e2e8f0;
            border-radius: 999px;
            overflow: hidden;
            margin-top: 4px;
        }

        .progress-fill {
            height: 100%;
            border-radius: 999px;
            background: var(--primary-gradient);
            transition: width 0.5s ease;
        }

        .star-stars {
            color: var(--gold);
            font-size: 0.95rem;
            letter-spacing: 1px;
        }

        /* ---------- Footer ---------- */
        footer {
            background: #ffffff;
            border-top: 1px solid var(--border);
            padding: 1.4rem 2.25rem;
            font-size: 0.82rem;
            color: var(--text-muted);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: auto;
        }
    </style>
<link rel="stylesheet" href="/css/suite.css">
</head>
<body class="suite-ui report-ui">
@include('partials.account')



    <div class="container">

        <!-- Page Intro & Live Sync -->
        <div class="page-intro">
            <div>
                <h2>Báo cáo Toàn diện &amp; Đánh giá Mức độ Hài lòng</h2>
                <p>Theo dõi thời gian thực lưu lượng yêu cầu sinh viên, tỷ lệ tuân thủ SLA, chất lượng phục vụ và khối lượng xử lý của từng cán bộ.</p>
            </div>
            <div class="sync-badge">
                <span class="live-dot"></span>
                <span id="lastSyncTime">Đang tải dữ liệu báo cáo...</span>
            </div>
        </div>

        <!-- Filter Controls (Bright Panel) -->
        <div class="filter-panel">
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
                    <label>Phòng ban hỗ trợ</label>
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
                    <label>Dịch vụ sinh viên</label>
                    <select id="typeFilter" onchange="loadDashboardData()">
                        <option value="">Tất cả dịch vụ</option>
                        <option value="1">Cấp lại thẻ sinh viên (SLA 24h)</option>
                        <option value="2">Xin xác nhận sinh viên (SLA 48h)</option>
                        <option value="3">Đăng ký học phần bổ sung (SLA 72h)</option>
                        <option value="4">Xác nhận nộp học phí (SLA 24h)</option>
                        <option value="5">Phúc khảo bài thi (SLA 120h)</option>
                        <option value="6">Giới thiệu thực tập tốt nghiệp (SLA 48h)</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Cán bộ tiếp nhận</label>
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
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        Lọc số liệu
                    </button>
                    <button class="btn btn-excel" onclick="exportExcel()">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Xuất Excel
                    </button>
                    <button class="btn btn-pdf" onclick="exportPdf()">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        Xuất PDF
                    </button>
                </div>
            </div>
        </div>

        <!-- KPI Metrics Strip -->
        <div class="kpi-grid">
            <!-- KPI 1: Tổng số yêu cầu sinh viên -->
            <div class="kpi-card blue">
                <div class="kpi-header">
                    <span class="kpi-title">Yêu cầu tiếp nhận</span>
                    <div class="kpi-icon-badge blue">📋</div>
                </div>
                <div class="kpi-val" id="kpiTotal">0</div>
                <div class="kpi-footer">
                    <span class="badge-pill success" id="kpiResolvedRate">0% hoàn tất</span>
                    <span>(<strong id="kpiResolvedCount">0</strong> đã giải quyết)</span>
                </div>
            </div>

            <!-- KPI 2: Tỉ lệ đạt SLA -->
            <div class="kpi-card green" id="kpiSlaCard">
                <div class="kpi-header">
                    <span class="kpi-title">Tỷ lệ đúng hạn SLA</span>
                    <div class="kpi-icon-badge green">⏱️</div>
                </div>
                <div class="kpi-val" id="kpiSlaCompliance">100%</div>
                <div class="kpi-footer">
                    <span class="badge-pill danger" id="kpiSlaOverdue">Quá hạn: 0%</span>
                    <span>Đúng hạn: <strong id="kpiSlaOnTime">0 YC</strong></span>
                </div>
            </div>

            <!-- KPI 3: Điểm hài lòng CSAT -->
            <div class="kpi-card gold">
                <div class="kpi-header">
                    <span class="kpi-title">Hài lòng sinh viên (CSAT)</span>
                    <div class="kpi-icon-badge gold">⭐</div>
                </div>
                <div class="kpi-val" id="kpiCsat" style="color: var(--gold);">5.0 ⭐</div>
                <div class="kpi-footer">
                    <span class="badge-pill warning" id="kpiRatingVotes">0 đánh giá</span>
                    <span>Thang điểm 5.0</span>
                </div>
            </div>

            <!-- KPI 4: Thời gian xử lý trung bình -->
            <div class="kpi-card indigo">
                <div class="kpi-header">
                    <span class="kpi-title">Thời gian xử lý TB</span>
                    <div class="kpi-icon-badge indigo">⚡</div>
                </div>
                <div class="kpi-val" id="kpiAvgHours">0h</div>
                <div class="kpi-footer">
                    <span class="badge-pill info">Chuẩn cam kết</span>
                    <span>Từ lúc tiếp nhận đến xong</span>
                </div>
            </div>

            <!-- KPI 5: Hồ sơ đang xử lý -->
            <div class="kpi-card red">
                <div class="kpi-header">
                    <span class="kpi-title">Hồ sơ đang xử lý</span>
                    <div class="kpi-icon-badge red">📁</div>
                </div>
                <div class="kpi-val" id="kpiActiveWorkload">0</div>
                <div class="kpi-footer">
                    <span class="badge-pill danger" id="kpiPendingStatus">Bình thường</span>
                    <span>Hồ sơ đang tiến hành</span>
                </div>
            </div>
        </div>

        <!-- SLA Warning Banner (Hiển thị khi có yêu cầu quá hạn) -->
        <div id="slaAlertBanner" class="sla-alert-banner" style="display: none;">
            <div class="sla-alert-icon">⚠️</div>
            <div class="sla-alert-content">
                <h4>Cảnh báo vi phạm thời gian cam kết chuẩn SLA</h4>
                <p id="slaAlertText">Hệ thống ghi nhận có yêu cầu hỗ trợ sinh viên bị quá thời gian cam kết. Đề nghị các cán bộ phụ trách ưu tiên xử lý ngay.</p>
            </div>
        </div>

        <!-- HÀNG BIỂU ĐỒ 1: Biểu đồ đường (Xu hướng) & Biểu đồ tròn (Trạng thái) -->
        <div class="chart-grid-2">
            <!-- Biểu đồ Đường: Xu hướng -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title-group">
                        <h3>Biểu đồ đường: Xu hướng tiếp nhận yêu cầu sinh viên</h3>
                        <p>Biến động số lượng sinh viên nộp hồ sơ qua các ngày trong kỳ</p>
                    </div>
                </div>
                <div class="chart-wrap">
                    <canvas id="timeLineChart"></canvas>
                </div>
            </div>

            <!-- Biểu đồ Tròn: Trạng thái yêu cầu -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title-group">
                        <h3>Biểu đồ tròn: Trạng thái hồ sơ</h3>
                        <p>Tỷ lệ phân bổ theo tiến trình xử lý dịch vụ</p>
                    </div>
                </div>
                <div class="chart-wrap">
                    <canvas id="statusDonutChart"></canvas>
                </div>
            </div>
        </div>

        <!-- HÀNG BIỂU ĐỒ 2: Biểu đồ cột (Khối lượng cán bộ) & Biểu đồ tròn (SLA & Điểm sao) -->
        <div class="chart-grid-3">
            <!-- Biểu đồ Cột: Khối lượng cán bộ -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title-group">
                        <h3>Biểu đồ cột: Khối lượng công việc cán bộ</h3>
                        <p>Hồ sơ đang xử lý so với đã hoàn thành</p>
                    </div>
                </div>
                <div class="chart-wrap">
                    <canvas id="staffBarChart"></canvas>
                </div>
            </div>

            <!-- Biểu đồ Tròn: Tỷ lệ SLA -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title-group">
                        <h3>Biểu đồ tròn: Tỷ lệ tuân thủ SLA</h3>
                        <p>Cam kết giải quyết đúng thời hạn</p>
                    </div>
                </div>
                <div class="chart-wrap">
                    <canvas id="slaDonutChart"></canvas>
                </div>
            </div>

            <!-- Biểu đồ Cột: Phân bổ sao đánh giá -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title-group">
                        <h3>Biểu đồ cột: Phân bổ sao đánh giá</h3>
                        <p>Khảo sát mức độ hài lòng của sinh viên</p>
                    </div>
                </div>
                <div class="chart-wrap">
                    <canvas id="starsBarChart"></canvas>
                </div>
            </div>
        </div>

        <!-- BẢNG XẾP HẠNG CÁN BỘ TIẾP DÂN & HỖ TRỢ SINH VIÊN -->
        <div class="card">
            <div class="card-header">
                <div class="card-title-group">
                    <h3>Bảng xếp hạng Cán bộ theo Hiệu suất &amp; Hài lòng sinh viên</h3>
                    <p>Đánh giá tổng hợp: Tỷ lệ giải quyết (40%), Chuẩn SLA (35%), Điểm hài lòng CSAT (25%)</p>
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
                            <th style="width: 130px;">Tiến độ</th>
                            <th style="text-align: center;">Chuẩn SLA</th>
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

        <!-- BẢNG XẾP HẠNG PHÒNG BAN & GIÁM SÁT TẢI CÔNG VIỆC -->
        <div class="chart-grid-2">
            <!-- Xếp hạng Phòng ban -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title-group">
                        <h3>Bảng xếp hạng Phòng ban hỗ trợ</h3>
                        <p>So sánh năng suất và chất lượng dịch vụ giữa các đơn vị</p>
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

            <!-- Giám sát tải công việc (Workload Monitor) -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title-group">
                        <h3>Giám sát Khối lượng Công việc (Workload Monitor)</h3>
                        <p>Theo dõi để điều phối cán bộ tránh ùn tắc hồ sơ</p>
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

        <!-- PHẢN HỒI & NHẬN XÉT CỦA SINH VIÊN -->
        <div class="card">
            <div class="card-header">
                <div class="card-title-group">
                    <h3>Phản hồi &amp; Ý kiến Đóng góp từ Sinh viên</h3>
                    <p>Khảo sát ý kiến sinh viên gửi sau khi nhận kết quả hỗ trợ</p>
                </div>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 130px;">Mã Yêu Cầu</th>
                            <th>Sinh viên</th>
                            <th>Phòng ban tiếp nhận</th>
                            <th>Mức đánh giá</th>
                            <th>Nội dung nhận xét</th>
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

    <!-- Footer -->
    <footer>
        <div>
            <strong>Hệ thống Tiếp nhận &amp; Xử lý Yêu cầu Hỗ trợ Sinh viên</strong> · Phân hệ 5: Báo cáo &amp; Đánh giá
        </div>
        <div>
            <span>Giao diện Bright &amp; Modern · Phiên bản 2.5</span>
        </div>
    </footer>

    <!-- JavaScript & Chart.js Logic -->
    <script>
        let timeLineChartInstance = null;
        let statusDonutChartInstance = null;
        let staffBarChartInstance = null;
        let slaDonutChartInstance = null;
        let starsBarChartInstance = null;

        function getAuthHeaders() { return window.AccountHeaders(); }

        function initializeDashboard() {
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
                    document.getElementById('lastSyncTime').innerText = `Đã đồng bộ lúc ${new Date().toLocaleTimeString('vi-VN')}`;
                } else {
                    document.getElementById('lastSyncTime').innerText = 'Không tải được số liệu';
                }
            } catch (err) {
                console.error(err);
                document.getElementById('lastSyncTime').innerText = 'Lỗi kết nối máy chủ';
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
            document.getElementById('kpiSlaOverdue').innerText = `Quá hạn: ${overdue}%`;
            document.getElementById('kpiSlaOnTime').innerText = `${onTimeCount} YC`;

            // SLA Banner
            const alertBanner = document.getElementById('slaAlertBanner');
            if (overdueCount > 0) {
                alertBanner.style.display = 'flex';
                document.getElementById('slaAlertText').innerText = `Hệ thống ghi nhận ${overdueCount} yêu cầu (${overdue}%) đã vượt quá thời gian cam kết SLA. Đề nghị các cán bộ phụ trách ưu tiên xử lý ngay.`;
            } else {
                alertBanner.style.display = 'none';
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
            const inProgress = (data.by_status?.in_progress || 0) + (data.by_status?.received || 0) + (data.by_status?.waiting_info || 0);
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

        // Biểu đồ đường (Line chart)
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

        // Biểu đồ tròn trạng thái (Doughnut chart)
        function renderStatusDonutChart(statusData) {
            const ctx = document.getElementById('statusDonutChart').getContext('2d');
            const labels = ['Mới', 'Đã tiếp nhận', 'Đang xử lý', 'Chờ bổ sung', 'Đã giải quyết', 'Đã đóng', 'Đã hủy'];
            const values = [
                statusData.new || 0,
                statusData.received || 0,
                statusData.in_progress || 0,
                statusData.waiting_info || 0,
                statusData.resolved || 0,
                statusData.closed || 0,
                statusData.cancelled || 0
            ];

            if (statusDonutChartInstance) statusDonutChartInstance.destroy();

            statusDonutChartInstance = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: ['#f59e0b', '#3b82f6', '#6366f1', '#a855f7', '#10b981', '#64748b', '#ef4444'],
                        borderWidth: 3,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
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

        // Biểu đồ cột khối lượng cán bộ (Bar chart)
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

        // Biểu đồ tròn SLA (Doughnut chart)
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
                    cutout: '70%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 12, padding: 12, font: { size: 11 } }
                        }
                    }
                }
            });
        }

        // Biểu đồ cột phân bổ sao đánh giá (Bar chart)
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
                        label: 'Lượt đánh giá',
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

        /* ---------- TABLES ---------- */

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
                        <td style="text-align: center; color: var(--warning); font-weight: 700;">${inProg}</td>
                        <td style="text-align: center; color: var(--success); font-weight: 700;">${s.resolved_count}</td>
                        <td style="text-align: center; font-weight: 700;">${s.total_assigned}</td>
                        <td>
                            <div style="font-size: 0.76rem; font-weight: 700; color: var(--text-muted);">${s.completion_rate}%</div>
                            <div class="progress-track">
                                <div class="progress-fill" style="width: ${s.completion_rate}%"></div>
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
                        <td style="text-align: center; font-family: 'Space Grotesk', sans-serif; font-weight: 700; font-size: 1.05rem; color: var(--primary);">
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
                        <td style="text-align: center; color: var(--success); font-weight: 700;">${d.resolved_count}</td>
                        <td style="text-align: center;"><span class="badge-pill ${d.sla_rate >= 85 ? 'success' : 'warning'}">${d.sla_rate}%</span></td>
                        <td style="text-align: center; color: var(--gold); font-weight: 700;">${d.csat} ⭐</td>
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
                        <td style="text-align: center; font-weight: 700; color: var(--warning);">${w.in_progress}</td>
                        <td style="text-align: center; color: var(--success); font-weight: 700;">${w.resolved}</td>
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
                            <td><span class="star-stars">${'⭐'.repeat(r.rating)}</span></td>
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
            initializeDashboard();
        });
    </script>
</body>
</html>