
@props(['items'])
<ol class="uni-timeline">
@forelse($items as $item)<li>
<span class="uni-timeline-dot">
</span>
<div>
<strong>{{ $item->to_status }}</strong>
<time>{{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:i') }}</time>
<p>{{ $item->note ?: 'Cập nhật tiến độ xử lý' }}</p>
</div>
</li>
@empty<li>Chưa có hoạt động.</li>
@endforelse</ol>
