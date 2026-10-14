@extends('layouts.admin')

@section('title', 'Moderasi Ulasan Pembeli')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-extrabold text-[#1E1B1B]">Moderasi Ulasan Pembeli</h1>
        <p class="text-sm text-gray-500">Kelola kurasi penayangan testimoni dan rating destinasi dari rombongan sekolah</p>
    </div>

    <!-- Filter Card -->
    <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-xs">
        <form method="GET" action="{{ route('admin.reviews.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <select name="destination_id" class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                    <option value="">Semua Destinasi</option>
                    @foreach($destinations as $d)
                        <option value="{{ $d->id }}" {{ request('destination_id') == $d->id ? 'selected' : '' }}>
                            {{ $d->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="status" class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                    <option value="">Semua Status Moderasi</option>
                    <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Diterbitkan (Published)</option>
                    <option value="hidden" {{ request('status') === 'hidden' ? 'selected' : '' }}>Disembunyikan (Hidden)</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                </select>
            </div>
            <div>
                <select name="rating" class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                    <option value="">Semua Rating Bintang</option>
                    @for($i = 5; $i >= 1; $i--)
                        <option value="{{ $i }}" {{ request('rating') == $i ? 'selected' : '' }}>{{ $i }} Bintang</option>
                    @endfor
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="w-full bg-[#703A3A] text-white rounded-xl py-2 text-xs font-bold uppercase hover:bg-[#561D1E]">
                    Filter
                </button>
                <a href="{{ route('admin.reviews.index') }}" class="px-3 py-2 text-xs text-gray-500 hover:text-gray-700 border border-gray-200 rounded-xl">
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
                        <th class="pb-3">Destinasi & Booking</th>
                        <th class="pb-3">Institusi Pembeli</th>
                        <th class="pb-3">Rating</th>
                        <th class="pb-3">Ulasan & Balasan Mitra</th>
                        <th class="pb-3 text-center">Status</th>
                        <th class="pb-3 text-right">Moderasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($reviews as $rev)
                    <tr class="hover:bg-gray-50/70 transition-colors">
                        <td class="py-3.5 pr-2">
                            <span class="font-bold text-[#1E1B1B] block">{{ $rev->destination?->name }}</span>
                            <span class="text-[11px] font-mono text-gray-400">#{{ $rev->booking?->booking_code }}</span>
                        </td>
                        <td class="py-3.5 pr-2">
                            <span class="font-semibold text-gray-800 block">{{ $rev->booking?->user?->institution?->institution_name ?? $rev->booking?->user?->name }}</span>
                            <span class="text-[10px] text-gray-400 font-mono">{{ $rev->created_at?->format('d M Y') }}</span>
                        </td>
                        <td class="py-3.5 pr-2">
                            <div class="flex items-center text-amber-500">
                                @for($i = 1; $i <= 5; $i++)
                                    <span class="material-symbols-outlined text-[16px] {{ $i <= $rev->rating ? 'filled' : 'opacity-30' }}">star</span>
                                @endfor
                            </div>
                        </td>
                        <td class="py-3.5 pr-2 max-w-md">
                            <p class="text-gray-700 italic">"{{ $rev->comment }}"</p>
                            @if($rev->partner_reply)
                                <div class="mt-2 p-2 rounded-xl bg-stone-50 border border-stone-200/60 text-[11px] text-stone-600">
                                    <span class="font-bold text-gray-800 block">Balasan Mitra ({{ $rev->replied_at?->format('d/m/Y') }}):</span>
                                    <span>{{ $rev->partner_reply }}</span>
                                </div>
                            @endif
                        </td>
                        <td class="py-3.5 text-center">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $rev->status === \App\Enums\ReviewStatus::PUBLISHED ? 'bg-emerald-50 text-emerald-800' : ($rev->status === \App\Enums\ReviewStatus::HIDDEN ? 'bg-rose-50 text-rose-800' : 'bg-amber-50 text-amber-800') }}">
                                {{ $rev->status instanceof \App\Enums\ReviewStatus ? $rev->status->label() : ucfirst((string)$rev->status) }}
                            </span>
                        </td>
                        <td class="py-3.5 text-right space-x-1">
                            @if($rev->status !== \App\Enums\ReviewStatus::PUBLISHED)
                            <form action="{{ route('admin.reviews.moderate', $rev->id) }}" method="POST" class="inline">
                                @csrf
                                <input type="hidden" name="status" value="published">
                                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-2.5 py-1 rounded-full text-[10px] font-bold uppercase">
                                    Terbitkan
                                </button>
                            </form>
                            @endif
                            @if($rev->status !== \App\Enums\ReviewStatus::HIDDEN)
                            <form action="{{ route('admin.reviews.moderate', $rev->id) }}" method="POST" class="inline">
                                @csrf
                                <input type="hidden" name="status" value="hidden">
                                <button type="submit" class="border border-rose-300 text-rose-700 hover:bg-rose-50 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase">
                                    Sembunyikan
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-gray-400">Belum ada ulasan pembeli.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 pt-4 border-t border-gray-100">
            {{ $reviews->links() }}
        </div>
    </div>
</div>
@endsection
