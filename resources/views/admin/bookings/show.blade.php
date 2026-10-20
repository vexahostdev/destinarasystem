@extends('layouts.admin')

@section('title', 'Detail Reservasi #' . $booking->booking_code)

@section('content')
<div class="space-y-6" x-data="{ openCancelModal: false, openRescheduleModal: false }">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.bookings.index') }}" class="text-xs font-bold text-gray-500 hover:text-gray-700 flex items-center gap-1 mb-2">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                <span>Kembali ke Daftar Reservasi</span>
            </a>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-extrabold text-[#111827] font-mono">#{{ $booking->booking_code }}</h1>
                <x-status-badge :enum="$booking->status" />
            </div>
            <p class="text-sm text-gray-500 mt-1">Dipesan pada {{ $booking->created_at->translatedFormat('d F Y H:i') }}</p>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap items-center gap-2">
            @if($booking->status === \App\Enums\BookingStatus::DIBAYAR)
                <form action="{{ route('admin.bookings.updateStatus', $booking->id) }}" method="POST" class="inline" onsubmit="return confirm('Konfirmasi reservasi ini sekarang?')">
                    @csrf
                    <input type="hidden" name="action" value="force_confirm">
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-2xl text-xs font-bold uppercase tracking-wider">
                        Konfirmasi Booking
                    </button>
                </form>
            @endif

            @if(in_array($booking->status, [\App\Enums\BookingStatus::DIBAYAR, \App\Enums\BookingStatus::DIKONFIRMASI]))
                <button type="button" @click="openRescheduleModal = true" class="border border-stone-300 text-stone-700 hover:bg-stone-50 px-4 py-2 rounded-2xl text-xs font-bold uppercase tracking-wider">
                    Jadwal Ulang
                </button>
            @endif

            @if($booking->status === \App\Enums\BookingStatus::DIKONFIRMASI)
                <form action="{{ route('admin.bookings.updateStatus', $booking->id) }}" method="POST" class="inline" onsubmit="return confirm('Tandai kunjungan rombongan ini telah selesai?')">
                    @csrf
                    <input type="hidden" name="action" value="complete">
                    <button type="submit" class="bg-[#51634b] hover:bg-[#3d4b38] text-white px-4 py-2 rounded-2xl text-xs font-bold uppercase tracking-wider">
                        Tandai Selesai
                    </button>
                </form>
            @endif

            @if(!in_array($booking->status, [\App\Enums\BookingStatus::BATAL, \App\Enums\BookingStatus::KEDALUWARSA, \App\Enums\BookingStatus::SELESAI]))
                <button type="button" @click="openCancelModal = true" class="border border-rose-300 text-rose-700 hover:bg-rose-50 px-4 py-2 rounded-2xl text-xs font-bold uppercase tracking-wider">
                    Batalkan Reservasi
                </button>
            @endif
        </div>
    </div>

    <!-- 2 Cols Content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Details (2 Cols) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Informasi Tapak & Institusi -->
            <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs space-y-4">
                <h3 class="text-base font-bold text-[#111827]">Detail Pesanan & Destinasi</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <span class="text-gray-400 block font-semibold">Destinasi Tapak:</span>
                        <span class="font-bold text-gray-900 text-sm block mt-0.5">{{ $booking->destination?->name }}</span>
                        <span class="text-gray-500">{{ $booking->destination?->city }}, {{ $booking->destination?->province?->name }}</span>
                        <span class="block text-gray-500 mt-1">Mitra: {{ $booking->destination?->partner?->name }}</span>
                    </div>
                    <div>
                        <span class="text-gray-400 block font-semibold">Institusi Pemesan:</span>
                        <span class="font-bold text-gray-900 text-sm block mt-0.5">{{ $booking->user?->institution?->institution_name ?? $booking->user?->name }}</span>
                        <span class="text-gray-500">PIC: {{ $booking->user?->institution?->pic_name ?? $booking->user?->name }}</span>
                        <span class="block text-gray-500 font-mono">{{ $booking->user?->institution?->pic_phone ?? $booking->user?->phone }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-3 border-t border-gray-100 text-xs">
                    <div>
                        <span class="text-gray-400 block font-semibold">Paket Layanan:</span>
                        <span class="font-bold text-gray-800">{{ $booking->package?->name ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-400 block font-semibold">Tanggal Kunjungan:</span>
                        <span class="font-bold text-gray-800 font-mono">
                            {{ $booking->planned_date_start?->translatedFormat('d F Y') }} s/d {{ $booking->planned_date_end?->translatedFormat('d F Y') }}
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-400 block font-semibold">Jumlah Peserta:</span>
                        <span class="font-bold text-gray-800 font-mono">{{ $booking->participant_count }} Orang (Pax)</span>
                    </div>
                </div>
            </div>

            <!-- Rincian Item Layanan -->
            <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs space-y-4">
                <h3 class="text-base font-bold text-[#111827]">Rincian Item Pembayaran</h3>
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-gray-100 text-gray-400 uppercase font-semibold">
                            <th class="pb-2">Uraian</th>
                            <th class="pb-2 text-center">Kuantitas</th>
                            <th class="pb-2 text-right">Harga Satuan</th>
                            <th class="pb-2 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($booking->items as $item)
                        <tr>
                            <td class="py-2.5 font-medium text-gray-800">{{ $item->name }}</td>
                            <td class="py-2.5 text-center font-mono">{{ $item->quantity }}</td>
                            <td class="py-2.5 text-right font-mono">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                            <td class="py-2.5 text-right font-mono font-bold">{{ $item->total < 0 ? '-' : '' }}Rp {{ number_format(abs($item->total), 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td class="py-2.5 font-medium text-gray-800">Paket Tapak ({{ $booking->package?->name ?? 'Wisata Edukasi' }})</td>
                            <td class="py-2.5 text-center font-mono">{{ $booking->participant_count }}</td>
                            <td class="py-2.5 text-right font-mono">Rp {{ number_format($booking->participant_count > 0 ? (int)(max(0, $booking->subtotal_amount - $booking->addons_amount) / $booking->participant_count) : 0, 0, ',', '.') }}</td>
                            <td class="py-2.5 text-right font-mono font-bold">Rp {{ number_format(max(0, $booking->subtotal_amount - $booking->addons_amount), 0, ',', '.') }}</td>
                        </tr>
                        @if($booking->addons_amount > 0)
                        <tr>
                            <td class="py-2.5 font-medium text-gray-800">Layanan Tambahan (Add-ons)</td>
                            <td class="py-2.5 text-center font-mono">1</td>
                            <td class="py-2.5 text-right font-mono">Rp {{ number_format($booking->addons_amount, 0, ',', '.') }}</td>
                            <td class="py-2.5 text-right font-mono font-bold">Rp {{ number_format($booking->addons_amount, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                        @if($booking->insurance_amount > 0)
                        <tr>
                            <td class="py-2.5 font-medium text-gray-800">Premi Asuransi Siswa</td>
                            <td class="py-2.5 text-center font-mono">{{ $booking->participant_count }}</td>
                            <td class="py-2.5 text-right font-mono">Rp {{ number_format(setting('insurance_per_pax', 2000), 0, ',', '.') }}</td>
                            <td class="py-2.5 text-right font-mono font-bold">Rp {{ number_format($booking->insurance_amount, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                        @if($booking->platform_fee_amount > 0)
                        <tr>
                            <td class="py-2.5 font-medium text-gray-800">Administrasi & Verifikasi Dokumen</td>
                            <td class="py-2.5 text-center font-mono">1</td>
                            <td class="py-2.5 text-right font-mono">Rp {{ number_format($booking->platform_fee_amount, 0, ',', '.') }}</td>
                            <td class="py-2.5 text-right font-mono font-bold">Rp {{ number_format($booking->platform_fee_amount, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                        @if($booking->discount_amount > 0)
                        <tr class="text-emerald-700">
                            <td class="py-2.5 font-medium">Diskon Voucher</td>
                            <td class="py-2.5 text-center font-mono">1</td>
                            <td class="py-2.5 text-right font-mono">- Rp {{ number_format($booking->discount_amount, 0, ',', '.') }}</td>
                            <td class="py-2.5 text-right font-mono font-bold">- Rp {{ number_format($booking->discount_amount, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                        @endforelse
                        <tr class="font-bold text-gray-900 border-t border-gray-200">
                            <td colspan="3" class="pt-3 text-right">Total Transaksi:</td>
                            <td class="pt-3 text-right font-mono text-[#1F2B45] text-sm">Rp {{ number_format($booking->total_amount, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Dokumen Resmi Terkait -->
            <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs space-y-3">
                <h3 class="text-base font-bold text-[#111827]">Dokumen Resmi Terbit</h3>
                <div class="space-y-2">
                    @forelse($booking->documents as $doc)
                    <div class="p-3 rounded-2xl bg-stone-50 border border-stone-200/60 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[#1F2B45]">description</span>
                            <div>
                                <span class="font-bold text-gray-800 block">{{ $doc->title }}</span>
                                <span class="text-[10px] text-gray-400 font-mono">{{ $doc->document_number }} • Versi {{ $doc->version }}</span>
                            </div>
                        </div>
                        <a href="{{ route('admin.documents.preview', $doc->id) }}" target="_blank" class="text-xs font-bold text-[#1F2B45] hover:underline">
                            Preview PDF
                        </a>
                    </div>
                    @empty
                    <p class="text-xs text-gray-400">Belum ada dokumen yang di-generate untuk booking ini.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Right Side: Timeline & Riwayat Status -->
        <div class="space-y-6">
            <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs space-y-4">
                <h3 class="text-base font-bold text-[#111827]">Riwayat Perubahan Status</h3>
                <div class="relative border-l-2 border-stone-200 ml-3 space-y-4 text-xs">
                    @foreach($booking->statusHistories as $hist)
                    <div class="ml-4 relative">
                        <span class="absolute -left-[23px] top-1 w-3 h-3 rounded-full bg-[#1F2B45] border-2 border-white"></span>
                        <div class="font-bold text-gray-800 uppercase">{{ $hist->to_status->value ?? $hist->to_status }}</div>
                        <div class="text-[10px] text-gray-400 font-mono">{{ $hist->created_at->translatedFormat('d M Y, H:i') }} • {{ $hist->actor?->name ?? 'System' }}</div>
                        @if($hist->note)
                            <p class="text-gray-600 mt-0.5 text-[11px]">{{ $hist->note }}</p>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Cancel -->
    <div x-show="openCancelModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" style="display: none;">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl">
            <h3 class="text-base font-bold text-gray-900">Batalkan Reservasi #{{ $booking->booking_code }}</h3>
            <p class="text-xs text-gray-500">Slot ketersediaan akan otomatis dilepas kembali ke kalender.</p>

            <form action="{{ route('admin.bookings.updateStatus', $booking->id) }}" method="POST" class="space-y-3">
                @csrf
                <input type="hidden" name="action" value="cancel">

                <div>
                    <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Alasan Pembatalan *</label>
                    <textarea name="reason" required rows="2" placeholder="Sebutkan alasan pembatalan..."
                              class="w-full text-xs rounded-xl border-gray-200 focus:border-[#1F2B45] focus:ring-[#1F2B45]"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Override Persentase Refund (%)</label>
                    <input type="number" name="refund_percent" min="0" max="100" placeholder="Kosongkan untuk otomatis hitung kebijakan"
                           class="w-full text-xs rounded-xl border-gray-200 focus:border-[#1F2B45] focus:ring-[#1F2B45]">
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="openCancelModal = false" class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-xl">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 text-xs font-bold uppercase bg-rose-600 text-white rounded-2xl hover:bg-rose-700">
                        Konfirmasi Batalkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Reschedule -->
    <div x-show="openRescheduleModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" style="display: none;">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl">
            <h3 class="text-base font-bold text-gray-900">Jadwal Ulang Tanggal Kunjungan</h3>

            <form action="{{ route('admin.bookings.updateStatus', $booking->id) }}" method="POST" class="space-y-3">
                @csrf
                <input type="hidden" name="action" value="reschedule">

                @php
                    $maxReschedule = (int) setting('max_reschedule_per_booking', 1);
                    $usedReschedule = (int) ($booking->reschedule_count ?? 0);
                    $remainingReschedule = $maxReschedule === 0 ? 'Tanpa Batas' : max(0, $maxReschedule - $usedReschedule) . ' kali';
                    $cutoffDays = (int) setting('reschedule_cutoff_days', 3);
                @endphp
                <div class="p-3 bg-stone-50 rounded-xl border border-stone-200 text-xs space-y-1">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Sisa Kuota Reschedule:</span>
                        <span class="font-bold text-gray-800">{{ $remainingReschedule }}</span>
                    </div>
                    <div class="flex justify-between text-[11px]">
                        <span class="text-gray-500">Batas Waktu:</span>
                        <span class="text-gray-700">Maksimal H-{{ $cutoffDays }} sebelum tanggal kunjungan lama</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Tanggal Mulai Baru *</label>
                    <input type="date" name="new_date" required min="{{ now()->format('Y-m-d') }}"
                           class="w-full text-xs rounded-xl border-gray-200 focus:border-[#1F2B45] focus:ring-[#1F2B45]">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Alasan Penjadwalan Ulang *</label>
                    <textarea name="reschedule_reason" required rows="2" placeholder="Catatan alasan perubahan tanggal..."
                              class="w-full text-xs rounded-xl border-gray-200 focus:border-[#1F2B45] focus:ring-[#1F2B45]"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="openRescheduleModal = false" class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-xl">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 text-xs font-bold uppercase bg-[#1F2B45] text-white rounded-2xl hover:bg-[#16203A]">
                        Simpan Jadwal Baru
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
