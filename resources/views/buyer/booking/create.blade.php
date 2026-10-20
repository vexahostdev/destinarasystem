@extends('layouts.buyer')

@section('title', 'Form Pengajuan Booking Rombongan — Destinara')

@section('content')
<div class="space-y-6">

    <!-- Header Breadcrumb & Judul -->
    <div class="pb-4 border-b border-slate-200">
        <div class="flex items-center gap-2 text-xs text-slate-500 mb-2">
            <a href="{{ route('buyer.dashboard') }}" class="hover:text-[#8C5151]">Dashboard</a>
            <span>/</span>
            <span class="text-[#8C5151] font-semibold">Pengajuan Rombongan Baru</span>
        </div>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h1 class="font-serif text-2xl sm:text-3xl font-bold text-slate-800">Formulir Pengajuan Rombongan Belajar</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                    Lengkapi spesifikasi ekskursi agar mitra tapak dan tim Destinara dapat mempersiapkan izin adat, akomodasi, dan pemandu lokal.
                </p>
            </div>
            @if(isset($reorderData) && $reorderData)
            <div class="px-3.5 py-1.5 bg-[#51634b]/10 border border-[#51634b]/30 text-[#51634b] text-xs font-semibold rounded-full flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px]">repeat</span>
                <span>Pesan Ulang dari Pesanan #{{ $reorderData->booking_code }}</span>
            </div>
            @endif
        </div>
    </div>

    <!-- Stepper Tracker Visual -->
    <div class="grid grid-cols-3 border border-slate-200 bg-white rounded-2xl overflow-hidden text-xs shadow-xs">
        <div class="p-3 sm:p-4 border-r border-slate-200 bg-[#8C5151]/10 flex items-center gap-2.5">
            <span class="w-6 h-6 rounded-full bg-[#8C5151] text-white flex items-center justify-center font-bold text-xs shrink-0">1</span>
            <div>
                <span class="font-bold text-[#8C5151] block">Pilih Paket & Tanggal</span>
                <span class="text-[10px] text-slate-500 hidden sm:inline">Konfirmasi kuota & live slot</span>
            </div>
        </div>
        <div class="p-3 sm:p-4 border-r border-slate-200 flex items-center gap-2.5 text-slate-400">
            <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center font-bold text-xs shrink-0">2</span>
            <div>
                <span class="font-bold text-slate-700 block">Pembayaran Resmi</span>
                <span class="text-[10px] text-slate-400 hidden sm:inline">Invoice & VA terverifikasi</span>
            </div>
        </div>
        <div class="p-3 sm:p-4 flex items-center gap-2.5 text-slate-400">
            <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center font-bold text-xs shrink-0">3</span>
            <div>
                <span class="font-bold text-slate-700 block">Konfirmasi Mitra & Tiket</span>
                <span class="text-[10px] text-slate-400 hidden sm:inline">e-Voucher, QR & Surat Izin</span>
            </div>
        </div>
    </div>

    <!-- Mount Livewire Booking Form -->
    <livewire:buyer.booking-form 
        :destination="$selectedDestination" 
        :preselectedPackage="$reorderData?->package?->slug ?? null" />

</div>
@endsection
