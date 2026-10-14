@extends('layouts.admin')

@section('title', 'Tim Internal & Hak Akses Role')

@section('content')
<div class="space-y-6" x-data="{ tab: 'users', openUserModal: false, openRoleModal: false, activeUser: null, activeRole: null }">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-[#1E1B1B]">Tim Internal & Peran</h1>
            <p class="text-sm text-gray-500">Kelola akun staf internal platform dan konfigurasi hak akses permission Spatie</p>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" @click="openUserModal = true"
                    class="bg-[#703A3A] hover:bg-[#561D1E] text-white px-5 py-2.5 rounded-full text-xs font-bold uppercase tracking-wider flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">person_add</span>
                <span>Tambah Staf Internal</span>
            </button>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="border-b border-gray-200">
        <nav class="flex space-x-6 text-xs font-bold uppercase tracking-wider">
            <button @click="tab = 'users'" :class="tab === 'users' ? 'border-[#703A3A] text-[#703A3A]' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-3 px-1 border-b-2 transition-all">
                Staf Internal ({{ $internalUsers->total() }})
            </button>
            <button @click="tab = 'roles'" :class="tab === 'roles' ? 'border-[#703A3A] text-[#703A3A]' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-3 px-1 border-b-2 transition-all">
                Daftar Peran & Hak Akses ({{ $roles->count() }})
            </button>
        </nav>
    </div>

    <!-- Tab 1: Staf Internal -->
    <div x-show="tab === 'users'" class="space-y-6">
        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-gray-100 text-gray-400 uppercase font-semibold">
                        <th class="pb-3">Nama Staf</th>
                        <th class="pb-3">Email & Kontak</th>
                        <th class="pb-3">Peran / Role</th>
                        <th class="pb-3 text-center">Status</th>
                        <th class="pb-3">Login Terakhir</th>
                        <th class="pb-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($internalUsers as $usr)
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 font-bold text-gray-800">
                            {{ $usr->name }}
                        </td>
                        <td class="py-3 text-gray-600">
                            <span class="block">{{ $usr->email }}</span>
                            <span class="text-[11px] text-gray-400 font-mono">{{ $usr->phone ?? '-' }}</span>
                        </td>
                        <td class="py-3">
                            @foreach($usr->roles as $r)
                                <span class="px-2.5 py-0.5 rounded-full bg-stone-100 text-[#703A3A] font-bold text-[10px] uppercase">
                                    {{ $r->name }}
                                </span>
                            @endforeach
                        </td>
                        <td class="py-3 text-center">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $usr->status === 'active' ? 'bg-emerald-50 text-emerald-800' : 'bg-rose-50 text-rose-800' }}">
                                {{ $usr->status }}
                            </span>
                        </td>
                        <td class="py-3 text-gray-500 font-mono text-[11px]">
                            {{ $usr->last_login_at?->diffForHumans() ?? 'Belum pernah' }}
                        </td>
                        <td class="py-3 text-right">
                            @if($usr->id !== Auth::id())
                            <form action="{{ route('admin.team.user.delete', $usr->id) }}" method="POST" onsubmit="return confirm('Nonaktifkan akses staf ini?')" class="inline">
                                @csrf
                                <button type="submit" class="text-xs text-rose-600 hover:text-rose-800 font-semibold">
                                    Hapus
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-6 text-center text-gray-400">Belum ada staf internal.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="mt-4 pt-4 border-t border-gray-100">
                {{ $internalUsers->links() }}
            </div>
        </div>
    </div>

    <!-- Tab 2: Peran & Permissions -->
    <div x-show="tab === 'roles'" class="space-y-6" style="display: none;">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($roles as $role)
            <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h3 class="text-base font-bold text-[#1E1B1B] uppercase">{{ $role->name }}</h3>
                        <p class="text-xs text-gray-400">{{ $role->permissions->count() }} izin akses aktif</p>
                    </div>
                </div>

                <div class="space-y-2 max-h-60 overflow-y-auto pr-2">
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($role->permissions as $perm)
                            <span class="px-2 py-0.5 rounded-md bg-stone-100 text-stone-700 text-[10px] font-mono">
                                {{ $perm->name }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Modal Tambah User -->
    <div x-show="openUserModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" style="display: none;">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl">
            <h3 class="text-base font-bold text-gray-900">Tambah Staf Internal Baru</h3>

            <form action="{{ route('admin.team.user.store') }}" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Nama Lengkap *</label>
                    <input type="text" name="name" required placeholder="Nama staf..."
                           class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Alamat Email *</label>
                    <input type="email" name="email" required placeholder="email@destinara.id..."
                           class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Nomor WhatsApp</label>
                    <input type="text" name="phone" placeholder="62812..."
                           class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Kata Sandi Awal *</label>
                    <input type="password" name="password" required minlength="8" placeholder="Minimal 8 karakter..."
                           class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Peran / Role *</label>
                    <select name="role" required class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                        @foreach($roles as $r)
                            <option value="{{ $r->name }}">{{ strtoupper($r->name) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-gray-100">
                    <button type="button" @click="openUserModal = false" class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-xl">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 text-xs font-bold uppercase bg-[#703A3A] text-white rounded-xl hover:bg-[#561D1E]">
                        Simpan Staf
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
