@extends('layouts.buyer')

@section('title', 'Notifikasi & Pemberitahuan — Destinara')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">

    <!-- Header Breadcrumb -->
    <div class="pb-4 border-b border-slate-200">
        <div class="flex items-center gap-2 text-xs text-slate-500 mb-2">
            <a href="{{ route('buyer.dashboard') }}" class="hover:text-[#8C5151]">Dashboard</a>
            <span>/</span>
            <span class="text-[#8C5151] font-semibold">Notifikasi & Riwayat Pesan</span>
        </div>
        <div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-slate-800">Pemberitahuan Sistem</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                Riwayat pesan transaksi, pengingat batas waktu, dan pembaruan kesiapan mitra tapak.
            </p>
        </div>
    </div>

    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden divide-y divide-slate-100">
        @forelse($notifications as $notif)
            <div class="p-5 flex items-start gap-4 hover:bg-slate-50/60 transition {{ $notif->read_at ? 'opacity-75' : 'bg-slate-50/30' }}">
                <div class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 
                    {{ $notif->channel === 'whatsapp' ? 'bg-emerald-100 text-emerald-700' : 'bg-sky-100 text-sky-700' }}">
                    <span class="material-symbols-outlined text-[20px]">
                        {{ $notif->channel === 'whatsapp' ? 'chat' : 'mail' }}
                    </span>
                </div>

                <div class="flex-1 min-w-0 space-y-1">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-bold text-sm text-slate-900 truncate">{{ $notif->title }}</span>
                        <span class="text-[11px] text-slate-400 shrink-0">{{ $notif->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed">{{ $notif->body }}</p>
                    <div class="flex items-center gap-2 pt-1 text-[10px] text-slate-400">
                        <span class="uppercase font-semibold tracking-wider">{{ $notif->channel }}</span>
                        <span>•</span>
                        <span>Status: <strong class="text-slate-600 uppercase">{{ $notif->status }}</strong></span>
                    </div>
                </div>
            </div>
        @empty
            <div class="p-12 text-center text-slate-400">
                <span class="material-symbols-outlined text-4xl text-slate-300 mb-2">notifications_off</span>
                <p class="text-sm font-medium text-slate-500">Belum ada notifikasi atau pemberitahuan baru.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $notifications->links() }}
    </div>

</div>
@endsection
