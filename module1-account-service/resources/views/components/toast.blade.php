
@props(['type'=>'success'])
<div role="{{ $type==='error'?'alert':'status' }}" class="uni-toast uni-alert {{ $type }}">
<span>{{ $slot }}</span>
<button type="button" onclick="this.parentElement.remove()" aria-label="Đóng thông báo">×</button>
</div>
