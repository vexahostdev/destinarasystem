@extends('layouts.admin')

@section('title', 'Verifikasi Akun Institusi & Mitra')

@section('content')
<div class="space-y-8" x-data="{ activeTab: '{{ $tab }}', docModal: false, docTitle: '', docUrl: '', rejectModal: false, rejectAction: '', rejectTargetName: '' }">

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

    <!-- Tab Switcher & Sub-filter -->
    <div class="space-y-4 border-b border-[#2B211E]/12 pb-3">
        <div class="flex items-center gap-3">
            @if($canInstitutions)
            <a href="{{ route('admin.users.verify', ['tab' => 'institutions', 'status' => $status]) }}" 
               class="px-4 py-2.5 text-xs font-semibold uppercase tracking-wider flex items-center gap-2 transition-colors {{ $tab === 'institutions' ? 'bg-[#703A3A] text-white shadow-xs' : 'text-[#2B211E]/70 hover:bg-[#2B211E]/5' }}">
                <span class="material-symbols-outlined text-[16px]">school</span>
                <span>Institusi Pendidikan (Sekolah/Kampus)</span>
                <span class="px-1.5 py-0.2 bg-white/20 text-[10px] font-mono">{{ $pendingInstitutionsCount }}</span>
            </a>
            @endif

            @if($canPartners)
            <a href="{{ route('admin.users.verify', ['tab' => 'partners', 'status' => $status]) }}" 
               class="px-4 py-2.5 text-xs font-semibold uppercase tracking-wider flex items-center gap-2 transition-colors {{ $tab === 'partners' ? 'bg-[#703A3A] text-white shadow-xs' : 'text-[#2B211E]/70 hover:bg-[#2B211E]/5' }}">
                <span class="material-symbols-outlined text-[16px]">nature_people</span>
                <span>Mitra Pengelola Tapak Adat & Riset</span>
                <span class="px-1.5 py-0.2 bg-white/20 text-[10px] font-mono">{{ $pendingPartnersCount }}</span>
            </a>
            @endif
        </div>

        <!-- Sub-filter Status -->
        <div class="flex items-center gap-2 overflow-x-auto text-xs font-semibold uppercase tracking-wider">
            <a href="{{ route('admin.users.verify', ['tab' => $tab, 'status' => 'pending']) }}"
               class="px-3 py-1.5 transition-colors {{ ($status ?? '') === 'pending' ? 'bg-[#703A3A] text-white' : 'text-[#2B211E]/70 hover:bg-[#2B211E]/5 bg-white border border-[#2B211E]/10' }}">
                Menunggu Validasi ({{ $tab === 'institutions' ? $pendingInstitutionsCount : $pendingPartnersCount }})
            </a>
            <a href="{{ route('admin.users.verify', ['tab' => $tab, 'status' => 'unverified']) }}"
               class="px-3 py-1.5 transition-colors {{ ($status ?? '') === 'unverified' ? 'bg-[#703A3A] text-white' : 'text-[#2B211E]/70 hover:bg-[#2B211E]/5 bg-white border border-[#2B211E]/10' }}">
                Belum Mengajukan ({{ $tab === 'institutions' ? $unverifiedInstitutionsCount : $unverifiedPartnersCount }})
            </a>
            <a href="{{ route('admin.users.verify', ['tab' => $tab, 'status' => 'verified']) }}"
               class="px-3 py-1.5 transition-colors {{ ($status ?? '') === 'verified' ? 'bg-[#703A3A] text-white' : 'text-[#2B211E]/70 hover:bg-[#2B211E]/5 bg-white border border-[#2B211E]/10' }}">
                Terverifikasi Sah
            </a>
            <a href="{{ route('admin.users.verify', ['tab' => $tab]) }}"
               class="px-3 py-1.5 transition-colors {{ empty($status) ? 'bg-[#703A3A] text-white' : 'text-[#2B211E]/70 hover:bg-[#2B211E]/5 bg-white border border-[#2B211E]/10' }}">
                Semua
            </a>
        </div>
    </div>

    <!-- Content Tab 1: Institusi Pendidikan -->
    @if($tab === 'institutions')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="font-serif text-lg font-bold text-[#2B211E]">Daftar Institusi Pendidikan Terdaftar</h2>
            <span class="text-xs text-[#2B211E]/60 font-mono">{{ $institutions->total() }} Institusi</span>
        </div>

        <div class="grid grid-cols-1 gap-4">
            @forelse($institutions as $inst)
            @php
                $isPending = $inst->verification_status === \App\Enums\VerificationStatus::PENDING;
                $isVerified = $inst->verification_status === \App\Enums\VerificationStatus::VERIFIED;
                $isUnverified = $inst->verification_status === \App\Enums\VerificationStatus::UNVERIFIED;
            @endphp
            <div class="rgs-card bg-white p-6 border-l-4 {{ $isPending ? 'border-l-blue-600' : ($isVerified ? 'border-l-emerald-600' : ($isUnverified ? 'border-l-gray-400' : 'border-l-red-600')) }}">
                <div class="flex flex-col lg:flex-row items-start justify-between gap-6">
                    
                    <div class="space-y-3 flex-1">
                        <div class="flex flex-wrap items-center gap-3">
                            <h3 class="font-serif text-xl font-bold text-[#2B211E]">{{ $inst->institution_name }}</h3>
                            <span class="px-2 py-0.5 bg-[#faf6f0] border border-[#2B211E]/15 text-[10px] font-mono font-bold uppercase">
                                {{ $inst->institution_type }}
                            </span>
                            @if($isVerified)
                                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-900 border border-emerald-300 text-[10px] font-mono font-bold flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[12px]">verified</span>
                                    <span>TERVERIFIKASI</span>
                                </span>
                            @elseif($isPending)
                                <span class="px-2 py-0.5 bg-blue-100 text-blue-900 border border-blue-300 text-[10px] font-mono font-bold">
                                    MENUNGGU VALIDASI
                                </span>
                            @elseif($isUnverified)
                                <span class="px-2 py-0.5 bg-gray-100 text-gray-700 border border-gray-300 text-[10px] font-mono font-bold">
                                    BELUM MENGAJUKAN
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
                                <span class="font-medium text-[#2B211E]">{{ $inst->city }}, {{ $inst->province?->name ?? ($inst->province ?? '-') }}</span>
                            </div>
                            <div>
                                <span class="text-[#2B211E]/60 block text-[10px] font-mono uppercase">PIC Penanggung Jawab:</span>
                                <span class="font-medium text-[#2B211E]">{{ $inst->pic_name ?? '-' }} ({{ $inst->pic_phone ?? '-' }})</span>
                            </div>
                        </div>

                        <p class="text-xs text-[#2B211E]/70">
                            <strong>Alamat Resmi:</strong> {{ $inst->address ?? '-' }}
                        </p>

                        @if($inst->verification_note)
                        <div class="p-2.5 bg-red-50 border border-red-200 text-xs text-red-700">
                            <strong>Catatan Verifikasi:</strong> {{ $inst->verification_note }}
                        </div>
                        @endif

                        <!-- Berkas Pendukung Institusi -->
                        <div class="flex flex-wrap items-center gap-2 pt-2 text-xs">
                            <span class="text-[#2B211E]/60 font-semibold">Berkas Pendukung:</span>
                            @forelse($inst->documents as $doc)
                                <a href="{{ route('admin.users.institutionDocument.download', $doc->id) }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#703A3A] hover:underline bg-white px-2 py-1 border border-[#2B211E]/15">
                                    <span class="material-symbols-outlined text-[16px]">description</span>
                                    <span>{{ $doc->original_name ?? basename($doc->file_path) }} ({{ strtoupper(str_replace('_', ' ', $doc->type)) }})</span>
                                    <span class="material-symbols-outlined text-[14px]">download</span>
                                </a>
                            @empty
                                @if($inst->letterhead_path)
                                    <span class="text-xs text-[#2B211E]/70 font-mono">{{ basename($inst->letterhead_path) }}</span>
                                @else
                                    <span class="text-xs text-[#2B211E]/50 italic">Belum ada berkas diunggah</span>
                                @endif
                            @endforelse
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-row lg:flex-col items-center justify-center gap-2 shrink-0 w-full lg:w-44 border-t lg:border-t-0 pt-4 lg:pt-0 border-[#2B211E]/10">
                        @if($isPending || $isUnverified)
                        <form action="{{ route('admin.users.updateVerification', ['type' => 'institution', 'id' => $inst->id]) }}" method="POST" class="w-full">
                            @csrf
                            <input type="hidden" name="action" value="verify">
                            <button type="submit" class="rgs-btn-primary w-full py-2 text-xs font-semibold uppercase tracking-wider flex items-center justify-center gap-1.5 shadow-xs">
                                <span class="material-symbols-outlined text-[16px]">verified</span>
                                <span>Verifikasi Sah</span>
                            </button>
                        </form>
                        @endif

                        @if($isVerified || $isPending)
                        <button type="button" 
                                @click="rejectAction = '{{ route('admin.users.updateVerification', ['type' => 'institution', 'id' => $inst->id]) }}'; rejectTargetName = '{{ addslashes($inst->institution_name) }}'; rejectModal = true"
                                class="rgs-btn-outline w-full py-2 text-xs font-semibold uppercase tracking-wider flex items-center justify-center gap-1.5 text-red-700 hover:bg-red-50 border-red-200">
                            <span class="material-symbols-outlined text-[16px]">cancel</span>
                            <span>Tolak / Cabut</span>
                        </button>
                        @endif
                    </div>

                </div>
            </div>
            @empty
            <div class="rgs-card p-8 text-center bg-white text-xs text-[#2B211E]/60">
                Tidak ada institusi yang ditemukan pada filter ini.
            </div>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $institutions->links() }}
        </div>
    </div>
    @endif

    <!-- Content Tab 2: Mitra Pengelola Tapak Adat -->
    @if($tab === 'partners')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="font-serif text-lg font-bold text-[#2B211E]">Daftar Lembaga Adat & Mitra Pengelola</h2>
            <span class="text-xs text-[#2B211E]/60 font-mono">{{ $partners->total() }} Lembaga</span>
        </div>

        <div class="grid grid-cols-1 gap-4">
            @forelse($partners as $p)
            @php
                $isPending = $p->verification_status === \App\Enums\VerificationStatus::PENDING;
                $isVerified = $p->verification_status === \App\Enums\VerificationStatus::VERIFIED;
                $isUnverified = $p->verification_status === \App\Enums\VerificationStatus::UNVERIFIED;
            @endphp
            <div class="rgs-card bg-white p-6 border-l-4 {{ $isPending ? 'border-l-blue-600' : ($isVerified ? 'border-l-emerald-600' : ($isUnverified ? 'border-l-gray-400' : 'border-l-red-600')) }}">
                <div class="flex flex-col lg:flex-row items-start justify-between gap-6">
                    
                    <div class="space-y-3 flex-1">
                        <div class="flex flex-wrap items-center gap-3">
                            <h3 class="font-serif text-xl font-bold text-[#2B211E]">{{ $p->organization_name }}</h3>
                            <span class="px-2 py-0.5 bg-[#faf6f0] border border-[#2B211E]/15 text-[10px] font-mono font-bold uppercase">
                                {{ $p->organizationType?->name ?? ($p->organization_type ?? 'Lembaga Mitra') }}
                            </span>
                            @if($isVerified)
                                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-900 border border-emerald-300 text-[10px] font-mono font-bold flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[12px]">verified</span>
                                    <span>LEGALITAS ADAT SAH</span>
                                </span>
                            @elseif($isPending)
                                <span class="px-2 py-0.5 bg-blue-100 text-blue-900 border border-blue-300 text-[10px] font-mono font-bold">
                                    MENUNGGU VALIDASI
                                </span>
                            @elseif($isUnverified)
                                <span class="px-2 py-0.5 bg-gray-100 text-gray-700 border border-gray-300 text-[10px] font-mono font-bold">
                                    BELUM MENGAJUKAN
                                </span>
                            @else
                                <span class="px-2 py-0.5 bg-red-100 text-red-900 border border-red-300 text-[10px] font-mono font-bold">
                                    DITOLAK / PERBAIKAN
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
                                <span class="font-mono font-bold text-[#2B211E]">{{ $p->bank_account_number ?? '-' }}</span>
                                @if($p->bank_account_holder)
                                    <span class="text-[10px] text-[#2B211E]/60 block">a/n {{ $p->bank_account_holder }}</span>
                                @endif
                            </div>
                        </div>

                        <p class="text-xs text-[#2B211E]/70">
                            <strong>Alamat & Wilayah:</strong> {{ $p->address ?? '-' }} ({{ $p->city ?? '-' }}, {{ $p->province?->name ?? '-' }})
                        </p>

                        @if($p->verification_note)
                        <div class="p-2.5 bg-red-50 border border-red-200 text-xs text-red-700">
                            <strong>Catatan Verifikasi:</strong> {{ $p->verification_note }}
                        </div>
                        @endif

                        <!-- Berkas Legalitas Mitra -->
                        <div class="flex flex-wrap items-center gap-2 pt-2 text-xs">
                            <span class="text-[#2B211E]/60 font-semibold">Berkas Legalitas:</span>
                            @forelse($p->documents as $doc)
                                <a href="{{ route('admin.users.partnerDocument.download', $doc->id) }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#703A3A] hover:underline bg-white px-2 py-1 border border-[#2B211E]/15">
                                    <span class="material-symbols-outlined text-[16px]">description</span>
                                    <span>{{ $doc->original_name ?? basename($doc->file_path) }} ({{ strtoupper(str_replace('_', ' ', $doc->type)) }})</span>
                                    <span class="material-symbols-outlined text-[14px]">download</span>
                                </a>
                            @empty
                                <span class="text-xs text-[#2B211E]/50 italic">Belum ada berkas legalitas diunggah</span>
                            @endforelse
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-row lg:flex-col items-center justify-center gap-2 shrink-0 w-full lg:w-44 border-t lg:border-t-0 pt-4 lg:pt-0 border-[#2B211E]/10">
                        @if($isPending || $isUnverified)
                        <form action="{{ route('admin.users.updateVerification', ['type' => 'partner', 'id' => $p->id]) }}" method="POST" class="w-full">
                            @csrf
                            <input type="hidden" name="action" value="verify">
                            <button type="submit" class="rgs-btn-primary w-full py-2 text-xs font-semibold uppercase tracking-wider flex items-center justify-center gap-1.5 shadow-xs">
                                <span class="material-symbols-outlined text-[16px]">verified</span>
                                <span>Verifikasi Sah</span>
                            </button>
                        </form>
                        @endif

                        @if($isVerified || $isPending)
                        <button type="button" 
                                @click="rejectAction = '{{ route('admin.users.updateVerification', ['type' => 'partner', 'id' => $p->id]) }}'; rejectTargetName = '{{ addslashes($p->organization_name) }}'; rejectModal = true"
                                class="rgs-btn-outline w-full py-2 text-xs font-semibold uppercase tracking-wider flex items-center justify-center gap-1.5 text-red-700 hover:bg-red-50 border-red-200">
                            <span class="material-symbols-outlined text-[16px]">cancel</span>
                            <span>Tolak / Cabut</span>
                        </button>
                        @endif
                    </div>

                </div>
            </div>
            @empty
            <div class="rgs-card p-8 text-center bg-white text-xs text-[#2B211E]/60">
                Tidak ada mitra yang ditemukan pada filter ini.
            </div>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $partners->links() }}
        </div>
    </div>
    @endif

    <!-- Modal Tolak Verifikasi -->
    <div x-show="rejectModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4">
            <h3 class="text-base font-bold text-gray-900">Tolak Verifikasi Akun</h3>
            <p class="text-xs text-gray-500">Berikan catatan alasan penolakan verifikasi untuk <strong x-text="rejectTargetName"></strong> agar pengguna dapat memperbarui dokumen legalitasnya.</p>
            <form :action="rejectAction" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="action" value="reject">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Alasan Penolakan <span class="text-rose-500">*</span></label>
                    <textarea name="note" rows="3" required minlength="5" maxlength="1000"
                              placeholder="Contoh: Dokumen legalitas kedaluwarsa atau NPSN tidak terdaftar di pangkalan data Kemendikbud."
                              class="w-full text-xs rounded-xl border-gray-300 focus:border-[#703A3A] focus:ring-[#703A3A]"></textarea>
                    @error('note')
                        <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="rejectModal = false"
                            class="px-4 py-2 border border-gray-200 rounded-full text-xs font-bold text-gray-700 hover:bg-gray-50">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-full text-xs font-bold uppercase tracking-wider">
                        Tolak Verifikasi
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
