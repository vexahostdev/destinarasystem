<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#FFFFFF]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pemeliharaan Sistem — Destinara</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}"/>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..24,400,0..1,0" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 antialiased selection:bg-[#1F2B45] selection:text-white bg-[#FFFFFF]">

    <div class="max-w-md w-full bg-white rounded-3xl p-8 sm:p-10 shadow-[0_20px_50px_rgba(31, 43, 69,0.1)] border border-gray-100 relative z-10 text-center space-y-5">
        
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-[#1F2B45]/10 text-[#1F2B45] mb-1">
            <span class="material-symbols-outlined text-4xl">construction</span>
        </div>

        <div>
            <span class="text-2xl font-extrabold tracking-tight text-[#16203A] block">DESTINARA</span>
            <span class="text-[10px] tracking-widest text-[#1F2B45] uppercase font-bold">Ruang Belajar Tapak Nusantara</span>
        </div>

        <h2 class="text-xl font-bold text-[#111827]">Sistem Sedang Ditingkatkan</h2>

        <p class="text-xs text-gray-500 leading-relaxed">
            {{ $message ?? 'Kami sedang melakukan pemeliharaan dan peningkatan performa sistem secara berkala. Layanan akan segera kembali aktif.' }}
        </p>

        <div class="pt-2 text-xs text-gray-400">
            Butuh bantuan mendesak? Hubungi kami di <a href="mailto:{{ setting('company_email', 'halo@destinara.my.id') }}" class="text-[#1F2B45] font-semibold underline">{{ setting('company_email', 'halo@destinara.my.id') }}</a>
        </div>

    </div>

</body>
</html>
