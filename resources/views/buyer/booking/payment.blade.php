@extends('layouts.buyer')

@section('title', 'Kasir & Pembayaran Pesanan #' . $booking->booking_code)

@section('content')
<div x-data="{
    gateway: '{{ $payment->gateway ?? 'xendit' }}',
    countdown: '',
    expiresAt: new Date('{{ $booking->expires_at ? $booking->expires_at->toIso8601String() : now()->addHours(24)->toIso8601String() }}').getTime(),
    updateTimer() {
        const now = new Date().getTime();
        const diff = this.expiresAt - now;
        if (diff <= 0) {
            this.countdown = 'Kedaluwarsa';
            return;
        }
        const hours = Math.floor(diff / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);
        this.countdown = `${hours.toString().padStart(2, '0')} : ${minutes.toString().padStart(2, '0')} : ${seconds.toString().padStart(2, '0')}`;
    },
    init() {
        this.updateTimer();
        setInterval(() => this.updateTimer(), 1000);
    }
}" class="space-y-6 max-w-4xl mx-auto">

    <!-- Header Breadcrumb & Checkout -->
    <div class="pb-4 border-b border-slate-200">
        <div class="flex items-center gap-2 text-xs text-slate-500 mb-2">
            <a href="{{ route('buyer.dashboard') }}" class="hover:text-[#8C5151]">Dashboard</a>
            <span>/</span>
            <a href="{{ route('buyer.booking.show', $booking->booking_code) }}" class="hover:text-[#8C5151]">Pesanan #{{ $booking->booking_code }}</a>
            <span>/</span>
            <span class="text-[#8C5151] font-semibold">Kasir & Pembayaran</span>
        </div>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h1 class="font-serif text-2xl sm:text-3xl font-bold text-slate-800">Penyelesaian Pembayaran Resmi</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                    Transaksi aman dan sah dengan kwitansi resmi untuk akuntabilitas pertanggungjawaban BOSP.
                </p>
            </div>

            <!-- Countdown Timer Pembayaran -->
            <div class="px-4 py-2 bg-amber-50 border border-amber-200 text-amber-800 rounded-2xl text-xs font-semibold flex items-center gap-2.5 shadow-xs">
                <span class="material-symbols-outlined text-[18px]">timer</span>
                <div>
                    <span class="text-[10px] uppercase text-amber-600 block leading-none">Batas Waktu Bayar:</span>
                    <span class="font-mono font-bold text-sm text-slate-900" x-text="countdown">Memuat...</span>
                </div>
            </div>
        </div>
    </div>

    @if(session('info'))
        <div class="p-4 bg-sky-50 border border-sky-200 text-sky-800 rounded-2xl text-xs font-medium">
            {{ session('info') }}
        </div>
    @endif

    <!-- Ringkasan Nilai Tagihan -->
    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-[#8C5151] block">Destinasi Rombongan:</span>
                <h2 class="font-serif text-xl font-bold text-slate-900">{{ $booking->destination->name }}</h2>
                <div class="text-xs text-slate-500 mt-0.5">
                    {{ $booking->participant_count }} Siswa • {{ \Carbon\Carbon::parse($booking->planned_date_start)->translatedFormat('d M Y') }}
                </div>
            </div>

            <div class="text-left sm:text-right">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Total Tagihan (Invoice):</span>
                <div class="text-2xl font-black text-[#8C5151]">
                    Rp {{ number_format($booking->total_amount, 0, ',', '.') }}
                </div>
                <span class="text-[10px] text-slate-400 font-mono">Kode: {{ $booking->booking_code }}</span>
            </div>
        </div>

        <!-- Rincian Biaya -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-4 text-xs">
            <div class="p-3 bg-slate-50 rounded-xl">
                <span class="text-slate-500 block text-[11px]">Subtotal Paket:</span>
                <span class="font-bold text-slate-800">Rp {{ number_format($booking->subtotal_amount, 0, ',', '.') }}</span>
            </div>
            <div class="p-3 bg-slate-50 rounded-xl">
                <span class="text-slate-500 block text-[11px]">Asuransi Lapangan:</span>
                <span class="font-bold text-slate-800">Rp {{ number_format($booking->insurance_amount, 0, ',', '.') }}</span>
            </div>
            <div class="p-3 bg-slate-50 rounded-xl">
                <span class="text-slate-500 block text-[11px]">Biaya Layanan BOS:</span>
                <span class="font-bold text-slate-800">Rp {{ number_format($booking->platform_fee_amount, 0, ',', '.') }}</span>
            </div>
            <div class="p-3 bg-slate-50 rounded-xl">
                <span class="text-slate-500 block text-[11px]">Diskon:</span>
                <span class="font-bold text-emerald-600">-Rp {{ number_format($booking->discount_amount, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <!-- Pilihan Jalur Pembayaran Resmi -->
    <form action="{{ route('buyer.booking.pay', $booking->booking_code) }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-sm space-y-6">
        @csrf

        <div>
            <h3 class="text-base font-bold text-slate-900 mb-1">Pilih Metode Pembayaran</h3>
            <p class="text-xs text-slate-500">Pilih gateway online otomatis atau transfer manual ke rekening resmi instansi Destinara.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            
            <!-- Xendit Online -->
            <label class="cursor-pointer p-4 rounded-2xl border-2 transition flex flex-col justify-between"
                   :class="gateway === 'xendit' ? 'border-[#8C5151] bg-[#8C5151]/5 shadow-sm' : 'border-slate-200 hover:border-slate-300'">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="font-bold text-sm text-slate-900 block">Xendit Invoice</span>
                        <span class="text-xs text-slate-500">VA Otomatis, QRIS, e-Wallet</span>
                    </div>
                    <input type="radio" name="gateway" value="xendit" x-model="gateway" class="text-[#8C5151] focus:ring-[#8C5151]">
                </div>
                <div class="mt-4 flex items-center gap-1.5 text-[11px] text-emerald-700 font-semibold">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                    <span>Verifikasi Otomatis Seketika</span>
                </div>
            </label>

            <!-- Midtrans Snap -->
            <label class="cursor-pointer p-4 rounded-2xl border-2 transition flex flex-col justify-between"
                   :class="gateway === 'midtrans' ? 'border-[#8C5151] bg-[#8C5151]/5 shadow-sm' : 'border-slate-200 hover:border-slate-300'">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="font-bold text-sm text-slate-900 block">Midtrans Snap</span>
                        <span class="text-xs text-slate-500">Transfer Bank & Kartu</span>
                    </div>
                    <input type="radio" name="gateway" value="midtrans" x-model="gateway" class="text-[#8C5151] focus:ring-[#8C5151]">
                </div>
                <div class="mt-4 flex items-center gap-1.5 text-[11px] text-emerald-700 font-semibold">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                    <span>Verifikasi Otomatis Seketika</span>
                </div>
            </label>

            <!-- Transfer Manual Bank Destinara -->
            <label class="cursor-pointer p-4 rounded-2xl border-2 transition flex flex-col justify-between"
                   :class="gateway === 'manual_transfer' ? 'border-[#8C5151] bg-[#8C5151]/5 shadow-sm' : 'border-slate-200 hover:border-slate-300'">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="font-bold text-sm text-slate-900 block">Transfer Manual</span>
                        <span class="text-xs text-slate-500">Rekening Resmi PT Destinara</span>
                    </div>
                    <input type="radio" name="gateway" value="manual_transfer" x-model="gateway" class="text-[#8C5151] focus:ring-[#8C5151]">
                </div>
                <div class="mt-4 flex items-center gap-1.5 text-[11px] text-amber-700 font-semibold">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                    <span>Unggah Bukti Transfer</span>
                </div>
            </label>

        </div>

        <!-- Detail Rekening Manual Transfer -->
        <div x-show="gateway === 'manual_transfer'" x-cloak class="p-5 bg-slate-50 rounded-2xl border border-slate-200 space-y-4">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Rekening Resmi Destinara</h4>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($banks as $bank)
                    <div class="p-3.5 bg-white rounded-xl border border-slate-200 text-xs">
                        <span class="font-bold text-slate-900 block">{{ $bank->name }} ({{ $bank->code }})</span>
                        <span class="font-mono text-sm font-black text-[#8C5151] block my-1">{{ $bank->account_number }}</span>
                        <span class="text-slate-500 text-[11px]">a.n. {{ $bank->account_holder }}</span>
                    </div>
                @endforeach
            </div>

            <div class="pt-2">
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Unggah Bukti Transfer (JPG/PNG/PDF, maks 5MB) <span class="text-rose-500">*</span></label>
                <input type="file" name="proof_file" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-[#8C5151] file:text-white hover:file:bg-[#734040]">
                @error('proof_file') <p class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
            <a href="{{ route('buyer.booking.show', $booking->booking_code) }}" class="text-xs text-slate-500 hover:underline font-medium">
                Kembali ke Ringkasan Dossier
            </a>

            <button type="submit" class="w-full sm:w-auto px-8 py-3 bg-[#8C5151] hover:bg-[#734040] text-white rounded-full text-xs font-bold uppercase tracking-wider transition shadow-md hover:shadow-lg flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[16px]">lock</span>
                <span>Lanjutkan Pembayaran Resmi</span>
            </button>
        </div>

    </form>

</div>
@endsection
