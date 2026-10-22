@extends('layouts.admin')

@section('title', 'Rekonsiliasi Keuangan Platform')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-[#111827]">Rekonsiliasi Keuangan</h1>
            <p class="text-sm text-gray-500">Laporan penutupan buku transaksi riil, hak bagi hasil mitra, dan fee gateway per periode</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.finance.exportCsv', ['period' => $period]) }}" class="px-4 py-2 rounded-full border border-gray-300 text-xs font-bold uppercase text-gray-700 bg-white hover:bg-gray-50 flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">download</span>
                <span>Export CSV</span>
            </a>
            @if(!($recon['is_closed'] ?? false))
                @can('reconciliation.close')
                <form action="{{ route('admin.finance.closePeriod') }}" method="POST" onsubmit="return confirm('Tutup buku periode {{ $period }}? Setelah ditutup, data historis periode ini akan terkunci secara permanen.')">
                    @csrf
                    <input type="hidden" name="period" value="{{ $period }}">
                    <button type="submit" class="bg-[#1F2B45] hover:bg-[#16203A] text-white px-5 py-2 rounded-2xl text-xs font-bold uppercase tracking-wider flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">lock</span>
                        <span>Tutup Buku Periode</span>
                    </button>
                </form>
                @endcan
            @else
                <span class="px-3.5 py-1.5 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold uppercase flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">lock</span>
                    <span>Telah Ditutup Buku</span>
                </span>
            @endif
        </div>
    </div>

    <!-- Period Selector -->
    <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-xs flex items-center justify-between">
        <form method="GET" action="{{ route('admin.finance.reconciliation') }}" class="flex items-center gap-3">
            <label class="text-xs font-bold uppercase text-gray-700">Pilih Periode Bulan:</label>
            <input type="month" name="period" value="{{ $period }}"
                   class="text-xs rounded-xl border-gray-200 focus:border-[#1F2B45] focus:ring-[#1F2B45]">
            <button type="submit" class="bg-[#1F2B45] text-white rounded-2xl px-4 py-2 text-xs font-bold uppercase hover:bg-[#16203A]">
                Tampilkan
            </button>
        </form>
        <span class="text-xs font-mono text-gray-400">Periode Aktif: {{ \Carbon\Carbon::parse($period . '-01')->translatedFormat('F Y') }}</span>
    </div>

    <!-- 4 Summary Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-xs">
            <span class="text-xs font-semibold text-gray-400 block mb-1">Total GMV (Bruto)</span>
            <span class="text-2xl font-extrabold text-[#111827] font-mono">
                Rp {{ number_format($recon['total_gmv'] ?? 0, 0, ',', '.') }}
            </span>
            <span class="text-[11px] text-gray-400 mt-2 block">{{ $recon['total_bookings'] ?? 0 }} reservasi terbayar</span>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-xs">
            <span class="text-xs font-semibold text-gray-400 block mb-1">Hak Mitra Pengelola</span>
            <span class="text-2xl font-extrabold text-emerald-700 font-mono">
                Rp {{ number_format($recon['partner_share'] ?? 0, 0, ',', '.') }}
            </span>
            <span class="text-[11px] text-gray-400 mt-2 block">Porsi 90% bagi hasil tapak</span>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-xs">
            <span class="text-xs font-semibold text-gray-400 block mb-1">Pendapatan Platform</span>
            <span class="text-2xl font-extrabold text-[#1F2B45] font-mono">
                Rp {{ number_format($recon['platform_commission'] ?? 0, 0, ',', '.') }}
            </span>
            <span class="text-[11px] text-gray-400 mt-2 block">Komisi 10% platform</span>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-xs">
            <span class="text-xs font-semibold text-gray-400 block mb-1">Estimasi Biaya Gateway</span>
            <span class="text-2xl font-extrabold text-slate-700 font-mono">
                Rp {{ number_format($recon['gateway_fees'] ?? 0, 0, ',', '.') }}
            </span>
            <span class="text-[11px] text-gray-400 mt-2 block">Biaya processing payment</span>
        </div>
    </div>

    <!-- Riwayat Penutupan Buku -->
    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs">
        <h3 class="text-base font-bold text-[#111827] mb-4">Arsip Periode Tutup Buku</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-gray-100 text-gray-400 uppercase tracking-wider font-semibold">
                        <th class="pb-3">Periode</th>
                        <th class="pb-3 text-right">Total Transaksi (IDR)</th>
                        <th class="pb-3 text-right">Hak Mitra (IDR)</th>
                        <th class="pb-3 text-right">Komisi Platform (IDR)</th>
                        <th class="pb-3">Ditutup Oleh</th>
                        <th class="pb-3">Waktu Tutup Buku</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($allReconciliations as $hist)
                    <tr class="hover:bg-gray-50/70 transition-colors">
                        <td class="py-3 font-mono font-bold text-[#1F2B45]">
                            {{ $hist->period }}
                        </td>
                        <td class="py-3 text-right font-mono font-bold text-gray-800">
                            Rp {{ number_format($hist->total_gmv, 0, ',', '.') }}
                        </td>
                        <td class="py-3 text-right font-mono text-emerald-700 font-semibold">
                            Rp {{ number_format($hist->total_partner_payout, 0, ',', '.') }}
                        </td>
                        <td class="py-3 text-right font-mono text-[#1F2B45] font-bold">
                            Rp {{ number_format($hist->total_commission, 0, ',', '.') }}
                        </td>
                        <td class="py-3 text-gray-700">
                            {{ $hist->closedBy?->name ?? 'System' }}
                        </td>
                        <td class="py-3 font-mono text-gray-500">
                            {{ $hist->closed_at?->format('d/m/Y H:i') ?? '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-6 text-center text-gray-400">Belum ada periode yang ditutup buku.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
