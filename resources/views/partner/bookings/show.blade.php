@extends('layouts.partner')

@section('title', 'Detail Permohonan Rombongan #' . $booking->booking_code)

@section('content')
<div class="space-y-8">

    <!-- Header & Navigasi -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-[#1F2937]/12">
        <div>
            <div class="flex items-center gap-2 text-xs text-[#1F2937]/60 mb-2">
                <a href="{{ route('mitra.dashboard') }}" class="hover:text-[#1F2B45]">Dashboard</a>
                <span>/</span>
                <a href="{{ route('mitra.bookings') }}" class="hover:text-[#1F2B45]">Booking Masuk</a>
                <span>/</span>
                <span class="text-[#1F2B45] font-semibold">{{ $booking->booking_code }}</span>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#1F2937]">
                    Permohonan: {{ $booking->destination->name }}
                </h1>

                @if($booking->status === \App\Enums\BookingStatus::DIKONFIRMASI)
                    <span class="px-3 py-1 bg-[#51634b]/15 text-[#51634b] text-xs font-bold uppercase tracking-wider border border-[#51634b]/30 flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#51634b]"></span> Terjadwal & Siap
                    </span>
                @elseif($booking->status === \App\Enums\BookingStatus::DIBAYAR)
                    <span class="px-3 py-1 bg-amber-100 text-amber-800 text-xs font-bold uppercase tracking-wider border border-amber-300 flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> Lunas — Perlu Konfirmasi
                    </span>
                @elseif($booking->status === \App\Enums\BookingStatus::BERLANGSUNG)
                    <span class="px-3 py-1 bg-blue-100 text-blue-800 text-xs font-bold uppercase tracking-wider border border-blue-300 flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> Sedang Berkunjung
                    </span>
                @elseif($booking->status === \App\Enums\BookingStatus::SELESAI)
                    <span class="px-3 py-1 bg-[#1F2937]/10 text-[#1F2937]/80 text-xs font-bold uppercase tracking-wider">
                        Selesai
                    </span>
                @elseif($booking->status === \App\Enums\BookingStatus::BATAL)
                    <span class="px-3 py-1 bg-red-100 text-red-700 text-xs font-bold uppercase tracking-wider">
                        Ditolak / Batal
                    </span>
                @else
                    <span class="px-3 py-1 bg-gray-100 text-gray-700 text-xs font-bold uppercase tracking-wider">
                        {{ $booking->status->label() }}
                    </span>
                @endif
            </div>
            <div class="text-xs text-[#1F2937]/60 mt-1 font-mono">
                KODE RESERVASI: {{ $booking->booking_code }} • DIAJUKAN: {{ $booking->created_at->translatedFormat('d F Y, H:i') }} WIB
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('mitra.bookings') }}" class="rgs-btn-outline px-4 py-2 text-xs font-bold uppercase tracking-wider flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                <span>Kembali ke Daftar</span>
            </a>
        </div>
    </div>

    <!-- Konten Utama: 2 Kolom -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Kolom Kiri: Rincian Rombongan & Paket (2 Kolom) -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Card Rincian Ekskursi -->
            <div class="rgs-card p-6 space-y-4">
                <h3 class="font-serif text-lg font-bold text-[#1F2937] border-b border-[#1F2937]/10 pb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#1F2B45] text-[20px]">groups</span>
                    <span>Informasi Rombongan Institusi</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <span class="text-[#1F2937]/60 block text-[11px]">Nama Institusi / Lembaga:</span>
                        <span class="font-bold text-[#1F2937] text-sm">{{ $booking->institution->institution_name ?? $booking->user?->name }}</span>
                    </div>
                    <div>
                        <span class="text-[#1F2937]/60 block text-[11px]">Penanggung Jawab (PIC):</span>
                        <span class="font-bold text-[#1F2937] text-sm">{{ $booking->institution->pic_name ?? $booking->user?->name }}</span>
                        @if(!empty($booking->user?->phone))
                            <span class="block text-slate-500 font-mono mt-0.5">Telp: {{ $booking->user->phone }}</span>
                        @endif
                    </div>
                    <div>
                        <span class="text-[#1F2937]/60 block text-[11px]">Jadwal Kunjungan:</span>
                        <span class="font-bold text-[#1F2937]">
                            {{ \Carbon\Carbon::parse($booking->planned_date_start)->translatedFormat('d F Y') }}
                            s/d
                            {{ \Carbon\Carbon::parse($booking->planned_date_end)->translatedFormat('d F Y') }}
                        </span>
                    </div>
                    <div>
                        <span class="text-[#1F2937]/60 block text-[11px]">Total Peserta & Pendamping:</span>
                        <span class="font-bold text-[#1F2937]">{{ $booking->participant_count }} Peserta + {{ $booking->companion_count }} Pendamping</span>
                    </div>
                    <div>
                        <span class="text-[#1F2937]/60 block text-[11px]">Jenis Kunjungan:</span>
                        <span class="font-bold text-[#1F2B45] uppercase">
                            {{ $booking->inquiry_type === \App\Enums\InquiryType::STUDY_TOUR ? 'Study Tour P5' : 'Riset Ilmiah & Penelitian' }}
                        </span>
                    </div>
                    <div>
                        <span class="text-[#1F2937]/60 block text-[11px]">Estimasi Payout Mitra:</span>
                        <span class="font-bold font-mono text-[#51634b] text-sm">
                            Rp {{ number_format($booking->partner_net_amount ?: ($booking->subtotal_amount - $booking->commission_amount), 0, ',', '.') }}
                        </span>
                    </div>
                </div>

                @if($booking->purpose_notes)
                    <div class="mt-4 p-3 bg-[#F4F6F9] border border-[#1F2937]/10 rounded-xl text-xs space-y-1">
                        <span class="font-bold text-[#1F2937] block">Catatan Tujuan & Kebutuhan Lapangan:</span>
                        <p class="text-[#1F2937]/80 italic">"{{ $booking->purpose_notes }}"</p>
                    </div>
                @endif
            </div>

            <!-- Card Pusat Dokumen Resmi -->
            <div id="surat-izin" class="rgs-card p-6 border-t-4 border-t-[#51634b] space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-[#1F2937]/10">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#51634b] text-[20px]">folder_special</span>
                        <h3 class="font-serif text-lg font-bold text-[#1F2937]">Dokumen & Berkas Kegiatan</h3>
                    </div>
                    <span class="text-[11px] text-[#51634b] font-bold uppercase">Arsip Resmi</span>
                </div>

                <div class="space-y-3">
                    @forelse($booking->documents as $doc)
                    <div class="p-3.5 bg-white border border-[#1F2937]/15 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-[#1F2B45]/10 text-[#1F2B45] flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-[22px]">description</span>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-xs text-[#1F2937]">{{ $doc->title }}</span>
                                    @if($doc->status === \App\Enums\DocumentStatus::VOID)
                                        <span class="px-2 py-0.5 bg-rose-100 text-rose-800 text-[10px] font-bold uppercase rounded border border-rose-300">
                                            Void (Tidak Berlaku)
                                        </span>
                                    @elseif($doc->status === \App\Enums\DocumentStatus::FINAL)
                                        <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-bold uppercase rounded border border-emerald-300">
                                            Sah
                                        </span>
                                    @elseif($doc->status === \App\Enums\DocumentStatus::DRAFT)
                                        <span class="px-2 py-0.5 bg-amber-100 text-amber-800 text-[10px] font-bold uppercase rounded border border-amber-300">
                                            Draf
                                        </span>
                                    @endif
                                </div>
                                <div class="text-[11px] text-[#1F2937]/60 font-mono">
                                    {{ $doc->document_number }} • Versi {{ $doc->version }}
                                </div>
                            </div>
                        </div>

                        <a href="{{ route('documents.download', ['document' => $doc->id]) }}" target="_blank" class="px-3.5 py-1.5 bg-[#F4F6F9] hover:bg-[#1F2B45] hover:text-white text-[#1F2937] text-xs font-bold uppercase tracking-wider border border-[#1F2937]/20 flex items-center justify-center gap-1.5 transition-colors">
                            <span class="material-symbols-outlined text-[16px]">visibility</span>
                            <span>Unduh Berkas</span>
                        </a>
                    </div>
                    @empty
                    <div class="p-4 bg-[#F4F6F9] text-center text-xs text-[#1F2937]/60">
                        Belum ada dokumen yang diterbitkan untuk reservasi ini.
                    </div>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- Kolom Kanan: Upload Surat Izin Bertanda Tangan (1 Kolom) -->
        <div class="space-y-6">

            <!-- Card Aksi Upload Surat Izin Bertanda Tangan -->
            <div class="rgs-card p-6 border-t-4 border-t-[#1F2B45] space-y-4">
                <div class="flex items-center gap-2 pb-3 border-b border-[#1F2937]/10">
                    <span class="material-symbols-outlined text-[#1F2B45] text-[20px]">verified</span>
                    <h3 class="font-serif text-lg font-bold text-[#1F2937]">Surat Izin Bertanda Tangan</h3>
                </div>

                @if($canUploadPermit)
                    <div class="space-y-3">
                        <p class="text-xs text-[#1F2937]/75">
                            Unduh draf surat izin resmi di samping, cetak dan bubuhkan tanda tangan/stempel pengelola destinasi, lalu unggah kembali berkas pindaian (PDF) di bawah ini.
                        </p>

                        @if($permitSigned)
                            <div class="p-3 bg-emerald-50 border border-emerald-300 rounded-xl text-emerald-900 text-xs space-y-1">
                                <span class="font-bold flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">check_circle</span>
                                    Surat Izin Resmi Aktif: Versi {{ $permitSigned->version }}
                                </span>
                                <span class="font-mono text-[11px] block">{{ $permitSigned->document_number }}</span>
                                <span class="text-[10px] text-emerald-700 block">Diterbitkan: {{ $permitSigned->generated_at?->translatedFormat('d M Y, H:i') }}</span>
                                <span class="text-[10px] text-emerald-700 block italic">Mengunggah berkas baru akan menggantikan (void) versi ini.</span>
                            </div>
                        @endif

                        <form action="{{ route('mitra.booking.uploadPermit', $booking->booking_code) }}" method="POST" enctype="multipart/form-data" class="space-y-3 pt-2">
                            @csrf
                            <div>
                                <label class="block text-xs font-bold text-[#1F2937] mb-1">Pilih Berkas PDF Resmi:</label>
                                <input type="file" name="file" accept=".pdf,application/pdf" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-2xl file:border-0 file:text-xs file:font-semibold file:bg-[#1F2B45]/10 file:text-[#1F2B45] hover:file:bg-[#1F2B45]/20 cursor-pointer" required>
                                @error('file')
                                    <p class="text-rose-600 text-[11px] mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="submit" class="w-full rgs-btn-primary py-2.5 text-xs font-bold uppercase tracking-wider flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">upload_file</span>
                                <span>Unggah Surat Izin</span>
                            </button>
                        </form>
                    </div>
                @else
                    <div class="p-4 bg-[#F4F6F9] border border-[#1F2937]/10 rounded-xl text-xs text-[#1F2937]/70 space-y-2">
                        @if($booking->status !== \App\Enums\BookingStatus::DIKONFIRMASI)
                            <p>Unggah surat izin hanya tersedia setelah permohonan booking dikonfirmasi resmi oleh pengelola.</p>
                        @elseif(!$permitDraft)
                            <p>Rombongan ini tidak mengajukan kebutuhan draf surat izin dinas.</p>
                        @else
                            <p>Fitur unggah surat izin tidak aktif untuk status booking saat ini.</p>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Card Bantuan Operasional -->
            <div class="rgs-card p-5 bg-[#F4F6F9] space-y-2 text-xs text-[#1F2937]/70">
                <span class="font-bold text-[#1F2937] block">Bantuan Perizinan:</span>
                <p>Jika memerlukan asistensi legalitas, hubungi tim pendamping kemitraan Destinara melalui saluran resmi pengelola.</p>
            </div>

        </div>

    </div>

</div>
@endsection
