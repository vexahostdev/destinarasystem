@extends('layouts.auth')

@section('title', 'Masuk Akun')
@section('auth_kicker', 'Ruang Belajar Tapak')
@section('auth_title')
    Jelajahi<br>Nusantara
@endsection
@section('auth_subtitle', 'Tempat kurikulum bertemu kearifan tapak.')
@section('auth_lead', 'Masuk untuk mengelola reservasi kunjungan lapangan institusi Anda atau panel pengelola destinasi.')

@section('card')
    <div class="text-center space-y-1">
        <h2 class="text-2xl font-extrabold tracking-tight">Masuk Akun Lapangan</h2>
        <p class="text-xs text-white/75">Akses reservasi jadwal institusi atau panel pengelola tapak.</p>
    </div>

    <x-auth-alerts />

    <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-xs font-semibold text-white/90 mb-1.5">Email Resmi</label>
            <div class="relative">
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="nama@instansi.sch.id" class="auth-input has-icon">
                <span class="material-symbols-outlined absolute left-3 top-3 text-lg text-white/70">mail</span>
            </div>
        </div>

        <div x-data="{ show: false }">
            <label for="password" class="block text-xs font-semibold text-white/90 mb-1.5">Kata Sandi</label>
            <div class="relative">
                <input :type="show ? 'text' : 'password'" type="password" id="password" name="password" required autocomplete="current-password" placeholder="Masukkan kata sandi" class="auth-input has-icon pr-11">
                <span class="material-symbols-outlined absolute left-3 top-3 text-lg text-white/70">lock</span>
                <button type="button" @click="show = !show" class="absolute right-3 top-2.5 text-white/70 hover:text-white transition-colors" :aria-label="show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">
                    <span class="material-symbols-outlined text-lg" x-text="show ? 'visibility_off' : 'visibility'">visibility</span>
                </button>
            </div>
        </div>

        <div class="flex items-center justify-between text-xs">
            <label class="flex items-center gap-2 text-white/85 cursor-pointer">
                <input type="checkbox" name="remember" class="rounded border-white/40 bg-white/10 text-[#1F2B45] focus:ring-white/40">
                <span>Ingat saya</span>
            </label>
            <a href="{{ route('password.request') }}" class="font-semibold text-white hover:underline underline-offset-4">Lupa sandi?</a>
        </div>

        <button type="submit" class="w-full bg-[#1F2B45] hover:bg-[#2E3D5C] text-white py-3.5 px-6 rounded-2xl font-bold text-sm shadow-lg transition-all cursor-pointer">
            Masuk ke Sistem &rarr;
        </button>
    </form>

    <div class="flex items-center gap-3 text-[11px] text-white/60">
        <span class="h-px flex-1 bg-white/25"></span>
        <span class="flex items-center gap-1"><span class="material-symbols-outlined text-sm">security</span> Data institusi terproteksi</span>
        <span class="h-px flex-1 bg-white/25"></span>
    </div>

    <div class="text-center text-xs space-y-2">
        <p class="text-white/85">
            Belum memiliki akun? <a href="{{ route('register') }}" class="font-bold text-white underline underline-offset-4 hover:no-underline">Daftar Akun Baru</a>
        </p>
        <a href="{{ route('destinasi.index') }}" class="inline-block text-[11px] text-white/60 hover:text-white transition-colors">&larr; Kembali ke Katalog Destinasi</a>
    </div>
@endsection
