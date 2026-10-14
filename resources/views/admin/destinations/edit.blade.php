@extends('layouts.admin')

@section('title', 'Kelola Destinasi: ' . $destination->name)

@section('content')
<div class="space-y-6">
    <!-- Breadcrumb & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-gray-100">
        <div>
            <a href="{{ route('admin.destinations.index') }}" class="text-xs font-bold text-gray-500 hover:text-gray-700 flex items-center gap-1 mb-2">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                <span>Kembali ke Katalog Destinasi</span>
            </a>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-extrabold text-[#1E1B1B]">{{ $destination->name }}</h1>
                <span class="px-3 py-1 text-xs font-bold rounded-full {{ $destination->status->badgeClass() }}">
                    {{ $destination->status->label() }}
                </span>
                @if($destination->is_featured)
                    <span class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-amber-100 text-amber-800 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">star</span>
                        Featured
                    </span>
                @endif
            </div>
            <p class="text-xs text-gray-500 mt-1">
                Pengelola: <strong>{{ $destination->partner?->partnerProfile?->organization_name ?? $destination->partner?->name }}</strong> &bull;
                Lokasi: {{ $destination->city }}, {{ $destination->province?->name }} &bull;
                Kapasitas: {{ $destination->default_daily_capacity }} pax/hari
            </p>
        </div>

        <div class="flex items-center gap-2">
            @can('destinations.feature')
                <form action="{{ route('admin.destinations.toggleFeatured', $destination->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-3 py-2 text-xs font-bold rounded-xl border border-gray-200 hover:bg-gray-50 flex items-center gap-1.5 transition">
                        <span class="material-symbols-outlined text-[16px] {{ $destination->is_featured ? 'text-amber-500 fill-current' : 'text-gray-400' }}">star</span>
                        <span>{{ $destination->is_featured ? 'Hapus Featured' : 'Jadikan Featured' }}</span>
                    </button>
                </form>
            @endcan

            @can('destinations.update')
                <form action="{{ route('admin.destinations.toggleActive', $destination->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-3 py-2 text-xs font-bold rounded-xl border {{ $destination->status->value === 'published' ? 'border-amber-200 text-amber-700 hover:bg-amber-50' : 'border-emerald-200 text-emerald-700 hover:bg-emerald-50' }} transition">
                        {{ $destination->status->value === 'published' ? 'Nonaktifkan Sementara' : 'Aktifkan Destinasi' }}
                    </button>
                </form>
            @endcan
        </div>
    </div>

    <!-- Tab Navigation -->
    @php
        $activeTab = request()->query('tab', 'info');
        $tabs = [
            'info' => ['label' => 'Informasi Dasar', 'icon' => 'info'],
            'media' => ['label' => 'Foto & Galeri (' . $destination->media->count() . ')', 'icon' => 'photo_library'],
            'facilities' => ['label' => 'Fasilitas (' . $destination->facilities->count() . ')', 'icon' => 'checklist'],
            'contacts' => ['label' => 'Kontak (' . $destination->contacts->count() . ')', 'icon' => 'contacts'],
            'packages' => ['label' => 'Paket Edukasi (' . $destination->allPackages->count() . ')', 'icon' => 'inventory_2'],
            'addons' => ['label' => 'Add-on (' . $destination->allAddons->count() . ')', 'icon' => 'extension'],
            'availability' => ['label' => 'Ketersediaan Slot', 'icon' => 'calendar_month'],
            'policy' => ['label' => 'Kebijakan Batal', 'icon' => 'policy'],
            'seo' => ['label' => 'SEO & Meta', 'icon' => 'search'],
            'curation' => ['label' => 'Riwayat Kurasi', 'icon' => 'history_edu'],
        ];
    @endphp

    <div class="flex items-center gap-1 overflow-x-auto pb-2 border-b border-gray-100">
        @foreach($tabs as $tabKey => $tab)
            <a href="{{ route('admin.destinations.edit', ['destination' => $destination->id, 'tab' => $tabKey]) }}"
               class="px-3.5 py-2 text-xs font-bold rounded-xl whitespace-nowrap flex items-center gap-1.5 transition {{ $activeTab === $tabKey ? 'bg-[#703A3A] text-white shadow-xs' : 'text-gray-600 hover:bg-gray-100' }}">
                <span class="material-symbols-outlined text-[16px]">{{ $tab['icon'] }}</span>
                <span>{{ $tab['label'] }}</span>
            </a>
        @endforeach
    </div>

    <!-- TAB 1: INFORMASI DASAR -->
    @if($activeTab === 'info')
        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs">
            <h2 class="text-base font-bold text-gray-800 mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-[#703A3A] text-[20px]">edit_note</span>
                <span>Edit Informasi Dasar Destinasi</span>
            </h2>

            <form action="{{ route('admin.destinations.update', $destination->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Nama Destinasi *</label>
                        <input type="text" name="name" value="{{ old('name', $destination->name) }}" required class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Kategori *</label>
                        <select name="category_id" required class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id', $destination->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Provinsi *</label>
                        <select name="province_id" required class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                            @foreach($provinces as $prov)
                                <option value="{{ $prov->id }}" {{ old('province_id', $destination->province_id) == $prov->id ? 'selected' : '' }}>{{ $prov->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Kota / Kabupaten *</label>
                        <input type="text" name="city" value="{{ old('city', $destination->city) }}" required class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Alamat Lengkap</label>
                    <textarea name="address" rows="2" class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">{{ old('address', $destination->address) }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Deskripsi Singkat (Ringkasan Kartu) *</label>
                    <input type="text" name="short_description" value="{{ old('short_description', $destination->short_description) }}" required maxlength="300" class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Deskripsi Lengkap *</label>
                    <textarea name="description" rows="5" required class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">{{ old('description', $destination->description) }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Muatan Edukasi & Kurikulum</label>
                    <textarea name="educational_highlights" rows="3" class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">{{ old('educational_highlights', $destination->educational_highlights) }}</textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Kapasitas Harian Default (Pax) *</label>
                        <input type="number" name="default_daily_capacity" value="{{ old('default_daily_capacity', $destination->default_daily_capacity) }}" min="1" required class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Kebijakan Pembatalan *</label>
                        <select name="cancellation_policy_id" required class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                            @foreach($cancellationPolicies as $pol)
                                <option value="{{ $pol->id }}" {{ old('cancellation_policy_id', $destination->cancellation_policy_id) == $pol->id ? 'selected' : '' }}>{{ $pol->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Ganti Foto Sampul (Cover Image)</label>
                    @if($destination->cover_image)
                        <div class="mb-2">
                            <img src="{{ Storage::url($destination->cover_image) }}" alt="Sampul" class="h-32 w-auto rounded-xl object-cover border border-gray-200">
                        </div>
                    @endif
                    <input type="file" name="cover_image" accept="image/*" class="text-xs text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#703A3A]/10 file:text-[#703A3A]">
                </div>

                <div class="pt-4 border-t border-gray-100 flex justify-end">
                    <button type="submit" class="px-5 py-2.5 bg-[#703A3A] hover:bg-[#5a2e2e] text-white text-xs font-bold rounded-xl shadow-xs transition">
                        Simpan Perubahan Informasi
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- TAB 2: FOTO & GALERI -->
    @if($activeTab === 'media')
        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs space-y-6">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                <div>
                    <h2 class="text-base font-bold text-gray-800">Galeri Foto Destinasi</h2>
                    <p class="text-xs text-gray-500">Maksimal 10 foto kualitas tinggi untuk ditampilkan di etalase pembeli</p>
                </div>
                <button type="button" onclick="document.getElementById('upload-modal').classList.remove('hidden')" class="px-3.5 py-2 bg-[#703A3A] text-white text-xs font-bold rounded-xl flex items-center gap-1.5 transition">
                    <span class="material-symbols-outlined text-[16px]">add_photo_alternate</span>
                    <span>Upload Foto Baru</span>
                </button>
            </div>

            @if($destination->media->isEmpty())
                <div class="py-12 text-center text-gray-400 text-xs">
                    <span class="material-symbols-outlined text-[48px] text-gray-300 block mb-2">image_not_supported</span>
                    Belum ada foto dalam galeri destinasi ini.
                </div>
            @else
                <form action="{{ route('admin.destinations.media.sort', $destination->id) }}" method="POST">
                    @csrf
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-4">
                        @foreach($destination->media->sortBy('sort_order') as $media)
                            <div class="group relative rounded-2xl overflow-hidden border border-gray-200 bg-gray-50">
                                <img src="{{ Storage::url($media->path) }}" alt="{{ $media->caption }}" class="h-40 w-full object-cover">
                                <div class="p-2.5 bg-white border-t border-gray-100">
                                    <p class="text-[11px] font-medium text-gray-700 truncate">{{ $media->caption ?? 'Tanpa keterangan' }}</p>
                                    <div class="flex items-center justify-between mt-2">
                                        <div class="flex items-center gap-1">
                                            <span class="text-[10px] text-gray-400">Urut:</span>
                                            <input type="number" name="order[]" value="{{ $media->id }}" class="hidden">
                                            <span class="text-xs font-bold text-gray-600">#{{ $loop->iteration }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </form>

                <div class="pt-4 border-t border-gray-100">
                    <h3 class="text-xs font-bold uppercase text-gray-500 mb-3">Kelola / Hapus Foto</h3>
                    <div class="space-y-2">
                        @foreach($destination->media as $m)
                            <div class="flex items-center justify-between p-2 rounded-xl border border-gray-100 hover:bg-gray-50 text-xs">
                                <div class="flex items-center gap-3">
                                    <img src="{{ Storage::url($m->path) }}" class="w-10 h-10 object-cover rounded-lg">
                                    <span class="text-gray-700 font-medium">{{ $m->caption ?? 'Foto #' . $m->id }}</span>
                                </div>
                                <form action="{{ route('admin.destinations.media.delete', ['destination' => $destination->id, 'media' => $m->id]) }}" method="POST" onsubmit="return confirm('Hapus foto ini dari galeri?')">
                                    @csrf
                                    <button type="submit" class="text-rose-600 hover:text-rose-800 font-bold text-xs flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[16px]">delete</span>
                                        <span>Hapus</span>
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Modal Upload Foto -->
            <div id="upload-modal" class="hidden fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-xl">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-bold text-gray-800">Upload Foto Galeri</h3>
                        <button type="button" onclick="document.getElementById('upload-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <form action="{{ route('admin.destinations.media.add', $destination->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-700 mb-1">File Gambar *</label>
                            <input type="file" name="image" required accept="image/*" class="w-full text-xs text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#703A3A]/10 file:text-[#703A3A]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Keterangan Foto (Caption)</label>
                            <input type="text" name="caption" placeholder="Contoh: Suasana workshop membatik..." class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" onclick="document.getElementById('upload-modal').classList.add('hidden')" class="px-4 py-2 border border-gray-200 text-xs font-bold rounded-xl text-gray-600">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-[#703A3A] text-white text-xs font-bold rounded-xl">Upload Foto</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- TAB 3: FASILITAS -->
    @if($activeTab === 'facilities')
        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs">
            <h2 class="text-base font-bold text-gray-800 mb-2">Fasilitas Tersedia</h2>
            <p class="text-xs text-gray-500 mb-6">Pilih fasilitas yang tersedia di lokasi tapak untuk memudahkan verifikasi pemesan</p>

            <form action="{{ route('admin.destinations.facilities.update', $destination->id) }}" method="POST">
                @csrf
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 mb-6">
                    @foreach($facilities as $facility)
                        <label class="flex items-center gap-2 p-3 rounded-2xl border border-gray-200 hover:bg-gray-50 cursor-pointer text-xs font-medium text-gray-700">
                            <input type="checkbox" name="facilities[]" value="{{ $facility->id }}"
                                   {{ $destination->facilities->contains($facility->id) ? 'checked' : '' }}
                                   class="rounded text-[#703A3A] focus:ring-[#703A3A]">
                            <span>{{ $facility->name }}</span>
                        </label>
                    @endforeach
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="px-5 py-2.5 bg-[#703A3A] text-white text-xs font-bold rounded-xl shadow-xs transition">
                        Simpan Daftar Fasilitas
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- TAB 4: KONTAK -->
    @if($activeTab === 'contacts')
        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs space-y-6">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                <div>
                    <h2 class="text-base font-bold text-gray-800">Kontak Narahubung Tapak</h2>
                    <p class="text-xs text-gray-500">Daftar PIC resmi tapak untuk koordinasi lapangan saat rombongan tiba</p>
                </div>
                <button type="button" onclick="document.getElementById('contact-modal').classList.remove('hidden')" class="px-3.5 py-2 bg-[#703A3A] text-white text-xs font-bold rounded-xl flex items-center gap-1.5 transition">
                    <span class="material-symbols-outlined text-[16px]">add</span>
                    <span>Tambah Kontak</span>
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-gray-50 text-gray-600 uppercase font-bold border-b border-gray-200">
                        <tr>
                            <th class="py-3 px-4">Nama Narahubung</th>
                            <th class="py-3 px-4">Peran / Jabatan</th>
                            <th class="py-3 px-4">Nomor Telepon/WA</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($destination->contacts as $contact)
                            <tr class="hover:bg-gray-50">
                                <td class="py-3 px-4 font-bold text-gray-800">{{ $contact->contact_name }}</td>
                                <td class="py-3 px-4 text-gray-600">{{ $contact->role ?? '-' }}</td>
                                <td class="py-3 px-4 text-gray-800 font-mono">{{ $contact->phone }}</td>
                                <td class="py-3 px-4">
                                    @if($contact->is_primary)
                                        <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold">Utama</span>
                                    @else
                                        <span class="text-gray-400 text-[11px]">-</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <form action="{{ route('admin.destinations.contacts.delete', ['destination' => $destination->id, 'contact' => $contact->id]) }}" method="POST" onsubmit="return confirm('Hapus kontak ini?')">
                                        @csrf
                                        <button type="submit" class="text-rose-600 hover:text-rose-800 font-bold">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-gray-400">Belum ada kontak terdaftar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Modal Tambah Kontak -->
            <div id="contact-modal" class="hidden fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-xl">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-bold text-gray-800">Tambah Kontak Narahubung</h3>
                        <button type="button" onclick="document.getElementById('contact-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <form action="{{ route('admin.destinations.contacts.save', $destination->id) }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Nama Lengkap *</label>
                            <input type="text" name="contact_name" required class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Peran / Jabatan</label>
                            <input type="text" name="role" placeholder="Contoh: Koordinator Lapangan" class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Nomor WhatsApp / Telepon *</label>
                            <input type="text" name="phone" required placeholder="08xxxxxxxxxx" class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="is_primary" value="1" id="is_primary" class="rounded text-[#703A3A] focus:ring-[#703A3A]">
                            <label for="is_primary" class="text-xs text-gray-700">Jadikan kontak utama</label>
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" onclick="document.getElementById('contact-modal').classList.add('hidden')" class="px-4 py-2 border border-gray-200 text-xs font-bold rounded-xl text-gray-600">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-[#703A3A] text-white text-xs font-bold rounded-xl">Simpan Kontak</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- TAB 5: PAKET EDUKASI -->
    @if($activeTab === 'packages')
        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs space-y-6">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                <div>
                    <h2 class="text-base font-bold text-gray-800">Paket Edukasi & Harga</h2>
                    <p class="text-xs text-gray-500">Destinasi wajib memiliki minimal 1 paket aktif agar dapat dipublikasikan</p>
                </div>
                <button type="button" onclick="document.getElementById('package-modal').classList.remove('hidden')" class="px-3.5 py-2 bg-[#703A3A] text-white text-xs font-bold rounded-xl flex items-center gap-1.5 transition">
                    <span class="material-symbols-outlined text-[16px]">add</span>
                    <span>Tambah Paket Baru</span>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @forelse($destination->allPackages as $pkg)
                    <div class="p-5 rounded-2xl border {{ $pkg->is_active ? 'border-gray-200 bg-white' : 'border-gray-200 bg-gray-50 opacity-70' }} space-y-3">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-sm font-bold text-gray-900">{{ $pkg->name }}</h3>
                                <p class="text-xs text-[#703A3A] font-extrabold mt-0.5">Rp {{ number_format($pkg->price_per_pax, 0, ',', '.') }} / pax</p>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $pkg->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-600' }}">
                                {{ $pkg->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>

                        <p class="text-xs text-gray-600 line-clamp-2">{{ $pkg->description }}</p>

                        <div class="pt-2 border-t border-gray-100 flex items-center justify-between text-[11px] text-gray-500">
                            <span>Min: {{ $pkg->min_participants }} pax &bull; Durasi: {{ $pkg->duration_days }} hari</span>
                            <div class="flex items-center gap-2">
                                <form action="{{ route('admin.destinations.packages.toggle', ['destination' => $destination->id, 'package' => $pkg->id]) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="text-xs font-bold text-gray-600 hover:text-gray-900">
                                        {{ $pkg->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                                <form action="{{ route('admin.destinations.packages.delete', ['destination' => $destination->id, 'package' => $pkg->id]) }}" method="POST" onsubmit="return confirm('Hapus paket ini?')">
                                    @csrf
                                    <button type="submit" class="text-xs font-bold text-rose-600 hover:text-rose-800">Hapus</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-2 py-8 text-center text-rose-600 text-xs font-medium">
                        Destinasi ini belum memiliki paket edukasi. Tambahkan paket agar destinasi dapat tayang.
                    </div>
                @endforelse
            </div>

            <!-- Modal Tambah Paket -->
            <div id="package-modal" class="hidden fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl p-6 max-w-lg w-full shadow-xl">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-bold text-gray-800">Tambah Paket Edukasi</h3>
                        <button type="button" onclick="document.getElementById('package-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <form action="{{ route('admin.destinations.packages.save', $destination->id) }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Nama Paket *</label>
                            <input type="text" name="name" required placeholder="Contoh: Paket Belajar Membatik 1 Hari" class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Harga per Pax (Rp) *</label>
                                <input type="number" name="price_per_pax" required min="0" placeholder="50000" class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Minimal Peserta *</label>
                                <input type="number" name="min_participants" required min="1" value="10" class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Durasi Kunjungan (Hari) *</label>
                            <input type="number" name="duration_days" required min="1" value="1" class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Deskripsi & Aktivitas Paket *</label>
                            <textarea name="description" rows="4" required placeholder="Jelaskan alur kegiatan, benefit, dan fasilitas yang didapatkan..." class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]"></textarea>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="is_active" value="1" id="pkg_is_active" checked class="rounded text-[#703A3A] focus:ring-[#703A3A]">
                            <label for="pkg_is_active" class="text-xs text-gray-700">Aktifkan paket langsung</label>
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" onclick="document.getElementById('package-modal').classList.add('hidden')" class="px-4 py-2 border border-gray-200 text-xs font-bold rounded-xl text-gray-600">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-[#703A3A] text-white text-xs font-bold rounded-xl">Simpan Paket</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- TAB 6: ADD-ON -->
    @if($activeTab === 'addons')
        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs space-y-6">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                <div>
                    <h2 class="text-base font-bold text-gray-800">Add-on Layanan & Fasilitas Tambahan</h2>
                    <p class="text-xs text-gray-500">Layanan opsional seperti makan siang, pemandu lokal, atau sewa perlengkapan</p>
                </div>
                <button type="button" onclick="document.getElementById('addon-modal').classList.remove('hidden')" class="px-3.5 py-2 bg-[#703A3A] text-white text-xs font-bold rounded-xl flex items-center gap-1.5 transition">
                    <span class="material-symbols-outlined text-[16px]">add</span>
                    <span>Tambah Add-on</span>
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-gray-50 text-gray-600 uppercase font-bold border-b border-gray-200">
                        <tr>
                            <th class="py-3 px-4">Nama Layanan</th>
                            <th class="py-3 px-4">Tipe Penetapan Harga</th>
                            <th class="py-3 px-4">Tarif (Rp)</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($destination->allAddons as $addon)
                            <tr class="hover:bg-gray-50">
                                <td class="py-3 px-4 font-bold text-gray-800">{{ $addon->name }}</td>
                                <td class="py-3 px-4 text-gray-600 uppercase font-mono text-[11px]">{{ $addon->pricing_type }}</td>
                                <td class="py-3 px-4 font-bold text-gray-800">Rp {{ number_format($addon->price, 0, ',', '.') }}</td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $addon->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-600' }}">
                                        {{ $addon->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <form action="{{ route('admin.destinations.addons.delete', ['destination' => $destination->id, 'addon' => $addon->id]) }}" method="POST" onsubmit="return confirm('Hapus add-on ini?')">
                                        @csrf
                                        <button type="submit" class="text-rose-600 hover:text-rose-800 font-bold">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-gray-400">Belum ada add-on terdaftar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Modal Tambah Addon -->
            <div id="addon-modal" class="hidden fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-xl">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-bold text-gray-800">Tambah Add-on</h3>
                        <button type="button" onclick="document.getElementById('addon-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <form action="{{ route('admin.destinations.addons.save', $destination->id) }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Nama Layanan Add-on *</label>
                            <input type="text" name="name" required placeholder="Contoh: Paket Makan Siang Tradisional" class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Tarif (Rp) *</label>
                                <input type="number" name="price" required min="0" placeholder="25000" class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Tipe Harga *</label>
                                <select name="pricing_type" required class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                                    <option value="per_pax">Per Pax</option>
                                    <option value="per_group">Per Rombongan</option>
                                    <option value="fixed">Biaya Tetap</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Keterangan Singkat</label>
                            <input type="text" name="description" placeholder="Penjelasan singkat add-on..." class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="is_active" value="1" id="addon_active" checked class="rounded text-[#703A3A] focus:ring-[#703A3A]">
                            <label for="addon_active" class="text-xs text-gray-700">Aktifkan add-on</label>
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" onclick="document.getElementById('addon-modal').classList.add('hidden')" class="px-4 py-2 border border-gray-200 text-xs font-bold rounded-xl text-gray-600">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-[#703A3A] text-white text-xs font-bold rounded-xl">Simpan Add-on</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- TAB 7: KETERSEDIAAN / SLOTS -->
    @if($activeTab === 'availability')
        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs space-y-6">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                <div>
                    <h2 class="text-base font-bold text-gray-800">Kalender & Slot Ketersediaan</h2>
                    <p class="text-xs text-gray-500">Kelola kuota peserta per tanggal kunjungan untuk mencegah overbooking</p>
                </div>
                <button type="button" onclick="document.getElementById('bulk-slot-modal').classList.remove('hidden')" class="px-3.5 py-2 bg-[#703A3A] text-white text-xs font-bold rounded-xl flex items-center gap-1.5 transition">
                    <span class="material-symbols-outlined text-[16px]">date_range</span>
                    <span>Generate Slot Massal</span>
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-gray-50 text-gray-600 uppercase font-bold border-b border-gray-200">
                        <tr>
                            <th class="py-3 px-4">Tanggal Kunjungan</th>
                            <th class="py-3 px-4">Kapasitas Maksimal</th>
                            <th class="py-3 px-4">Dipesan (Booked)</th>
                            <th class="py-3 px-4">Ditahan (Held)</th>
                            <th class="py-3 px-4">Sisa Kuota</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Ubah Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($destination->availabilitySlots as $slot)
                            @php
                                $remaining = max(0, $slot->capacity - ($slot->booked_count + $slot->held_count));
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="py-3 px-4 font-bold text-gray-800">{{ \Carbon\Carbon::parse($slot->date)->translatedFormat('l, d M Y') }}</td>
                                <td class="py-3 px-4">{{ $slot->capacity }} pax</td>
                                <td class="py-3 px-4 font-bold text-emerald-600">{{ $slot->booked_count }}</td>
                                <td class="py-3 px-4 font-bold text-amber-600">{{ $slot->held_count }}</td>
                                <td class="py-3 px-4 font-bold {{ $remaining <= 0 ? 'text-rose-600' : 'text-gray-800' }}">{{ $remaining }} pax</td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $slot->status === 'open' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                        {{ strtoupper($slot->status) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <form action="{{ route('admin.destinations.slots.update', $destination->id) }}" method="POST" class="inline-flex items-center gap-1">
                                        @csrf
                                        <input type="hidden" name="slot_id" value="{{ $slot->id }}">
                                        <input type="hidden" name="capacity" value="{{ $slot->capacity }}">
                                        <input type="hidden" name="status" value="{{ $slot->status === 'open' ? 'closed' : 'open' }}">
                                        <button type="submit" class="text-xs font-bold text-[#703A3A] hover:underline">
                                            {{ $slot->status === 'open' ? 'Tutup Tanggal' : 'Buka Tanggal' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-gray-400">Belum ada slot ketersediaan 60 hari ke depan. Silakan gunakan Generate Slot Massal.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Modal Bulk Generate Slot -->
            <div id="bulk-slot-modal" class="hidden fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-xl">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-bold text-gray-800">Generate Slot Massal</h3>
                        <button type="button" onclick="document.getElementById('bulk-slot-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <form action="{{ route('admin.destinations.slots.bulk', $destination->id) }}" method="POST" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Mulai Tanggal *</label>
                                <input type="date" name="start_date" required value="{{ now()->toDateString() }}" class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Sampai Tanggal *</label>
                                <input type="date" name="end_date" required value="{{ now()->addMonths(2)->toDateString() }}" class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Kapasitas Kuota Harian *</label>
                            <input type="number" name="capacity" required min="1" value="{{ $destination->default_daily_capacity }}" class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-700 mb-2">Hari Operasional Dibuka</label>
                            <div class="grid grid-cols-4 gap-2 text-xs">
                                @foreach(['Senin' => 1, 'Selasa' => 2, 'Rabu' => 3, 'Kamis' => 4, 'Jumat' => 5, 'Sabtu' => 6, 'Minggu' => 7] as $dayName => $dayNum)
                                    <label class="flex items-center gap-1.5 p-2 rounded-lg border border-gray-200">
                                        <input type="checkbox" name="days_of_week[]" value="{{ $dayNum }}" checked class="rounded text-[#703A3A] focus:ring-[#703A3A]">
                                        <span>{{ $dayName }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" onclick="document.getElementById('bulk-slot-modal').classList.add('hidden')" class="px-4 py-2 border border-gray-200 text-xs font-bold rounded-xl text-gray-600">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-[#703A3A] text-white text-xs font-bold rounded-xl">Generate Slot</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- TAB 8: KEBIJAKAN PEMBATALAN -->
    @if($activeTab === 'policy')
        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs space-y-6">
            <h2 class="text-base font-bold text-gray-800">Kebijakan Pembatalan & Pengembalian Dana</h2>
            <p class="text-xs text-gray-500">Pilih skema penalti dan refund pembatalan mandiri pemesan yang berlaku untuk tapak ini</p>

            <form action="{{ route('admin.destinations.update', $destination->id) }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="name" value="{{ $destination->name }}">
                <input type="hidden" name="category_id" value="{{ $destination->category_id }}">
                <input type="hidden" name="province_id" value="{{ $destination->province_id }}">
                <input type="hidden" name="city" value="{{ $destination->city }}">
                <input type="hidden" name="short_description" value="{{ $destination->short_description }}">
                <input type="hidden" name="description" value="{{ $destination->description }}">
                <input type="hidden" name="default_daily_capacity" value="{{ $destination->default_daily_capacity }}">

                <div class="space-y-3">
                    @foreach($cancellationPolicies as $pol)
                        <label class="block p-4 rounded-2xl border {{ $destination->cancellation_policy_id == $pol->id ? 'border-[#703A3A] bg-[#703A3A]/5' : 'border-gray-200 hover:bg-gray-50' }} cursor-pointer">
                            <div class="flex items-center gap-3">
                                <input type="radio" name="cancellation_policy_id" value="{{ $pol->id }}" {{ $destination->cancellation_policy_id == $pol->id ? 'checked' : '' }} class="text-[#703A3A] focus:ring-[#703A3A]">
                                <div>
                                    <h3 class="text-xs font-bold text-gray-800">{{ $pol->name }}</h3>
                                    <p class="text-[11px] text-gray-500 mt-0.5">{{ $pol->description }}</p>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>

                <div class="flex justify-end pt-4 border-t border-gray-100">
                    <button type="submit" class="px-5 py-2.5 bg-[#703A3A] text-white text-xs font-bold rounded-xl shadow-xs transition">
                        Perbarui Kebijakan Pembatalan
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- TAB 9: SEO & META -->
    @if($activeTab === 'seo')
        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs space-y-6">
            <h2 class="text-base font-bold text-gray-800">Optimasi Mesin Pencari (SEO)</h2>
            <p class="text-xs text-gray-500">Konfigurasikan judul dan deskripsi meta untuk meningkatkan visibilitas di Google</p>

            <form action="{{ route('admin.destinations.update', $destination->id) }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="name" value="{{ $destination->name }}">
                <input type="hidden" name="category_id" value="{{ $destination->category_id }}">
                <input type="hidden" name="province_id" value="{{ $destination->province_id }}">
                <input type="hidden" name="city" value="{{ $destination->city }}">
                <input type="hidden" name="short_description" value="{{ $destination->short_description }}">
                <input type="hidden" name="description" value="{{ $destination->description }}">
                <input type="hidden" name="default_daily_capacity" value="{{ $destination->default_daily_capacity }}">
                <input type="hidden" name="cancellation_policy_id" value="{{ $destination->cancellation_policy_id }}">

                <div>
                    <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Meta Title</label>
                    <input type="text" name="meta_title" value="{{ old('meta_title', $destination->meta_title) }}" placeholder="{{ $destination->name }} | Destinara" class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Meta Description</label>
                    <textarea name="meta_description" rows="3" placeholder="Deskripsi ringkas yang muncul pada cuplikan hasil pencarian mesin pencari..." class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]">{{ old('meta_description', $destination->meta_description) }}</textarea>
                </div>

                <div class="flex justify-end pt-4 border-t border-gray-100">
                    <button type="submit" class="px-5 py-2.5 bg-[#703A3A] text-white text-xs font-bold rounded-xl shadow-xs transition">
                        Simpan Pengaturan SEO
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- TAB 10: RIWAYAT KURASI & MODERASI -->
    @if($activeTab === 'curation')
        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs space-y-6">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                <div>
                    <h2 class="text-base font-bold text-gray-800">Riwayat Kurasi & Moderasi Status</h2>
                    <p class="text-xs text-gray-500">Catatan tinjauan administrasi sebelum publikasi resmi ke katalog publik</p>
                </div>

                <span class="px-3 py-1 text-xs font-bold rounded-full {{ $destination->status->badgeClass() }}">
                    Status: {{ $destination->status->label() }}
                </span>
            </div>

            @can('destinations.verify')
                <div class="p-4 rounded-2xl bg-gray-50 border border-gray-200">
                    <h3 class="text-xs font-bold uppercase text-gray-700 mb-2">Tindakan Moderasi Kurasi</h3>
                    <form action="{{ route('admin.destinations.updateStatus', $destination->id) }}" method="POST" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-[11px] font-bold text-gray-600 mb-1">Catatan Kurasi / Alasan</label>
                            <textarea name="notes" rows="2" placeholder="Tuliskan catatan perbaikan atau alasan persetujuan..." class="w-full text-xs rounded-xl border-gray-200 focus:border-[#703A3A] focus:ring-[#703A3A]"></textarea>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="submit" name="action" value="approve" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition">
                                Setujui & Terbitkan (Approve)
                            </button>
                            <button type="submit" name="action" value="revision" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-xl transition">
                                Minta Revisi Mitra (Revision Required)
                            </button>
                            <button type="submit" name="action" value="reject" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl transition">
                                Tolak / Arsipkan (Archive)
                            </button>
                        </div>
                    </form>
                </div>
            @endcan

            <div class="space-y-3">
                <h3 class="text-xs font-bold uppercase text-gray-500">Log Catatan Moderasi</h3>
                @forelse($destination->moderations as $mod)
                    <div class="p-4 rounded-2xl border border-gray-100 bg-white space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-gray-800">{{ $mod->admin?->name ?? 'Admin Sistem' }}</span>
                            <span class="text-gray-400 font-mono">{{ $mod->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $mod->action === 'approve' ? 'bg-emerald-100 text-emerald-800' : ($mod->action === 'revision' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }}">
                            {{ $mod->action }}
                        </span>
                        <p class="text-xs text-gray-600 mt-1">{{ $mod->note ?? 'Tanpa catatan tertulis' }}</p>
                    </div>
                @empty
                    <p class="text-xs text-gray-400 italic">Belum ada catatan kurasi untuk destinasi ini.</p>
                @endforelse
            </div>
        </div>
    @endif
</div>
@endsection
