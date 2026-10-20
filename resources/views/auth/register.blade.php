<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#FFFFFF]">
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
<body class="min-h-screen flex items-center justify-center p-4 sm:p-6 lg:p-10 antialiased selection:bg-[#1F2B45] selection:text-white bg-[#FFFFFF]">

    <div class="max-w-6xl w-full bg-white rounded-3xl shadow-[0_20px_60px_rgba(31, 43, 69,0.12)] border border-gray-100 overflow-hidden grid grid-cols-1 lg:grid-cols-12 min-h-[640px]">
        
        <!-- Left Panel: Deep Maroon Branding -->
        <div class="lg:col-span-5 bg-gradient-to-br from-[#1F2B45] via-[#2E3D5C] to-[#16203A] text-white p-8 sm:p-12 flex flex-col justify-between relative overflow-hidden">
            <!-- Top Brand -->
            <div class="space-y-6 relative z-10">
                <a href="{{ route('destinasi.index') }}" class="flex items-center gap-3 group">
                    <img src="{{ asset('assets/img/logo-mark-tight.png') }}" alt="Destinara Logo" class="w-10 h-10 object-contain drop-shadow-md brightness-110">
                    <div>
                        <span class="text-2xl font-extrabold tracking-tight text-white block leading-none">DESTINARA</span>
                        <span class="text-[10px] tracking-widest text-[#D4E9CA] uppercase font-semibold">Ruang Belajar Tapak</span>
                    </div>
                </a>

                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-white/10 backdrop-blur-md rounded-full text-[#EEF1F5] text-xs font-semibold uppercase tracking-wider">
                    <span class="w-2 h-2 rounded-full bg-[#D4E9CA] animate-pulse"></span>
                    <span>Pintu Masuk Resmi Ekosistem</span>
                </div>

                <h2 class="text-2xl sm:text-3xl font-extrabold text-white leading-tight">
                    Menghubungkan Ruang Kelas dengan Alam Nusantara.
                </h2>

                <div class="p-5 bg-white/10 backdrop-blur-md rounded-2xl border border-white/10 text-xs sm:text-sm text-white/90 space-y-2">
                    <p class="leading-relaxed">
                        "Destinara merampingkan tata kelola perizinan dan kunjungan lapangan, sehingga institusi pendidikan dan komunitas tapak dapat berkolaborasi secara etis, transparan, dan berkelanjutan."
                    </p>
                    <div class="font-bold text-white text-xs pt-1">
                        Platform Ekosistem Pembelajaran Lapang Nusantara
                    </div>
                </div>
            </div>

            <!-- Bottom Information Pillars -->
            <div class="pt-8 border-t border-white/10 grid grid-cols-3 gap-4 text-center relative z-10 mt-8 lg:mt-0">
                <div>
                    <span class="material-symbols-outlined text-2xl text-white mb-1">school</span>
                    <span class="text-[10px] text-white/80 uppercase tracking-wider font-semibold block">Edukasi Lapang</span>
                </div>
                <div>
                    <span class="material-symbols-outlined text-2xl text-[#D4E9CA] mb-1">handshake</span>
                    <span class="text-[10px] text-white/80 uppercase tracking-wider font-semibold block">Kemitraan Adat</span>
                </div>
                <div>
                    <span class="material-symbols-outlined text-2xl text-[#FFD7A7] mb-1">verified_user</span>
                    <span class="text-[10px] text-white/80 uppercase tracking-wider font-semibold block">Tata Kelola Sah</span>
                </div>
            </div>
        </div>

        <!-- Right Panel: Registration Form -->
        <div class="lg:col-span-7 p-8 sm:p-12 flex flex-col justify-center bg-white space-y-6" x-data="{ role: '{{ old('account_type', 'buyer') }}' }">
            
            <!-- Top Switch: Masuk vs Daftar -->
            <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                <div class="flex items-center gap-6">
                    <a href="{{ route('login') }}" class="text-xs font-bold uppercase tracking-wider text-gray-400 hover:text-[#1F2B45] transition-colors pb-1">
                        Masuk Akun
                    </a>
                    <span class="text-xs font-bold uppercase tracking-wider text-[#1F2B45] border-b-2 border-[#1F2B45] pb-1">
                        Daftar Akun Baru
                    </span>
                </div>
                <a href="{{ route('destinasi.index') }}" class="text-xs text-gray-500 hover:text-[#1F2B45] flex items-center gap-1 font-semibold transition-colors">
                    <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                    <span>Katalog Destinasi</span>
                </a>
            </div>

            <div>
                <h3 class="text-2xl sm:text-3xl font-extrabold text-[#111827]">Pendaftaran Akun</h3>
                <p class="text-xs sm:text-sm text-gray-500 mt-1">Pilih tipe akun Anda untuk memulai proses reservasi atau kurasi destinasi.</p>
            </div>

            <x-flash />
            <x-form-errors />

            <!-- Registration Form Inputs -->
            <form action="{{ route('register.post') }}" method="POST" class="space-y-4">
                @csrf

                <!-- Role Selector (Buyer vs Partner) -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Tipe Pengguna <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label @click="role = 'buyer'" 
                             :class="role === 'buyer' ? 'border-[#1F2B45] bg-[#1F2B45]/5 ring-2 ring-[#1F2B45]/30' : 'border-gray-200 bg-white hover:border-gray-300'" 
                             class="p-4 rounded-2xl border cursor-pointer transition-all flex items-start gap-3">
                            <input type="radio" name="account_type" value="buyer" x-model="role" class="mt-1 text-[#1F2B45] focus:ring-[#1F2B45]">
                            <div>
                                <span class="font-bold text-xs text-[#111827] block">Instansi Pendidikan</span>
                                <span class="text-[11px] text-gray-500 block mt-0.5">Sekolah, Kampus, & Peneliti (Buyer)</span>
                            </div>
                        </label>
                        <label @click="role = 'partner'" 
                             :class="role === 'partner' ? 'border-[#1F2B45] bg-[#1F2B45]/5 ring-2 ring-[#1F2B45]/30' : 'border-gray-200 bg-white hover:border-gray-300'" 
                             class="p-4 rounded-2xl border cursor-pointer transition-all flex items-start gap-3">
                            <input type="radio" name="account_type" value="partner" x-model="role" class="mt-1 text-[#1F2B45] focus:ring-[#1F2B45]">
                            <div>
                                <span class="font-bold text-xs text-[#111827] block">Pengelola Destinasi</span>
                                <span class="text-[11px] text-gray-500 block mt-0.5">Desa Wisata & Adat (Mitra)</span>
                            </div>
                        </label>
                    </div>
                    @error('account_type')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-[#111827] mb-1.5">Nama PIC <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" required placeholder="Contoh: Budi Santoso" 
                               class="w-full text-xs px-3.5 py-3 bg-[#FFFFFF] rounded-xl border border-gray-200 text-[#111827] focus:outline-none focus:border-[#1F2B45] focus:ring-1 focus:ring-[#1F2B45] transition-all">
                        @error('name')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#111827] mb-1.5">
                            <span x-text="role === 'buyer' ? 'Nama Sekolah / Kampus' : 'Nama Desa / Komunitas'"></span> <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="org_name" value="{{ old('org_name') }}" required placeholder="Nama Sekolah / Institusi / Pokdarwis" 
                               class="w-full text-xs px-3.5 py-3 bg-[#FFFFFF] rounded-xl border border-gray-200 text-[#111827] focus:outline-none focus:border-[#1F2B45] focus:ring-1 focus:ring-[#1F2B45] transition-all">
                        @error('org_name')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-[#111827] mb-1.5">Email Resmi <span class="text-red-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email') }}" required placeholder="nama@instansi.sch.id" 
                               class="w-full text-xs px-3.5 py-3 bg-[#FFFFFF] rounded-xl border border-gray-200 text-[#111827] focus:outline-none focus:border-[#1F2B45] focus:ring-1 focus:ring-[#1F2B45] transition-all">
                        @error('email')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#111827] mb-1.5">Nomor WhatsApp <span class="text-red-500">*</span></label>
                        <input type="tel" name="phone" value="{{ old('phone') }}" required placeholder="08xxxxxxxxxx" 
                               class="w-full text-xs px-3.5 py-3 bg-[#FFFFFF] rounded-xl border border-gray-200 text-[#111827] focus:outline-none focus:border-[#1F2B45] focus:ring-1 focus:ring-[#1F2B45] transition-all">
                        @error('phone')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-[#111827] mb-1.5">Kata Sandi <span class="text-red-500">*</span></label>
                        <input type="password" name="password" required placeholder="Minimal 8 karakter" 
                               class="w-full text-xs px-3.5 py-3 bg-[#FFFFFF] rounded-xl border border-gray-200 text-[#111827] focus:outline-none focus:border-[#1F2B45] focus:ring-1 focus:ring-[#1F2B45] transition-all">
                        @error('password')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#111827] mb-1.5">Konfirmasi Kata Sandi <span class="text-red-500">*</span></label>
                        <input type="password" name="password_confirmation" required placeholder="Ulangi kata sandi" 
                               class="w-full text-xs px-3.5 py-3 bg-[#FFFFFF] rounded-xl border border-gray-200 text-[#111827] focus:outline-none focus:border-[#1F2B45] focus:ring-1 focus:ring-[#1F2B45] transition-all">
                        @error('password_confirmation')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="space-y-1 pt-1">
                    <div class="flex items-start gap-2">
                        <input type="checkbox" name="terms" value="1" required id="tos" {{ old('terms') ? 'checked' : '' }} class="mt-0.5 rounded text-[#1F2B45] focus:ring-[#1F2B45]">
                        <label for="tos" class="text-[11px] text-gray-500 leading-tight">
                            Saya menyetujui <a href="{{ route('terms') }}" target="_blank" class="text-[#1F2B45] font-semibold underline hover:text-[#16203A]">Syarat &amp; Ketentuan</a> serta Kebijakan Privasi platform Destinara.
                        </label>
                    </div>
                    @error('terms')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" 
                        class="w-full bg-[#1F2B45] hover:bg-[#16203A] text-white py-3.5 px-6 rounded-2xl font-bold text-sm shadow-md hover:shadow-lg transition-all cursor-pointer">
                    Daftar Akun Sekarang &rarr;
                </button>
            </form>

            <div class="pt-2 text-center text-xs text-gray-500">
                Sudah memiliki akun? <a href="{{ route('login') }}" class="text-[#1F2B45] font-bold hover:underline">Masuk ke Sistem</a>
            </div>

        </div>

    </div>

    @livewireScriptConfig
</body>
</html>
