@extends('layouts.partner')

@section('title', 'Kelola Listing Destinasi')

@section('content')
<div class="space-y-8">

    <!-- Header & Tombol Tambah Destinasi -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-[#2B211E]/12">
        <div>
            <div class="flex items-center gap-2 text-xs text-[#2B211E]/60 mb-2">
                <a href="{{ route('mitra.dashboard') }}" class="hover:text-[#703A3A]">Dashboard</a>
                <span>/</span>
                <span class="text-[#703A3A] font-semibold">Tapak Kelolaan</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#2B211E]">Katalog Tapak Laboratorium Kelolaan</h1>
            <p class="text-xs sm:text-sm text-[#2B211E]/70 mt-1">
                Kelola konten edukasi, fasilitas rombongan, tarif tiket resmi, dan pantau status kurasi listing baru.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('mitra.destinations.create') }}" class="rgs-btn-primary px-5 py-2.5 text-xs font-bold uppercase tracking-wider flex items-center gap-1.5 shadow-sm">
                <span class="material-symbols-outlined text-[18px]">add_circle</span>
                <span>Ajukan Destinasi Baru</span>
            </a>
        </div>
    </div>

    <!-- Grid Kartu Tapak Kelolaan -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @foreach($destinations as $dest)
        <div class="rgs-card overflow-hidden border border-[#2B211E]/15 hover:border-[#703A3A]/40 transition-all flex flex-col">
            <!-- Cover Gambar & Badge Status -->
            <div class="aspect-16/9 bg-gray-100 relative overflow-hidden">
                <img src="{{ asset($dest->cover_image) }}" alt="{{ $dest->name }}" class="w-full h-full object-cover">

                <div class="absolute top-3 left-3 flex items-center gap-2">
                    <span class="px-2.5 py-1 bg-[#2B211E]/80 text-white text-[10px] font-bold uppercase tracking-wider">
                        {{ strtoupper(str_replace('_', ' ', $dest->category)) }}
                    </span>
                </div>

                <div class="absolute top-3 right-3">
                    @if($dest->status === \App\Enums\DestinationStatus::PUBLISHED)
                        <span class="px-2.5 py-1 bg-[#51634b] text-white text-[10px] font-bold uppercase tracking-wider shadow-xs flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-white"></span> Tayang & Aktif
                        </span>
                    @elseif($dest->status === \App\Enums\DestinationStatus::PENDING_REVIEW)
                        <span class="px-2.5 py-1 bg-[#b87a38] text-white text-[10px] font-bold uppercase tracking-wider shadow-xs flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-white"></span> Sedang Dikurasi Admin
                        </span>
                    @else
                        <span class="px-2.5 py-1 bg-gray-600 text-white text-[10px] font-bold uppercase tracking-wider">
                            Draf
                        </span>
                    @endif
                </div>
            </div>

            <!-- Konten Deskripsi & Spesifikasi -->
            <div class="p-6 space-y-4 flex-1 flex flex-col justify-between">
                <div class="space-y-2">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-[#703A3A]">
                        {{ $dest->city }}, {{ $dest->province?->name ?? (is_string($dest->province) ? $dest->province : '') }}
                    </div>
                    <h2 class="font-serif text-xl font-bold text-[#2B211E] leading-snug">
                        {{ $dest->name }}
                    </h2>
                    <p class="text-xs text-[#2B211E]/75 leading-relaxed line-clamp-3">
                        {{ $dest->description }}
                    </p>

                    <!-- Modul & Fasilitas Singkat -->
                    @if($dest->educational_highlights)
                    <div class="p-3 bg-[#faf6f0] border border-[#2B211E]/10 text-xs space-y-1">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#703A3A] block">Fokus Modul Ilmiah:</span>
                        <p class="text-[11px] text-[#2B211E]/80">{{ $dest->educational_highlights }}</p>
                    </div>
                    @endif
                </div>

                <!-- Footer Metrik & Tombol Aksi -->
                <div class="pt-4 border-t border-[#2B211E]/10 space-y-3">
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <span class="text-[#2B211E]/60 text-[11px] block">Tarif Retribusi Edukasi:</span>
                            <span class="font-serif text-base font-bold text-[#703A3A]">
                                Rp {{ number_format($dest->price_per_pax, 0, ',', '.') }} <span class="text-xs font-sans text-[#2B211E]/60 font-normal">/ pax</span>
                            </span>
                        </div>
                        <div>
                            <span class="text-[#2B211E]/60 text-[11px] block">Total Kunjungan Rombongan:</span>
                            <span class="font-bold text-[#2B211E]">
                                {{ $dest->bookings_count ?? 0 }} Rombongan
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-2 pt-1">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('mitra.availability', ['destinasi' => $dest->slug]) }}" class="rgs-btn-outline px-3.5 py-1.5 text-xs font-bold uppercase tracking-wider flex items-center gap-1">
                                <span class="material-symbols-outlined text-[16px]">calendar_month</span>
                                <span>Kelola Slot</span>
                            </a>
                        </div>

                        @if($dest->status === \App\Enums\DestinationStatus::PUBLISHED)
                            <a href="{{ route('destinasi.show', $dest->slug) }}" target="_blank" class="text-xs font-bold text-[#51634b] hover:underline flex items-center gap-1">
                                <span>Lihat Halaman Publik</span>
                                <span class="material-symbols-outlined text-[14px]">open_in_new</span>
                            </a>
                        @else
                            <span class="text-[11px] text-[#b87a38] font-semibold italic">
                                Menunggu persetujuan kurasi admin
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

</div>
@endsection
