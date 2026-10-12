<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Destinara — Menghidupkan Ruang Belajar Nyata di Tapak Nusantara')</title>
    <meta name="description" content="@yield('meta_description', 'Inisiatif pendidikan lapangan dan riset berbasis komunitas yang menjembatani kurikulum institusi dengan kearifan tapak dan pengetahuan lokal di seluruh Nusantara.')">

    <!-- Favicon Configuration (dari comprodestinara) -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}"/>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}"/>
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}"/>
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}"/>
    <link rel="manifest" href="{{ asset('site.webmanifest') }}"/>
    <meta name="theme-color" content="#703a3a"/>

    <!-- Google Fonts & Material Symbols -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Work+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..24,400,0..1,0" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: #FAF7F5;
            color: #1E1B1B;
        }
    </style>
    @stack('styles')
</head>
<body class="min-h-screen flex flex-col antialiased selection:bg-[#703A3A] selection:text-white" 
      x-data="{ mobileMenuOpen: false, scrolled: false }" 
      x-init="scrolled = (window.pageYOffset > 20); window.addEventListener('scroll', () => { scrolled = (window.pageYOffset > 20) }, { passive: true })">

    <!-- ==================== HEADER (POMAII ARCHITECTURE: TRANSPARENT ON TOP, SOLID ON SCROLL) ==================== -->
    <header class="fixed top-0 inset-x-0 z-50 transition-all duration-300"
            :class="scrolled ? 'bg-white/95 backdrop-blur-md shadow-xs border-b border-gray-100' : 'bg-transparent border-b border-transparent shadow-none'">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            
            <!-- Left: Brand Logo & Tagline -->
            <a href="{{ route('destinasi.index') }}" class="flex items-center gap-3 group shrink-0" aria-label="Destinara Etalase Tapak">
                <img src="{{ asset('assets/img/logo-mark-tight.png') }}" alt="Destinara Logo" class="h-10 w-auto object-contain transition-transform group-hover:scale-105">
                <div class="flex flex-col">
                    <span class="text-xl sm:text-2xl font-black text-[#1A1A1A] tracking-tight leading-none">DESTINARA</span>
                    <span class="text-[10px] text-gray-500 font-medium tracking-wide mt-1 leading-none">Ruang Belajar Tapak Nusantara</span>
                </div>
            </a>

            <!-- Center: Destinara System Navigation Links (Pomaii Clean Spacing & Interactive Hover Underline) -->
            <nav class="hidden md:flex items-center gap-7 lg:gap-8" aria-label="Navigasi Utama">
                <a href="{{ route('destinasi.index') }}" 
                   class="group text-sm font-semibold transition-colors relative py-1 {{ request()->routeIs('destinasi.*') ? 'text-[#703A3A] font-bold' : 'text-gray-800 hover:text-[#703A3A]' }}">
                    <span>Katalog Destinasi</span>
                    <span class="absolute -bottom-1 left-0 right-0 h-[2px] bg-[#703A3A] rounded-full transition-all duration-300 origin-left {{ request()->routeIs('destinasi.*') ? 'scale-x-100 opacity-100' : 'scale-x-0 opacity-0 group-hover:scale-x-100 group-hover:opacity-100' }}"></span>
                </a>

                <a href="{{ route('cara-kerja') }}" 
                   class="group text-sm font-medium transition-colors relative py-1 {{ request()->routeIs('cara-kerja') ? 'text-[#703A3A] font-bold' : 'text-gray-800 hover:text-[#703A3A]' }}">
                    <span>Alur &amp; Cara Kerja</span>
                    <span class="absolute -bottom-1 left-0 right-0 h-[2px] bg-[#703A3A] rounded-full transition-all duration-300 origin-left {{ request()->routeIs('cara-kerja') ? 'scale-x-100 opacity-100' : 'scale-x-0 opacity-0 group-hover:scale-x-100 group-hover:opacity-100' }}"></span>
                </a>

                <a href="{{ route('register') }}" 
                   class="group text-sm font-medium transition-colors relative py-1 {{ request()->routeIs('register') ? 'text-[#703A3A] font-bold' : 'text-gray-800 hover:text-[#703A3A]' }}">
                    <span>Kemitraan Adat</span>
                    <span class="absolute -bottom-1 left-0 right-0 h-[2px] bg-[#703A3A] rounded-full transition-all duration-300 origin-left {{ request()->routeIs('register') ? 'scale-x-100 opacity-100' : 'scale-x-0 opacity-0 group-hover:scale-x-100 group-hover:opacity-100' }}"></span>
                </a>

                <span class="hidden xl:inline-flex items-center gap-1.5 px-3 py-1 bg-[#51634b]/10 text-[#51634b] text-xs font-bold rounded-full">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#51634b]"></span>
                    <span>100% Berdokumen Resmi</span>
                </span>
            </nav>

            <!-- Right: Exactly 3 Pomaii Circular Outline Buttons (Search, Wishlist, Drawer Menu) -->
            <div class="flex items-center gap-2.5 sm:gap-3 shrink-0">
                
                <!-- Circle 1: Search Button -->
                <a href="{{ route('destinasi.index') }}" 
                   title="Cari Tapak & Destinasi"
                   class="w-10 h-10 rounded-full border border-gray-200/90 bg-white/80 backdrop-blur-xs flex items-center justify-center text-gray-700 hover:text-[#703A3A] hover:border-[#703A3A] hover:bg-gray-50 transition-all cursor-pointer shadow-xs">
                    <span class="material-symbols-outlined text-[19px]">search</span>
                </a>

                <!-- Circle 2: Saved / Wishlist Button -->
                <a href="{{ route('destinasi.index') }}" 
                   title="Tapak Tersimpan"
                   class="w-10 h-10 rounded-full border border-gray-200/90 bg-white/80 backdrop-blur-xs flex items-center justify-center text-gray-700 hover:text-[#703A3A] hover:border-[#703A3A] hover:bg-gray-50 transition-all cursor-pointer shadow-xs">
                    <span class="material-symbols-outlined text-[19px]">favorite_border</span>
                </a>

                <!-- Circle 3: Drawer Menu Toggle (Desktop & Mobile) -->
                <button type="button" 
                        @click="mobileMenuOpen = true" 
                        title="Menu & Akun"
                        aria-label="Buka Menu"
                        class="w-10 h-10 rounded-full border border-gray-200/90 bg-white/80 backdrop-blur-xs flex items-center justify-center text-gray-700 hover:text-[#703A3A] hover:border-[#703A3A] hover:bg-gray-50 transition-all cursor-pointer shadow-xs">
                    <span class="material-symbols-outlined text-[20px]">menu</span>
                </button>

            </div>
        </div>

        <!-- Slide-out Drawer Overlay Backdrop -->
        <div x-show="mobileMenuOpen" 
             x-transition:enter="transition-opacity ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="mobileMenuOpen = false"
             class="fixed inset-0 bg-black/40 backdrop-blur-xs z-50"
             style="display: none;"></div>

        <!-- Slide-out Menu Drawer Panel (Desktop & Mobile) -->
        <div x-show="mobileMenuOpen" 
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             class="fixed inset-y-0 right-0 w-full sm:w-[420px] bg-white z-50 shadow-2xl flex flex-col justify-between p-6 sm:p-8 overflow-y-auto"
             role="dialog" aria-modal="true"
             style="display: none;">
            <div>
                <!-- Top Brand Header & Close Button -->
                <div class="flex items-center justify-between pb-5 mb-5 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('assets/img/logo-mark-tight.png') }}" alt="Destinara" class="h-9 w-auto">
                        <div>
                            <span class="font-serif text-xl font-bold text-[#1A1A1A] block leading-none">DESTINARA</span>
                            <span class="text-[10px] text-gray-500 font-medium">Ruang Belajar Tapak Nusantara</span>
                        </div>
                    </div>
                    <button type="button" @click="mobileMenuOpen = false" aria-label="Tutup Menu" class="w-10 h-10 rounded-full border border-gray-200 flex items-center justify-center text-gray-500 hover:text-[#703A3A] hover:border-[#703A3A] transition-colors">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <!-- Account / Portal Card -->
                <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100 mb-6">
                    @auth
                        @php
                            $userRole = Auth::user()->role ?? 'buyer';
                            $roleRoute = match($userRole) {
                                'buyer' => 'buyer.dashboard',
                                'mitra' => 'mitra.dashboard',
                                'admin' => 'admin.dashboard',
                                'superadmin' => 'superadmin.dashboard',
                                default => 'buyer.dashboard',
                            };
                            $roleLabel = match($userRole) {
                                'buyer' => 'Buyer Institusi',
                                'mitra' => 'Mitra Pengelola Tapak',
                                'admin' => 'Admin Sistem',
                                'superadmin' => 'Super Admin',
                                default => 'Dashboard',
                            };
                        @endphp
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <span class="text-xs text-gray-500 block">Masuk sebagai</span>
                                <span class="font-bold text-sm text-gray-900">{{ Auth::user()->name ?? 'Pengguna' }}</span>
                            </div>
                            <span class="px-2.5 py-1 text-[11px] font-bold rounded-full bg-[#703A3A]/10 text-[#703A3A]">{{ $roleLabel }}</span>
                        </div>
                        <a href="{{ route($roleRoute) }}" class="w-full bg-[#703A3A] hover:bg-[#561D1E] text-white py-2.5 rounded-full text-xs font-bold transition-all flex items-center justify-center gap-2 shadow-sm">
                            <span class="material-symbols-outlined text-[16px]">dashboard</span>
                            <span>Buka Ruang Kerja</span>
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="mt-2 text-center">
                            @csrf
                            <button type="submit" class="text-xs text-gray-500 hover:text-red-600 transition-colors">Keluar (Logout)</button>
                        </form>
                    @else
                        <div class="mb-3">
                            <span class="text-xs font-bold text-gray-900 block">Portal Layanan &amp; Kemitraan</span>
                            <span class="text-[11px] text-gray-500">Akses akun sekolah, universitas, peneliti, dan mitra tapak.</span>
                        </div>
                        <div class="flex flex-col gap-2">
                            <a href="{{ route('login') }}" class="w-full bg-[#703A3A] hover:bg-[#561D1E] text-white py-2.5 rounded-full text-xs font-bold transition-all text-center shadow-sm">
                                Masuk ke Sistem
                            </a>
                            <a href="{{ route('register') }}" class="w-full border border-gray-200 hover:border-[#703A3A] text-gray-700 hover:text-[#703A3A] py-2.5 rounded-full text-xs font-bold transition-all text-center">
                                Daftarkan Akun Baru
                            </a>
                        </div>
                    @endauth
                </div>

                <!-- Navigation Links List -->
                <div class="space-y-1">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-gray-400 px-3 block mb-2">Navigasi Utama</span>
                    <a href="{{ route('destinasi.index') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('destinasi.*') ? 'bg-[#703A3A]/10 text-[#703A3A]' : 'text-gray-700 hover:bg-gray-50' }}">
                        <span>Katalog Destinasi</span>
                        <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                    </a>
                    <a href="{{ route('cara-kerja') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('cara-kerja') ? 'bg-[#703A3A]/10 text-[#703A3A]' : 'text-gray-700 hover:bg-gray-50' }}">
                        <span>Alur &amp; Cara Kerja</span>
                        <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                    </a>
                    <a href="{{ route('register') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('register') ? 'bg-[#703A3A]/10 text-[#703A3A]' : 'text-gray-700 hover:bg-gray-50' }}">
                        <span>Pendaftaran Kemitraan Adat</span>
                        <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                    </a>
                </div>
            </div>

            <!-- Drawer Bottom Help -->
            <div class="pt-6 border-t border-gray-100 text-xs text-gray-500 space-y-2">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px] text-[#703A3A]">call</span>
                    <a href="https://wa.me/6282116200363" target="_blank" class="hover:text-[#703A3A] font-mono">+62 821-1620-0363</a>
                </div>
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px] text-[#703A3A]">mail</span>
                    <a href="mailto:halo@destinara.id" class="hover:text-[#703A3A]">halo@destinara.id</a>
                </div>
                <p class="text-[11px] text-gray-400 pt-2">&copy; {{ date('Y') }} Destinara. Inisiatif Tapak Terverifikasi FPIC.</p>
            </div>
        </div>
    </header>

    <!-- Main Page Content -->
    <main class="flex-grow">
        @yield('content')
    </main>

    <!-- ==================== FOOTER (DESTINARA OFFICIAL 4-COLUMN ARCHITECTURE — IDENTICAL TO COMPRO) ==================== -->
    <footer class="w-full bg-[#FAF9F6] py-14 lg:py-18 mt-16 border-t border-gray-200/60 text-gray-600 transition-colors">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Top Brand Strip -->
            <div class="pb-10 mb-10 border-b border-gray-200/60 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <a href="{{ route('destinasi.index') }}" class="inline-flex items-center gap-3">
                    <img src="{{ asset('assets/img/logo-mark-tight.png') }}" alt="Destinara" class="h-10 w-auto object-contain">
                    <div>
                        <span class="text-2xl font-black text-[#1A1A1A] tracking-tight block leading-none">DESTINARA</span>
                        <span class="text-[10px] text-gray-500 font-medium tracking-wide mt-1 block">Ruang Belajar Tapak Nusantara</span>
                    </div>
                </a>
                <div class="flex items-center gap-3 text-xs text-gray-500">
                    <span class="w-2 h-2 rounded-full bg-[#51634b]"></span>
                    <span>Inisiatif Pendidikan Lapangan &amp; Riset Komunitas Terverifikasi FPIC Adat</span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-12 pb-12 border-b border-gray-200/60">
                
                <!-- Kolom 1: Perusahaan -->
                <div class="flex flex-col gap-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-900">Perusahaan</h4>
                    <ul class="flex flex-col gap-2.5 text-xs text-gray-600">
                        <li>
                            <a href="https://destinara.id/tentang-kami" target="_blank" rel="noopener noreferrer" class="hover:text-[#703A3A] transition-colors">Tentang Destinara</a>
                        </li>
                        <li>
                            <a href="https://destinara.id/tentang-kami#filosofi" target="_blank" rel="noopener noreferrer" class="hover:text-[#703A3A] transition-colors">Filosofi Pendidikan</a>
                        </li>
                        <li>
                            <a href="https://destinara.id/tentang-kami#rekam-jejak" target="_blank" rel="noopener noreferrer" class="hover:text-[#703A3A] transition-colors">Rekam Jejak &amp; Dampak</a>
                        </li>
                        <li>
                            <a href="{{ route('destinasi.index') }}" class="hover:text-[#703A3A] transition-colors font-semibold text-[#703A3A]">Katalog Destinasi Tapak</a>
                        </li>
                        <li>
                            <a href="https://destinara.id/syarat-ketentuan" target="_blank" rel="noopener noreferrer" class="hover:text-[#703A3A] transition-colors">Syarat &amp; Ketentuan</a>
                        </li>
                        <li>
                            <a href="https://destinara.id/kebijakan-privasi" target="_blank" rel="noopener noreferrer" class="hover:text-[#703A3A] transition-colors">Kebijakan Privasi</a>
                        </li>
                        <li>
                            <a href="https://destinara.id/kebijakan-privasi/en" target="_blank" rel="noopener noreferrer" class="hover:text-[#703A3A] transition-colors">Privacy Policy (EN)</a>
                        </li>
                    </ul>
                </div>

                <!-- Kolom 2: Untuk Pengguna -->
                <div class="flex flex-col gap-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-900">Untuk Pengguna</h4>
                    <ul class="flex flex-col gap-2.5 text-xs text-gray-600">
                        <li>
                            <a href="https://destinara.id/untuk-sekolah" target="_blank" rel="noopener noreferrer" class="hover:text-[#703A3A] transition-colors">Program Untuk Sekolah</a>
                        </li>
                        <li>
                            <a href="https://destinara.id/untuk-peneliti" target="_blank" rel="noopener noreferrer" class="hover:text-[#703A3A] transition-colors">Kolaborasi Riset Peneliti</a>
                        </li>
                        <li>
                            <a href="https://destinara.id/mitra-desa" target="_blank" rel="noopener noreferrer" class="hover:text-[#703A3A] transition-colors">Kemitraan Desa &amp; Adat</a>
                        </li>
                        <li>
                            <a href="{{ route('cara-kerja') }}" class="hover:text-[#703A3A] transition-colors">Alur &amp; Prosedur Kemitraan</a>
                        </li>
                        <li>
                            <a href="https://destinara.id/tentang-kami#fpic" target="_blank" rel="noopener noreferrer" class="hover:text-[#703A3A] transition-colors">Etika Persetujuan FPIC</a>
                        </li>
                    </ul>
                </div>

                <!-- Kolom 3: Publikasi & Layanan Sistem -->
                <div class="flex flex-col gap-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-900">Publikasi &amp; Layanan</h4>
                    <ul class="flex flex-col gap-2.5 text-xs text-gray-600">
                        <li>
                            <a href="https://destinara.id/cerita" target="_blank" rel="noopener noreferrer" class="hover:text-[#703A3A] transition-colors">Cerita Lapangan Terbaru</a>
                        </li>
                        <li>
                            <a href="{{ route('register') }}" class="hover:text-[#703A3A] transition-colors">Pendaftaran Akun Baru</a>
                        </li>
                        <li>
                            <a href="{{ route('login') }}" class="hover:text-[#703A3A] transition-colors">Masuk ke Portal Reservasi</a>
                        </li>
                        <li>
                            <a href="https://destinara.id/untuk-sekolah#silabus" target="_blank" rel="noopener noreferrer" class="hover:text-[#703A3A] transition-colors">Panduan Silabus &amp; Asesmen</a>
                        </li>
                    </ul>
                </div>

                <!-- Kolom 4: Sekretariat & Narahubung -->
                <div class="flex flex-col gap-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-900">Sekretariat &amp; Narahubung</h4>
                    <div class="flex flex-col gap-2 text-xs text-gray-600">
                        <p>Sleman: Jl. Kaliurang KM 14, Sinduharjo, Ngaglik</p>
                        <p>Menteng: Jl. Teuku Umar No. 12, Jakarta Pusat</p>
                        <div class="flex flex-col gap-1.5 pt-2">
                            <span class="text-gray-900 font-semibold text-[11px] uppercase tracking-wider">Narahubung WhatsApp:</span>
                            <a href="https://wa.me/6282116200363" target="_blank" rel="noopener noreferrer" class="hover:text-[#703A3A] transition-colors font-mono">
                                Maya: +62 821-1620-0363
                            </a>
                            <a href="https://wa.me/6285894860696" target="_blank" rel="noopener noreferrer" class="hover:text-[#703A3A] transition-colors font-mono">
                                Azki: +62 858-9486-0696
                            </a>
                            <a href="https://wa.me/6285774410978" target="_blank" rel="noopener noreferrer" class="hover:text-[#703A3A] transition-colors font-mono">
                                Ryan: +62 857-7441-0978
                            </a>
                        </div>
                        <div class="flex flex-col gap-1 pt-2">
                            <span class="text-gray-900 font-semibold text-[11px] uppercase tracking-wider">Surel Resmi:</span>
                            <a href="mailto:partnership@destinara.id" class="hover:text-[#703A3A] transition-colors">
                                partnership@destinara.id
                            </a>
                            <a href="mailto:hello@destinara.id" class="hover:text-[#703A3A] transition-colors">
                                hello@destinara.id
                            </a>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Bottom Bar -->
            <div class="pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-gray-500 text-center sm:text-left">
                <p>&copy; {{ date('Y') }} PT DESTINARA CHAKRAWAL ARTHA. Hak cipta dilindungi undang-undang. | Dikembangkan oleh vexahostcloud</p>
                <div class="flex items-center justify-center gap-3 text-xs">
                    <a href="https://destinara.id/syarat-ketentuan" target="_blank" rel="noopener noreferrer" class="hover:text-[#703A3A] transition-colors font-medium">Syarat &amp; Ketentuan</a>
                    <span>•</span>
                    <a href="https://destinara.id/kebijakan-privasi" target="_blank" rel="noopener noreferrer" class="hover:text-[#703A3A] transition-colors font-medium">Kebijakan Privasi</a>
                    <span>•</span>
                    <a href="https://destinara.id/kebijakan-privasi/en" target="_blank" rel="noopener noreferrer" class="hover:text-[#703A3A] transition-colors font-medium">Privacy Policy (EN)</a>
                </div>
                <p class="italic text-[#703A3A]">Menghubungkan ruang kelas dengan kearifan tapak dan pengetahuan lokal.</p>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
