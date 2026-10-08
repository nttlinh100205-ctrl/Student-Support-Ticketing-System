
@extends('layouts.app')

@section('title','Nhật ký xử lý – UniSupport')

@section('content')<div class="uni-dashboard">
<div class="uni-page-heading">
<div>
<span class="uni-eyebrow">QUẢN TRỊ / NHẬT KÝ</span>
<h1>Nhật ký hoạt động xử lý yêu cầu</h1>
<p>Lịch sử tiếp nhận, phân công và thay đổi trạng thái được hệ thống ghi tự động.</p>
</div>
</div>
<x-card>
<x-table>
<x-slot:head>
<tr>
<th>Thời gian</th>
<th>Yêu cầu</th>
<th>Người thực hiện</th>
<th>Trạng thái</th>
<th>Nội dung</th>
</tr>
</x-slot:head>
@forelse($events as $event)<tr>
<td>{{ \Carbon\Carbon::parse($event->created_at)->format('d/m/Y H:i') }}</td>
<td>{{ $event->request?->code ?? 'Yêu cầu đã xóa' }}</td>
<td>{{ $event->changed_by ? (config('master_data.users.'.$event->changed_by.'.full_name') ?? '#'.$event->changed_by) : 'Hệ thống' }}</td>
<td>
<x-badge-status :status="$event->to_status"/>
</td>
<td>{{ $event->note ?: 'Cập nhật trạng thái' }}</td>
</tr>
@empty<tr>
<td colspan="5">
<x-empty-state title="Chưa có hoạt động"/>
</td>
</tr>
@endforelse</x-table>
<x-pagination :items="$events"/>
</x-card>
</div>
@endsection
