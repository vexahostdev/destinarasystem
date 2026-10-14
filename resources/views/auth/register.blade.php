<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#FAF7F5]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pendaftaran Akun Lapangan — Destinara</title>

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
<body class="min-h-screen flex items-center justify-center p-4 sm:p-6 lg:p-10 antialiased selection:bg-[#703A3A] selection:text-white bg-[#FAF7F5]" x-data="{ role: 'buyer' }">

    <div class="max-w-6xl w-full bg-white rounded-3xl shadow-[0_20px_60px_rgba(112,58,58,0.12)] border border-gray-100 overflow-hidden grid grid-cols-1 lg:grid-cols-12 min-h-[640px]">
        
        <!-- Left Panel: Deep Maroon Branding -->
        <div class="lg:col-span-5 bg-gradient-to-br from-[#703A3A] via-[#5C2626] to-[#401414] text-white p-8 sm:p-12 flex flex-col justify-between relative overflow-hidden">
            <!-- Top Brand -->
            <div class="space-y-6 relative z-10">
                <a href="{{ route('destinasi.index') }}" class="flex items-center gap-3 group">
                    <img src="{{ asset('assets/img/logo-mark-tight.png') }}" alt="Destinara Logo" class="w-10 h-10 object-contain drop-shadow-md brightness-110">
                    <div>
                        <span class="text-2xl font-extrabold tracking-tight text-white block leading-none">DESTINARA</span>
                        <span class="text-[10px] tracking-widest text-[#D4E9CA] uppercase font-semibold">Ruang Belajar Tapak</span>
                    </div>
                </a>

                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-white/10 backdrop-blur-md rounded-full text-[#FDEAE5] text-xs font-semibold uppercase tracking-wider">
                    <span class="w-2 h-2 rounded-full bg-[#D4E9CA] animate-pulse"></span>
                    <span>Pintu Masuk Resmi Ekosistem</span>
                </div>

                <h2 class="text-2xl sm:text-3xl font-extrabold text-white leading-tight">
                    Menghubungkan Ruang Kelas dengan Alam Nusantara.
                </h2>

                <div class="p-5 bg-white/10 backdrop-blur-md rounded-2xl border border-white/10 text-xs sm:text-sm text-white/90 space-y-2">
                    <p class="leading-relaxed">
                        "Destinara merampingkan birokrasi perizinan riset, sehingga guru dan mahasiswa dapat fokus menggali kearifan lokal secara etis, aman, dan terlindungi hukum."
                    </p>
                    <div class="font-bold text-white text-xs pt-1">
                        Tim Kurasi Lapang Destinara
                    </div>
                </div>
            </div>

            <!-- Bottom Stats -->
            <div class="pt-8 border-t border-white/10 grid grid-cols-3 gap-4 text-center relative z-10 mt-8 lg:mt-0">
                <div>
                    <span class="text-2xl font-extrabold text-white block">15+</span>
                    <span class="text-[10px] text-white/70 uppercase tracking-wider font-semibold">Desa Mitra</span>
                </div>
                <div>
                    <span class="text-2xl font-extrabold text-[#D4E9CA] block">1.200+</span>
                    <span class="text-[10px] text-white/70 uppercase tracking-wider font-semibold">Pelajar</span>
                </div>
                <div>
                    <span class="text-2xl font-extrabold text-[#FFD7A7] block">100%</span>
                    <span class="text-[10px] text-white/70 uppercase tracking-wider font-semibold">Legalitas FPIC</span>
                </div>
            </div>
        </div>

        <!-- Right Panel: Registration Form -->
        <div class="lg:col-span-7 p-8 sm:p-12 flex flex-col justify-center bg-white space-y-6">
            
            <!-- Top Switch: Masuk vs Daftar -->
            <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                <div class="flex items-center gap-6">
                    <a href="{{ route('login') }}" class="text-xs font-bold uppercase tracking-wider text-gray-400 hover:text-[#703A3A] transition-colors pb-1">
                        Masuk Akun
                    </a>
                    <span class="text-xs font-bold uppercase tracking-wider text-[#703A3A] border-b-2 border-[#703A3A] pb-1">
                        Daftar Akun Baru
                    </span>
                </div>
                <a href="{{ route('destinasi.index') }}" class="text-xs text-gray-500 hover:text-[#703A3A] flex items-center gap-1 font-semibold transition-colors">
                    <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                    <span>Katalog Destinasi</span>
                </a>
            </div>

            <div>
                <h3 class="text-2xl sm:text-3xl font-extrabold text-[#1E1B1B]">Pendaftaran Akun</h3>
                <p class="text-xs sm:text-sm text-gray-500 mt-1">Pilih tipe akun Anda untuk memulai proses reservasi.</p>
            </div>

            <!-- Role Selector (Buyer vs Partner) -->
            <div class="space-y-2">
                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider">Tipe Pengguna</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div @click="role = 'buyer'" 
                         :class="role === 'buyer' ? 'border-[#703A3A] bg-[#703A3A]/5 ring-2 ring-[#703A3A]/30' : 'border-gray-200 bg-white hover:border-gray-300'" 
                         class="p-4 rounded-2xl border cursor-pointer transition-all flex items-start gap-3">
                        <input type="radio" name="role_select" value="buyer" :checked="role === 'buyer'" class="mt-1 text-[#703A3A] focus:ring-[#703A3A]">
                        <div>
                            <span class="font-bold text-xs text-[#1E1B1B] block">Instansi Pendidikan</span>
                            <span class="text-[11px] text-gray-500 block mt-0.5">Sekolah, Kampus, & Peneliti (Buyer)</span>
                        </div>
                    </div>
                    <div @click="role = 'partner'" 
                         :class="role === 'partner' ? 'border-[#703A3A] bg-[#703A3A]/5 ring-2 ring-[#703A3A]/30' : 'border-gray-200 bg-white hover:border-gray-300'" 
                         class="p-4 rounded-2xl border cursor-pointer transition-all flex items-start gap-3">
                        <input type="radio" name="role_select" value="partner" :checked="role === 'partner'" class="mt-1 text-[#703A3A] focus:ring-[#703A3A]">
                        <div>
                            <span class="font-bold text-xs text-[#1E1B1B] block">Pengelola Destinasi</span>
                            <span class="text-[11px] text-gray-500 block mt-0.5">Desa Wisata & Adat (Mitra)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alert Errors -->
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

            <!-- Registration Form Inputs -->
            <form action="{{ route('register.post') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="role" :value="role">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-[#1E1B1B] mb-1.5">Nama PIC</label>
                        <input type="text" name="name" value="{{ old('name') }}" required placeholder="Contoh: Ryan Prasetya" 
                               class="w-full text-xs px-3.5 py-3 bg-[#FAF7F5] rounded-xl border border-gray-200 text-[#1E1B1B] focus:outline-none focus:border-[#703A3A] focus:ring-1 focus:ring-[#703A3A] transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#1E1B1B] mb-1.5" x-text="role === 'buyer' ? 'Nama Sekolah / Kampus' : 'Nama Desa / Sanggar'"></label>
                        <input type="text" name="org_name" value="{{ old('org_name') }}" required placeholder="Contoh: SMA Negeri 1 / Desa Wonosadi" 
                               class="w-full text-xs px-3.5 py-3 bg-[#FAF7F5] rounded-xl border border-gray-200 text-[#1E1B1B] focus:outline-none focus:border-[#703A3A] focus:ring-1 focus:ring-[#703A3A] transition-all">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-[#1E1B1B] mb-1.5">Email Resmi</label>
                        <input type="email" name="email" value="{{ old('email') }}" required placeholder="nama@instansi.sch.id" 
                               class="w-full text-xs px-3.5 py-3 bg-[#FAF7F5] rounded-xl border border-gray-200 text-[#1E1B1B] focus:outline-none focus:border-[#703A3A] focus:ring-1 focus:ring-[#703A3A] transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#1E1B1B] mb-1.5">Nomor WhatsApp</label>
                        <input type="tel" name="phone" value="{{ old('phone') }}" required placeholder="0812-xxxx-xxxx" 
                               class="w-full text-xs px-3.5 py-3 bg-[#FAF7F5] rounded-xl border border-gray-200 text-[#1E1B1B] focus:outline-none focus:border-[#703A3A] focus:ring-1 focus:ring-[#703A3A] transition-all">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-[#1E1B1B] mb-1.5">Kata Sandi</label>
                    <input type="password" name="password" required placeholder="Minimal 8 karakter" 
                           class="w-full text-xs px-3.5 py-3 bg-[#FAF7F5] rounded-xl border border-gray-200 text-[#1E1B1B] focus:outline-none focus:border-[#703A3A] focus:ring-1 focus:ring-[#703A3A] transition-all">
                </div>

                <div class="flex items-start gap-2 pt-1">
                    <input type="checkbox" required id="tos" class="mt-0.5 rounded text-[#703A3A] focus:ring-[#703A3A]">
                    <label for="tos" class="text-[11px] text-gray-500 leading-tight">
                        Saya menyetujui <a href="#" class="text-[#703A3A] font-semibold underline">Syarat & Ketentuan</a> serta bersedia mematuhi <a href="#" class="text-[#703A3A] font-semibold underline">Protokol Etika Tapak</a>.
                    </label>
                </div>

                <button type="submit" 
                        class="w-full bg-[#703A3A] hover:bg-[#561D1E] text-white py-3.5 px-6 rounded-full font-bold text-sm shadow-md hover:shadow-lg transition-all cursor-pointer">
                    Daftar Akun Sekarang &rarr;
                </button>
            </form>

            <div class="pt-2 text-center text-xs text-gray-500">
                Sudah memiliki akun? <a href="{{ route('login') }}" class="text-[#703A3A] font-bold hover:underline">Masuk ke Sistem</a>
            </div>

        </div>

    </div>

</body>
</html>
