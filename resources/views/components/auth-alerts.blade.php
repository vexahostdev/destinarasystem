{{-- Pesan sesi & error validasi untuk halaman auth berlatar foto --}}
@php
    $tones = [
        'success' => ['bg-emerald-50 border-emerald-200 text-emerald-800', 'text-emerald-600', 'check_circle'],
        'status' => ['bg-emerald-50 border-emerald-200 text-emerald-800', 'text-emerald-600', 'check_circle'],
        'error' => ['bg-red-50 border-red-200 text-red-800', 'text-red-600', 'error'],
        'warning' => ['bg-amber-50 border-amber-200 text-amber-800', 'text-amber-600', 'warning'],
    ];
@endphp

@foreach ($tones as $key => [$box, $iconColor, $icon])
    @if (is_string(session($key)) && session($key) !== 'verification-link-sent')
        <div class="p-3 rounded-2xl border text-xs flex items-center gap-2 {{ $box }}">
            <span class="material-symbols-outlined text-base shrink-0 {{ $iconColor }}">{{ $icon }}</span>
            <span>{{ session($key) }}</span>
        </div>
    @endif
@endforeach

@if ($errors->any())
    <div class="p-3 bg-red-50 rounded-2xl border border-red-200 text-xs text-red-800 space-y-1">
        @foreach ($errors->all() as $error)
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-base text-red-600 shrink-0">error</span>
                <span>{{ $error }}</span>
            </div>
        @endforeach
    </div>
@endif
