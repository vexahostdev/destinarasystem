@extends('layouts.admin')

@section('title', 'Manajemen Pembayaran')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-[#1E1B1B]">Log Pembayaran</h1>
            <p class="text-sm text-gray-500">Daftar invoice transaksi gateway & verifikasi transfer bank manual</p>
        </div>
    </div>

    <!-- Filter -->
    <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-xs">
        <form method="GET" action="{{ route('admin.finance.payments') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari external ID atau invoice..."
                       class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
            </div>
            <div>
                <select name="status" class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                    <option value="">Semua Status Pembayaran</option>
                    @foreach(\App\Enums\PaymentStatus::cases() as $st)
                        <option value="{{ $st->value }}" {{ request('status') === $st->value ? 'selected' : '' }}>
                            {{ $st->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="w-full bg-[#703A3A] text-white rounded-xl py-2 text-xs font-bold uppercase hover:bg-[#561D1E] transition-all">
                    Filter
                </button>
                <a href="{{ route('admin.finance.payments') }}" class="px-3 py-2 text-xs text-gray-500 hover:text-gray-700 border border-gray-200 rounded-xl">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-gray-100 text-gray-400 uppercase tracking-wider font-semibold">
                        <th class="pb-3">Kode / External ID</th>
                        <th class="pb-3">Booking</th>
                        <th class="pb-3">Gateway & Metode</th>
                        <th class="pb-3 text-right">Nominal</th>
                        <th class="pb-3 text-center">Status</th>
                        <th class="pb-3">Waktu Bayar / Expired</th>
                        <th class="pb-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($payments as $p)
                    <tr class="hover:bg-gray-50/70 transition-colors">
                        <td class="py-3.5 pr-2 font-mono font-bold text-gray-800">
                            {{ $p->external_id }}
                            @if($p->gateway_reference)
                                <span class="block text-[10px] text-gray-400 font-normal">Ref: {{ $p->gateway_reference }}</span>
                            @endif
                        </td>
                        <td class="py-3.5 pr-2">
                            <a href="{{ route('admin.bookings.show', $p->booking->booking_code) }}" class="text-[#703A3A] font-bold hover:underline font-mono">
                                {{ $p->booking->booking_code }}
                            </a>
                            <span class="block text-[11px] text-gray-500">{{ $p->booking->destination?->name }}</span>
                        </td>
                        <td class="py-3.5 pr-2">
                            <span class="px-2 py-0.5 rounded-full bg-stone-100 text-stone-700 text-[10px] font-semibold uppercase">
                                {{ $p->gateway->value ?? $p->gateway }}
                            </span>
                            @if($p->payment_method)
                                <span class="block text-[11px] text-gray-400 mt-0.5">{{ $p->payment_method }}</span>
                            @endif
                        </td>
                        <td class="py-3.5 pr-2 text-right font-mono font-bold text-gray-900">
                            Rp {{ number_format($p->amount, 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 text-center">
                            <x-status-badge :enum="$p->status" />
                        </td>
                        <td class="py-3.5 text-gray-600 font-mono text-[11px]">
                            @if($p->paid_at)
                                <span class="text-emerald-700 block font-semibold">Lunas: {{ $p->paid_at->format('d/m/Y H:i') }}</span>
                            @elseif($p->expires_at)
                                <span class="text-amber-700 block">Exp: {{ $p->expires_at->format('d/m/Y H:i') }}</span>
                            @else
                                -
                            @endif
                        </td>
                        <td class="py-3.5 text-right space-x-2">
                            @if($p->gateway->value === 'manual_transfer' && $p->status === \App\Enums\PaymentStatus::PENDING)
                                @can('payments.verify_manual')
                                <form action="{{ route('admin.finance.verifyManual', $p->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" onclick="return confirm('Verifikasi pembayaran manual ini dan setujui status booking?')"
                                            class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1 rounded-full text-[10px] font-bold uppercase">
                                        Verifikasi
                                    </button>
                                </form>
                                @endcan
                            @endif

                            @if($p->gateway->value !== 'manual_transfer' && $p->status === \App\Enums\PaymentStatus::PENDING)
                                <form action="{{ route('admin.finance.checkGatewayStatus', $p->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="border border-gray-200 hover:bg-gray-50 text-gray-700 px-3 py-1 rounded-full text-[10px] font-bold uppercase">
                                        Cek Status
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-gray-400">Belum ada riwayat pembayaran.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 pt-4 border-t border-gray-100">
            {{ $payments->links() }}
        </div>
    </div>
</div>
@endsection
