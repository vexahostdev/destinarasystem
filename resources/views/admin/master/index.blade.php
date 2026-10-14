@extends('layouts.admin')

@section('title', 'Master Data Platform')

@section('content')
<div class="space-y-6" x-data="{ tab: '{{ $tab }}' }">
    <div>
        <h1 class="text-2xl font-extrabold text-[#1E1B1B]">Master Data Platform</h1>
        <p class="text-sm text-gray-500">Kelola taksonomi kategori, fasilitas, daftar bank, dan kebijakan pembatalan</p>
    </div>

    <!-- Tabs Navigation -->
    <div class="border-b border-gray-200">
        <nav class="flex space-x-6 overflow-x-auto text-xs font-bold uppercase tracking-wider">
            <button @click="tab = 'categories'" :class="tab === 'categories' ? 'border-[#703A3A] text-[#703A3A]' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-3 px-1 border-b-2 transition-all">
                Kategori Destinasi ({{ $categories->count() }})
            </button>
            <button @click="tab = 'facilities'" :class="tab === 'facilities' ? 'border-[#703A3A] text-[#703A3A]' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-3 px-1 border-b-2 transition-all">
                Fasilitas ({{ $facilities->count() }})
            </button>
            <button @click="tab = 'banks'" :class="tab === 'banks' ? 'border-[#703A3A] text-[#703A3A]' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-3 px-1 border-b-2 transition-all">
                Daftar Bank ({{ $banks->count() }})
            </button>
            <button @click="tab = 'policies'" :class="tab === 'policies' ? 'border-[#703A3A] text-[#703A3A]' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-3 px-1 border-b-2 transition-all">
                Kebijakan Pembatalan ({{ $cancellationPolicies->count() }})
            </button>
            <button @click="tab = 'provinces'" :class="tab === 'provinces' ? 'border-[#703A3A] text-[#703A3A]' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-3 px-1 border-b-2 transition-all">
                Provinsi ({{ $provinces->count() }})
            </button>
        </nav>
    </div>

    <!-- Tab 1: Kategori Destinasi -->
    <div x-show="tab === 'categories'" class="space-y-6">
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-xs">
            <h3 class="text-sm font-bold text-gray-900 mb-3">Tambah Kategori Baru</h3>
            <form action="{{ route('admin.master.store', 'categories') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                @csrf
                <div>
                    <input type="text" name="name" required placeholder="Nama Kategori..."
                           class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                </div>
                <div>
                    <input type="text" name="icon" placeholder="Material Icon (misal: landscape)..."
                           class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                </div>
                <div>
                    <input type="text" name="description" placeholder="Deskripsi singkat..."
                           class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                </div>
                <div>
                    <button type="submit" class="w-full bg-[#703A3A] text-white rounded-xl py-2 text-xs font-bold uppercase hover:bg-[#561D1E]">
                        + Tambah Kategori
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-gray-100 text-gray-400 uppercase font-semibold">
                        <th class="pb-3">Ikon & Kategori</th>
                        <th class="pb-3">Slug</th>
                        <th class="pb-3 text-center">Jumlah Destinasi</th>
                        <th class="pb-3 text-center">Status</th>
                        <th class="pb-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($categories as $cat)
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 font-bold text-gray-800 flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px] text-[#703A3A]">{{ $cat->icon ?? 'nature' }}</span>
                            <span>{{ $cat->name }}</span>
                        </td>
                        <td class="py-3 font-mono text-gray-500">{{ $cat->slug }}</td>
                        <td class="py-3 text-center font-mono font-bold">{{ $cat->destinations_count }}</td>
                        <td class="py-3 text-center">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $cat->is_active ? 'bg-emerald-50 text-emerald-800' : 'bg-stone-100 text-stone-500' }}">
                                {{ $cat->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="py-3 text-right">
                            <form action="{{ route('admin.master.update', ['type' => 'categories', 'id' => $cat->id]) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-xs font-semibold px-2.5 py-1 rounded-full border border-gray-200 hover:bg-gray-50 text-gray-700">
                                    {{ $cat->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-6 text-center text-gray-400">Belum ada kategori.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tab 2: Fasilitas Tapak -->
    <div x-show="tab === 'facilities'" class="space-y-6" style="display: none;">
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-xs">
            <h3 class="text-sm font-bold text-gray-900 mb-3">Tambah Fasilitas Baru</h3>
            <form action="{{ route('admin.master.store', 'facilities') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @csrf
                <div>
                    <input type="text" name="name" required placeholder="Nama Fasilitas (misal: Toilet Standar Difabel)..."
                           class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                </div>
                <div>
                    <input type="text" name="icon" placeholder="Material Icon (misal: wc)..."
                           class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                </div>
                <div>
                    <button type="submit" class="w-full bg-[#703A3A] text-white rounded-xl py-2 text-xs font-bold uppercase hover:bg-[#561D1E]">
                        + Tambah Fasilitas
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-gray-100 text-gray-400 uppercase font-semibold">
                        <th class="pb-3">Ikon & Nama Fasilitas</th>
                        <th class="pb-3 text-center">Status</th>
                        <th class="pb-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($facilities as $fac)
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 font-bold text-gray-800 flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px] text-[#51634b]">{{ $fac->icon ?? 'check_circle' }}</span>
                            <span>{{ $fac->name }}</span>
                        </td>
                        <td class="py-3 text-center">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $fac->is_active ? 'bg-emerald-50 text-emerald-800' : 'bg-stone-100 text-stone-500' }}">
                                {{ $fac->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="py-3 text-right">
                            <form action="{{ route('admin.master.update', ['type' => 'facilities', 'id' => $fac->id]) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-xs font-semibold px-2.5 py-1 rounded-full border border-gray-200 hover:bg-gray-50 text-gray-700">
                                    {{ $fac->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="py-6 text-center text-gray-400">Belum ada fasilitas.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tab 3: Daftar Bank -->
    <div x-show="tab === 'banks'" class="space-y-6" style="display: none;">
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-xs">
            <h3 class="text-sm font-bold text-gray-900 mb-3">Tambah Bank Rekening Baru</h3>
            <form action="{{ route('admin.master.store', 'banks') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @csrf
                <div>
                    <input type="text" name="name" required placeholder="Nama Bank (misal: Bank Mandiri)..."
                           class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                </div>
                <div>
                    <input type="text" name="code" required placeholder="Kode Bank (misal: MANDIRI)..."
                           class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                </div>
                <div>
                    <button type="submit" class="w-full bg-[#703A3A] text-white rounded-xl py-2 text-xs font-bold uppercase hover:bg-[#561D1E]">
                        + Tambah Bank
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-gray-100 text-gray-400 uppercase font-semibold">
                        <th class="pb-3">Nama Bank</th>
                        <th class="pb-3 font-mono">Kode Bank</th>
                        <th class="pb-3 text-center">Status</th>
                        <th class="pb-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($banks as $b)
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 font-bold text-gray-800">{{ $b->name }}</td>
                        <td class="py-3 font-mono text-[#703A3A] font-bold">{{ $b->code }}</td>
                        <td class="py-3 text-center">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $b->is_active ? 'bg-emerald-50 text-emerald-800' : 'bg-stone-100 text-stone-500' }}">
                                {{ $b->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="py-3 text-right">
                            <form action="{{ route('admin.master.update', ['type' => 'banks', 'id' => $b->id]) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-xs font-semibold px-2.5 py-1 rounded-full border border-gray-200 hover:bg-gray-50 text-gray-700">
                                    {{ $b->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="py-6 text-center text-gray-400">Belum ada bank.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tab 4: Kebijakan Pembatalan -->
    <div x-show="tab === 'policies'" class="space-y-6" style="display: none;">
        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs">
            <h3 class="text-base font-bold text-gray-900 mb-4">Kebijakan Pembatalan & Skema Refund</h3>
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-gray-100 text-gray-400 uppercase font-semibold">
                        <th class="pb-3">Nama Kebijakan</th>
                        <th class="pb-3 text-center">&gt; H-7</th>
                        <th class="pb-3 text-center">H-7 s.d. H-3</th>
                        <th class="pb-3 text-center">&lt; H-3</th>
                        <th class="pb-3">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($cancellationPolicies as $pol)
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 font-bold text-gray-800">{{ $pol->name }}</td>
                        <td class="py-3 text-center font-mono font-bold text-emerald-700">{{ $pol->refund_percent_before_h7 }}%</td>
                        <td class="py-3 text-center font-mono font-bold text-amber-700">{{ $pol->refund_percent_h7_to_h3 }}%</td>
                        <td class="py-3 text-center font-mono font-bold text-rose-700">{{ $pol->refund_percent_after_h3 }}%</td>
                        <td class="py-3 text-gray-500">{{ $pol->description }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-6 text-center text-gray-400">Belum ada kebijakan pembatalan.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tab 5: Provinsi -->
    <div x-show="tab === 'provinces'" class="space-y-6" style="display: none;">
        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs">
            <h3 class="text-base font-bold text-gray-900 mb-4">Daftar Wilayah Provinsi Resmi</h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                @foreach($provinces as $prv)
                <div class="p-3 rounded-xl bg-stone-50 border border-stone-200/60 font-semibold text-gray-700 flex items-center justify-between">
                    <span>{{ $prv->name }}</span>
                    <span class="text-[10px] font-mono text-gray-400">{{ $prv->code }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
