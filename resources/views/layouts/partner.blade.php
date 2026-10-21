<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <x-theme-script />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Portal Mitra Pengelola') — DESTINARA</title>

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
        $readyBalance = Auth::check() ? \App\Models\PartnerPayout::where('partner_id', Auth::id())
            ->where('status', \App\Enums\PayoutStatus::PENDING)
            ->where('eligible_at', '<=', now())
            ->sum('net_amount') : 0;
        $orgType = Auth::user()->partnerProfile?->organization_type;
        $sections = [
            ['label' => 'Operasional Tapak', 'items' => [
                ['href' => route('mitra.dashboard'), 'active' => request()->routeIs('mitra.dashboard'), 'icon' => 'dashboard', 'label' => 'Dashboard'],
                ['href' => route('mitra.bookings'), 'active' => request()->routeIs('mitra.bookings*'), 'icon' => 'inbox', 'label' => 'Booking Masuk'],
                ['href' => route('mitra.availability'), 'active' => request()->routeIs('mitra.availability*'), 'icon' => 'calendar_month', 'label' => 'Kalender Slot'],
                ['href' => route('mitra.destinations'), 'active' => request()->routeIs('mitra.destinations*'), 'icon' => 'holiday_village', 'label' => 'Tapak Kelolaan'],
                ['href' => route('mitra.income'), 'active' => request()->routeIs('mitra.income*'), 'icon' => 'payments', 'label' => 'Pendapatan'],
                ['href' => route('mitra.profile'), 'active' => request()->routeIs('mitra.profile*'), 'icon' => 'badge', 'label' => 'Profil Adat'],
                ['href' => route('mitra.notifications'), 'active' => request()->routeIs('mitra.notifications*'), 'icon' => 'notifications', 'label' => 'Notifikasi', 'badge' => $unread],
            ]],
        ];
    @endphp

    <x-portal.sidebar :home="route('mitra.dashboard')" subtitle="Portal Pengelola Tapak" :sections="$sections"
                      :user-sub="$orgType ? ucwords(str_replace('_', ' ', $orgType)) : null">
        <x-slot:context>
            <span class="ps-sub block">Lembaga adat</span>
            <span class="block truncate font-medium">{{ Auth::user()->partnerProfile->organization_name ?? (Auth::user()->name ?? 'Pengelola Tapak') }}</span>
            <span class="ps-sub block">Hak Adat 90% • Protokol FPIC</span>
        </x-slot:context>
    </x-portal.sidebar>

    <div class="ps-inset">
        <x-portal.header :title="trim($__env->yieldContent('title', 'Dashboard'))" subtitle="Portal Mitra Adat">
            <a href="{{ route('mitra.income') }}" class="ps-chip hidden sm:inline-flex hover:text-[var(--ps-ink)]" title="Payout menunggu proses">
                <span>Menunggu payout</span>
                <span class="font-mono font-semibold text-[var(--ps-ink)]">Rp {{ number_format($readyBalance, 0, ',', '.') }}</span>
            </a>
            <a href="{{ route('mitra.notifications') }}" class="ps-icon-btn ps-notif" title="Pusat Notifikasi" aria-label="Pusat Notifikasi">
                <span class="material-symbols-outlined text-[18px]">notifications</span>
                @if ($unread > 0)<span class="ps-dot"></span>@endif
            </a>
            <a href="{{ route('mitra.availability') }}" class="ps-btn">
                <span class="material-symbols-outlined">edit_calendar</span>
                <span class="hidden sm:inline">Atur Slot</span>
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
