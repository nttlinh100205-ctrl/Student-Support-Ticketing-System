
@props(['title' => 'Chưa có dữ liệu', 'description' => 'Thông tin sẽ xuất hiện tại đây khi có cập nhật.', 'icon' => 'inbox'])
<div {{ $attributes->merge(['class' => 'uni-empty']) }}>
<i class="fa-solid fa-{{ $icon }}" aria-hidden="true">
</i>
<h3>{{ $title }}</h3>
<p>{{ $description }}</p>{{ $slot }}</div>
