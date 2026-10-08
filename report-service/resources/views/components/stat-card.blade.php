
@props(['label', 'value' => '—', 'hint' => '', 'icon' => 'chart-simple', 'tone' => 'blue'])
<article {{ $attributes->merge(['class' => 'uni-stat tone-'.$tone]) }}>
<div>
<span>{{ $label }}</span>
<i class="fa-solid fa-{{ $icon }}" aria-hidden="true">
</i>
</div>
<strong>{{ $value }}</strong>
<small>{{ $hint }}</small>
</article>
