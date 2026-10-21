{{-- Header panel halaman portal: tombol sidebar, judul + subjudul, aksi di kanan (slot). --}}
@props(['title', 'subtitle' => null])

<header class="ps-header">
    <button type="button" @click="toggleSidebar()" title="Buka/tutup menu" aria-label="Buka/tutup menu" class="ps-icon-btn -ml-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <rect x="3" y="3" width="18" height="18" rx="2" stroke-width="2"></rect>
            <path stroke-linecap="round" stroke-width="2" d="M9 3v18"></path>
        </svg>
    </button>
    <span class="ps-divider" aria-hidden="true"></span>

    <div class="min-w-0 flex-1">
        <h2 class="ps-header-title truncate">{{ $title }}</h2>
        @if ($subtitle)
            <p class="ps-header-sub truncate">{{ $subtitle }}</p>
        @endif
    </div>

    <div class="flex shrink-0 items-center gap-2">
        {{ $slot }}
    </div>
</header>
