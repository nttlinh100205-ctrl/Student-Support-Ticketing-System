<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:x="urn:schemas-microsoft-com:office:excel"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    @verbatim
    <!--[if gte mso 9]>
    <xml>
        <x:ExcelWorkbook>
            <x:ExcelWorksheets>
                <x:ExcelWorksheet>
                    <x:Name>BaoCaoTongQuan</x:Name>
                    <x:WorksheetOptions>
                        <x:DisplayGridlines/>
                        <x:Print>
                            <x:ValidPrinterInfo/>
                            <x:Orientation>Landscape</x:Orientation>
                        </x:Print>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
    <![endif]-->
    @endverbatim
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            color: #0F172A;
            background-color: #FFFFFF;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 24px;
        }
        th, td {
            vertical-align: middle;
            font-size: 10pt;
            padding: 7px 10px;
        }
        .header-org {
            font-size: 11pt;
            font-weight: bold;
            color: #1E3A8A;
            text-align: left;
        }
        .header-sub {
            font-size: 9.5pt;
            color: #475569;
        }
        .header-motto {
            font-size: 10.5pt;
            font-weight: bold;
            text-align: right;
            color: #0F172A;
        }
        .report-title {
            font-size: 15pt;
            font-weight: 800;
            color: #1E40AF;
            text-align: center;
            text-transform: uppercase;
            padding: 12px 0 4px 0;
        }
        .report-subtitle {
            font-size: 9.5pt;
            color: #64748B;
            text-align: center;
            font-style: italic;
            padding-bottom: 14px;
        }
        .section-header {
            background-color: #1E40AF;
            color: #FFFFFF;
            font-size: 11pt;
            font-weight: bold;
            text-align: left;
            padding: 8px 12px;
            border: 1pt solid #1E40AF;
        }
        .table-data th {
            background-color: #2563EB;
            color: #FFFFFF;
            font-weight: bold;
            text-align: center;
            border: 0.5pt solid #1D4ED8;
            font-size: 9.5pt;
        }
        .table-data td {
            border: 0.5pt solid #CBD5E1;
            font-size: 9pt;
        }
        .table-summary td, .table-summary th {
            border: 0.5pt solid #CBD5E1;
            font-size: 9.5pt;
        }
        .table-summary th {
            background-color: #EFF6FF;
            color: #1E40AF;
            font-weight: bold;
            text-align: left;
        }
        .bg-light {
            background-color: #F8FAFC;
        }
        .bg-card-blue {
            background-color: #EFF6FF;
            color: #1E40AF;
            font-weight: bold;
        }
        .bg-card-emerald {
            background-color: #ECFDF5;
            color: #047857;
            font-weight: bold;
        }
        .bg-card-amber {
            background-color: #FFFBEB;
            color: #B45309;
            font-weight: bold;
        }
        .bg-card-rose {
            background-color: #FFF1F2;
            color: #BE123C;
            font-weight: bold;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .text-mono {
            font-family: Consolas, 'Courier New', monospace;
            mso-number-format: "\@";
        }
        .stars {
            color: #D97706;
            font-weight: bold;
            font-size: 10pt;
        }
        .badge-status {
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: 600;
        }
    </style>
</head>
<body>

    {{-- TIÊU ĐỀ QUỐC HIỆU / CƠ QUAN CHỦ QUẢN --}}
    <table>
        <tr>
            <td colspan="6" class="header-org">
                BỘ GIÁO DỤC VÀ ĐÀO TẠO<br>
                TRƯỜNG ĐẠI HỌC CHUẨN QUỐC GIA<br>
                <span class="header-sub">HỆ THỐNG MỘT CỬA HỖ TRỢ SINH VIÊN (MODULE 3)</span>
            </td>
            <td colspan="10" class="header-motto">
                CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM<br>
                <span style="font-weight: normal; font-size: 9.5pt;">Độc lập - Tự do - Hạnh phúc</span><br>
                <span style="font-size: 8.5pt; color: #64748B; font-weight: normal;">────────────</span>
            </td>
        </tr>
        <tr><td colspan="16"></td></tr>
        <tr>
            <td colspan="16" class="report-title">
                BÁO CÁO TỔNG QUAN TIẾP NHẬN YÊU CẦU & ĐÁNH GIÁ CHẤT LƯỢNG HỌC VỤ
            </td>
        </tr>
        <tr>
            <td colspan="16" class="report-subtitle">
                Thời gian xuất: {{ now()->format('d/m/Y H:i:s') }} | Người lập: {{ $user['full_name'] }} ({{ $user['role'] === 'admin' ? 'Quản trị viên Hệ thống' : 'Trưởng phòng Quản lý' }}) | Phạm vi: Toàn trường
            </td>
        </tr>
    </table>

    {{-- KHỐI 1: TỔNG HỢP CHỈ SỐ HOẠT ĐỘNG & SLA --}}
    <table class="table-summary">
        <thead>
            <tr>
                <th colspan="4" class="section-header">
                    I. BẢNG TỔNG QUAN CHỈ SỐ HOẠT ĐỘNG (SLA & CHẤT LƯỢNG PHỤC VỤ)
                </th>
            </tr>
            <tr style="background-color: #DBEAFE; color: #1E3A8A;">
                <th style="width: 35%;">Chỉ số hoạt động (KPI)</th>
                <th style="width: 20%; text-align: center;">Số lượng / Giá trị</th>
                <th style="width: 20%; text-align: center;">Tỷ lệ / Đánh giá</th>
                <th style="width: 25%;">Ghi chú nghiệp vụ</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="font-bold">Tổng số yêu cầu tiếp nhận</td>
                <td class="text-center font-bold bg-card-blue" style="font-size: 11pt;">{{ $slaStats['total'] }}</td>
                <td class="text-center">100.0%</td>
                <td>Toàn bộ hồ sơ trong hệ thống</td>
            </tr>
            <tr>
                <td>Hồ sơ đang xử lý (Cán bộ đang thụ lý)</td>
                <td class="text-center font-bold bg-card-amber">{{ $slaStats['in_progress'] }}</td>
                <td class="text-center">{{ $slaStats['total'] > 0 ? round(($slaStats['in_progress'] / $slaStats['total']) * 100, 1) : 0 }}%</td>
                <td>Gồm Mới tạo, Đã tiếp nhận, Đang xử lý, Chờ SV bổ sung</td>
            </tr>
            <tr>
                <td>Hồ sơ đã giải quyết / Hoàn tất</td>
                <td class="text-center font-bold bg-card-emerald">{{ $slaStats['completed'] }}</td>
                <td class="text-center">{{ $slaStats['total'] > 0 ? round(($slaStats['completed'] / $slaStats['total']) * 100, 1) : 0 }}%</td>
                <td>Chờ SV xác nhận hoặc Đã hoàn tất</td>
            </tr>
            <tr>
                <td>Tỷ lệ hoàn thành đúng thời hạn SLA</td>
                <td class="text-center font-bold bg-card-emerald" style="font-size: 11pt;">{{ $slaStats['on_time_rate'] }}%</td>
                <td class="text-center font-bold" style="color: #047857;">{{ $slaStats['on_time_rate'] >= 90 ? 'Đạt chuẩn xuất sắc' : ($slaStats['on_time_rate'] >= 75 ? 'Đạt chuẩn' : 'Cần cải thiện') }}</td>
                <td>Cam kết thời gian xử lý theo quy định</td>
            </tr>
            <tr>
                <td>Hồ sơ cảnh báo sắp quá hạn SLA</td>
                <td class="text-center font-bold bg-card-amber">{{ $slaStats['warning'] }}</td>
                <td class="text-center">{{ $slaStats['total'] > 0 ? round(($slaStats['warning'] / $slaStats['total']) * 100, 1) : 0 }}%</td>
                <td>Thời gian còn lại dưới 24 giờ</td>
            </tr>
            <tr>
                <td>Hồ sơ vi phạm quá hạn SLA</td>
                <td class="text-center font-bold bg-card-rose">{{ $slaStats['breached'] }}</td>
                <td class="text-center">{{ $slaStats['total'] > 0 ? round(($slaStats['breached'] / $slaStats['total']) * 100, 1) : 0 }}%</td>
                <td>Vượt quá thời hạn giải quyết cam kết</td>
            </tr>
            <tr>
                <td class="font-bold">Tổng số lượt sinh viên đánh giá (CSAT)</td>
                <td class="text-center font-bold bg-card-blue">{{ $slaStats['rated_count'] }}</td>
                <td class="text-center">{{ $slaStats['completed'] > 0 ? round(($slaStats['rated_count'] / $slaStats['completed']) * 100, 1) : 0 }}% phản hồi</td>
                <td>Tỷ lệ sinh viên gửi đánh giá sau khi hoàn tất</td>
            </tr>
            <tr>
                <td class="font-bold">Điểm đánh giá chất lượng trung bình (CSAT)</td>
                <td class="text-center font-bold stars" style="font-size: 12pt;">{{ $slaStats['avg_rating'] ?? '5.0' }} / 5.0 ★</td>
                <td class="text-center font-bold" style="color: #D97706;">
                    @php $r = (float) ($slaStats['avg_rating'] ?? 5.0); @endphp
                    {{ $r >= 4.5 ? 'Rất hài lòng ★★★★★' : ($r >= 3.5 ? 'Hài lòng ★★★★☆' : 'Cần cải thiện') }}
                </td>
                <td>Chỉ số đo lường mức độ hài lòng của SV</td>
            </tr>
        </tbody>
    </table>

    {{-- KHỐI 2: THỐNG KÊ CHI TIẾT ĐÁNH GIÁ SAO (CSAT BREAKDOWN) --}}
    <table class="table-summary">
        <thead>
            <tr>
                <th colspan="4" class="section-header">
                    II. PHÂN BỐ ĐÁNH GIÁ CHẤT LƯỢNG PHỤC VỤ THEO MỨC ĐỘ SAO
                </th>
            </tr>
            <tr style="background-color: #FEF3C7; color: #92400E;">
                <th style="width: 35%;">Mức độ đánh giá</th>
                <th style="width: 20%; text-align: center;">Số lượt đánh giá</th>
                <th style="width: 20%; text-align: center;">Tỷ lệ (%)</th>
                <th style="width: 25%;">Ý nghĩa phản hồi</th>
            </tr>
        </thead>
        <tbody>
            @php
                $starLabels = [
                    5 => ['label' => '5 sao (Rất hài lòng)', 'stars' => '★★★★★', 'color' => '#047857', 'meaning' => 'Cực kỳ hài lòng về tốc độ và thái độ'],
                    4 => ['label' => '4 sao (Hài lòng)', 'stars' => '★★★★☆', 'color' => '#2563EB', 'meaning' => 'Hài lòng, đáp ứng tốt yêu cầu'],
                    3 => ['label' => '3 sao (Bình thường)', 'stars' => '★★★☆☆', 'color' => '#D97706', 'meaning' => 'Đạt yêu cầu tiêu chuẩn'],
                    2 => ['label' => '2 sao (Chưa hài lòng)', 'stars' => '★★☆☆☆', 'color' => '#DC2626', 'meaning' => 'Xử lý chậm hoặc chưa rõ ràng'],
                    1 => ['label' => '1 sao (Rất chưa hài lòng)', 'stars' => '★☆☆☆☆', 'color' => '#991B1B', 'meaning' => 'Không hài lòng, cần khắc phục'],
                ];
                $ratedTotal = max(1, $slaStats['rated_count']);
            @endphp
            @foreach($starLabels as $starScore => $starMeta)
                @php
                    $countStar = $starDistribution[$starScore] ?? 0;
                    $pctStar = $slaStats['rated_count'] > 0 ? round(($countStar / $slaStats['rated_count']) * 100, 1) : 0;
                @endphp
                <tr>
                    <td>
                        <span class="stars">{{ $starMeta['stars'] }}</span>
                        <strong>{{ $starMeta['label'] }}</strong>
                    </td>
                    <td class="text-center font-bold">{{ $countStar }}</td>
                    <td class="text-center font-bold" style="color: {{ $starMeta['color'] }};">{{ $pctStar }}%</td>
                    <td>{{ $starMeta['meaning'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- KHỐI 3: THỐNG KÊ THEO PHÒNG BAN TIẾP NHẬN --}}
    @if(!empty($byDepartment))
    <table class="table-summary">
        <thead>
            <tr>
                <th colspan="7" class="section-header">
                    III. THỐNG KÊ TIẾP NHẬN & ĐÁNH GIÁ THEO PHÒNG BAN
                </th>
            </tr>
            <tr style="background-color: #DBEAFE; color: #1E3A8A;">
                <th style="width: 6%; text-align: center;">STT</th>
                <th style="width: 32%;">Phòng ban tiếp nhận</th>
                <th style="width: 12%; text-align: center;">Tổng hồ sơ</th>
                <th style="width: 12%; text-align: center;">Đang xử lý</th>
                <th style="width: 12%; text-align: center;">Đã hoàn tất</th>
                <th style="width: 12%; text-align: center;">Quá hạn SLA</th>
                <th style="width: 14%; text-align: center;">Đánh giá CSAT</th>
            </tr>
        </thead>
        <tbody>
            @php $deptIndex = 1; @endphp
            @foreach($byDepartment as $deptId => $deptData)
                <tr class="{{ $deptIndex % 2 === 0 ? 'bg-light' : '' }}">
                    <td class="text-center">{{ $deptIndex++ }}</td>
                    <td class="font-bold">{{ $deptData['name'] }}</td>
                    <td class="text-center font-bold">{{ $deptData['total'] }}</td>
                    <td class="text-center">{{ $deptData['in_progress'] }}</td>
                    <td class="text-center" style="color: #047857; font-weight: bold;">{{ $deptData['completed'] }}</td>
                    <td class="text-center" style="color: {{ $deptData['breached'] > 0 ? '#DC2626' : '#64748B' }}; font-weight: bold;">
                        {{ $deptData['breached'] }}
                    </td>
                    <td class="text-center">
                        @if($deptData['avg_rating'])
                            <span class="stars">★ {{ $deptData['avg_rating'] }}</span>
                            <span style="font-size: 8pt; color: #64748B;">({{ $deptData['rated_count'] }})</span>
                        @else
                            <span style="color: #94A3B8;">Chưa có</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- KHỐI 4: DANH SÁCH CHI TIẾT CÁC YÊU CẦU HỖ TRỢ --}}
    <table class="table-data">
        <thead>
            <tr>
                <th colspan="16" class="section-header">
                    IV. DANH SÁCH CHI TIẾT CÁC YÊU CẦU HỖ TRỢ (TỔNG SỐ: {{ $requests->count() }} HỒ SƠ)
                </th>
            </tr>
            <tr>
                <th style="width: 4%;">STT</th>
                <th style="width: 10%;">Mã yêu cầu</th>
                <th style="width: 18%;">Tiêu đề yêu cầu</th>
                <th style="width: 7%;">Mã SV</th>
                <th style="width: 11%;">Họ tên sinh viên</th>
                <th style="width: 12%;">Phòng ban tiếp nhận</th>
                <th style="width: 11%;">Loại hỗ trợ</th>
                <th style="width: 7%;">Mức ưu tiên</th>
                <th style="width: 9%;">Trạng thái</th>
                <th style="width: 11%;">Cán bộ phụ trách</th>
                <th style="width: 9%;">Hạn xử lý SLA</th>
                <th style="width: 8%;">Tình trạng SLA</th>
                <th style="width: 8%;">Đánh giá</th>
                <th style="width: 16%;">Nhận xét của SV</th>
                <th style="width: 8%;">Thời gian gửi</th>
                <th style="width: 8%;">Thời gian đóng</th>
            </tr>
        </thead>
        <tbody>
            @php
                $statusLabels = [
                    'new' => 'Mới tạo',
                    'received' => 'Đã tiếp nhận',
                    'in_progress' => 'Đang xử lý',
                    'waiting_info' => 'Chờ SV bổ sung',
                    'resolved' => 'Chờ SV xác nhận',
                    'closed' => 'Đã hoàn tất',
                    'cancelled' => 'Đã hủy',
                ];
                $priorityLabels = ['low' => 'Thấp', 'normal' => 'Bình thường', 'high' => 'Cao', 'urgent' => 'Khẩn cấp'];
                $slaLabels = ['on_time' => 'Đúng hạn', 'warning' => 'Sắp quá hạn', 'breached' => 'Quá hạn'];
                $stt = 1;
            @endphp
            @forelse($requests as $req)
                @php
                    $statusVal = $req->status instanceof \App\Enums\RequestStatus ? $req->status->value : $req->status;
                    $priorityVal = $req->priority instanceof \App\Enums\RequestPriority ? $req->priority->value : $req->priority;
                    $slaVal = $req->sla_flag instanceof \App\Enums\SlaFlag ? $req->sla_flag->value : $req->sla_flag;
                    $studentName = $allUsers[$req->student_id]['full_name'] ?? ('SV #'.$req->student_id);
                    $staffName = $req->assigned_to ? ($staffNames[$req->assigned_to] ?? ('Cán bộ #'.$req->assigned_to)) : 'Chưa phân công';
                @endphp
                <tr class="{{ $stt % 2 === 0 ? 'bg-light' : '' }}">
                    <td class="text-center">{{ $stt++ }}</td>
                    <td class="text-mono font-bold text-center" style="color: #1E40AF;">{{ $req->code }}</td>
                    <td><strong>{{ $req->title }}</strong></td>
                    <td class="text-mono text-center">#{{ $req->student_id }}</td>
                    <td>{{ $studentName }}</td>
                    <td>{{ $departments[$req->department_id] ?? ('#'.$req->department_id) }}</td>
                    <td>{{ $supportTypes[$req->support_type_id]['name'] ?? ('#'.$req->support_type_id) }}</td>
                    <td class="text-center font-bold" style="color: {{ $priorityVal === 'urgent' ? '#DC2626' : ($priorityVal === 'high' ? '#D97706' : '#2563EB') }};">
                        {{ $priorityLabels[$priorityVal] ?? $priorityVal }}
                    </td>
                    <td class="text-center font-bold">
                        {{ $statusLabels[$statusVal] ?? $statusVal }}
                    </td>
                    <td>{{ $staffName }}</td>
                    <td class="text-center text-mono">
                        {{ $req->sla_deadline_at ? $req->sla_deadline_at->format('d/m/Y H:i') : '—' }}
                    </td>
                    <td class="text-center font-bold" style="color: {{ $slaVal === 'breached' ? '#DC2626' : ($slaVal === 'warning' ? '#D97706' : '#047857') }};">
                        {{ $slaLabels[$slaVal] ?? $slaVal }}
                    </td>
                    <td class="text-center">
                        @if($req->rating)
                            <span class="stars">{{ str_repeat('★', (int) $req->rating) }}</span>
                            <span style="font-weight: bold; font-size: 8.5pt;">({{ $req->rating }}/5)</span>
                        @else
                            <span style="color: #94A3B8; font-style: italic;">Chưa có</span>
                        @endif
                    </td>
                    <td style="font-style: italic; color: #475569;">{{ $req->rating_comment ?? '' }}</td>
                    <td class="text-center text-mono">{{ $req->created_at ? $req->created_at->format('d/m/Y H:i') : '' }}</td>
                    <td class="text-center text-mono">{{ $req->closed_at ? $req->closed_at->format('d/m/Y H:i') : ($req->resolved_at ? $req->resolved_at->format('d/m/Y H:i') : '—') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="16" class="text-center" style="padding: 20px; color: #94A3B8; font-style: italic;">
                        Không có yêu cầu hỗ trợ nào phù hợp với điều kiện tìm kiếm.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- PHẦN KÝ TÊN VÀ XÁC NHẬN --}}
    <table style="margin-top: 30px; border: none;">
        <tr>
            <td colspan="6" style="text-align: center; vertical-align: top;">
                <strong>NGƯỜI LẬP BÁO CÁO</strong><br>
                <span style="font-size: 9pt; color: #64748B; font-style: italic;">(Ký và ghi rõ họ tên)</span>
                <br><br><br><br>
                <strong>{{ $user['full_name'] }}</strong>
            </td>
            <td colspan="4"></td>
            <td colspan="6" style="text-align: center; vertical-align: top;">
                <em>Hà Nội, ngày {{ now()->format('d') }} tháng {{ now()->format('m') }} năm {{ now()->format('Y') }}</em><br>
                <strong>BAN GIÁM HIỆU / LÃNH ĐẠO ĐƠN VỊ DUYỆT</strong><br>
                <span style="font-size: 9pt; color: #64748B; font-style: italic;">(Ký tên và đóng dấu)</span>
                <br><br><br><br>
                <span style="color: #94A3B8;">(Đã duyệt điện tử)</span>
            </td>
        </tr>
    </table>

</body>
</html>
