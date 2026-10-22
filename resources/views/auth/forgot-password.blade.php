@extends('layouts.auth')

@section('title', 'Lupa Kata Sandi')
@section('auth_kicker', 'Ruang Belajar Tapak')
@section('auth_title')
    Pulihkan<br>Akses
@endsection
@section('auth_subtitle', 'Perjalanan Anda tinggal satu langkah lagi.')
@section('auth_lead', 'Kami akan mengirim tautan pengaturan ulang kata sandi ke email resmi yang terdaftar.')

@section('card')
    <div class="text-center space-y-1">
        <h2 class="text-2xl font-extrabold tracking-tight">Atur Ulang Kata Sandi</h2>
        <p class="text-xs text-white/75">Masukkan alamat email resmi akun Anda. Kami akan mengirimkan tautan untuk mengatur ulang kata sandi.</p>
    </div>

    <x-auth-alerts />

    <form action="{{ route('password.email') }}" method="POST" class="space-y-4">
        @csrf
        <div>
            <label for="email" class="block text-xs font-semibold text-white/90 mb-1.5">Alamat Email Terdaftar</label>
            <div class="relative">
                <input type="email" id="email" name="email" value="{{ old('email') }}" required placeholder="nama@instansi.sch.id" class="auth-input has-icon">
                <span class="material-symbols-outlined absolute left-3 top-3 text-lg text-white/70">mail</span>
            </div>
        </div>

        <button type="submit" class="w-full bg-[#1F2B45] hover:bg-[#2E3D5C] text-white py-3.5 px-6 rounded-2xl font-bold text-sm shadow-lg transition-all cursor-pointer">
            Kirim Tautan Reset &rarr;
        </button>
    </form>

    <div class="text-center text-xs">
        <a href="{{ route('login') }}" class="font-bold text-white hover:underline underline-offset-4">&larr; Kembali ke Halaman Masuk</a>
    </div>
@endsection
