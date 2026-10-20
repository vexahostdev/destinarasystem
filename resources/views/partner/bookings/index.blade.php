@extends('layouts.partner')

@section('title', 'Daftar Booking Masuk')

@section('content')
<div class="space-y-8">

    <!-- Header & Filter -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-[#2B211E]/12">
        <div>
            <div class="flex items-center gap-2 text-xs text-[#2B211E]/60 mb-2">
                <a href="{{ route('mitra.dashboard') }}" class="hover:text-[#703A3A]">Dashboard</a>
                <span>/</span>
                <span class="text-[#703A3A] font-semibold">Booking Masuk</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#2B211E]">Permohonan Rombongan Pelajar & Riset</h1>
            <p class="text-xs sm:text-sm text-[#2B211E]/70 mt-1">
                Verifikasi kesiapan pemandu lokal dan kapasitas balai pertemuan sebelum rombongan tiba di lokasi.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <span class="px-3 py-1.5 bg-[#51634b]/10 text-[#51634b] text-xs font-bold uppercase tracking-wider border border-[#51634b]/20">
                Respon Cepat Disarankan &lt; {{ (int) setting('partner_confirm_sla_hours', 48) }} Jam
            </span>
        </div>
    </div>

    <!-- Filter Tab Status -->
    <div class="border-b border-[#2B211E]/15 flex items-center gap-2 overflow-x-auto text-xs font-bold uppercase tracking-wider pb-1">
        <a href="{{ route('mitra.bookings', ['status' => 'semua']) }}" 
           class="px-4 py-2 border-b-2 transition-colors whitespace-nowrap {{ ($statusFilter === 'semua') ? 'border-[#703A3A] text-[#703A3A] bg-[#703A3A]/5' : 'border-transparent text-[#2B211E]/60 hover:text-[#2B211E]' }}">
            Semua Permohonan
        </a>
        <a href="{{ route('mitra.bookings', ['status' => 'dibayar']) }}" 
           class="px-4 py-2 border-b-2 transition-colors whitespace-nowrap {{ ($statusFilter === 'dibayar') ? 'border-[#703A3A] text-[#703A3A] bg-[#703A3A]/5' : 'border-transparent text-[#2B211E]/60 hover:text-[#2B211E]' }}">
            Perlu Respon Mitra
        </a>
        <a href="{{ route('mitra.bookings', ['status' => 'dikonfirmasi']) }}" 
           class="px-4 py-2 border-b-2 transition-colors whitespace-nowrap {{ ($statusFilter === 'dikonfirmasi') ? 'border-[#703A3A] text-[#703A3A] bg-[#703A3A]/5' : 'border-transparent text-[#2B211E]/60 hover:text-[#2B211E]' }}">
            Dikonfirmasi / Siap
        </a>
        <a href="{{ route('mitra.bookings', ['status' => 'berlangsung']) }}" 
           class="px-4 py-2 border-b-2 transition-colors whitespace-nowrap {{ ($statusFilter === 'berlangsung') ? 'border-[#703A3A] text-[#703A3A] bg-[#703A3A]/5' : 'border-transparent text-[#2B211E]/60 hover:text-[#2B211E]' }}">
            Sedang Berkunjung
        </a>
        <a href="{{ route('mitra.bookings', ['status' => 'selesai']) }}" 
           class="px-4 py-2 border-b-2 transition-colors whitespace-nowrap {{ ($statusFilter === 'selesai') ? 'border-[#703A3A] text-[#703A3A] bg-[#703A3A]/5' : 'border-transparent text-[#2B211E]/60 hover:text-[#2B211E]' }}">
            Selesai Terlaksana
        </a>
        <a href="{{ route('mitra.bookings', ['status' => 'batal']) }}" 
           class="px-4 py-2 border-b-2 transition-colors whitespace-nowrap {{ ($statusFilter === 'batal') ? 'border-[#703A3A] text-[#703A3A] bg-[#703A3A]/5' : 'border-transparent text-[#2B211E]/60 hover:text-[#2B211E]' }}">
            Ditolak / Batal
        </a>
    </div>

    <!-- Daftar Rombongan Masuk -->
    <div class="space-y-4">
        @forelse($bookings as $booking)
        <div class="rgs-card p-5 border border-[#2B211E]/15 hover:border-[#703A3A]/40 transition-all flex flex-col lg:flex-row lg:items-center justify-between gap-5">
            <!-- Informasi Rombongan -->
            <div class="space-y-2 flex-1">
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="font-mono font-bold text-[#703A3A]">{{ $booking->booking_code }}</span>
                    <span class="text-[#2B211E]/40">•</span>
                    <span class="font-bold text-[#2B211E] text-sm">{{ $booking->institution->institution_name }}</span>
                    <span class="px-2 py-0.5 bg-[#703A3A]/10 text-[#703A3A] text-[10px] font-bold uppercase">
                        {{ $booking->inquiry_type === \App\Enums\InquiryType::STUDY_TOUR ? 'Study Tour P5' : 'Riset Ilmiah' }}
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs text-[#2B211E]/75">
                    <div>
                        <span class="text-[#2B211E]/60 text-[11px] block">Jadwal Ekskursi:</span>
                        <span class="font-bold text-[#2B211E]">
                            {{ \Carbon\Carbon::parse($booking->planned_date_start)->translatedFormat('d M Y') }} s/d {{ \Carbon\Carbon::parse($booking->planned_date_end)->translatedFormat('d M Y') }}
                        </span>
                    </div>
                    <div>
                        <span class="text-[#2B211E]/60 text-[11px] block">Jumlah Rombongan:</span>
                        <span class="font-bold text-[#2B211E]">{{ $booking->participant_count }} Siswa + {{ $booking->companion_count }} Pendamping</span>
                    </div>
                    <div>
                        <span class="text-[#2B211E]/60 text-[11px] block">Estimasi Payout Pengelola:</span>
                        <span class="font-bold font-mono text-[#51634b]">Rp {{ number_format($booking->partner_net_amount ?: ($booking->subtotal_amount - $booking->commission_amount), 0, ',', '.') }}</span>
                    </div>
                </div>

                <!-- Kontak PIC Pemohon & Catatan Khusus -->
                <div class="p-3 bg-[#faf6f0] border border-[#2B211E]/10 text-xs space-y-1">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="text-[11px] text-[#2B211E]/70">
                            PIC: <strong>{{ $booking->user->name ?? 'Pemohon' }}</strong> @if(!empty($booking->user->phone)) (WhatsApp: <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $booking->user->phone) }}" target="_blank" class="text-[#51634b] font-bold underline">{{ $booking->user->phone }}</a>) @endif
                        </span>
                        <span class="text-[11px] text-[#2B211E]/60">Destinasi: <strong>{{ $booking->destination->name }}</strong></span>
                    </div>

                    @if($booking->purpose_notes)
                    <div class="text-[11px] text-[#2B211E]/80 italic pt-1 border-t border-[#2B211E]/10">
                        Catatan Kebutuhan: "{{ $booking->purpose_notes }}"
                    </div>
                    @endif
                </div>
            </div>

            <!-- Kolom Status & Aksi Tindakan Mitra -->
            <div class="flex flex-col sm:flex-row lg:flex-col items-start sm:items-center lg:items-end justify-between gap-3 pt-3 lg:pt-0 border-t lg:border-t-0 border-[#2B211E]/10">
                <!-- Status Badge -->
                <div>
                    @php $st = is_object($booking->status) ? $booking->status->value : $booking->status; @endphp
                    @if($st === 'dikonfirmasi')
                        <span class="px-3 py-1 bg-[#51634b]/15 text-[#51634b] text-xs font-bold uppercase tracking-wider border border-[#51634b]/30 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#51634b]"></span> Terjadwal & Siap
                        </span>
                    @elseif($st === 'dibayar')
                        <span class="px-3 py-1 bg-amber-100 text-amber-800 text-xs font-bold uppercase tracking-wider border border-amber-300 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> Lunas — Perlu Konfirmasi
                        </span>
                    @elseif($st === 'berlangsung')
                        <span class="px-3 py-1 bg-blue-100 text-blue-800 text-xs font-bold uppercase tracking-wider border border-blue-300 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> Sedang Berkunjung
                        </span>
                    @elseif($st === 'menunggu_pembayaran')
                        <span class="px-3 py-1 bg-gray-100 text-gray-700 text-xs font-bold uppercase tracking-wider border border-gray-300 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Menunggu Bayar
                        </span>
                    @elseif($st === 'selesai')
                        <span class="px-3 py-1 bg-[#2B211E]/10 text-[#2B211E]/80 text-xs font-bold uppercase tracking-wider">
                            Selesai
                        </span>
                    @elseif($st === 'batal')
                        <span class="px-3 py-1 bg-red-100 text-red-700 text-xs font-bold uppercase tracking-wider">
                            Ditolak / Batal
                        </span>
                    @else
                        <span class="px-3 py-1 bg-gray-100 text-gray-700 text-xs font-bold uppercase tracking-wider">
                            {{ ucfirst($st) }}
                        </span>
                    @endif
                </div>

                <!-- Tombol Aksi Cepat -->
                <div class="flex flex-wrap items-center gap-2">
                    @if($st === 'dibayar')
                        <form action="{{ route('mitra.booking.confirm', $booking->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-[#51634b] text-white hover:bg-[#3f4f3a] text-xs font-bold uppercase tracking-wider flex items-center gap-1 shadow-xs transition-colors">
                                <span class="material-symbols-outlined text-[16px]">check</span>
                                <span>Terima Rombongan</span>
                            </button>
                        </form>

                        <button type="button" onclick="const r = prompt('Alasan penolakan / pengalihan jadwal:'); if(r){ document.getElementById('b-rej-{{ $booking->id }}').reason.value = r; document.getElementById('b-rej-{{ $booking->id }}').submit(); }" class="px-3 py-2 border border-red-300 text-red-700 bg-white hover:bg-red-50 text-xs font-bold uppercase tracking-wider transition-colors">
                            Tolak
                        </button>
                        <form id="b-rej-{{ $booking->id }}" action="{{ route('mitra.booking.reject', $booking->id) }}" method="POST" class="hidden">
                            @csrf
                            <input type="hidden" name="reason" value="">
                        </form>
                    @endif

                    <a href="{{ route('mitra.booking.show', $booking->booking_code) }}" class="px-3 py-2 border border-[#2B211E]/20 text-[#2B211E] bg-[#faf6f0] hover:bg-[#703A3A] hover:text-white text-xs font-bold uppercase tracking-wider transition-colors flex items-center gap-1">
                        <span class="material-symbols-outlined text-[15px]">info</span>
                        <span>Detail</span>
                    </a>
                </div>
            </div>
        </div>
        @empty
        <div class="rgs-card p-12 text-center text-xs text-[#2B211E]/60 space-y-2">
            <span class="material-symbols-outlined text-4xl text-[#703A3A]">inbox</span>
            <div>Tidak ada daftar permohonan booking pada kategori ini.</div>
        </div>
        @endforelse
    </div>

</div>
@endsection
