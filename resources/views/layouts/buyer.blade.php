<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <x-theme-script />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Portal Buyer Institusi') — DESTINARA</title>

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
        $unread = Auth::check() ? Auth::user()->unreadNotifications()->count() : 0;
        $sections = [
            ['label' => 'Menu Utama', 'items' => [
                ['href' => route('buyer.dashboard'), 'active' => request()->routeIs('buyer.dashboard'), 'icon' => 'dashboard', 'label' => 'Dashboard'],
                ['href' => route('buyer.booking.create'), 'active' => request()->routeIs('buyer.booking.create'), 'icon' => 'post_add', 'label' => 'Ajukan Kunjungan'],
                ['href' => route('buyer.history'), 'active' => request()->routeIs('buyer.history'), 'icon' => 'receipt_long', 'label' => 'Riwayat Pesanan'],
                ['href' => route('buyer.profile'), 'active' => request()->routeIs('buyer.profile'), 'icon' => 'school', 'label' => 'Profil Institusi'],
                ['href' => route('buyer.notifications'), 'active' => request()->routeIs('buyer.notifications*'), 'icon' => 'notifications', 'label' => 'Notifikasi', 'badge' => $unread],
            ]],
            ['label' => 'Eksplorasi Tapak', 'items' => [
                ['href' => route('destinasi.index'), 'active' => false, 'icon' => 'travel_explore', 'label' => 'Katalog Tapak'],
            ]],
        ];
    @endphp

    <x-portal.sidebar :home="route('buyer.dashboard')" subtitle="Portal Buyer Institusi" :sections="$sections"
                      :user-sub="Auth::user()->institution->pic_position ?? null">
        <x-slot:context>
            <span class="ps-sub block">Institusi buyer</span>
            <span class="block truncate font-medium">{{ Auth::user()->institution->institution_name ?? (Auth::user()->name ?? 'Institusi Buyer') }}</span>
            <span class="ps-sub block">NPSN: {{ Auth::user()->institution->npsn ?? '-' }}</span>
        </x-slot:context>
    </x-portal.sidebar>

    <div class="ps-inset">
        <x-portal.header :title="trim($__env->yieldContent('title', 'Dashboard'))" subtitle="Portal Buyer Institusi">
            <span class="ps-chip hidden sm:inline-flex">
                <span class="material-symbols-outlined text-emerald-600">verified</span>
                <span>Akun Pembeli Aktif</span>
            </span>
            <a href="{{ route('buyer.notifications') }}" class="ps-icon-btn ps-notif" title="Pusat Notifikasi" aria-label="Pusat Notifikasi">
                <span class="material-symbols-outlined text-[18px]">notifications</span>
                @if ($unread > 0)<span class="ps-dot"></span>@endif
            </a>
            <a href="{{ route('buyer.booking.create') }}" class="ps-btn ps-btn-primary">
                <span class="material-symbols-outlined">add_circle</span>
                <span class="hidden sm:inline">Ajukan Booking</span>
            </a>
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
