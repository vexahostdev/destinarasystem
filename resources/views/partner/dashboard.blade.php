@extends('layouts.partner')

@section('title', 'Dashboard Mitra Pengelola')

@section('content')
<div class="space-y-8">

    <!-- Header Sambutan & Quick Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-gray-200">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-[#51634b] mb-1">
                <span class="material-symbols-outlined text-[16px]">holiday_village</span>
                <span>{{ $profile->organization_name ?? 'Desa Wisata Adat Penglipuran' }}</span>
                <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-800 text-[10px] font-bold border border-emerald-200/50">Terverifikasi</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-[#1E1B1B] tracking-tight">Panel Pengelola Tapak Adat</h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-1">
                Pantau kedatangan rombongan delegasi belajar, kontrol kalender slot harian, dan pantau pencairan dana komunal adat.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('mitra.availability') }}" class="px-5 py-2.5 rounded-full border border-gray-300 hover:border-gray-400 text-gray-700 bg-white text-xs font-bold uppercase tracking-wider flex items-center gap-1.5 transition-all shadow-xs">
                <span class="material-symbols-outlined text-[18px]">calendar_month</span>
                <span>Kalender Slot</span>
            </a>
            <a href="{{ route('mitra.destinations.create') }}" class="bg-[#51634b] hover:bg-[#3f4f3a] text-white px-5 py-2.5 rounded-full text-xs font-bold uppercase tracking-wider flex items-center gap-1.5 shadow-sm hover:shadow-md transition-all">
                <span class="material-symbols-outlined text-[18px]">add_circle</span>
                <span>Ajukan Tapak Baru</span>
            </a>
        </div>
    </div>

    <!-- 4 Metric Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Metric 1: Booking Baru -->
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-xs hover:shadow-md transition-all">
            <div class="flex items-center justify-between text-gray-500 text-xs font-semibold mb-2">
                <span>Perlu Respon</span>
                <div class="w-8 h-8 rounded-full bg-amber-50 text-amber-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">notifications_active</span>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-amber-700">{{ $pendingBookingsCount }}</div>
            <div class="text-[11px] text-gray-400 mt-1">Menunggu persetujuan adat</div>
        </div>

        <!-- Metric 2: Rombongan Terkonfirmasi -->
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-xs hover:shadow-md transition-all">
            <div class="flex items-center justify-between text-gray-500 text-xs font-semibold mb-2">
                <span>Rombongan Terjadwal</span>
                <div class="w-8 h-8 rounded-full bg-emerald-50 text-[#51634b] flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">groups</span>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-[#51634b]">{{ $confirmedBookingsCount }}</div>
            <div class="text-[11px] text-gray-400 mt-1">Siap berkunjung ke tapak</div>
        </div>

        <!-- Metric 3: Saldo Siap Tarik -->
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-xs hover:shadow-md transition-all">
            <div class="flex items-center justify-between text-gray-500 text-xs font-semibold mb-2">
                <span>Saldo Siap Tarik</span>
                <div class="w-8 h-8 rounded-full bg-[#703A3A]/10 text-[#703A3A] flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">account_balance_wallet</span>
                </div>
            </div>
            <div class="text-2xl sm:text-3xl font-extrabold text-[#703A3A]">
                Rp {{ number_format($readyBalance, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-[#51634b] font-semibold mt-1">Dapat dicairkan ke BPD Bali</div>
        </div>

        <!-- Metric 4: Total Payout Diterima -->
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-xs hover:shadow-md transition-all">
            <div class="flex items-center justify-between text-gray-500 text-xs font-semibold mb-2">
                <span>Total Dana Diterima</span>
                <div class="w-8 h-8 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">savings</span>
                </div>
            </div>
            <div class="text-2xl sm:text-3xl font-extrabold text-[#1E1B1B]">
                Rp {{ number_format($totalPaidOut, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-gray-400 mt-1">Rekonsiliasi bersih komunal</div>
        </div>
    </div>

    <!-- Booking Perlu Respon Cepat -->
    <div class="bg-white rounded-3xl p-6 border border-amber-200/60 shadow-md space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-amber-600 text-[22px]">error</span>
                <h2 class="text-lg font-bold text-[#1E1B1B]">Permohonan Rombongan Masuk (Perlu Tindakan)</h2>
            </div>
            <span class="text-xs font-bold text-amber-700 uppercase tracking-wider bg-amber-50 px-3 py-1 rounded-full border border-amber-200">Maks. 1x24 Jam</span>
        </div>

        <div class="space-y-3">
            @forelse($urgentBookings as $booking)
            <div class="p-4 rounded-2xl bg-[#FAF7F5] border border-gray-200 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div class="space-y-1.5 flex-1">
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="font-mono font-bold text-[#703A3A] bg-white px-2.5 py-0.5 rounded-full border border-gray-200">{{ $booking->booking_code }}</span>
                        <span class="text-gray-300">•</span>
                        <span class="font-bold text-[#1E1B1B]">{{ $booking->institution->institution_name }}</span>
                        <span class="px-2.5 py-0.5 rounded-full bg-[#703A3A]/10 text-[#703A3A] text-[10px] font-bold uppercase">
                            {{ $booking->inquiry_type === 'study_tour' ? 'Study Tour P5' : 'Penelitian Lapangan' }}
                        </span>
                    </div>

                    <div class="text-xs text-gray-600 flex flex-wrap items-center gap-3">
                        <span><strong>{{ $booking->participant_count }} Siswa / Peserta</strong> ({{ $booking->guide_count }} Pendamping)</span>
                        <span>•</span>
                        <span>Tanggal: <strong>{{ \Carbon\Carbon::parse($booking->planned_date_start)->translatedFormat('d M Y') }} s/d {{ \Carbon\Carbon::parse($booking->planned_date_end)->translatedFormat('d M Y') }}</strong></span>
                        <span>•</span>
                        <span>Estimasi Payout: <strong class="text-[#51634b]">Rp {{ number_format($booking->subtotal_amount * 0.9, 0, ',', '.') }}</strong></span>
                    </div>

                    @if($booking->purpose_notes)
                    <div class="text-[11px] text-gray-500 italic pt-1">
                        "{{ $booking->purpose_notes }}"
                    </div>
                    @endif
                </div>

                <!-- Tombol Konfirmasi Langsung -->
                <div class="flex items-center gap-2 shrink-0">
                    <form action="{{ route('mitra.booking.confirm', $booking->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-5 py-2.5 rounded-full bg-[#51634b] text-white hover:bg-[#3f4f3a] text-xs font-bold uppercase tracking-wider flex items-center gap-1.5 transition-all shadow-xs cursor-pointer">
                            <span class="material-symbols-outlined text-[16px]">check_circle</span>
                            <span>Konfirmasi</span>
                        </button>
                    </form>

                    <button type="button" onclick="const r = prompt('Alasan penolakan / pengalihan jadwal:'); if(r){ document.getElementById('reject-form-{{ $booking->id }}').reason.value = r; document.getElementById('reject-form-{{ $booking->id }}').submit(); }" 
                            class="px-4 py-2.5 rounded-full border border-rose-300 text-rose-700 bg-white hover:bg-rose-50 text-xs font-bold uppercase tracking-wider transition-all cursor-pointer">
                        Tolak
                    </button>
                    <form id="reject-form-{{ $booking->id }}" action="{{ route('mitra.booking.reject', $booking->id) }}" method="POST" class="hidden">
                        @csrf
                        <input type="hidden" name="reason" value="">
                    </form>
                </div>
            </div>
            @empty
            <div class="p-6 text-center text-xs text-gray-400 bg-[#FAF7F5] rounded-2xl border border-gray-100">
                Tidak ada permohonan rombongan baru yang menunggu respon saat ini. Seluruh jadwal telah terkoordinasi.
            </div>
            @endforelse
        </div>
    </div>

    <!-- 2 Kolom: Okupansi Kuota Pekan Ini & Tapak Kelolaan -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

        <!-- Kolom Kiri: Kalender Slot Okupansi Pekan Ini (7 Kolom) -->
        <div class="lg:col-span-7 space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-gray-200">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#703A3A] text-[20px]">date_range</span>
                    <span class="text-lg font-bold text-[#1E1B1B]">Ketersediaan Slot 7 Hari Ke Depan</span>
                </div>
                <a href="{{ route('mitra.availability') }}" class="text-xs font-bold text-[#703A3A] hover:underline flex items-center gap-0.5">
                    <span>Kalender Penuh</span>
                    <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                </a>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                @foreach($upcomingSlots as $slot)
                <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-xs text-xs space-y-2">
                    <div class="font-bold text-[#1E1B1B]">
                        {{ \Carbon\Carbon::parse($slot->date)->translatedFormat('D, d M') }}
                    </div>
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="text-gray-400">Terisi:</span>
                        <span class="font-bold font-mono text-[#1E1B1B]">{{ $slot->booked_count }} / {{ $slot->capacity }}</span>
                    </div>
                    <div class="pt-1">
                        @if($slot->status === 'open')
                            <span class="px-2.5 py-1 rounded-full bg-emerald-50 text-[#51634b] text-[10px] font-bold uppercase tracking-wider block text-center">
                                Kuota Buka
                            </span>
                        @elseif($slot->status === 'full')
                            <span class="px-2.5 py-1 rounded-full bg-rose-50 text-rose-700 text-[10px] font-bold uppercase tracking-wider block text-center">
                                Penuh
                            </span>
                        @else
                            <span class="px-2.5 py-1 rounded-full bg-gray-100 text-gray-600 text-[10px] font-bold uppercase tracking-wider block text-center">
                                Tutup Adat
                            </span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Kolom Kanan: Tapak Pembelajaran Aktif (5 Kolom) -->
        <div class="lg:col-span-5 space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-gray-200">
                <span class="text-lg font-bold text-[#1E1B1B]">Tapak Kelolaan</span>
                <a href="{{ route('mitra.destinations') }}" class="text-xs font-bold text-[#51634b] hover:underline flex items-center gap-0.5">
                    <span>Lihat Semua</span>
                    <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                </a>
            </div>

            @if($primaryDest)
            <div class="bg-white rounded-3xl overflow-hidden border border-gray-100 shadow-sm">
                <div class="aspect-16/9 bg-gray-100 overflow-hidden relative">
                    <img src="{{ asset($primaryDest->cover_image) }}" alt="{{ $primaryDest->name }}" class="w-full h-full object-cover">
                    <div class="absolute top-3 left-3 px-3 py-1 rounded-full bg-black/60 backdrop-blur-md text-white text-[10px] font-bold uppercase">
                        {{ strtoupper(str_replace('_', ' ', $primaryDest->category)) }}
                    </div>
                </div>
                <div class="p-5 space-y-2 text-xs">
                    <h3 class="text-base font-bold text-[#1E1B1B]">{{ $primaryDest->name }}</h3>
                    <p class="text-[11px] text-gray-500 line-clamp-2">
                        {{ $primaryDest->description }}
                    </p>
                    <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                        <span class="text-[11px] text-gray-600">Tarif: <strong class="text-[#1E1B1B]">Rp {{ number_format($primaryDest->price_per_pax, 0, ',', '.') }} / pax</strong></span>
                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-[#51634b] text-[10px] font-bold uppercase">
                            Tayang & Aktif
                        </span>
                    </div>
                </div>
            </div>
            @endif
        </div>

    </div>

</div>
@endsection
