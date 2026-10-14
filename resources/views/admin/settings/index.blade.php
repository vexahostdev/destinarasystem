@extends('layouts.admin')

@section('title', 'Pengaturan Sistem Global')

@section('content')
<div class="space-y-6" x-data="{ activeGroup: '{{ $activeGroup }}' }">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-[#1E1B1B]">Pengaturan Ekosistem</h1>
            <p class="text-sm text-gray-500">Konfigurasi parameter bisnis, integrasi pihak ketiga terenkripsi, dan preferensi operasional</p>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="border-b border-gray-200">
        <nav class="flex space-x-6 overflow-x-auto text-xs font-bold uppercase tracking-wider">
            @foreach($groups as $grpKey => $grpLabel)
            <button @click="activeGroup = '{{ $grpKey }}'"
                    :class="activeGroup === '{{ $grpKey }}' ? 'border-[#703A3A] text-[#703A3A]' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-3 px-1 border-b-2 transition-all shrink-0">
                {{ $grpLabel }}
            </button>
            @endforeach
        </nav>
    </div>

    <!-- Settings Form -->
    <form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-6">
        @csrf

        @foreach($groups as $grpKey => $grpLabel)
        <div x-show="activeGroup === '{{ $grpKey }}'" class="space-y-6" {!! $grpKey !== $activeGroup ? 'style="display: none;"' : '' !!}>
            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-xs space-y-6">
                <div class="border-b border-gray-100 pb-4">
                    <h3 class="text-base font-bold text-[#1E1B1B]">{{ $grpLabel }}</h3>
                    <p class="text-xs text-gray-400">Parameter pengaturan grup {{ strtolower($grpLabel) }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    @forelse($groupedSettings[$grpKey] ?? [] as $field)
                    <div class="{{ ($field['type'] ?? 'text') === 'textarea' ? 'sm:col-span-2' : '' }} space-y-1">
                        <label class="block text-xs font-bold uppercase text-gray-700">
                            {{ $field['label'] ?? $field['key'] }}
                        </label>
                        @if(!empty($field['description']))
                            <p class="text-[11px] text-gray-400 mb-1">{{ $field['description'] }}</p>
                        @endif

                        @php
                            $val = $field['current_value'];
                            if (is_array($val)) {
                                $val = json_encode($val, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                            }
                        @endphp

                        @if(($field['type'] ?? 'text') === 'textarea' || is_array($field['current_value']))
                            <textarea name="{{ $field['key'] }}" rows="3"
                                      class="w-full text-xs font-mono rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">{{ $val }}</textarea>
                        @elseif(($field['type'] ?? 'bool') === 'bool' || ($field['type'] ?? '') === 'boolean')
                            <select name="{{ $field['key'] }}" class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                                <option value="1" {{ $val ? 'selected' : '' }}>Aktif / Ya</option>
                                <option value="0" {{ !$val ? 'selected' : '' }}>Nonaktif / Tidak</option>
                            </select>
                        @elseif(($field['type'] ?? 'text') === 'password')
                            <input type="password" name="{{ $field['key'] }}" value="{{ $val }}"
                                   placeholder="••••••••"
                                   class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                        @else
                            <input type="{{ in_array($field['type'] ?? 'text', ['string', 'text', 'int']) ? 'text' : ($field['type'] ?? 'text') }}" name="{{ $field['key'] }}" value="{{ $val }}"
                                   class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                        @endif
                    </div>
                    @empty
                    <div class="sm:col-span-2 text-center py-6 text-gray-400 text-xs">
                        Tidak ada pengaturan di grup ini.
                    </div>
                    @endforelse
                </div>

                <div class="flex items-center justify-between pt-6 border-t border-gray-100">
                    <div>
                        @if($grpKey === 'whatsapp')
                            <button type="submit" formaction="{{ route('admin.settings.test', 'whatsapp') }}"
                                    class="border border-stone-300 text-stone-700 px-4 py-2 rounded-full text-xs font-bold uppercase hover:bg-stone-50">
                                Tes Koneksi WhatsApp
                            </button>
                        @elseif($grpKey === 'mail')
                            <button type="submit" formaction="{{ route('admin.settings.test', 'mail') }}"
                                    class="border border-stone-300 text-stone-700 px-4 py-2 rounded-full text-xs font-bold uppercase hover:bg-stone-50">
                                Tes Kirim Email SMTP
                            </button>
                        @elseif($grpKey === 'payment')
                            <div class="flex items-center gap-2">
                                <button type="submit" formaction="{{ route('admin.settings.test', 'xendit') }}"
                                        class="border border-stone-300 text-stone-700 px-3 py-1.5 rounded-full text-xs font-bold uppercase hover:bg-stone-50">
                                    Tes Xendit
                                </button>
                                <button type="submit" formaction="{{ route('admin.settings.test', 'midtrans') }}"
                                        class="border border-stone-300 text-stone-700 px-3 py-1.5 rounded-full text-xs font-bold uppercase hover:bg-stone-50">
                                    Tes Midtrans
                                </button>
                            </div>
                        @endif
                    </div>

                    <button type="submit" class="bg-[#703A3A] hover:bg-[#561D1E] text-white px-6 py-2.5 rounded-full text-xs font-bold uppercase tracking-wider shadow-xs transition-all">
                        Simpan Pengaturan
                    </button>
                </div>
            </div>
        </div>
        @endforeach
    </form>
</div>
@endsection
