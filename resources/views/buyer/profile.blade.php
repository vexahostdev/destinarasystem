@extends('layouts.buyer')

@section('title', 'Profil Institusi & Dokumen Pendukung')

@section('content')
<div class="space-y-8 max-w-5xl mx-auto">

    <!-- Header Breadcrumb & Judul -->
    <div class="pb-4 border-b border-[#2B211E]/12">
        <div class="flex items-center gap-2 text-xs text-[#2B211E]/60 mb-2">
            <a href="{{ route('buyer.dashboard') }}" class="hover:text-[#703A3A]">Dashboard</a>
            <span>/</span>
            <span class="text-[#703A3A] font-semibold">Profil Institusi & Legalitas</span>
        </div>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#2B211E]">Profil Institusi & Dokumen Pendukung</h1>
                <p class="text-xs sm:text-sm text-[#2B211E]/70 mt-0.5">
                    Data sekolah/kampus dan penanggung jawab resmi untuk verifikasi SPK, SP2D, dan penerbitan izin dinas.
                </p>
            </div>

            <div class="px-3 py-1.5 bg-[#51634b]/10 border border-[#51634b]/30 text-[#51634b] text-xs font-bold uppercase tracking-wider flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[18px]">verified</span>
                <span>Akun Institusi Terverifikasi</span>
            </div>
        </div>
    </div>

    <!-- Form Pembaruan Data Institusi -->
    <form action="{{ route('buyer.profile.update') }}" method="POST" class="space-y-8">
        @csrf

        <!-- 1. Data Lembaga / Sekolah -->
        <div class="rgs-card p-6 border-t-4 border-t-[#703A3A] space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-[#2B211E]/10">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#703A3A] text-[20px]">account_balance</span>
                    <h2 class="font-serif text-lg font-bold text-[#2B211E]">1. Identitas Sekolah / Perguruan Tinggi</h2>
                </div>
                <span class="text-[11px] text-[#2B211E]/60">Data Pokok Pendidikan (Dapodik / PDDIKTI)</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E]">
                        Nama Resmi Lembaga / Sekolah <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="institution_name" placeholder="Contoh: SMA Negeri 1 ..." value="{{ old('institution_name', $institution->institution_name ?? '') }}" required class="w-full bg-[#faf6f0] border border-[#2B211E]/20 px-3.5 py-2 text-xs font-medium text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E]">
                        Jenis / Jenjang Lembaga <span class="text-red-500">*</span>
                    </label>
                    <select name="institution_type" class="w-full bg-[#faf6f0] border border-[#2B211E]/20 px-3.5 py-2 text-xs font-medium text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                        <option value="sekolah" {{ ($institution->institution_type ?? '') == 'sekolah' ? 'selected' : '' }}>Sekolah Menengah Atas / Kejuruan (SMA/SMK)</option>
                        <option value="kampus" {{ ($institution->institution_type ?? '') == 'kampus' ? 'selected' : '' }}>Perguruan Tinggi / Universitas</option>
                        <option value="riset" {{ ($institution->institution_type ?? '') == 'riset' ? 'selected' : '' }}>Pusat Riset / Laboratorium Sains</option>
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E]">
                        Nomor Pokok Sekolah Nasional (NPSN / NIDN)
                    </label>
                    <input type="text" name="npsn" placeholder="Contoh: 20104567" value="{{ old('npsn', $institution->npsn ?? '') }}" class="w-full bg-[#faf6f0] border border-[#2B211E]/20 px-3.5 py-2 text-xs font-mono text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E]">
                        Kota / Kabupaten & Provinsi <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" name="city" placeholder="Kabupaten/Kota" value="{{ old('city', $institution->city ?? '') }}" required class="w-full bg-[#faf6f0] border border-[#2B211E]/20 px-3 py-2 text-xs font-medium text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                        <input type="text" name="province" placeholder="Provinsi" value="{{ old('province', $institution->province ?? '') }}" required class="w-full bg-[#faf6f0] border border-[#2B211E]/20 px-3 py-2 text-xs font-medium text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                    </div>
                </div>

                <div class="sm:col-span-2 space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E]">
                        Alamat Lengkap Kantor / Kampus <span class="text-red-500">*</span>
                    </label>
                    <textarea name="address" rows="2" placeholder="Alamat lengkap instansi..." required class="w-full bg-[#faf6f0] border border-[#2B211E]/20 p-3 text-xs text-[#2B211E] focus:outline-none focus:border-[#703A3A]">{{ old('address', $institution->address ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <!-- 2. Data Penanggung Jawab (PIC) -->
        <div class="rgs-card p-6 border-t-4 border-t-[#703A3A] space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-[#2B211E]/10">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#703A3A] text-[20px]">person</span>
                    <h2 class="font-serif text-lg font-bold text-[#2B211E]">2. Penanggung Jawab Resmi (PIC)</h2>
                </div>
                <span class="text-[11px] text-[#2B211E]/60">Penandatangan SPK & Izin Ekskursi</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E]">
                        Nama Lengkap & Gelar PIC <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="pic_name" placeholder="Nama PIC..." value="{{ old('pic_name', $institution->pic_name ?? Auth::user()->name) }}" required class="w-full bg-[#faf6f0] border border-[#2B211E]/20 px-3.5 py-2 text-xs font-medium text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E]">
                        Jabatan Struktural Institusi <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="pic_position" placeholder="Contoh: Kepala Sekolah / Dosen Pembimbing" value="{{ old('pic_position', $institution->pic_position ?? '') }}" required class="w-full bg-[#faf6f0] border border-[#2B211E]/20 px-3.5 py-2 text-xs font-medium text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E]">
                        Nomor WhatsApp Aktif <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="pic_phone" placeholder="08xxxxxxxxxx" value="{{ old('pic_phone', $institution->pic_phone ?? Auth::user()->phone) }}" required class="w-full bg-[#faf6f0] border border-[#2B211E]/20 px-3.5 py-2 text-xs font-mono text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                    <span class="text-[10px] text-[#2B211E]/60">Digunakan untuk notifikasi status verifikasi tapak secara otomatis</span>
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E]">
                        Email Dinas / Surat Elektronik <span class="text-red-500">*</span>
                    </label>
                    <input type="email" name="pic_email" placeholder="email@instansi.sch.id" value="{{ old('pic_email', $institution->pic_email ?? Auth::user()->email) }}" required class="w-full bg-[#faf6f0] border border-[#2B211E]/20 px-3.5 py-2 text-xs text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                </div>
            </div>
        </div>

        <!-- 3. Legalitas & Otomasi Kop Surat Izin -->
        <div class="rgs-card p-6 border-t-4 border-t-[#51634b] space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-[#2B211E]/10">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#51634b] text-[20px]">approval_delegation</span>
                    <h2 class="font-serif text-lg font-bold text-[#2B211E]">3. Otomasi Dokumen & Kop Surat Resmi</h2>
                </div>
                <span class="text-[11px] text-[#51634b] font-bold uppercase">Format Standar Dinas</span>
            </div>

            <p class="text-xs text-[#2B211E]/75 leading-relaxed">
                Destinara secara otomatis memuat nama kepala dinas, nomor surat tugas, serta kop surat institusi Anda ke dalam <strong>Draf Surat Izin Riset</strong> dan <strong>Surat Konfirmasi Kunjungan (e-Pass)</strong> agar memangkas birokrasi perizinan ke Balai Taman Nasional dan Kesepakatan Lembaga Adat.
            </p>

            <div class="p-4 bg-[#faf6f0] border border-[#2B211E]/15 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="space-y-1">
                    <div class="font-bold text-xs text-[#2B211E]">Kop Surat & Stempel Digital Resmi:</div>
                    <div class="text-[11px] text-[#2B211E]/70">
                        @if(!empty($institution->official_letterhead))
                            File aktif: <code>{{ basename($institution->official_letterhead) }}</code> (Tersimpan aman di penyimpanan terenkripsi)
                        @else
                            Format berkas: PNG/JPG/PDF (Maks. 2MB)
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" class="px-3.5 py-1.5 bg-white border border-[#2B211E]/20 text-xs font-bold text-[#2B211E] uppercase tracking-wider hover:border-[#703A3A] transition-colors">
                        Ganti Berkas Kop
                    </button>
                </div>
            </div>
        </div>

        <!-- Tombol Aksi Simpan Profil -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('buyer.dashboard') }}" class="rgs-btn-outline px-5 py-2.5 text-xs font-bold uppercase tracking-wider">
                Batal
            </a>
            <button type="submit" class="rgs-btn-primary px-6 py-2.5 text-xs font-bold uppercase tracking-wider shadow-sm flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">save</span>
                <span>Simpan Perubahan Profil</span>
            </button>
        </div>

    </form>

</div>
@endsection
