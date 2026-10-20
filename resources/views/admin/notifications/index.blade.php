@extends('layouts.admin')

@section('title', 'Pusat Notifikasi & Queue Dispatch')

@section('content')
<div class="space-y-8" x-data="{ previewModal: false, modalMsg: {} }">

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-[#2B211E]/12 pb-6">
        <div>
            <div class="flex items-center gap-2 text-xs font-mono font-semibold uppercase tracking-wider text-[#2B3A4A] mb-1">
                <span>Infrastruktur Komunikasi Otomatis</span>
                <span>•</span>
                <span>Gateway Dispatcher</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#2B211E]">
                Pusat Notifikasi & Log Antrian (Queue)
            </h1>
            <p class="text-sm text-[#2B211E]/70 max-w-2xl">
                Pantau seluruh pengiriman notifikasi otomatis transaksi: pengingat Virtual Account, konfirmasi rombongan masuk, penerbitan e-Pass, dan status transfer dana ke mitra pengelola.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <span class="px-3 py-1.5 bg-emerald-100 text-emerald-900 border border-emerald-300 text-xs font-semibold flex items-center gap-1.5 font-mono">
                <span class="w-2 h-2 rounded-full bg-emerald-600 animate-pulse"></span>
                <span>VexaHost WhatsApp API: AKTIF</span>
            </span>
        </div>
    </div>

    <!-- 4 Mini Metric Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 font-mono text-xs">
        <div class="rgs-card p-4 bg-white border-t-2 border-t-[#2B211E]">
            <span class="text-[#2B211E]/60 uppercase block text-[10px]">Total Pesan Diproses</span>
            <span class="font-serif text-2xl font-bold text-[#2B211E]">{{ $stats['total'] }}</span>
            <span class="text-[10px] text-[#2B211E]/50 block mt-1">Multi-channel gateway</span>
        </div>

        <div class="rgs-card p-4 bg-white border-t-2 border-t-emerald-600">
            <span class="text-emerald-800 uppercase block text-[10px]">Berhasil Terkirim</span>
            <span class="font-serif text-2xl font-bold text-emerald-800">{{ $stats['sent'] }}</span>
            <span class="text-[10px] text-emerald-700 block mt-1">Tersampaikan ke device</span>
        </div>

        <div class="rgs-card p-4 bg-white border-t-2 border-t-blue-600">
            <span class="text-blue-800 uppercase block text-[10px]">Dalam Antrian (Queue)</span>
            <span class="font-serif text-2xl font-bold text-blue-800">{{ $stats['queued'] }}</span>
            <span class="text-[10px] text-blue-700 block mt-1">Menunggu worker</span>
        </div>

        <div class="rgs-card p-4 bg-white border-t-2 border-t-red-600">
            <span class="text-red-800 uppercase block text-[10px]">Gagal / Butuh Retry</span>
            <span class="font-serif text-2xl font-bold text-red-800">{{ $stats['failed'] }}</span>
            <span class="text-[10px] text-red-700 block mt-1">Nomor/email invalid</span>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
        <!-- Filter Tabs -->
        <div class="flex items-center gap-2 overflow-x-auto text-xs font-semibold uppercase tracking-wider">
            <a href="{{ route('admin.notifications.index', ['channel' => 'all', 'status' => $status]) }}" 
               class="px-3.5 py-2 transition-colors {{ $channel === 'all' ? 'bg-[#703A3A] text-white' : 'bg-white text-[#2B211E]/75 border border-[#2B211E]/15 hover:bg-[#2B211E]/5' }}">
                Semua Kanal
            </a>
            <a href="{{ route('admin.notifications.index', ['channel' => 'whatsapp', 'status' => $status]) }}" 
               class="px-3.5 py-2 transition-colors flex items-center gap-1.5 {{ $channel === 'whatsapp' ? 'bg-[#703A3A] text-white' : 'bg-white text-[#2B211E]/75 border border-[#2B211E]/15 hover:bg-[#2B211E]/5' }}">
                <span>WhatsApp</span>
            </a>
            <a href="{{ route('admin.notifications.index', ['channel' => 'email', 'status' => $status]) }}" 
               class="px-3.5 py-2 transition-colors flex items-center gap-1.5 {{ $channel === 'email' ? 'bg-[#703A3A] text-white' : 'bg-white text-[#2B211E]/75 border border-[#2B211E]/15 hover:bg-[#2B211E]/5' }}">
                <span>Email SMTP</span>
            </a>
        </div>

        <!-- Filter Status -->
        <div class="flex items-center gap-2 text-xs font-mono">
            <span class="text-[#2B211E]/60 uppercase">Status:</span>
            <a href="{{ route('admin.notifications.index', ['channel' => $channel, 'status' => 'all']) }}" 
               class="px-2.5 py-1 text-xs {{ $status === 'all' ? 'font-bold text-[#703A3A] underline' : 'text-[#2B211E]/70' }}">Semua</a>
            <a href="{{ route('admin.notifications.index', ['channel' => $channel, 'status' => 'sent']) }}" 
               class="px-2.5 py-1 text-xs {{ $status === 'sent' ? 'font-bold text-emerald-800 underline' : 'text-[#2B211E]/70' }}">Sent</a>
            <a href="{{ route('admin.notifications.index', ['channel' => $channel, 'status' => 'queued']) }}" 
               class="px-2.5 py-1 text-xs {{ $status === 'queued' ? 'font-bold text-blue-800 underline' : 'text-[#2B211E]/70' }}">Queued</a>
            <a href="{{ route('admin.notifications.index', ['channel' => $channel, 'status' => 'failed']) }}" 
               class="px-2.5 py-1 text-xs {{ $status === 'failed' ? 'font-bold text-red-800 underline' : 'text-[#2B211E]/70' }}">Failed</a>
        </div>
    </div>

    <!-- Data Table Log Notifikasi -->
    <div class="rgs-card bg-white overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-[#faf6f0] border-b border-[#2B211E]/12 text-[#2B211E]/60 uppercase tracking-wider font-mono">
                        <th class="py-3 px-4 font-semibold">Penerima & Kontak</th>
                        <th class="py-3 px-4 font-semibold text-center">Kanal</th>
                        <th class="py-3 px-4 font-semibold">Event Trigger & Isi Notifikasi</th>
                        <th class="py-3 px-4 font-semibold text-center">Status</th>
                        <th class="py-3 px-4 font-semibold font-mono">Waktu Kirim</th>
                        <th class="py-3 px-4 font-semibold text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#2B211E]/08">
                    @forelse($logs as $log)
                    <tr class="hover:bg-[#faf6f0]/60 transition-colors">
                        <td class="py-3.5 px-4">
                            <span class="font-semibold text-[#2B211E] block">{{ $log->recipient_name }}</span>
                            <span class="text-[11px] font-mono text-[#2B211E]/60">{{ $log->target }}</span>
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            @if($log->channel === \App\Enums\NotificationChannel::WHATSAPP)
                                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-900 border border-emerald-300 text-[10px] font-mono font-bold uppercase inline-flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[12px]">chat</span>
                                    <span>WA</span>
                                </span>
                            @else
                                <span class="px-2 py-0.5 bg-blue-100 text-blue-900 border border-blue-300 text-[10px] font-mono font-bold uppercase inline-flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[12px]">mail</span>
                                    <span>EMAIL</span>
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 max-w-md">
                            <span class="font-bold text-[#2B211E] block text-xs">{{ $log->title }}</span>
                            <p class="text-[11px] text-[#2B211E]/75 line-clamp-1 mt-0.5">{{ $log->preview_text }}</p>
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            @if($log->status === \App\Enums\NotificationStatus::SENT)
                                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-900 border border-emerald-300 text-[10px] font-mono font-bold uppercase">
                                    TERKIRIM
                                </span>
                            @elseif($log->status === \App\Enums\NotificationStatus::QUEUED)
                                <span class="px-2 py-0.5 bg-blue-100 text-blue-900 border border-blue-300 text-[10px] font-mono font-bold uppercase">
                                    ANTRIAN
                                </span>
                            @else
                                <span class="px-2 py-0.5 bg-red-100 text-red-900 border border-red-300 text-[10px] font-mono font-bold uppercase">
                                    GAGAL ({{ $log->retry_count }}x)
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 font-mono text-[11px] text-[#2B211E]/70">
                            {{ $log->sent_at ? $log->sent_at->format('d/m/Y H:i') : '-' }}
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button type="button" 
                                        @click="modalMsg = { title: '{{ addslashes($log->title) }}', recipient: '{{ addslashes($log->recipient_name) }}', target: '{{ $log->target }}', text: '{{ addslashes($log->preview_text) }}', channel: '{{ $log->channel?->value ?? (string)$log->channel }}' }; previewModal = true"
                                        title="Pratinjau Pesan Lengkap" 
                                        class="p-1 text-[#2B211E]/60 hover:text-[#703A3A]">
                                    <span class="material-symbols-outlined text-[16px]">visibility</span>
                                </button>

                                @if($log->status === \App\Enums\NotificationStatus::FAILED)
                                <form action="{{ route('admin.notifications.retry', $log->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" title="Kirim Ulang Pesan Ini" class="px-2 py-1 bg-red-50 hover:bg-red-100 text-red-800 border border-red-200 text-[10px] font-mono font-bold uppercase flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[12px]">replay</span>
                                        <span>Retry</span>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-[#2B211E]/50">Tidak ada log notifikasi pada filter ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Audit Trail Admin Operasional -->
    @php
        $auditLogs = $auditLogs ?? collect();
    @endphp
    <div class="rgs-card bg-white p-6">
        <div class="flex items-center justify-between border-b border-[#2B211E]/10 pb-3 mb-4">
            <div>
                <h3 class="font-serif text-base font-bold text-[#2B211E]">Rekam Jejak Audit Harian (Audit Trail)</h3>
                <p class="text-xs text-[#2B211E]/60">Catatan kronologis tindakan operasional dan verifikasi resmi petugas</p>
            </div>
            <span class="text-xs font-mono text-[#2B211E]/60">{{ count($auditLogs) }} Tindakan Tercatat</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-mono">
                <thead>
                    <tr class="border-b border-[#2B211E]/10 text-[#2B211E]/60 uppercase text-[10px]">
                        <th class="pb-2">Waktu</th>
                        <th class="pb-2">Petugas</th>
                        <th class="pb-2">Aksi</th>
                        <th class="pb-2">Deskripsi Tindakan</th>
                        <th class="pb-2 text-right">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#2B211E]/08 text-[11px]">
                    @foreach($auditLogs as $a)
                    <tr class="hover:bg-[#faf6f0]/50">
                        <td class="py-2.5 text-[#2B211E]/60 whitespace-nowrap">{{ $a->created_at->format('d/m/Y H:i:s') }}</td>
                        <td class="py-2.5 font-sans font-semibold text-[#2B211E]">{{ $a->user->name ?? 'Sistem / Pengguna' }}</td>
                        <td class="py-2.5 font-bold text-[#703A3A] uppercase">{{ $a->action }}</td>
                        <td class="py-2.5 font-sans text-[#2B211E]/80">{{ $a->description }}</td>
                        <td class="py-2.5 text-right text-[#2B211E]/50">{{ $a->ip_address }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Pratinjau Pesan Keluar -->
    <div x-show="previewModal" 
         x-cloak
         class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4 backdrop-blur-xs"
         @keydown.escape.window="previewModal = false">
        
        <div class="bg-white max-w-md w-full p-6 space-y-4 border border-[#2B211E]/20 shadow-2xl" @click.away="previewModal = false">
            <div class="flex items-center justify-between border-b border-[#2B211E]/10 pb-3">
                <h3 class="font-serif text-lg font-bold text-[#2B211E]">Isi Pesan Notifikasi Gateway</h3>
                <button @click="previewModal = false" class="text-[#2B211E]/60 hover:text-[#2B211E]">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>

            <div class="space-y-3 text-xs">
                <div class="bg-[#faf6f0] p-3 border border-[#2B211E]/10 space-y-1">
                    <p class="text-[#2B211E]/60 font-mono text-[10px] uppercase">Penerima:</p>
                    <p class="font-bold text-[#2B211E]" x-text="modalMsg.recipient"></p>
                    <p class="font-mono text-[#703A3A]" x-text="modalMsg.target"></p>
                </div>

                <div class="space-y-1">
                    <p class="font-bold text-[#2B211E]" x-text="modalMsg.title"></p>
                    <div class="p-3 bg-gray-50 border border-[#2B211E]/15 rounded-none font-sans text-xs leading-relaxed text-[#2B211E]/90 whitespace-pre-wrap" x-text="modalMsg.text"></div>
                </div>
            </div>

            <div class="flex items-center justify-end pt-2 border-t border-[#2B211E]/10">
                <button type="button" @click="previewModal = false" class="rgs-btn-primary px-4 py-2 text-xs font-semibold uppercase">
                    Tutup
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
