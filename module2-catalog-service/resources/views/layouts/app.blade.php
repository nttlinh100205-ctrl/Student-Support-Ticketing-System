<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Hỗ trợ Sinh viên')</title>

    {{-- Tailwind CSS --}}
    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            200: '#c7d2fe',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3'
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif']
                    },
                    boxShadow: {
                        soft: '0 2px 15px -3px rgba(0,0,0,0.07), 0 4px 6px -4px rgba(0,0,0,0.05)'
                    }
                }
            }
        };
    </script>

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <style>
        [x-cloak],
        [hidden] {
            display: none !important;
        }

        body {
            font-family: Inter, system-ui, sans-serif;
        }

        .urgent-row {
            background: linear-gradient(90deg, #fef2f2 0%, #fff 40%);
            border-left: 3px solid #ef4444;
        }

        .high-row {
            background: linear-gradient(90deg, #fff7ed 0%, #fff 40%);
            border-left: 3px solid #f97316;
        }
    </style>
    <script>
        // Identity supplied by the authenticated account session.
        window.CatalogAuth = {
            user: () => window.AccountUser || {},
            headers: () => window.AccountHeaders(),
        };
    </script>
<link rel="stylesheet" href="/css/suite.css">
</head>

<body class="suite-ui bg-slate-100/80 text-slate-800 antialiased min-h-screen">
@include('partials.account')

<div class="suite-shell min-h-screen flex">
    {{-- SIDEBAR --}}
    <aside class="suite-sidebar w-60 bg-white border-r border-slate-200/80 flex flex-col shrink-0 shadow-soft">

        {{-- Logo --}}
        <div class="p-5 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 flex items-center justify-center text-white font-bold text-sm shadow-md shadow-brand-500/30">
                    SV
                </div>

                <div>
                    <h1 class="font-semibold text-slate-900 text-sm leading-tight">
                        Hỗ trợ Sinh viên
                    </h1>
                    <p class="text-[11px] text-slate-400 font-medium">
                        DANH MỤC & TỔ CHỨC
                    </p>
                </div>
            </div>
        </div>

        {{-- MENU --}}
        <nav class="flex-1 p-3 space-y-1 overflow-y-auto">
            <p class="px-3 py-2 text-[10px] font-semibold text-slate-400 uppercase tracking-wider">
                Sinh viên
            </p>

            {{-- Tra cứu trước khi gửi yêu cầu --}}
            <a href="{{ route('catalog.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all
                      {{ request()->routeIs('catalog.index')
                          ? 'bg-brand-50 text-brand-700 shadow-sm'
                          : 'text-slate-600 hover:bg-slate-50' }}">
                <svg class="w-5 h-5 opacity-70 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"/>
                </svg>
                Tra cứu hỗ trợ
            </a>

            {{-- Menu Admin: chỉ hiện khi vai trò hiện tại là admin --}}
            <div id="adminCatalogMenu" class="hidden space-y-1">
                <p class="px-3 pt-6 pb-2 text-[10px] font-semibold text-slate-400 uppercase tracking-wider">
                    Danh mục & tổ chức
                </p>

                @foreach([
                    ['admin/departments', 'admin.departments', 'Phòng ban', 'M3 21h18M5 21V5a2 2 0 012-2h10a2 2 0 012 2v16M9 8h2m2 0h2M9 12h2m2 0h2M9 16h2m2 0h2'],
                    ['admin/support-types', 'admin.support-types', 'Loại hỗ trợ & SLA', 'M4 6h16M4 12h16M4 18h16'],
                    ['admin/support-type-fields', 'admin.support-type-fields', 'Biểu mẫu theo loại', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                    ['admin/department-staff', 'admin.department-staff', 'Cán bộ theo phòng ban', 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7M7 20H2v-2a3 3 0 015.356-1.857M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                    ['admin/faqs', 'admin.faqs', 'Câu hỏi thường gặp', 'M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3m.08 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                ] as [$path, $name, $label, $icon])
                    <a href="{{ route($name) }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all
                              {{ request()->is($path)
                                  ? 'bg-brand-50 text-brand-700 shadow-sm'
                                  : 'text-slate-600 hover:bg-slate-50' }}">
                        <svg class="w-5 h-5 opacity-70 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $icon }}"/>
                        </svg>
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </nav>

        <div class="suite-sidebar-footer"><strong>Danh mục & tổ chức</strong>Chuẩn hóa thông tin để mỗi yêu cầu được chuyển đến đúng nơi.</div>
    </aside>

    {{-- NỘI DUNG TRANG --}}
    <main class="suite-main catalog-main flex-1 min-w-0 overflow-auto">
        <div class="max-w-6xl mx-auto p-6 md:p-8">
            @if(session('success'))
                <div class="mb-5 rounded-xl bg-emerald-50 border border-emerald-200/80 text-emerald-800 px-4 py-3 text-sm flex items-center gap-2 shadow-sm">
                    <svg class="w-5 h-5 text-emerald-500 shrink-0"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24"
                         aria-hidden="true">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 13l4 4L19 7"/>
                    </svg>

                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-5 rounded-xl bg-rose-50 border border-rose-200/80 text-rose-700 px-4 py-3 text-sm flex items-center gap-2 shadow-sm">
                    <svg class="w-5 h-5 text-rose-500 shrink-0"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24"
                         aria-hidden="true">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>

                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </div>
    </main>
</div>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const current = CatalogAuth.user();

    if (current.role === 'admin') {
        document.getElementById('adminCatalogMenu').classList.remove('hidden');
    }

});
</script>
</body>
</html>