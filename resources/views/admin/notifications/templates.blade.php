@extends('layouts.admin')

@section('title', 'Template Notifikasi Transaksional')

@section('content')
<div class="space-y-6" x-data="{ selectedTemplate: {{ $templates->first()?->id ?? 'null' }} }">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-[#1E1B1B]">Template Notifikasi Otomatis</h1>
            <p class="text-sm text-gray-500">Sesuaikan pesan transaksional WhatsApp dan Email ke pemesan dan mitra tapak</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Sidebar Templates List -->
        <div class="bg-white rounded-3xl p-4 border border-gray-100 shadow-xs space-y-1">
            @foreach($templates as $tmpl)
            <button type="button" @click="selectedTemplate = {{ $tmpl->id }}"
                    :class="selectedTemplate === {{ $tmpl->id }} ? 'bg-[#703A3A] text-white' : 'text-gray-700 hover:bg-stone-50'"
                    class="w-full text-left p-3 rounded-2xl transition-all">
                <span class="font-bold text-xs block font-mono">{{ $tmpl->event_key }}</span>
                <span class="text-[10px] opacity-80 uppercase block mt-0.5">{{ $tmpl->channel->value ?? $tmpl->channel }} • {{ $tmpl->audience }}</span>
            </button>
            @endforeach
        </div>

        <!-- Editor Panel -->
        <div class="lg:col-span-3">
            @foreach($templates as $tmpl)
            <div x-show="selectedTemplate === {{ $tmpl->id }}" class="space-y-4" {!! $loop->first ? '' : 'style="display: none;"' !!}>
                <form action="{{ route('admin.notifications.templates.update', $tmpl->id) }}" method="POST" class="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-xs space-y-4">
                    @csrf

                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <div>
                            <h3 class="text-base font-bold font-mono text-[#1E1B1B]">{{ $tmpl->event_key }}</h3>
                            <span class="text-xs text-gray-400">Kanal: {{ strtoupper($tmpl->channel->value ?? $tmpl->channel) }} • Sasaran: {{ strtoupper($tmpl->audience) }}</span>
                        </div>
                    </div>

                    @if(($tmpl->channel->value ?? $tmpl->channel) === 'mail')
                    <div class="space-y-1">
                        <label class="block text-xs font-bold uppercase text-gray-700">Subjek Email *</label>
                        <input type="text" name="subject" value="{{ $tmpl->subject }}" required
                               class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                    </div>
                    @endif

                    <div class="space-y-1">
                        <label class="block text-xs font-bold uppercase text-gray-700">Format Isi Pesan *</label>
                        <textarea name="body" rows="8" required
                                  class="w-full text-xs font-mono rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A] leading-relaxed">{{ $tmpl->body }}</textarea>
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <input type="checkbox" name="is_active" id="active_{{ $tmpl->id }}" value="1" {{ $tmpl->is_active ? 'checked' : '' }}
                               class="rounded border-gray-300 text-[#703A3A] focus:ring-[#703A3A]">
                        <label for="active_{{ $tmpl->id }}" class="text-xs font-bold text-gray-700">Aktifkan pengiriman template ini</label>
                    </div>

                    <div class="flex items-center justify-end pt-4 border-t border-gray-100">
                        <button type="submit" class="bg-[#703A3A] hover:bg-[#561D1E] text-white px-6 py-2.5 rounded-full text-xs font-bold uppercase tracking-wider shadow-xs transition-all">
                            Simpan Template
                        </button>
                    </div>
                </form>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
