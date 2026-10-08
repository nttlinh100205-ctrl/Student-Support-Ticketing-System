
@extends('layouts.app')

@section('title','Đánh giá của sinh viên – UniSupport')

@section('content')<div class="uni-dashboard">
<div class="uni-page-heading">
<div>
<span class="uni-eyebrow">CHẤT LƯỢNG HỖ TRỢ</span>
<h1>Đánh giá của sinh viên</h1>
<p>Phản hồi từ các yêu cầu trong phạm vi công việc của bạn.</p>
</div>
</div>
<div class="uni-grid uni-grid-2">
@forelse($tickets as $ticket)<x-card :title="$ticket->code" :subtitle="$ticket->title">
<p class="text-amber-500 text-xl">{{ str_repeat('★',$ticket->rating) }}{{ str_repeat('☆',5-$ticket->rating) }}</p>
<p class="text-sm my-4">{{ $ticket->rating_comment ?: 'Sinh viên không để lại nhận xét.' }}</p>
<div class="uni-rating-criteria">
@foreach(['attitude'=>'Thái độ','speed'=>'Tốc độ','quality'=>'Chất lượng'] as $key=>$label)
@if($ticket->{'rating_'.$key})
<small>{{ $label }}: {{ $ticket->{'rating_'.$key} }}/5</small>
@endif
@endforeach
</div>
<a href="{{ route('requests.show',$ticket) }}" class="uni-button secondary">Xem yêu cầu</a>
</x-card>
@empty<x-empty-state title="Chưa có đánh giá" description="Đánh giá sẽ xuất hiện sau khi yêu cầu hoàn tất."/>
@endforelse</div>
<x-pagination :items="$tickets"/>
</div>
@endsection
