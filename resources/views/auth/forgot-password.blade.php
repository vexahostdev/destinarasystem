<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#FAF7F5]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lupa Kata Sandi — Destinara</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}"/>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..24,400,0..1,0" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 antialiased selection:bg-[#703A3A] selection:text-white bg-[#FAF7F5]">

    <div class="max-w-md w-full bg-white rounded-3xl p-8 sm:p-10 shadow-[0_20px_50px_rgba(112,58,58,0.1)] border border-gray-100 relative z-10 space-y-6">
        
        <div class="text-center space-y-3">
            <a href="{{ route('home') }}" class="inline-flex items-center justify-center gap-2 group mb-1">
                <img src="{{ asset('assets/img/logo-mark-tight.png') }}" alt="Logo Destinara" class="w-12 h-12 object-contain group-hover:scale-105 transition-transform">
            </a>
            <div>
                <span class="text-2xl font-extrabold tracking-tight text-[#4A2020] block">DESTINARA</span>
                <span class="text-[10px] tracking-widest text-[#703A3A] uppercase font-bold">Ruang Belajar Tapak Nusantara</span>
            </div>
            <h2 class="text-xl font-bold text-[#1E1B1B] pt-1">Atur Ulang Kata Sandi</h2>
            <p class="text-xs text-gray-500">
                Masukkan alamat email resmi akun Anda. Kami akan mengirimkan tautan untuk mengatur ulang kata sandi.
            </p>
        </div>

        @if(session('status'))
            <div class="p-3 bg-emerald-50 rounded-2xl border border-emerald-200 text-xs text-emerald-800 flex items-center gap-2">
                <span class="material-symbols-outlined text-base text-emerald-600 shrink-0">check_circle</span>
                <span>{{ session('status') }}</span>
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

        <form action="{{ route('password.email') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-[#1E1B1B] mb-1.5">Alamat Email Terdaftar</label>
                <div class="relative">
                    <input type="email" name="email" value="{{ old('email') }}" required placeholder="nama@instansi.sch.id" 
                           class="w-full text-xs pl-10 pr-4 py-3 bg-[#FAF7F5] rounded-xl border border-gray-200 text-[#1E1B1B] focus:outline-none focus:border-[#703A3A] focus:ring-1 focus:ring-[#703A3A] transition-all">
                    <span class="material-symbols-outlined absolute left-3 top-3 text-lg text-gray-400">mail</span>
                </div>
            </div>

            <button type="submit" 
                    class="w-full bg-[#703A3A] hover:bg-[#561D1E] text-white py-3.5 px-6 rounded-full font-bold text-sm shadow-md hover:shadow-lg transition-all cursor-pointer">
                Kirim Tautan Reset &rarr;
            </button>
        </form>

        <div class="pt-2 border-t border-gray-100 text-center text-xs">
            <a href="{{ route('login') }}" class="text-[#703A3A] font-bold hover:underline">
                &larr; Kembali ke Halaman Masuk
            </a>
        </div>

    </div>

    @livewireScriptConfig
</body>
</html>
