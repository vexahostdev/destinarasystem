@extends('layouts.admin')

@section('title', 'Verifikasi & Kurasi Listing Tapak Baru')

@section('content')
<div class="space-y-8" x-data="{ selectedDest: null, modalType: null, modalNotes: '' }">

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-[#2B211E]/12 pb-6">
        <div>
            <div class="flex items-center gap-2 text-xs font-mono font-semibold uppercase tracking-wider text-[#703A3A] mb-1">
                <span>Kurasi Lapangan Nusantara</span>
                <span>•</span>
                <span>Gatekeeper Mutu Edukasi</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#2B211E]">
                Verifikasi & Telaah Listing Tapak Baru
            </h1>
            <p class="text-sm text-[#2B211E]/70 max-w-2xl">
                Evaluasi dossier tapak adat dan stasiun riset yang diajukan oleh mitra pengelola sebelum diterbitkan ke katalog publik Destinara.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <span class="px-3 py-1.5 bg-amber-100 text-amber-900 border border-amber-300 text-xs font-semibold flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                <span>{{ $pendingCount }} Dossier Menunggu Telaah</span>
            </span>
        </div>
    </div>

    <!-- Filter Tabs Status -->
    <div class="flex items-center gap-2 border-b border-[#2B211E]/12 pb-2 overflow-x-auto text-xs font-semibold uppercase tracking-wider">
        <a href="{{ route('admin.destinations.verify', ['status' => 'pending_review']) }}" 
           class="px-4 py-2 transition-colors flex items-center gap-1.5 {{ $status === 'pending_review' ? 'bg-[#703A3A] text-white' : 'text-[#2B211E]/70 hover:bg-[#2B211E]/5' }}">
            <span>Menunggu Telaah</span>
            <span class="px-1.5 py-0.2 bg-white/20 text-[10px] font-mono">{{ $pendingCount }}</span>
        </a>
        <a href="{{ route('admin.destinations.verify', ['status' => 'published']) }}" 
           class="px-4 py-2 transition-colors {{ $status === 'published' ? 'bg-[#703A3A] text-white' : 'text-[#2B211E]/70 hover:bg-[#2B211E]/5' }}">
            Diterbitkan (Aktif)
        </a>
        <a href="{{ route('admin.destinations.verify', ['status' => 'all']) }}" 
           class="px-4 py-2 transition-colors {{ $status === 'all' ? 'bg-[#703A3A] text-white' : 'text-[#2B211E]/70 hover:bg-[#2B211E]/5' }}">
            Semua Arsip Tapak
        </a>
    </div>

    <!-- Grid List Pengajuan Tapak -->
    @if($destinations->isEmpty())
        <div class="rgs-card p-12 text-center bg-white">
            <span class="material-symbols-outlined text-4xl text-[#2B211E]/30 mb-2">inbox</span>
            <h3 class="font-serif text-lg font-bold text-[#2B211E]">Tidak Ada Dossier Tapak</h3>
            <p class="text-xs text-[#2B211E]/60 max-w-md mx-auto mt-1">
                Semua pengajuan tapak adat dan stasiun riset pada filter ini telah selesai ditelaah oleh tim kurasi.
            </p>
        </div>
    @else
        <div class="space-y-6">
            @foreach($destinations as $d)
            @php
                $statusVal = $d->status->value ?? (string)$d->status;
            @endphp
            <div class="rgs-card bg-white p-6 transition-all hover:shadow-md border-l-4 {{ $statusVal === 'pending_review' ? 'border-l-amber-600' : ($statusVal === 'published' ? 'border-l-emerald-600' : 'border-l-gray-400') }}">
                <div class="flex flex-col lg:flex-row items-start justify-between gap-6">
                    
                    <!-- Thumbnail & Quick Specs -->
                    <div class="w-full lg:w-64 h-44 bg-[#faf6f0] shrink-0 overflow-hidden relative border border-[#2B211E]/10">
                        <img src="{{ $d->cover_image ? asset('storage/' . $d->cover_image) : asset('assets/img/hero-terroir.png') }}" alt="{{ $d->name }}" class="w-full h-full object-cover">
                        <div class="absolute top-2 left-2 px-2 py-0.5 bg-black/60 backdrop-blur-xs text-white text-[10px] font-mono uppercase">
                            {{ $d->category?->name ?? 'Edukasi' }}
                        </div>
                        <div class="absolute bottom-2 left-2 px-2 py-0.5 bg-[#703A3A] text-white text-[10px] font-mono">
                            {{ $d->packages->min('price_per_pax') ? 'Mulai Rp ' . number_format($d->packages->min('price_per_pax'), 0, ',', '.') : 'Tersedia Paket' }}
                        </div>
                    </div>

                    <!-- Dossier Overview & Checklist -->
                    <div class="flex-1 space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <span class="text-xs font-mono text-[#2B211E]/60 uppercase">{{ $d->province?->name ?? '' }} • {{ $d->city }}</span>
                                <h3 class="font-serif text-xl font-bold text-[#2B211E]">{{ $d->name }}</h3>
                            </div>
                            <div>
                                <x-status-badge :enum="$d->status" />
                            </div>
                        </div>

                        <p class="text-xs text-[#2B211E]/80 leading-relaxed">
                            {{ $d->short_description ?? $d->description }}
                        </p>

                        <!-- Highlights Kurikulum & Standar Keselamatan -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 text-xs">
                            <div class="bg-[#faf6f0] p-2.5 border border-[#2B211E]/10">
                                <span class="font-bold text-[#703A3A] block mb-1">Relevansi Kurikulum & Sasaran:</span>
                                <span class="text-[#2B211E]/80 block mb-1">{{ $d->educational_highlights ?? 'Pembelajaran lapangan tematik terintegrasi kearifan lokal.' }}</span>
                                <div class="flex flex-wrap gap-1 mt-1">
                                    @if(is_array($d->suitable_for))
                                        @foreach($d->suitable_for as $grade)
                                            <span class="px-1.5 py-0.2 bg-white border border-[#2B211E]/15 text-[10px] font-mono uppercase">{{ $grade }}</span>
                                        @endforeach
                                    @endif
                                </div>
                            </div>

                            <div class="bg-[#faf6f0] p-2.5 border border-[#2B211E]/10">
                                <span class="font-bold text-[#51634b] block mb-1">Daya Dukung & Fasilitas Rombongan:</span>
                                <span class="text-[#2B211E]/80 block">Kapasitas Harian: <strong class="font-mono">{{ $d->default_daily_capacity ?? 50 }} Siswa/Hari</strong></span>
                                <div class="flex flex-wrap gap-1 mt-1">
                                    @foreach($d->facilities->take(3) as $fac)
                                        <span class="px-1.5 py-0.2 bg-white border border-[#2B211E]/15 text-[10px]">{{ $fac->name }}</span>
                                    @endforeach
                                    @if($d->facilities->count() > 3)
                                        <span class="text-[10px] text-[#2B211E]/60">+{{ $d->facilities->count() - 3 }} lainnya</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Kriteria Mutu Destinara -->
                        <div class="flex flex-wrap items-center gap-4 text-[11px] font-mono text-[#2B211E]/70 pt-2 border-t border-[#2B211E]/08">
                            <span class="flex items-center gap-1 text-emerald-800">
                                <span class="material-symbols-outlined text-[14px]">check_circle</span>
                                Protokol FPIC Adat Sah
                            </span>
                            <span class="flex items-center gap-1 text-emerald-800">
                                <span class="material-symbols-outlined text-[14px]">check_circle</span>
                                Akses Bus Besar Terverifikasi
                            </span>
                            <span class="flex items-center gap-1 text-emerald-800">
                                <span class="material-symbols-outlined text-[14px]">check_circle</span>
                                Surat Penetapan Pengelola
                            </span>
                        </div>
                    </div>

                    <!-- Action Column -->
                    <div class="flex flex-row lg:flex-col items-center justify-end gap-2 w-full lg:w-48 shrink-0 pt-4 lg:pt-0 border-t lg:border-t-0 border-[#2B211E]/10">
                        @if($d->status === 'pending_review')
                            <form action="{{ route('admin.destinations.updateStatus', $d->id) }}" method="POST" class="w-full">
                                @csrf
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="rgs-btn-primary w-full py-2 text-xs font-semibold uppercase tracking-wider flex items-center justify-center gap-1.5 shadow-xs">
                                    <span class="material-symbols-outlined text-[16px]">verified</span>
                                    <span>Setujui & Terbitkan</span>
                                </button>
                            </form>

                            <button type="button" 
                                    @click="selectedDest = {{ $d->id }}; modalType = 'revision'"
                                    class="rgs-btn-outline w-full py-2 text-xs font-semibold uppercase tracking-wider flex items-center justify-center gap-1.5 bg-white">
                                <span class="material-symbols-outlined text-[16px]">edit_note</span>
                                <span>Minta Revisi</span>
                            </button>

                            <button type="button" 
                                    @click="selectedDest = {{ $d->id }}; modalType = 'reject'"
                                    class="w-full py-2 text-xs font-semibold uppercase tracking-wider text-red-700 hover:bg-red-50 border border-red-200 transition-colors flex items-center justify-center gap-1.5">
                                <span class="material-symbols-outlined text-[16px]">close</span>
                                <span>Tolak Dossier</span>
                            </button>
                        @else
                            <a href="{{ route('admin.destinations.curation', $d->id) }}" class="rgs-btn-outline w-full py-2 text-xs font-semibold uppercase tracking-wider flex items-center justify-center gap-1.5 bg-white">
                                <span class="material-symbols-outlined text-[16px]">edit</span>
                                <span>Edit Kurasi</span>
                            </a>
                        @endif

                        <a href="{{ route('destinasi.show', $d->slug) }}" target="_blank" class="w-full py-2 text-xs font-medium text-[#2B211E]/70 hover:text-[#703A3A] flex items-center justify-center gap-1">
                            <span>Pratinjau Publik</span>
                            <span class="material-symbols-outlined text-[14px]">open_in_new</span>
                        </a>
                    </div>

                </div>
            </div>
            @endforeach
        </div>
    @endif

    <!-- Modal Interaktif: Revisi / Penolakan -->
    <div x-show="modalType !== null" 
         x-cloak
         class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4 backdrop-blur-xs"
         @keydown.escape.window="modalType = null">
        
        <div class="bg-white max-w-lg w-full p-6 space-y-4 border border-[#2B211E]/20 shadow-xl" @click.away="modalType = null">
            <div class="flex items-center justify-between border-b border-[#2B211E]/10 pb-3">
                <h3 class="font-serif text-lg font-bold text-[#2B211E]" x-text="modalType === 'revision' ? 'Catatan Redaksi & Permintaan Revisi' : 'Alasan Penolakan Dossier Tapak'"></h3>
                <button @click="modalType = null" class="text-[#2B211E]/60 hover:text-[#2B211E]">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>

            <form :action="'/admin/destinasi/' + selectedDest + '/status'" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="action" :value="modalType">

                <div class="space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E]/70">
                        Catatan Kurator Lapangan (Akan Diterima Pengelola Tapak via WhatsApp/Portal)
                    </label>
                    <textarea name="notes" rows="4" required class="w-full p-3 border border-[#2B211E]/20 text-xs focus:ring-1 focus:ring-[#703A3A] outline-none" placeholder="Tuliskan catatan perbaikan berkas, penyesuaian kurikulum P5, atau alasan penolakan..."></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2 border-t border-[#2B211E]/10">
                    <button type="button" @click="modalType = null" class="rgs-btn-outline px-4 py-2 text-xs font-semibold uppercase">
                        Batal
                    </button>
                    <button type="submit" 
                            :class="modalType === 'revision' ? 'rgs-btn-primary' : 'bg-red-700 hover:bg-red-800 text-white'"
                            class="px-4 py-2 text-xs font-semibold uppercase tracking-wider">
                        <span x-text="modalType === 'revision' ? 'Kirim Catatan Revisi' : 'Konfirmasi Tolak'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
