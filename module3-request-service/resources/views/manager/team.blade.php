
@extends('layouts.app')

@section('title','Cán bộ và phân công – UniSupport')

@section('content')<div class="uni-dashboard">
<div class="uni-page-heading">
<div>
<span class="uni-eyebrow">ĐIỀU PHỐI PHÒNG BAN</span>
<h1>Cán bộ & phân công yêu cầu</h1>
<p>Cân đối tải công việc và theo dõi chất lượng hỗ trợ.</p>
</div>
<a href="{{ route('requests.index') }}" class="uni-button secondary">Theo dõi tiến độ</a>
</div>
<div class="uni-grid uni-grid-4">
@forelse($team as $person)<x-stat-card :label="$person['name']" :value="$person['active'].' đơn'" :hint="'Đánh giá: '.($person['rating'] ?? '—').' / 5'" icon="user-tie"/>
@empty<x-empty-state title="Chưa có cán bộ thuộc phòng"/>
@endforelse</div>
<x-card title="Yêu cầu chờ phân công">
@if($pending->isEmpty())<x-empty-state title="Đã phân công tất cả" description="Hiện không có yêu cầu chưa được giao cán bộ."/>
@else<x-table>
<x-slot:head>
<tr>
<th>Yêu cầu</th>
<th>Phòng ban</th>
<th>Ngày tạo</th>
<th>
</th>
</tr>
</x-slot:head>
@foreach($pending as $ticket)<tr>
<td>
<a href="{{ route('requests.show',$ticket) }}">{{ $ticket->code }}</a>
<p>{{ $ticket->title }}</p>
</td>
<td>{{ config('master_data.departments.'.$ticket->department_id) }}</td>
<td>{{ $ticket->created_at->format('d/m/Y') }}</td>
<td>
<button class="uni-button" data-open-modal="assign-{{ $ticket->id }}">Phân công</button>
<x-modal :name="'assign-'.$ticket->id" :title="'Phân công '.$ticket->code">
<form class="uni-form" action="{{ route('requests.assign',$ticket) }}" method="POST">
@csrf
@method('PUT')<label>Cán bộ xử lý<select name="assigned_to" required>
<option value="">Chọn cán bộ</option>
@foreach($team as $person)
@if((int)$person['department_id'] === (int)$ticket->department_id)<option value="{{ $person['id'] }}">{{ $person['name'] }} · {{ $person['active'] }} yêu cầu đang mở</option>
@endif
@endforeach</select>
</label>
<label>Hạn xử lý<input type="datetime-local" name="sla_deadline_at">
</label>
<label>Mức ưu tiên<select name="priority">
@foreach(['low'=>'Thấp','normal'=>'Bình thường','high'=>'Cao','urgent'=>'Khẩn cấp'] as $value=>$label)<option value="{{ $value }}" @selected($ticket->priority->value===$value)>{{ $label }}</option>
@endforeach</select>
</label>
<label>Ghi chú<textarea name="note" maxlength="1000">
</textarea>
</label>
<button class="uni-button" type="submit">Xác nhận phân công</button>
</form>
</x-modal>
</td>
</tr>
@endforeach</x-table>
<x-pagination :items="$pending"/>
@endif</x-card>
</div>
@endsection
