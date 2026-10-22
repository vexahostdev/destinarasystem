@extends('layouts.admin')

@section('title', 'Manajemen Pengembalian Dana (Refund)')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-extrabold text-[#111827]">Pengembalian Dana (Refund)</h1>
        <p class="text-sm text-gray-500">Antrean dan proses eksekusi refund pembatalan reservasi sesuai kebijakan</p>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs" x-data="{ openProcessModal: false, openRejectModal: false, activeRefund: null }">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-gray-100 text-gray-400 uppercase tracking-wider font-semibold">
                        <th class="pb-3">ID / Booking</th>
                        <th class="pb-3">Pemesan / Rekening Tujuan</th>
                        <th class="pb-3 text-right">Nominal Refund</th>
                        <th class="pb-3 text-center">Persentase</th>
                        <th class="pb-3 text-center">Status</th>
                        <th class="pb-3">Alasan</th>
                        <th class="pb-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($refunds as $r)
                    <tr class="hover:bg-gray-50/70 transition-colors">
                        <td class="py-3.5 pr-2 font-mono">
                            <span class="font-bold text-[#1F2B45] block">#REF-{{ $r->id }}</span>
                            <a href="{{ route('admin.bookings.show', $r->booking->booking_code) }}" class="text-gray-500 hover:underline">
                                {{ $r->booking->booking_code }}
                            </a>
                        </td>
                        <td class="py-3.5 pr-2">
                            <span class="font-bold text-gray-800 block">{{ $r->buyer_account_holder ?? $r->booking->user?->name }}</span>
                            <span class="text-[11px] text-gray-500 font-mono">
                                {{ $r->buyerBank?->name ?? 'Bank Tujuan' }} - {{ $r->buyer_account_number ?? '-' }}
                            </span>
                        </td>
                        <td class="py-3.5 pr-2 text-right font-mono font-bold text-rose-800">
                            Rp {{ number_format($r->amount, 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 text-center font-mono font-bold text-gray-700">
                            {{ $r->percent }}%
                        </td>
                        <td class="py-3.5 text-center">
                            <x-status-badge :enum="$r->status" />
                        </td>
                        <td class="py-3.5 pr-2 text-gray-600 max-w-xs truncate" title="{{ $r->reason }}">
                            {{ $r->reason ?? '-' }}
                        </td>
                        <td class="py-3.5 text-right space-x-2">
                            @if($r->status === \App\Enums\RefundStatus::PENDING)
                                @can('refunds.process')
                                <button type="button" @click="activeRefund = {{ $r->id }}; openProcessModal = true"
                                        class="bg-[#1F2B45] hover:bg-[#16203A] text-white px-3 py-1 rounded-full text-[10px] font-bold uppercase">
                                    Proses
                                </button>
                                <button type="button" @click="activeRefund = {{ $r->id }}; openRejectModal = true"
                                        class="border border-rose-300 text-rose-700 hover:bg-rose-50 px-3 py-1 rounded-full text-[10px] font-bold uppercase">
                                    Tolak
                                </button>
                                @endcan
                            @elseif($r->proof_file)
                                <a href="{{ asset('storage/' . $r->proof_file) }}" target="_blank" class="text-xs text-[#1F2B45] hover:underline font-semibold">
                                    Lihat Bukti
                                </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-gray-400">Tidak ada pengajuan refund saat ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 pt-4 border-t border-gray-100">
            {{ $refunds->links() }}
        </div>

        <!-- Process Modal -->
        <div x-show="openProcessModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" style="display: none;">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl">
                <h3 class="text-base font-bold text-gray-900">Konfirmasi Eksekusi Refund</h3>
                <p class="text-xs text-gray-500">Unggah bukti transfer pengembalian dana ke rekening pemesan.</p>

                <form :action="'/admin/keuangan/refund/' + activeRefund + '/proses'" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Bukti Transfer (JPG/PNG/PDF) *</label>
                        <input type="file" name="proof_file" required accept="image/*,application/pdf"
                               class="w-full text-xs file:mr-4 file:py-2 file:px-4 file:rounded-2xl file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                        @error('proof_file')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Catatan Tambahan (Opsional)</label>
                        <textarea name="note" rows="2" placeholder="Catatan internal penyelesaian refund..."
                                  class="w-full text-xs rounded-xl border-gray-200 focus:border-[#1F2B45] focus:ring-[#1F2B45]"></textarea>
                        @error('note')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="openProcessModal = false" class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-xl">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold uppercase bg-[#1F2B45] text-white rounded-2xl hover:bg-[#16203A]">
                            Selesaikan Refund
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Reject Modal -->
        <div x-show="openRejectModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" style="display: none;">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl">
                <h3 class="text-base font-bold text-gray-900">Tolak Pengajuan Refund</h3>

                <form :action="'/admin/keuangan/refund/' + activeRefund + '/tolak'" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Alasan Penolakan *</label>
                        <textarea name="reason" required rows="3" placeholder="Jelaskan alasan penolakan pengembalian dana..."
                                  class="w-full text-xs rounded-xl border-gray-200 focus:border-[#1F2B45] focus:ring-[#1F2B45]"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="openRejectModal = false" class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-xl">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold uppercase bg-rose-600 text-white rounded-2xl hover:bg-rose-700">
                            Tolak Refund
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
