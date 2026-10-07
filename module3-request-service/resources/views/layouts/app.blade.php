<!DOCTYPE html>
<html lang="vi" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Hỗ trợ Sinh viên — Module 3')</title>

    <!-- Google Fonts: Cormorant Garamond (Serif) & Manrope (Sans-serif) theo phong cách Nội Thất Tinh Hoa -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        cream: '#F3E9DC',
                        paper: '#FAF6F0',
                        card: '#FFFFFF',
                        wood: {
                            50: '#FAF7F4',
                            100: '#F4ECE4',
                            200: '#E6D8C8',
                            300: '#CBB4A0',
                            400: '#9B8B7E',
                            500: '#7A5A44',
                            600: '#5A4536',
                            700: '#3F2F24',
                            800: '#3A2E26',
                            900: '#261E18',
                        },
                        gold: {
                            50: '#FDFBF7',
                            100: '#FBF5E8',
                            200: '#F3E5C8',
                            300: '#E7CCA0',
                            400: '#D8B67B',
                            500: '#C29D62',
                            600: '#A88247',
                            700: '#8A6733',
                        },
                        ink: {
                            DEFAULT: '#3A2E26',
                            muted: '#7E7065',
                            light: '#A3968B',
                        },
                        borderWarm: '#E6D8C8',
                    },
                    fontFamily: {
                        serif: ['"Cormorant Garamond"', 'Georgia', 'serif'],
                        sans: ['Manrope', '-apple-system', 'BlinkMacSystemFont', 'sans-serif'],
                    },
                    boxShadow: {
                        warm: '0 8px 24px rgba(58, 46, 38, 0.05)',
                        'warm-lg': '0 12px 32px rgba(58, 46, 38, 0.08)',
                        'warm-sm': '0 2px 8px rgba(58, 46, 38, 0.04)',
                    }
                }
            }
        }
    </script>

    <style>
        [x-cloak] { display: none !important; }
        body {
            font-family: 'Manrope', sans-serif;
            background-color: #FAF6F0;
            color: #3A2E26;
        }
        h1, h2, h3, .font-heading {
            font-family: 'Cormorant Garamond', Georgia, serif;
        }
        .urgent-row {
            background: linear-gradient(90deg, #FFF5F5 0%, #FFFFFF 45%);
            border-left: 3px solid #D9534F;
        }
        .high-row {
            background: linear-gradient(90deg, #FFF9F2 0%, #FFFFFF 45%);
            border-left: 3px solid #D97706;
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 7px;
            height: 7px;
        }
        ::-webkit-scrollbar-track {
            background: #F4ECE4;
        }
        ::-webkit-scrollbar-thumb {
            background: #CBB4A0;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #9B8B7E;
        }
    </style>
</head>
<body class="bg-paper text-ink antialiased min-h-screen flex flex-col">
    <div class="min-h-screen flex flex-col md:flex-row flex-1">
        
        {{-- Sidebar lấy cảm hứng từ thanh điều hướng Nội Thất Tinh Hoa --}}
        <aside class="w-full md:w-64 bg-white border-b md:border-b-0 md:border-r border-borderWarm flex flex-col shrink-0 shadow-warm">
            {{-- Brand Logo & Title --}}
            <div class="p-5 border-b border-borderWarm/70 bg-gradient-to-b from-[#FAF6F0] to-white">
                <a href="{{ route('requests.index') }}" class="flex items-center gap-3.5 group">
                    <div class="w-11 h-11 rounded-xl bg-wood-800 border border-gold-500/40 flex items-center justify-center text-gold-400 font-serif font-bold text-lg shadow-warm transition-transform group-hover:scale-105">
                        <span class="tracking-widest">HT</span>
                    </div>
                    <div class="min-w-0">
                        <h1 class="font-serif font-bold text-wood-800 text-lg leading-tight tracking-wide group-hover:text-gold-600 transition-colors">
                            Hỗ Trợ Sinh Viên
                        </h1>
                        <p class="text-[11px] font-medium text-ink-muted tracking-wider uppercase">
                            Dịch Vụ Yêu Cầu · Mod 3
                        </p>
                    </div>
                </a>
            </div>

            {{-- Main Navigation --}}
            <nav class="flex-1 p-3.5 space-y-1.5">
                <p class="px-3 py-1.5 text-[10px] font-bold text-ink-muted uppercase tracking-widest font-mono">
                    Danh mục thao tác
                </p>

                <a href="{{ route('requests.index') }}"
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200
                          {{ request()->routeIs('requests.index') || request()->routeIs('requests.show')
                              ? 'bg-cream text-wood-800 font-semibold shadow-warm-sm border border-gold-500/30'
                              : 'text-ink-muted hover:text-wood-800 hover:bg-wood-50' }}">
                    <svg class="w-4 h-4 text-gold-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <span>Danh sách yêu cầu</span>
                </a>

                @if(($user['role'] ?? '') === 'student')
                <a href="{{ route('requests.create') }}"
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200
                          {{ request()->routeIs('requests.create')
                              ? 'bg-cream text-wood-800 font-semibold shadow-warm-sm border border-gold-500/30'
                              : 'text-ink-muted hover:text-wood-800 hover:bg-wood-50' }}">
                    <svg class="w-4 h-4 text-gold-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Tạo yêu cầu mới</span>
                </a>
                @endif
            </nav>

            {{-- Role switcher mô phỏng phong cách VIP member badge --}}
            <div class="p-3.5 border-t border-borderWarm bg-wood-50/60">
                <div class="flex items-center justify-between px-2 mb-2">
                    <p class="text-[10px] font-bold text-ink-muted uppercase tracking-widest font-mono">
                        Chuyển vai trò (Test)
                    </p>
                    <span class="w-1.5 h-1.5 rounded-full bg-gold-500 animate-pulse"></span>
                </div>

                <form method="POST" action="{{ route('requests.switch-role') }}" class="grid grid-cols-2 gap-1.5 md:block md:space-y-1">
                    @csrf
                    @foreach($demoUsers ?? [] as $u)
                        <button type="submit" name="user_id" value="{{ $u['id'] }}"
                                class="w-full text-left px-2.5 py-2 rounded-xl text-xs transition-all duration-150 flex items-center gap-2.5
                                       {{ ($user['id'] ?? 0) === $u['id']
                                            ? 'bg-white text-wood-800 font-semibold shadow-warm-sm border border-gold-500/40 ring-1 ring-gold-200'
                                            : 'text-ink-muted hover:bg-white/80 hover:text-wood-800' }}">
                            <span class="w-6 h-6 rounded-lg flex items-center justify-center text-[10px] font-bold shrink-0 font-serif
                                {{ ($user['id'] ?? 0) === $u['id'] ? 'bg-wood-800 text-gold-300' : 'bg-wood-200 text-wood-700' }}">
                                {{ mb_substr($u['full_name'], 0, 1) }}
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-medium text-ink">{{ $u['full_name'] }}</span>
                                <span class="block text-[10px] text-ink-muted">
                                    {{ match($u['role']) {
                                        'student' => 'Sinh viên',
                                        'staff' => 'Cán bộ',
                                        'department_head' => 'Trưởng phòng TC-KT',
                                        'admin' => 'Quản trị viên',
                                        default => $u['role'],
                                    } }}
                                </span>
                            </span>
                        </button>
                    @endforeach
                </form>
            </div>
        </aside>

        {{-- Main Content Area --}}
        <main class="min-w-0 flex-1 overflow-auto bg-paper">
            {{-- Top Banner / Header Decor --}}
            <div class="h-1 bg-gradient-to-r from-wood-800 via-gold-500 to-wood-800"></div>

            <div class="max-w-6xl mx-auto p-4 sm:p-6 md:p-8">
                {{-- Flash Messages --}}
                @if(session('success'))
                    <div class="mb-5 rounded-2xl bg-[#F6FAF6] border border-[#CDE5CF] text-[#2D5A34] px-4 py-3 text-sm flex items-center gap-3 shadow-warm-sm">
                        <span class="w-6 h-6 rounded-full bg-[#E4F2E6] flex items-center justify-center shrink-0 text-[#2D5A34]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </span>
                        <div class="flex-1 font-medium">{{ session('success') }}</div>
                    </div>
                @endif
                @if(session('error'))
                    <div class="mb-5 rounded-2xl bg-[#FDF5F5] border border-[#F5D0D0] text-[#8C2828] px-4 py-3 text-sm flex items-center gap-3 shadow-warm-sm">
                        <span class="w-6 h-6 rounded-full bg-[#FAECEC] flex items-center justify-center shrink-0 text-[#8C2828]">
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
