@props(['action' => '', 'resetUrl' => '', 'searchPlaceholder' => 'Cari data...'])

<form method="GET" action="{{ $action }}" class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6">
    <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
        <div class="w-full md:w-72 relative">
            <input type="text" 
                   name="search" 
                   value="{{ request('search') }}" 
                   placeholder="{{ $searchPlaceholder }}" 
                   class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-[#8C5151] focus:border-transparent outline-none">
            <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </div>

        <div class="w-full md:w-auto flex flex-wrap items-center gap-3">
            {{ $slot }}

            <button type="submit" class="px-4 py-2 bg-[#8C5151] hover:bg-[#734040] text-white rounded-lg text-sm font-medium transition shadow-sm">
                Filter
            </button>

            @if(request()->hasAny(['search', 'status', 'category_id', 'date_from', 'date_to', 'type', 'action']))
                <a href="{{ $resetUrl ?: url()->current() }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-sm font-medium transition">
                    Reset
                </a>
            @endif
        </div>
    </div>
</form>
