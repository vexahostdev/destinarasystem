@extends('layouts.partner')

@section('title', 'Verifikasi Check-in Rombongan — ' . $booking->booking_code)

@section('content')
<div class="max-w-3xl mx-auto py-8 px-4 sm:px-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-[#8C5151] px-6 py-4 text-white">
            <h1 class="text-xl font-bold">Verifikasi Check-in Masuk Tapak</h1>
            <p class="text-sm text-[#FED7D7]">Pindai e-Pass resmi rombongan belajar</p>
        </div>

        <div class="p-6">
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">
                    {{ session('error') }}
                </div>
            @endif

            @if(isset($dateWindowError) && $dateWindowError)
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">
                    {{ $dateWindowError }}
                </div>
            @endif

            <div class="border-b border-gray-100 pb-4 mb-4">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm text-gray-500 font-mono">{{ $booking->booking_code }}</span>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $booking->isOngoing() ? 'bg-blue-100 text-blue-800' : ($booking->isConfirmed() ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800') }}">
                        {{ $booking->status->label() }}
                    </span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">{{ $booking->institution?->institution_name ?? 'Institusi Pemesan' }}</h2>
                <p class="text-gray-600 text-sm mt-1">Destinasi: <span class="font-medium text-gray-800">{{ $booking->destination->name }}</span></p>
            </div>

            <div class="grid grid-cols-2 gap-4 text-sm mb-6 bg-gray-50 p-4 rounded-lg">
                <div>
                    <span class="text-gray-500 block">Paket:</span>
                    <span class="font-medium text-gray-800">{{ $booking->package->name }}</span>
                </div>
                <div>
                    <span class="text-gray-500 block">Jadwal Kunjungan:</span>
                    <span class="font-medium text-gray-800">{{ $booking->planned_date_start->translatedFormat('d F Y') }} s.d. {{ $booking->planned_date_end->translatedFormat('d F Y') }}</span>
                </div>
                <div>
                    <span class="text-gray-500 block">Peserta Terdaftar:</span>
                    <span class="font-bold text-gray-900">{{ $booking->participant_count }} Peserta</span>
                    @if($booking->companion_count > 0)
                        <span class="text-xs text-gray-500 block">+ {{ $booking->companion_count }} Pendamping</span>
                    @endif
                </div>
                <div>
                    <span class="text-gray-500 block">PIC Rombongan:</span>
                    <span class="font-medium text-gray-800">{{ $booking->institution?->pic_name ?? '-' }} ({{ $booking->institution?->pic_phone ?? '-' }})</span>
                </div>
            </div>

            @if($booking->isConfirmed() && empty($dateWindowError))
                <form action="{{ route('mitra.checkin.confirm', $booking->booking_code) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="actual_participant_count" class="block text-sm font-medium text-gray-700">Jumlah Peserta Aktual yang Hadir di Lokasi</label>
                        <input type="number" id="actual_participant_count" name="actual_participant_count" value="{{ old('actual_participant_count', $booking->participant_count) }}" min="1" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#8C5151] focus:ring-[#8C5151] sm:text-sm">
                    </div>

                    <div>
                        <label for="note" class="block text-sm font-medium text-gray-700">Catatan Kedatangan (Opsional)</label>
                        <textarea id="note" name="note" rows="2" placeholder="Kondisi rombongan, waktu tiba, dll." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#8C5151] focus:ring-[#8C5151] sm:text-sm">{{ old('note') }}</textarea>
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="w-full bg-[#8C5151] hover:bg-[#723f3f] text-white font-bold py-3 px-4 rounded-lg shadow transition">
                            Konfirmasi Masuk Tapak (Check-in)
                        </button>
                    </div>
                </form>
            @elseif($booking->isOngoing() && $booking->checkIn)
                <div class="p-4 bg-green-50 border border-green-200 rounded-lg text-sm">
                    <p class="font-bold text-green-900">Rombongan Telah Check-in</p>
                    <p class="text-green-800 mt-1">Check-in berhasil diverifikasi pada {{ $booking->checkIn->scanned_at->translatedFormat('d F Y, H:i') }} WIB dengan jumlah {{ $booking->checkIn->actual_participant_count }} peserta hadir.</p>
                </div>
            @else
                <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg text-sm text-yellow-800">
                    Status pesanan saat ini adalah <strong>{{ $booking->status->label() }}</strong>. Check-in hanya dapat diproses untuk pesanan yang telah dikonfirmasi dan lunas.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
