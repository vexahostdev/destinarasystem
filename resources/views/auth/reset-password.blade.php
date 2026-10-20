@extends('layouts.auth')

@section('title', 'Kata Sandi Baru')
@section('auth_kicker', 'Ruang Belajar Tapak')
@section('auth_title')
    Sandi<br>Baru
@endsection
@section('auth_subtitle', 'Amankan kembali akun Anda.')
@section('auth_lead', 'Gunakan kata sandi yang kuat dan belum pernah dipakai di layanan lain.')

@section('card')
    <div class="text-center space-y-1">
        <h2 class="text-2xl font-extrabold tracking-tight">Buat Kata Sandi Baru</h2>
        <p class="text-xs text-white/75">Tentukan kata sandi baru yang aman untuk akun Anda.</p>
    </div>

    <x-auth-alerts />

    <form action="{{ route('password.update') }}" method="POST" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="email" class="block text-xs font-semibold text-white/90 mb-1.5">Alamat Email</label>
            <div class="relative">
                <input type="email" id="email" name="email" value="{{ $email ?? old('email') }}" required readonly class="auth-input has-icon">
                <span class="material-symbols-outlined absolute left-3 top-3 text-lg text-white/70">mail</span>
            </div>
        </div>

        <div>
            <label for="password" class="block text-xs font-semibold text-white/90 mb-1.5">Kata Sandi Baru</label>
            <div class="relative">
                <input type="password" id="password" name="password" required placeholder="Minimal 8 karakter" class="auth-input has-icon">
                <span class="material-symbols-outlined absolute left-3 top-3 text-lg text-white/70">lock</span>
            </div>
        </div>

        <div>
            <label for="password_confirmation" class="block text-xs font-semibold text-white/90 mb-1.5">Konfirmasi Kata Sandi Baru</label>
            <div class="relative">
                <input type="password" id="password_confirmation" name="password_confirmation" required placeholder="Ulangi kata sandi baru" class="auth-input has-icon">
                <span class="material-symbols-outlined absolute left-3 top-3 text-lg text-white/70">lock_reset</span>
            </div>
        </div>

        <button type="submit" class="w-full bg-[#1F2B45] hover:bg-[#2E3D5C] text-white py-3.5 px-6 rounded-2xl font-bold text-sm shadow-lg transition-all cursor-pointer">
            Simpan Kata Sandi Baru &rarr;
        </button>
    </form>

    <div class="text-center text-xs">
        <a href="{{ route('login') }}" class="font-bold text-white hover:underline underline-offset-4">&larr; Batal &amp; Kembali ke Halaman Masuk</a>
    </div>
@endsection
