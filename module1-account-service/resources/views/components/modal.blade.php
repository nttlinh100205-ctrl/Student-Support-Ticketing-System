
@props(['name', 'title'])
<dialog id="{{ $name }}" {{ $attributes->merge(['class' => 'uni-modal']) }}>
<header>
<h2>{{ $title }}</h2>
<button type="button" onclick="this.closest('dialog').close()" aria-label="Đóng">×</button>
</header>{{ $slot }}</dialog>
