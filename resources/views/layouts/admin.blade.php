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
<body class="ps-shell antialiased" x-data="portalShell">
    @php
        $can = fn (...$p) => Auth::user()?->canAny($p);
        $item = fn ($route, $pattern, $icon, $label) => ['href' => route($route), 'active' => request()->routeIs(...(array) $pattern), 'icon' => $icon, 'label' => $label];
        $sections = [
            ['label' => null, 'items' => array_values(array_filter([
                $can('dashboard.view') ? $item('admin.dashboard', 'admin.dashboard', 'dashboard', 'Dashboard') : null,
            ]))],
            ['label' => 'Operasional Tapak', 'items' => array_values(array_filter([
                $can('destinations.view') ? $item('admin.destinations.index', ['admin.destinations.index*', 'admin.destinations.create*', 'admin.destinations.edit*', 'admin.destinations.show*'], 'travel_explore', 'Destinasi') : null,
                $can('destinations.verify') ? $item('admin.destinations.verify', 'admin.destinations.verify*', 'fact_check', 'Verifikasi Tapak') : null,
                $can('bookings.view') ? $item('admin.bookings.index', 'admin.bookings*', 'assignment', 'Kelola Booking') : null,
                $can('reviews.moderate') ? $item('admin.reviews.index', 'admin.reviews*', 'star_rate', 'Moderasi Ulasan') : null,
            ]))],
            ['label' => 'Pengguna & Mitra', 'items' => array_values(array_filter([
                $can('institutions.verify', 'partners.verify') ? $item('admin.users.verify', 'admin.users.verify*', 'how_to_reg', 'Verifikasi Legalitas') : null,
                $can('users.view') ? $item('admin.users.index', 'admin.users.index*', 'group', 'Manajemen Pengguna') : null,
            ]))],
            ['label' => 'Keuangan & Transaksi', 'items' => array_values(array_filter([
                $can('payments.view') ? $item('admin.finance.payments', 'admin.finance.payments*', 'payments', 'Pembayaran') : null,
                $can('refunds.view') ? $item('admin.finance.refunds', 'admin.finance.refunds*', 'currency_exchange', 'Refund') : null,
                $can('payouts.view') ? $item('admin.finance.payouts', 'admin.finance.payouts*', 'account_balance_wallet', 'Payout Mitra') : null,
                $can('reconciliation.view') ? $item('admin.finance.reconciliation', 'admin.finance.reconciliation*', 'account_balance', 'Rekonsiliasi') : null,
            ]))],
            ['label' => 'Dokumen & Pemasaran', 'items' => array_values(array_filter([
                $can('documents.view') ? $item('admin.documents.index', 'admin.documents*', 'description', 'Dokumen Resmi') : null,
                $can('vouchers.manage') ? $item('admin.vouchers.index', 'admin.vouchers*', 'confirmation_number', 'Voucher Promo') : null,
                $can('notifications.view') ? $item('admin.notifications.index', 'admin.notifications*', 'notifications_active', 'Log Notifikasi') : null,
            ]))],
            ['label' => 'Pengaturan Sistem', 'items' => array_values(array_filter([
                $can('master_data.manage') ? $item('admin.master.index', 'admin.master*', 'dataset', 'Master Data') : null,
                $can('site_content.manage') ? $item('admin.siteContents.index', 'admin.siteContents*', 'web', 'Konten Halaman') : null,
                $can('internal_users.manage') ? $item('admin.team.index', 'admin.team*', 'badge', 'Tim & Akses Role') : null,
                $can('settings.general') ? $item('admin.settings.index', 'admin.settings*', 'settings', 'Pengaturan Global') : null,
                $can('audit_logs.view') ? $item('admin.auditLogs.index', 'admin.auditLogs*', 'history', 'Audit Log') : null,
                $can('analytics.view') ? $item('admin.analytics.index', 'admin.analytics*', 'query_stats', 'Analitik Makro') : null,
                $can('system_health.view') ? $item('admin.health.index', 'admin.health*', 'health_and_safety', 'System Health') : null,
            ]))],
        ];
        $roleName = Auth::user()?->getRoleNames()->first();
    @endphp

    <x-portal.sidebar :home="route('admin.dashboard')" subtitle="Admin Operasional" :sections="$sections">
        <x-slot:context>
            <span class="ps-sub block">Peran</span>
            <span class="block truncate font-medium">{{ $roleName ? ucwords(str_replace('_', ' ', $roleName)) : 'Admin' }}</span>
            <span class="ps-sub flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> SLA 2 jam kerja • Aktif</span>
        </x-slot:context>
    </x-portal.sidebar>

    <div class="ps-inset">
        <x-portal.header :title="trim($__env->yieldContent('title', 'Dashboard'))" subtitle="Konsol Operasional">
            <span class="ps-chip hidden sm:inline-flex">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>SLA: 2 Jam Kerja</span>
            </span>
            @can('destinations.verify')
                <a href="{{ route('admin.destinations.verify') }}" class="ps-btn">
                    <span class="material-symbols-outlined">pending_actions</span>
                    <span class="hidden sm:inline">Kurasi Destinasi</span>
                </a>
            @endcan
        </x-portal.header>

        <main class="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-8">
            <x-flash />
            <x-form-errors />
            @yield('content')
        </main>
    </div>

    @livewireScriptConfig
</body>
</html>
