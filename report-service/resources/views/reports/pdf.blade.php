<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Báo cáo Hiệu suất Xử lý &amp; Đánh giá Hài lòng</title>
    <style>
        @page {
            margin: 20mm 15mm 20mm 15mm;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1c2436;
            line-height: 1.4;
        }
        .header-table {
            width: 100%;
            margin-bottom: 20px;
            border-bottom: 2px solid #1c2436;
            padding-bottom: 12px;
        }
        .school-name {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            color: #37517a;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin: 15px 0 5px 0;
            text-transform: uppercase;
            color: #1c2436;
        }
        .meta-info {
            text-align: center;
            font-size: 10px;
            color: #5a6274;
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #1c2436;
            margin-top: 18px;
            margin-bottom: 8px;
            border-bottom: 1px solid #ddd6c4;
            padding-bottom: 4px;
            text-transform: uppercase;
        }
        .kpi-table {
            width: 100%;
            margin-bottom: 15px;
            border-collapse: collapse;
        }
        .kpi-box {
            background-color: #fbf9f4;
            border: 1px solid #ddd6c4;
            padding: 8px 12px;
            text-align: center;
            width: 25%;
        }
        .kpi-title {
            font-size: 9px;
            color: #5a6274;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .kpi-value {
            font-size: 16px;
            font-weight: bold;
            color: #1c2436;
        }
        .kpi-sub {
            font-size: 9px;
            color: #8a6a34;
            margin-top: 2px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 10px;
        }
        table.data-table th {
            background-color: #f0ebe1;
            color: #1c2436;
            font-weight: bold;
            padding: 6px 8px;
            border: 1px solid #ddd6c4;
            text-align: left;
        }
        table.data-table td {
            padding: 5px 8px;
            border: 1px solid #e8e2d2;
        }
        table.data-table tr:nth-child(even) {
            background-color: #faf8f5;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }
        .badge-success { background-color: #e2ede4; color: #4c6e52; }
        .badge-danger { background-color: #fce8e6; color: #99493f; }
        .badge-warning { background-color: #fdf3e7; color: #a15a22; }
        .badge-info { background-color: #e7eef7; color: #37517a; }
        .footer-table {
            width: 100%;
            margin-top: 30px;
        }
        .signature-box {
            text-align: center;
            font-size: 11px;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                <div class="school-name">HỆ THỐNG HỖ TRỢ SINH VIÊN</div>
                <div style="font-size: 10px; color: #5a6274;">Cổng Dịch vụ Trực tuyến &amp; Tiếp nhận Yêu cầu</div>
            </td>
            <td style="width: 40%; text-align: right; font-size: 10px; color: #5a6274;">
                <div>Mẫu báo cáo: <strong>M5-REPORT</strong></div>
                <div>Thời gian xuất: <strong>{{ $generatedAt }}</strong></div>
            </td>
        </tr>
    </table>

    <div class="report-title">BÁO CÁO HIỆU SUẤT XỬ LÝ &amp; ĐÁNH GIÁ HÀI LÒNG</div>
    <div class="meta-info">
        Áp dụng cho kỳ báo cáo:
        @if(!empty($filters['from_date']) || !empty($filters['to_date']))
            Từ <strong>{{ $filters['from_date'] ?? '...' }}</strong> đến <strong>{{ $filters['to_date'] ?? '...' }}</strong>
        @else
            <strong>Toàn bộ thời gian</strong>
        @endif
        @if(!empty($filters['department_id']))
            | Phòng ban: <strong>{{ $departments[$filters['department_id']]['name'] ?? ('#'.$filters['department_id']) }}</strong>
        @endif
    </div>

    <!-- 1. KPI Strip -->
    <table class="kpi-table">
        <tr>
            <td class="kpi-box">
                <div class="kpi-title">Tổng số yêu cầu</div>
                <div class="kpi-value">{{ $stats['total_requests'] }}</div>
                <div class="kpi-sub">
                    Hoàn thành: {{ ($stats['by_status']['resolved'] ?? 0) + ($stats['by_status']['closed'] ?? 0) }} YC
                </div>
            </td>
            <td class="kpi-box">
                <div class="kpi-title">Tỷ lệ đúng hạn SLA</div>
                <div class="kpi-value" style="color: #4c6e52;">{{ $stats['sla_metrics']['sla_compliance_rate'] }}%</div>
                <div class="kpi-sub">
                    Quá hạn: {{ $stats['sla_metrics']['overdue_count'] }} YC ({{ $stats['sla_metrics']['sla_overdue_rate'] }}%)
                </div>
            </td>
            <td class="kpi-box">
                <div class="kpi-title">Điểm hài lòng CSAT</div>
                <div class="kpi-value" style="color: #a9781f;">{{ $stats['ratings_summary']['average_rating'] }} / 5.0 ⭐</div>
                <div class="kpi-sub">
                    Tổng lượt đánh giá: {{ $stats['ratings_summary']['total_ratings'] }}
                </div>
            </td>
            <td class="kpi-box">
                <div class="kpi-title">Thời gian xử lý TB</div>
                <div class="kpi-value">{{ $stats['avg_processing_hours'] ? $stats['avg_processing_hours'].'h' : 'N/A' }}</div>
                <div class="kpi-sub">Tính theo giờ thực tế</div>
            </td>
        </tr>
    </table>

    <!-- 2. Xếp hạng Phòng ban -->
    <div class="section-title">1. Xếp hạng Hiệu suất &amp; Hài lòng theo Phòng ban</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 35px; text-align: center;">Hạng</th>
                <th>Phòng ban</th>
                <th style="text-align: center;">Tổng tiếp nhận</th>
                <th style="text-align: center;">Đã hoàn thành</th>
                <th style="text-align: center;">Tỷ lệ xong</th>
                <th style="text-align: center;">Đúng hạn SLA</th>
                <th style="text-align: center;">TG xử lý TB</th>
                <th style="text-align: center;">Điểm CSAT</th>
                <th style="text-align: center;">Điểm Tổng hợp</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stats['department_rankings'] as $dept)
            <tr>
                <td style="text-align: center; font-weight: bold;">#{{ $dept['rank'] }}</td>
                <td><strong>{{ $dept['department_name'] }}</strong></td>
                <td style="text-align: center;">{{ $dept['total_assigned'] }}</td>
                <td style="text-align: center;">{{ $dept['resolved_count'] }}</td>
                <td style="text-align: center;">{{ $dept['completion_rate'] }}%</td>
                <td style="text-align: center;">
                    <span class="badge {{ $dept['sla_rate'] >= 85 ? 'badge-success' : 'badge-warning' }}">{{ $dept['sla_rate'] }}%</span>
                </td>
                <td style="text-align: center;">{{ $dept['avg_hours'] ? $dept['avg_hours'].'h' : '—' }}</td>
                <td style="text-align: center; color: #a9781f;">{{ $dept['csat'] }} ⭐</td>
                <td style="text-align: center; font-weight: bold; color: #37517a;">{{ $dept['performance_score'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- 3. Xếp hạng Cán bộ & Khối lượng công việc -->
    <div class="section-title">2. Xếp hạng Cán bộ &amp; Khối lượng công việc</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 35px; text-align: center;">Hạng</th>
                <th>Cán bộ phụ trách</th>
                <th>Phòng ban</th>
                <th style="text-align: center;">Đang xử lý</th>
                <th style="text-align: center;">Đã giải quyết</th>
                <th style="text-align: center;">Tổng việc</th>
                <th style="text-align: center;">Đúng hạn SLA</th>
                <th style="text-align: center;">Điểm CSAT</th>
                <th style="text-align: center;">Xếp loại</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stats['staff_rankings'] as $staff)
            <tr>
                <td style="text-align: center; font-weight: bold;">#{{ $staff['rank'] }}</td>
                <td><strong>{{ $staff['staff_name'] }}</strong></td>
                <td>{{ $staff['department_name'] }}</td>
                <td style="text-align: center;">{{ $staff['total_assigned'] - $staff['resolved_count'] }}</td>
                <td style="text-align: center;">{{ $staff['resolved_count'] }}</td>
                <td style="text-align: center; font-weight: bold;">{{ $staff['total_assigned'] }}</td>
                <td style="text-align: center;">{{ $staff['sla_rate'] }}%</td>
                <td style="text-align: center; color: #a9781f;">{{ $staff['csat'] }} ⭐</td>
                <td style="text-align: center;">
                    <span class="badge {{ $staff['tier'] === 'Xuất sắc' ? 'badge-success' : ($staff['tier'] === 'Tốt' ? 'badge-info' : 'badge-warning') }}">
                        {{ $staff['tier'] }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- 4. Danh sách Yêu cầu Quá hạn SLA (nếu có) -->
    @if(count($stats['sla_metrics']['overdue_requests']) > 0)
    <div class="section-title">3. Danh sách Yêu cầu Quá hạn Chuẩn SLA (Cần lưu ý)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Mã YC</th>
                <th>Tiêu đề</th>
                <th>Sinh viên</th>
                <th>Cán bộ phụ trách</th>
                <th>Ngày tạo</th>
                <th>Hạn cam kết SLA</th>
                <th>Trạng thái</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stats['sla_metrics']['overdue_requests'] as $req)
            <tr>
                <td><strong>{{ $req['code'] }}</strong></td>
                <td>{{ $req['title'] }}</td>
                <td>{{ $req['student_name'] }}</td>
                <td>{{ $req['staff_name'] }}</td>
                <td>{{ $req['created_at'] }}</td>
                <td style="color: #99493f; font-weight: bold;">{{ $req['due_at'] }}</td>
                <td><span class="badge badge-danger">{{ $req['status'] }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <table class="footer-table">
        <tr>
            <td class="signature-box" style="width: 50%;">
                <p><strong>NGƯỜI LẬP BÁO CÁO</strong></p>
                <p style="font-size: 9px; color: #5a6274;">(Ký và ghi rõ họ tên)</p>
                <br><br><br>
                <p>Hệ thống Hỗ trợ Sinh viên</p>
            </td>
            <td class="signature-box" style="width: 50%;">
                <p><strong>TRƯỞNG ĐƠN VỊ PHÊ DUYỆT</strong></p>
                <p style="font-size: 9px; color: #5a6274;">(Ký và đóng dấu)</p>
                <br><br><br>
                <p>Ban Giám hiệu / Lãnh đạo Phòng ban</p>
            </td>
        </tr>
    </table>

</body>
</html>
