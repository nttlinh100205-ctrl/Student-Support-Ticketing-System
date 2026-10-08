
@props(['title' => null, 'subtitle' => null])
<section {{ $attributes->merge(['class' => 'uni-card']) }}>
@if($title)<header class="uni-card-heading">
<div>
<h2>{{ $title }}</h2>
@if($subtitle)<p>{{ $subtitle }}</p>
@endif</div>{{ $actions ?? '' }}</header>
@endif{{ $slot }}</section>
