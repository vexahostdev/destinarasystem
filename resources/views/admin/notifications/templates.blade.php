@extends('layouts.admin')

@section('title', 'Template Notifikasi Transaksional')

@section('content')
<div class="space-y-6" x-data="{
    selectedTemplate: {{ $templates->first()?->id ?? 'null' }},
    showCreateModal: false,
    showPreviewModal: false,
    searchQuery: '',
    previewData: { event_key: '', channel: '', audience: '', subject: '', body: '' },
    async loadPreview(id) {
        try {
            const res = await fetch(`/admin/notifikasi/template/${id}/preview`, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
            if (res.ok) {
                this.previewData = await res.json();
                this.showPreviewModal = true;
            }
        } catch (e) {
            alert('Gagal memuat preview template.');
        }
    }
}">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-[#1E1B1B]">Template Notifikasi Otomatis</h1>
            <p class="text-sm text-gray-500">Sesuaikan pesan transaksional WhatsApp, Email, dan In-App untuk pemesan, mitra, dan internal</p>
        </div>
        <button type="button" @click="showCreateModal = true" class="inline-flex items-center gap-2 bg-[#703A3A] hover:bg-[#561D1E] text-white px-5 py-2.5 rounded-full text-xs font-bold uppercase tracking-wider shadow-xs transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Template Baru
        </button>
    </div>

    @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs px-4 py-3 rounded-2xl">
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="bg-rose-50 border border-rose-200 text-rose-800 text-xs px-4 py-3 rounded-2xl">
        {{ session('error') }}
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Sidebar Templates List -->
        <div class="bg-white rounded-3xl p-4 border border-gray-100 shadow-xs space-y-3">
            <div class="relative">
                <input type="text" x-model="searchQuery" placeholder="Cari event / channel..." class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A] pl-8">
                <svg class="w-4 h-4 absolute left-2.5 top-2.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <div class="space-y-1 max-h-[600px] overflow-y-auto pr-1">
                @foreach($templates as $tmpl)
                <div x-show="!searchQuery || '{{ strtolower($tmpl->event_key.' '.$tmpl->channel->value.' '.$tmpl->audience) }}'.includes(searchQuery.toLowerCase())">
                    <button type="button" @click="selectedTemplate = {{ $tmpl->id }}"
                            :class="selectedTemplate === {{ $tmpl->id }} ? 'bg-[#703A3A] text-white' : 'text-gray-700 hover:bg-stone-50'"
                            class="w-full text-left p-3 rounded-2xl transition-all flex items-center justify-between group">
                        <div>
                            <span class="font-bold text-xs block font-mono">{{ $tmpl->event_key }}</span>
                            <span class="text-[10px] opacity-80 uppercase block mt-0.5">{{ $tmpl->channel->value ?? $tmpl->channel }} • {{ $tmpl->audience }}</span>
                        </div>
                        <span class="text-[10px] px-2 py-0.5 rounded-full font-bold {{ $tmpl->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-500' }}">
                            {{ $tmpl->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </button>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Editor Panel -->
        <div class="lg:col-span-3">
            @foreach($templates as $tmpl)
            <div x-show="selectedTemplate === {{ $tmpl->id }}" class="space-y-4" {!! $loop->first ? '' : 'style="display: none;"' !!}>
                <div class="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-xs space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <div>
                            <h3 class="text-base font-bold font-mono text-[#1E1B1B]">{{ $tmpl->event_key }}</h3>
                            <span class="text-xs text-gray-400">Kanal: {{ strtoupper($tmpl->channel->value ?? $tmpl->channel) }} • Sasaran: {{ strtoupper($tmpl->audience) }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="loadPreview({{ $tmpl->id }})" class="border border-stone-200 text-gray-700 hover:bg-stone-50 px-3 py-1.5 rounded-full text-xs font-bold transition-all">
                                Preview Contoh
                            </button>
                            <form action="{{ route('admin.notifications.templates.toggle', $tmpl->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 rounded-full text-xs font-bold {{ $tmpl->is_active ? 'bg-rose-50 text-rose-700 hover:bg-rose-100' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                                    {{ $tmpl->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                        </div>
                    </div>

                    <form action="{{ route('admin.notifications.templates.update', $tmpl->id) }}" method="POST" class="space-y-4">
                        @csrf

                        @if(($tmpl->channel->value ?? $tmpl->channel) === 'mail')
                        <div class="space-y-1">
                            <label class="block text-xs font-bold uppercase text-gray-700">Subjek Email *</label>
                            <input type="text" name="subject" value="{{ $tmpl->subject }}" required
                                   class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                        </div>
                        @else
                        <div class="space-y-1">
                            <label class="block text-xs font-bold uppercase text-gray-700">Subjek / Judul Notifikasi (Opsional)</label>
                            <input type="text" name="subject" value="{{ $tmpl->subject }}"
                                   class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                        </div>
                        @endif

                        <div class="space-y-1">
                            <label class="block text-xs font-bold uppercase text-gray-700">Format Isi Pesan *</label>
                            <textarea name="body" rows="8" required
                                      class="w-full text-xs font-mono rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A] leading-relaxed">{{ $tmpl->body }}</textarea>
                            <p class="text-[11px] text-gray-400">Gunakan tag kurung kurawal ganda seperti <code class="bg-stone-100 px-1 py-0.5 rounded">@{{ booking_code }}</code>, <code class="bg-stone-100 px-1 py-0.5 rounded">@{{ total_amount }}</code>, dsb.</p>
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
            </div>
            @endforeach
        </div>
    </div>

    <!-- Modal Tambah Template Baru -->
    <div x-show="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 space-y-4 shadow-xl" @click.away="showCreateModal = false">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <h3 class="text-base font-bold text-[#1E1B1B]">Tambah Template Notifikasi Baru</h3>
                <button type="button" @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form action="{{ route('admin.notifications.templates.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-700">Event Key *</label>
                    <select name="event_key" required class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                        @foreach($availableEvents as $eventKey)
                        <option value="{{ $eventKey }}">{{ $eventKey }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-700">Kanal *</label>
                        <select name="channel" required class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                            <option value="mail">Email (mail)</option>
                            <option value="whatsapp">WhatsApp</option>
                            <option value="database">In-App (database)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-700">Sasaran (Audience) *</label>
                        <select name="audience" required class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                            <option value="buyer">Pembeli (buyer)</option>
                            <option value="partner">Mitra (partner)</option>
                            <option value="admin">Internal / Admin</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-gray-700">Subjek / Judul (Opsional)</label>
                    <input type="text" name="subject" placeholder="Contoh: Pemberitahuan Pembayaran" class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-gray-700">Isi Pesan (Body) *</label>
                    <textarea name="body" rows="6" required placeholder="Halo {{user_name}}, ..." class="w-full text-xs font-mono rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]"></textarea>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" id="create_active" value="1" checked class="rounded border-gray-300 text-[#703A3A] focus:ring-[#703A3A]">
                    <label for="create_active" class="text-xs font-bold text-gray-700">Langsung aktifkan template</label>
                </div>

                <div class="flex justify-end gap-2 pt-4 border-t border-gray-100">
                    <button type="button" @click="showCreateModal = false" class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-stone-50 rounded-full">Batal</button>
                    <button type="submit" class="bg-[#703A3A] hover:bg-[#561D1E] text-white px-5 py-2 rounded-full text-xs font-bold uppercase tracking-wider">Simpan Template</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Preview Template -->
    <div x-show="showPreviewModal" class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 space-y-4 shadow-xl" @click.away="showPreviewModal = false">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <div>
                    <h3 class="text-base font-bold text-[#1E1B1B]">Preview Contoh Pesan</h3>
                    <span class="text-xs text-gray-400" x-text="`${previewData.event_key} (${previewData.channel} / ${previewData.audience})`"></span>
                </div>
                <button type="button" @click="showPreviewModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="space-y-3 bg-stone-50 rounded-2xl p-4 border border-stone-200">
                <template x-if="previewData.subject">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-gray-400 block">Subjek</span>
                        <p class="text-xs font-bold text-gray-800" x-text="previewData.subject"></p>
                    </div>
                </template>
                <div>
                    <span class="text-[10px] uppercase font-bold text-gray-400 block">Isi Pesan</span>
                    <pre class="text-xs font-mono whitespace-pre-wrap text-gray-700 leading-relaxed font-sans" x-text="previewData.body"></pre>
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button type="button" @click="showPreviewModal = false" class="bg-[#703A3A] text-white px-5 py-2 rounded-full text-xs font-bold">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection
