@extends('layouts.buyer')

@section('title', 'Profil Institusi & Dokumen Pendukung')

@section('content')
@php
    $isVerified = $institution->verification_status === \App\Enums\VerificationStatus::VERIFIED;
    $isPending = $institution->verification_status === \App\Enums\VerificationStatus::PENDING;
    $isRejected = $institution->verification_status === \App\Enums\VerificationStatus::REJECTED;
    $isUnverified = $institution->verification_status === \App\Enums\VerificationStatus::UNVERIFIED;
@endphp

<div class="space-y-8 max-w-5xl mx-auto">

    <!-- Header Breadcrumb & Judul -->
    <div class="pb-4 border-b border-[#1F2937]/12">
        <div class="flex items-center gap-2 text-xs text-[#1F2937]/60 mb-2">
            <a href="{{ route('buyer.dashboard') }}" class="hover:text-[#1F2B45]">Dashboard</a>
            <span>/</span>
            <span class="text-[#1F2B45] font-semibold">Profil Institusi & Legalitas</span>
        </div>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#1F2937]">Profil Institusi & Dokumen Pendukung</h1>
                <p class="text-xs sm:text-sm text-[#1F2937]/70 mt-0.5">
                    Data sekolah/kampus dan penanggung jawab resmi untuk verifikasi SPK, SP2D, dan penerbitan izin dinas.
                </p>
            </div>

            <div>
                @if($isVerified)
                    <div class="px-3 py-1.5 bg-emerald-100 border border-emerald-300 text-emerald-900 text-xs font-bold uppercase tracking-wider flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px]">verified</span>
                        <span>Akun Institusi Terverifikasi</span>
                    </div>
                @elseif($isPending)
                    <div class="px-3 py-1.5 bg-blue-100 border border-blue-300 text-blue-900 text-xs font-bold uppercase tracking-wider flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px]">hourglass_top</span>
                        <span>Menunggu Validasi Admin</span>
                    </div>
                @elseif($isRejected)
                    <div class="px-3 py-1.5 bg-red-100 border border-red-300 text-red-900 text-xs font-bold uppercase tracking-wider flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px]">warning</span>
                        <span>Verifikasi Perlu Perbaikan</span>
                    </div>
                @else
                    <div class="px-3 py-1.5 bg-gray-100 border border-gray-300 text-gray-700 text-xs font-bold uppercase tracking-wider flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px]">info</span>
                        <span>Belum Terverifikasi (Opsional)</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Alert Catatan Penolakan -->
    @if($isRejected && $institution->verification_note)
    <div class="p-4 bg-red-50 border border-red-300 rounded-sm text-xs text-red-800 space-y-1">
        <div class="flex items-center gap-2 font-bold text-red-900">
            <span class="material-symbols-outlined text-[18px]">error</span>
            <span>Catatan Penolakan dari Administrator:</span>
        </div>
        <p class="pl-6 text-red-700">{{ $institution->verification_note }}</p>
    </div>
    @endif

    <!-- Form Pembaruan Data Institusi -->
    <form action="{{ route('buyer.profile.update') }}" method="POST" class="space-y-8">
        @csrf

        <!-- 1. Data Lembaga / Sekolah -->
        <div class="rgs-card p-6 border-t-4 border-t-[#1F2B45] space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-[#1F2937]/10">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#1F2B45] text-[20px]">account_balance</span>
                    <h2 class="font-serif text-lg font-bold text-[#1F2937]">1. Identitas Sekolah / Perguruan Tinggi</h2>
                </div>
                <span class="text-[11px] text-[#1F2937]/60">Data Pokok Pendidikan (Dapodik / PDDIKTI)</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#1F2937]">
                        Nama Resmi Lembaga / Sekolah <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="institution_name" placeholder="Contoh: SMA Negeri 1 ..." value="{{ old('institution_name', $institution->institution_name ?? '') }}" required class="w-full bg-[#F4F6F9] border border-[#1F2937]/20 px-3.5 py-2 text-xs font-medium text-[#1F2937] focus:outline-none focus:border-[#1F2B45]">
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#1F2937]">
                        Jenis / Jenjang Lembaga <span class="text-red-500">*</span>
                    </label>
                    <select name="institution_type" class="w-full bg-[#F4F6F9] border border-[#1F2937]/20 px-3.5 py-2 text-xs font-medium text-[#1F2937] focus:outline-none focus:border-[#1F2B45]">
                        <option value="sekolah" {{ ($institution->institution_type ?? '') == 'sekolah' ? 'selected' : '' }}>Sekolah Menengah Atas / Kejuruan (SMA/SMK)</option>
                        <option value="kampus" {{ ($institution->institution_type ?? '') == 'kampus' ? 'selected' : '' }}>Perguruan Tinggi / Universitas</option>
                        <option value="riset" {{ ($institution->institution_type ?? '') == 'riset' ? 'selected' : '' }}>Pusat Riset / Laboratorium Sains</option>
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#1F2937]">
                        Nomor Pokok Sekolah Nasional (NPSN / NIDN)
                    </label>
                    <input type="text" name="npsn" placeholder="Contoh: 20104567" value="{{ old('npsn', $institution->npsn ?? '') }}" class="w-full bg-[#F4F6F9] border border-[#1F2937]/20 px-3.5 py-2 text-xs font-mono text-[#1F2937] focus:outline-none focus:border-[#1F2B45]">
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#1F2937]">
                        Kota / Kabupaten & Provinsi <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" name="city" placeholder="Kabupaten/Kota" value="{{ old('city', $institution->city ?? '') }}" required class="w-full bg-[#F4F6F9] border border-[#1F2937]/20 px-3 py-2 text-xs font-medium text-[#1F2937] focus:outline-none focus:border-[#1F2B45]">
                        <input type="text" name="province" placeholder="Provinsi" value="{{ old('province', $institution->province ?? '') }}" required class="w-full bg-[#F4F6F9] border border-[#1F2937]/20 px-3 py-2 text-xs font-medium text-[#1F2937] focus:outline-none focus:border-[#1F2B45]">
                    </div>
                </div>

                <div class="sm:col-span-2 space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#1F2937]">
                        Alamat Lengkap Kantor / Kampus <span class="text-red-500">*</span>
                    </label>
                    <textarea name="address" rows="2" placeholder="Alamat lengkap instansi..." required class="w-full bg-[#F4F6F9] border border-[#1F2937]/20 p-3 text-xs text-[#1F2937] focus:outline-none focus:border-[#1F2B45]">{{ old('address', $institution->address ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <!-- 2. Data Penanggung Jawab (PIC) -->
        <div class="rgs-card p-6 border-t-4 border-t-[#1F2B45] space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-[#1F2937]/10">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#1F2B45] text-[20px]">person</span>
                    <h2 class="font-serif text-lg font-bold text-[#1F2937]">2. Penanggung Jawab Resmi (PIC)</h2>
                </div>
                <span class="text-[11px] text-[#1F2937]/60">Penandatangan SPK & Izin Ekskursi</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#1F2937]">
                        Nama Lengkap & Gelar PIC <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="pic_name" placeholder="Nama PIC..." value="{{ old('pic_name', $institution->pic_name ?? Auth::user()->name) }}" required class="w-full bg-[#F4F6F9] border border-[#1F2937]/20 px-3.5 py-2 text-xs font-medium text-[#1F2937] focus:outline-none focus:border-[#1F2B45]">
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#1F2937]">
                        Jabatan Struktural Institusi <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="pic_position" placeholder="Contoh: Kepala Sekolah / Dosen Pembimbing" value="{{ old('pic_position', $institution->pic_position ?? '') }}" required class="w-full bg-[#F4F6F9] border border-[#1F2937]/20 px-3.5 py-2 text-xs font-medium text-[#1F2937] focus:outline-none focus:border-[#1F2B45]">
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#1F2937]">
                        Nomor WhatsApp Aktif <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="pic_phone" placeholder="08xxxxxxxxxx" value="{{ old('pic_phone', $institution->pic_phone ?? Auth::user()->phone) }}" required class="w-full bg-[#F4F6F9] border border-[#1F2937]/20 px-3.5 py-2 text-xs font-mono text-[#1F2937] focus:outline-none focus:border-[#1F2B45]">
                    <span class="text-[10px] text-[#1F2937]/60">Digunakan untuk notifikasi status verifikasi tapak secara otomatis</span>
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#1F2937]">
                        Email Dinas / Surat Elektronik <span class="text-red-500">*</span>
                    </label>
                    <input type="email" name="pic_email" placeholder="email@instansi.sch.id" value="{{ old('pic_email', $institution->pic_email ?? Auth::user()->email) }}" required class="w-full bg-[#F4F6F9] border border-[#1F2937]/20 px-3.5 py-2 text-xs text-[#1F2937] focus:outline-none focus:border-[#1F2B45]">
                </div>
            </div>
        </div>

        <!-- Tombol Aksi Simpan Profil -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('buyer.dashboard') }}" class="rgs-btn-outline px-5 py-2.5 text-xs font-bold uppercase tracking-wider">
                Batal
            </a>
            <button type="submit" class="rgs-btn-primary px-6 py-2.5 text-xs font-bold uppercase tracking-wider shadow-xs flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">save</span>
                <span>Simpan Perubahan Profil</span>
            </button>
        </div>
    </form>

    <!-- 3. Legalitas & Dokumen Pendukung Institusi -->
    <div class="rgs-card p-6 border-t-4 border-t-[#51634b] space-y-6">
        <div class="flex items-center justify-between pb-3 border-b border-[#1F2937]/10">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[#51634b] text-[20px]">approval_delegation</span>
                <h2 class="font-serif text-lg font-bold text-[#1F2937]">3. Otomasi Dokumen & Kop Surat Resmi</h2>
            </div>
            <span class="text-[11px] text-[#51634b] font-bold uppercase">Format Standar Dinas</span>
        </div>

        <p class="text-xs text-[#1F2937]/75 leading-relaxed">
            Destinara secara otomatis memuat nama penanggung jawab, nomor izin, serta kop surat institusi Anda ke dalam <strong>Surat Konfirmasi Kunjungan (e-Pass)</strong> agar memangkas birokrasi perizinan.
        </p>

        <!-- Form Upload Berkas Pendukung -->
        <form action="{{ route('buyer.profile.document.upload') }}" method="POST" enctype="multipart/form-data" class="p-4 bg-[#F4F6F9] border border-[#1F2937]/15 space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#1F2937]">
                        Jenis Dokumen <span class="text-red-500">*</span>
                    </label>
                    <select name="type" required class="w-full bg-white border border-[#1F2937]/20 px-3 py-2 text-xs font-medium text-[#1F2937] focus:outline-none focus:border-[#1F2B45]">
                        <option value="kop_surat">Kop Surat / Stempel Digital Resmi</option>
                        <option value="sk_pendirian">SK Izin Operasional Sekolah / Kampus</option>
                        <option value="surat_tugas">Surat Tugas / Rekomendasi Kepala Instansi</option>
                        <option value="lainnya">Dokumen Pendukung Lainnya</option>
                    </select>
                </div>

                <div class="sm:col-span-2 space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#1F2937]">
                        Pilih Berkas Dokumen <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center gap-2">
                        <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" required class="w-full bg-white border border-[#1F2937]/20 px-3 py-1.5 text-xs text-[#1F2937] focus:outline-none focus:border-[#1F2B45]">
                        <button type="submit" class="rgs-btn-primary px-4 py-2 text-xs font-bold uppercase tracking-wider shrink-0 flex items-center gap-1.5 shadow-xs">
                            <span class="material-symbols-outlined text-[16px]">upload_file</span>
                            <span>Unggah</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Daftar Dokumen Institusi yang Sudah Diunggah -->
        <div class="space-y-2">
            <h4 class="text-xs font-bold uppercase tracking-wider text-[#1F2937]">Berkas Terunggah ({{ $institution->documents->count() }})</h4>
            @if($institution->documents->isNotEmpty())
            <div class="divide-y divide-[#1F2937]/10 border border-[#1F2937]/15 bg-white">
                @foreach($institution->documents as $doc)
                <div class="p-3.5 flex items-center justify-between gap-4 text-xs">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-[#1F2B45] text-[20px]">description</span>
                        <div>
                            <div class="font-bold text-[#1F2937]">{{ $doc->original_name }}</div>
                            <div class="text-[11px] text-[#1F2937]/60 flex items-center gap-2">
                                <span class="font-mono uppercase font-semibold text-[#1F2B45]">{{ str_replace('_', ' ', $doc->type) }}</span>
                                <span>•</span>
                                <span>Diunggah {{ $doc->created_at->translatedFormat('d M Y H:i') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('buyer.profile.document.download', $doc->id) }}" class="px-3 py-1.5 border border-[#1F2937]/20 hover:border-[#1F2B45] text-xs font-semibold text-[#1F2B45] flex items-center gap-1 transition-colors">
                            <span class="material-symbols-outlined text-[14px]">download</span>
                            <span>Unduh</span>
                        </a>

                        <form action="{{ route('buyer.profile.document.delete', $doc->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus dokumen ini?')">
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
            <div class="p-6 text-center text-xs text-[#1F2937]/60 bg-[#F4F6F9] border border-[#1F2937]/10">
                Belum ada berkas pendukung yang diunggah.
            </div>
            @endif
        </div>
    </div>

    <!-- 4. Bagian Pengajuan Verifikasi Institusi (Opsional) -->
    <div class="rgs-card p-6 border-t-4 {{ $isVerified ? 'border-t-emerald-600' : ($isPending ? 'border-t-blue-600' : 'border-t-[#2B3A4A]') }} space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-[#1F2937]/10">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined {{ $isVerified ? 'text-emerald-700' : ($isPending ? 'text-blue-700' : 'text-[#2B3A4A]') }} text-[22px]">
                    {{ $isVerified ? 'verified_user' : ($isPending ? 'pending_actions' : 'badge') }}
                </span>
                <h2 class="font-serif text-lg font-bold text-[#1F2937]">4. Verifikasi Keabsahan Institusi (Opsional BOS/BOPTN)</h2>
            </div>
            <span class="text-[11px] font-mono text-[#1F2937]/60">Validasi Kredensial</span>
        </div>

        @if($isVerified)
            <div class="p-4 bg-emerald-50 border border-emerald-200 text-xs text-emerald-900 flex items-center gap-3">
                <span class="material-symbols-outlined text-emerald-700 text-2xl">verified</span>
                <div>
                    <h4 class="font-bold text-sm">Institusi Anda Telah Terverifikasi Sah</h4>
                    <p class="text-emerald-800 mt-0.5">NPSN/NIDN institusi Anda telah terverifikasi untuk pertanggungjawaban BOS dan kelengkapan dinas.</p>
                </div>
            </div>
        @elseif($isPending)
            <div class="p-4 bg-blue-50 border border-blue-200 text-xs text-blue-900 flex items-center gap-3">
                <span class="material-symbols-outlined text-blue-700 text-2xl animate-spin">sync</span>
                <div>
                    <h4 class="font-bold text-sm">Pengajuan Verifikasi Institusi Sedang Ditinjau</h4>
                    <p class="text-blue-800 mt-0.5">Tim Destinara sedang memvalidasi keabsahan data institusi Anda.</p>
                </div>
            </div>
        @else
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 bg-[#F4F6F9] border border-[#1F2937]/15">
                <div class="space-y-1">
                    <h4 class="font-bold text-xs text-[#1F2937] uppercase">Ajukan Validasi Keabsahan Institusi</h4>
                    <p class="text-xs text-[#1F2937]/75">
                        Memudahkan verifikasi SPK dan penerbitan izin dinas secara resmi untuk rombongan sekolah / universitas.
                    </p>
                </div>

                <form action="{{ route('buyer.profile.submitVerification') }}" method="POST">
                    @csrf
                    <button type="submit" class="rgs-btn-primary px-6 py-2.5 text-xs font-bold uppercase tracking-wider flex items-center gap-2 shadow-xs shrink-0 cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">verified</span>
                        <span>Ajukan Verifikasi Institusi</span>
                    </button>
                </form>
            </div>
        @endif
    </div>

</div>
@endsection
