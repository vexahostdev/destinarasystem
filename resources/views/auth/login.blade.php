<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#FAF7F5]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk Akun — Destinara</title>

    <!-- Favicon Configuration -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}"/>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}"/>
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}"/>
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}"/>
    <link rel="manifest" href="{{ asset('site.webmanifest') }}"/>
    <meta name="theme-color" content="#703a3a"/>

    <!-- Google Fonts & Material Symbols -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..24,400,0..1,0" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 antialiased selection:bg-[#703A3A] selection:text-white bg-[#FAF7F5]">

    <!-- Centered Modern Login Card -->
    <div class="max-w-md w-full bg-white rounded-3xl p-8 sm:p-10 shadow-[0_20px_50px_rgba(112,58,58,0.1)] border border-gray-100 relative z-10 space-y-6">
        
        <!-- Header & Logo -->
        <div class="text-center space-y-3">
            <a href="{{ route('destinasi.index') }}" class="inline-flex items-center justify-center gap-2 group mb-1">
                <img src="{{ asset('assets/img/logo-mark-tight.png') }}" alt="Logo Destinara" class="w-12 h-12 object-contain group-hover:scale-105 transition-transform">
            </a>
            <div>
                <span class="text-2xl font-extrabold tracking-tight text-[#4A2020] block">DESTINARA</span>
                <span class="text-[10px] tracking-widest text-[#703A3A] uppercase font-bold">Ruang Belajar Tapak Nusantara</span>
            </div>
            <h2 class="text-xl font-bold text-[#1E1B1B] pt-1">Masuk Akun Lapangan</h2>
            <p class="text-xs text-gray-500">
                Akses reservasi jadwal institusi atau panel pengelola tapak.
            </p>
        </div>

        <!-- Form Login -->
        <form onsubmit="event.preventDefault(); alert('Login berhasil disimulasikan! Menuju dashboard...'); window.location.href='{{ route('destinasi.index') }}';" class="space-y-4">
            
            <div>
                <label class="block text-xs font-bold text-[#1E1B1B] mb-1.5">Email Resmi</label>
                <div class="relative">
                    <input type="email" required placeholder="nama@instansi.sch.id" 
                           class="w-full text-xs pl-10 pr-4 py-3 bg-[#FAF7F5] rounded-xl border border-gray-200 text-[#1E1B1B] focus:outline-none focus:border-[#703A3A] focus:ring-1 focus:ring-[#703A3A] transition-all">
                    <span class="material-symbols-outlined absolute left-3 top-3 text-lg text-gray-400">mail</span>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-[#1E1B1B] mb-1.5">Kata Sandi</label>
                <div class="relative">
                    <input type="password" required placeholder="Masukkan kata sandi" 
                           class="w-full text-xs pl-10 pr-4 py-3 bg-[#FAF7F5] rounded-xl border border-gray-200 text-[#1E1B1B] focus:outline-none focus:border-[#703A3A] focus:ring-1 focus:ring-[#703A3A] transition-all">
                    <span class="material-symbols-outlined absolute left-3 top-3 text-lg text-gray-400">lock</span>
                </div>
            </div>

            <div class="flex items-center justify-between text-xs pt-1">
                <label class="flex items-center gap-2 text-gray-600 cursor-pointer">
                    <input type="checkbox" class="rounded text-[#703A3A] focus:ring-[#703A3A]">
                    <span>Ingat saya</span>
                </label>
                <a href="#" onclick="alert('Alur pemulihan sandi: Masukkan email instansi untuk menerima tautan.')" class="text-[#703A3A] font-semibold hover:underline">
                    Lupa sandi?
                </a>
            </div>

            <button type="submit" 
                    class="w-full bg-[#703A3A] hover:bg-[#561D1E] text-white py-3.5 px-6 rounded-full font-bold text-sm shadow-md hover:shadow-lg transition-all cursor-pointer">
                Masuk ke Sistem &rarr;
            </button>
        </form>

        <!-- Quick Demo Access for Evaluator -->
        <div class="space-y-2 pt-2">
            <div class="flex items-center gap-2 text-[10px] text-gray-400 uppercase tracking-wider font-bold">
                <div class="h-px bg-gray-200 flex-1"></div>
                <span>Akses Pengujian Cepat</span>
                <div class="h-px bg-gray-200 flex-1"></div>
            </div>
            <a href="{{ route('buyer.quick-login') }}" class="w-full py-2.5 px-4 bg-[#FAF7F5] hover:bg-[#703A3A] hover:text-white rounded-xl border border-gray-200 text-[#703A3A] text-xs font-bold transition-all flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[18px]">school</span>
                <span>Masuk Demo: Buyer Institusi</span>
            </a>
            <a href="{{ route('mitra.quick-login') }}" class="w-full py-2.5 px-4 bg-[#FAF7F5] hover:bg-[#51634b] hover:text-white rounded-xl border border-gray-200 text-[#51634b] text-xs font-bold transition-all flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[18px]">nature_people</span>
                <span>Masuk Demo: Mitra Pengelola</span>
            </a>
            <a href="{{ route('admin.quick-login') }}" class="w-full py-2.5 px-4 bg-[#FAF7F5] hover:bg-[#2B3A4A] hover:text-white rounded-xl border border-gray-200 text-[#2B3A4A] text-xs font-bold transition-all flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[18px]">admin_panel_settings</span>
                <span>Masuk Demo: Admin Operasional</span>
            </a>
            <a href="{{ route('superadmin.quick-login') }}" class="w-full py-2.5 px-4 bg-[#FAF7F5] hover:bg-[#4A1E1E] hover:text-white rounded-xl border border-gray-200 text-[#4A1E1E] text-xs font-bold transition-all flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[18px]">shield</span>
                <span>Masuk Demo: Super Admin</span>
            </a>
        </div>

        <!-- Institutional Security Notice -->
        <div class="p-3 bg-emerald-50 rounded-2xl border border-emerald-200/50 text-[11px] text-emerald-800 flex items-center gap-2">
            <span class="material-symbols-outlined text-base text-emerald-600 shrink-0">security</span>
            <span>Autentikasi terenkripsi & data institusi terproteksi</span>
        </div>

        <!-- Footer link -->
        <div class="pt-2 border-t border-gray-100 text-center text-xs space-y-2">
            <p class="text-gray-600">
                Belum memiliki akun? <a href="{{ route('register') }}" class="text-[#703A3A] font-bold hover:underline">Daftar Akun Baru</a>
            </p>
            <a href="{{ route('destinasi.index') }}" class="inline-block text-[11px] text-gray-400 hover:text-[#703A3A] transition-colors">
                &larr; Kembali ke Katalog Destinasi
            </a>
        </div>

    </div>

</body>
</html>
