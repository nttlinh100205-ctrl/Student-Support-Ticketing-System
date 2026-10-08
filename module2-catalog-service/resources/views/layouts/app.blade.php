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


</body>
</html>