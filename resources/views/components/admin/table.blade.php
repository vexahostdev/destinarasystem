@props(['empty' => false, 'emptyMessage' => 'Tidak ada data ditemukan.', 'pagination' => null])

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm text-slate-600">
            @if(isset($header))
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                    <tr>
                        {{ $header }}
                    </tr>
                </thead>
            @endif
            <tbody class="divide-y divide-slate-200">
                @if($empty)
                    <tr>
                        <td colspan="100" class="px-6 py-12 text-center text-slate-400">
                            @if(isset($emptyState))
                                {{ $emptyState }}
                            @else
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-10 h-10 mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                    <p class="text-sm font-medium text-slate-500">{{ $emptyMessage }}</p>
                                </div>
                            @endif
                        </td>
                    </tr>
                @else
                    {{ $slot }}
                @endif
            </tbody>
        </table>
    </div>

    @if($pagination)
        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50/50">
            {{ $pagination }}
        </div>
    @endif
</div>
