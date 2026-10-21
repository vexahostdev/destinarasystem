@extends('layouts.admin')

@section('title', 'System Health & Diagnostik Infrastruktur')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-[#111827]">Diagnostik & Kesehatan Sistem</h1>
            <p class="text-sm text-gray-500">Pemeriksaan live koneksi database, queue workers, gateway, storage, dan scheduler</p>
        </div>
        <div>
            <a href="{{ route('admin.health.index') }}" class="bg-[#1F2B45] hover:bg-[#16203A] text-white px-5 py-2.5 rounded-2xl text-xs font-bold uppercase tracking-wider flex items-center gap-2 shadow-xs transition-all">
                <span class="material-symbols-outlined text-[18px]">refresh</span>
                <span>Jalankan Ulang Diagnostik</span>
            </a>
        </div>
    </div>

    <!-- Health Check Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($checks as $key => $chk)
        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs flex items-start justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    @if($chk['status'] === 'healthy')
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    @elseif($chk['status'] === 'warning')
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                    @elseif($chk['status'] === 'info')
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                    @else
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                    @endif
                    <h3 class="text-sm font-bold text-gray-900">{{ $chk['name'] }}</h3>
                </div>
                <p class="text-xs text-gray-500 leading-relaxed">{{ $chk['detail'] }}</p>
            </div>

            <div class="text-right shrink-0">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $chk['status'] === 'healthy' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : ($chk['status'] === 'warning' ? 'bg-amber-50 text-amber-800 border border-amber-200' : ($chk['status'] === 'info' ? 'bg-blue-50 text-blue-800 border border-blue-200' : 'bg-rose-50 text-rose-800 border border-rose-200')) }}">
                    {{ strtoupper($chk['status']) }}
                </span>
                @if($chk['latency'] !== '-')
                    <span class="block text-[11px] font-mono text-gray-400 mt-1">{{ $chk['latency'] }}</span>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    <!-- Info Server & Runtime Environment -->
    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs space-y-3">
        <h3 class="text-base font-bold text-[#111827]">Lingkungan Runtime & Host</h3>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs font-mono">
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/60">
                <span class="text-gray-400 block text-[10px]">Versi PHP</span>
                <span class="font-bold text-gray-800">{{ PHP_VERSION }}</span>
            </div>
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/60">
                <span class="text-gray-400 block text-[10px]">Versi Laravel</span>
                <span class="font-bold text-gray-800">{{ app()->version() }}</span>
            </div>
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/60">
                <span class="text-gray-400 block text-[10px]">Environment</span>
                <span class="font-bold text-[#1F2B45] uppercase">{{ app()->environment() }}</span>
            </div>
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/60">
                <span class="text-gray-400 block text-[10px]">Waktu Server</span>
                <span class="font-bold text-gray-800">{{ now()->format('H:i:s T') }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
