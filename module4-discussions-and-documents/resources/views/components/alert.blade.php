
@props(['type' => 'success'])
<div {{ $attributes->merge(['class' => 'uni-alert alert-'.$type, 'role' => $type === 'error' ? 'alert' : 'status']) }}>{{ $slot }}</div>
