@extends('layouts.superadmin')

@section('title', 'Manajemen Akses & Tindakan Kritis')

@section('content')
<div class="space-y-8" x-data="{ 
    criticalModal: false, 
    criticalAction: '', 
    criticalTitle: '', 
    criticalDesc: '', 
    confirmText: '', 
    targetUserId: null 
}">

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-[#2B211E]/12 pb-6">
        <div>
            <div class="flex items-center gap-2 text-xs font-mono font-semibold uppercase tracking-wider text-[#4A1E1E] mb-1">
                <span>Otoritas Root & Tata Kelola Identitas</span>
                <span>•</span>
                <span>Security Access Control</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#2B211E]">
                Manajemen Akses & Tindakan Kritis Ekosistem
            </h1>
            <p class="text-sm text-[#2B211E]/70 max-w-2xl">
                Otorisasi akun seluruh ekosistem (Buyer Institusi, Mitra Adat, Admin Operasional) dan eksekusi instruksi tingkat tinggi dengan protokol pengamanan berlapis.
            </p>
        </div>

        <!-- Tombol Tindakan Kritis Terbuka -->
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" 
                    @click="criticalAction = 'clear_cache'; criticalTitle = 'Pembersihan Cache Sistem Darurat'; criticalDesc = 'Tindakan ini akan mengosongkan seluruh application cache, compiled blade views, dan route config secara instan di server.'; confirmText = ''; criticalModal = true"
                    class="px-3 py-2 bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-300 text-xs font-semibold uppercase tracking-wider flex items-center gap-1.5 transition-colors">
                <span class="material-symbols-outlined text-[16px]">cached</span>
                <span>Flush Cache Darurat</span>
            </button>

            <button type="button" 
                    @click="criticalAction = 'toggle_maintenance'; criticalTitle = 'Toggle Mode Pemeliharaan Darurat'; criticalDesc = 'Tindakan ini akan membatasi akses publik platform dan menampilkan layar pemeliharaan sistem sementara waktu.'; confirmText = ''; criticalModal = true"
                    class="px-3 py-2 bg-red-50 hover:bg-red-100 text-red-900 border border-red-300 text-xs font-semibold uppercase tracking-wider flex items-center gap-1.5 transition-colors">
                <span class="material-symbols-outlined text-[16px]">power_settings_new</span>
                <span>Maintenance Mode</span>
            </button>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
        <!-- Role Pills -->
        <div class="flex items-center gap-2 overflow-x-auto text-xs font-semibold uppercase tracking-wider">
            <a href="{{ route('superadmin.users', ['role' => 'all', 'search' => $search]) }}" 
               class="px-3 py-1.5 transition-colors {{ $role === 'all' ? 'bg-[#4A1E1E] text-white shadow-xs' : 'bg-white text-[#2B211E]/70 border border-[#2B211E]/15 hover:bg-[#2B211E]/5' }}">
                Semua ({{ $roleCounts['all'] }})
            </a>
            <a href="{{ route('superadmin.users', ['role' => 'buyer', 'search' => $search]) }}" 
               class="px-3 py-1.5 transition-colors {{ $role === 'buyer' ? 'bg-[#4A1E1E] text-white shadow-xs' : 'bg-white text-[#2B211E]/70 border border-[#2B211E]/15 hover:bg-[#2B211E]/5' }}">
                Buyer Institusi ({{ $roleCounts['buyer'] }})
            </a>
            <a href="{{ route('superadmin.users', ['role' => 'partner', 'search' => $search]) }}" 
               class="px-3 py-1.5 transition-colors {{ $role === 'partner' ? 'bg-[#4A1E1E] text-white shadow-xs' : 'bg-white text-[#2B211E]/70 border border-[#2B211E]/15 hover:bg-[#2B211E]/5' }}">
                Mitra Adat ({{ $roleCounts['partner'] }})
            </a>
            <a href="{{ route('superadmin.users', ['role' => 'admin', 'search' => $search]) }}" 
               class="px-3 py-1.5 transition-colors {{ $role === 'admin' ? 'bg-[#4A1E1E] text-white shadow-xs' : 'bg-white text-[#2B211E]/70 border border-[#2B211E]/15 hover:bg-[#2B211E]/5' }}">
                Admin Operasional ({{ $roleCounts['admin'] }})
            </a>
            <a href="{{ route('superadmin.users', ['role' => 'superadmin', 'search' => $search]) }}" 
               class="px-3 py-1.5 transition-colors {{ $role === 'superadmin' ? 'bg-[#4A1E1E] text-white shadow-xs' : 'bg-white text-[#2B211E]/70 border border-[#2B211E]/15 hover:bg-[#2B211E]/5' }}">
                Super Admin ({{ $roleCounts['superadmin'] }})
            </a>
        </div>

        <!-- Search Input -->
        <form method="GET" action="{{ route('superadmin.users') }}" class="flex items-center gap-2">
            <input type="hidden" name="role" value="{{ $role }}">
            <div class="relative w-full sm:w-64">
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama atau email..." 
                       class="w-full pl-8 pr-3 py-1.5 text-xs border border-[#2B211E]/20 bg-white focus:ring-1 focus:ring-[#703A3A] outline-none">
                <span class="material-symbols-outlined text-[16px] absolute left-2 top-2 text-[#2B211E]/40">search</span>
            </div>
            <button type="submit" class="rgs-btn-dark px-3 py-1.5 text-xs font-semibold uppercase">
                Cari
            </button>
        </form>
    </div>

    <!-- Master User Table -->
    <div class="rgs-card bg-white overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-[#faf6f0] border-b border-[#2B211E]/12 text-[#2B211E]/60 uppercase tracking-wider font-mono">
                        <th class="py-3 px-4 font-semibold">Pengguna & Identitas</th>
                        <th class="py-3 px-4 font-semibold text-center">Peran Sistem</th>
                        <th class="py-3 px-4 font-semibold">Entitas Terkait</th>
                        <th class="py-3 px-4 font-semibold">Kontak & Telepon</th>
                        <th class="py-3 px-4 font-semibold text-center">Status Akses</th>
                        <th class="py-3 px-4 font-semibold text-center">Aksi Otoritas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#2B211E]/08">
                    @forelse($users as $u)
                    <tr class="hover:bg-[#faf6f0]/60 transition-colors">
                        <td class="py-3.5 px-4">
                            <span class="font-bold text-[#2B211E] block">{{ $u->name }}</span>
                            <span class="text-[11px] font-mono text-[#2B211E]/60">{{ $u->email }}</span>
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            @if($u->role === 'superadmin')
                                <span class="px-2 py-0.5 bg-[#4A1E1E] text-white text-[10px] font-mono font-bold uppercase">Super Admin</span>
                            @elseif($u->role === 'admin')
                                <span class="px-2 py-0.5 bg-[#2B3A4A] text-white text-[10px] font-mono font-bold uppercase">Admin Ops</span>
                            @elseif($u->role === 'partner')
                                <span class="px-2 py-0.5 bg-[#51634b] text-white text-[10px] font-mono font-bold uppercase">Mitra Adat</span>
                            @else
                                <span class="px-2 py-0.5 bg-[#703A3A] text-white text-[10px] font-mono font-bold uppercase">Buyer</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4">
                            @if($u->institution)
                                <span class="font-medium text-[#2B211E] block">{{ $u->institution->institution_name }}</span>
                                <span class="text-[10px] text-[#2B211E]/60 font-mono">NPSN: {{ $u->institution->npsn ?? '-' }}</span>
                            @elseif($u->partnerProfile)
                                <span class="font-medium text-[#2B211E] block">{{ $u->partnerProfile->organization_name }}</span>
                                <span class="text-[10px] text-[#51634b] font-mono font-bold">Terverifikasi Adat</span>
                            @else
                                <span class="text-[11px] text-[#2B211E]/50 italic">Internal Staf</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 font-mono text-[#2B211E]/80">
                            {{ $u->phone ?? '-' }}
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            <span class="px-2 py-0.5 bg-emerald-100 text-emerald-900 border border-emerald-300 text-[10px] font-mono font-bold uppercase">
                                AKTIF
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                @if($u->role !== 'superadmin')
                                <button type="button" 
                                        @click="criticalAction = 'suspend_user'; targetUserId = {{ $u->id }}; criticalTitle = 'Bekukan Akun {{ addslashes($u->name) }}'; criticalDesc = 'Akun ini akan kehilangan seluruh hak akses login ke sistem secara instan.'; confirmText = ''; criticalModal = true"
                                        title="Bekukan Akun" 
                                        class="p-1 text-[#2B211E]/60 hover:text-red-700 transition-colors">
                                    <span class="material-symbols-outlined text-[16px]">block</span>
                                </button>
                                @endif
                                <button type="button" 
                                        @click="criticalAction = 'reset_password'; targetUserId = {{ $u->id }}; criticalTitle = 'Setel Ulang Sandi: {{ addslashes($u->name) }}'; criticalDesc = 'Instruksi pemulihan kredensial resmi akan dipicu untuk akun {{ $u->email }}.'; confirmText = ''; criticalModal = true"
                                        title="Reset Password" 
                                        class="p-1 text-[#2B211E]/60 hover:text-[#703A3A] transition-colors">
                                    <span class="material-symbols-outlined text-[16px]">lock_reset</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-[#2B211E]/50">Tidak ada akun yang cocok.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL DIALOG TINDAKAN KRITIS (POPUP DARK BACKDROP SESUAI FIGMA) -->
    <div x-show="criticalModal" 
         x-cloak
         class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm flex items-center justify-center p-4"
         @keydown.escape.window="criticalModal = false">
        
        <div class="bg-[#1A1211] border-2 border-red-600/80 max-w-md w-full p-6 text-white space-y-5 shadow-2xl" @click.away="criticalModal = false">
            
            <div class="flex items-start justify-between border-b border-white/10 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-red-600/20 border border-red-500/40 flex items-center justify-center text-red-500">
                        <span class="material-symbols-outlined text-2xl">warning</span>
                    </div>
                    <div>
                        <span class="text-[10px] font-mono tracking-widest text-red-400 uppercase font-bold block">PERINGATAN TINDAKAN TINGKAT TINGGI</span>
                        <h3 class="font-serif text-lg font-bold text-white" x-text="criticalTitle"></h3>
                    </div>
                </div>
                <button @click="criticalModal = false" class="text-white/40 hover:text-white">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            <p class="text-xs text-white/80 leading-relaxed font-sans" x-text="criticalDesc"></p>

            <form action="{{ route('superadmin.users.critical') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="critical_action" :value="criticalAction">
                <input type="hidden" name="target_user_id" :value="targetUserId">

                <div class="p-3 bg-white/5 border border-white/10 space-y-2">
                    <label class="block text-[11px] font-mono text-white/70">
                        Ketik <strong class="text-red-400">KONFIRMASI</strong> di bawah untuk melanjutkan:
                    </label>
                    <input type="text" name="confirmation_code" x-model="confirmText" required 
                           class="w-full p-2.5 bg-black/50 border border-red-500/50 text-white font-mono text-xs uppercase focus:ring-1 focus:ring-red-500 outline-none" 
                           placeholder="Ketik KONFIRMASI">
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="criticalModal = false" class="px-4 py-2 text-xs font-semibold text-white/70 hover:text-white uppercase tracking-wider">
                        Batal
                    </button>
                    <button type="submit" 
                            :disabled="confirmText !== 'KONFIRMASI'"
                            :class="confirmText === 'KONFIRMASI' ? 'bg-red-600 hover:bg-red-700 cursor-pointer' : 'bg-red-900/40 text-white/30 cursor-not-allowed'"
                            class="px-5 py-2.5 text-xs font-semibold uppercase tracking-wider text-white transition-colors flex items-center gap-1.5 shadow-md">
                        <span class="material-symbols-outlined text-[16px]">lock_open</span>
                        <span>Eksekusi Tindakan</span>
                    </button>
                </div>
            </form>

        </div>
    </div>

</div>
@endsection
