@extends('layouts.auth')

@section('title', 'Daftar Akun')
@section('card_width', 'max-w-xl')
@section('auth_kicker', 'Pintu Masuk Resmi Ekosistem')
@section('auth_title')
    Mulai<br>Perjalanan
@endsection
@section('auth_subtitle', 'Menghubungkan ruang kelas dengan alam Nusantara.')
@section('auth_lead', 'Destinara merampingkan perizinan dan kunjungan lapangan, agar institusi pendidikan dan komunitas tapak berkolaborasi secara etis, transparan, dan berkelanjutan.')

@section('card')
    <div x-data="{ role: '{{ old('account_type', 'buyer') }}' }" class="space-y-5">
        <div class="flex items-center gap-6 border-b border-white/20 pb-3 text-xs font-bold uppercase tracking-wider">
            <a href="{{ route('login') }}" class="text-white/60 hover:text-white transition-colors pb-1">Masuk Akun</a>
            <span class="text-white border-b-2 border-white pb-1">Daftar Akun Baru</span>
        </div>

        <div>
            <h2 class="text-2xl font-extrabold tracking-tight">Pendaftaran Akun</h2>
            <p class="text-xs text-white/75 mt-1">Pilih tipe akun Anda untuk memulai proses reservasi atau kurasi destinasi.</p>
        </div>

        <x-auth-alerts />

        <form action="{{ route('register.post') }}" method="POST" class="space-y-4">
            @csrf

            <div class="space-y-2">
                <span class="block text-xs font-semibold text-white/90">Tipe Pengguna <span class="text-red-300">*</span></span>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label :class="role === 'buyer' ? 'border-white bg-white/20 ring-2 ring-white/30' : 'border-white/30 bg-white/5 hover:border-white/60'"
                           class="p-3.5 rounded-2xl border cursor-pointer transition-all flex items-start gap-3">
                        <input type="radio" name="account_type" value="buyer" x-model="role" class="mt-0.5 border-white/50 bg-white/10 text-[#1F2B45] focus:ring-white/40">
                        <span>
                            <span class="font-bold text-xs block">Instansi Pendidikan</span>
                            <span class="text-[11px] text-white/70 block mt-0.5">Sekolah, Kampus, &amp; Peneliti (Buyer)</span>
                        </span>
                    </label>
                    <label :class="role === 'partner' ? 'border-white bg-white/20 ring-2 ring-white/30' : 'border-white/30 bg-white/5 hover:border-white/60'"
                           class="p-3.5 rounded-2xl border cursor-pointer transition-all flex items-start gap-3">
                        <input type="radio" name="account_type" value="partner" x-model="role" class="mt-0.5 border-white/50 bg-white/10 text-[#1F2B45] focus:ring-white/40">
                        <span>
                            <span class="font-bold text-xs block">Pengelola Destinasi</span>
                            <span class="text-[11px] text-white/70 block mt-0.5">Desa Wisata &amp; Adat (Mitra)</span>
                        </span>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="name" class="block text-xs font-semibold text-white/90 mb-1.5">Nama PIC <span class="text-red-300">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required placeholder="Contoh: Budi Santoso" class="auth-input">
                </div>
                <div>
                    <label for="org_name" class="block text-xs font-semibold text-white/90 mb-1.5">
                        <span x-text="role === 'buyer' ? 'Nama Sekolah / Kampus' : 'Nama Desa / Komunitas'">Nama Sekolah / Kampus</span> <span class="text-red-300">*</span>
                    </label>
                    <input type="text" id="org_name" name="org_name" value="{{ old('org_name') }}" required placeholder="Nama Sekolah / Institusi / Pokdarwis" class="auth-input">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="email" class="block text-xs font-semibold text-white/90 mb-1.5">Email Resmi <span class="text-red-300">*</span></label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required placeholder="nama@instansi.sch.id" class="auth-input">
                </div>
                <div>
                    <label for="phone" class="block text-xs font-semibold text-white/90 mb-1.5">Nomor WhatsApp <span class="text-red-300">*</span></label>
                    <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" required placeholder="08xxxxxxxxxx" class="auth-input">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="password" class="block text-xs font-semibold text-white/90 mb-1.5">Kata Sandi <span class="text-red-300">*</span></label>
                    <input type="password" id="password" name="password" required placeholder="Minimal 8 karakter" class="auth-input">
                </div>
                <div>
                    <label for="password_confirmation" class="block text-xs font-semibold text-white/90 mb-1.5">Konfirmasi Kata Sandi <span class="text-red-300">*</span></label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required placeholder="Ulangi kata sandi" class="auth-input">
                </div>
            </div>

            <div class="flex items-start gap-2 pt-1">
                <input type="checkbox" name="terms" value="1" required id="tos" {{ old('terms') ? 'checked' : '' }} class="mt-0.5 rounded border-white/40 bg-white/10 text-[#1F2B45] focus:ring-white/40">
                <label for="tos" class="text-[11px] text-white/80 leading-tight">
                    Saya menyetujui <a href="{{ route('terms') }}" target="_blank" class="font-semibold text-white underline underline-offset-2">Syarat &amp; Ketentuan</a> serta Kebijakan Privasi platform Destinara.
                </label>
            </div>

            <button type="submit" class="w-full bg-[#1F2B45] hover:bg-[#2E3D5C] text-white py-3.5 px-6 rounded-2xl font-bold text-sm shadow-lg transition-all cursor-pointer">
                Daftar Akun Sekarang &rarr;
            </button>
        </form>

        <div class="text-center text-xs space-y-2">
            <p class="text-white/85">Sudah memiliki akun? <a href="{{ route('login') }}" class="font-bold text-white underline underline-offset-4 hover:no-underline">Masuk ke Sistem</a></p>
            <a href="{{ route('destinasi.index') }}" class="inline-block text-[11px] text-white/60 hover:text-white transition-colors">&larr; Kembali ke Katalog Destinasi</a>
        </div>
    </div>
@endsection
