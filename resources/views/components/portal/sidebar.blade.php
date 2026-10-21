{{-- Sidebar portal bergaya POS Kasir (shadcn inset). Sama untuk buyer, mitra, dan admin/superadmin.
     Butuh x-data="portalShell" di <body>.
     $sections: [['label' => ?string, 'items' => [['href', 'active' => bool, 'icon', 'label', 'badge' => ?int], ...]], ...] --}}
@props(['home', 'subtitle', 'sections' => [], 'userSub' => null])

@php($user = Auth::user())

<div x-show="mobileSidebarOpen" x-cloak x-transition.opacity.duration.200ms @click="mobileSidebarOpen = false"
     class="ps-backdrop" aria-hidden="true"></div>

<aside class="ps-aside" :class="{ 'is-open': mobileSidebarOpen, 'is-rail': rail }"
       @keydown.escape.window="mobileSidebarOpen = false">

    {{-- Kepala: identitas portal --}}
    <a href="{{ $home }}" class="ps-brand" title="Destinara">
        <span class="ps-logo"><img src="{{ asset('assets/img/logo-mark-tight.png') }}" alt="Logo Destinara"></span>
        <span x-show="!rail" class="grid min-w-0 flex-1">
            <span class="ps-title truncate">Destinara</span>
            <span class="ps-sub truncate">{{ $subtitle }}</span>
        </span>
    </a>

    @isset($context)
        <div x-show="!rail" class="ps-context">{{ $context }}</div>
    @endisset

    {{-- Menu --}}
    <nav class="ps-nav" aria-label="Menu portal">
        @foreach ($sections as $section)
            @if (! empty($section['items']))
                @if (! empty($section['label']))
                    <div x-show="!rail" class="ps-label">{{ $section['label'] }}</div>
                @endif
                @foreach ($section['items'] as $item)
                    <a href="{{ $item['href'] }}" title="{{ $item['label'] }}"
                       class="ps-link {{ ! empty($item['active']) ? 'is-active' : '' }}"
                       @if (! empty($item['active'])) aria-current="page" @endif>
                        <span class="material-symbols-outlined">{{ $item['icon'] }}</span>
                        <span x-show="!rail" class="truncate">{{ $item['label'] }}</span>
                        @if (! empty($item['badge']))
                            <span x-show="!rail" class="ps-badge">{{ $item['badge'] }}</span>
                        @endif
                    </a>
                @endforeach
            @endif
        @endforeach
    </nav>

    {{-- Tema: Terang / Gelap berdampingan, yang aktif disorot --}}
    <div class="ps-seg" role="group" aria-label="Tema tampilan">
        <button type="button" @click="setTheme('light')" :class="!dark && 'is-on'" title="Mode terang">
            <span class="material-symbols-outlined">light_mode</span>
            <span x-show="!rail">Terang</span>
        </button>
        <button type="button" @click="setTheme('dark')" :class="dark && 'is-on'" title="Mode gelap">
            <span class="material-symbols-outlined">dark_mode</span>
            <span x-show="!rail">Gelap</span>
        </button>
    </div>

    {{-- Kaki: pengguna yang login + keluar --}}
    <div class="ps-user">
        <span class="ps-avatar">{{ strtoupper(substr($user->name ?? 'U', 0, 2)) }}</span>
        <span x-show="!rail" class="grid min-w-0 flex-1">
            <span class="truncate text-sm font-medium">{{ $user->name ?? 'Pengguna' }}</span>
            <span class="ps-sub truncate">{{ $userSub ?? ($user->email ?? '') }}</span>
        </span>
        <form x-show="!rail" method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" title="Keluar" aria-label="Keluar" class="ps-icon-btn is-danger">
                <span class="material-symbols-outlined text-[18px]">logout</span>
            </button>
        </form>
    </div>
</aside>
