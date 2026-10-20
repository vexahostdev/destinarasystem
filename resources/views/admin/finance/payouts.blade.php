@extends('layouts.admin')

@section('title', 'Pencairan Dana Mitra (Payout)')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-[#111827]">Pencairan Dana Mitra (Payout)</h1>
            <p class="text-sm text-gray-500">Penyaluran bagi hasil pengelola tapak atas kunjungan rombongan yang telah selesai</p>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs" x-data="{ openProcessModal: false, openHoldModal: false, activePayout: null }">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-gray-100 text-gray-400 uppercase tracking-wider font-semibold">
                        <th class="pb-3">No. Payout / Booking</th>
                        <th class="pb-3">Mitra & Rekening Tujuan</th>
                        <th class="pb-3 text-right">Bruto</th>
                        <th class="pb-3 text-right">Komisi Platform</th>
                        <th class="pb-3 text-right">Hak Mitra (Net)</th>
                        <th class="pb-3 text-center">Status</th>
                        <th class="pb-3">Eligible Sejak</th>
                        <th class="pb-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($payouts as $p)
                    <tr class="hover:bg-gray-50/70 transition-colors">
                        <td class="py-3.5 pr-2 font-mono">
                            <span class="font-bold text-[#1F2B45] block">{{ $p->payout_number }}</span>
                            <a href="{{ route('admin.bookings.show', $p->booking->booking_code) }}" class="text-gray-500 hover:underline">
                                {{ $p->booking->booking_code }}
                            </a>
                        </td>
                        <td class="py-3.5 pr-2">
                            <span class="font-bold text-gray-800 block">{{ $p->partner?->name }}</span>
                            <span class="text-[11px] text-gray-500 font-mono">
                                {{ $p->bank_name }} - {{ $p->bank_account_number }} (a.n. {{ $p->bank_account_holder }})
                            </span>
                        </td>
                        <td class="py-3.5 pr-2 text-right font-mono text-gray-600">
                            Rp {{ number_format($p->gross_amount, 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 pr-2 text-right font-mono text-amber-700 font-semibold">
                            Rp {{ number_format($p->commission_amount, 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 pr-2 text-right font-mono font-bold text-emerald-800">
                            Rp {{ number_format($p->net_amount, 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 text-center">
                            <x-status-badge :enum="$p->status" />
                        </td>
                        <td class="py-3.5 text-gray-500 font-mono text-[11px]">
                            {{ $p->eligible_at?->format('d/m/Y') ?? '-' }}
                        </td>
                        <td class="py-3.5 text-right space-x-2">
                            @if($p->status === \App\Enums\PayoutStatus::PENDING)
                                @can('payouts.process')
                                <button type="button" @click="activePayout = {{ $p->id }}; openProcessModal = true"
                                        class="bg-[#1F2B45] hover:bg-[#16203A] text-white px-3 py-1 rounded-full text-[10px] font-bold uppercase">
                                    Transfer
                                </button>
                                <button type="button" @click="activePayout = {{ $p->id }}; openHoldModal = true"
                                        class="border border-amber-300 text-amber-800 hover:bg-amber-50 px-3 py-1 rounded-full text-[10px] font-bold uppercase">
                                    Tahan
                                </button>
                                @endcan
                            @elseif($p->proof_file)
                                <a href="{{ asset('storage/' . $p->proof_file) }}" target="_blank" class="text-xs text-[#1F2B45] hover:underline font-semibold">
                                    Bukti Transfer
                                </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-8 text-center text-gray-400">Tidak ada data payout mitra saat ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 pt-4 border-t border-gray-100">
            {{ $payouts->links() }}
        </div>

        <!-- Transfer Process Modal -->
        <div x-show="openProcessModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" style="display: none;">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl">
                <h3 class="text-base font-bold text-gray-900">Konfirmasi Penyaluran Payout</h3>
                <p class="text-xs text-gray-500">Unggah bukti transfer pencairan dana ke rekening mitra pengelola.</p>

                <form action="{{ route('admin.finance.processPayout') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <input type="hidden" name="payout_ids[]" :value="activePayout">

                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Bukti Transfer (JPG/PNG/PDF) *</label>
                        <input type="file" name="proof" required accept="image/*,application/pdf"
                               class="w-full text-xs file:mr-4 file:py-2 file:px-4 file:rounded-2xl file:border-0 file:text-xs file:font-semibold file:bg-stone-100 file:text-stone-700 hover:file:bg-stone-200">
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="openProcessModal = false" class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-xl">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold uppercase bg-[#1F2B45] text-white rounded-2xl hover:bg-[#16203A]">
                            Selesaikan Transfer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Hold Modal -->
        <div x-show="openHoldModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" style="display: none;">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl">
                <h3 class="text-base font-bold text-gray-900">Tahan Penyaluran Payout</h3>

                <form :action="'/admin/keuangan/payout/' + activePayout + '/hold'" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Alasan Penahanan Dana *</label>
                        <textarea name="reason" required rows="3" placeholder="Sebutkan kendala atau alasan penahanan dana..."
                                  class="w-full text-xs rounded-xl border-gray-200 focus:border-[#1F2B45] focus:ring-[#1F2B45]"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="openHoldModal = false" class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-xl">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold uppercase bg-amber-600 text-white rounded-2xl hover:bg-amber-700">
                            Tahan Payout
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
