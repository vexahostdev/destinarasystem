@extends('layouts.admin')

@section('title', 'Manajemen Konten Halaman (CMS)')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-extrabold text-[#1E1B1B]">Manajemen Konten Halaman</h1>
        <p class="text-sm text-gray-500">Sesuaikan narasi beranda, panduan alur kerja, FAQ, serta dokumen syarat & ketentuan</p>
    </div>

    <!-- Section Tabs -->
    <div class="border-b border-gray-200">
        <nav class="flex space-x-6 text-xs font-bold uppercase tracking-wider">
            @foreach($defaultKeys as $key => $label)
            <a href="{{ route('admin.siteContents.index', ['key' => $key]) }}"
               class="py-3 px-1 border-b-2 transition-all {{ $activeKey === $key ? 'border-[#703A3A] text-[#703A3A]' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                {{ $label }}
            </a>
            @endforeach
        </nav>
    </div>

    @if($current)
    <form action="{{ route('admin.siteContents.update', $current->id) }}" method="POST" class="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-xs space-y-6">
        @csrf

        <div class="space-y-1">
            <label class="block text-xs font-bold uppercase text-gray-700">Judul Bagian / Halaman *</label>
            <input type="text" name="title" value="{{ old('title', $current->title) }}" required
                   class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
        </div>

        <div class="space-y-1">
            <label class="block text-xs font-bold uppercase text-gray-700">Isi Konten / Teks Lengkap (Markdown atau HTML) *</label>
            <textarea name="body" rows="12" required
                      class="w-full text-xs font-mono rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A] leading-relaxed">{{ old('body', $current->body) }}</textarea>
            <p class="text-[11px] text-gray-400">Gunakan format markdown atau baris teks rapi. Konten ini akan langsung ditampilkan di halaman publik terkait.</p>
        </div>

        <div class="flex items-center justify-end pt-4 border-t border-gray-100">
            <button type="submit" class="bg-[#703A3A] hover:bg-[#561D1E] text-white px-6 py-2.5 rounded-full text-xs font-bold uppercase tracking-wider shadow-xs transition-all">
                Simpan Pembaruan Konten
            </button>
        </div>
    </form>
    @endif
</div>
@endsection
