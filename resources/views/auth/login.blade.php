<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#FFFFFF]">
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
    <meta name="theme-color" content="#1F2B45"/>

    <!-- Google Fonts & Material Symbols -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..24,400,0..1,0" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 antialiased selection:bg-[#1F2B45] selection:text-white bg-[#FFFFFF]">

    <!-- Centered Modern Login Card -->
    <div class="max-w-md w-full bg-white rounded-3xl p-8 sm:p-10 shadow-[0_20px_50px_rgba(31, 43, 69,0.1)] border border-gray-100 relative z-10 space-y-6">
        
        <!-- Header & Logo -->
        <div class="text-center space-y-3">
            <a href="{{ route('destinasi.index') }}" class="inline-flex items-center justify-center gap-2 group mb-1">
                <img src="{{ asset('assets/img/logo-mark-tight.png') }}" alt="Logo Destinara" class="w-12 h-12 object-contain group-hover:scale-105 transition-transform">
            </a>
            <div>
                <span class="text-2xl font-extrabold tracking-tight text-[#16203A] block">DESTINARA</span>
                <span class="text-[10px] tracking-widest text-[#1F2B45] uppercase font-bold">Ruang Belajar Tapak Nusantara</span>
            </div>
            <h2 class="text-xl font-bold text-[#111827] pt-1">Masuk Akun Lapangan</h2>
            <p class="text-xs text-gray-500">
                Akses reservasi jadwal institusi atau panel pengelola tapak.
            </p>
        </div>

        <!-- Alert Flash Message -->
        @if(session('success'))
            <div class="p-3 bg-emerald-50 rounded-2xl border border-emerald-200 text-xs text-emerald-800 flex items-center gap-2">
                <span class="material-symbols-outlined text-base text-emerald-600 shrink-0">check_circle</span>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="p-3 bg-red-50 rounded-2xl border border-red-200 text-xs text-red-800 flex items-center gap-2">
                <span class="material-symbols-outlined text-base text-red-600 shrink-0">error</span>
                <span>{{ session('error') }}</span>
            </div>
        @endif
        @if(session('warning'))
            <div class="p-3 bg-amber-50 rounded-2xl border border-amber-200 text-xs text-amber-800 flex items-center gap-2">
                <span class="material-symbols-outlined text-base text-amber-600 shrink-0">warning</span>
                <span>{{ session('warning') }}</span>
            </div>
        @endif
        @if($errors->any())
            <div class="p-3 bg-red-50 rounded-2xl border border-red-200 text-xs text-red-800 space-y-1">
                @foreach($errors->all() as $error)
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-base text-red-600 shrink-0">error</span>
                        <span>{{ $error }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Form Login -->
        <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
            @csrf
            
            <div>
                <label class="block text-xs font-bold text-[#111827] mb-1.5">Email Resmi</label>
                <div class="relative">
                    <input type="email" name="email" value="{{ old('email') }}" required placeholder="nama@instansi.sch.id" 
                           class="w-full text-xs pl-10 pr-4 py-3 bg-[#FFFFFF] rounded-xl border border-gray-200 text-[#111827] focus:outline-none focus:border-[#1F2B45] focus:ring-1 focus:ring-[#1F2B45] transition-all">
                    <span class="material-symbols-outlined absolute left-3 top-3 text-lg text-gray-400">mail</span>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-[#111827] mb-1.5">Kata Sandi</label>
                <div class="relative">
                    <input type="password" name="password" required placeholder="Masukkan kata sandi" 
                           class="w-full text-xs pl-10 pr-4 py-3 bg-[#FFFFFF] rounded-xl border border-gray-200 text-[#111827] focus:outline-none focus:border-[#1F2B45] focus:ring-1 focus:ring-[#1F2B45] transition-all">
                    <span class="material-symbols-outlined absolute left-3 top-3 text-lg text-gray-400">lock</span>
                </div>
            </div>

            <div class="flex items-center justify-between text-xs pt-1">
                <label class="flex items-center gap-2 text-gray-600 cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded text-[#1F2B45] focus:ring-[#1F2B45]">
                    <span>Ingat saya</span>
                </label>
                <a href="{{ route('password.request') }}" class="text-[#1F2B45] font-semibold hover:underline">
                    Lupa sandi?
                </a>
            </div>

            <button type="submit" 
                    class="w-full bg-[#1F2B45] hover:bg-[#16203A] text-white py-3.5 px-6 rounded-2xl font-bold text-sm shadow-md hover:shadow-lg transition-all cursor-pointer">
                Masuk ke Sistem &rarr;
            </button>
        </form>



        <!-- Institutional Security Notice -->
        <div class="p-3 bg-emerald-50 rounded-2xl border border-emerald-200/50 text-[11px] text-emerald-800 flex items-center gap-2">
            <span class="material-symbols-outlined text-base text-emerald-600 shrink-0">security</span>
            <span>Autentikasi terenkripsi & data institusi terproteksi</span>
        </div>

        <!-- Footer link -->
        <div class="pt-2 border-t border-gray-100 text-center text-xs space-y-2">
            <p class="text-gray-600">
                Belum memiliki akun? <a href="{{ route('register') }}" class="text-[#1F2B45] font-bold hover:underline">Daftar Akun Baru</a>
            </p>
            <a href="{{ route('destinasi.index') }}" class="inline-block text-[11px] text-gray-400 hover:text-[#1F2B45] transition-colors">
                &larr; Kembali ke Katalog Destinasi
            </a>
        </div>

    </div>

    @livewireScriptConfig
</body>
</html>
