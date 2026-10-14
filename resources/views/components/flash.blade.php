@if (session('success'))
    <div x-data="{ show: true }" x-show="show" class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 shadow-sm flex items-start justify-between gap-3 transition-all" role="alert">
        <div class="flex items-start gap-3">
            <span class="material-symbols-outlined text-emerald-600 text-xl shrink-0">check_circle</span>
            <div class="text-xs sm:text-sm font-medium leading-relaxed">
                {{ session('success') }}
            </div>
        </div>
        <button type="button" @click="show = false" class="text-emerald-500 hover:text-emerald-700 p-1 shrink-0 transition-colors" aria-label="Tutup">
            <span class="material-symbols-outlined text-base">close</span>
        </button>
    </div>
@endif

@if (session('error'))
    <div x-data="{ show: true }" x-show="show" class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 shadow-sm flex items-start justify-between gap-3 transition-all" role="alert">
        <div class="flex items-start gap-3">
            <span class="material-symbols-outlined text-rose-600 text-xl shrink-0">error</span>
            <div class="text-xs sm:text-sm font-medium leading-relaxed">
                {{ session('error') }}
            </div>
        </div>
        <button type="button" @click="show = false" class="text-rose-500 hover:text-rose-700 p-1 shrink-0 transition-colors" aria-label="Tutup">
            <span class="material-symbols-outlined text-base">close</span>
        </button>
    </div>
@endif

@if (session('warning'))
    <div x-data="{ show: true }" x-show="show" class="mb-6 p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 shadow-sm flex items-start justify-between gap-3 transition-all" role="alert">
        <div class="flex items-start gap-3">
            <span class="material-symbols-outlined text-amber-600 text-xl shrink-0">warning</span>
            <div class="text-xs sm:text-sm font-medium leading-relaxed">
                {{ session('warning') }}
            </div>
        </div>
        <button type="button" @click="show = false" class="text-amber-500 hover:text-amber-700 p-1 shrink-0 transition-colors" aria-label="Tutup">
            <span class="material-symbols-outlined text-base">close</span>
        </button>
    </div>
@endif

@if (session('info'))
    <div x-data="{ show: true }" x-show="show" class="mb-6 p-4 rounded-2xl bg-blue-50 border border-blue-200 text-blue-900 shadow-sm flex items-start justify-between gap-3 transition-all" role="alert">
        <div class="flex items-start gap-3">
            <span class="material-symbols-outlined text-blue-600 text-xl shrink-0">info</span>
            <div class="text-xs sm:text-sm font-medium leading-relaxed">
                {{ session('info') }}
            </div>
        </div>
        <button type="button" @click="show = false" class="text-blue-500 hover:text-blue-700 p-1 shrink-0 transition-colors" aria-label="Tutup">
            <span class="material-symbols-outlined text-base">close</span>
        </button>
    </div>
@endif

@if (session('status') && !session('success'))
    <div x-data="{ show: true }" x-show="show" class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 shadow-sm flex items-start justify-between gap-3 transition-all" role="alert">
        <div class="flex items-start gap-3">
            <span class="material-symbols-outlined text-emerald-600 text-xl shrink-0">info</span>
            <div class="text-xs sm:text-sm font-medium leading-relaxed">
                {{ session('status') }}
            </div>
        </div>
        <button type="button" @click="show = false" class="text-emerald-500 hover:text-emerald-700 p-1 shrink-0 transition-colors" aria-label="Tutup">
            <span class="material-symbols-outlined text-base">close</span>
        </button>
    </div>
@endif
