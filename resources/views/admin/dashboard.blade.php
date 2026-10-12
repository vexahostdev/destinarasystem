@extends('layouts.admin')

@section('title', 'Dashboard Operasional Harian')

@section('content')
<div class="space-y-8">

    <!-- Top Banner & Waktu Operasional -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-xs border border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="space-y-2">
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-gray-500">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Pusat Kendali Terpadu Destinara</span>
                <span>•</span>
                <span>{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-[#1E1B1B] tracking-tight">
                Dashboard Operasional & Kurasi
            </h1>
            <p class="text-sm text-gray-500 max-w-2xl leading-relaxed">
                Pantau antrian kurasi tapak adat, validasi nomor NPSN/legalitas lembaga, kendalikan jadwal rombongan lintas provinsi, dan monitor dispatch notifikasi secara real-time.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('admin.destinations.verify') }}" class="bg-[#703A3A] hover:bg-[#561D1E] text-white px-5 py-2.5 rounded-full text-xs font-bold uppercase tracking-wider flex items-center gap-2 shadow-xs transition-all">
                <span class="material-symbols-outlined text-[18px]">verified</span>
                <span>Telaah Tapak ({{ $pendingDestinations }})</span>
            </a>
            <a href="{{ route('admin.bookings.index') }}" class="px-5 py-2.5 rounded-full border border-gray-300 hover:border-gray-400 text-gray-700 bg-white text-xs font-bold uppercase tracking-wider flex items-center gap-2 transition-all shadow-xs">
                <span class="material-symbols-outlined text-[18px]">calendar_month</span>
                <span>Pantau Reservasi</span>
            </a>
        </div>
    </div>

    <!-- 4 Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Pending Destinasi -->
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-xs hover:shadow-md transition-all">
            <div class="flex items-center justify-between text-gray-500 text-xs font-semibold mb-2">
                <span>Menunggu Telaah</span>
                <div class="w-8 h-8 rounded-full bg-amber-50 text-amber-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">travel_explore</span>
                </div>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-[#1E1B1B]">{{ $pendingDestinations }}</span>
                <span class="text-xs text-amber-800 font-medium">Tapak Baru</span>
            </div>
            <p class="text-[11px] text-gray-400 mt-2">Standar keselamatan & modul P5</p>
            <a href="{{ route('admin.destinations.verify') }}" class="inline-flex items-center gap-1 text-xs font-bold text-[#703A3A] mt-3 hover:underline">
                <span>Buka antrian kurasi</span>
                <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
            </a>
        </div>

        <!-- Card 2: Pending Verifikasi User -->
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-xs hover:shadow-md transition-all">
            <div class="flex items-center justify-between text-gray-500 text-xs font-semibold mb-2">
                <span>Validasi Kredensial</span>
                <div class="w-8 h-8 rounded-full bg-blue-50 text-blue-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">badge</span>
                </div>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-[#1E1B1B]">{{ $pendingVerifications }}</span>
                <span class="text-xs text-blue-800 font-medium">Pengguna</span>
            </div>
            <p class="text-[11px] text-gray-400 mt-2">NPSN Kemdikbud & SK Lembaga</p>
            <a href="{{ route('admin.users.verify') }}" class="inline-flex items-center gap-1 text-xs font-bold text-[#703A3A] mt-3 hover:underline">
                <span>Periksa dokumen</span>
                <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
            </a>
        </div>

        <!-- Card 3: Active Bookings -->
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-xs hover:shadow-md transition-all">
            <div class="flex items-center justify-between text-gray-500 text-xs font-semibold mb-2">
                <span>Rombongan Aktif</span>
                <div class="w-8 h-8 rounded-full bg-emerald-50 text-[#51634b] flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">groups</span>
                </div>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-[#1E1B1B]">{{ $activeBookings }}</span>
                <span class="text-xs text-[#51634b] font-medium">Rombongan</span>
            </div>
            <p class="text-[11px] text-gray-400 mt-2">{{ $urgentBookings }} reservasi menunggu respon</p>
            <a href="{{ route('admin.bookings.index') }}" class="inline-flex items-center gap-1 text-xs font-bold text-[#703A3A] mt-3 hover:underline">
                <span>Kelola jadwal</span>
                <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
            </a>
        </div>

        <!-- Card 4: Queue Logs -->
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-xs hover:shadow-md transition-all">
            <div class="flex items-center justify-between text-gray-500 text-xs font-semibold mb-2">
                <span>Notifikasi Gateway</span>
                <div class="w-8 h-8 rounded-full bg-rose-50 text-rose-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">notifications_active</span>
                </div>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-[#1E1B1B]">{{ $failedNotifs }}</span>
                <span class="text-xs text-rose-700 font-medium">Gagal Kirim</span>
            </div>
            <p class="text-[11px] text-gray-400 mt-2">Gateway Fonnte & Mailgun</p>
            <a href="{{ route('admin.notifications.index', ['status' => 'failed']) }}" class="inline-flex items-center gap-1 text-xs font-bold text-[#703A3A] mt-3 hover:underline">
                <span>Tinjau queue error</span>
                <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
            </a>
        </div>
    </div>

    <!-- Antrian Tugas Mendesak -->
    @if(count($urgentItems) > 0)
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold text-[#1E1B1B] flex items-center gap-2">
                <span class="material-symbols-outlined text-amber-600">priority_high</span>
                <span>Tindakan Prioritas (SLA Alert)</span>
            </h2>
            <span class="text-xs text-gray-400 font-mono">{{ count($urgentItems) }} butuh respon</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($urgentItems as $item)
            <div class="rounded-2xl p-4 flex items-start justify-between gap-4 border border-amber-200/80 bg-amber-50/40">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $item['badge_color'] }}">
                            {{ $item['badge'] }}
                        </span>
                        <h3 class="text-sm font-bold text-[#1E1B1B]">{{ $item['title'] }}</h3>
                    </div>
                    <p class="text-xs text-gray-600 leading-relaxed">{{ $item['desc'] }}</p>
                </div>
                <a href="{{ $item['link'] }}" class="px-3.5 py-1.5 rounded-full border border-gray-300 text-xs font-bold uppercase tracking-wider bg-white hover:bg-gray-50 transition-all shrink-0">
                    {{ $item['action_label'] }}
                </a>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Layout Grid: Tabel Reservasi Terkini & Panel Samping -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left 2 Cols: Master Tabel Booking Terkini -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs">
                <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-4">
                    <div>
                        <h2 class="text-lg font-bold text-[#1E1B1B]">Arus Reservasi Terkini</h2>
                        <p class="text-xs text-gray-400">Daftar booking sekolah dan institusi yang baru masuk sistem</p>
                    </div>
                    <a href="{{ route('admin.bookings.index') }}" class="text-xs font-bold text-[#703A3A] hover:underline flex items-center gap-1">
                        <span>Lihat Semua</span>
                        <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-gray-100 text-gray-400 uppercase tracking-wider font-semibold">
                                <th class="pb-3">Kode / Instansi</th>
                                <th class="pb-3">Tapak</th>
                                <th class="pb-3">Tgl Kunjungan</th>
                                <th class="pb-3 text-right">Rombongan</th>
                                <th class="pb-3 text-center">Status</th>
                                <th class="pb-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($recentBookings as $b)
                            <tr class="hover:bg-gray-50/70 transition-colors">
                                <td class="py-3.5 pr-2">
                                    <span class="font-mono font-bold text-[#703A3A] block">{{ $b->booking_code }}</span>
                                    <span class="text-[#1E1B1B] font-medium">{{ $b->user->institution->institution_name ?? $b->user->name }}</span>
                                </td>
                                <td class="py-3.5 pr-2">
                                    <span class="font-semibold text-[#1E1B1B] block">{{ $b->destination->name ?? '-' }}</span>
                                    <span class="text-[11px] text-gray-400">{{ $b->destination->city ?? '' }}</span>
                                </td>
                                <td class="py-3.5 pr-2 font-mono text-gray-600">
                                    {{ \Carbon\Carbon::parse($b->visit_date)->format('d M Y') }}
                                </td>
                                <td class="py-3.5 pr-2 text-right font-mono font-bold text-[#1E1B1B]">
                                    {{ $b->total_pax }} Pax
                                </td>
                                <td class="py-3.5 text-center">
                                    @if($b->status === 'confirmed')
                                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 text-[10px] font-bold uppercase">Lunas</span>
                                    @elseif($b->status === 'awaiting_payment')
                                        <span class="px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-800 text-[10px] font-bold uppercase">Menunggu Bayar</span>
                                    @elseif($b->status === 'pending_confirmation')
                                        <span class="px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-800 text-[10px] font-bold uppercase">Review Mitra</span>
                                    @elseif($b->status === 'completed')
                                        <span class="px-2.5 py-0.5 rounded-full bg-gray-100 text-gray-700 text-[10px] font-bold uppercase">Selesai</span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-full bg-rose-50 text-rose-800 text-[10px] font-bold uppercase">{{ $b->status }}</span>
                                    @endif
                                </td>
                                <td class="py-3.5 text-right">
                                    <a href="{{ route('admin.bookings.index', ['search' => $b->booking_code]) }}" class="text-[#703A3A] font-bold hover:underline">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Distribusi Tematik Pembelajaran P5 -->
            <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs">
                <h3 class="text-base font-bold text-[#1E1B1B] mb-1">Cakupan Kurikulum & Relevansi</h3>
                <p class="text-xs text-gray-400 mb-4">Pemetaan modul pembelajaran tapak nusantara yang terdaftar dalam platform</p>
                
                <div class="space-y-3.5 text-xs">
                    <div>
                        <div class="flex justify-between font-semibold mb-1.5">
                            <span>Kearifan Lokal & Tata Ruang Adat</span>
                            <span class="font-mono text-gray-500">45% (9 Tapak)</span>
                        </div>
                        <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                            <div class="bg-[#703A3A] h-2 rounded-full" style="width: 45%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between font-semibold mb-1.5">
                            <span>Biosfer Maritim & Konservasi Alam</span>
                            <span class="font-mono text-gray-500">30% (6 Tapak)</span>
                        </div>
                        <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                            <div class="bg-[#51634b] h-2 rounded-full" style="width: 30%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between font-semibold mb-1.5">
                            <span>Teknologi Pertanian Tradisional & Pangan</span>
                            <span class="font-mono text-gray-500">25% (5 Tapak)</span>
                        </div>
                        <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                            <div class="bg-[#b87a38] h-2 rounded-full" style="width: 25%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right 1 Col: Audit Trail & Indikator Sistem -->
        <div class="space-y-6">
            
            <!-- SLA & Kinerja Layanan -->
            <div class="rounded-3xl bg-emerald-50/60 p-6 border border-emerald-200/50 space-y-3">
                <h3 class="text-base font-bold text-emerald-900 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#51634b]">verified_user</span>
                    <span>Standar Layanan Operasional (SLA)</span>
                </h3>
                <div class="space-y-3 text-xs">
                    <div class="flex items-center justify-between pb-2 border-b border-emerald-200/40">
                        <span class="text-emerald-800">Respon Verifikasi Tapak</span>
                        <span class="font-mono font-bold text-emerald-900">18.4 Jam (Target: &lt;24h)</span>
                    </div>
                    <div class="flex items-center justify-between pb-2 border-b border-emerald-200/40">
                        <span class="text-emerald-800">Penerbitan Dokumen BOS</span>
                        <span class="font-mono font-bold text-emerald-900">Instan (&lt;5 detik)</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-emerald-800">Sukses Notifikasi WA</span>
                        <span class="font-mono font-bold text-emerald-900">98.2% Lolos Gateway</span>
                    </div>
                </div>
            </div>

            <!-- Rekam Jejak Aktivitas Operasional (Audit Trail) -->
            <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
                    <h3 class="text-base font-bold text-[#1E1B1B] flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px] text-[#703A3A]">history</span>
                        <span>Audit Trail</span>
                    </h3>
                    <span class="text-[10px] font-mono text-gray-400 uppercase">Live Log</span>
                </div>

                <div class="space-y-4">
                    @foreach($auditTrail as $a)
                    <div class="text-xs space-y-1 pb-3 border-b border-gray-100 last:border-0 last:pb-0">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-[#1E1B1B]">{{ $a->user->name ?? 'Admin Operasional' }}</span>
                            <span class="text-[10px] text-gray-400 font-mono">{{ $a->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-gray-600 leading-relaxed">{{ $a->description }}</p>
                        <div class="flex items-center gap-2 text-[10px] font-mono text-[#703A3A]">
                            <span>{{ strtoupper($a->action) }}</span>
                            <span>•</span>
                            <span class="text-gray-400">{{ $a->ip_address }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="mt-4 pt-3 border-t border-gray-100 text-center">
                    <a href="{{ route('admin.notifications.index') }}" class="text-xs font-bold text-[#703A3A] hover:underline">
                        Lihat Log Sistem Lengkap &rarr;
                    </a>
                </div>
            </div>

            <!-- Panduan Cepat Petugas Admin -->
            <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs space-y-3">
                <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400">Panduan Petugas</h4>
                <ul class="text-xs text-gray-600 space-y-2 list-disc list-inside leading-relaxed">
                    <li>Dossier baru wajib diverifikasi kriteria zonasi adat dan kapasitas daya tampung.</li>
                    <li>Verifikasi sekolah wajib memeriksa keabsahan NPSN pada pangkalan data Kemdikbud.</li>
                    <li>Intervensi booking hanya diizinkan untuk keadaan darurat / force majeure.</li>
                </ul>
            </div>

        </div>

    </div>

</div>
@endsection
