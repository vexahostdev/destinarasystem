@extends('layouts.public')

@section('title', 'Verifikasi Keabsahan Dokumen — Destinara')

@section('content')
<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-xl mx-auto">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-extrabold text-[#2E3D5C]">DESTINARA</h1>
            <p class="text-sm text-gray-500 mt-1">Sistem Verifikasi Keabsahan Dokumen Digital</p>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden border border-gray-200">
            @if($document)
                <div class="p-6">
                    <div class="flex items-center justify-center w-16 h-16 mx-auto bg-green-100 rounded-full mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>

                    <div class="text-center mb-6">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $document->isFinal() ? 'bg-green-100 text-green-800' : ($document->isVoid() ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                            {{ $document->status->label() }}
                        </span>
                        <h2 class="text-xl font-bold text-gray-900 mt-2">{{ $document->title }}</h2>
                        <p class="text-sm text-gray-500">Nomor: <span class="font-mono font-medium text-gray-700">{{ $document->document_number }}</span></p>
                    </div>

                    <div class="border-t border-gray-100 pt-4 space-y-3 text-sm">
                        <div class="flex justify-between py-1">
                            <span class="text-gray-500">Jenis Dokumen</span>
                            <span class="font-medium text-gray-900">{{ $document->type->label() }}</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-gray-500">Tanggal Diterbitkan</span>
                            <span class="font-medium text-gray-900">{{ $document->generated_at ? $document->generated_at->translatedFormat('d F Y, H:i') : '-' }} WIB</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-gray-500">Versi Dokumen</span>
                            <span class="font-medium text-gray-900">v{{ $document->version }}</span>
                        </div>

                        @if($document->booking)
                            <div class="flex justify-between py-1">
                                <span class="text-gray-500">Kode Reservasi</span>
                                <span class="font-mono font-medium text-gray-900">{{ $document->booking->booking_code }}</span>
                            </div>
                            <div class="flex justify-between py-1">
                                <span class="text-gray-500">Destinasi Tapak</span>
                                <span class="font-medium text-gray-900">{{ $document->booking->destination->name }}</span>
                            </div>
                            <div class="flex justify-between py-1">
                                <span class="text-gray-500">Institusi Pemesan</span>
                                <span class="font-medium text-gray-900">{{ $document->booking->institution?->institution_name ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between py-1">
                                <span class="text-gray-500">Jadwal Kunjungan</span>
                                <span class="font-medium text-gray-900">{{ $document->booking->planned_date_start->format('d/m/Y') }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="mt-6 p-4 bg-gray-50 rounded-md border border-gray-100 text-xs text-gray-500 text-center">
                        Dokumen ini diterbitkan secara sah oleh platform Destinara dan dilindungi integritasnya melalui tanda tangan digital.
                    </div>
                </div>
            @else
                <div class="p-6 text-center">
                    <div class="flex items-center justify-center w-16 h-16 mx-auto bg-red-100 rounded-full mb-4">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <h2 class="text-lg font-bold text-gray-900">Dokumen Tidak Ditemukan</h2>
                    <p class="text-sm text-gray-500 mt-2">Kode hash verifikasi yang Anda pindai tidak valid atau dokumen tidak terdaftar di sistem Destinara.</p>
                </div>
            @endif

            <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 text-center">
                <a href="{{ route('destinasi.index') }}" class="text-sm font-medium text-[#2E3D5C] hover:underline">
                    &larr; Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
