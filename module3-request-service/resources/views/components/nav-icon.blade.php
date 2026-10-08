@props(['name'=>'requests'])
@php
$paths = [
'home'=>'M3 10 12 3l9 7M5 9v12h14V9M9 21v-7h6v7',
'catalog'=>'M3 21h18M5 21V7l7-4 7 4v14M9 8h1m4 0h1M9 12h1m4 0h1M10 21v-5h4v5',
'requests'=>'M5 4h14v6a2 2 0 0 0 0 4v6H5v-6a2 2 0 0 0 0-4V4M9 8h6M9 12h3M9 16h6',
'news'=>'M5 4h15v15a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8h2M5 4v15M9 8h7M9 12h7M9 16h3',
'reports'=>'M4 3v18h17M8 17v-4M13 17V7M18 17v-7',
'accounts'=>'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M22 21v-2a4 4 0 0 0-3-3.87M16 3a4 4 0 0 1 0 8M13 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0',
'create'=>'M12 5v14M5 12h14',
'check'=>'M9 12l2 2 4-4M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0',
'clock'=>'M12 8v4l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0',
'star'=>'m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9L12 3Z',
'kanban'=>'M3 4h18v16H3V4M9 4v16M15 4v16M5 8h2M11 8h2M17 8h2M5 12h2M11 12h2',
'settings'=>'M4 7h16M4 17h16M8 4v6M16 14v6',
'file'=>'M14 2H5v20h14V7l-5-5ZM14 2v6h5M8 12h8M8 16h8',
'help'=>'M9.1 9a3 3 0 0 1 5.8 1c0 2-3 2-3 4M12 17h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0',
];
@endphp
<svg {{ $attributes->merge(['class'=>'uni-nav-icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="{{ $paths[$name] ?? $paths['requests'] }}"/></svg>
