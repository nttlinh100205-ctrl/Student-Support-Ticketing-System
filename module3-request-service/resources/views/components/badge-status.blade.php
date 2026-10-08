
@props(['status', 'assigned' => false])
@php($labels = ['new'=>'Chờ tiếp nhận','received'=>'Đã tiếp nhận','in_progress'=>'Đang xử lý','waiting_info'=>'Cần bổ sung','resolved'=>'Hoàn thành','closed'=>'Đã đóng','cancelled'=>'Đã hủy','rejected'=>'Từ chối'])
<span {{ $attributes->merge(['class' => 'uni-badge status-'.$status]) }}>{{ $status === 'new' && $assigned ? 'Đã phân công' : ($labels[$status] ?? $status) }}</span>
