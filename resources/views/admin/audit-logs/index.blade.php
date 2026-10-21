@extends('layouts.admin')

@section('title', 'Rekam Jejak Audit (Audit Trail)')

@section('content')
<div class="space-y-6" x-data="{ openDiffModal: false, activeLog: null, loadingDiff: false }">
    <div>
        <h1 class="text-2xl font-extrabold text-[#111827]">Audit Trail Ekosistem</h1>
        <p class="text-sm text-gray-500">Log jejak digital seluruh tindakan operasional, perubahan status, dan intervensi data sensitif</p>
    </div>

    <!-- Filter Card -->
    <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-xs">
        <form method="GET" action="{{ route('admin.auditLogs.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <select name="user_id" class="w-full text-xs rounded-xl border-gray-200 focus:border-[#1F2B45] focus:ring-[#1F2B45]">
                    <option value="">Semua Operator</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                            {{ $u->name }} ({{ $u->email }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <input type="text" name="action" value="{{ request('action') }}" placeholder="Filter aksi (misal: booking, update)..."
                       class="w-full text-xs rounded-xl border-gray-200 focus:border-[#1F2B45] focus:ring-[#1F2B45]">
            </div>
            <div>
                <input type="date" name="date_from" value="{{ request('date_from') }}" placeholder="Dari tanggal..."
                       class="w-full text-xs rounded-xl border-gray-200 focus:border-[#1F2B45] focus:ring-[#1F2B45]">
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="w-full bg-[#1F2B45] text-white rounded-xl py-2 text-xs font-bold uppercase hover:bg-[#16203A]">
                    Filter Log
                </button>
                <a href="{{ route('admin.auditLogs.index') }}" class="px-3 py-2 text-xs text-gray-500 hover:text-gray-700 border border-gray-200 rounded-2xl">
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
                        <th class="pb-3">Waktu</th>
                        <th class="pb-3">Pengguna / Operator</th>
                        <th class="pb-3">Aksi</th>
                        <th class="pb-3">Keterangan</th>
                        <th class="pb-3">Target Entitas</th>
                        <th class="pb-3 font-mono">Alamat IP</th>
                        <th class="pb-3 text-right">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($logs as $log)
                    <tr class="hover:bg-gray-50/70 transition-colors">
                        <td class="py-3 font-mono text-gray-500 text-[11px]">
                            {{ $log->created_at?->format('d/m/Y H:i:s') }}
                        </td>
                        <td class="py-3 font-bold text-gray-800">
                            {{ $log->user?->name ?? 'System Automated' }}
                        </td>
                        <td class="py-3">
                            <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 text-[10px] font-mono font-bold">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td class="py-3 text-gray-600 max-w-sm truncate" title="{{ $log->description }}">
                            {{ $log->description ?? '-' }}
                        </td>
                        <td class="py-3 text-gray-500 font-mono text-[11px]">
                            {{ $log->target_type ? class_basename($log->target_type) . " #{$log->target_id}" : '-' }}
                        </td>
                        <td class="py-3 font-mono text-gray-400 text-[11px]">
                            {{ $log->ip_address ?? '-' }}
                        </td>
                        <td class="py-3 text-right">
                            <button type="button" @click="loadingDiff = true; openDiffModal = true; fetch('/admin/audit-logs/' + {{ $log->id }}).then(r => r.json()).then(d => { activeLog = d; loadingDiff = false; })"
                                    class="text-xs text-[#1F2B45] font-bold hover:underline">
                                Lihat Diff
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-gray-400">Belum ada catatan log audit.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 pt-4 border-t border-gray-100">
            {{ $logs->links() }}
        </div>
    </div>

    <!-- Modal Diff -->
    <div x-show="openDiffModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" style="display: none;">
        <div class="bg-white rounded-3xl max-w-2xl w-full p-6 space-y-4 shadow-xl max-h-[85vh] flex flex-col">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <h3 class="text-base font-bold text-gray-900">Perubahan Data (Audit Diff)</h3>
                <button @click="openDiffModal = false" class="text-gray-400 hover:text-gray-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto space-y-4" x-show="!loadingDiff && activeLog">
                <div class="grid grid-cols-2 gap-4 text-xs font-mono">
                    <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl space-y-2">
                        <span class="font-bold text-rose-800 uppercase block">Data Lama (Old Values)</span>
                        <pre class="text-[11px] whitespace-pre-wrap text-rose-900" x-text="JSON.stringify(activeLog?.old_values, null, 2)"></pre>
                    </div>
                    <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl space-y-2">
                        <span class="font-bold text-emerald-800 uppercase block">Data Baru (New Values)</span>
                        <pre class="text-[11px] whitespace-pre-wrap text-emerald-900" x-text="JSON.stringify(activeLog?.new_values, null, 2)"></pre>
                    </div>
                </div>
            </div>

            <div x-show="loadingDiff" class="py-12 text-center text-xs text-gray-400">
                Memuat rincian perubahan data...
            </div>
        </div>
    </div>
</div>
@endsection
