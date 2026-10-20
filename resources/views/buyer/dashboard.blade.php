@extends('layouts.buyer')

@section('title', 'Dashboard Buyer Institusi')

@section('content')
<div class="space-y-8">

    <!-- Header Sambutan & Quick Action -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-gray-200">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-[#1F2B45] mb-1">
                <span class="material-symbols-outlined text-[16px]">account_balance</span>
                <span>{{ $institution->institution_name ?? (Auth::user()->name ?? 'Institusi Terdaftar') }}</span>
                @if($institution?->verification_status === \App\Enums\VerificationStatus::VERIFIED)
                    <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-800 text-[10px] uppercase font-bold border border-emerald-200/50">Terverifikasi</span>
                @elseif($institution?->verification_status === \App\Enums\VerificationStatus::PENDING)
                    <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-800 text-[10px] uppercase font-bold border border-blue-200/50">Menunggu Validasi</span>
                @elseif($institution?->verification_status === \App\Enums\VerificationStatus::REJECTED)
                    <span class="px-2 py-0.5 rounded-full bg-red-50 text-red-800 text-[10px] uppercase font-bold border border-red-200/50">Perlu Perbaikan</span>
                @else
                    <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-700 text-[10px] uppercase font-bold border border-gray-200">Belum Terverifikasi</span>
                @endif
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-[#111827] tracking-tight">Ruang Kerja Perjalanan Akademik</h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-1">
                Kelola jadwal ekskursi lapangan, perizinan dinas, verifikasi kemitraan tapak, dan administrasi rombongan belajar.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('buyer.booking.create') }}" class="bg-[#1F2B45] hover:bg-[#16203A] text-white px-5 py-2.5 rounded-2xl text-xs font-bold uppercase tracking-wider flex items-center gap-2 shadow-sm hover:shadow-md transition-all">
                <span class="material-symbols-outlined text-[18px]">add_circle</span>
                <span>Ajukan Booking Rombongan</span>
            </a>
        </div>
    </div>

    <!-- 4 Metric Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Metric 1: Aktif -->
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-xs hover:shadow-md transition-all">
            <div class="flex items-center justify-between text-gray-500 text-xs font-semibold mb-2">
                <span>Rombongan Aktif</span>
                <div class="w-8 h-8 rounded-full bg-[#1F2B45]/10 text-[#1F2B45] flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">pending_actions</span>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-[#111827]">{{ $activeBookingsCount }}</div>
            <div class="text-[11px] text-gray-400 mt-1">Dalam proses & terjadwal</div>
        </div>

        <!-- Metric 2: Menunggu Pembayaran -->
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-xs hover:shadow-md transition-all">
            <div class="flex items-center justify-between text-gray-500 text-xs font-semibold mb-2">
                <span>Menunggu Pembayaran</span>
                <div class="w-8 h-8 rounded-full bg-amber-50 text-amber-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-amber-700">{{ $pendingPaymentCount }}</div>
            <div class="text-[11px] text-gray-400 mt-1">Perlu penyelesaian transaksi</div>
        </div>

        <!-- Metric 3: Dikonfirmasi & Siap -->
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-xs hover:shadow-md transition-all">
            <div class="flex items-center justify-between text-gray-500 text-xs font-semibold mb-2">
                <span>Kunjungan Dikonfirmasi</span>
                <div class="w-8 h-8 rounded-full bg-emerald-50 text-[#51634b] flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">verified_user</span>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-[#51634b]">{{ $confirmedCount }}</div>
            <div class="text-[11px] text-gray-400 mt-1">e-Pass & izin resmi siap unduh</div>
        </div>

        <!-- Metric 4: Selesai -->
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-xs hover:shadow-md transition-all">
            <div class="flex items-center justify-between text-gray-500 text-xs font-semibold mb-2">
                <span>Ekskursi Selesai</span>
                <div class="w-8 h-8 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">history_edu</span>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-[#111827]">{{ $completedCount }}</div>
            <div class="text-[11px] text-gray-400 mt-1">Arsip LPJ & repeat order</div>
        </div>
    </div>

    <!-- Jadwal Ekskursi Terdekat (Hero Card) -->
    @if($nextBooking)
    <div class="bg-white rounded-3xl overflow-hidden border border-gray-100 shadow-md">
        <div class="bg-[#FFFFFF] px-6 py-3.5 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[#1F2B45] text-[18px]">event_upcoming</span>
                <span class="text-xs font-bold uppercase tracking-wider text-[#1F2B45]">Ekskursi Rombongan Terdekat</span>
            </div>
            <div class="text-xs font-mono font-bold text-gray-600 bg-white px-3 py-1 rounded-full border border-gray-200">
                KODE: {{ $nextBooking->booking_code }}
            </div>
        </div>

        <div class="p-6 grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
            <!-- Thumbnail Foto Tapak -->
            <div class="lg:col-span-4 relative aspect-video sm:aspect-4/3 rounded-2xl bg-gray-100 overflow-hidden shadow-xs">
                <img src="{{ asset($nextBooking->destination->cover_image) }}" alt="{{ $nextBooking->destination->name }}" class="w-full h-full object-cover">
                <div class="absolute top-2.5 left-2.5 px-3 py-1 rounded-full bg-black/60 backdrop-blur-md text-white text-[10px] font-bold uppercase tracking-wider">
                    {{ strtoupper($nextBooking->destination->category?->name ?? (is_string($nextBooking->destination->category) ? $nextBooking->destination->category : 'Tapak Edukasi')) }}
                </div>
            </div>

            <!-- Detail Info Rombongan -->
            <div class="lg:col-span-8 space-y-4">
                <div class="flex flex-wrap items-center gap-2">
                    @php
                        $nextSt = $nextBooking->status instanceof \App\Enums\BookingStatus ? $nextBooking->status->value : (string)$nextBooking->status;
                    @endphp
                    @if($nextSt === 'dikonfirmasi' || $nextSt === 'dibayar')
                        <span class="px-3 py-1 rounded-full bg-emerald-50 text-[#51634b] text-xs font-bold uppercase tracking-wider flex items-center gap-1.5 border border-emerald-200/50">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#51634b]"></span> Terkonfirmasi
                        </span>
                    @elseif($nextSt === 'menunggu_pembayaran')
                        <span class="px-3 py-1 rounded-full bg-amber-50 text-amber-700 text-xs font-bold uppercase tracking-wider flex items-center gap-1.5 border border-amber-200/50">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Menunggu Pembayaran
                        </span>
                    @else
                        <span class="px-3 py-1 rounded-full bg-[#1F2B45]/10 text-[#1F2B45] text-xs font-bold uppercase tracking-wider">
                            {{ $nextBooking->status instanceof \App\Enums\BookingStatus ? $nextBooking->status->label() : ucfirst((string)$nextBooking->status) }}
                        </span>
                    @endif

                    <span class="text-xs text-gray-300">•</span>
                    <span class="text-xs font-bold text-[#1F2B45] uppercase tracking-wide">
                        {{ ($nextBooking->inquiry_type instanceof \App\Enums\InquiryType ? $nextBooking->inquiry_type->value : (string)$nextBooking->inquiry_type) === 'study_tour' ? 'Study Tour Kurikulum P5' : 'Penelitian Lapangan' }}
                    </span>
                </div>

                <h2 class="text-xl sm:text-2xl font-extrabold text-[#111827]">
                    {{ $nextBooking->destination->name }}
                </h2>

                <!-- Ringkasan Spesifikasi Kunjungan -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 py-3 border-y border-gray-100 text-xs">
                    <div>
                        <span class="text-gray-400 block text-[11px]">Tanggal Kunjungan</span>
                        <span class="font-bold text-[#111827]">{{ \Carbon\Carbon::parse($nextBooking->planned_date_start)->translatedFormat('d M Y') }}</span>
                    </div>
                    <div>
                        <span class="text-gray-400 block text-[11px]">Kapasitas</span>
                        <span class="font-bold text-[#111827]">{{ $nextBooking->participant_count }} Siswa + {{ $nextBooking->companion_count }} Guru</span>
                    </div>
                    <div>
                        <span class="text-gray-400 block text-[11px]">Total Transaksi</span>
                        <span class="font-bold text-[#1F2B45]">Rp {{ number_format($nextBooking->total_amount, 0, ',', '.') }}</span>
                    </div>
                    <div>
                        <span class="text-gray-400 block text-[11px]">Lokasi Tapak</span>
                        <span class="font-bold text-[#111827]">{{ $nextBooking->destination->city }}, {{ $nextBooking->destination->province?->name ?? (is_string($nextBooking->destination->province) ? $nextBooking->destination->province : '') }}</span>
                    </div>
                </div>

                <!-- Checklist Kesiapan & Action Button -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-1">
                    <div class="flex items-center gap-3 text-xs text-gray-500">
                        <span class="flex items-center gap-1 font-medium">
                            <span class="material-symbols-outlined text-[#51634b] text-[16px]">check_circle</span>
                            <span>SOP Keselamatan</span>
                        </span>
                        <span class="flex items-center gap-1 font-medium">
                            <span class="material-symbols-outlined text-[#51634b] text-[16px]">check_circle</span>
                            <span>Asuransi</span>
                        </span>
                        <span class="flex items-center gap-1 font-medium">
                            <span class="material-symbols-outlined text-[#51634b] text-[16px]">check_circle</span>
                            <span>Pemandu Siap</span>
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('buyer.booking.show', $nextBooking->booking_code) }}" class="px-4 py-2 rounded-full border border-gray-200 text-xs font-bold uppercase tracking-wider text-gray-700 hover:bg-gray-50 transition-all">
                            Detail Dossier
                        </a>

                        @if($nextSt === 'menunggu_pembayaran')
                            <a href="{{ route('buyer.booking.payment', $nextBooking->booking_code) }}" class="bg-[#1F2B45] hover:bg-[#16203A] text-white px-5 py-2 rounded-full text-xs font-bold uppercase tracking-wider flex items-center gap-1 shadow-xs transition-all">
                                <span class="material-symbols-outlined text-[16px]">credit_card</span>
                                <span>Bayar Tagihan</span>
                            </a>
                        @else
                            <a href="{{ route('buyer.booking.show', $nextBooking->booking_code) }}#dokumen" class="bg-[#51634b] hover:bg-[#3f4f3a] text-white px-5 py-2 rounded-full text-xs font-bold uppercase tracking-wider flex items-center gap-1 shadow-xs transition-all">
                                <span class="material-symbols-outlined text-[16px]">download</span>
                                <span>Unduh e-Pass</span>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- 2 Kolom: Riwayat Booking Berjalan & Rekomendasi Tapak -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

        <!-- Kolom Kiri: Riwayat Berjalan (7 Kolom) -->
        <div class="lg:col-span-7 space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-gray-200">
                <div class="flex items-center gap-2">
                    <span class="text-lg font-extrabold text-[#111827]">Pesanan Terbaru</span>
                    <span class="px-2.5 py-0.5 rounded-full bg-[#1F2B45]/10 text-[#1F2B45] text-xs font-bold">{{ $recentBookings->count() }}</span>
                </div>
                <a href="{{ route('buyer.history') }}" class="text-xs font-bold text-[#1F2B45] hover:underline flex items-center gap-1">
                    <span>Lihat Semua</span>
                    <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                </a>
            </div>

            <div class="space-y-3">
                @forelse($recentBookings as $b)
                <div class="bg-white rounded-2xl p-4 border border-gray-100 hover:border-[#1F2B45]/30 transition-all shadow-xs hover:shadow-md flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 text-[11px]">
                            <span class="font-mono font-bold text-[#1F2B45]">{{ $b->booking_code }}</span>
                            <span class="text-gray-300">•</span>
                            <span class="text-gray-500">{{ \Carbon\Carbon::parse($b->planned_date_start)->translatedFormat('d M Y') }}</span>
                        </div>
                        <h3 class="text-base font-bold text-[#111827] leading-snug">
                            {{ $b->destination->name }}
                        </h3>
                        <div class="text-xs text-gray-500 flex items-center gap-2">
                            <span>{{ $b->participant_count }} Siswa</span>
                            <span>•</span>
                            <span class="font-bold text-[#111827]">Rp {{ number_format($b->total_amount, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="flex items-center sm:flex-col sm:items-end justify-between gap-2">
                        @php
                            $bSt = $b->status instanceof \App\Enums\BookingStatus ? $b->status->value : (string)$b->status;
                            $bLabel = $b->status instanceof \App\Enums\BookingStatus ? $b->status->label() : ucfirst((string)$b->status);
                        @endphp
                        @if($bSt === 'dikonfirmasi')
                            <span class="px-2.5 py-1 rounded-full bg-emerald-50 text-[#51634b] text-[10px] font-bold uppercase tracking-wider">
                                Siap Berangkat
                            </span>
                        @elseif($bSt === 'menunggu_pembayaran')
                            <span class="px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 text-[10px] font-bold uppercase tracking-wider">
                                Belum Bayar
                            </span>
                        @elseif($bSt === 'selesai')
                            <span class="px-2.5 py-1 rounded-full bg-gray-100 text-gray-600 text-[10px] font-bold uppercase tracking-wider">
                                Selesai
                            </span>
                        @else
                            <span class="px-2.5 py-1 rounded-full bg-gray-100 text-gray-700 text-[10px] font-bold uppercase tracking-wider">
                                {{ $bLabel }}
                            </span>
                        @endif

                        <a href="{{ route('buyer.booking.show', $b->booking_code) }}" class="text-xs font-bold text-[#1F2B45] hover:underline flex items-center gap-0.5 mt-1">
                            <span>Detail</span>
                            <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                        </a>
                    </div>
                </div>
                @empty
                <div class="bg-white rounded-2xl p-8 text-center text-gray-400 text-xs border border-gray-100">
                    Belum ada riwayat pesanan rombongan. Klik tombol "Ajukan Booking Rombongan" di atas untuk memulai.
                </div>
                @endforelse
            </div>
        </div>

        <!-- Kolom Kanan: Rekomendasi Tapak Unggulan (5 Kolom) -->
        <div class="lg:col-span-5 space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-gray-200">
                <span class="text-lg font-extrabold text-[#111827]">Rekomendasi Tapak</span>
                <a href="{{ route('destinasi.index') }}" target="_blank" class="text-xs font-bold text-[#51634b] hover:underline flex items-center gap-0.5">
                    <span>Katalog</span>
                    <span class="material-symbols-outlined text-[14px]">open_in_new</span>
                </a>
            </div>

            <div class="space-y-3">
                @foreach($recommendedDestinations as $dest)
                <div class="bg-white rounded-2xl p-3 border border-gray-100 shadow-xs hover:shadow-md transition-all flex gap-3.5 items-center">
                    <div class="w-18 h-18 rounded-xl bg-gray-100 shrink-0 overflow-hidden">
                        <img src="{{ asset($dest->cover_image) }}" alt="{{ $dest->name }}" class="w-full h-full object-cover">
                    </div>
                    <div class="flex-1 min-w-0">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#1F2B45] block">
                            {{ $dest->city }}, {{ $dest->province?->name ?? (is_string($dest->province) ? $dest->province : '') }}
                        </span>
                        <h4 class="text-sm font-bold text-[#111827] truncate">
                            {{ $dest->name }}
                        </h4>
                        <div class="text-[11px] text-gray-500 mt-0.5">
                            Mulai <strong class="text-[#111827]">Rp {{ number_format($dest->price_per_pax, 0, ',', '.') }}</strong> / pax
                        </div>
                        <div class="mt-1.5 flex items-center gap-2">
                            <a href="{{ route('buyer.booking.create', ['destinasi' => $dest->slug]) }}" class="text-[11px] font-bold text-[#1F2B45] hover:underline flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">add_circle</span>
                                <span>Pesan Rombongan</span>
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Callout Bantuan Layanan Sekolah -->
            <div class="p-5 rounded-2xl bg-[#1F2B45]/5 border border-[#1F2B45]/10 text-xs text-gray-700 space-y-2">
                <div class="flex items-center gap-2 font-bold text-[#1F2B45]">
                    <span class="material-symbols-outlined text-[18px]">support_agent</span>
                    <span>Layanan Rombongan Sekolah</span>
                </div>
                <p class="text-[11px] text-gray-600 leading-relaxed">
                    Butuh penyesuaian SPK, dokumen verifikasi BOS daerah, atau proposal kurikulum untuk komite sekolah? Tim operasional Destinara siap mendampingi.
                </p>
                <div class="text-[11px] font-bold text-[#1F2B45]">
                    WhatsApp Hotline: {{ setting('support_whatsapp', setting('company_phone', 'Hubungi Admin')) }}
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
