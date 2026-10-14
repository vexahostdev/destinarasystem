@extends('layouts.buyer')

@section('title', 'Dossier Pesanan #' . $booking->booking_code)

@section('content')
<div class="space-y-8">

    <!-- Header Dossier & Aksi -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-[#2B211E]/12">
        <div>
            <div class="flex items-center gap-2 text-xs text-[#2B211E]/60 mb-2">
                <a href="{{ route('buyer.dashboard') }}" class="hover:text-[#703A3A]">Dashboard</a>
                <span>/</span>
                <a href="{{ route('buyer.history') }}" class="hover:text-[#703A3A]">Riwayat</a>
                <span>/</span>
                <span class="text-[#703A3A] font-semibold">{{ $booking->booking_code }}</span>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#2B211E]">
                    Dossier Pesanan: {{ $booking->destination->name }}
                </h1>

                @php
                    $bSt = $booking->status instanceof \App\Enums\BookingStatus ? $booking->status->value : (string)$booking->status;
                    $bLabel = $booking->status instanceof \App\Enums\BookingStatus ? $booking->status->label() : ucfirst((string)$booking->status);
                @endphp
                @if($bSt === 'dikonfirmasi')
                    <span class="px-3 py-1 bg-[#51634b]/15 text-[#51634b] text-xs font-bold uppercase tracking-wider border border-[#51634b]/30 flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-[#51634b]"></span> Terkonfirmasi & Lunas
                    </span>
                @elseif($bSt === 'dibayar')
                    <span class="px-3 py-1 bg-amber-100 text-amber-800 text-xs font-bold uppercase tracking-wider border border-amber-300 flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span> Dibayar (Menunggu Konfirmasi Mitra)
                    </span>
                @elseif($bSt === 'menunggu_pembayaran')
                    <span class="px-3 py-1 bg-[#b87a38]/15 text-[#b87a38] text-xs font-bold uppercase tracking-wider border border-[#b87a38]/30 flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-[#b87a38]"></span> Menunggu Pembayaran
                    </span>
                @elseif($bSt === 'selesai')
                    <span class="px-3 py-1 bg-[#2B211E]/15 text-[#2B211E] text-xs font-bold uppercase tracking-wider border border-[#2B211E]/30">
                        Ekskursi Selesai
                    </span>
                @else
                    <span class="px-3 py-1 bg-gray-200 text-gray-800 text-xs font-bold uppercase tracking-wider">
                        {{ $bLabel }}
                    </span>
                @endif
            </div>
            <div class="text-xs text-[#2B211E]/60 mt-1 font-mono">
                ID SISTEM: {{ $booking->booking_code }} • DIAJUKAN: {{ $booking->created_at->translatedFormat('d F Y, H:i') }} WIB
            </div>
        </div>

        <!-- Tombol Aksi Cepat Sesuai Status -->
        <div class="flex flex-wrap items-center gap-2">
            @if($bSt === 'menunggu_pembayaran')
                <a href="{{ route('buyer.booking.payment', $booking->booking_code) }}" class="rgs-btn-primary px-5 py-2.5 text-xs font-bold uppercase tracking-wider flex items-center gap-1.5 shadow-sm">
                    <span class="material-symbols-outlined text-[18px]">payment</span>
                    <span>Lanjutkan Pembayaran</span>
                </a>

                @if(isset($latestPayment))
                    <form action="{{ route('buyer.booking.checkStatus', $booking->booking_code) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="rgs-btn-outline px-3.5 py-2 text-xs font-bold uppercase tracking-wider flex items-center gap-1.5 hover:bg-gray-100" title="Cek status terkini ke gateway pembayaran">
                            <span class="material-symbols-outlined text-[18px]">sync</span>
                            <span>Cek Status</span>
                        </button>
                    </form>
                @endif
            @else
                <a href="#dokumen" class="px-4 py-2 bg-[#51634b] text-white text-xs font-bold uppercase tracking-wider flex items-center gap-1.5 hover:bg-[#3f4f3a] transition-colors">
                    <span class="material-symbols-outlined text-[18px]">file_download</span>
                    <span>Unduh Dokumen Resmi</span>
                </a>
            @endif

            @if(isset($canCancel) && $canCancel)
                <button type="button" onclick="document.getElementById('modal-cancel-booking').classList.remove('hidden')" class="px-3.5 py-2 text-xs font-bold uppercase tracking-wider text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-300 rounded-full flex items-center gap-1.5 transition">
                    <span class="material-symbols-outlined text-[18px]">cancel</span>
                    <span>Batalkan</span>
                </button>
            @endif

            <a href="{{ route('buyer.booking.create', ['reorder' => $booking->booking_code]) }}" class="rgs-btn-outline px-4 py-2 text-xs font-bold uppercase tracking-wider flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[18px]">replay</span>
                <span>Pesan Ulang</span>
            </a>
        </div>
    </div>

    @if(isset($manualPayment) && isset($manualPayment->raw_response['rejection_reason']))
        <!-- Banner Peringatan Bukti Transfer Ditolak & Form Upload Ulang -->
        <div class="p-5 bg-rose-50 border-2 border-rose-300 rounded-2xl text-rose-900 space-y-3">
            <div class="flex items-start gap-3">
                <span class="material-symbols-outlined text-rose-600 text-2xl mt-0.5">error</span>
                <div class="space-y-1">
                    <h4 class="font-bold text-sm">Bukti Transfer Pembayaran Ditolak Admin</h4>
                    <p class="text-xs text-rose-700">
                        Catatan penolakan: <strong>"{{ $manualPayment->raw_response['rejection_reason'] }}"</strong>
                    </p>
                    <p class="text-[11px] text-rose-600">
                        Silakan unggah ulang bukti transfer yang jelas (struk ATM, mutasi m-banking, atau nota resmi bank).
                    </p>
                </div>
            </div>
            <form action="{{ route('buyer.booking.reuploadProof', $booking->booking_code) }}" method="POST" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3 pt-3 border-t border-rose-200">
                @csrf
                <div class="flex-1 min-w-[200px]">
                    <input type="file" name="proof_file" accept=".jpg,.jpeg,.png,.pdf" required class="w-full text-xs text-slate-700 file:mr-3 file:py-1.5 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-rose-700 file:text-white hover:file:bg-rose-800">
                </div>
                <button type="submit" class="px-4 py-2 bg-rose-700 hover:bg-rose-800 text-white text-xs font-bold uppercase tracking-wider rounded-full shadow-xs flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">upload_file</span>
                    <span>Unggah Ulang Bukti</span>
                </button>
            </form>
        </div>
    @endif

    <!-- 5-Stage Visual Progress Tracker -->
    <div class="rgs-card p-6 border-t-4 border-t-[#703A3A]">
        <h3 class="font-serif text-sm font-bold text-[#2B211E] uppercase tracking-wider mb-5">
            Tahapan Verifikasi & Status Pelaksanaan
        </h3>

        @php
            $isPaid = in_array($booking->status, ['dibayar', 'dikonfirmasi', 'selesai']);
            $isConfirmed = in_array($booking->status, ['dikonfirmasi', 'selesai']);
            $isCompleted = ($booking->status === 'selesai');
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-5 gap-3 relative">
            <!-- Step 1: Pengajuan -->
            <div class="p-3 border border-[#51634b]/30 bg-[#51634b]/10 text-xs space-y-1">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-[#51634b] text-[10px] uppercase">Langkah 1</span>
                    <span class="material-symbols-outlined text-[#51634b] text-[16px]">check_circle</span>
                </div>
                <div class="font-bold text-[#2B211E]">Pengajuan Masuk</div>
                <div class="text-[10px] text-[#2B211E]/70">Diterima sistem</div>
            </div>

            <!-- Step 2: Verifikasi Mitra -->
            <div class="p-3 border border-[#51634b]/30 bg-[#51634b]/10 text-xs space-y-1">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-[#51634b] text-[10px] uppercase">Langkah 2</span>
                    <span class="material-symbols-outlined text-[#51634b] text-[16px]">check_circle</span>
                </div>
                <div class="font-bold text-[#2B211E]">Kuota Disetujui</div>
                <div class="text-[10px] text-[#2B211E]/70">Slot tanggal terkonfirmasi</div>
            </div>

            <!-- Step 3: Pembayaran -->
            <div class="p-3 border {{ $isPaid ? 'border-[#51634b]/30 bg-[#51634b]/10' : 'border-[#b87a38]/40 bg-[#b87a38]/10 ring-1 ring-[#b87a38]' }} text-xs space-y-1">
                <div class="flex items-center justify-between">
                    <span class="font-bold {{ $isPaid ? 'text-[#51634b]' : 'text-[#b87a38]' }} text-[10px] uppercase">Langkah 3</span>
                    <span class="material-symbols-outlined {{ $isPaid ? 'text-[#51634b]' : 'text-[#b87a38]' }} text-[16px]">
                        {{ $isPaid ? 'check_circle' : 'pending' }}
                    </span>
                </div>
                <div class="font-bold text-[#2B211E]">Pembayaran Tagihan</div>
                <div class="text-[10px] text-[#2B211E]/70">
                    {{ $isPaid ? 'Lunas terverifikasi' : 'Menunggu pembayaran' }}
                </div>
            </div>

            <!-- Step 4: Dokumen & Izin -->
            <div class="p-3 border {{ $isConfirmed ? 'border-[#51634b]/30 bg-[#51634b]/10' : 'border-[#2B211E]/15 bg-[#faf6f0]' }} text-xs space-y-1">
                <div class="flex items-center justify-between">
                    <span class="font-bold {{ $isConfirmed ? 'text-[#51634b]' : 'text-[#2B211E]/50' }} text-[10px] uppercase">Langkah 4</span>
                    <span class="material-symbols-outlined {{ $isConfirmed ? 'text-[#51634b]' : 'text-[#2B211E]/40' }} text-[16px]">
                        {{ $isConfirmed ? 'check_circle' : 'hourglass_empty' }}
                    </span>
                </div>
                <div class="font-bold text-[#2B211E]">Penerbitan Izin & e-Pass</div>
                <div class="text-[10px] text-[#2B211E]/70">
                    {{ $isConfirmed ? 'Siap diunduh' : 'Menunggu pelunasan' }}
                </div>
            </div>

            <!-- Step 5: Pelaksanaan -->
            <div class="p-3 border {{ $isCompleted ? 'border-[#51634b]/30 bg-[#51634b]/10' : 'border-[#2B211E]/15 bg-[#faf6f0]' }} text-xs space-y-1">
                <div class="flex items-center justify-between">
                    <span class="font-bold {{ $isCompleted ? 'text-[#51634b]' : 'text-[#2B211E]/50' }} text-[10px] uppercase">Langkah 5</span>
                    <span class="material-symbols-outlined {{ $isCompleted ? 'text-[#51634b]' : 'text-[#2B211E]/40' }} text-[16px]">
                        {{ $isCompleted ? 'verified' : 'flag' }}
                    </span>
                </div>
                <div class="font-bold text-[#2B211E]">Ekskursi Lapangan</div>
                <div class="text-[10px] text-[#2B211E]/70">
                    {{ $isCompleted ? 'Selesai terlaksana' : 'Jadwal kunjungan' }}
                </div>
            </div>
        </div>
    </div>

    <!-- 2 Kolom Konten Dossier -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

        <!-- Kolom Kiri: Detail Tapak, PIC, & Rombongan (7 Kolom) -->
        <div class="lg:col-span-7 space-y-6">

            <!-- Card Informasi Tapak & PIC Pengelola -->
            <div class="rgs-card p-6 border-t-4 border-t-[#703A3A] space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-[#2B211E]/10">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#703A3A] text-[20px]">nature_people</span>
                        <h2 class="font-serif text-lg font-bold text-[#2B211E]">Informasi Destinasi & Pengelola</h2>
                    </div>
                    <span class="text-xs font-semibold text-[#703A3A] uppercase">{{ $booking->destination->city }}, {{ $booking->destination->province }}</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    <div class="sm:col-span-1 aspect-video sm:aspect-square bg-gray-100 border border-[#2B211E]/15 overflow-hidden">
                        <img src="{{ asset($booking->destination->cover_image) }}" alt="{{ $booking->destination->name }}" class="w-full h-full object-cover">
                    </div>
                    <div class="sm:col-span-2 space-y-2">
                        <h3 class="font-serif text-base font-bold text-[#2B211E]">{{ $booking->destination->name }}</h3>
                        <p class="text-xs text-[#2B211E]/75 leading-relaxed">
                            {{ $booking->destination->description }}
                        </p>
                    </div>
                </div>

                <!-- Kontak PIC Pengelola (Sesuai PRD: Terbuka Penuh Setelah Booking Terverifikasi) -->
                <div class="p-4 bg-[#faf6f0] border border-[#2B211E]/15 space-y-2 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-[#703A3A] uppercase tracking-wider text-[10px] flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">contacts</span>
                            <span>Kontak Resmi Penanggung Jawab Tapak Adat</span>
                        </span>
                        @if($isPaid)
                            <span class="text-[10px] text-[#51634b] font-bold uppercase flex items-center gap-0.5">
                                <span class="material-symbols-outlined text-[12px]">lock_open</span> Terverifikasi Terbuka
                            </span>
                        @else
                            <span class="text-[10px] text-[#b87a38] font-bold uppercase flex items-center gap-0.5">
                                <span class="material-symbols-outlined text-[12px]">lock</span> Terbuka Pasca Pembayaran
                            </span>
                        @endif
                    </div>

                    @php
                        $contact = $booking->destination->contacts->first();
                    @endphp

                    @if($contact)
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
                        <div>
                            <span class="text-[#2B211E]/60 text-[11px] block">Nama Koordinator Tapak:</span>
                            <span class="font-bold text-[#2B211E]">{{ $contact->contact_name }} ({{ $contact->role }})</span>
                        </div>
                        <div>
                            <span class="text-[#2B211E]/60 text-[11px] block">WhatsApp / Telepon Resmi:</span>
                            @if($isPaid)
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $contact->phone) }}" target="_blank" class="font-bold text-[#51634b] hover:underline flex items-center gap-1">
                                    <span>{{ $contact->phone }}</span>
                                    <span class="material-symbols-outlined text-[14px]">open_in_new</span>
                                </a>
                            @else
                                <span class="font-mono text-[#2B211E]/50">0878-xxxx-{{ substr($contact->phone, -4) }} (Disamarkan)</span>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Card Spesifikasi Rombongan Belajar -->
            <div class="rgs-card p-6 border-t-4 border-t-[#703A3A] space-y-4">
                <div class="flex items-center gap-2 pb-3 border-b border-[#2B211E]/10">
                    <span class="material-symbols-outlined text-[#703A3A] text-[20px]">badge</span>
                    <h2 class="font-serif text-lg font-bold text-[#2B211E]">Rincian Delegasi Rombongan</h2>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-xs">
                    <div>
                        <span class="text-[#2B211E]/60 block text-[11px]">Institusi Pemohon</span>
                        <span class="font-bold text-[#2B211E]">{{ $booking->institution->institution_name }}</span>
                    </div>
                    <div>
                        <span class="text-[#2B211E]/60 block text-[11px]">PIC Institusi</span>
                        <span class="font-bold text-[#2B211E]">{{ $booking->user->name }}</span>
                    </div>
                    <div>
                        <span class="text-[#2B211E]/60 block text-[11px]">Kategori Ekskursi</span>
                        <span class="font-bold text-[#703A3A] uppercase">{{ $booking->inquiry_type instanceof \App\Enums\InquiryType ? $booking->inquiry_type->label() : str_replace('_', ' ', (string)$booking->inquiry_type) }}</span>
                    </div>
                    <div>
                        <span class="text-[#2B211E]/60 block text-[11px]">Tanggal Pelaksanaan</span>
                        <span class="font-bold text-[#2B211E]">
                            {{ \Carbon\Carbon::parse($booking->planned_date_start)->translatedFormat('d M Y') }} s/d {{ \Carbon\Carbon::parse($booking->planned_date_end)->translatedFormat('d M Y') }}
                        </span>
                    </div>
                    <div>
                        <span class="text-[#2B211E]/60 block text-[11px]">Jumlah Siswa</span>
                        <span class="font-bold text-[#2B211E]">{{ $booking->participant_count }} Orang</span>
                    </div>
                    <div>
                        <span class="text-[#2B211E]/60 block text-[11px]">Guru / Dosen Pendamping</span>
                        <span class="font-bold text-[#2B211E]">{{ $booking->guide_count }} Orang</span>
                    </div>
                </div>

                @if($booking->purpose_notes)
                <div class="pt-3 border-t border-[#2B211E]/10 text-xs">
                    <span class="text-[#2B211E]/60 block text-[11px] mb-1">Catatan Kurikulum / Kebutuhan Khusus:</span>
                    <div class="p-3 bg-[#faf6f0] border border-[#2B211E]/10 italic text-[#2B211E]/80">
                        "{{ $booking->purpose_notes }}"
                    </div>
                </div>
                @endif
            </div>

            <!-- Card Pusat Dokumen Resmi Destinara (#dokumen) -->
            <div id="dokumen" class="rgs-card p-6 border-t-4 border-t-[#51634b] space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-[#2B211E]/10">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#51634b] text-[20px]">folder_special</span>
                        <h2 class="font-serif text-lg font-bold text-[#2B211E]">Dokumen Resmi Berstempel Destinara</h2>
                    </div>
                    <span class="text-[11px] text-[#51634b] font-bold uppercase">Sah untuk SPK & BOS</span>
                </div>

                <div class="space-y-3">
                    @forelse($booking->documents as $doc)
                    <div class="p-3.5 bg-white border border-[#2B211E]/15 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-[#703A3A]/10 text-[#703A3A] flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-[22px]">description</span>
                            </div>
                            <div>
                                <div class="font-bold text-xs text-[#2B211E]">{{ $doc->title }}</div>
                                <div class="text-[11px] text-[#2B211E]/60">
                                    Diterbitkan: {{ $doc->generated_at ? $doc->generated_at->translatedFormat('d M Y, H:i') : $booking->created_at->translatedFormat('d M Y') }}
                                </div>
                            </div>
                        </div>

                        <a href="{{ route('documents.download', ['document' => $doc->id]) }}" target="_blank" class="px-3.5 py-1.5 bg-[#faf6f0] hover:bg-[#703A3A] hover:text-white text-[#2B211E] text-xs font-bold uppercase tracking-wider border border-[#2B211E]/20 flex items-center justify-center gap-1.5 transition-colors">
                            <span class="material-symbols-outlined text-[16px]">visibility</span>
                            <span>Pratinjau / Unduh</span>
                        </a>
                    </div>
                    @empty
                    <div class="p-4 bg-[#faf6f0] text-center text-xs text-[#2B211E]/60">
                        Dokumen resmi akan otomatis diterbitkan begitu pembayaran diselesaikan.
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Card Ulasan Kunjungan Selesai -->
            @if($booking->status === \App\Enums\BookingStatus::SELESAI || $booking->status === 'selesai')
                <div class="rgs-card p-6 border-t-4 border-t-amber-500 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-[#2B211E]/10">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-amber-500 text-[20px]">star</span>
                            <h2 class="font-serif text-lg font-bold text-[#2B211E]">Ulasan & Penilaian Ekskursi</h2>
                        </div>
                    </div>

                    @if($booking->review)
                        <div class="p-4 bg-amber-50/50 rounded-2xl border border-amber-200/50 space-y-2 text-xs">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-1 text-amber-500 font-bold">
                                    @for($s = 1; $s <= 5; $s++)
                                        <span>{{ $s <= $booking->review->rating ? '★' : '☆' }}</span>
                                    @endfor
                                    <span class="text-slate-800 ml-1">({{ $booking->review->rating }}/5)</span>
                                </div>
                                <span class="text-[11px] text-slate-500">{{ $booking->review->created_at->translatedFormat('d M Y') }}</span>
                            </div>
                            <p class="text-slate-700 italic">"{{ $booking->review->comment }}"</p>
                            @if($booking->review->reply)
                                <div class="mt-3 p-3 bg-white rounded-xl border border-slate-200 text-xs">
                                    <span class="font-bold text-slate-900 block mb-0.5">Tanggapan Pengelola Tapak:</span>
                                    <p class="text-slate-600">{{ $booking->review->reply }}</p>
                                </div>
                            @endif
                        </div>
                    @else
                        <form action="{{ route('buyer.booking.review', $booking->booking_code) }}" method="POST" class="space-y-3">
                            @csrf
                            <p class="text-xs text-slate-600">Bagikan pengalaman ekskursi institusi Anda untuk membantu sekolah lain dan pengelola tapak.</p>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Rating</label>
                                <select name="rating" class="text-xs border border-slate-300 rounded-lg p-2 bg-white" required>
                                    <option value="5">★★★★★ — Sangat Memuaskan (5)</option>
                                    <option value="4">★★★★☆ — Memuaskan (4)</option>
                                    <option value="3">★★★☆☆ — Cukup (3)</option>
                                    <option value="2">★★☆☆☆ — Kurang (2)</option>
                                    <option value="1">★☆☆☆☆ — Sangat Kurang (1)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Komentar / Masukan Pembelajaran</label>
                                <textarea name="comment" rows="3" required class="w-full text-xs border border-slate-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-[#8C5151]" placeholder="Ceritakan bagaimana kegiatan berlangsung, kesesuaian modul edukasi, keramahan pemandu..."></textarea>
                            </div>
                            <button type="submit" class="px-4 py-2 bg-[#8C5151] hover:bg-[#734040] text-white text-xs font-bold uppercase rounded-lg transition">
                                Kirim Ulasan Resmi
                            </button>
                        </form>
                    @endif
                </div>
            @endif

        </div>

        <!-- Kolom Kanan: Rincian Keuangan & Kasir (5 Kolom) -->
        <div class="lg:col-span-5 space-y-6">

            <!-- Card Plinth Keuangan Transaksi -->
            <div class="rgs-card border border-[#2B211E]/20 shadow-sm overflow-hidden">
                <div class="bg-[#703A3A] text-white p-4">
                    <div class="text-[10px] font-bold uppercase tracking-widest text-white/70">Rincian Pembayaran Resmi</div>
                    <div class="font-serif text-xl font-bold">Total: Rp {{ number_format($booking->total_amount, 0, ',', '.') }}</div>
                </div>

                <div class="p-5 space-y-3 text-xs">
                    <div class="flex items-center justify-between pb-2 border-b border-[#2B211E]/10">
                        <span class="text-[#2B211E]/70">Biaya Tapak ({{ $booking->participant_count }} Siswa)</span>
                        <span class="font-bold text-[#2B211E]">Rp {{ number_format($booking->subtotal_amount, 0, ',', '.') }}</span>
                    </div>

                    <div class="flex items-center justify-between pb-2 border-b border-[#2B211E]/10">
                        <span class="text-[#2B211E]/70">Premi Asuransi Siswa (Rp {{ number_format(setting('insurance_per_pax', 2000), 0, ',', '.') }}/pax)</span>
                        <span class="font-bold text-[#2B211E]">Rp {{ number_format($booking->insurance_amount, 0, ',', '.') }}</span>
                    </div>

                    <div class="flex items-center justify-between pb-2 border-b border-[#2B211E]/10">
                        <span class="text-[#2B211E]/70">Administrasi & Verifikasi Dokumen</span>
                        <span class="font-bold text-[#2B211E]">Rp {{ number_format($booking->platform_fee, 0, ',', '.') }}</span>
                    </div>

                    <!-- Status Pembayaran Plinth -->
                    <div class="p-3.5 {{ $isPaid ? 'bg-[#51634b]/10 border-[#51634b]/30' : 'bg-[#b87a38]/10 border-[#b87a38]/30' }} border flex items-center justify-between">
                        <div>
                            <span class="text-[10px] uppercase font-bold tracking-wider text-[#2B211E]/60 block">Status Transaksi</span>
                            <span class="font-bold text-sm {{ $isPaid ? 'text-[#51634b]' : 'text-[#b87a38]' }}">
                                {{ $isPaid ? 'LUNAS TERVERIFIKASI' : 'BELUM DIBAYAR' }}
                            </span>
                        </div>
                        @if($isPaid)
                            <span class="material-symbols-outlined text-[#51634b] text-[24px]">verified</span>
                        @else
                            <span class="material-symbols-outlined text-[#b87a38] text-[24px]">schedule</span>
                        @endif
                    </div>

                    <!-- Tombol Aksi Kasir -->
                    @if(!$isPaid)
                        <a href="{{ route('buyer.booking.payment', $booking->booking_code) }}" class="w-full rgs-btn-primary py-3 px-4 text-xs font-bold uppercase tracking-widest shadow-sm flex items-center justify-center gap-2">
                            <span>Buka Halaman Kasir / Bayar</span>
                            <span class="material-symbols-outlined text-[18px]">credit_card</span>
                        </a>
                    @else
                        @php
                            $rawMethod = $booking->payment->payment_method ?? 'VA';
                            $methodStr = $rawMethod instanceof \UnitEnum ? $rawMethod->value : (string)$rawMethod;
                        @endphp
                        <div class="p-3 bg-[#51634b]/8 border border-[#51634b]/20 text-[11px] text-[#51634b] font-medium leading-relaxed">
                            ✓ Pembayaran telah diterima via <strong>{{ strtoupper(str_replace('_', ' ', $methodStr)) }}</strong> pada {{ $booking->payment->paid_at ? $booking->payment->paid_at->translatedFormat('d M Y, H:i') : '-' }} WIB.
                        </div>
                    @endif
                </div>
            </div>

            <!-- Callout Kebijakan Lapangan & Kepatuhan Adat -->
            <div class="rgs-card p-4 space-y-2 text-xs border-l-4 border-l-[#51634b]">
                <span class="font-bold text-[#2B211E] block">Protokol FPIC & Keselamatan Lapangan</span>
                <p class="text-[11px] text-[#2B211E]/70 leading-relaxed">
                    Setiap rombongan wajib menaati panduan kearifan lokal yang telah disepakati, menjaga kebersihan area adat, serta menggunakan e-Pass resmi saat tiba di lokasi.
                </p>
            </div>

        </div>

    </div>

    @if(isset($canCancel) && $canCancel)
    <!-- Modal Pembatalan Pesanan Mandiri -->
    <div id="modal-cancel-booking" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 hidden" onclick="if(event.target === this) this.classList.add('hidden')">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 space-y-5 shadow-2xl relative">
            <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                <div class="flex items-center gap-2 text-rose-700">
                    <span class="material-symbols-outlined text-2xl">cancel</span>
                    <h3 class="font-serif text-lg font-bold text-slate-900">Batalkan Pesanan</h3>
                </div>
                <button type="button" onclick="document.getElementById('modal-cancel-booking').classList.add('hidden')" class="p-1 text-slate-400 hover:text-slate-600 rounded-full">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <!-- Kalkulasi Estimasi Refund -->
            <div class="p-4 bg-amber-50/70 border border-amber-200 rounded-2xl text-xs space-y-2">
                <div class="flex items-center justify-between font-bold text-amber-900">
                    <span>Estimasi Pengembalian Dana (Refund):</span>
                    <span class="text-sm font-black text-[#703A3A]">{{ $refundCalc['percent'] }}% (Rp {{ number_format($refundCalc['amount'], 0, ',', '.') }})</span>
                </div>
                <p class="text-[11px] text-amber-800 leading-relaxed">
                    {{ $refundCalc['tier_note'] ?? 'Sesuai dengan ketentuan kebijakan pembatalan destinasi dan jangka waktu pelaksanaan.' }}
                </p>
                @if($refundCalc['percent'] == 0)
                    <div class="text-[11px] text-rose-700 font-medium">
                        * Pembatalan pada tahap ini tidak berhak atas pengembalian dana (0%).
                    </div>
                @endif
            </div>

            <form action="{{ route('buyer.booking.cancel', $booking->booking_code) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Alasan Pembatalan <span class="text-rose-500">*</span></label>
                    <textarea name="reason" rows="3" required minlength="5" maxlength="500" class="w-full text-xs border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-[#703A3A] outline-none" placeholder="Tuliskan alasan pembatalan kegiatan ekskursi..."></textarea>
                </div>

                @if($refundCalc['percent'] > 0)
                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-3">
                        <span class="text-xs font-bold text-slate-900 block">Informasi Rekening Pengembalian Dana</span>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Nama Bank Tujuan <span class="text-rose-500">*</span></label>
                            <select name="bank_name" required class="w-full text-xs border border-slate-300 rounded-lg p-2 bg-white focus:ring-2 focus:ring-[#703A3A]">
                                <option value="">-- Pilih Bank --</option>
                                @foreach($banks as $b)
                                    <option value="{{ $b->name }}">{{ $b->name }} ({{ $b->code }})</option>
                                @endforeach
                                <option value="Bank Lainnya">Bank Lainnya</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Nomor Rekening <span class="text-rose-500">*</span></label>
                                <input type="text" name="bank_account_number" required maxlength="50" class="w-full text-xs border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-[#703A3A]" placeholder="1234567890">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Nama Pemilik Rekening <span class="text-rose-500">*</span></label>
                                <input type="text" name="bank_account_holder" required maxlength="255" class="w-full text-xs border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-[#703A3A]" placeholder="a.n. Sekolah / Lembaga">
                            </div>
                        </div>
                    </div>
                @endif

                <div class="pt-2 flex items-center justify-end gap-3">
                    <button type="button" onclick="document.getElementById('modal-cancel-booking').classList.add('hidden')" class="px-4 py-2 border border-slate-300 text-slate-700 text-xs font-bold uppercase rounded-full hover:bg-slate-100">
                        Kembali
                    </button>
                    <button type="submit" onclick="return confirm('Apakah Anda yakin ingin membatalkan pesanan ini secara permanen?')" class="px-5 py-2 bg-rose-700 hover:bg-rose-800 text-white text-xs font-bold uppercase tracking-wider rounded-full transition shadow-sm">
                        Ya, Batalkan Pesanan
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection
