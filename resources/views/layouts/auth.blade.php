<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Autentikasi') — Destinara</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}"/>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}"/>
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}"/>
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}"/>
    <link rel="manifest" href="{{ asset('site.webmanifest') }}"/>
    <meta name="theme-color" content="#1F2B45"/>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..24,400,0..1,0" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        /* style.css (tanpa layer) mengatur warna body; halaman berlatar foto memakai teks putih */
        body { font-family: 'Plus Jakarta Sans', sans-serif; color: #fff; background-color: #111827; }
        /* Kolom form di atas kartu kaca */
        .auth-input { width: 100%; font-size: .8125rem; padding: .8rem 1rem; border-radius: .75rem; color: #fff;
            background: rgba(255,255,255,.10); border: 1px solid rgba(255,255,255,.35); transition: all .2s ease; }
        .auth-input::placeholder { color: rgba(255,255,255,.6); }
        .auth-input:focus { outline: none; border-color: #fff; background: rgba(255,255,255,.16); box-shadow: 0 0 0 3px rgba(255,255,255,.18); }
        .auth-input.has-icon { padding-left: 2.6rem; }
        .auth-input[readonly] { color: rgba(255,255,255,.7); background: rgba(255,255,255,.05); }
        .auth-input:-webkit-autofill { -webkit-text-fill-color: #fff; transition: background-color 9999s ease-in-out 0s; }
    </style>
    @stack('styles')
</head>
<body class="min-h-screen antialiased text-white bg-[#111827] selection:bg-white selection:text-[#1F2B45]">

    <!-- Latar: foto yang sama dengan beranda Destinara -->
    <div class="fixed inset-0 -z-10" aria-hidden="true">
        <img src="{{ asset('assets/img/hd/hero-bg-waterfall.webp') }}" alt="" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-r from-black/75 via-black/45 to-black/25"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-black/30"></div>
    </div>

    <main class="min-h-screen max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 items-center">

        <!-- Kiri: brand & headline -->
        <section class="space-y-4 lg:space-y-6">
            <a href="{{ route('destinasi.index') }}" class="inline-flex items-center gap-3 group">
                <img src="{{ asset('assets/img/logo-mark-tight.png') }}" alt="Logo Destinara" class="w-10 h-10 object-contain brightness-110 group-hover:scale-105 transition-transform">
                <span class="text-xl font-extrabold tracking-tight">DESTINARA</span>
            </a>

            <div class="space-y-3">
                <p class="text-sm sm:text-base font-bold tracking-[0.25em] uppercase text-white/85">@yield('auth_kicker', 'Ruang Belajar Tapak')</p>
                <h1 class="text-4xl sm:text-6xl lg:text-7xl font-extrabold uppercase leading-[0.95] tracking-tight drop-shadow-[0_2px_20px_rgba(0,0,0,0.45)]">
                    @yield('auth_title', 'Jelajahi Nusantara')
                </h1>
                <p class="text-lg sm:text-2xl font-semibold text-white/95 leading-snug max-w-md">@yield('auth_subtitle')</p>
                <p class="hidden sm:block text-sm text-white/75 leading-relaxed max-w-sm">@yield('auth_lead')</p>
            </div>
        </section>

        <!-- Kanan: kartu kaca -->
        <section class="w-full @yield('card_width', 'max-w-md') lg:justify-self-end">
            <div class="bg-white/10 backdrop-blur-xl border border-white/25 rounded-3xl p-6 sm:p-8 shadow-[0_24px_60px_rgba(0,0,0,0.35)] space-y-5">
                @yield('card')
            </div>
        </section>
    </main>

    @stack('scripts')
    @livewireScriptConfig
</body>
</html>
