@extends('layouts.admin')

@section('title', 'Verifikasi Akun Institusi & Mitra')

@section('content')
<div class="space-y-8" x-data="{ activeTab: '{{ $tab }}', docModal: false, docTitle: '', docUrl: '' }">

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-[#2B211E]/12 pb-6">
        <div>
            <div class="flex items-center gap-2 text-xs font-mono font-semibold uppercase tracking-wider text-[#2B3A4A] mb-1">
                <span>Integritas Ekosistem Destinara</span>
                <span>•</span>
                <span>Kredensial Resmi</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#2B211E]">
                Verifikasi Akun Mitra & Institusi Buyer
            </h1>
            <p class="text-sm text-[#2B211E]/70 max-w-2xl">
                Validasi pangkalan data legalitas: nomor NPSN/NIDN instansi pendidikan untuk pertanggungjawaban dana BOS/BOPTN, serta SK lembaga adat dan rekening pencairan mitra.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <span class="px-3 py-1.5 bg-blue-100 text-blue-900 border border-blue-300 text-xs font-semibold flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
                <span>{{ $pendingInstitutionsCount + $pendingPartnersCount }} Pengguna Menunggu Validasi</span>
            </span>
        </div>
    </div>

    <!-- Tab Switcher -->
    <div class="flex items-center gap-3 border-b border-[#2B211E]/12 pb-2">
        <a href="{{ route('admin.users.verify', ['tab' => 'institutions']) }}" 
           class="px-4 py-2.5 text-xs font-semibold uppercase tracking-wider flex items-center gap-2 transition-colors {{ $tab === 'institutions' ? 'bg-[#703A3A] text-white shadow-xs' : 'text-[#2B211E]/70 hover:bg-[#2B211E]/5' }}">
            <span class="material-symbols-outlined text-[16px]">school</span>
            <span>Institusi Pendidikan (Sekolah/Kampus)</span>
            <span class="px-1.5 py-0.2 bg-white/20 text-[10px] font-mono">{{ $pendingInstitutionsCount }}</span>
        </a>

        <a href="{{ route('admin.users.verify', ['tab' => 'partners']) }}" 
           class="px-4 py-2.5 text-xs font-semibold uppercase tracking-wider flex items-center gap-2 transition-colors {{ $tab === 'partners' ? 'bg-[#703A3A] text-white shadow-xs' : 'text-[#2B211E]/70 hover:bg-[#2B211E]/5' }}">
            <span class="material-symbols-outlined text-[16px]">nature_people</span>
            <span>Mitra Pengelola Tapak Adat & Riset</span>
            <span class="px-1.5 py-0.2 bg-white/20 text-[10px] font-mono">{{ $pendingPartnersCount }}</span>
        </a>
    </div>

    <!-- Content Tab 1: Institusi Pendidikan -->
    @if($tab === 'institutions')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="font-serif text-lg font-bold text-[#2B211E]">Daftar Institusi Pendidikan Terdaftar</h2>
            <span class="text-xs text-[#2B211E]/60 font-mono">{{ count($institutions) }} Institusi</span>
        </div>

        <div class="grid grid-cols-1 gap-4">
            @foreach($institutions as $inst)
            <div class="rgs-card bg-white p-6 border-l-4 {{ $inst->verification_status === 'pending' ? 'border-l-blue-600' : ($inst->verification_status === 'verified' ? 'border-l-emerald-600' : 'border-l-red-600') }}">
                <div class="flex flex-col lg:flex-row items-start justify-between gap-6">
                    
                    <div class="space-y-3 flex-1">
                        <div class="flex flex-wrap items-center gap-3">
                            <h3 class="font-serif text-xl font-bold text-[#2B211E]">{{ $inst->institution_name }}</h3>
                            <span class="px-2 py-0.5 bg-[#faf6f0] border border-[#2B211E]/15 text-[10px] font-mono font-bold uppercase">
                                {{ $inst->institution_type }}
                            </span>
                            @if($inst->verification_status === 'verified')
                                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-900 border border-emerald-300 text-[10px] font-mono font-bold flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[12px]">verified</span>
                                    <span>TERVERIFIKASI BOS</span>
                                </span>
                            @elseif($inst->verification_status === 'pending')
                                <span class="px-2 py-0.5 bg-blue-100 text-blue-900 border border-blue-300 text-[10px] font-mono font-bold">
                                    MENUNGGU VALIDASI
                                </span>
                            @else
                                <span class="px-2 py-0.5 bg-red-100 text-red-900 border border-red-300 text-[10px] font-mono font-bold">
                                    DITOLAK / REVISI
                                </span>
                            @endif
                        </div>

                        <!-- Detail Legalitas & PIC -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 text-xs bg-[#faf6f0] p-3 border border-[#2B211E]/10">
                            <div>
                                <span class="text-[#2B211E]/60 block text-[10px] font-mono uppercase">Nomor NPSN / NIDN:</span>
                                <span class="font-mono font-bold text-[#703A3A] text-sm">{{ $inst->npsn ?? 'Tidak terisi' }}</span>
                            </div>
                            <div>
                                <span class="text-[#2B211E]/60 block text-[10px] font-mono uppercase">Domisili / Wilayah:</span>
                                <span class="font-medium text-[#2B211E]">{{ $inst->city }}, {{ $inst->province?->name ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="text-[#2B211E]/60 block text-[10px] font-mono uppercase">PIC Penanggung Jawab:</span>
                                <span class="font-medium text-[#2B211E]">{{ $inst->pic_name ?? '-' }} ({{ $inst->pic_phone ?? '-' }})</span>
                            </div>
                        </div>

                        <p class="text-xs text-[#2B211E]/70">
                            <strong>Alamat Resmi:</strong> {{ $inst->address ?? '-' }}
                        </p>

                        <!-- Berkas Verifikasi -->
                        <div class="flex items-center gap-3 pt-2 text-xs">
                            <span class="text-[#2B211E]/60">Berkas Pendukung:</span>
                            <button type="button" 
                                    @click="docModal = true; docTitle = 'Berkas Legalitas {{ $inst->institution_name }} (NPSN: {{ $inst->npsn }})'"
                                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#703A3A] hover:underline bg-white px-2 py-1 border border-[#2B211E]/15">
                                <span class="material-symbols-outlined text-[16px]">description</span>
                                <span>{{ $inst->official_letterhead ? basename($inst->official_letterhead) : 'Belum unggah berkas' }}</span>
                                <span class="material-symbols-outlined text-[14px]">visibility</span>
                            </button>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-row lg:flex-col items-center justify-center gap-2 shrink-0 w-full lg:w-44 border-t lg:border-t-0 pt-4 lg:pt-0 border-[#2B211E]/10">
                        @if($inst->verification_status === 'pending' || $inst->verification_status === 'unverified')
                        <form action="{{ route('admin.users.updateVerification', ['type' => 'institution', 'id' => $inst->id]) }}" method="POST" class="w-full">
                            @csrf
                            <input type="hidden" name="action" value="verify">
                            <button type="submit" class="rgs-btn-primary w-full py-2 text-xs font-semibold uppercase tracking-wider flex items-center justify-center gap-1.5 shadow-xs">
                                <span class="material-symbols-outlined text-[16px]">verified</span>
                                <span>Verifikasi Sah</span>
                            </button>
                        </form>
                        @endif

                        @if($inst->verification_status === 'verified' || $inst->verification_status === 'pending')
                        <form action="{{ route('admin.users.updateVerification', ['type' => 'institution', 'id' => $inst->id]) }}" method="POST" class="w-full">
                            @csrf
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" class="rgs-btn-outline w-full py-2 text-xs font-semibold uppercase tracking-wider flex items-center justify-center gap-1.5 text-red-700 hover:bg-red-50 border-red-200">
                                <span class="material-symbols-outlined text-[16px]">cancel</span>
                                <span>Tolak / Cabut</span>
                            </button>
                        </form>
                        @endif
                    </div>

                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Content Tab 2: Mitra Pengelola Tapak Adat -->
    @if($tab === 'partners')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="font-serif text-lg font-bold text-[#2B211E]">Daftar Lembaga Adat & Mitra Pengelola</h2>
            <span class="text-xs text-[#2B211E]/60 font-mono">{{ count($partners) }} Lembaga</span>
        </div>

        <div class="grid grid-cols-1 gap-4">
            @foreach($partners as $p)
            <div class="rgs-card bg-white p-6 border-l-4 {{ $p->verification_status === 'pending' ? 'border-l-blue-600' : ($p->verification_status === 'verified' ? 'border-l-emerald-600' : 'border-l-red-600') }}">
                <div class="flex flex-col lg:flex-row items-start justify-between gap-6">
                    
                    <div class="space-y-3 flex-1">
                        <div class="flex flex-wrap items-center gap-3">
                            <h3 class="font-serif text-xl font-bold text-[#2B211E]">{{ $p->organization_name }}</h3>
                            <span class="px-2 py-0.5 bg-[#faf6f0] border border-[#2B211E]/15 text-[10px] font-mono font-bold uppercase">
                                {{ str_replace('_', ' ', $p->organization_type) }}
                            </span>
                            @if($p->verification_status === 'verified')
                                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-900 border border-emerald-300 text-[10px] font-mono font-bold flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[12px]">verified</span>
                                    <span>LEGALITAS ADAT SAH</span>
                                </span>
                            @elseif($p->verification_status === 'pending')
                                <span class="px-2 py-0.5 bg-blue-100 text-blue-900 border border-blue-300 text-[10px] font-mono font-bold">
                                    MENUNGGU VALIDASI
                                </span>
                            @else
                                <span class="px-2 py-0.5 bg-red-100 text-red-900 border border-red-300 text-[10px] font-mono font-bold">
                                    DITOLAK
                                </span>
                            @endif
                        </div>

                        <!-- Legalitas & Rekening Payout -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 text-xs bg-[#faf6f0] p-3 border border-[#2B211E]/10">
                            <div>
                                <span class="text-[#2B211E]/60 block text-[10px] font-mono uppercase">SK Penetapan / Akta:</span>
                                <span class="font-mono font-bold text-[#2B211E]">{{ $p->legal_document_number ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="text-[#2B211E]/60 block text-[10px] font-mono uppercase">Bank Rekening Penampung:</span>
                                <span class="font-bold text-[#51634b]">{{ $p->bank?->name ?? ($p->bank_name ?? '-') }}</span>
                            </div>
                            <div>
                                <span class="text-[#2B211E]/60 block text-[10px] font-mono uppercase">Nomor Rekening Resmi:</span>
                                <span class="font-mono font-bold text-[#2B211E]">{{ $p->bank_account_number }}</span>
                                <span class="text-[10px] text-[#2B211E]/60 block">a/n {{ $p->bank_account_holder }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 text-xs text-[#2B211E]/70">
                            <span class="material-symbols-outlined text-[16px] text-emerald-700">security</span>
                            <span>Rekening penampung telah sesuai protokol pencairan dana komisi 90% hak masyarakat adat.</span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-row lg:flex-col items-center justify-center gap-2 shrink-0 w-full lg:w-44 border-t lg:border-t-0 pt-4 lg:pt-0 border-[#2B211E]/10">
                        @if($p->verification_status === 'pending' || $p->verification_status === 'unverified')
                        <form action="{{ route('admin.users.updateVerification', ['type' => 'partner', 'id' => $p->id]) }}" method="POST" class="w-full">
                            @csrf
                            <input type="hidden" name="action" value="verify">
                            <button type="submit" class="rgs-btn-primary w-full py-2 text-xs font-semibold uppercase tracking-wider flex items-center justify-center gap-1.5 shadow-xs">
                                <span class="material-symbols-outlined text-[16px]">verified</span>
                                <span>Verifikasi Sah</span>
                            </button>
                        </form>
                        @endif

                        @if($p->verification_status === 'verified' || $p->verification_status === 'pending')
                        <form action="{{ route('admin.users.updateVerification', ['type' => 'partner', 'id' => $p->id]) }}" method="POST" class="w-full">
                            @csrf
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" class="rgs-btn-outline w-full py-2 text-xs font-semibold uppercase tracking-wider flex items-center justify-center gap-1.5 text-red-700 hover:bg-red-50 border-red-200">
                                <span class="material-symbols-outlined text-[16px]">cancel</span>
                                <span>Tolak / Cabut</span>
                            </button>
                        </form>
                        @endif
                    </div>

                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Modal Pratinjau Dokumen Legalitas -->
    <div x-show="docModal" 
         x-cloak
         class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4 backdrop-blur-xs"
         @keydown.escape.window="docModal = false">
        
        <div class="bg-white max-w-2xl w-full p-6 space-y-4 border border-[#2B211E]/20 shadow-2xl" @click.away="docModal = false">
            <div class="flex items-center justify-between border-b border-[#2B211E]/10 pb-3">
                <h3 class="font-serif text-lg font-bold text-[#2B211E]" x-text="docTitle"></h3>
                <button @click="docModal = false" class="text-[#2B211E]/60 hover:text-[#2B211E]">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>

            <!-- Visual Dokumen Arsip Resmi -->
            <div class="bg-[#faf6f0] p-6 border border-[#2B211E]/15 font-mono text-xs space-y-3">
                <div class="text-center border-b border-[#2B211E]/20 pb-3">
                    <p class="font-bold text-sm text-[#2B211E]">KEMENTERIAN PENDIDIKAN, KEBUDAYAAN, RISET, DAN TEKNOLOGI</p>
                    <p class="text-[10px] text-[#2B211E]/70">PANGKALAN DATA POKOK PENDIDIKAN (DAPODIK) REPUBLIK INDONESIA</p>
                </div>
                <div class="space-y-1 py-2 text-[11px]">
                    <div class="flex justify-between"><span>STATUS OPERASIONAL:</span><span class="text-emerald-800 font-bold">TERDAFTAR RESMI & AKTIF</span></div>
                    <div class="flex justify-between"><span>KODE REGISTRASI BOS:</span><span>BOS-2026-DIKTI-VERIFIED</span></div>
                    <div class="flex justify-between"><span>STATUS AKREDITASI:</span><span>A (UNGGUL)</span></div>
                </div>
                <div class="p-3 bg-white border border-[#2B211E]/10 text-center text-xs text-emerald-800 font-bold flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[16px]">verified</span>
                    <span>Tanda Tangan Digital & Verifikasi Pangkalan Data Sukses</span>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-[#2B211E]/10">
                <button type="button" @click="docModal = false" class="rgs-btn-primary px-4 py-2 text-xs font-semibold uppercase">
                    Tutup Pratinjau
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
