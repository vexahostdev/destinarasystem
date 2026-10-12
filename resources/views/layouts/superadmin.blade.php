<!DOCTYPE html>
<html lang="id" class="h-full bg-[#fbf8f5]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Kontrol Ekosistem Super Admin') — DESTINARA</title>

    <!-- Favicon Lengkap Resmi Destinara -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#4A1E1E">

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
        .rgs-card-wine {
            border-top: 4px solid #4A1E1E;
            border-radius: 1.25rem;
        }
        .rgs-btn-primary {
            background-color: #703A3A;
            color: #ffffff;
            border-radius: 9999px;
            transition: all 0.2s ease;
        }
        .rgs-btn-primary:hover {
            background-color: #4A1E1E;
        }
        .rgs-btn-dark {
            background-color: #4A1E1E;
            color: #ffffff;
            border-radius: 9999px;
            transition: all 0.2s ease;
        }
        .rgs-btn-dark:hover {
            background-color: #311313;
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

    <!-- Sidebar Kiri Tetap Super Admin (Fixed Desktop w-64, Drawer on Mobile) -->
    <aside :class="mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
           class="fixed inset-y-0 left-0 z-50 w-64 bg-[#240e0e] border-r border-[#3d1818] flex flex-col transition-transform duration-300 ease-in-out shadow-lg text-white">
        
        <!-- Sidebar Header & Logo -->
        <div class="h-16 px-5 border-b border-[#3d1818] flex items-center justify-between shrink-0 bg-[#1c0a0a]">
            <a href="{{ route('superadmin.dashboard') }}" class="flex items-center gap-3">
                <img src="{{ asset('assets/img/logo-mark-tight.png') }}" alt="Logo Destinara" class="h-9 w-auto object-contain flex-shrink-0 drop-shadow-sm">
                <div class="flex flex-col">
                    <span class="font-serif text-lg font-bold tracking-tight text-white leading-none">DESTINARA</span>
                    <span class="text-[9px] tracking-wider uppercase font-semibold text-[#fca5a5]">Super Admin Ekosistem</span>
                </div>
            </a>
            <!-- Mobile Close Button -->
            <button @click="mobileSidebarOpen = false" class="md:hidden text-[#d6b4b4] hover:text-white p-1">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>

        <!-- Master Security Otoritas Card -->
        <div class="p-4 border-b border-[#3d1818] bg-[#1a0808]">
            <div class="flex items-center gap-2 text-[11px] font-semibold text-[#fca5a5]">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>Otoritas Root Level Tier 1</span>
            </div>
            <div class="text-[10px] text-[#d6b4b4] mt-0.5">Dewan Pengawas & Direksi</div>
        </div>

        <!-- Navigasi Menu Vertikal Super Admin -->
        <nav class="flex-1 px-3 py-2 space-y-1 overflow-y-auto">
            <div class="px-3 pb-2 text-[10px] font-bold uppercase tracking-wider text-[#d6b4b4]/60">
                Eksekutif & Tata Kelola
            </div>

            <a href="{{ route('superadmin.dashboard') }}" 
               class="flex items-center gap-3 px-3.5 py-2.5 text-xs font-bold tracking-wide rounded-full transition-all {{ request()->routeIs('superadmin.dashboard') ? 'text-white bg-[#f87171]/20 border border-[#f87171]/40 shadow-xs' : 'text-[#d6b4b4] hover:text-white hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[18px] {{ request()->routeIs('superadmin.dashboard') ? 'text-[#fca5a5]' : 'text-[#d6b4b4]' }}">tune</span>
                <span>Kontrol Ekosistem</span>
            </a>

            <a href="{{ route('superadmin.settings') }}" 
               class="flex items-center gap-3 px-3.5 py-2.5 text-xs font-bold tracking-wide rounded-full transition-all {{ request()->routeIs('superadmin.settings*') ? 'text-white bg-[#f87171]/20 border border-[#f87171]/40 shadow-xs' : 'text-[#d6b4b4] hover:text-white hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[18px] {{ request()->routeIs('superadmin.settings*') ? 'text-[#fca5a5]' : 'text-[#d6b4b4]' }}">settings</span>
                <span>Pengaturan Master</span>
            </a>

            <a href="{{ route('superadmin.users') }}" 
               class="flex items-center gap-3 px-3.5 py-2.5 text-xs font-bold tracking-wide rounded-full transition-all {{ request()->routeIs('superadmin.users*') ? 'text-white bg-[#f87171]/20 border border-[#f87171]/40 shadow-xs' : 'text-[#d6b4b4] hover:text-white hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[18px] {{ request()->routeIs('superadmin.users*') ? 'text-[#fca5a5]' : 'text-[#d6b4b4]' }}">shield</span>
                <span>Akses & Tindakan</span>
            </a>

            <a href="{{ route('superadmin.analytics') }}" 
               class="flex items-center gap-3 px-3.5 py-2.5 text-xs font-bold tracking-wide rounded-full transition-all {{ request()->routeIs('superadmin.analytics*') ? 'text-white bg-[#f87171]/20 border border-[#f87171]/40 shadow-xs' : 'text-[#d6b4b4] hover:text-white hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[18px] {{ request()->routeIs('superadmin.analytics*') ? 'text-[#fca5a5]' : 'text-[#d6b4b4]' }}">monitoring</span>
                <span>Analitik Bisnis</span>
            </a>

            <a href="{{ route('superadmin.reconciliation') }}" 
               class="flex items-center gap-3 px-3.5 py-2.5 text-xs font-bold tracking-wide rounded-full transition-all {{ request()->routeIs('superadmin.reconciliation*') ? 'text-white bg-[#f87171]/20 border border-[#f87171]/40 shadow-xs' : 'text-[#d6b4b4] hover:text-white hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[18px] {{ request()->routeIs('superadmin.reconciliation*') ? 'text-[#fca5a5]' : 'text-[#d6b4b4]' }}">account_balance</span>
                <span>Rekonsiliasi & Audit</span>
            </a>
        </nav>

        <!-- Sidebar Footer: Profil Dewan Direksi & Logout -->
        <div class="p-3 border-t border-[#3d1818] bg-[#1a0808] shrink-0">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-8 h-8 shrink-0 rounded-full bg-[#4A1E1E] border border-[#6b2c2c] text-white flex items-center justify-center text-xs font-bold">
                        SP
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-xs font-bold text-white truncate">Dr. Ir. Suryadi Pratama</span>
                        <span class="text-[10px] text-[#fca5a5] font-medium truncate">Dewan Direksi</span>
                    </div>
                </div>
                <a href="{{ route('login') }}" title="Keluar ke Portal Masuk" class="p-2 text-[#d6b4b4] hover:text-white hover:bg-white/5 rounded-full transition-colors">
                    <span class="material-symbols-outlined text-[18px]">logout</span>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content Area (Berada di kanan Sidebar, margin kiri w-64 pada desktop) -->
    <div class="flex-1 md:pl-64 flex flex-col min-w-0 min-h-screen">
        
        <!-- Topbar 1 Baris Ringkas & Bersih Super Admin -->
        <header class="sticky top-0 z-30 bg-[#ffffff] border-b border-[#2B211E]/12 shadow-xs h-16 flex items-center justify-between px-4 sm:px-6 lg:px-8">
            <!-- Left: Mobile Menu Toggle & Context Breadcrumb -->
            <div class="flex items-center gap-3">
                <button @click="mobileSidebarOpen = true" class="md:hidden p-2 text-[#2B211E]/70 hover:text-[#2B211E] border border-[#2B211E]/15">
                    <span class="material-symbols-outlined text-[20px]">menu</span>
                </button>
                <div class="flex items-center gap-2 text-xs text-[#2B211E]/70">
                    <span class="font-serif font-semibold text-[#4A1E1E]">Dewan Eksekutif</span>
                    <span>/</span>
                    <span class="font-medium text-[#2B211E]">@yield('title', 'Kontrol Ekosistem')</span>
                </div>
            </div>

            <!-- Right: Node Health Indicator & Fast Audit Link -->
            <div class="flex items-center gap-3">
                <div class="hidden sm:flex items-center gap-2 px-2.5 py-1 bg-[#4A1E1E]/5 border border-[#4A1E1E]/20 text-[11px] font-semibold text-[#4A1E1E]">
                    <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                    <span>Semua Sistem Normal • 100% Uptime</span>
                </div>

                <a href="{{ route('superadmin.reconciliation') }}" class="rgs-btn-dark px-3 py-1.5 text-xs font-semibold uppercase tracking-wider flex items-center gap-1.5 shadow-xs">
                    <span class="material-symbols-outlined text-[16px]">verified</span>
                    <span class="hidden sm:inline">Buka Buku Kas Escrow</span>
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

        @if(session('warning'))
            <div class="bg-[#b87a38] text-white px-4 py-3 border-b border-[#925f28]">
                <div class="max-w-7xl mx-auto flex items-center justify-between text-xs sm:text-sm">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">warning</span>
                        <span>{{ session('warning') }}</span>
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

        <!-- Footer Archival Super Admin -->
        <footer class="mt-auto border-t border-[#2B211E]/12 bg-[#ffffff] py-4 text-xs text-[#2B211E]/60">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="font-serif font-bold text-[#4A1E1E]">DESTINARA EXECUTIVE</span>
                    <span>— Tata Kelola Strategis & Rekonsiliasi Ekosistem Nusantara</span>
                </div>
                <div class="flex items-center gap-4 text-[11px]">
                    <span>Enkripsi Database: AES-256</span>
                    <span>•</span>
                    <span>Audit Finansial Terakreditasi</span>
                </div>
            </div>
        </footer>
    </div>

</body>
</html>
