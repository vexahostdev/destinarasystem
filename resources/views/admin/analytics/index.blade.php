@extends('layouts.admin')

@section('title', 'Analitik Bisnis & Reporting Makro')

@section('content')
<div class="space-y-8">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-[#1E1B1B]">Analitik Bisnis & Reporting Makro</h1>
            <p class="text-sm text-gray-500">Pemantauan funnel reservasi, pertumbuhan GMV, destinasi terlaris, dan retensi institusi</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.analytics.exportCsv', ['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]) }}"
               class="px-5 py-2.5 rounded-full border border-gray-300 text-xs font-bold uppercase tracking-wider text-gray-700 bg-white hover:bg-gray-50 flex items-center gap-2 shadow-xs transition-all">
                <span class="material-symbols-outlined text-[18px]">download</span>
                <span>Export CSV Analitik</span>
            </a>
        </div>
    </div>

    <!-- Filter Rentang Periode -->
    <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-xs">
        <form method="GET" action="{{ route('admin.analytics.index') }}" class="flex flex-wrap items-center gap-3">
            <span class="text-xs font-bold uppercase text-gray-700">Rentang Waktu:</span>
            <input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}"
                   class="text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
            <span class="text-xs text-gray-400">s/d</span>
            <input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}"
                   class="text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
            <button type="submit" class="bg-[#703A3A] text-white rounded-xl px-4 py-2 text-xs font-bold uppercase hover:bg-[#561D1E]">
                Terapkan
            </button>
            <span class="text-xs font-mono text-gray-400 ml-auto">{{ $startDate->format('d M Y') }} - {{ $endDate->format('d M Y') }}</span>
        </form>
    </div>

    <!-- 4 High Level KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-xs">
            <span class="text-xs font-semibold text-gray-400 block mb-1">Total GMV Periode</span>
            <span class="text-2xl font-extrabold text-[#703A3A] font-mono">
                Rp {{ number_format($gmv, 0, ',', '.') }}
            </span>
            <span class="text-[11px] text-gray-400 mt-2 block">Nilai reservasi berhasil</span>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-xs">
            <span class="text-xs font-semibold text-gray-400 block mb-1">Estimasi Revenue Platform</span>
            <span class="text-2xl font-extrabold text-[#2B3A4A] font-mono">
                Rp {{ number_format($platformRevenue, 0, ',', '.') }}
            </span>
            <span class="text-[11px] text-gray-400 mt-2 block">Komisi platform terakumulasi</span>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-xs">
            <span class="text-xs font-semibold text-gray-400 block mb-1">Waktu Booking &rarr; Bayar</span>
            <span class="text-2xl font-extrabold text-stone-800 font-mono">
                {{ $avgPayMinutes }} Menit
            </span>
            <span class="text-[11px] text-gray-400 mt-2 block">Rata-rata kecepatan pelunasan</span>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-xs">
            <span class="text-xs font-semibold text-gray-400 block mb-1">Repeat Order Rate</span>
            <span class="text-2xl font-extrabold text-emerald-700 font-mono">
                {{ $repeatOrderRate }}%
            </span>
            <span class="text-[11px] text-gray-400 mt-2 block">Institusi pesan &gt;1 kali</span>
        </div>
    </div>

    <!-- Funnel Konversi Reservasi (SPEC §14.4) -->
    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs space-y-4">
        <div>
            <h3 class="text-base font-bold text-[#1E1B1B]">Funnel Konversi Reservasi</h3>
            <p class="text-xs text-gray-400">Perjalanan pemesanan dari tahap draf s.d. kunjungan lapangan selesai</p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3">
            <div class="p-4 rounded-2xl bg-stone-50 border border-stone-200/60 text-center">
                <span class="text-[10px] uppercase font-bold text-gray-400 block">1. Dibuat</span>
                <span class="text-2xl font-extrabold text-gray-800 font-mono mt-1 block">{{ $funnel['created'] }}</span>
                <span class="text-[10px] text-gray-400 mt-1 block">Inquiry Masuk</span>
            </div>
            <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200/60 text-center">
                <span class="text-[10px] uppercase font-bold text-amber-800 block">2. Dibayar</span>
                <span class="text-2xl font-extrabold text-amber-900 font-mono mt-1 block">{{ $funnel['paid'] }}</span>
                <span class="text-[10px] text-amber-700 mt-1 block">Dana Diterima</span>
            </div>
            <div class="p-4 rounded-2xl bg-blue-50 border border-blue-200/60 text-center">
                <span class="text-[10px] uppercase font-bold text-blue-800 block">3. Dikonfirmasi</span>
                <span class="text-2xl font-extrabold text-blue-900 font-mono mt-1 block">{{ $funnel['confirmed'] }}</span>
                <span class="text-[10px] text-blue-700 mt-1 block">Disetujui Mitra</span>
            </div>
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200/60 text-center">
                <span class="text-[10px] uppercase font-bold text-emerald-800 block">4. Selesai</span>
                <span class="text-2xl font-extrabold text-emerald-900 font-mono mt-1 block">{{ $funnel['completed'] }}</span>
                <span class="text-[10px] text-emerald-700 mt-1 block">Selesai Tapak</span>
            </div>
            <div class="p-4 rounded-2xl bg-stone-100 border border-stone-200 text-center opacity-80">
                <span class="text-[10px] uppercase font-bold text-stone-600 block">Drop: Expired</span>
                <span class="text-2xl font-extrabold text-stone-700 font-mono mt-1 block">{{ $funnel['expired'] }}</span>
                <span class="text-[10px] text-stone-500 mt-1 block">Batas Waktu Habis</span>
            </div>
            <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-center opacity-80">
                <span class="text-[10px] uppercase font-bold text-rose-800 block">Drop: Batal</span>
                <span class="text-2xl font-extrabold text-rose-900 font-mono mt-1 block">{{ $funnel['cancelled'] }}</span>
                <span class="text-[10px] text-rose-600 mt-1 block">Dibatalkan</span>
            </div>
        </div>
    </div>

    <!-- 2 Column Breakdown -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Monthly Trends Table -->
        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs space-y-4">
            <h3 class="text-base font-bold text-[#1E1B1B]">Tren Nilai Transaksi Bulanan</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-gray-100 text-gray-400 uppercase font-semibold">
                            <th class="pb-3">Bulan</th>
                            <th class="pb-3 text-center">Reservasi</th>
                            <th class="pb-3 text-right">GMV (IDR)</th>
                            <th class="pb-3 text-right">Komisi (IDR)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($monthlyTrends as $t)
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 font-mono font-bold text-gray-800">{{ $t->month }}</td>
                            <td class="py-3 text-center font-mono font-bold text-gray-600">{{ $t->total_bookings }}</td>
                            <td class="py-3 text-right font-mono font-bold text-[#703A3A]">
                                Rp {{ number_format($t->monthly_gmv, 0, ',', '.') }}
                            </td>
                            <td class="py-3 text-right font-mono text-emerald-700 font-semibold">
                                Rp {{ number_format($t->monthly_rev, 0, ',', '.') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-gray-400">Belum ada riwayat transaksi.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Destinations -->
        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs space-y-4">
            <h3 class="text-base font-bold text-[#1E1B1B]">Destinasi Tapak Terpopuler</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-gray-100 text-gray-400 uppercase font-semibold">
                            <th class="pb-3">Destinasi</th>
                            <th class="pb-3 text-center">Selesai</th>
                            <th class="pb-3 text-right">Total Transaksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($topDestinations as $td)
                        <tr class="hover:bg-gray-50">
                            <td class="py-3">
                                <span class="font-bold text-gray-800 block">{{ $td->name }}</span>
                                <span class="text-[11px] text-gray-400">{{ $td->city }}</span>
                            </td>
                            <td class="py-3 text-center font-mono font-bold text-emerald-800">
                                {{ $td->completed_bookings }} Rombongan
                            </td>
                            <td class="py-3 text-right font-mono font-bold text-[#703A3A]">
                                Rp {{ number_format($td->total_gmv ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="py-6 text-center text-gray-400">Belum ada data destinasi selesai.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
