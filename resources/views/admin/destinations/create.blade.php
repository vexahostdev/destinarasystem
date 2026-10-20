@extends('layouts.admin')

@section('title', 'Tambah Destinasi Baru')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.destinations.index') }}" class="text-xs font-bold text-gray-500 hover:text-gray-700 flex items-center gap-1 mb-2">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                <span>Kembali ke Katalog Destinasi</span>
            </a>
            <h1 class="text-2xl font-extrabold text-[#1E1B1B]">Tambah Destinasi Baru</h1>
            <p class="text-sm text-gray-500">Mendaftarkan tapak edukasi baru ke dalam direktori resmi platform</p>
        </div>
    </div>

    <form action="{{ route('admin.destinations.store') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-xs space-y-6">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Mitra Pengelola *</label>
                <select name="partner_id" required class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                    <option value="">Pilih Mitra...</option>
                    @foreach($partners as $p)
                        <option value="{{ $p->id }}" {{ old('partner_id') == $p->id ? 'selected' : '' }}>
                            {{ $p->name }} ({{ $p->partnerProfile?->organization_name ?? $p->email }})
                        </option>
                    @endforeach
                </select>
                @error('partner_id') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Kategori Destinasi *</label>
                <select name="category_id" required class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                    <option value="">Pilih Kategori...</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}" {{ old('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
                @error('category_id') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Nama Destinasi / Tapak *</label>
            <input type="text" name="name" value="{{ old('name') }}" required placeholder="Contoh: Desa Wisata Pengrajin Keramik Kasongan"
                   class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
            @error('name') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Provinsi *</label>
                <select name="province_id" required class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                    <option value="">Pilih Provinsi...</option>
                    @foreach($provinces as $prov)
                        <option value="{{ $prov->id }}" {{ old('province_id') == $prov->id ? 'selected' : '' }}>{{ $prov->name }}</option>
                    @endforeach
                </select>
                @error('province_id') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Kabupaten / Kota *</label>
                <input type="text" name="city" value="{{ old('city') }}" required placeholder="Contoh: Bantul"
                       class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                @error('city') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Alamat Lengkap</label>
            <textarea name="address" rows="2" placeholder="Alamat jalan, dusun, kelurahan, kecamatan..."
                      class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">{{ old('address') }}</textarea>
        </div>

        <div>
            <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Deskripsi Singkat (Ringkasan Kartu) *</label>
            <input type="text" name="short_description" value="{{ old('short_description') }}" required maxlength="300"
                   placeholder="Ringkasan 1-2 kalimat untuk kartu pencarian dan katalog..."
                   class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
            @error('short_description') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Deskripsi Lengkap & Latar Belakang *</label>
            <textarea name="description" rows="5" required placeholder="Deskripsi lengkap mengenai tapak, sejarah, ekosistem..."
                      class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">{{ old('description') }}</textarea>
            @error('description') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Sorotan Kurikulum & Muatan Edukatif</label>
            <textarea name="educational_highlights" rows="3" placeholder="Poin pembelajaran utama, relevansi Kurikulum Merdeka / P5..."
                      class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">{{ old('educational_highlights') }}</textarea>
        </div>

        <div>
            <label class="block text-xs font-bold uppercase text-gray-700 mb-2">Fasilitas Sarana Rombongan Tersedia</label>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                @foreach($facilities as $fac)
                    <label class="flex items-center gap-2 p-2.5 rounded-xl border border-gray-100 bg-gray-50/50 hover:bg-gray-50 cursor-pointer text-xs">
                        <input type="checkbox" name="facilities[]" value="{{ $fac->id }}"
                               {{ in_array($fac->id, old('facilities', [])) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-[#703A3A] focus:ring-[#703A3A]">
                        <span class="text-gray-700 font-medium">{{ $fac->name }}</span>
                    </label>
                @endforeach
            </div>
            @error('facilities') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Kapasitas Harian Default (Pax) *</label>
                <input type="number" name="default_daily_capacity" value="{{ old('default_daily_capacity', 50) }}" required min="1"
                       class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                @error('default_daily_capacity') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Foto Sampul Utama (Cover Image)</label>
                <input type="file" name="cover_image" accept="image/*"
                       class="w-full text-xs file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-[#703A3A] file:text-white hover:file:bg-[#561D1E]">
                @error('cover_image') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
            <a href="{{ route('admin.destinations.index') }}" class="px-5 py-2.5 rounded-full border border-gray-300 text-xs font-bold uppercase text-gray-600 hover:bg-gray-50">
                Batal
            </a>
            <button type="submit" class="bg-[#703A3A] hover:bg-[#561D1E] text-white px-6 py-2.5 rounded-full text-xs font-bold uppercase tracking-wider shadow-xs transition-all">
                Simpan & Terbitkan Destinasi
            </button>
        </div>
    </form>
</div>
@endsection
