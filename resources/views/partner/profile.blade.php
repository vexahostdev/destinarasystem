@extends('layouts.partner')

@section('title', 'Profil Pengelola & Rekening Payout')

@section('content')
@php
    $isVerified = $profile->verification_status === \App\Enums\VerificationStatus::VERIFIED;
    $isPending = $profile->verification_status === \App\Enums\VerificationStatus::PENDING;
    $isRejected = $profile->verification_status === \App\Enums\VerificationStatus::REJECTED;
    $isUnverified = $profile->verification_status === \App\Enums\VerificationStatus::UNVERIFIED;

    $hasProfile = !empty($profile->organization_name);
    $hasBank = !empty($profile->bank_id) && !empty($profile->bank_account_number) && !empty($profile->bank_account_holder);
    $hasDoc = $profile->documents->isNotEmpty();
    $canSubmit = $hasProfile && $hasBank && $hasDoc;
@endphp

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

            <div>
                @if($isVerified)
                    <div class="px-3 py-1.5 bg-emerald-100 border border-emerald-300 text-emerald-900 text-xs font-bold uppercase tracking-wider flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px]">verified</span>
                        <span>Mitra Terverifikasi Resmi</span>
                    </div>
                @elseif($isPending)
                    <div class="px-3 py-1.5 bg-blue-100 border border-blue-300 text-blue-900 text-xs font-bold uppercase tracking-wider flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px]">hourglass_top</span>
                        <span>Menunggu Verifikasi Admin</span>
                    </div>
                @elseif($isRejected)
                    <div class="px-3 py-1.5 bg-red-100 border border-red-300 text-red-900 text-xs font-bold uppercase tracking-wider flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px]">warning</span>
                        <span>Verifikasi Perlu Perbaikan</span>
                    </div>
                @else
                    <div class="px-3 py-1.5 bg-gray-100 border border-gray-300 text-gray-700 text-xs font-bold uppercase tracking-wider flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px]">info</span>
                        <span>Belum Terverifikasi</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Banner Catatan Penolakan Admin -->
    @if($isRejected && $profile->verification_note)
    <div class="p-4 bg-red-50 border border-red-300 rounded-sm text-xs text-red-800 space-y-1">
        <div class="flex items-center gap-2 font-bold text-red-900">
            <span class="material-symbols-outlined text-[18px]">error</span>
            <span>Catatan Penolakan dari Administrator:</span>
        </div>
        <p class="pl-6 text-red-700">{{ $profile->verification_note }}</p>
        <p class="pl-6 text-xs text-red-600 italic">Silakan perbarui data profil, rekening, atau unggah dokumen legalitas baru sesuai instruksi di atas, lalu ajukan verifikasi ulang.</p>
    </div>
    @endif

    <!-- Banner Panduan Kelengkapan Verifikasi -->
    @if(!$isVerified)
    <div class="rgs-card p-5 bg-[#faf6f0] border border-[#2B211E]/15 space-y-3">
        <div class="flex items-center justify-between pb-2 border-b border-[#2B211E]/10">
            <h3 class="text-xs font-bold uppercase tracking-wider text-[#2B211E] flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px] text-[#703A3A]">checklist</span>
                <span>Tahapan Menuju Kemitraan Terverifikasi</span>
            </h3>
            <span class="text-[11px] font-mono text-[#2B211E]/60">Syarat Publikasi Destinasi</span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
            <div class="p-3 bg-white border border-[#2B211E]/10 flex items-start gap-2.5">
                <span class="material-symbols-outlined text-[20px] {{ $hasProfile ? 'text-emerald-700' : 'text-gray-300' }}">
                    {{ $hasProfile ? 'check_circle' : 'radio_button_unchecked' }}
                </span>
                <div>
                    <div class="font-bold text-[#2B211E]">1. Profil Lembaga</div>
                    <div class="text-[11px] text-[#2B211E]/60">{{ $hasProfile ? 'Sudah terisi' : 'Nama & data wajib' }}</div>
                </div>
            </div>
            <div class="p-3 bg-white border border-[#2B211E]/10 flex items-start gap-2.5">
                <span class="material-symbols-outlined text-[20px] {{ $hasBank ? 'text-emerald-700' : 'text-gray-300' }}">
                    {{ $hasBank ? 'check_circle' : 'radio_button_unchecked' }}
                </span>
                <div>
                    <div class="font-bold text-[#2B211E]">2. Rekening Payout</div>
                    <div class="text-[11px] text-[#2B211E]/60">{{ $hasBank ? 'Sudah terisi' : 'Bank & nomor rekening' }}</div>
                </div>
            </div>
            <div class="p-3 bg-white border border-[#2B211E]/10 flex items-start gap-2.5">
                <span class="material-symbols-outlined text-[20px] {{ $hasDoc ? 'text-emerald-700' : 'text-gray-300' }}">
                    {{ $hasDoc ? 'check_circle' : 'radio_button_unchecked' }}
                </span>
                <div>
                    <div class="font-bold text-[#2B211E]">3. Dokumen Legalitas</div>
                    <div class="text-[11px] text-[#2B211E]/60">{{ $hasDoc ? $profile->documents->count().' dokumen' : 'Min. 1 berkas (SK/KTP/NPWP)' }}</div>
                </div>
            </div>
        </div>
    </div>
    @endif

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
                        Bentuk Organisasi
                    </label>
                    <select name="organization_type_id" class="w-full bg-[#faf6f0] border border-[#2B211E]/20 px-3.5 py-2 text-xs font-medium text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                        <option value="">-- Pilih Bentuk Organisasi --</option>
                        @foreach($organizationTypes as $type)
                            <option value="{{ $type->id }}" {{ (old('organization_type_id', $profile->organization_type_id) == $type->id) ? 'selected' : '' }}>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E]">
                        Nomor SK Penetapan / Surat Keputusan
                    </label>
                    <input type="text" name="legal_document_number" placeholder="Contoh: SK.BUP-BGL/442/2021" value="{{ old('legal_document_number', $profile->legal_document_number ?? '') }}" class="w-full bg-[#faf6f0] border border-[#2B211E]/20 px-3.5 py-2 text-xs font-mono text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E]">
                        Kota / Kabupaten
                    </label>
                    <input type="text" name="city" placeholder="Kabupaten / Kota" value="{{ old('city', $profile->city ?? '') }}" class="w-full bg-[#faf6f0] border border-[#2B211E]/20 px-3.5 py-2 text-xs font-medium focus:outline-none focus:border-[#703A3A]">
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E]">
                        Provinsi
                    </label>
                    <select name="province_id" class="w-full bg-[#faf6f0] border border-[#2B211E]/20 px-3.5 py-2 text-xs font-medium focus:outline-none focus:border-[#703A3A]">
                        <option value="">-- Pilih Provinsi --</option>
                        @foreach($provinces as $prov)
                            <option value="{{ $prov->id }}" {{ (old('province_id', $profile->province_id) == $prov->id) ? 'selected' : '' }}>
                                {{ $prov->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-2 space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E]">
                        Alamat Domisili Lembaga
                    </label>
                    <textarea name="address" rows="2" placeholder="Alamat lengkap lembaga pengelola..." class="w-full bg-[#faf6f0] border border-[#2B211E]/20 p-3 text-xs text-[#2B211E] focus:outline-none focus:border-[#703A3A]">{{ old('address', $profile->address ?? '') }}</textarea>
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
                    <input type="text" name="pic_name" placeholder="Nama PIC Pengelola..." value="{{ old('pic_name', $user->name ?? '') }}" required class="w-full bg-[#faf6f0] border border-[#2B211E]/20 px-3.5 py-2 text-xs font-medium text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E]">
                        Nomor WhatsApp Aktif <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="pic_phone" placeholder="08xxxxxxxxxx" value="{{ old('pic_phone', $user->phone ?? '') }}" required class="w-full bg-[#faf6f0] border border-[#2B211E]/20 px-3.5 py-2 text-xs font-mono text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
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
                Hasil retribusi kunjungan rombongan belajar yang masuk ke rekening penampungan ini disalurkan setelah kegiatan selesai sesuai ketentuan pencairan (komisi platform {{ $profile->commission_percent_override ?? setting('default_commission_percent', 10) }}%).
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E]">
                        Nama Bank Penerbit <span class="text-red-500">*</span>
                    </label>
                    <select name="bank_id" required class="w-full bg-[#faf6f0] border border-[#2B211E]/20 px-3.5 py-2 text-xs font-medium text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                        <option value="">-- Pilih Bank --</option>
                        @foreach($banks as $b)
                            <option value="{{ $b->id }}" {{ (old('bank_id', $profile->bank_id) == $b->id) ? 'selected' : '' }}>
                                {{ $b->name }} ({{ $b->code }})
                            </option>
                        @endforeach
                    </select>
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
            <button type="submit" class="rgs-btn-primary px-6 py-2.5 text-xs font-bold uppercase tracking-wider shadow-xs flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">save</span>
                <span>Simpan Perubahan Profil & Rekening</span>
            </button>
        </div>
    </form>

    <!-- 4. Unggah & Kelola Dokumen Legalitas -->
    <div class="rgs-card p-6 border-t-4 border-t-[#2B3A4A] space-y-6">
        <div class="flex items-center justify-between pb-3 border-b border-[#2B211E]/10">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[#2B3A4A] text-[20px]">folder_shared</span>
                <h2 class="font-serif text-lg font-bold text-[#2B211E]">4. Dokumen Legalitas & Akta Pengelola</h2>
            </div>
            <span class="text-[11px] text-[#2B211E]/60">PDF, JPG, PNG (Maks. 5MB)</span>
        </div>

        <p class="text-xs text-[#2B211E]/75 leading-relaxed">
            Unggah dokumen pembuktian legalitas pengelolaan tapak: Surat Keputusan (SK) Pengelola/Pokdarwis, KTP Penanggung Jawab Resmi (PIC), NPWP Organisasi, atau surat pengesahan dinas terkait.
        </p>

        <!-- Form Upload Dokumen -->
        <form action="{{ route('mitra.profile.document.upload') }}" method="POST" enctype="multipart/form-data" class="p-4 bg-[#faf6f0] border border-[#2B211E]/15 space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E]">
                        Jenis Dokumen <span class="text-red-500">*</span>
                    </label>
                    <select name="type" required class="w-full bg-white border border-[#2B211E]/20 px-3 py-2 text-xs font-medium text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                        <option value="sk_pengelola">SK Pengelola / Surat Tugas</option>
                        <option value="ktp_pic">KTP Penanggung Jawab (PIC)</option>
                        <option value="npwp">NPWP Lembaga / Pengelola</option>
                        <option value="lainnya">Dokumen Legalitas Lainnya</option>
                    </select>
                </div>

                <div class="sm:col-span-2 space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E]">
                        Pilih Berkas Dokumen <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center gap-2">
                        <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" required class="w-full bg-white border border-[#2B211E]/20 px-3 py-1.5 text-xs text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                        <button type="submit" class="rgs-btn-primary px-4 py-2 text-xs font-bold uppercase tracking-wider shrink-0 flex items-center gap-1.5 shadow-xs">
                            <span class="material-symbols-outlined text-[16px]">upload_file</span>
                            <span>Unggah</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Daftar Dokumen yang Sudah Diunggah -->
        <div class="space-y-2">
            <h4 class="text-xs font-bold uppercase tracking-wider text-[#2B211E]">Berkas Terunggah ({{ $profile->documents->count() }})</h4>
            @if($profile->documents->isNotEmpty())
            <div class="divide-y divide-[#2B211E]/10 border border-[#2B211E]/15 bg-white">
                @foreach($profile->documents as $doc)
                <div class="p-3.5 flex items-center justify-between gap-4 text-xs">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-[#703A3A] text-[20px]">description</span>
                        <div>
                            <div class="font-bold text-[#2B211E]">{{ $doc->original_name }}</div>
                            <div class="text-[11px] text-[#2B211E]/60 flex items-center gap-2">
                                <span class="font-mono uppercase font-semibold text-[#703A3A]">{{ str_replace('_', ' ', $doc->type) }}</span>
                                <span>•</span>
                                <span>Diunggah {{ $doc->created_at->translatedFormat('d M Y H:i') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('mitra.profile.document.download', $doc->id) }}" class="px-3 py-1.5 border border-[#2B211E]/20 hover:border-[#703A3A] text-xs font-semibold text-[#703A3A] flex items-center gap-1 transition-colors">
                            <span class="material-symbols-outlined text-[14px]">download</span>
                            <span>Unduh</span>
                        </a>

                        <form action="{{ route('mitra.profile.document.delete', $doc->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus dokumen ini?')">
                            @csrf
                            <button type="submit" class="px-2.5 py-1.5 border border-red-200 text-red-600 hover:bg-red-50 text-xs font-semibold transition-colors flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">delete</span>
                                <span>Hapus</span>
                            </button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="p-6 text-center text-xs text-[#2B211E]/60 bg-[#faf6f0] border border-[#2B211E]/10">
                Belum ada berkas legalitas yang diunggah. Silakan unggah minimal 1 dokumen legalitas di atas untuk verifikasi kemitraan.
            </div>
            @endif
        </div>
    </div>

    <!-- 5. Bagian Pengajuan Verifikasi Akun -->
    <div class="rgs-card p-6 border-t-4 {{ $isVerified ? 'border-t-emerald-600' : ($isPending ? 'border-t-blue-600' : 'border-t-amber-600') }} space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-[#2B211E]/10">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined {{ $isVerified ? 'text-emerald-700' : ($isPending ? 'text-blue-700' : 'text-amber-700') }} text-[22px]">
                    {{ $isVerified ? 'verified_user' : ($isPending ? 'pending_actions' : 'assignment_turned_in') }}
                </span>
                <h2 class="font-serif text-lg font-bold text-[#2B211E]">5. Status & Pengajuan Verifikasi Resmi</h2>
            </div>
            <span class="text-[11px] font-mono text-[#2B211E]/60">Validasi Kepatuhan</span>
        </div>

        @if($isVerified)
            <div class="p-4 bg-emerald-50 border border-emerald-200 text-xs text-emerald-900 flex items-center gap-3">
                <span class="material-symbols-outlined text-emerald-700 text-2xl">verified</span>
                <div>
                    <h4 class="font-bold text-sm">Akun Kemitraan Anda Telah Terverifikasi Sah</h4>
                    <p class="text-emerald-800 mt-0.5">Seluruh berkas legalitas dan rekening penampungan telah divalidasi. Anda dapat mengunggah dan mempublikasikan listing tapak edukasi.</p>
                </div>
            </div>
        @elseif($isPending)
            <div class="p-4 bg-blue-50 border border-blue-200 text-xs text-blue-900 flex items-center gap-3">
                <span class="material-symbols-outlined text-blue-700 text-2xl animate-spin">sync</span>
                <div>
                    <h4 class="font-bold text-sm">Pengajuan Verifikasi Sedang Diproses</h4>
                    <p class="text-blue-800 mt-0.5">Berkas legalitas Anda sedang dalam antrean telaah kurator Destinara. Kami akan memberi tahu Anda via WhatsApp dan email begitu proses selesai.</p>
                </div>
            </div>
        @else
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 bg-[#faf6f0] border border-[#2B211E]/15">
                <div class="space-y-1">
                    <h4 class="font-bold text-xs text-[#2B211E] uppercase">Kirim Berkas untuk Diverifikasi Kurator</h4>
                    <p class="text-xs text-[#2B211E]/75">
                        Pastikan data lembaga, rekening penerima payout, dan minimal satu dokumen pendukung telah lengkap sebelum mengirimkan permohonan.
                    </p>
                </div>

                @if($canSubmit)
                <form action="{{ route('mitra.profile.submitVerification') }}" method="POST">
                    @csrf
                    <button type="submit" class="rgs-btn-primary px-6 py-2.5 text-xs font-bold uppercase tracking-wider flex items-center gap-2 shadow-xs shrink-0 cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">verified</span>
                        <span>Ajukan Verifikasi Sekarang</span>
                    </button>
                </form>
                @else
                <button type="button" disabled class="px-5 py-2.5 bg-gray-300 text-gray-600 text-xs font-bold uppercase tracking-wider cursor-not-allowed shrink-0">
                    Lengkapi Berkas Dahulu
                </button>
                @endif
            </div>
        @endif
    </div>

</div>
@endsection
