
@props(['items'])

@if($items->hasPages())<div class="uni-pagination">{{ $items->withQueryString()->links() }}</div>
@endif
