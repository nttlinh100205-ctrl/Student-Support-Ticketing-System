<div class="uni-dashboard">
@include('partials.latest-news')
<div class="uni-page-heading">
<div>
<span class="uni-eyebrow">{{ $roleLabel }} / TỔNG QUAN</span>
<h1>Xin chào, {{ $user['full_name'] ?? 'bạn' }} 👋</h1>
<p>{{ $intro }}</p>
</div>
<a class="uni-button {{ $user['role'] === 'student' ? 'cta' : '' }}" href="{{ $user['role'] === 'student' ? route('requests.create') : route('requests.index') }}">
<i class="fa-solid fa-plus">
</i>{{ $user['role'] === 'student' ? 'Tạo yêu cầu mới' : 'Mở danh sách yêu cầu' }}</a>
</div>

@if($user['role'] === 'student')<section class="uni-hero">
<div>
<span class="uni-eyebrow" style="color:#bfdbfe">LUÔN ĐỒNG HÀNH CÙNG BẠN</span>
<h2>Mỗi thắc mắc đều xứng đáng<br>nhận được một lời giải đáp.</h2>
<p>Gửi yêu cầu đến đúng phòng ban, theo dõi tiến độ và trao đổi với cán bộ ngay trên UniSupport.</p>
<a class="uni-button cta" href="{{ route('requests.create') }}">Bạn cần hỗ trợ gì hôm nay? <i class="fa-solid fa-arrow-right">
</i>
</a>
</div>
<i class="fa-solid fa-graduation-cap" aria-hidden="true">
</i>
</section>
@endif
@if($user['role']==='admin')
<x-stat-card label="Người dùng hệ thống" :value="$stats['users'] ?? '—'" hint="Tổng số tài khoản từ module 1" icon="users"/>
@endif
<div class="uni-grid uni-grid-4">
<x-stat-card label="Tổng yêu cầu" :value="$stats['total']" hint="Trong phạm vi quản lý của bạn" icon="ticket"/>
<x-stat-card label="Đang xử lý" :value="$stats['active']" hint="Yêu cầu chưa kết thúc" tone="orange" icon="clock"/>
<x-stat-card label="Đã hoàn thành" :value="$stats['completed']" :hint="$stats['completed_week'].' yêu cầu đóng trong tuần'" tone="green" icon="circle-check"/>
<x-stat-card label="Điểm hài lòng" :value="$stats['rating'] ? number_format($stats['rating'],1).' / 5' : '—'" hint="Từ đánh giá thực tế của sinh viên" tone="purple" icon="star"/>
</div>

@if($user['role'] !== 'student')<div class="uni-quick-links">
@if(in_array($user['role'],['admin','department_head']))<a href="{{ route('requests.index',['queue'=>'unassigned']) }}">
<i class="fa-solid fa-user-plus">
</i>
<strong>{{ $stats['unassigned'] }} yêu cầu chưa phân công</strong>
<small>Điều phối người phụ trách</small>
</a>
@endif<a href="{{ route('requests.index',['queue'=>'overdue']) }}">
<i class="fa-regular fa-clock">
</i>
<strong>{{ $stats['overdue'] }} yêu cầu quá hạn</strong>
<small>Ưu tiên xử lý ngay</small>
</a>
<a href="{{ route('requests.index',['sort'=>'deadline']) }}">
<i class="fa-regular fa-calendar">
</i>
<strong>{{ $stats['due_soon'] }} yêu cầu đến hạn trong 24 giờ</strong>
<small>Chủ động theo dõi thời hạn</small>
</a>
</div>
@endif
<div class="uni-grid uni-grid-2">
<x-card title="Yêu cầu trong 14 ngày" subtitle="Số lượng yêu cầu được tạo mỗi ngày">
<div class="uni-chart">
<canvas id="trend-chart" aria-label="Biểu đồ yêu cầu theo ngày" role="img">
</canvas>
</div>
<noscript>Bật JavaScript để xem biểu đồ.</noscript>
</x-card>
<x-card title="Phân bố loại hỗ trợ" subtitle="Dựa trên các yêu cầu thuộc phạm vi của bạn">
<div class="uni-chart">
<canvas id="types-chart" aria-label="Biểu đồ yêu cầu theo loại" role="img">
</canvas>
</div>
@if($types->isEmpty())<p class="text-sm text-slate-500">Chưa có yêu cầu để thống kê.</p>
@endif</x-card>
</div>

@if(in_array($user['role'],['admin','department_head']))<div class="uni-grid uni-grid-2">
<x-card title="Yêu cầu theo phòng ban">
<div class="uni-chart">
<canvas id="departments-chart" aria-label="Biểu đồ yêu cầu theo phòng ban" role="img">
</canvas>
</div>
</x-card>
<x-card title="Tải công việc của cán bộ">
<x-slot:actions>
<a href="{{ route('workspace.team') }}" class="uni-button secondary">Xem phòng ban</a>
</x-slot:actions>
@forelse(array_slice($team,0,5) as $person)<div class="uni-workload">
<div>
<span>{{ $person['name'] }}</span>
<strong>{{ $person['active'] }} yêu cầu</strong>
</div>
<progress max="{{ max(1, ...array_column($team,'active')) }}" value="{{ $person['active'] }}">
</progress>
</div>
@empty<x-empty-state title="Chưa có cán bộ" description="Thêm tài khoản cán bộ và gán phòng ban ở module tài khoản."/>
@endforelse</x-card>
</div>
@endif
<x-card title="Yêu cầu cập nhật gần đây" subtitle="Theo dõi những hồ sơ đang có thay đổi">
<x-slot:actions>
<a href="{{ route('requests.index') }}" class="uni-button secondary">Xem tất cả <i class="fa-solid fa-arrow-right">
</i>
</a>
</x-slot:actions>
@if($recent->isEmpty())<x-empty-state title="Chưa có yêu cầu" description="Các yêu cầu của bạn sẽ được hiển thị tại đây."/>
@else<x-table>
<x-slot:head>
<tr>
<th>Mã / nội dung</th>
<th>Trạng thái</th>
<th>Cập nhật</th>
<th>
</th>
</tr>
</x-slot:head>
@foreach($recent as $ticket)<tr>
<td>
<a href="{{ route('requests.show',$ticket) }}">{{ $ticket->code }}</a>
<div>{{ $ticket->title }}</div>
</td>
<td>
<x-badge-status :status="$ticket->status->value" :assigned="(bool)$ticket->assigned_to"/>
</td>
<td>{{ $ticket->updated_at?->format('d/m/Y H:i') }}</td>
<td>
<a href="{{ route('requests.show',$ticket) }}">Chi tiết →</a>
</td>
</tr>
@endforeach</x-table>
@endif</x-card>
<div class="uni-grid uni-grid-2">
<x-card title="Hoạt động gần đây">
@forelse($activity as $event)<div class="uni-workload">
<a href="{{ route('requests.show',$event->request_id) }}" class="text-sm text-blue-600">{{ $event->request?->code }}</a>
<p class="text-sm text-slate-600">{{ $event->note ?: 'Cập nhật trạng thái yêu cầu' }}</p>
<small class="text-slate-400">{{ \Carbon\Carbon::parse($event->created_at)->format('d/m/Y H:i') }}</small>
</div>
@empty<x-empty-state title="Chưa có hoạt động"/>
@endforelse</x-card>
<x-card title="Thông tin & hỗ trợ">
<div class="uni-quick-links" style="grid-template-columns:1fr">
<a href="{{ config('ui.news') }}">
<i class="fa-regular fa-newspaper">
</i>
<strong>Tin tức & thông báo</strong>
<small>Cập nhật thông tin từ các phòng ban</small>
</a>
<a href="{{ config('ui.catalog') }}/catalog">
<i class="fa-regular fa-circle-question">
</i>
<strong>Tra cứu thủ tục và câu hỏi thường gặp</strong>
<small>Chuẩn bị thông tin trước khi gửi yêu cầu</small>
</a>
</div>
</x-card>
</div>
</div>
<script id="uni-chart-data" type="application/json">{!! json_encode(['trend'=>$trend,'types'=>$types,'departments'=>$departments], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) !!}</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.min.js">
</script>
<script src="/js/dashboard-charts.js" defer>
</script>
