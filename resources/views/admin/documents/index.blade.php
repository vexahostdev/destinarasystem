@extends('layouts.admin')

@section('title', 'Arsip Dokumen Resmi Platform')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-[#111827]">Arsip Dokumen Resmi</h1>
            <p class="text-sm text-gray-500">Daftar arsip dokumen legalitas, e-voucher, invoice BOS, kuitansi, dan surat konfirmasi</p>
        </div>
        <div class="flex items-center gap-3">
            @can('documents.templates')
            <a href="{{ route('admin.documents.templates') }}" class="bg-[#1F2B45] hover:bg-[#16203A] text-white px-5 py-2.5 rounded-2xl text-xs font-bold uppercase tracking-wider flex items-center gap-2 shadow-xs transition-all">
                <span class="material-symbols-outlined text-[18px]">edit_document</span>
                <span>Kelola Template Dokumen</span>
            </a>
            @endcan
        </div>
    </div>

    <!-- Filter Card -->
    <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-xs">
        <form method="GET" action="{{ route('admin.documents.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nomor dokumen / booking..."
                       class="w-full text-xs rounded-xl border-gray-200 focus:border-[#1F2B45] focus:ring-[#1F2B45]">
            </div>
            <div>
                <select name="type" class="w-full text-xs rounded-xl border-gray-200 focus:border-[#1F2B45] focus:ring-[#1F2B45]">
                    <option value="">Semua Tipe Dokumen</option>
                    @foreach($types as $t)
                        <option value="{{ $t->value }}" {{ request('type') === $t->value ? 'selected' : '' }}>
                            {{ $t->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="status" class="w-full text-xs rounded-xl border-gray-200 focus:border-[#1F2B45] focus:ring-[#1F2B45]">
                    <option value="">Semua Status</option>
                    <option value="final" {{ request('status') === 'final' ? 'selected' : '' }}>Final</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="void" {{ request('status') === 'void' ? 'selected' : '' }}>Void (Dibatalkan)</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="w-full bg-[#1F2B45] text-white rounded-xl py-2 text-xs font-bold uppercase hover:bg-[#16203A]">
                    Filter
                </button>
                <a href="{{ route('admin.documents.index') }}" class="px-3 py-2 text-xs text-gray-500 hover:text-gray-700 border border-gray-200 rounded-2xl">
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
                        <th class="pb-3">Nomor Dokumen</th>
                        <th class="pb-3">Judul & Tipe</th>
                        <th class="pb-3">Booking / Institusi</th>
                        <th class="pb-3 text-center">Versi</th>
                        <th class="pb-3 text-center">Status</th>
                        <th class="pb-3">Tanggal Terbit</th>
                        <th class="pb-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($documents as $doc)
                    <tr class="hover:bg-gray-50/70 transition-colors">
                        <td class="py-3.5 pr-2 font-mono font-bold text-[#1F2B45]">
                            {{ $doc->document_number }}
                        </td>
                        <td class="py-3.5 pr-2">
                            <span class="font-bold text-gray-800 block">{{ $doc->title }}</span>
                            <span class="text-[10px] font-mono text-gray-400 uppercase">{{ $doc->type->label() }}</span>
                        </td>
                        <td class="py-3.5 pr-2">
                            @if($doc->booking)
                                <a href="{{ route('admin.bookings.show', $doc->booking->booking_code) }}" class="font-mono text-gray-800 hover:underline font-bold block">
                                    {{ $doc->booking->booking_code }}
                                </a>
                                <span class="text-[11px] text-gray-500">{{ $doc->booking->user?->institution?->institution_name ?? $doc->booking->user?->name }}</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="py-3.5 text-center font-mono font-bold text-gray-600">
                            v{{ $doc->version }}
                        </td>
                        <td class="py-3.5 text-center">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $doc->status->value === 'final' ? 'bg-emerald-50 text-emerald-800' : ($doc->status->value === 'void' ? 'bg-rose-50 text-rose-800' : 'bg-stone-100 text-stone-600') }}">
                                {{ $doc->status->value ?? $doc->status }}
                            </span>
                        </td>
                        <td class="py-3.5 text-gray-500 font-mono text-[11px]">
                            {{ $doc->generated_at?->format('d/m/Y H:i') ?? '-' }}
                        </td>
                        <td class="py-3.5 text-right space-x-1">
                            <a href="{{ route('admin.documents.preview', $doc->id) }}" target="_blank"
                               class="text-xs text-[#1F2B45] font-bold hover:underline px-2 py-1">
                                Preview
                            </a>

                            @if($doc->status->value !== 'void')
                                @can('documents.regenerate')
                                <form action="{{ route('admin.documents.regenerate', $doc->id) }}" method="POST" class="inline" onsubmit="return confirm('Regenerate dokumen ini? Versi lama akan otomatis berstatus void.')">
                                    @csrf
                                    <button type="submit" class="border border-stone-200 text-stone-700 hover:bg-stone-50 px-2 py-1 rounded-2xl text-[10px] font-semibold">
                                        Regenerate
                                    </button>
                                </form>
                                <form action="{{ route('admin.documents.void', $doc->id) }}" method="POST" class="inline" onsubmit="return confirm('Tandai dokumen ini sebagai void?')">
                                    @csrf
                                    <button type="submit" class="border border-rose-200 text-rose-700 hover:bg-rose-50 px-2 py-1 rounded-2xl text-[10px] font-semibold">
                                        Void
                                    </button>
                                </form>
                                @endcan
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-gray-400">Belum ada dokumen terbit dalam arsip.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 pt-4 border-t border-gray-100">
            {{ $documents->links() }}
        </div>
    </div>
</div>
@endsection
