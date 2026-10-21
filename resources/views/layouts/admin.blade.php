<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <x-theme-script />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Konsol Admin Operasional') — DESTINARA</title>

    <!-- Favicon Lengkap Resmi Destinara -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#1F2B45">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..24,400,0..1,0" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

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
            border-top: 4px solid #1F2B45;
            border-radius: 1.25rem;
        }
        .rgs-card-blue {
            border-top: 4px solid #2B3A4A;
            border-radius: 1.25rem;
        }
        .rgs-btn-primary {
            background-color: #1F2B45;
            color: #ffffff;
            border-radius: 9999px;
            transition: all 0.2s ease;
        }
        .rgs-btn-primary:hover {
            background-color: #16203A;
        }
        .rgs-btn-outline {
            border: 1px solid rgba(0, 0, 0, 0.12);
            border-radius: 9999px;
            color: #111827;
            background-color: transparent;
            transition: all 0.2s ease;
        }
        .rgs-btn-outline:hover {
            background-color: rgba(0, 0, 0, 0.04);
        }
    </style>
</head>
<body class="app-shell shell-dark text-[#1F2937] antialiased" x-data="{ mobileSidebarOpen: false, sidebarOpen: (() => { try { return localStorage.getItem('destinara-sidebar') !== '0' } catch (e) { return true } })() }"
      x-init="$watch('sidebarOpen', v => { try { localStorage.setItem('destinara-sidebar', v ? '1' : '0') } catch (e) {} })"
      :class="sidebarOpen && 'sidebar-open'">

    <!-- Mobile Sidebar Backdrop -->
    <div x-show="mobileSidebarOpen" 
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="mobileSidebarOpen = false" 
         class="fixed inset-0 z-40 bg-[#1F2937]/60 backdrop-blur-xs md:hidden" 
         style="display: none;"></div>

    <!-- Sidebar Kiri Tetap Admin (Fixed Desktop w-64, Drawer on Mobile) -->
    <aside :class="(mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0') + (sidebarOpen ? '' : ' md:-ml-64')"
           class="fixed inset-y-0 left-0 z-50 w-64 shrink-0 md:static md:z-auto md:h-full md:transition-[margin] md:duration-200 bg-[#1F2937] border-r border-[#1F2937] flex flex-col transition-transform duration-300 ease-in-out shadow-lg text-white">
        
        <!-- Sidebar Header & Logo -->
        <div class="h-16 px-5 border-b border-white/10 flex items-center justify-between shrink-0">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3">
                <img src="{{ asset('assets/img/logo-mark-tight.png') }}" alt="Logo Destinara" class="h-9 w-auto object-contain flex-shrink-0 drop-shadow-sm">
                <div class="flex flex-col">
                    <span class="font-serif text-lg font-bold tracking-tight text-white leading-none">DESTINARA</span>
                    <span class="text-[9px] tracking-wider uppercase font-semibold text-[#d4a373]">Admin Operasional</span>
                </div>
            </a>
            <!-- Mobile Close Button -->
            <button @click="mobileSidebarOpen = false" class="md:hidden text-[#9AA3AF] hover:text-white p-1">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>

        <!-- SLA Live Status Card -->
        <div class="p-4 border-b border-[#1F2937] bg-[#111827]">
            <div class="flex items-center gap-2 text-[11px] font-semibold text-[#d4a373]">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>SLA: 2 Jam Kerja • Aktif</span>
            </div>
            <div class="text-[10px] text-[#9AA3AF] mt-0.5">Pusat Kendali Terpadu Lapangan</div>
        </div>

        <!-- Navigasi Menu Vertikal Admin Berbasis Izin (SPEC §14.4) -->
        <nav class="flex-1 px-3 py-2 space-y-1 overflow-y-auto">
            @can('dashboard.view')
            <a href="{{ route('admin.dashboard') }}" 
               class="flex items-center gap-3 px-3.5 py-2 text-xs font-bold tracking-wide rounded-2xl transition-all {{ request()->routeIs('admin.dashboard') ? 'text-white bg-[#b87a38] shadow-xs' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                <span class="material-symbols-outlined text-[18px]">dashboard</span>
                <span>Dashboard</span>
            </a>
            @endcan

            <div class="pt-3 pb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                Operasional Tapak
            </div>

            @can('destinations.view')
            <a href="{{ route('admin.destinations.index') }}" 
               class="flex items-center gap-3 px-3.5 py-2 text-xs font-bold tracking-wide rounded-2xl transition-all {{ request()->routeIs('admin.destinations.index*') || request()->routeIs('admin.destinations.create*') ? 'text-white bg-[#b87a38] shadow-xs' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                <span class="material-symbols-outlined text-[18px]">travel_explore</span>
                <span>Destinasi</span>
            </a>
            @endcan

            @can('destinations.verify')
            <a href="{{ route('admin.destinations.verify') }}" 
               class="flex items-center gap-3 px-3.5 py-2 text-xs font-bold tracking-wide rounded-2xl transition-all {{ request()->routeIs('admin.destinations.verify*') ? 'text-white bg-[#b87a38] shadow-xs' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                <span class="material-symbols-outlined text-[18px]">fact_check</span>
                <span>Verifikasi Tapak</span>
            </a>
            @endcan

            @can('bookings.view')
            <a href="{{ route('admin.bookings.index') }}" 
               class="flex items-center gap-3 px-3.5 py-2 text-xs font-bold tracking-wide rounded-2xl transition-all {{ request()->routeIs('admin.bookings*') ? 'text-white bg-[#b87a38] shadow-xs' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                <span class="material-symbols-outlined text-[18px]">assignment</span>
                <span>Kelola Booking</span>
            </a>
            @endcan

            @can('reviews.moderate')
            <a href="{{ route('admin.reviews.index') }}" 
               class="flex items-center gap-3 px-3.5 py-2 text-xs font-bold tracking-wide rounded-2xl transition-all {{ request()->routeIs('admin.reviews*') ? 'text-white bg-[#b87a38] shadow-xs' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                <span class="material-symbols-outlined text-[18px]">star_rate</span>
                <span>Moderasi Ulasan</span>
            </a>
            @endcan

            <div class="pt-3 pb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                Pengguna & Mitra
            </div>

            @canany(['institutions.verify', 'partners.verify'])
            <a href="{{ route('admin.users.verify') }}" 
               class="flex items-center gap-3 px-3.5 py-2 text-xs font-bold tracking-wide rounded-2xl transition-all {{ request()->routeIs('admin.users.verify*') ? 'text-white bg-[#b87a38] shadow-xs' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                <span class="material-symbols-outlined text-[18px]">how_to_reg</span>
                <span>Verifikasi Legalitas</span>
            </a>
            @endcanany

            @can('users.view')
            <a href="{{ route('admin.users.index') }}" 
               class="flex items-center gap-3 px-3.5 py-2 text-xs font-bold tracking-wide rounded-2xl transition-all {{ request()->routeIs('admin.users.index*') ? 'text-white bg-[#b87a38] shadow-xs' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                <span class="material-symbols-outlined text-[18px]">group</span>
                <span>Manajemen Pengguna</span>
            </a>
            @endcan

            <div class="pt-3 pb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                Keuangan & Transaksi
            </div>

            @can('payments.view')
            <a href="{{ route('admin.finance.payments') }}" 
               class="flex items-center gap-3 px-3.5 py-2 text-xs font-bold tracking-wide rounded-2xl transition-all {{ request()->routeIs('admin.finance.payments*') ? 'text-white bg-[#b87a38] shadow-xs' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                <span class="material-symbols-outlined text-[18px]">payments</span>
                <span>Pembayaran</span>
            </a>
            @endcan

            @can('refunds.view')
            <a href="{{ route('admin.finance.refunds') }}" 
               class="flex items-center gap-3 px-3.5 py-2 text-xs font-bold tracking-wide rounded-2xl transition-all {{ request()->routeIs('admin.finance.refunds*') ? 'text-white bg-[#b87a38] shadow-xs' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                <span class="material-symbols-outlined text-[18px]">currency_exchange</span>
                <span>Refund</span>
            </a>
            @endcan

            @can('payouts.view')
            <a href="{{ route('admin.finance.payouts') }}" 
               class="flex items-center gap-3 px-3.5 py-2 text-xs font-bold tracking-wide rounded-2xl transition-all {{ request()->routeIs('admin.finance.payouts*') ? 'text-white bg-[#b87a38] shadow-xs' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                <span class="material-symbols-outlined text-[18px]">account_balance_wallet</span>
                <span>Payout Mitra</span>
            </a>
            @endcan

            @can('reconciliation.view')
            <a href="{{ route('admin.finance.reconciliation') }}" 
               class="flex items-center gap-3 px-3.5 py-2 text-xs font-bold tracking-wide rounded-2xl transition-all {{ request()->routeIs('admin.finance.reconciliation*') ? 'text-white bg-[#b87a38] shadow-xs' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                <span class="material-symbols-outlined text-[18px]">account_balance</span>
                <span>Rekonsiliasi</span>
            </a>
            @endcan

            <div class="pt-3 pb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                Dokumen & Pemasaran
            </div>

            @can('documents.view')
            <a href="{{ route('admin.documents.index') }}" 
               class="flex items-center gap-3 px-3.5 py-2 text-xs font-bold tracking-wide rounded-2xl transition-all {{ request()->routeIs('admin.documents*') ? 'text-white bg-[#b87a38] shadow-xs' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                <span class="material-symbols-outlined text-[18px]">description</span>
                <span>Dokumen Resmi</span>
            </a>
            @endcan

            @can('vouchers.manage')
            <a href="{{ route('admin.vouchers.index') }}" 
               class="flex items-center gap-3 px-3.5 py-2 text-xs font-bold tracking-wide rounded-2xl transition-all {{ request()->routeIs('admin.vouchers*') ? 'text-white bg-[#b87a38] shadow-xs' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                <span class="material-symbols-outlined text-[18px]">confirmation_number</span>
                <span>Voucher Promo</span>
            </a>
            @endcan

            @can('notifications.view')
            <a href="{{ route('admin.notifications.index') }}" 
               class="flex items-center gap-3 px-3.5 py-2 text-xs font-bold tracking-wide rounded-2xl transition-all {{ request()->routeIs('admin.notifications*') ? 'text-white bg-[#b87a38] shadow-xs' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                <span class="material-symbols-outlined text-[18px]">notifications_active</span>
                <span>Log Notifikasi</span>
            </a>
            @endcan

            <div class="pt-3 pb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                Pengaturan Sistem
            </div>

            @can('master_data.manage')
            <a href="{{ route('admin.master.index') }}" 
               class="flex items-center gap-3 px-3.5 py-2 text-xs font-bold tracking-wide rounded-2xl transition-all {{ request()->routeIs('admin.master*') ? 'text-white bg-[#b87a38] shadow-xs' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                <span class="material-symbols-outlined text-[18px]">dataset</span>
                <span>Master Data</span>
            </a>
            @endcan

            @can('site_content.manage')
            <a href="{{ route('admin.siteContents.index') }}" 
               class="flex items-center gap-3 px-3.5 py-2 text-xs font-bold tracking-wide rounded-2xl transition-all {{ request()->routeIs('admin.siteContents*') ? 'text-white bg-[#b87a38] shadow-xs' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                <span class="material-symbols-outlined text-[18px]">web</span>
                <span>Konten Halaman</span>
            </a>
            @endcan

            @can('internal_users.manage')
            <a href="{{ route('admin.team.index') }}" 
               class="flex items-center gap-3 px-3.5 py-2 text-xs font-bold tracking-wide rounded-2xl transition-all {{ request()->routeIs('admin.team*') ? 'text-white bg-[#b87a38] shadow-xs' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                <span class="material-symbols-outlined text-[18px]">badge</span>
                <span>Tim & Akses Role</span>
            </a>
            @endcan

            @can('settings.general')
            <a href="{{ route('admin.settings.index') }}" 
               class="flex items-center gap-3 px-3.5 py-2 text-xs font-bold tracking-wide rounded-2xl transition-all {{ request()->routeIs('admin.settings*') ? 'text-white bg-[#b87a38] shadow-xs' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                <span class="material-symbols-outlined text-[18px]">settings</span>
                <span>Pengaturan Global</span>
            </a>
            @endcan

            @can('audit_logs.view')
            <a href="{{ route('admin.auditLogs.index') }}" 
               class="flex items-center gap-3 px-3.5 py-2 text-xs font-bold tracking-wide rounded-2xl transition-all {{ request()->routeIs('admin.auditLogs*') ? 'text-white bg-[#b87a38] shadow-xs' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                <span class="material-symbols-outlined text-[18px]">history</span>
                <span>Audit Log</span>
            </a>
            @endcan

            @can('analytics.view')
            <a href="{{ route('admin.analytics.index') }}" 
               class="flex items-center gap-3 px-3.5 py-2 text-xs font-bold tracking-wide rounded-2xl transition-all {{ request()->routeIs('admin.analytics*') ? 'text-white bg-[#b87a38] shadow-xs' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                <span class="material-symbols-outlined text-[18px]">query_stats</span>
                <span>Analitik Makro</span>
            </a>
            @endcan

            @can('system_health.view')
            <a href="{{ route('admin.health.index') }}" 
               class="flex items-center gap-3 px-3.5 py-2 text-xs font-bold tracking-wide rounded-2xl transition-all {{ request()->routeIs('admin.health*') ? 'text-white bg-[#b87a38] shadow-xs' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                <span class="material-symbols-outlined text-[18px]">health_and_safety</span>
                <span>System Health</span>
            </a>
            @endcan
        </nav>

        <!-- Sidebar Footer: Profil Pengguna & Logout -->
        <div class="p-3 border-t border-white/10 bg-[#111827] shrink-0">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-8 h-8 shrink-0 rounded-full bg-[#1F2B45] border border-[#2E3D5C] text-white flex items-center justify-center text-xs font-bold">
                        {{ strtoupper(substr(Auth::user()->name ?? 'AD', 0, 2)) }}
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-xs font-bold text-white truncate">{{ Auth::user()->name ?? 'Admin Operasional' }}</span>
                        <span class="text-[10px] text-[#d4a373] font-medium truncate">{{ Auth::user()->email ?? 'admin@destinara.id' }}</span>
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" title="Keluar Sesi" class="p-2 text-slate-400 hover:text-white hover:bg-white/10 rounded-full transition-colors cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">logout</span>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content Area (Berada di kanan Sidebar, margin kiri w-64 pada desktop) -->
    <div class="sidebar-inset">
        
        <!-- Topbar 1 Baris Ringkas & Bersih Admin -->
        <header class="sticky top-0 z-30 bg-[#ffffff] border-b border-[#1F2937]/12 h-16 shrink-0 flex items-center justify-between px-4 sm:px-6 lg:px-8">
            <!-- Left: Mobile Menu Toggle & Context Breadcrumb -->
            <div class="flex items-center gap-3">
                <x-sidebar-toggle />
                <span class="h-4 w-px bg-[#1F2937]/15" aria-hidden="true"></span>
                <div class="flex items-center gap-2 text-xs text-[#1F2937]/70">
                    <span class="font-serif font-semibold text-[#1F2B45]">Konsol Operasional</span>
                    <span>/</span>
                    <span class="font-medium text-[#1F2937]">@yield('title', 'Dashboard')</span>
                </div>
            </div>

            <!-- Right: SLA Status Indicator & Quick Queue Alert -->
            <div class="flex items-center gap-3">
                <div class="hidden sm:flex items-center gap-2 px-2.5 py-1 bg-[#1F2937]/5 border border-[#1F2937]/15 text-[11px] font-semibold text-[#1F2937]">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>SLA: 2 Jam Kerja</span>
                </div>

                <x-theme-toggle />

                @can('destinations.verify')
                <a href="{{ route('admin.destinations.verify') }}" class="rgs-btn-outline px-3 py-1.5 text-xs font-semibold uppercase tracking-wider flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">pending_actions</span>
                    <span class="hidden sm:inline">Kurasi Destinasi</span>
                </a>
                @endcan
            </div>
        </header>

        <!-- Main Content Container -->
        <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <x-flash />
            <x-form-errors />
            @yield('content')
        </main>

        <!-- Footer Archival Terroir Admin -->
        <footer class="mt-auto border-t border-[#1F2937]/12 bg-[#ffffff] py-4 text-xs text-[#1F2937]/60">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="font-serif font-bold text-[#2B3A4A]">DESTINARA OPERASIONAL</span>
                    <span>— Pusat Kurasi & Koordinasi Lapangan Nusantara</span>
                </div>
                <div class="flex items-center gap-4 text-[11px]">
                    <span>SLA Konfirmasi Mitra: &lt; {{ (int) setting('partner_confirm_sla_hours', 48) }} Jam</span>
                    <span>•</span>
                    <span>Audit Trail Logging Aktif</span>
                </div>
            </div>
        </footer>
    </div>

    @livewireScriptConfig
</body>
</html>
