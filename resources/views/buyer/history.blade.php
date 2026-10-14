@extends('layouts.buyer')

@section('title', 'Arsip Ekskursi & Riwayat Transaksi')

@section('content')
<div class="space-y-8">

    <!-- Header & Summary Stats -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-[#2B211E]/12">
        <div>
            <div class="flex items-center gap-2 text-xs text-[#2B211E]/60 mb-2">
                <a href="{{ route('buyer.dashboard') }}" class="hover:text-[#703A3A]">Dashboard</a>
                <span>/</span>
                <span class="text-[#703A3A] font-semibold">Arsip Ekskursi Lapangan</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#2B211E]">Riwayat Transaksi & Ekskursi Rombongan</h1>
            <p class="text-xs sm:text-sm text-[#2B211E]/70 mt-1">
                Seluruh catatan pemesanan, kuitansi resmi, kelengkapan SPJ BOS, dan fitur pengajuan rombongan angkatan baru.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('buyer.booking.create') }}" class="rgs-btn-primary px-5 py-2.5 text-xs font-bold uppercase tracking-wider flex items-center gap-1.5 shadow-sm">
                <span class="material-symbols-outlined text-[18px]">add_circle</span>
                <span>Ajukan Rombongan Baru</span>
            </a>
        </div>
    </div>

    <!-- 2 Metric Summary Plinth -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rgs-card p-4 border-t-4 border-t-[#703A3A]">
            <span class="text-[11px] text-[#2B211E]/60 block font-medium">Total Realisasi Anggaran</span>
            <div class="font-serif text-2xl font-bold text-[#703A3A] mt-1">
                Rp {{ number_format($totalExpenditure, 0, ',', '.') }}
            </div>
            <span class="text-[10px] text-[#2B211E]/50">Terverifikasi lunas & selesai</span>
        </div>

        <div class="rgs-card p-4 border-t-4 border-t-[#51634b]">
            <span class="text-[11px] text-[#2B211E]/60 block font-medium">Total Siswa Terlayani</span>
            <div class="font-serif text-2xl font-bold text-[#51634b] mt-1">
                {{ $totalPax }} Siswa
            </div>
            <span class="text-[10px] text-[#2B211E]/50">Terlindungi asuransi lapangan</span>
        </div>

        <div class="rgs-card p-4 border-t-4 border-t-[#2B211E]/30">
            <span class="text-[11px] text-[#2B211E]/60 block font-medium">Total Pesanan Tercatat</span>
            <div class="font-serif text-2xl font-bold text-[#2B211E] mt-1">
                {{ $bookings->count() }} Ekskursi
            </div>
            <span class="text-[10px] text-[#2B211E]/50">Dalam arsip institusi</span>
        </div>

        <div class="rgs-card p-4 border-t-4 border-t-[#b87a38]">
            <span class="text-[11px] text-[#2B211E]/60 block font-medium">Kepatuhan SPK / LPJ</span>
            <div class="font-serif text-2xl font-bold text-[#b87a38] mt-1">
                100% Sah
            </div>
            <span class="text-[10px] text-[#2B211E]/50">Dokumen stempel digital lengkap</span>
        </div>
    </div>

    <!-- Filter Tabs Status Pesanan -->
    <div class="border-b border-[#2B211E]/15 flex items-center gap-2 overflow-x-auto text-xs font-bold uppercase tracking-wider pb-1">
        <a href="{{ route('buyer.history', ['status' => 'semua']) }}" 
           class="px-4 py-2 border-b-2 transition-colors whitespace-nowrap {{ ($statusFilter === 'semua') ? 'border-[#703A3A] text-[#703A3A] bg-[#703A3A]/5' : 'border-transparent text-[#2B211E]/60 hover:text-[#2B211E]' }}">
            Semua Pesanan
        </a>
        <a href="{{ route('buyer.history', ['status' => 'menunggu_pembayaran']) }}" 
           class="px-4 py-2 border-b-2 transition-colors whitespace-nowrap {{ ($statusFilter === 'menunggu_pembayaran') ? 'border-[#703A3A] text-[#703A3A] bg-[#703A3A]/5' : 'border-transparent text-[#2B211E]/60 hover:text-[#2B211E]' }}">
            Menunggu Pembayaran
        </a>
        <a href="{{ route('buyer.history', ['status' => 'dikonfirmasi']) }}" 
           class="px-4 py-2 border-b-2 transition-colors whitespace-nowrap {{ ($statusFilter === 'dikonfirmasi') ? 'border-[#703A3A] text-[#703A3A] bg-[#703A3A]/5' : 'border-transparent text-[#2B211E]/60 hover:text-[#2B211E]' }}">
            Terkonfirmasi & Siap
        </a>
        <a href="{{ route('buyer.history', ['status' => 'selesai']) }}" 
           class="px-4 py-2 border-b-2 transition-colors whitespace-nowrap {{ ($statusFilter === 'selesai') ? 'border-[#703A3A] text-[#703A3A] bg-[#703A3A]/5' : 'border-transparent text-[#2B211E]/60 hover:text-[#2B211E]' }}">
            Selesai Terlaksana
        </a>
    </div>

    <!-- Daftar Kartu Riwayat Booking -->
    <div class="space-y-4">
        @forelse($bookings as $booking)
        <div class="rgs-card p-5 border border-[#2B211E]/15 hover:border-[#703A3A]/50 transition-all flex flex-col lg:flex-row lg:items-center justify-between gap-5">
            <!-- Kolom Kiri: Thumbnail & Ringkasan -->
            <div class="flex items-start gap-4 flex-1">
                <div class="w-24 h-24 sm:w-28 sm:h-28 bg-[#eee5df] border border-[#2B211E]/15 shrink-0 overflow-hidden relative">
                    <img src="{{ asset($booking->destination->cover_image) }}" alt="{{ $booking->destination->name }}" class="w-full h-full object-cover">
                    <div class="absolute bottom-0 inset-x-0 bg-[#2B211E]/80 text-white text-[9px] text-center uppercase tracking-wider py-0.5">
                        {{ $booking->inquiry_type === \App\Enums\InquiryType::STUDY_TOUR ? 'P5 / STUDY TOUR' : 'RISET ILMIAH' }}
                    </div>
                </div>

                <div class="space-y-1.5 flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-mono text-xs font-bold text-[#703A3A]">{{ $booking->booking_code }}</span>
                        <span class="text-[#2B211E]/40">•</span>
                        <span class="text-xs text-[#2B211E]/60">
                            {{ \Carbon\Carbon::parse($booking->planned_date_start)->translatedFormat('d M Y') }} - {{ \Carbon\Carbon::parse($booking->planned_date_end)->translatedFormat('d M Y') }}
                        </span>
                    </div>

                    <h3 class="font-serif text-lg font-bold text-[#2B211E] truncate">
                        {{ $booking->destination->name }}
                    </h3>

                    <div class="text-xs text-[#2B211E]/70 flex flex-wrap items-center gap-3">
                        <span class="flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px] text-[#2B211E]/60">groups</span>
                            <span>{{ $booking->participant_count }} Siswa ({{ $booking->companion_count }} Pendamping)</span>
                        </span>
                        <span>•</span>
                        <span class="flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px] text-[#2B211E]/60">location_on</span>
                            <span>{{ $booking->destination->city }}, {{ $booking->destination->province?->name ?? (is_string($booking->destination->province) ? $booking->destination->province : '') }}</span>
                        </span>
                    </div>

                    <div class="pt-1">
                        <span class="text-xs text-[#2B211E]/60">Nilai Transaksi: </span>
                        <span class="font-serif text-base font-bold text-[#703A3A]">
                            Rp {{ number_format($booking->total_amount, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Status & Aksi Tindakan Cepat -->
            <div class="flex flex-col sm:flex-row lg:flex-col items-start sm:items-center lg:items-end justify-between gap-3 pt-3 lg:pt-0 border-t lg:border-t-0 border-[#2B211E]/10">
                <!-- Status Badge -->
                <div>
                    @php
                        $st = $booking->status instanceof \App\Enums\BookingStatus ? $booking->status->value : (string)$booking->status;
                    @endphp
                    @if($st === 'dikonfirmasi' || $st === 'dibayar')
                        <span class="px-3 py-1 bg-[#51634b]/15 text-[#51634b] text-xs font-bold uppercase tracking-wider border border-[#51634b]/30 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#51634b]"></span> Terkonfirmasi / Lunas
                        </span>
                    @elseif($st === 'menunggu_pembayaran')
                        <span class="px-3 py-1 bg-[#b87a38]/15 text-[#b87a38] text-xs font-bold uppercase tracking-wider border border-[#b87a38]/30 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#b87a38]"></span> Menunggu Pembayaran
                        </span>
                    @elseif($st === 'selesai')
                        <span class="px-3 py-1 bg-[#2B211E]/10 text-[#2B211E]/80 text-xs font-bold uppercase tracking-wider border border-[#2B211E]/20">
                            Ekskursi Selesai
                        </span>
                    @else
                        <span class="px-3 py-1 bg-gray-100 text-gray-700 text-xs font-bold uppercase tracking-wider">
                            {{ $booking->status instanceof \App\Enums\BookingStatus ? $booking->status->label() : ucfirst((string)$booking->status) }}
                        </span>
                    @endif
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('buyer.booking.show', $booking->booking_code) }}" class="rgs-btn-outline px-3.5 py-1.5 text-xs font-bold uppercase tracking-wider">
                        Buka Dossier
                    </a>

                    @if($booking->status === \App\Enums\BookingStatus::MENUNGGU_PEMBAYARAN)
                        <a href="{{ route('buyer.booking.payment', $booking->booking_code) }}" class="rgs-btn-primary px-3.5 py-1.5 text-xs font-bold uppercase tracking-wider flex items-center gap-1">
                            <span class="material-symbols-outlined text-[15px]">credit_card</span>
                            <span>Bayar</span>
                        </a>
                    @else
                        <a href="{{ route('buyer.booking.show', $booking->booking_code) }}#dokumen" class="px-3.5 py-1.5 bg-[#51634b] text-white hover:bg-[#3f4f3a] text-xs font-bold uppercase tracking-wider flex items-center gap-1">
                            <span class="material-symbols-outlined text-[15px]">description</span>
                            <span>e-Pass</span>
                        </a>
                    @endif

                    <!-- Tombol Repeat Order / Pesan Ulang -->
                    <a href="{{ route('buyer.booking.create', ['reorder' => $booking->booking_code]) }}" title="Pesan Ulang Rombongan Baru dengan format yang sama" class="px-3.5 py-1.5 bg-[#faf6f0] hover:bg-[#703A3A] hover:text-white border border-[#2B211E]/20 text-[#2B211E] text-xs font-bold uppercase tracking-wider flex items-center gap-1 transition-colors">
                        <span class="material-symbols-outlined text-[15px]">repeat</span>
                        <span class="hidden sm:inline">Pesan Ulang</span>
                    </a>
                </div>
            </div>
        </div>
        @empty
        <div class="rgs-card p-12 text-center text-xs text-[#2B211E]/60 space-y-3">
            <span class="material-symbols-outlined text-4xl text-[#703A3A]">folder_off</span>
            <div>Tidak ada transaksi rombongan pada filter ini.</div>
            <a href="{{ route('buyer.booking.create') }}" class="rgs-btn-primary px-4 py-2 text-xs font-bold uppercase inline-block">
                Ajukan Pesanan Rombongan Sekarang
            </a>
        </div>
        @endforelse
    </div>

</div>
@endsection
