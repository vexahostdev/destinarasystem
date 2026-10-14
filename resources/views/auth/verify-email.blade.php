<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#FAF7F5]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Alamat Email — Destinara</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}"/>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..24,400,0..1,0" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 antialiased selection:bg-[#703A3A] selection:text-white bg-[#FAF7F5]">

    <div class="max-w-md w-full bg-white rounded-3xl p-8 sm:p-10 shadow-[0_20px_50px_rgba(112,58,58,0.1)] border border-gray-100 relative z-10 space-y-6 text-center">
        
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-50 text-amber-600 mb-2">
            <span class="material-symbols-outlined text-3xl">mark_email_unread</span>
        </div>

        <div class="space-y-2">
            <h2 class="text-xl font-bold text-[#1E1B1B]">Verifikasi Alamat Email</h2>
            <p class="text-xs text-gray-500 leading-relaxed">
                Terima kasih telah mendaftar di Destinara! Sebelum mulai menjelajah atau memesan, silakan klik tautan verifikasi yang baru saja kami kirimkan ke email Anda:
            </p>
            <p class="text-xs font-bold text-[#703A3A]">{{ auth()->user()?->email }}</p>
        </div>

        @if (session('status') == 'verification-link-sent')
            <div class="p-3 bg-emerald-50 rounded-2xl border border-emerald-200 text-xs text-emerald-800 flex items-center gap-2">
                <span class="material-symbols-outlined text-base text-emerald-600 shrink-0">check_circle</span>
                <span>Tautan verifikasi baru telah dikirimkan ke alamat email Anda.</span>
            </div>
        @endif

        <div class="space-y-3 pt-2">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="w-full bg-[#703A3A] hover:bg-[#561D1E] text-white py-3 px-6 rounded-full font-bold text-xs shadow-md transition-all cursor-pointer">
                    Kirim Ulang Tautan Verifikasi
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-xs text-gray-400 hover:text-gray-600 font-semibold transition-colors">
                    Keluar / Ganti Akun
                </button>
            </form>
        </div>

    </div>

</body>
</html>
