<!DOCTYPE html>
<html lang="id" class="h-full bg-[#fbf8f5]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Konsol Admin Operasional') — DESTINARA</title>

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
            border-top: 4px solid #703A3A;
            border-radius: 1.25rem;
        }
        .rgs-card-blue {
            border-top: 4px solid #2B3A4A;
            border-radius: 1.25rem;
        }
        .rgs-btn-primary {
            background-color: #703A3A;
            color: #ffffff;
            border-radius: 9999px;
            transition: all 0.2s ease;
        }
        .rgs-btn-primary:hover {
            background-color: #561D1E;
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

    <!-- Sidebar Kiri Tetap Admin (Fixed Desktop w-64, Drawer on Mobile) -->
    <aside :class="mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
           class="fixed inset-y-0 left-0 z-50 w-64 bg-[#231917] border-r border-[#382a26] flex flex-col transition-transform duration-300 ease-in-out shadow-lg text-white">
        
        <!-- Sidebar Header & Logo -->
        <div class="h-16 px-5 border-b border-[#382a26] flex items-center justify-between shrink-0 bg-[#1c1311]">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3">
                <img src="{{ asset('assets/img/logo-mark-tight.png') }}" alt="Logo Destinara" class="h-9 w-auto object-contain flex-shrink-0 drop-shadow-sm">
                <div class="flex flex-col">
                    <span class="font-serif text-lg font-bold tracking-tight text-white leading-none">DESTINARA</span>
                    <span class="text-[9px] tracking-wider uppercase font-semibold text-[#d4a373]">Admin Operasional</span>
                </div>
            </a>
            <!-- Mobile Close Button -->
            <button @click="mobileSidebarOpen = false" class="md:hidden text-[#a89a95] hover:text-white p-1">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>

        <!-- SLA Live Status Card -->
        <div class="p-4 border-b border-[#382a26] bg-[#1c1311]">
            <div class="flex items-center gap-2 text-[11px] font-semibold text-[#d4a373]">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>SLA: 2 Jam Kerja • Aktif</span>
            </div>
            <div class="text-[10px] text-[#a89a95] mt-0.5">Pusat Kendali Terpadu Lapangan</div>
        </div>

        <!-- Navigasi Menu Vertikal Admin -->
        <nav class="flex-1 px-3 py-2 space-y-1 overflow-y-auto">
            <div class="px-3 pb-2 text-[10px] font-bold uppercase tracking-wider text-stone-400">
                Pusat Kontrol
            </div>

            <a href="{{ route('admin.dashboard') }}" 
               class="flex items-center gap-3 px-3.5 py-2.5 text-xs font-bold tracking-wide rounded-full transition-all {{ request()->routeIs('admin.dashboard') ? 'text-white bg-[#b87a38] shadow-xs' : 'text-stone-300 hover:text-white hover:bg-white/10' }}">
                <span class="material-symbols-outlined text-[18px]">dashboard</span>
                <span>Dashboard</span>
            </a>

            <a href="{{ route('admin.destinations.verify') }}" 
               class="flex items-center justify-between px-3.5 py-2.5 text-xs font-bold tracking-wide rounded-full transition-all {{ request()->routeIs('admin.destinations.verify*') ? 'text-white bg-[#b87a38] shadow-xs' : 'text-stone-300 hover:text-white hover:bg-white/10' }}">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[18px]">fact_check</span>
                    <span>Verif Tapak</span>
                </div>
                <span class="px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-200 text-[10px] font-bold">2</span>
            </a>

            <a href="{{ route('admin.users.verify') }}" 
               class="flex items-center justify-between px-3.5 py-2.5 text-xs font-bold tracking-wide rounded-full transition-all {{ request()->routeIs('admin.users.verify*') ? 'text-white bg-[#b87a38] shadow-xs' : 'text-stone-300 hover:text-white hover:bg-white/10' }}">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[18px]">how_to_reg</span>
                    <span>Verif User</span>
                </div>
                <span class="px-2 py-0.5 rounded-full bg-rose-500/20 text-rose-200 text-[10px] font-bold">2</span>
            </a>

            <a href="{{ route('admin.bookings.index') }}" 
               class="flex items-center gap-3 px-3.5 py-2.5 text-xs font-bold tracking-wide rounded-full transition-all {{ request()->routeIs('admin.bookings*') ? 'text-white bg-[#b87a38] shadow-xs' : 'text-stone-300 hover:text-white hover:bg-white/10' }}">
                <span class="material-symbols-outlined text-[18px]">assignment</span>
                <span>Kelola Booking</span>
            </a>

            <a href="{{ route('admin.documents.index') }}" 
               class="flex items-center gap-3 px-3.5 py-2.5 text-xs font-bold tracking-wide rounded-full transition-all {{ request()->routeIs('admin.documents*') ? 'text-white bg-[#b87a38] shadow-xs' : 'text-stone-300 hover:text-white hover:bg-white/10' }}">
                <span class="material-symbols-outlined text-[18px]">description</span>
                <span>Dokumen Resmi</span>
            </a>

            <a href="{{ route('admin.destinations.curation') }}" 
               class="flex items-center gap-3 px-3.5 py-2.5 text-xs font-bold tracking-wide rounded-full transition-all {{ request()->routeIs('admin.destinations.curation*') ? 'text-white bg-[#b87a38] shadow-xs' : 'text-stone-300 hover:text-white hover:bg-white/10' }}">
                <span class="material-symbols-outlined text-[18px]">edit_note</span>
                <span>Editor Kurasi</span>
            </a>

            <a href="{{ route('admin.notifications.index') }}" 
               class="flex items-center justify-between px-3.5 py-2.5 text-xs font-bold tracking-wide rounded-full transition-all {{ request()->routeIs('admin.notifications*') ? 'text-white bg-[#b87a38] shadow-xs' : 'text-stone-300 hover:text-white hover:bg-white/10' }}">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[18px]">notifications_active</span>
                    <span>Log & Queue</span>
                </div>
                <span class="px-2 py-0.5 rounded-full bg-rose-500/20 text-rose-200 text-[10px] font-bold">1</span>
            </a>
        </nav>

        <!-- Sidebar Footer: Profil Pengguna & Logout -->
        <div class="p-3 border-t border-white/10 bg-[#1c1311] shrink-0">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-8 h-8 shrink-0 rounded-full bg-[#703A3A] border border-[#8c5151] text-white flex items-center justify-center text-xs font-bold">
                        RP
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-xs font-bold text-white truncate">Rian Pratama, S.Hum.</span>
                        <span class="text-[10px] text-[#d4a373] font-medium truncate">Lead Operasional</span>
                    </div>
                </div>
                <a href="{{ route('login') }}" title="Keluar ke Halaman Masuk" class="p-2 text-stone-400 hover:text-white hover:bg-white/10 rounded-full transition-colors">
                    <span class="material-symbols-outlined text-[18px]">logout</span>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content Area (Berada di kanan Sidebar, margin kiri w-64 pada desktop) -->
    <div class="flex-1 md:pl-64 flex flex-col min-w-0 min-h-screen">
        
        <!-- Topbar 1 Baris Ringkas & Bersih Admin -->
        <header class="sticky top-0 z-30 bg-[#ffffff] border-b border-[#2B211E]/12 shadow-xs h-16 flex items-center justify-between px-4 sm:px-6 lg:px-8">
            <!-- Left: Mobile Menu Toggle & Context Breadcrumb -->
            <div class="flex items-center gap-3">
                <button @click="mobileSidebarOpen = true" class="md:hidden p-2 text-[#2B211E]/70 hover:text-[#2B211E] border border-[#2B211E]/15">
                    <span class="material-symbols-outlined text-[20px]">menu</span>
                </button>
                <div class="flex items-center gap-2 text-xs text-[#2B211E]/70">
                    <span class="font-serif font-semibold text-[#703A3A]">Konsol Operasional</span>
                    <span>/</span>
                    <span class="font-medium text-[#2B211E]">@yield('title', 'Dashboard')</span>
                </div>
            </div>

            <!-- Right: SLA Status Indicator & Quick Queue Alert -->
            <div class="flex items-center gap-3">
                <div class="hidden sm:flex items-center gap-2 px-2.5 py-1 bg-[#2B211E]/5 border border-[#2B211E]/15 text-[11px] font-semibold text-[#2B211E]">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>SLA: 2 Jam Kerja</span>
                </div>

                <a href="{{ route('admin.destinations.verify') }}" class="rgs-btn-outline px-3 py-1.5 text-xs font-semibold uppercase tracking-wider flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">pending_actions</span>
                    <span class="hidden sm:inline">2 Antrian Tapak</span>
                </a>
            </div>
        </header>

        <!-- Flash Messages Editorial -->
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

        @if(session('info'))
            <div class="bg-[#2B3A4A] text-white px-4 py-3 border-b border-[#1f2a36]">
                <div class="max-w-7xl mx-auto flex items-center justify-between text-xs sm:text-sm">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">info</span>
                        <span>{{ session('info') }}</span>
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

        <!-- Footer Archival Terroir Admin -->
        <footer class="mt-auto border-t border-[#2B211E]/12 bg-[#ffffff] py-4 text-xs text-[#2B211E]/60">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="font-serif font-bold text-[#2B3A4A]">DESTINARA OPERASIONAL</span>
                    <span>— Pusat Kurasi & Koordinasi Lapangan Nusantara</span>
                </div>
                <div class="flex items-center gap-4 text-[11px]">
                    <span>SLA Verifikasi Dokumen: &lt; 24 Jam</span>
                    <span>•</span>
                    <span>Audit Trail Logging Aktif</span>
                </div>
            </div>
        </footer>
    </div>

</body>
</html>
