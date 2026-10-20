@extends('layouts.admin')

@section('title', 'Template Dokumen Resmi')

@section('content')
<div class="space-y-6" x-data="{ selectedTemplate: {{ $templates->first()?->id ?? 'null' }} }">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-[#1E1B1B]">Template Dokumen PDF</h1>
            <p class="text-sm text-gray-500">Sesuaikan tata letak format surat izin, kuitansi, invoice BOS, dan e-voucher tapak</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Sidebar Templates List -->
        <div class="bg-white rounded-3xl p-4 border border-gray-100 shadow-xs space-y-1">
            @foreach($templates as $tmpl)
            <button type="button" @click="selectedTemplate = {{ $tmpl->id }}"
                    :class="selectedTemplate === {{ $tmpl->id }} ? 'bg-[#703A3A] text-white' : 'text-gray-700 hover:bg-stone-50'"
                    class="w-full text-left p-3 rounded-2xl transition-all">
                <span class="font-bold text-xs block">{{ $tmpl->title }}</span>
                <span class="text-[10px] font-mono opacity-80 uppercase block mt-0.5">{{ $tmpl->type }}</span>
            </button>
            @endforeach
        </div>

        <!-- Editor & Preview Panel -->
        <div class="lg:col-span-3">
            @foreach($templates as $tmpl)
            <div x-show="selectedTemplate === {{ $tmpl->id }}" class="space-y-4" {!! $loop->first ? '' : 'style="display: none;"' !!}>
                <form action="{{ route('admin.documents.templates.update', $tmpl->id) }}" method="POST" class="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-xs space-y-4">
                    @csrf

                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <div>
                            <h3 class="text-base font-bold text-[#1E1B1B]">{{ $tmpl->title }}</h3>
                            <span class="text-xs font-mono text-gray-400 uppercase">Tipe: {{ $tmpl->type }}</span>
                        </div>
                        <a href="{{ route('admin.documents.templates.preview', $tmpl->id) }}" target="_blank"
                           class="border border-stone-300 text-stone-700 hover:bg-stone-50 px-4 py-2 rounded-full text-xs font-bold uppercase tracking-wider flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px]">picture_as_pdf</span>
                            <span>Preview PDF Contoh</span>
                        </a>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-bold uppercase text-gray-700">Judul Dokumen *</label>
                        <input type="text" name="title" value="{{ $tmpl->title }}" required
                               class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-bold uppercase text-gray-700">Format Isi Dokumen (HTML & Placeholders) *</label>
                        <textarea name="body" rows="14" required
                                  class="w-full text-xs font-mono rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A] leading-relaxed">{{ $tmpl->body }}</textarea>
                        <p class="text-[11px] text-gray-400">Placeholder yang didukung: <code class="text-[#703A3A] font-mono">@verbatim{{booking.code}}@endverbatim, @verbatim{{booking.visit_date}}@endverbatim, @verbatim{{institution.name}}@endverbatim, @verbatim{{destination.name}}@endverbatim, @verbatim{{package.name}}@endverbatim, @verbatim{{items_table}}@endverbatim, @verbatim{{qr_checkin}}@endverbatim</code></p>
                    </div>

                    <div class="flex items-center justify-end pt-4 border-t border-gray-100">
                        <button type="submit" class="bg-[#703A3A] hover:bg-[#561D1E] text-white px-6 py-2.5 rounded-full text-xs font-bold uppercase tracking-wider shadow-xs transition-all">
                            Simpan Template Dokumen
                        </button>
                    </div>
                </form>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
