
@extends('layouts.app')

@section('title','Bảng công việc – UniSupport')

@section('content')
<div class="uni-dashboard">
<div class="uni-page-heading">
<div>
<span class="uni-eyebrow">CÔNG VIỆC / KANBAN</span>
<h1>Bảng tiến độ yêu cầu</h1>
<p>Mở một thẻ để trao đổi hoặc cập nhật trạng thái. Hiển thị {{ $tickets->count() }} / {{ $tickets->total() }} yêu cầu.</p>
</div>
<a href="{{ route('requests.index') }}" class="uni-button secondary">
<i class="fa-solid fa-list">
</i> Dạng danh sách</a>
</div>
<form class="uni-form" method="GET">
<label>Tìm yêu cầu<input name="q" value="{{ request('q') }}" placeholder="Nhập tiêu đề rồi nhấn Enter">
</label>
</form>
<div class="uni-kanban">
@foreach(\App\Enums\RequestStatus::cases() as $state)<section class="uni-kanban-column">
<h3>{{ $state->label() }} <span>{{ $tickets->where('status',$state)->count() }}</span>
</h3>
@forelse($tickets->where('status',$state) as $ticket)<a class="uni-ticket" href="{{ route('requests.show',$ticket) }}">
<small>{{ $ticket->code }}</small>
<strong>{{ $ticket->title }}</strong>
<x-badge-status :status="$state->value"/>
<footer>
<small>{{ config('master_data.staff.'.$ticket->assigned_to.'.full_name','Chưa phân công') }}</small>
<small>{{ $ticket->sla_deadline_at?->format('d/m H:i') }}</small>
</footer>
</a>
@empty<p class="text-xs text-slate-400">Không có yêu cầu trong trang này.</p>
@endforelse</section>
@endforeach</div>
<x-pagination :items="$tickets"/>
</div>

@endsection
