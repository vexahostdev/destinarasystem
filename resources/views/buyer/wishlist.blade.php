@extends('layouts.buyer')

@section('title', 'Destinasi Disimpan (Wishlist) — Destinara')

@section('content')
<div class="space-y-6">

    <!-- Header Breadcrumb -->
    <div class="pb-4 border-b border-slate-200">
        <div class="flex items-center gap-2 text-xs text-slate-500 mb-2">
            <a href="{{ route('buyer.dashboard') }}" class="hover:text-[#8C5151]">Dashboard</a>
            <span>/</span>
            <span class="text-[#8C5151] font-semibold">Wishlist Tapak Edukasi</span>
        </div>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h1 class="font-serif text-2xl sm:text-3xl font-bold text-slate-800">Koleksi Tapak Tersimpan</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                    Daftar laboratorium alam dan desa adat yang Anda simpan untuk rencana ekskursi mendatang.
                </p>
            </div>
            <a href="{{ route('destinasi.index') }}" class="px-4 py-2 bg-[#8C5151] hover:bg-[#734040] text-white text-xs font-bold rounded-full transition shadow-xs flex items-center gap-1.5 self-start sm:self-auto">
                <span class="material-symbols-outlined text-[16px]">explore</span>
                <span>Jelajahi Katalog</span>
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl text-xs font-medium">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($wishlists as $wish)
            @php
                $dest = $wish->destination;
                $firstMedia = $dest->media->first();
                $imageSrc = $firstMedia ? asset('storage/' . $firstMedia->file_path) : 'https://images.unsplash.com/photo-1596402184320-417e7178b2cd?auto=format&fit=crop&w=800&q=80';
                $minPrice = $dest->packages->min('price_per_pax') ?? 0;
            @endphp
            <div class="bg-white rounded-3xl p-3.5 shadow-sm border border-slate-200 hover:shadow-md transition flex flex-col justify-between">
                <div>
                    <div class="relative w-full aspect-[16/10] rounded-2xl overflow-hidden bg-slate-100 mb-3">
                        <img src="{{ $imageSrc }}" alt="{{ $dest->name }}" class="w-full h-full object-cover">
                        <div class="absolute top-3 right-3">
                            <form action="{{ route('buyer.wishlist.toggle') }}" method="POST">
                                @csrf
                                <input type="hidden" name="destination_id" value="{{ $dest->id }}">
                                <button type="submit" class="p-2 bg-white/90 backdrop-blur rounded-full text-rose-500 hover:bg-white shadow-xs transition" title="Hapus dari wishlist">
                                    <span class="material-symbols-outlined text-[18px]">favorite</span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="px-2 pb-2">
                        <span class="text-[10px] uppercase font-bold tracking-wider text-[#8C5151] block mb-1">
                            {{ $dest->province?->name ?? $dest->city }}
                        </span>
                        <h3 class="font-bold text-slate-900 text-base leading-snug">
                            <a href="{{ route('destinasi.show', $dest->slug) }}" class="hover:underline">{{ $dest->name }}</a>
                        </h3>
                        <p class="text-xs text-slate-500 line-clamp-2 mt-1 leading-relaxed">{{ $dest->description }}</p>
                    </div>
                </div>

                <div class="px-2 pt-3 border-t border-slate-100 flex items-center justify-between mt-2">
                    <div>
                        <span class="text-[10px] text-slate-400 block font-medium">Mulai dari</span>
                        <span class="text-sm font-extrabold text-[#8C5151]">Rp {{ number_format($minPrice, 0, ',', '.') }}<span class="text-[10px] font-normal text-slate-500">/pax</span></span>
                    </div>

                    <a href="{{ route('buyer.booking.create', ['destinasi' => $dest->slug]) }}" class="px-3.5 py-1.5 bg-[#8C5151] hover:bg-[#734040] text-white text-xs font-bold rounded-full transition shadow-xs flex items-center gap-1">
                        <span>Pesan</span>
                        <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-1 sm:col-span-2 lg:col-span-3 py-16 text-center text-slate-400 bg-white rounded-3xl p-8 border border-slate-200">
                <span class="material-symbols-outlined text-4xl text-slate-300 mb-2">favorite_border</span>
                <p class="text-sm font-medium text-slate-600">Belum ada tapak edukasi di daftar wishlist Anda.</p>
                <a href="{{ route('destinasi.index') }}" class="inline-block mt-3 text-xs font-bold text-[#8C5151] hover:underline">
                    Jelajahi Katalog Destinasi Sekarang
                </a>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $wishlists->links() }}
    </div>

</div>
@endsection
