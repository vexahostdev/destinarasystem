@extends('layouts.buyer')

@section('title', 'Notifikasi & Pemberitahuan — Destinara')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">

    <!-- Header Breadcrumb & Aksi Tandai Semua Dibaca -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-200">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-2">
                <a href="{{ route('buyer.dashboard') }}" class="hover:text-[#703A3A]">Dashboard</a>
                <span>/</span>
                <span class="text-[#703A3A] font-semibold">Notifikasi & Riwayat Pesan</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-slate-900">Pemberitahuan Sistem</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                Pemberitahuan resmi terkait status booking, penerbitan invoice, dan verifikasi dokumen institusi Anda.
            </p>
        </div>

        @if(Auth::user()->unreadNotifications()->count() > 0)
            <form action="{{ route('buyer.notifications.readAll') }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold uppercase tracking-wider rounded-full transition flex items-center gap-1.5 shadow-2xs">
                    <span class="material-symbols-outlined text-[16px]">done_all</span>
                    <span>Tandai Semua Dibaca</span>
                </button>
            </form>
        @endif
    </div>

    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden divide-y divide-slate-100">
        @forelse($notifications as $notif)
            @php
                $isRead = !is_null($notif->read_at);
                $subject = $notif->data['subject'] ?? 'Pemberitahuan Sistem';
                $message = $notif->data['message'] ?? ($notif->data['body'] ?? '');
                $eventKey = $notif->data['event_key'] ?? null;
            @endphp
            <div class="p-5 flex items-start gap-4 hover:bg-slate-50/60 transition {{ $isRead ? 'opacity-80 bg-white' : 'bg-rose-50/20' }}">
                <div class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 {{ $isRead ? 'bg-slate-100 text-slate-500' : 'bg-[#703A3A]/10 text-[#703A3A]' }}">
                    <span class="material-symbols-outlined text-[20px]">
                        {{ $isRead ? 'mail_outline' : 'mark_email_unread' }}
                    </span>
                </div>

                <div class="flex-1 min-w-0 space-y-1">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-sm {{ $isRead ? 'text-slate-700' : 'text-slate-900' }}">
                                {{ $subject }}
                            </span>
                            @if(!$isRead)
                                <span class="w-2 h-2 rounded-full bg-rose-600"></span>
                            @endif
                        </div>
                        <span class="text-[11px] text-slate-400 shrink-0">
                            {{ $notif->created_at->diffForHumans() }}
                        </span>
                    </div>

                    <p class="text-xs text-slate-600 leading-relaxed whitespace-pre-line">{{ $message }}</p>

                    <div class="flex items-center justify-between pt-2">
                        <div class="flex items-center gap-2 text-[10px] text-slate-400">
                            @if($eventKey)
                                <span class="font-mono bg-slate-100 px-2 py-0.5 rounded text-slate-600">{{ $eventKey }}</span>
                            @endif
                            <span>{{ $notif->created_at->translatedFormat('d M Y, H:i') }} WIB</span>
                        </div>

                        @if(!$isRead)
                            <form action="{{ route('buyer.notifications.read', $notif->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="text-[11px] text-[#703A3A] hover:underline font-bold flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">check</span>
                                    <span>Tandai dibaca</span>
                                </button>
                            </form>
                        @endif
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
