
@extends('layouts.app')
@section('title','Cấu hình hệ thống – UniSupport')
@section('content')
<div class="uni-dashboard">
<div class="uni-page-heading">
<div>
<span class="uni-eyebrow">QUẢN TRỊ</span>
<h1>Cấu hình xử lý yêu cầu</h1>
<p>Thời hạn mặc định và ngưỡng cảnh báo sớm của hệ thống.</p>
</div>
</div>
<x-card title="Cam kết thời gian hỗ trợ (SLA)" subtitle="SLA theo loại hỗ trợ ở module danh mục được ưu tiên nếu đã thiết lập. Các yêu cầu hiện tại giữ nguyên thời hạn.">
<form method="POST" action="{{ route('workspace.settings.save') }}" class="uni-form">
@csrf
@method('PUT')
@foreach(['low'=>'Ưu tiên thấp','normal'=>'Bình thường','high'=>'Ưu tiên cao','urgent'=>'Khẩn cấp'] as $key=>$label)
<label>{{ $label }} (giờ)<input name="{{ $key }}" type="number" min="1" max="8760" required value="{{ old($key,$settings['deadline_hours.'.$key]) }}">
</label>
<x-field-error :name="$key"/>
@endforeach
<label>Cảnh báo khi đã dùng (%) thời gian xử lý<input name="warning" type="number" min="1" max="99" required value="{{ old('warning',$settings['warning_threshold_percent']) }}">
</label>
<x-field-error name="warning"/>
<button class="uni-button" type="submit">Lưu cấu hình</button>
</form>
</x-card>
</div>
@endsection
