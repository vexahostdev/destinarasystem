@extends('layouts.auth')

@section('title', 'Verifikasi Email')
@section('auth_kicker', 'Ruang Belajar Tapak')
@section('auth_title')
    Satu<br>Langkah Lagi
@endsection
@section('auth_subtitle', 'Konfirmasi email untuk mulai menjelajah.')
@section('auth_lead', 'Verifikasi memastikan setiap reservasi dan kemitraan tercatat atas nama institusi yang sah.')

@section('card')
    <div class="text-center space-y-3">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-white/15 border border-white/30">
            <span class="material-symbols-outlined text-3xl">mark_email_unread</span>
        </div>
        <h2 class="text-2xl font-extrabold tracking-tight">Verifikasi Alamat Email</h2>
        <p class="text-xs text-white/75 leading-relaxed">
            Terima kasih telah mendaftar di Destinara! Sebelum mulai menjelajah atau memesan, silakan klik tautan verifikasi yang baru saja kami kirimkan ke email Anda:
        </p>
        <p class="text-sm font-bold">{{ auth()->user()?->email }}</p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="p-3 bg-emerald-50 rounded-2xl border border-emerald-200 text-xs text-emerald-800 flex items-center gap-2">
            <span class="material-symbols-outlined text-base text-emerald-600 shrink-0">check_circle</span>
            <span>Tautan verifikasi baru telah dikirimkan ke alamat email Anda.</span>
        </div>
    @endif

    <div class="space-y-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="w-full bg-[#1F2B45] hover:bg-[#2E3D5C] text-white py-3.5 px-6 rounded-2xl font-bold text-sm shadow-lg transition-all cursor-pointer">
                Kirim Ulang Tautan Verifikasi
            </button>
        </form>
        <form method="POST" action="{{ route('logout') }}" class="text-center">
            @csrf
            <button type="submit" class="text-xs text-white/70 hover:text-white font-semibold transition-colors">Keluar / Ganti Akun</button>
        </form>
    </div>
@endsection
