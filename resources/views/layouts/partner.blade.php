<!DOCTYPE html>
<html lang="id" class="h-full bg-[#fbf8f5]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Portal Mitra Pengelola') — DESTINARA</title>

    <!-- Favicon Lengkap Resmi Destinara -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#703A3A">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..24,400,0..1,0" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .rgs-card {
            background-color: #ffffff;
            border-radius: 1.25rem;
            border: 1px solid rgba(0, 0, 0, 0.06);
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.04);
        }
        .rgs-card-accent {
            border-top: 4px solid #51634b;
            border-radius: 1.25rem;
        }
        .rgs-btn-primary {
            background-color: #51634b;
            color: #ffffff;
            border-radius: 9999px;
            transition: all 0.2s ease;
        }
        .rgs-btn-primary:hover {
            background-color: #3f4e3a;
        }
        .rgs-btn-outline {
            border: 1px solid rgba(0, 0, 0, 0.12);
            border-radius: 9999px;
            color: #1E1B1B;
            background-color: transparent;
            transition: all 0.2s ease;
        }
        .rgs-btn-outline:hover {
            background-color: rgba(0, 0, 0, 0.04);
        }
    </style>
</head>
<body class="min-h-full bg-[#fbf8f5] text-[#2B211E] antialiased flex" x-data="{ mobileSidebarOpen: false }">

    <!-- Mobile Sidebar Backdrop -->
    <div x-show="mobileSidebarOpen" 
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="mobileSidebarOpen = false" 
         class="fixed inset-0 z-40 bg-[#2B211E]/60 backdrop-blur-xs md:hidden" 
         style="display: none;"></div>

    <!-- Sidebar Kiri Tetap Mitra (Fixed Desktop w-64, Drawer on Mobile) -->
    <aside :class="mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
           class="fixed inset-y-0 left-0 z-50 w-64 bg-[#ffffff] border-r border-[#2B211E]/12 flex flex-col transition-transform duration-300 ease-in-out shadow-xs">
        
        <!-- Sidebar Header & Logo -->
        <div class="h-16 px-5 border-b border-[#2B211E]/12 flex items-center justify-between shrink-0">
            <a href="{{ route('mitra.dashboard') }}" class="flex items-center gap-3">
                <img src="{{ asset('assets/img/logo-mark-tight.png') }}" alt="Logo Destinara" class="h-9 w-auto object-contain flex-shrink-0 drop-shadow-xs">
                <div class="flex flex-col">
                    <span class="font-serif text-lg font-bold tracking-tight text-[#2B211E] leading-none">DESTINARA</span>
                    <span class="text-[9px] tracking-wider uppercase font-semibold text-[#51634b]">Portal Pengelola Tapak</span>
                </div>
            </a>
            <!-- Mobile Close Button -->
            <button @click="mobileSidebarOpen = false" class="md:hidden text-[#2B211E]/60 hover:text-[#2B211E] p-1">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>

        <!-- Identitas Lembaga Adat Mitra Card -->
        <div class="m-3 p-3.5 rounded-2xl bg-[#51634b]/10 border border-[#51634b]/20">
            <div class="flex items-center gap-1.5 text-[10px] uppercase font-bold tracking-wider text-[#51634b] mb-1">
                <span class="material-symbols-outlined text-[14px]">verified</span>
                <span>Lembaga Adat</span>
            </div>
            <div class="text-xs font-bold text-[#1E1B1B] truncate">Desa Adat Penglipuran</div>
            <div class="text-[10px] text-gray-500 mt-0.5">Hak Adat 90% • Protokol FPIC</div>
        </div>

        <!-- Navigasi Menu Vertikal Mitra -->
        <nav class="flex-1 px-3 py-2 space-y-1 overflow-y-auto">
            <div class="px-3 pb-2 text-[10px] font-bold uppercase tracking-wider text-gray-400">
                Operasional Tapak
            </div>

            <a href="{{ route('mitra.dashboard') }}" 
               class="flex items-center gap-3 px-3.5 py-2.5 text-xs font-bold tracking-wide rounded-full transition-all {{ request()->routeIs('mitra.dashboard') ? 'text-white bg-[#51634b] shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                <span class="material-symbols-outlined text-[18px]">dashboard</span>
                <span>Dashboard</span>
            </a>

            <a href="{{ route('mitra.bookings') }}" 
               class="flex items-center gap-3 px-3.5 py-2.5 text-xs font-bold tracking-wide rounded-full transition-all {{ request()->routeIs('mitra.bookings*') ? 'text-white bg-[#51634b] shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                <span class="material-symbols-outlined text-[18px]">inbox</span>
                <span>Booking Masuk</span>
            </a>

            <a href="{{ route('mitra.availability') }}" 
               class="flex items-center gap-3 px-3.5 py-2.5 text-xs font-bold tracking-wide rounded-full transition-all {{ request()->routeIs('mitra.availability*') ? 'text-white bg-[#51634b] shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                <span class="material-symbols-outlined text-[18px]">calendar_month</span>
                <span>Kalender Slot</span>
            </a>

            <a href="{{ route('mitra.destinations') }}" 
               class="flex items-center gap-3 px-3.5 py-2.5 text-xs font-bold tracking-wide rounded-full transition-all {{ request()->routeIs('mitra.destinations*') ? 'text-white bg-[#51634b] shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                <span class="material-symbols-outlined text-[18px]">holiday_village</span>
                <span>Tapak Kelolaan</span>
            </a>

            <a href="{{ route('mitra.income') }}" 
               class="flex items-center gap-3 px-3.5 py-2.5 text-xs font-bold tracking-wide rounded-full transition-all {{ request()->routeIs('mitra.income*') ? 'text-white bg-[#51634b] shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                <span class="material-symbols-outlined text-[18px]">payments</span>
                <span>Pendapatan</span>
            </a>

            <a href="{{ route('mitra.profile') }}" 
               class="flex items-center gap-3 px-3.5 py-2.5 text-xs font-bold tracking-wide rounded-full transition-all {{ request()->routeIs('mitra.profile*') ? 'text-white bg-[#51634b] shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                <span class="material-symbols-outlined text-[18px]">badge</span>
                <span>Profil Adat</span>
            </a>
        </nav>

        <!-- Sidebar Footer: Profil Pengguna & Logout -->
        <div class="p-3 border-t border-gray-100 bg-[#FAF7F5] shrink-0">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-8 h-8 shrink-0 rounded-full bg-[#51634b] text-white flex items-center justify-center text-xs font-bold">
                        WS
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-xs font-bold text-[#1E1B1B] truncate">{{ Auth::user()->name ?? 'I Wayan Sudarma' }}</span>
                        <span class="text-[10px] text-[#51634b] font-medium truncate">Kelian Adat</span>
                    </div>
                </div>
                <a href="{{ route('login') }}" title="Keluar dari Portal" class="p-2 text-gray-400 hover:text-[#703A3A] hover:bg-white rounded-full transition-colors">
                    <span class="material-symbols-outlined text-[18px]">logout</span>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content Area (Berada di kanan Sidebar, margin kiri w-64 pada desktop) -->
    <div class="flex-1 md:pl-64 flex flex-col min-w-0 min-h-screen">
        
        <!-- Topbar 1 Baris Ringkas & Bersih -->
        <header class="sticky top-0 z-30 bg-[#ffffff] border-b border-[#2B211E]/12 shadow-xs h-16 flex items-center justify-between px-4 sm:px-6 lg:px-8">
            <!-- Left: Mobile Menu Toggle & Context Breadcrumb -->
            <div class="flex items-center gap-3">
                <button @click="mobileSidebarOpen = true" class="md:hidden p-2 text-[#2B211E]/70 hover:text-[#2B211E] border border-[#2B211E]/15">
                    <span class="material-symbols-outlined text-[20px]">menu</span>
                </button>
                <div class="flex items-center gap-2 text-xs text-[#2B211E]/70">
                    <span class="font-serif font-semibold text-[#51634b]">Portal Mitra Adat</span>
                    <span>/</span>
                    <span class="font-medium text-[#2B211E]">@yield('title', 'Dashboard')</span>
                </div>
            </div>

            <!-- Right: Saldo Siap Tarik & Quick Action -->
            <div class="flex items-center gap-3">
                <a href="{{ route('mitra.income') }}" class="flex items-center gap-1.5 px-3 py-1.5 bg-[#faf6f0] border border-[#2B211E]/15 text-xs hover:border-[#51634b] transition-colors">
                    <span class="text-[10px] text-[#2B211E]/60 uppercase font-bold">Siap Tarik:</span>
                    <span class="font-bold font-mono text-[#51634b]">Rp 1.417.500</span>
                </a>

                <a href="{{ route('mitra.availability') }}" class="rgs-btn-outline px-3.5 py-1.5 text-xs font-semibold uppercase tracking-wider flex items-center gap-1.5 shadow-xs">
                    <span class="material-symbols-outlined text-[16px]">edit_calendar</span>
                    <span class="hidden sm:inline">Atur Slot</span>
                </a>
            </div>
        </header>

        <!-- Flash Messages -->
        @if(session('success'))
            <div class="bg-[#51634b] text-white px-4 py-3 border-b border-[#3b4937]">
                <div class="max-w-7xl mx-auto flex items-center justify-between text-xs sm:text-sm">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">verified</span>
                        <span>{{ session('success') }}</span>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="text-white/80 hover:text-white">
                        <span class="material-symbols-outlined text-[16px]">close</span>
                    </button>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-[#8C5151] text-white px-4 py-3 border-b border-[#703A3A]">
                <div class="max-w-7xl mx-auto flex items-center justify-between text-xs sm:text-sm">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">warning</span>
                        <span>{{ session('error') }}</span>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="text-white/80 hover:text-white">
                        <span class="material-symbols-outlined text-[16px]">close</span>
                    </button>
                </div>
            </div>
        @endif

        <!-- Main Content Container -->
        <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
            @yield('content')
        </main>

        <!-- Footer Archival Terroir -->
        <footer class="mt-auto border-t border-[#2B211E]/12 bg-[#ffffff] py-4 text-xs text-[#2B211E]/60">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="font-serif font-bold text-[#51634b]">DESTINARA MITRA</span>
                    <span>— Tata Kelola Kemitraan Adat & Stasiun Lapangan Nusantara</span>
                </div>
                <div class="flex items-center gap-4 text-[11px]">
                    <span>Rekonsiliasi Payout: Setiap Pekan</span>
                    <span>•</span>
                    <span>Protokol Perlindungan Adat FPIC</span>
                </div>
            </div>
        </footer>
    </div>

</body>
</html>
