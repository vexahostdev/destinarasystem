{{-- Buka/tutup sidebar. Layar lebar: lipat sidebar (diingat); ponsel: buka laci menu.
     Butuh state Alpine "sidebarOpen" & "mobileSidebarOpen" di <body>. --}}
<button type="button"
        @click="window.matchMedia('(min-width: 768px)').matches ? (sidebarOpen = !sidebarOpen) : (mobileSidebarOpen = true)"
        title="Buka/tutup menu" aria-label="Buka/tutup menu"
        {{ $attributes->merge(['class' => 'p-2 -ml-1 rounded-xl text-[#1F2937]/70 hover:text-[#1F2937] hover:bg-slate-100 transition-colors cursor-pointer']) }}>
    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <rect x="3" y="3" width="18" height="18" rx="2" stroke-width="2"></rect>
        <path stroke-linecap="round" stroke-width="2" d="M9 3v18"></path>
    </svg>
</button>
