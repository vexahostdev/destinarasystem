@extends('layouts.admin')

@section('title', 'Manajemen Pengguna Ekosistem')

@section('content')
<div class="space-y-6" x-data="{ openSuspendModal: false, openCommissionModal: false, activeUser: null, activeCommission: 10 }">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-[#1E1B1B]">Manajemen Pengguna</h1>
            <p class="text-sm text-gray-500">Kelola akun pembeli institusi dan mitra pengelola tapak, status akun, dan reset sandi</p>
        </div>
    </div>

    <!-- 3 Mini Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-xs">
            <span class="text-xs text-gray-400 font-semibold block">Total Pembeli Institusi</span>
            <span class="text-2xl font-extrabold text-gray-800 font-mono mt-1 block">{{ $stats['total_buyers'] }}</span>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-xs">
            <span class="text-xs text-gray-400 font-semibold block">Total Mitra Tapak</span>
            <span class="text-2xl font-extrabold text-gray-800 font-mono mt-1 block">{{ $stats['total_partners'] }}</span>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-xs">
            <span class="text-xs text-gray-400 font-semibold block">Akun Ditangguhkan</span>
            <span class="text-2xl font-extrabold text-rose-700 font-mono mt-1 block">{{ $stats['suspended'] }}</span>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-xs">
        <form method="GET" action="{{ route('admin.users.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, email, nomor HP..."
                       class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
            </div>
            <div>
                <select name="type" class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                    <option value="">Semua Tipe Akun</option>
                    <option value="buyer" {{ request('type') === 'buyer' ? 'selected' : '' }}>Pembeli Institusi</option>
                    <option value="partner" {{ request('type') === 'partner' ? 'selected' : '' }}>Mitra Pengelola</option>
                </select>
            </div>
            <div>
                <select name="status" class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                    <option value="">Semua Status Akun</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Ditangguhkan (Suspended)</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="w-full bg-[#703A3A] text-white rounded-xl py-2 text-xs font-bold uppercase hover:bg-[#561D1E]">
                    Filter
                </button>
                <a href="{{ route('admin.users.index') }}" class="px-3 py-2 text-xs text-gray-500 hover:text-gray-700 border border-gray-200 rounded-xl">
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
                    <tr class="border-b border-gray-100 text-gray-400 uppercase font-semibold">
                        <th class="pb-3">Nama & Email</th>
                        <th class="pb-3">Tipe & Entitas</th>
                        <th class="pb-3">Kontak</th>
                        <th class="pb-3 text-center">Email Verif</th>
                        <th class="pb-3 text-center">Status</th>
                        <th class="pb-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($users as $u)
                    <tr class="hover:bg-gray-50/70 transition-colors">
                        <td class="py-3.5 pr-2">
                            <span class="font-bold text-[#1E1B1B] block">{{ $u->name }}</span>
                            <span class="text-[11px] text-gray-500">{{ $u->email }}</span>
                        </td>
                        <td class="py-3.5 pr-2">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $u->isBuyer() ? 'bg-blue-50 text-blue-800' : 'bg-amber-50 text-amber-800' }}">
                                {{ $u->account_type->value }}
                            </span>
                            @if($u->institution)
                                <span class="block text-[11px] text-gray-700 font-semibold mt-1">{{ $u->institution->institution_name }}</span>
                            @elseif($u->partnerProfile)
                                <span class="block text-[11px] text-gray-700 font-semibold mt-1">{{ $u->partnerProfile->organization_name }}</span>
                                <span class="text-[10px] text-gray-400 font-mono">Komisi: {{ $u->partnerProfile->commission_rate ?? 10 }}%</span>
                            @endif
                        </td>
                        <td class="py-3.5 pr-2 font-mono text-gray-600">
                            {{ $u->phone ?? '-' }}
                        </td>
                        <td class="py-3.5 text-center">
                            @if($u->hasVerifiedEmail())
                                <span class="text-emerald-700 font-bold text-[10px] uppercase">Terverifikasi</span>
                            @else
                                <form action="{{ route('admin.users.verifyEmail', $u->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="border border-stone-200 text-stone-700 hover:bg-stone-50 px-2 py-0.5 rounded-full text-[10px] font-semibold">
                                        Verif Manual
                                    </button>
                                </form>
                            @endif
                        </td>
                        <td class="py-3.5 text-center">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $u->isActive() ? 'bg-emerald-50 text-emerald-800' : 'bg-rose-50 text-rose-800' }}">
                                {{ $u->status }}
                            </span>
                        </td>
                        <td class="py-3.5 text-right space-x-1">
                            <!-- Reset Password -->
                            <form action="{{ route('admin.users.sendPasswordReset', $u->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" onclick="return confirm('Kirim email tautan reset kata sandi ke {{ $u->email }}?')"
                                        class="p-1 text-gray-500 hover:text-gray-800" title="Kirim Link Reset Password">
                                    <span class="material-symbols-outlined text-[16px]">lock_reset</span>
                                </button>
                            </form>

                            <!-- Partner Commission -->
                            @if($u->isPartner() && $u->partnerProfile)
                            <button type="button" @click="activeUser = {{ $u->id }}; activeCommission = {{ $u->partnerProfile->commission_percent_override ?? 10 }}; openCommissionModal = true"
                                    class="p-1 text-[#703A3A] hover:text-[#561D1E]" title="Ubah Tarif Komisi Khusus">
                                <span class="material-symbols-outlined text-[16px]">percent</span>
                            </button>
                            @endif

                            <!-- Toggle Status / Suspend -->
                            @if($u->isActive())
                            <button type="button" @click="activeUser = {{ $u->id }}; openSuspendModal = true"
                                    class="border border-rose-300 text-rose-700 hover:bg-rose-50 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase">
                                Tangguhkan
                            </button>
                            @else
                            <form action="{{ route('admin.users.toggleStatus', $u->id) }}" method="POST" class="inline">
                                @csrf
                                <input type="hidden" name="status" value="active">
                                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-2.5 py-1 rounded-full text-[10px] font-bold uppercase">
                                    Aktifkan
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-gray-400">Belum ada akun pengguna.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 pt-4 border-t border-gray-100">
            {{ $users->links() }}
        </div>
    </div>

    <!-- Modal Suspend User -->
    <div x-show="openSuspendModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" style="display: none;">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl">
            <h3 class="text-base font-bold text-gray-900">Tangguhkan Akun Pengguna</h3>
            <p class="text-xs text-gray-500">Penangguhan akun akan langsung membatalkan seluruh sesi aktif pengguna.</p>

            <form :action="'/admin/pengguna/' + activeUser + '/status'" method="POST" class="space-y-3">
                @csrf
                <input type="hidden" name="status" value="suspended">

                <div>
                    <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Alasan Penangguhan *</label>
                    <textarea name="reason" required rows="3" placeholder="Sebutkan pelanggaran atau alasan penangguhan..."
                              class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="openSuspendModal = false" class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-xl">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 text-xs font-bold uppercase bg-rose-600 text-white rounded-xl hover:bg-rose-700">
                        Tangguhkan Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Partner Commission -->
    <div x-show="openCommissionModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" style="display: none;">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl">
            <h3 class="text-base font-bold text-gray-900">Ubah Tarif Komisi Khusus Mitra</h3>

            <form :action="'/admin/pengguna/' + activeUser + '/komisi'" method="POST" class="space-y-3">
                @csrf

                <div>
                    <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Tarif Komisi Platform (%) *</label>
                    <input type="number" step="0.1" name="commission_rate" x-model="activeCommission" required min="0" max="100"
                           class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="openCommissionModal = false" class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-xl">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 text-xs font-bold uppercase bg-[#703A3A] text-white rounded-xl hover:bg-[#561D1E]">
                        Simpan Komisi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
