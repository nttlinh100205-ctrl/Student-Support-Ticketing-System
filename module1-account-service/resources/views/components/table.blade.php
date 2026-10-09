<div class="uni-table-scroll">
<table {{ $attributes->merge(['class' => 'uni-table']) }}>
<thead>{{ $head ?? '' }}</thead>
<tbody>{{ $slot }}</tbody>
</table>
</div>
