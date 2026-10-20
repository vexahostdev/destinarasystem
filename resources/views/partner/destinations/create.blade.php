@extends('layouts.partner')

@section('title', 'Ajukan Destinasi Baru')

@section('content')
<div class="space-y-8 max-w-4xl mx-auto">

    <!-- Header Breadcrumb & Judul -->
    <div class="pb-4 border-b border-[#1F2937]/12">
        <div class="flex items-center gap-2 text-xs text-[#1F2937]/60 mb-2">
            <a href="{{ route('mitra.dashboard') }}" class="hover:text-[#1F2B45]">Dashboard</a>
            <span>/</span>
            <a href="{{ route('mitra.destinations') }}" class="hover:text-[#1F2B45]">Tapak Kelolaan</a>
            <span>/</span>
            <span class="text-[#1F2B45] font-semibold">Ajukan Tapak Baru</span>
        </div>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#1F2937]">Formulir Kurasi Pengajuan Tapak Baru</h1>
                <p class="text-xs sm:text-sm text-[#1F2937]/70 mt-0.5">
                    Daftarkan laboratorium alam atau situs budaya Anda agar dapat diakses oleh jejaring sekolah dan perguruan tinggi.
                </p>
            </div>

            <span class="px-3 py-1.5 bg-[#b87a38]/10 text-[#b87a38] text-xs font-bold uppercase tracking-wider border border-[#b87a38]/20">
                Tahap Telaah Kurasi Admin
            </span>
        </div>
    </div>

    <form action="{{ route('mitra.destinations.store') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
        @csrf

        <!-- 1. Identitas & Lokasi Geografis -->
        <div class="rgs-card p-6 border-t-4 border-t-[#1F2B45] space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-[#1F2937]/10">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#1F2B45] text-[20px]">location_on</span>
                    <h2 class="font-serif text-lg font-bold text-[#1F2937]">1. Identitas & Wilayah Administratif Tapak</h2>
                </div>
                <span class="text-[11px] text-[#1F2937]/60">Wajib Lengkap</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2 space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#1F2937]">
                        Nama Resmi Tapak Edukasi / Destinasi <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="Contoh: Kawasan Konservasi Kebun Bambu Purba Cikole" required class="w-full bg-[#F4F6F9] border border-[#1F2937]/20 px-3.5 py-2 text-xs font-medium text-[#1F2937] focus:outline-none focus:border-[#1F2B45]">
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#1F2937]">
                        Kategori Tapak Pembelajaran <span class="text-red-500">*</span>
                    </label>
                    <select name="category_id" required class="w-full bg-[#F4F6F9] border border-[#1F2937]/20 px-3.5 py-2 text-xs font-medium text-[#1F2937] focus:outline-none focus:border-[#1F2B45]">
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#1F2937]">
                        Kota / Kabupaten & Provinsi <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" name="city" value="{{ old('city') }}" placeholder="Kabupaten / Kota" required class="w-full bg-[#F4F6F9] border border-[#1F2937]/20 px-3 py-2 text-xs font-medium focus:outline-none focus:border-[#1F2B45]">
                        <select name="province_id" required class="w-full bg-[#F4F6F9] border border-[#1F2937]/20 px-3 py-2 text-xs font-medium focus:outline-none focus:border-[#1F2B45]">
                            <option value="">Pilih Provinsi</option>
                            @foreach($provinces as $prov)
                                <option value="{{ $prov->id }}" {{ old('province_id') == $prov->id ? 'selected' : '' }}>{{ $prov->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#1F2937]">
                        Kebijakan Pembatalan <span class="text-red-500">*</span>
                    </label>
                    <select name="cancellation_policy_id" required class="w-full bg-[#F4F6F9] border border-[#1F2937]/20 px-3.5 py-2 text-xs font-medium text-[#1F2937] focus:outline-none focus:border-[#1F2B45]">
                        @foreach($policies as $pol)
                            <option value="{{ $pol->id }}" {{ old('cancellation_policy_id') == $pol->id ? 'selected' : '' }}>{{ $pol->name }} (Refund: {{ $pol->refund_percent_before_h7 ?? 100 }}% &gt;H-7)</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-2 space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#1F2937]">
                        Foto Sampul Utama (Cover Image) <span class="text-red-500">*</span>
                    </label>
                    <input type="file" name="cover_image" accept="image/*" required class="w-full bg-[#F4F6F9] border border-[#1F2937]/20 p-2 text-xs font-medium text-[#1F2937] focus:outline-none focus:border-[#1F2B45] file:mr-4 file:py-1.5 file:px-3 file:border-0 file:text-xs file:font-semibold file:bg-[#1F2B45] file:text-white hover:file:bg-[#16203A]">
                    <span class="text-[10px] text-[#1F2937]/60">Format JPG/PNG maks 5MB. Foto ini akan menjadi identitas visual utama destinasi Anda.</span>
                </div>

                <div class="sm:col-span-2 space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#1F2937]">
                        Alamat Lengkap Tapak <span class="text-red-500">*</span>
                    </label>
                    <textarea name="address" rows="2" placeholder="Nama jalan, nomor, RT/RW, desa/kelurahan, kecamatan..." required class="w-full bg-[#F4F6F9] border border-[#1F2937]/20 p-3 text-xs text-[#1F2937] focus:outline-none focus:border-[#1F2B45]">{{ old('address') }}</textarea>
                </div>

                <div class="sm:col-span-2 space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#1F2937]">
                        Deskripsi Naratif Tapak & Sejarah Adat <span class="text-red-500">*</span>
                    </label>
                    <textarea name="description" rows="3" placeholder="Ceritakan latar belakang wilayah, kekayaan ekosistem, serta tata kelola kearifan lokal yang dipelihara masyarakat..." required class="w-full bg-[#F4F6F9] border border-[#1F2937]/20 p-3 text-xs text-[#1F2937] focus:outline-none focus:border-[#1F2B45]">{{ old('description') }}</textarea>
                </div>
            </div>
        </div>

        <!-- 2. Nilai Edukasi & Modul Kurikulum -->
        <div class="rgs-card p-6 border-t-4 border-t-[#1F2B45] space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-[#1F2937]/10">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#1F2B45] text-[20px]">school</span>
                    <h2 class="font-serif text-lg font-bold text-[#1F2937]">2. Nilai Edukasi & Relevansi Kurikulum</h2>
                </div>
                <span class="text-[11px] text-[#1F2937]/60">Acuan Kurikulum Merdeka & Riset</span>
            </div>

            <div class="space-y-4">
                <div class="space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#1F2937]">
                        Fokus Modul Ilmiah & Pembelajaran Lapangan <span class="text-red-500">*</span>
                    </label>
                    <textarea name="educational_highlights" rows="2" placeholder="Contoh: Pembelajaran zonasi hutan suci adat Palemahan, identifikasi 15 jenis vegetasi bambu endemik, dan workshop kerajinan bambu bersama pengrajin sepuh..." required class="w-full bg-[#F4F6F9] border border-[#1F2937]/20 p-3 text-xs text-[#1F2937] focus:outline-none focus:border-[#1F2B45]">{{ old('educational_highlights') }}</textarea>
                </div>

                <!-- Fasilitas Rombongan (Checkboxes) -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#1F2937]">
                        Fasilitas Sarana Rombongan Tersedia
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                        @foreach($facilities as $fac)
                            <label class="flex items-center gap-2 p-2 bg-[#F4F6F9] border border-[#1F2937]/10 cursor-pointer">
                                <input type="checkbox" name="facilities[]" value="{{ $fac->id }}"
                                       {{ in_array($fac->id, old('facilities', [])) ? 'checked' : '' }}
                                       class="accent-[#1F2B45]">
                                <span>{{ $fac->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Daya Tampung & Kapasitas Okupansi -->
        <div class="rgs-card p-6 border-t-4 border-t-[#1F2B45] space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-[#1F2937]/10">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#1F2B45] text-[20px]">groups</span>
                    <h2 class="font-serif text-lg font-bold text-[#1F2937]">3. Daya Tampung Rombongan</h2>
                </div>
                <span class="text-[11px] text-[#1F2937]/60">Paket edukasi diatur setelah draf dibuat</span>
            </div>

            <div>
                <div class="space-y-1 max-w-sm">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#1F2937]">
                        Kapasitas Maksimal Harian (Orang) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="default_daily_capacity" value="{{ old('default_daily_capacity', 100) }}" min="10" max="1000" required class="w-full bg-[#F4F6F9] border border-[#1F2937]/20 px-3.5 py-2 text-xs font-bold text-[#1F2937] focus:outline-none focus:border-[#1F2B45]">
                    <span class="text-[10px] text-[#1F2937]/60">Batas daya tampung kawasan per hari. Penetapan harga paket dilakukan pada tahap selanjutnya.</span>
                </div>
            </div>
        </div>

        <!-- 4. Kepatuhan FPIC & Surat Kesiapan Pengelola -->
        <div class="rgs-card p-6 border-t-4 border-t-[#51634b] space-y-4">
            <div class="flex items-center gap-2 pb-3 border-b border-[#1F2937]/10">
                <span class="material-symbols-outlined text-[#51634b] text-[20px]">verified</span>
                <h2 class="font-serif text-lg font-bold text-[#1F2937]">4. Kesepakatan Lembaga Adat & Protokol FPIC</h2>
            </div>

            <p class="text-xs text-[#1F2937]/75 leading-relaxed">
                Destinara menjunjung tinggi prinsip *Free, Prior and Informed Consent* (FPIC). Dengan mengajukan tapak ini, pengelola menyatakan telah mengantongi izin dari tetua adat / pimpinan lembaga pengelola untuk menerima rombongan pelajar dan peneliti secara terbuka dan aman.
            </p>

            <div class="p-3.5 bg-[#51634b]/8 border border-[#51634b]/20 flex items-start gap-3">
                <input type="checkbox" name="fpic_agreement" value="1" id="fpic" checked required class="mt-1 accent-[#51634b]">
                <label for="fpic" class="text-xs cursor-pointer">
                    <span class="font-bold text-[#1F2937] block">Pernyataan Kesiapan Lembaga Pengelola Tapak</span>
                    <span class="text-[#1F2937]/70 leading-relaxed block mt-0.5">
                        Saya menjamin bahwa lokasi ini aman untuk rombongan pelajar, memiliki izin operasional adat/desa, serta bersedia menyambut delegasi sesuai tanggal yang disepakati.
                    </span>
                </label>
            </div>
        </div>

        <!-- Tombol Kirim Pengajuan -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('mitra.destinations') }}" class="rgs-btn-outline px-5 py-2.5 text-xs font-bold uppercase tracking-wider">
                Batal
            </a>
            <button type="submit" class="rgs-btn-primary px-6 py-2.5 text-xs font-bold uppercase tracking-wider shadow-sm flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">send</span>
                <span>Kirim ke Antrean Kurasi Admin</span>
            </button>
        </div>

    </form>

</div>
@endsection
