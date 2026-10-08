<!DOCTYPE html>
<html lang="vi" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Hệ Thống Tiếp Nhận & Hỗ Trợ Sinh Viên')</title>

    <!-- Google Fonts: Plus Jakarta Sans & Inter (Chuẩn giao diện giáo dục trường đại học) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#EFF6FF',
                            100: '#DBEAFE',
                            200: '#BFDBFE',
                            300: '#93C5FD',
                            400: '#60A5FA',
                            500: '#3B82F6',
                            600: '#2563EB',
                            700: '#1D4ED8',
                            800: '#1E40AF',
                            900: '#1E3A8A',
                            950: '#172554',
                        },
                        school: {
                            bg: '#F8FAFC',
                            card: '#FFFFFF',
                            border: '#E2E8F0',
                            text: '#0F172A',
                            muted: '#64748B',
                            light: '#94A3B8',
                        }
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'Inter', 'system-ui', 'sans-serif'],
                    },
                    boxShadow: {
                        subtle: '0 1px 3px 0 rgba(15, 23, 42, 0.05), 0 1px 2px -1px rgba(15, 23, 42, 0.05)',
                        card: '0 4px 6px -1px rgba(15, 23, 42, 0.04), 0 2px 4px -2px rgba(15, 23, 42, 0.04)',
                        elevated: '0 10px 15px -3px rgba(15, 23, 42, 0.08), 0 4px 6px -4px rgba(15, 23, 42, 0.04)',
                    }
                }
            }
        }
    </script>

    <style>
        [x-cloak] { display: none !important; }
        body {
            font-family: 'Plus Jakarta Sans', Inter, sans-serif;
            background-color: #F8FAFC;
            color: #0F172A;
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #F1F5F9;
        }
        ::-webkit-scrollbar-thumb {
            background: #CBD5E1;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94A3B8;
        }
    </style>
<link rel="stylesheet" href="/css/suite.css">
<link rel="stylesheet" href="/css/requests.css">
</head>
<body class="suite-ui bg-slate-50 text-slate-900 antialiased min-h-screen flex flex-col">
@include('partials.account')
    <div class="suite-shell min-h-screen flex flex-col md:flex-row flex-1">
        
        {{-- Sidebar Cổng Thông Tin Trường Học (Xanh - Trắng) --}}
        <aside class="suite-sidebar w-full md:w-64 bg-white border-b md:border-b-0 md:border-r border-slate-200 flex flex-col shrink-0 shadow-subtle z-20">
            {{-- Brand Logo & University Title --}}
            <div class="p-5 border-b border-slate-100 bg-gradient-to-b from-blue-50/50 to-white">
                <a href="{{ route('requests.index') }}" class="flex items-center gap-3 group">
                    <div class="w-11 h-11 rounded-xl bg-gradient-to-tr from-primary-800 to-primary-600 flex items-center justify-center text-white shadow-md shadow-primary-500/25 transition-transform group-hover:scale-105">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14v7"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h1 class="font-bold text-slate-900 text-sm leading-tight tracking-tight group-hover:text-primary-700 transition-colors uppercase">
                            HỖ TRỢ SINH VIÊN
                        </h1>
                        <p class="text-[11px] font-semibold text-primary-600 tracking-wider">
                            CỔNG MỘT CỬA · MOD 3
                        </p>
                    </div>
                </a>
            </div>

            {{-- Main Navigation --}}
            <nav class="flex-1 p-3.5 space-y-1">
                @if(!config('account.fake'))
                <a href="{{ rtrim(config('account.url'), '/') }}" class="block px-3.5 py-2.5 rounded-xl text-sm font-semibold text-primary-700 bg-primary-50 mb-3">← Không gian làm việc</a>
                @endif
                <p class="px-3 py-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider font-mono">
                    Nghiệp vụ chính
                </p>

                <a href="{{ route('requests.index') }}"
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all duration-150
                          {{ request()->routeIs('requests.index') || request()->routeIs('requests.show')
                              ? 'bg-primary-50 text-primary-700 shadow-xs border border-primary-200'
                              : 'text-slate-600 hover:text-primary-700 hover:bg-slate-50' }}">
                    <svg class="w-4 h-4 text-primary-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <span>{{ match($user['role'] ?? '') { 'student' => 'Yêu cầu của tôi', 'staff' => 'Việc được giao', 'department_head' => 'Yêu cầu của phòng', default => 'Toàn bộ yêu cầu' } }}</span>
                </a>

                @if(($user['role'] ?? '') === 'student')
                <a href="{{ route('requests.create') }}"
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all duration-150
                          {{ request()->routeIs('requests.create')
                              ? 'bg-primary-50 text-primary-700 shadow-xs border border-primary-200'
                              : 'text-slate-600 hover:text-primary-700 hover:bg-slate-50' }}">
                    <svg class="w-4 h-4 text-primary-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Gửi yêu cầu mới</span>
                </a>
                @endif

                @if(in_array($user['role'] ?? '', ['admin', 'department_head'], true))
                <a href="{{ route('requests.export', request()->query()) }}"
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold text-slate-600 hover:text-emerald-700 hover:bg-emerald-50 transition-all duration-150 group">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Xuất báo cáo Excel</span>
                </a>
                @endif

            </nav>

            <div class="suite-sidebar-footer"><strong>Theo dõi đến khi hoàn tất</strong>Thông tin yêu cầu, trao đổi và lịch sử xử lý trong cùng một không gian.</div>
        </aside>

        {{-- Main Content Area --}}
        <main class="suite-main min-w-0 flex-1 overflow-auto bg-slate-50">
<div class="max-w-6xl mx-auto p-4 sm:p-6 md:p-8">
                {{-- Flash Messages --}}
                @if(session('success'))
                    <div class="mb-5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm flex items-center gap-3 shadow-subtle">
                        <span class="w-6 h-6 rounded-full bg-emerald-100 flex items-center justify-center shrink-0 text-emerald-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </span>
                        <div class="flex-1 font-medium">{{ session('success') }}</div>
                    </div>
                @endif
                @if(session('error'))
                    <div class="mb-5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 text-sm flex items-center gap-3 shadow-subtle">
                        <span class="w-6 h-6 rounded-full bg-rose-100 flex items-center justify-center shrink-0 text-rose-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <div class="flex-1 font-medium">{{ session('error') }}</div>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
