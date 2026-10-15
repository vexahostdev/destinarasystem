@extends('layouts.admin')

@section('title', 'Manajemen Booking Lintas Destinasi')

@section('content')
<div class="space-y-8" x-data="{ 
    rescheduleModal: false, 
    cancelModal: false, 
    selectedBookingId: null, 
    selectedBookingCode: '', 
    currentDate: '' 
}">

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-[#2B211E]/12 pb-6">
        <div>
            <div class="flex items-center gap-2 text-xs font-mono font-semibold uppercase tracking-wider text-[#703A3A] mb-1">
                <span>Manajemen Transaksi & Rombongan</span>
                <span>•</span>
                <span>Seluruh Indonesia</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#2B211E]">
                Manajemen Booking Lintas Destinasi
            </h1>
            <p class="text-sm text-[#2B211E]/70 max-w-2xl">
                Pantau seluruh reservasi rombongan sekolah dan kampus, track status pembayaran resmi SP2D/BOS, serta lakukan intervensi jadwal operasional jika terjadi kendala lapangan.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.documents.index') }}" class="rgs-btn-outline px-4 py-2.5 text-xs font-semibold uppercase tracking-wider flex items-center gap-2 bg-white">
                <span class="material-symbols-outlined text-[18px]">print</span>
                <span>Generator Dokumen Resmi</span>
            </a>
        </div>
    </div>

    <!-- Filter Bar & Search -->
    <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">
        <!-- Status Filter Pills -->
        <div class="flex items-center gap-2 overflow-x-auto pb-2 lg:pb-0 text-xs font-semibold uppercase tracking-wider">
            <a href="{{ route('admin.bookings.index', ['status' => 'all', 'search' => $search]) }}" 
               class="px-3.5 py-2 transition-colors flex items-center gap-1.5 {{ $status === 'all' ? 'bg-[#703A3A] text-white shadow-xs' : 'bg-white text-[#2B211E]/75 border border-[#2B211E]/15 hover:bg-[#2B211E]/5' }}">
                <span>Semua</span>
                <span class="px-1.5 py-0.2 bg-black/10 text-[10px] font-mono">{{ $statusCounts['all'] }}</span>
            </a>
            <a href="{{ route('admin.bookings.index', ['status' => 'pending_confirmation', 'search' => $search]) }}" 
               class="px-3.5 py-2 transition-colors flex items-center gap-1.5 {{ $status === 'pending_confirmation' ? 'bg-[#703A3A] text-white shadow-xs' : 'bg-white text-[#2B211E]/75 border border-[#2B211E]/15 hover:bg-[#2B211E]/5' }}">
                <span>Review Mitra</span>
                <span class="px-1.5 py-0.2 bg-amber-100 text-amber-900 border border-amber-300 text-[10px] font-mono">{{ $statusCounts['pending_confirmation'] }}</span>
            </a>
            <a href="{{ route('admin.bookings.index', ['status' => 'awaiting_payment', 'search' => $search]) }}" 
               class="px-3.5 py-2 transition-colors flex items-center gap-1.5 {{ $status === 'awaiting_payment' ? 'bg-[#703A3A] text-white shadow-xs' : 'bg-white text-[#2B211E]/75 border border-[#2B211E]/15 hover:bg-[#2B211E]/5' }}">
                <span>Menunggu Bayar</span>
                <span class="px-1.5 py-0.2 bg-amber-100 text-amber-900 border border-amber-300 text-[10px] font-mono">{{ $statusCounts['awaiting_payment'] }}</span>
            </a>
            <a href="{{ route('admin.bookings.index', ['status' => 'confirmed', 'search' => $search]) }}" 
               class="px-3.5 py-2 transition-colors flex items-center gap-1.5 {{ $status === 'confirmed' ? 'bg-[#703A3A] text-white shadow-xs' : 'bg-white text-[#2B211E]/75 border border-[#2B211E]/15 hover:bg-[#2B211E]/5' }}">
                <span>Lunas / Sah</span>
                <span class="px-1.5 py-0.2 bg-emerald-100 text-emerald-900 border border-emerald-300 text-[10px] font-mono">{{ $statusCounts['confirmed'] }}</span>
            </a>
            <a href="{{ route('admin.bookings.index', ['status' => 'completed', 'search' => $search]) }}" 
               class="px-3.5 py-2 transition-colors flex items-center gap-1.5 {{ $status === 'completed' ? 'bg-[#703A3A] text-white shadow-xs' : 'bg-white text-[#2B211E]/75 border border-[#2B211E]/15 hover:bg-[#2B211E]/5' }}">
                <span>Selesai</span>
                <span class="px-1.5 py-0.2 bg-gray-100 text-gray-900 border border-gray-300 text-[10px] font-mono">{{ $statusCounts['completed'] }}</span>
            </a>
        </div>

        <!-- Search Input Form -->
        <form method="GET" action="{{ route('admin.bookings.index') }}" class="flex items-center gap-2">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="relative w-full sm:w-72">
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari kode, sekolah, tapak..." 
                       class="w-full pl-9 pr-3 py-2 text-xs border border-[#2B211E]/20 bg-white focus:ring-1 focus:ring-[#703A3A] outline-none">
                <span class="material-symbols-outlined text-[16px] absolute left-2.5 top-2.5 text-[#2B211E]/40">search</span>
            </div>
            <button type="submit" class="rgs-btn-primary px-3 py-2 text-xs font-semibold uppercase">
                Filter
            </button>
            @if(!empty($search))
                <a href="{{ route('admin.bookings.index', ['status' => $status]) }}" class="text-xs text-[#703A3A] hover:underline">Reset</a>
            @endif
        </form>
    </div>

    <!-- Master Tabel Booking -->
    <div class="rgs-card bg-white overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-[#faf6f0] border-b border-[#2B211E]/12 text-[#2B211E]/60 uppercase tracking-wider font-mono">
                        <th class="py-3 px-4 font-semibold">Kode Reservasi</th>
                        <th class="py-3 px-4 font-semibold">Institusi Pemesan</th>
                        <th class="py-3 px-4 font-semibold">Destinasi Tapak</th>
                        <th class="py-3 px-4 font-semibold">Tgl Pelaksanaan</th>
                        <th class="py-3 px-4 font-semibold text-center">Rombongan</th>
                        <th class="py-3 px-4 font-semibold text-right">Nilai Transaksi</th>
                        <th class="py-3 px-4 font-semibold text-center">Status</th>
                        <th class="py-3 px-4 font-semibold text-center">Intervensi Operasional</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#2B211E]/08">
                    @forelse($bookings as $b)
                    <tr class="hover:bg-[#faf6f0]/60 transition-colors">
                        <td class="py-4 px-4">
                            <span class="font-mono font-bold text-[#703A3A] text-sm block">{{ $b->booking_code }}</span>
                            <span class="text-[10px] text-[#2B211E]/50 font-mono">{{ $b->created_at->format('d/m/Y H:i') }}</span>
                        </td>
                        <td class="py-4 px-4">
                            <span class="font-semibold text-[#2B211E] block">{{ $b->user->institution->institution_name ?? $b->user->name }}</span>
                            <span class="text-[11px] text-[#2B211E]/60">PIC: {{ $b->user->institution->pic_name ?? $b->user->name }} ({{ $b->user->phone ?? '-' }})</span>
                        </td>
                        <td class="py-4 px-4">
                            <span class="font-medium text-[#2B211E] block">{{ $b->destination->name ?? '-' }}</span>
                            <span class="text-[11px] text-[#2B211E]/60">{{ $b->destination->city ?? '' }}, {{ $b->destination->province?->name ?? (is_string($b->destination->province) ? $b->destination->province : '') }}</span>
                        </td>
                        <td class="py-4 px-4 font-mono font-semibold text-[#2B211E]">
                            {{ \Carbon\Carbon::parse($b->planned_date_start ?? $b->visit_date)->translatedFormat('d M Y') }}
                        </td>
                        <td class="py-4 px-4 text-center">
                            <span class="font-mono font-bold text-sm block">{{ $b->participant_count ?? $b->total_pax }} Pax</span>
                            <span class="text-[10px] uppercase font-mono text-[#2B211E]/60">{{ $b->destination->category?->name ?? $b->destination->category ?? 'studi' }}</span>
                        </td>
                        <td class="py-4 px-4 text-right">
                            <span class="font-mono font-bold text-sm text-[#2B211E] block">
                                Rp {{ number_format($b->total_amount ?? $b->total_price ?? 0, 0, ',', '.') }}
                            </span>
                            @if($b->payment && ($b->payment->status === \App\Enums\PaymentStatus::PAID || $b->payment->isPaid()))
                                <span class="text-[10px] text-emerald-800 font-semibold font-mono">Lunas ({{ strtoupper(is_object($b->payment->payment_method) ? $b->payment->payment_method->value : (string)($b->payment->payment_method ?? 'VA')) }})</span>
                            @else
                                <span class="text-[10px] text-amber-800 font-semibold font-mono">Belum Lunas</span>
                            @endif
                        </td>
                        <td class="py-4 px-4 text-center">
                            @php
                                $bSt = $b->status instanceof \UnitEnum ? $b->status->value : (string)$b->status;
                                $bLabel = $b->status instanceof \App\Enums\BookingStatus ? $b->status->label() : strtoupper($bSt);
                            @endphp
                            @if(in_array($bSt, ['confirmed', 'dikonfirmasi']))
                                <span class="px-2.5 py-1 bg-emerald-100 text-emerald-900 border border-emerald-300 text-[10px] font-semibold uppercase font-mono">
                                    Terkonfirmasi
                                </span>
                            @elseif(in_array($bSt, ['awaiting_payment', 'menunggu_pembayaran']))
                                <span class="px-2.5 py-1 bg-amber-100 text-amber-900 border border-amber-300 text-[10px] font-semibold uppercase font-mono">
                                    Menunggu Bayar
                                </span>
                            @elseif(in_array($bSt, ['pending_confirmation', 'review_mitra', 'dibayar']))
                                <span class="px-2.5 py-1 bg-blue-100 text-blue-900 border border-blue-300 text-[10px] font-semibold uppercase font-mono">
                                    Review Mitra
                                </span>
                            @elseif(in_array($bSt, ['completed', 'selesai']))
                                <span class="px-2.5 py-1 bg-gray-100 text-gray-800 border border-gray-300 text-[10px] font-semibold uppercase font-mono">
                                    Selesai
                                </span>
                            @else
                                <span class="px-2.5 py-1 bg-red-100 text-red-800 border border-red-300 text-[10px] font-semibold uppercase font-mono">
                                    {{ $bLabel }}
                                </span>
                            @endif
                        </td>
                        <td class="py-4 px-4 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                
                                @if(in_array($bSt, ['pending_confirmation', 'dibayar']))
                                    <!-- Force Confirm Button -->
                                    <form action="{{ route('admin.bookings.updateStatus', $b->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="action" value="force_confirm">
                                        <button type="submit" title="Konfirmasi Darurat Lapangan" class="p-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 transition-colors">
                                            <span class="material-symbols-outlined text-[16px]">check_circle</span>
                                        </button>
                                    </form>
                                @endif

                                <!-- Reschedule Button -->
                                <button type="button" 
                                        @click="selectedBookingId = {{ $b->id }}; selectedBookingCode = '{{ $b->booking_code }}'; currentDate = '{{ $b->visit_date }}'; rescheduleModal = true"
                                        title="Jadwalkan Ulang Tanggal Rombongan" 
                                        class="p-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 transition-colors">
                                    <span class="material-symbols-outlined text-[16px]">edit_calendar</span>
                                </button>

                                <!-- Dokumen Langsung -->
                                <a href="{{ route('admin.documents.index', ['code' => $b->booking_code]) }}" title="Terbitkan Dokumen Resmi" class="p-1.5 bg-blue-50 hover:bg-blue-100 text-blue-800 border border-blue-200 transition-colors">
                                    <span class="material-symbols-outlined text-[16px]">description</span>
                                </a>

                                @if(!in_array($bSt, ['cancelled', 'dibatalkan']))
                                <!-- Cancel Modal Button -->
                                <button type="button" 
                                        @click="selectedBookingId = {{ $b->id }}; selectedBookingCode = '{{ $b->booking_code }}'; cancelModal = true"
                                        title="Batalkan Reservasi" 
                                        class="p-1.5 bg-red-50 hover:bg-red-100 text-red-800 border border-red-200 transition-colors">
                                    <span class="material-symbols-outlined text-[16px]">cancel</span>
                                </button>
                                @endif

                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-12 text-center text-[#2B211E]/60">
                            <span class="material-symbols-outlined text-3xl mb-1 text-[#2B211E]/30">search_off</span>
                            <p class="font-medium">Tidak ada booking yang cocok dengan kriteria filter.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Reschedule Tanggal Kunjungan -->
    <div x-show="rescheduleModal" 
         x-cloak
         class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4 backdrop-blur-xs"
         @keydown.escape.window="rescheduleModal = false">
        
        <div class="bg-white max-w-md w-full p-6 space-y-4 border border-[#2B211E]/20 shadow-xl" @click.away="rescheduleModal = false">
            <div class="flex items-center justify-between border-b border-[#2B211E]/10 pb-3">
                <h3 class="font-serif text-lg font-bold text-[#2B211E]">Jadwalkan Ulang Kunjungan</h3>
                <button @click="rescheduleModal = false" class="text-[#2B211E]/60 hover:text-[#2B211E]">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>

            <form :action="'/admin/booking/' + selectedBookingId + '/status'" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="action" value="reschedule">

                <div class="bg-[#faf6f0] p-3 border border-[#2B211E]/10 text-xs">
                    <p class="text-[#2B211E]/70">Kode Reservasi:</p>
                    <p class="font-mono font-bold text-[#703A3A] text-sm" x-text="selectedBookingCode"></p>
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E]/70">
                        Tanggal Kunjungan Baru *
                    </label>
                    <input type="date" name="new_date" :value="currentDate" required min="{{ now()->addDays((int) setting('min_lead_days', 3))->format('Y-m-d') }}" class="w-full p-2.5 border border-[#2B211E]/20 text-xs focus:ring-1 focus:ring-[#703A3A] outline-none">
                    @error('new_date')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-[10px] text-[#2B211E]/60">Sistem akan otomatis menyesuaikan slot ketersediaan pada tapak tujuan.</p>
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E]/70">
                        Alasan Penjadwalan Ulang *
                    </label>
                    <textarea name="reschedule_reason" required rows="2" placeholder="Catatan alasan perubahan tanggal kunjungan..."
                              class="w-full p-2.5 border border-[#2B211E]/20 text-xs focus:ring-1 focus:ring-[#703A3A] outline-none"></textarea>
                    @error('reschedule_reason')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-3 pt-2 border-t border-[#2B211E]/10">
                    <button type="button" @click="rescheduleModal = false" class="rgs-btn-outline px-4 py-2 text-xs font-semibold uppercase">
                        Batal
                    </button>
                    <button type="submit" class="rgs-btn-primary px-4 py-2 text-xs font-semibold uppercase tracking-wider">
                        Simpan Jadwal Baru
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Batalkan Reservasi -->
    <div x-show="cancelModal" 
         x-cloak
         class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4 backdrop-blur-xs"
         @keydown.escape.window="cancelModal = false">
        
        <div class="bg-white max-w-md w-full p-6 space-y-4 border border-[#2B211E]/20 shadow-xl" @click.away="cancelModal = false">
            <div class="flex items-center justify-between border-b border-[#2B211E]/10 pb-3">
                <h3 class="font-serif text-lg font-bold text-red-700">Batalkan Reservasi Rombongan</h3>
                <button @click="cancelModal = false" class="text-[#2B211E]/60 hover:text-[#2B211E]">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>

            <form :action="'/admin/booking/' + selectedBookingId + '/status'" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="action" value="cancel">

                <p class="text-xs text-[#2B211E]/80 leading-relaxed">
                    Apakah Anda yakin ingin membatalkan reservasi <strong class="font-mono text-[#703A3A]" x-text="selectedBookingCode"></strong>? Slot kuota harian akan dikembalikan ke tapak dan pemberitahuan pembatalan akan dikirimkan ke pihak sekolah dan mitra.
                </p>

                <div class="flex items-center justify-end gap-3 pt-2 border-t border-[#2B211E]/10">
                    <button type="button" @click="cancelModal = false" class="rgs-btn-outline px-4 py-2 text-xs font-semibold uppercase">
                        Kembali
                    </button>
                    <button type="submit" class="px-4 py-2 text-xs font-semibold uppercase tracking-wider bg-red-700 hover:bg-red-800 text-white">
                        Konfirmasi Pembatalan
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
