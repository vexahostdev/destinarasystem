@extends('layouts.partner')

@section('title', 'Profil Pengelola & Rekening Payout')

@section('content')
<div class="space-y-8 max-w-4xl mx-auto">

    <!-- Header Breadcrumb & Judul -->
    <div class="pb-4 border-b border-[#2B211E]/12">
        <div class="flex items-center gap-2 text-xs text-[#2B211E]/60 mb-2">
            <a href="{{ route('mitra.dashboard') }}" class="hover:text-[#703A3A]">Dashboard</a>
            <span>/</span>
            <span class="text-[#703A3A] font-semibold">Profil & Rekening</span>
        </div>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#2B211E]">Profil Lembaga Adat & Rekening Payout</h1>
                <p class="text-xs sm:text-sm text-[#2B211E]/70 mt-0.5">
                    Data legalitas lembaga pengelola dan rekening bank untuk penyaluran bagi hasil retribusi tapak.
                </p>
            </div>

            <div class="px-3 py-1.5 bg-[#51634b]/10 border border-[#51634b]/30 text-[#51634b] text-xs font-bold uppercase tracking-wider flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[18px]">verified</span>
                <span>Mitra Terverifikasi Resmi</span>
            </div>
        </div>
    </div>

    <!-- Form Pembaruan Data Lembaga & Rekening -->
    <form action="{{ route('mitra.profile.update') }}" method="POST" class="space-y-8">
        @csrf

        <!-- 1. Identitas Lembaga Pengelola -->
        <div class="rgs-card p-6 border-t-4 border-t-[#703A3A] space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-[#2B211E]/10">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#703A3A] text-[20px]">holiday_village</span>
                    <h2 class="font-serif text-lg font-bold text-[#2B211E]">1. Badan / Lembaga Pengelola Tapak</h2>
                </div>
                <span class="text-[11px] text-[#2B211E]/60">Legalitas Komunal</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2 space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E]">
                        Nama Organisasi / Lembaga Adat <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="organization_name" placeholder="Contoh: Badan Pengelola / Pokdarwis ..." value="{{ old('organization_name', $profile->organization_name ?? '') }}" required class="w-full bg-[#faf6f0] border border-[#2B211E]/20 px-3.5 py-2 text-xs font-medium text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E]">
                        Bentuk Organisasi <span class="text-red-500">*</span>
                    </label>
                    <select name="organization_type" class="w-full bg-[#faf6f0] border border-[#2B211E]/20 px-3.5 py-2 text-xs font-medium text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                        <option value="yayasan_adat" {{ ($profile->organization_type ?? '') == 'yayasan_adat' ? 'selected' : '' }}>Lembaga Adat / Yayasan Komunitas</option>
                        <option value="pokdarwis" {{ ($profile->organization_type ?? '') == 'pokdarwis' ? 'selected' : '' }}>Kelompok Sadar Wisata (Pokdarwis)</option>
                        <option value="bumdes" {{ ($profile->organization_type ?? '') == 'bumdes' ? 'selected' : '' }}>Badan Usaha Milik Desa (BUMDes)</option>
                        <option value="koperasi" {{ ($profile->organization_type ?? '') == 'koperasi' ? 'selected' : '' }}>Koperasi Tani / Nelayan</option>
                        <option value="swasta" {{ ($profile->organization_type ?? '') == 'swasta' ? 'selected' : '' }}>Pengelola Swasta / Balai Riset</option>
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E]">
                        Nomor SK Penetapan / Surat Keputusan
                    </label>
                    <input type="text" name="legal_document_number" placeholder="Contoh: SK.BUP-BGL/442/2021" value="{{ old('legal_document_number', $profile->legal_document_number ?? '') }}" class="w-full bg-[#faf6f0] border border-[#2B211E]/20 px-3.5 py-2 text-xs font-mono text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                </div>
            </div>
        </div>

        <!-- 2. Penanggung Jawab Adat (PIC) -->
        <div class="rgs-card p-6 border-t-4 border-t-[#703A3A] space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-[#2B211E]/10">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#703A3A] text-[20px]">person</span>
                    <h2 class="font-serif text-lg font-bold text-[#2B211E]">2. Penanggung Jawab Resmi (PIC Adat)</h2>
                </div>
                <span class="text-[11px] text-[#2B211E]/60">Penghubung Rombongan Siswa</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E]">
                        Nama Lengkap & Gelar PIC <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="contact_name" placeholder="Nama PIC Pengelola..." value="{{ old('contact_name', $user->name ?? '') }}" required class="w-full bg-[#faf6f0] border border-[#2B211E]/20 px-3.5 py-2 text-xs font-medium text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E]">
                        Nomor WhatsApp Aktif <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="contact_phone" placeholder="08xxxxxxxxxx" value="{{ old('contact_phone', $user->phone ?? '') }}" required class="w-full bg-[#faf6f0] border border-[#2B211E]/20 px-3.5 py-2 text-xs font-mono text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                    <span class="text-[10px] text-[#2B211E]/60">Diteruskan ke guru pendamping setelah transaksi lunas</span>
                </div>
            </div>
        </div>

        <!-- 3. Rekening Bank Penampungan Payout -->
        <div class="rgs-card p-6 border-t-4 border-t-[#51634b] space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-[#2B211E]/10">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#51634b] text-[20px]">account_balance</span>
                    <h2 class="font-serif text-lg font-bold text-[#2B211E]">3. Rekening Bank Penampungan Payout</h2>
                </div>
                <span class="text-[11px] text-[#51634b] font-bold uppercase">Rekonsiliasi Otomatis</span>
            </div>

            <p class="text-xs text-[#2B211E]/75 leading-relaxed">
                Hasil retribusi kunjungan rombongan belajar yang masuk ke rekening penampungan ini diteruskan setiap pekan dengan rincian biaya komisi platform transparan 10%.
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E]">
                        Nama Bank Penerbit <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="bank_name" placeholder="Contoh: Bank BPD Bali / Mandiri / BCA" value="{{ old('bank_name', $profile->bank_name ?? '') }}" required class="w-full bg-[#faf6f0] border border-[#2B211E]/20 px-3.5 py-2 text-xs font-medium text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E]">
                        Nomor Rekening Bank <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="bank_account_number" placeholder="Nomor rekening bank..." value="{{ old('bank_account_number', $profile->bank_account_number ?? '') }}" required class="w-full bg-[#faf6f0] border border-[#2B211E]/20 px-3.5 py-2 text-xs font-mono font-bold text-[#703A3A] focus:outline-none focus:border-[#703A3A]">
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E]">
                        Nama Pemilik Rekening <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="bank_account_holder" placeholder="Atas nama rekening..." value="{{ old('bank_account_holder', $profile->bank_account_holder ?? '') }}" required class="w-full bg-[#faf6f0] border border-[#2B211E]/20 px-3.5 py-2 text-xs font-medium uppercase text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                </div>
            </div>
        </div>

        <!-- Tombol Aksi Simpan Profil -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('mitra.dashboard') }}" class="rgs-btn-outline px-5 py-2.5 text-xs font-bold uppercase tracking-wider">
                Batal
            </a>
            <button type="submit" class="rgs-btn-primary px-6 py-2.5 text-xs font-bold uppercase tracking-wider shadow-sm flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">save</span>
                <span>Simpan Perubahan Profil & Rekening</span>
            </button>
        </div>

    </form>

</div>
@endsection
