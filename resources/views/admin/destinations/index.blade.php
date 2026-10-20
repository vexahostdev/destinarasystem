@extends('layouts.admin')

@section('title', 'Katalog & Inventaris Destinara')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-[#1E1B1B]">Katalog Destinasi</h1>
            <p class="text-sm text-gray-500">Kelola direktori tapak nusantara, ketersediaan, dan status penayangan</p>
        </div>
        @can('destinations.create')
        <a href="{{ route('admin.destinations.create') }}" class="bg-[#703A3A] hover:bg-[#561D1E] text-white px-5 py-2.5 rounded-full text-xs font-bold uppercase tracking-wider flex items-center gap-2 self-start shadow-xs transition-all">
            <span class="material-symbols-outlined text-[18px]">add</span>
            <span>Tambah Destinasi Baru</span>
        </a>
        @endcan
    </div>

    <!-- Filter Card -->
    <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-xs">
        <form method="GET" action="{{ route('admin.destinations.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau kota..."
                       class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
            </div>
            <div>
                <select name="status" class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                    <option value="">Semua Status</option>
                    @foreach(\App\Enums\DestinationStatus::cases() as $st)
                        <option value="{{ $st->value }}" {{ request('status') === $st->value ? 'selected' : '' }}>
                            {{ $st->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="category_id" class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="w-full bg-[#703A3A] text-white rounded-xl py-2 text-xs font-bold uppercase hover:bg-[#561D1E] transition-all">
                    Filter
                </button>
                <a href="{{ route('admin.destinations.index') }}" class="px-3 py-2 text-xs text-gray-500 hover:text-gray-700 border border-gray-200 rounded-xl">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Table Card -->
    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-gray-100 text-gray-400 uppercase tracking-wider font-semibold">
                        <th class="pb-3">Destinasi</th>
                        <th class="pb-3">Mitra Pengelola</th>
                        <th class="pb-3">Kategori</th>
                        <th class="pb-3">Lokasi</th>
                        <th class="pb-3 text-center">Kapasitas Harian</th>
                        <th class="pb-3 text-center">Status</th>
                        <th class="pb-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($destinations as $dest)
                    <tr class="hover:bg-gray-50/70 transition-colors">
                        <td class="py-3.5 pr-3">
                            <div class="flex items-center gap-3">
                                @if($dest->cover_image)
                                    <img src="{{ asset('storage/' . $dest->cover_image) }}" alt="{{ $dest->name }}" class="w-10 h-10 rounded-xl object-cover shrink-0">
                                @else
                                    <div class="w-10 h-10 rounded-xl bg-stone-100 text-stone-400 flex items-center justify-center shrink-0">
                                        <span class="material-symbols-outlined text-[20px]">image</span>
                                    </div>
                                @endif
                                <div>
                                    <span class="font-bold text-[#1E1B1B] block">{{ $dest->name }}</span>
                                    <span class="text-[11px] text-gray-400 font-mono">/destinasi/{{ $dest->slug }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="py-3.5 pr-2">
                            <span class="font-medium text-[#1E1B1B]">{{ $dest->partner?->name ?? 'Internal Platform' }}</span>
                        </td>
                        <td class="py-3.5 pr-2">
                            <span class="px-2.5 py-0.5 rounded-full bg-stone-100 text-stone-700 text-[10px] font-semibold">
                                {{ $dest->category?->name ?? '-' }}
                            </span>
                        </td>
                        <td class="py-3.5 pr-2">
                            <span class="text-gray-700 block">{{ $dest->city }}</span>
                            <span class="text-[11px] text-gray-400">{{ $dest->province?->name ?? '-' }}</span>
                        </td>
                        <td class="py-3.5 text-center font-mono font-bold text-gray-700">
                            {{ $dest->default_daily_capacity }} Pax
                        </td>
                        <td class="py-3.5 text-center">
                            <x-status-badge :enum="$dest->status" />
                        </td>
                        <td class="py-3.5 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('destinasi.show', $dest->slug) }}" target="_blank" class="text-gray-500 hover:text-gray-700 p-1" title="Lihat Publik">
                                    <span class="material-symbols-outlined text-[18px]">visibility</span>
                                </a>
                                @can('destinations.update')
                                <form action="{{ route('admin.destinations.toggleActive', $dest->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-xs font-semibold px-2.5 py-1 rounded-full border border-gray-200 hover:bg-gray-50 text-gray-700">
                                        {{ $dest->status === \App\Enums\DestinationStatus::PUBLISHED ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-gray-400">
                            Tidak ada destinasi ditemukan sesuai filter.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 pt-4 border-t border-gray-100">
            {{ $destinations->links() }}
        </div>
    </div>
</div>
@endsection
