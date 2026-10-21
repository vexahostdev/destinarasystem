@extends('layouts.admin')

@section('title', 'Kelola Voucher Diskon')

@section('content')
<div class="space-y-6" x-data="{ openCreateModal: false }">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-[#111827]">Voucher & Kupon Promo</h1>
            <p class="text-sm text-gray-500">Kelola kode diskon promosi institusi dan statistik pemakaian kupon</p>
        </div>
        <button type="button" @click="openCreateModal = true"
                class="bg-[#1F2B45] hover:bg-[#16203A] text-white px-5 py-2.5 rounded-2xl text-xs font-bold uppercase tracking-wider flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]">add</span>
            <span>Buat Voucher Baru</span>
        </button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-gray-100 text-gray-400 uppercase font-semibold">
                        <th class="pb-3">Kode Kupon</th>
                        <th class="pb-3">Nama Promo</th>
                        <th class="pb-3">Potongan</th>
                        <th class="pb-3 text-center">Kuota & Terpakai</th>
                        <th class="pb-3">Masa Berlaku</th>
                        <th class="pb-3 text-center">Status</th>
                        <th class="pb-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($vouchers as $v)
                    <tr class="hover:bg-gray-50/70 transition-colors">
                        <td class="py-3.5 pr-2 font-mono font-extrabold text-[#1F2B45] text-sm">
                            {{ $v->code }}
                        </td>
                        <td class="py-3.5 pr-2">
                            <span class="font-bold text-gray-800 block">{{ $v->name }}</span>
                            @if($v->min_transaction > 0)
                                <span class="text-[11px] text-gray-400">Min. Transaksi: Rp {{ number_format($v->min_transaction, 0, ',', '.') }}</span>
                            @endif
                        </td>
                        <td class="py-3.5 pr-2 font-mono font-bold text-gray-800">
                            @if($v->type === 'percent')
                                {{ $v->value }}%
                                @if($v->max_discount)
                                    <span class="text-[10px] text-gray-400 block font-normal">(Maks Rp {{ number_format($v->max_discount, 0, ',', '.') }})</span>
                                @endif
                            @else
                                Rp {{ number_format($v->value, 0, ',', '.') }}
                            @endif
                        </td>
                        <td class="py-3.5 text-center font-mono">
                            <span class="font-bold text-gray-800">{{ $v->used_count }}</span>
                            <span class="text-gray-400">/ {{ $v->quota ?? '∞' }}</span>
                        </td>
                        <td class="py-3.5 text-gray-500 font-mono text-[11px]">
                            @if($v->starts_at && $v->ends_at)
                                {{ $v->starts_at->format('d/m/y') }} - {{ $v->ends_at->format('d/m/y') }}
                            @else
                                Tanpa batas
                            @endif
                        </td>
                        <td class="py-3.5 text-center">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $v->is_active ? 'bg-emerald-50 text-emerald-800' : 'bg-slate-100 text-slate-500' }}">
                                {{ $v->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="py-3.5 text-right">
                            <form action="{{ route('admin.vouchers.delete', $v->id) }}" method="POST" onsubmit="return confirm('Hapus voucher {{ $v->code }}?')" class="inline">
                                @csrf
                                <button type="submit" class="text-xs text-rose-600 hover:text-rose-800 font-semibold">
                                    Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-gray-400">Belum ada voucher promosi.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 pt-4 border-t border-gray-100">
            {{ $vouchers->links() }}
        </div>
    </div>

    <!-- Modal Create Voucher -->
    <div x-show="openCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" style="display: none;">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-xl">
            <h3 class="text-base font-bold text-gray-900">Buat Voucher Promo Baru</h3>

            <form action="{{ route('admin.vouchers.store') }}" method="POST" class="space-y-3">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Kode Voucher *</label>
                        <input type="text" name="code" required placeholder="MISAL: STUDYTOUR26"
                               class="w-full text-xs font-mono uppercase rounded-xl border-gray-200 focus:border-[#1F2B45] focus:ring-[#1F2B45]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Nama Promo *</label>
                        <input type="text" name="name" required placeholder="Diskon Awal Tahun"
                               class="w-full text-xs rounded-xl border-gray-200 focus:border-[#1F2B45] focus:ring-[#1F2B45]">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Tipe Diskon *</label>
                        <select name="type" required class="w-full text-xs rounded-xl border-gray-200 focus:border-[#1F2B45] focus:ring-[#1F2B45]">
                            <option value="fixed">Nominal Tetap (Rp)</option>
                            <option value="percent">Persentase (%)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Nilai Diskon *</label>
                        <input type="number" name="value" required min="1" placeholder="50000 atau 10"
                               class="w-full text-xs rounded-xl border-gray-200 focus:border-[#1F2B45] focus:ring-[#1F2B45]">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Min. Transaksi (Rp)</label>
                        <input type="number" name="min_transaction" placeholder="0"
                               class="w-full text-xs rounded-xl border-gray-200 focus:border-[#1F2B45] focus:ring-[#1F2B45]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Maks. Potongan (Rp)</label>
                        <input type="number" name="max_discount" placeholder="Khusus tipe %"
                               class="w-full text-xs rounded-xl border-gray-200 focus:border-[#1F2B45] focus:ring-[#1F2B45]">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Kuota Total</label>
                        <input type="number" name="quota" placeholder="Kosongkan jika unli"
                               class="w-full text-xs rounded-xl border-gray-200 focus:border-[#1F2B45] focus:ring-[#1F2B45]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Batas Per Pengguna</label>
                        <input type="number" name="per_user_limit" value="1" min="1"
                               class="w-full text-xs rounded-xl border-gray-200 focus:border-[#1F2B45] focus:ring-[#1F2B45]">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-gray-100">
                    <button type="button" @click="openCreateModal = false" class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-xl">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 text-xs font-bold uppercase bg-[#1F2B45] text-white rounded-2xl hover:bg-[#16203A]">
                        Terbitkan Voucher
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
