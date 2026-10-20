@extends('layouts.partner')

@section('title', 'Laporan Pendapatan & Payout')

@section('content')
<div class="space-y-8">

    <!-- Header & Aksi Pencairan -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-[#1F2937]/12">
        <div>
            <div class="flex items-center gap-2 text-xs text-[#1F2937]/60 mb-2">
                <a href="{{ route('mitra.dashboard') }}" class="hover:text-[#1F2B45]">Dashboard</a>
                <span>/</span>
                <span class="text-[#1F2B45] font-semibold">Laporan Keuangan & Payout</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#1F2937]">Rekonsiliasi Pendapatan & Pencairan Dana</h1>
            <p class="text-xs sm:text-sm text-[#1F2937]/70 mt-1">
                Catatan transparansi bagi hasil retribusi tapak, potongan komisi platform, dan transfer otomatis ke rekening pengelola.
            </p>
        </div>

        <!-- Status Penyaluran Payout -->
        <div class="flex items-center gap-2">
            <span class="px-4 py-2 bg-[#51634b]/10 text-[#51634b] border border-[#51634b]/20 text-xs font-bold uppercase tracking-wider flex items-center gap-1.5 rounded-full">
                <span class="material-symbols-outlined text-[16px]">verified</span>
                <span>Pencairan Otomatis via Batch Mingguan Admin</span>
            </span>
        </div>
    </div>

    <!-- 3 Plinth Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- Saldo Siap Ditarik -->
        <div class="rgs-card p-5 border-t-4 border-t-[#51634b]">
            <div class="text-[#1F2937]/60 text-xs font-medium mb-1">Saldo Tersedia Siap Cair</div>
            <div class="font-serif text-3xl font-bold text-[#51634b]">
                Rp {{ number_format($readyBalance, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-[#1F2937]/60 mt-1">Dari booking rombongan terverifikasi</div>
        </div>

        <!-- Total Dana Telah Dicairkan -->
        <div class="rgs-card p-5 border-t-4 border-t-[#1F2B45]">
            <div class="text-[#1F2937]/60 text-xs font-medium mb-1">Total Payout Diterima</div>
            <div class="font-serif text-3xl font-bold text-[#1F2B45]">
                Rp {{ number_format($totalTransferred, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-[#1F2937]/60 mt-1">Akumulasi transfer masuk rekening</div>
        </div>

        <!-- Rekening Bank Terdaftar -->
        <div class="rgs-card p-5 border-t-4 border-t-[#1F2937]/30 space-y-1">
            <div class="text-[#1F2937]/60 text-xs font-medium">Rekening Penampungan Payout</div>
            <div class="font-bold text-sm text-[#1F2937]">
                {{ $profile->bank_name ?? 'Belum dilengkapi' }}
            </div>
            <div class="font-mono text-xs text-[#1F2B45] font-bold">
                {{ $profile->bank_account_number ?? '' }}
            </div>
            <div class="text-[10px] text-[#1F2937]/60 uppercase">
                a.n. {{ $profile->bank_account_holder ?? ($user->name ?? 'Pengelola') }}
            </div>
        </div>
    </div>

    <!-- Grafik Bar Pendapatan Bulanan (CSS-only Bar Chart) -->
    <div class="rgs-card p-6 border-t-4 border-t-[#1F2B45] space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-[#1F2937]/10">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[#1F2B45] text-[20px]">bar_chart</span>
                <h2 class="font-serif text-lg font-bold text-[#1F2937]">Tren Pendapatan Tapak (5 Bulan Terakhir)</h2>
            </div>
            <span class="text-xs text-[#1F2937]/60">Satuan: Rupiah (Net Bersih)</span>
        </div>

        <div class="h-56 flex items-end justify-between gap-4 pt-6 px-4 bg-[#F4F6F9] border border-[#1F2937]/10">
            @php 
                $maxNet = collect($monthlyEarnings)->max('net');
                $maxVal = ($maxNet && $maxNet > 0) ? ($maxNet * 1.15) : 1000000; 
            @endphp
            @foreach($monthlyEarnings as $item)
                @php $heightPercent = round(($item['net'] / $maxVal) * 100); @endphp
                <div class="flex-1 flex flex-col items-center gap-2 h-full justify-end group">
                    <span class="text-[10px] font-mono font-bold text-[#1F2B45] opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                        Rp {{ number_format($item['net']/1000000, 1) }} jt
                    </span>
                    <div class="w-full max-w-[50px] bg-[#1F2B45] group-hover:bg-[#51634b] transition-all rounded-none shadow-xs" style="height: {{ $heightPercent }}%;"></div>
                    <span class="text-xs font-bold text-[#1F2937] uppercase mt-1">{{ $item['month'] }}</span>
                </div>
            @endforeach
        </div>

        @php
            $commPct = $profile->commission_percent_override ?? setting('default_commission_percent', 10);
            $partnerPct = 100 - $commPct;
        @endphp
        <div class="flex items-center justify-between text-[11px] text-[#1F2937]/70 pt-1">
            <span>Komisi Destinara: {{ $commPct }}% (Digunakan untuk biaya operasional platform & kurasi)</span>
            <span>Hak Pengelola: <strong>{{ $partnerPct }}% Bersih</strong></span>
        </div>
    </div>

    <!-- Tabel Riwayat Payout Lengkap -->
    <div class="rgs-card p-6 border-t-4 border-t-[#51634b] space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-[#1F2937]/10">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[#51634b] text-[20px]">history</span>
                <h2 class="font-serif text-lg font-bold text-[#1F2937]">Buku Kas Pencairan & Transfer Payout</h2>
            </div>
            <span class="text-xs text-[#51634b] font-bold uppercase">{{ $allPayouts->count() }} Catatan</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left border-collapse">
                <thead>
                    <tr class="border-b border-[#1F2937]/15 bg-[#F4F6F9] text-[11px] uppercase tracking-wider text-[#1F2937]/75">
                        <th class="p-3">ID Payout</th>
                        <th class="p-3">Tanggal Transfer</th>
                        <th class="p-3 text-right">Nilai Tiket (Kotor)</th>
                        <th class="p-3 text-right">Komisi ({{ $commPct }}%)</th>
                        <th class="p-3 text-right">Diterima Bersih</th>
                        <th class="p-3 text-center">Status</th>
                        <th class="p-3 text-right">Bukti Transfer</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#1F2937]/10">
                    @forelse($allPayouts as $payout)
                    <tr class="hover:bg-[#F4F6F9]/50 transition-colors">
                        <td class="p-3 font-mono font-bold text-[#1F2B45]">{{ $payout->payout_number }}</td>
                        <td class="p-3 text-[#1F2937]/70">
                            {{ $payout->transferred_at ? $payout->transferred_at->translatedFormat('d M Y, H:i') : 'Menunggu Penarikan' }}
                        </td>
                        <td class="p-3 text-right font-medium">Rp {{ number_format($payout->gross_amount, 0, ',', '.') }}</td>
                        <td class="p-3 text-right text-[#1F2937]/60">- Rp {{ number_format($payout->commission_amount, 0, ',', '.') }}</td>
                        <td class="p-3 text-right font-bold text-[#51634b]">Rp {{ number_format($payout->net_amount, 0, ',', '.') }}</td>
                        <td class="p-3 text-center">
                            @if($payout->status === \App\Enums\PayoutStatus::TRANSFERRED)
                                <span class="px-2.5 py-0.5 bg-[#51634b]/15 text-[#51634b] text-[10px] font-bold uppercase tracking-wider">
                                    Ditransfer
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 bg-[#b87a38]/15 text-[#b87a38] text-[10px] font-bold uppercase tracking-wider">
                                    Siap Tarik
                                </span>
                            @endif
                        </td>
                        <td class="p-3 text-right">
                            @if($payout->status === \App\Enums\PayoutStatus::TRANSFERRED)
                                <span class="text-[#51634b] font-bold text-[11px] flex items-center justify-end gap-1">
                                    <span class="material-symbols-outlined text-[14px]">receipt_long</span>
                                    <span>Bank Valid</span>
                                </span>
                            @else
                                <span class="text-[#1F2937]/40 text-[11px]">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-6 text-center text-xs text-[#1F2937]/60">Belum ada riwayat transaksi pencairan dana.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
