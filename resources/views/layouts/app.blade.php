<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <meta name="csrf-token"
          content="{{ csrf_token() }}">

    <title>
        @yield('title', 'Hỗ trợ Sinh viên')
    </title>

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
                            800: '#3730a3',
                        }
                    },

                    fontFamily: {
                        sans: [
                            'Inter',
                            'system-ui',
                            'sans-serif'
                        ]
                    },

                    boxShadow: {
                        soft:
                            '0 2px 15px -3px rgba(0,0,0,0.07), 0 4px 6px -4px rgba(0,0,0,0.05)',
                    }
                }
            }
        }
    </script>

    {{-- Font --}}
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <style>
        [x-cloak] {
            display: none !important;
        }

        body {
            font-family: Inter, system-ui, sans-serif;
        }

        .urgent-row {
            background:
                linear-gradient(
                    90deg,
                    #fef2f2 0%,
                    #fff 40%
                );

            border-left:
                3px solid #ef4444;
        }

        .high-row {
            background:
                linear-gradient(
                    90deg,
                    #fff7ed 0%,
                    #fff 40%
                );

            border-left:
                3px solid #f97316;
        }
    </style>
</head>


<body class="bg-slate-100/80 text-slate-800 antialiased min-h-screen">

@php
    /*
    |--------------------------------------------------------------------------
    | Chuẩn hóa role
    |--------------------------------------------------------------------------
    |
    | Dữ liệu cũ có thể là:
    | admin / ADMIN
    | student / STUDENT
    | staff / STAFF
    | department_head / DEPARTMENT_HEAD
    |
    | Chuyển tất cả về chữ thường để kiểm tra thống nhất.
    |
    */

    $currentRole = strtolower(
        $user['role'] ?? ''
    );
@endphp


<div class="min-h-screen flex">

    {{-- =====================================================
         SIDEBAR
    ====================================================== --}}
    <aside
        class="w-60 bg-white border-r border-slate-200/80
               flex flex-col shrink-0 shadow-soft">

        {{-- Logo --}}
        <div class="p-5 border-b border-slate-100">

            <div class="flex items-center gap-3">

                <div
                    class="w-10 h-10 rounded-xl
                           bg-gradient-to-br
                           from-brand-500 to-brand-700
                           flex items-center justify-center
                           text-white font-bold text-sm
                           shadow-md shadow-brand-500/30">

                    SV

                </div>


                <div>

                    <h1
                        class="font-semibold text-slate-900
                               text-sm leading-tight">

                        Hỗ trợ Sinh viên

                    </h1>

                    <p
                        class="text-[11px]
                               text-slate-400
                               font-medium">

                        Request Service

                    </p>

                </div>

            </div>

        </div>


        {{-- =================================================
             MENU
        ================================================== --}}
        <nav class="flex-1 p-3 space-y-1 overflow-y-auto">

            <p
                class="px-3 py-2
                       text-[10px]
                       font-semibold
                       text-slate-400
                       uppercase
                       tracking-wider">

                Menu

            </p>


            {{-- ===============================
                 DANH SÁCH YÊU CẦU
            ================================ --}}
            <a
                href="{{ route('requests.index') }}"

                class="flex items-center gap-3
                       px-3 py-2.5
                       rounded-xl
                       text-sm
                       font-medium
                       transition-all

                       {{
                            request()->routeIs('requests.index')
                            || request()->routeIs('requests.show')

                            ? 'bg-brand-50 text-brand-700 shadow-sm'
                            : 'text-slate-600 hover:bg-slate-50'
                       }}">

                <svg
                    class="w-5 h-5 opacity-70"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24">

                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.8"

                        d="
                        M9 5H7
                        a2 2 0 00-2 2
                        v12
                        a2 2 0 002 2
                        h10
                        a2 2 0 002-2
                        V7
                        a2 2 0 00-2-2
                        h-2
                        M9 5
                        a2 2 0 002 2
                        h2
                        a2 2 0 002-2
                        M9 5
                        a2 2 0 012-2
                        h2
                        a2 2 0 012 2
                        "
                    />

                </svg>

                Danh sách yêu cầu

            </a>


            {{-- ===============================
                 SINH VIÊN
            ================================ --}}
            @if($currentRole === 'student')

                <a
                    href="{{ route('requests.create') }}"

                    class="flex items-center gap-3
                           px-3 py-2.5
                           rounded-xl
                           text-sm
                           font-medium
                           transition-all

                           {{
                                request()->routeIs(
                                    'requests.create'
                                )

                                ? 'bg-brand-50 text-brand-700 shadow-sm'
                                : 'text-slate-600 hover:bg-slate-50'
                           }}">

                    <svg
                        class="w-5 h-5 opacity-70"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24">

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M12 4v16m8-8H4"
                        />

                    </svg>

                    Tạo yêu cầu mới

                </a>

            @endif


            {{-- =================================================
                 ADMIN - DANH MỤC & TỔ CHỨC
            ================================================== --}}
            {{-- 
                Không dùng @if($currentRole === 'admin') ở đây.
                Quyền ADMIN của các trang module 2 đang được xác thực
                bằng Sanctum token lưu trong localStorage.
            --}}
            <div id="adminCatalogMenu" class="hidden space-y-1">

                <p
                    class="px-3 pt-6 pb-2
                           text-[10px]
                           font-semibold
                           text-slate-400
                           uppercase
                           tracking-wider">

                    Danh mục & tổ chức

                </p>


                {{-- PHÒNG BAN --}}
                <a
                    href="{{ url('/admin/departments') }}"

                    class="flex items-center gap-3
                           px-3 py-2.5
                           rounded-xl
                           text-sm
                           font-medium
                           transition-all
                           {{
                                request()->is('admin/departments')
                                ? 'bg-brand-50 text-brand-700 shadow-sm'
                                : 'text-slate-600 hover:bg-slate-50'
                           }}">

                    <svg
                        class="w-5 h-5 opacity-70"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24">

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M3 21h18M5 21V5a2 2 0 012-2h10a2 2 0 012 2v16M9 8h2m2 0h2M9 12h2m2 0h2M9 16h2m2 0h2"
                        />

                    </svg>

                    Phòng ban

                </a>


                {{-- LOẠI HỖ TRỢ --}}
                <a
                    href="{{ url('/admin/support-types') }}"

                    class="flex items-center gap-3
                           px-3 py-2.5
                           rounded-xl
                           text-sm
                           font-medium
                           transition-all
                           {{
                                request()->is('admin/support-types')
                                ? 'bg-brand-50 text-brand-700 shadow-sm'
                                : 'text-slate-600 hover:bg-slate-50'
                           }}">

                    <svg
                        class="w-5 h-5 opacity-70"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24">

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M4 6h16M4 12h16M4 18h16"
                        />

                    </svg>

                    Loại hỗ trợ

                </a>


                {{-- CÁN BỘ THEO PHÒNG BAN --}}
                <a
                    href="{{ url('/admin/department-staff') }}"

                    class="flex items-center gap-3
                           px-3 py-2.5
                           rounded-xl
                           text-sm
                           font-medium
                           transition-all
                           {{
                                request()->is('admin/department-staff')
                                ? 'bg-brand-50 text-brand-700 shadow-sm'
                                : 'text-slate-600 hover:bg-slate-50'
                           }}">

                    <svg
                        class="w-5 h-5 opacity-70"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24">

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7M7 20H2v-2a3 3 0 015.356-1.857M15 7a3 3 0 11-6 0 3 3 0 016 0z"
                        />

                    </svg>

                    Cán bộ theo phòng ban

                </a>

            </div>


            {{-- =================================================
             ROLE SWITCHER - TEST
        ================================================== --}}
        <div
            class="p-3 border-t border-slate-100
                   bg-slate-50/50">

            <p
                class="px-2 mb-2
                       text-[10px]
                       font-semibold
                       text-slate-400
                       uppercase
                       tracking-wider">

                Đổi vai trò (test)

            </p>


            <form
                method="POST"
                action="{{ route('requests.switch-role') }}"
                class="space-y-0.5">

                @csrf


                @foreach($demoUsers ?? [] as $u)

                    <button
                        type="submit"
                        name="user_id"
                        value="{{ $u['id'] }}"

                        class="w-full text-left
                               px-3 py-2
                               rounded-lg
                               text-sm
                               transition
                               flex items-center
                               gap-2

                               {{
                                    ($user['id'] ?? 0)
                                    === $u['id']

                                    ? 'bg-white text-brand-700 font-medium shadow-sm ring-1 ring-brand-100'
                                    : 'text-slate-600 hover:bg-white/80'
                               }}">

                        <span
                            class="w-6 h-6
                                   rounded-full
                                   flex items-center
                                   justify-center
                                   text-[10px]
                                   font-bold
                                   shrink-0

                                   {{
                                        ($user['id'] ?? 0)
                                        === $u['id']

                                        ? 'bg-brand-100 text-brand-700'
                                        : 'bg-slate-200 text-slate-500'
                                   }}">

                            {{ mb_substr(
                                $u['full_name'],
                                0,
                                1
                            ) }}

                        </span>


                        <span class="min-w-0 flex-1">

                            <span
                                class="block truncate text-[13px]">

                                {{ $u['full_name'] }}

                            </span>


                            <span
                                class="text-[10px]
                                       text-slate-400">

                                {{
                                    match(
                                        strtolower(
                                            $u['role'] ?? ''
                                        )
                                    ) {
                                        'student'
                                            => 'Sinh viên',

                                        'staff'
                                            => 'Cán bộ',

                                        'department_head'
                                            => 'Trưởng phòng',

                                        'admin'
                                            => 'Admin',

                                        default
                                            => $u['role'] ?? '',
                                    }
                                }}

                            </span>

                        </span>

                    </button>

                @endforeach

            </form>

        </div>

    </aside>


    {{-- =====================================================
         MAIN CONTENT
    ====================================================== --}}
    <main class="flex-1 overflow-auto">

        <div
            class="max-w-6xl
                   mx-auto
                   p-6 md:p-8">


            {{-- Thành công --}}
            @if(session('success'))

                <div
                    class="mb-5
                           rounded-xl
                           bg-emerald-50
                           border
                           border-emerald-200/80
                           text-emerald-800
                           px-4 py-3
                           text-sm
                           flex items-center
                           gap-2
                           shadow-sm">

                    <svg
                        class="w-5 h-5
                               text-emerald-500
                               shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24">

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 13l4 4L19 7"
                        />

                    </svg>

                    {{ session('success') }}

                </div>

            @endif


            {{-- Lỗi --}}
            @if(session('error'))

                <div
                    class="mb-5
                           rounded-xl
                           bg-rose-50
                           border
                           border-rose-200/80
                           text-rose-700
                           px-4 py-3
                           text-sm
                           flex items-center
                           gap-2
                           shadow-sm">

                    <svg
                        class="w-5 h-5
                               text-rose-500
                               shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24">

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"

                            d="
                            M12 8v4
                            m0 4h.01
                            M21 12
                            a9 9 0 11-18 0
                            9 9 0 0118 0z
                            "
                        />

                    </svg>

                    {{ session('error') }}

                </div>

            @endif


            @yield('content')

        </div>

    </main>

</div>


{{-- Alpine JS --}}
<script
    defer
    src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js">
</script>


<script>
document.addEventListener('DOMContentLoaded', async function () {
    const adminMenu = document.getElementById('adminCatalogMenu');

    if (!adminMenu) {
        return;
    }

    const token = localStorage.getItem('access_token');

    if (!token) {
        return;
    }

    try {
        const response = await fetch('/api/v1/auth/me', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${token}`
            }
        });

        if (!response.ok) {
            return;
        }

        const result = await response.json();
        const role = String(result.user?.role ?? '').toUpperCase();

        if (role === 'ADMIN') {
            adminMenu.classList.remove('hidden');
        }
    } catch (error) {
        console.error('Không thể kiểm tra quyền ADMIN:', error);
    }
});
</script>

</body>
</html>