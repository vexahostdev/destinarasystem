@extends('layouts.partner')

@section('title', 'Kelola Tapak: ' . $destination->name)

@section('content')
<div class="space-y-8 max-w-5xl mx-auto">
    <!-- Breadcrumb & Header -->
    <div class="pb-4 border-b border-[#2B211E]/12">
        <div class="flex items-center gap-2 text-xs text-[#2B211E]/60 mb-2">
            <a href="{{ route('mitra.dashboard') }}" class="hover:text-[#703A3A]">Dashboard</a>
            <span>/</span>
            <a href="{{ route('mitra.destinations') }}" class="hover:text-[#703A3A]">Tapak Kelolaan</a>
            <span>/</span>
            <span class="text-[#703A3A] font-semibold">{{ $destination->name }}</span>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#2B211E]">{{ $destination->name }}</h1>
                    <span class="px-3 py-1 text-xs font-bold uppercase tracking-wider {{ $destination->status->badgeClass() }}">
                        {{ $destination->status->label() }}
                    </span>
                </div>
                <p class="text-xs sm:text-sm text-[#2B211E]/70 mt-1">
                    {{ $destination->city }}, {{ $destination->province?->name }} &bull;
                    Kapasitas Default: {{ $destination->default_daily_capacity }} pax/hari
                </p>
            </div>

            <div class="flex items-center gap-2">
                @if(in_array($destination->status->value, ['draft', 'revision_required']))
                    <form action="{{ route('mitra.destinations.submit', $destination->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="rgs-btn-dark px-4 py-2 text-xs font-bold uppercase tracking-wider shadow-xs flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px]">send</span>
                            <span>Ajukan Kurasi ke Admin</span>
                        </button>
                    </form>
                @endif

                @if(in_array($destination->status->value, ['published', 'inactive']))
                    <form action="{{ route('mitra.destinations.toggleActive', $destination->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-4 py-2 text-xs font-bold uppercase tracking-wider border border-[#2B211E]/20 hover:bg-[#2B211E]/5 transition">
                            {{ $destination->status->value === 'published' ? 'Nonaktifkan Sementara' : 'Aktifkan Kembali' }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <!-- Alert Banner jika Perlu Revisi (revision_required) -->
    @if($destination->status->value === 'revision_required')
        @php
            $latestRevision = $destination->moderations->where('action', 'revision')->last();
        @endphp
        <div class="p-5 rounded-2xl bg-amber-50 border-2 border-amber-300 space-y-2">
            <div class="flex items-center gap-2 text-amber-900 font-bold text-sm">
                <span class="material-symbols-outlined text-amber-600">warning</span>
                <span>Catatan Revisi dari Tim Kurasi Destinara</span>
            </div>
            <p class="text-xs text-amber-800 leading-relaxed pl-7">
                {{ $latestRevision?->note ?? 'Mohon periksa kembali kelengkapan paket, dokumen fasilitas, atau informasi tapak sebelum mengajukan kembali.' }}
            </p>
            <p class="text-[11px] text-amber-700/80 pl-7 font-medium">
                Setelah selesai memperbaiki rincian di bawah ini, klik tombol <strong>"Ajukan Kurasi ke Admin"</strong> di bagian atas.
            </p>
        </div>
    @endif

    <!-- Alert Paket Minimal -->
    @if($destination->allPackages->where('is_active', true)->isEmpty())
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center gap-2">
            <span class="material-symbols-outlined text-rose-600 text-[18px]">error</span>
            <span>Destinasi belum memiliki minimal 1 paket aktif. Tambahkan dan aktifkan paket di tab <strong>Paket Edukasi</strong> agar dapat diajukan kurasi.</span>
        </div>
    @endif

    <!-- Tab Navigation -->
    @php
        $activeTab = request()->query('tab', 'info');
        $tabs = [
            'info' => ['label' => 'Identitas & Lokasi', 'icon' => 'info'],
            'media' => ['label' => 'Galeri Foto (' . $destination->media->count() . ')', 'icon' => 'photo_library'],
            'facilities' => ['label' => 'Fasilitas (' . $destination->facilities->count() . ')', 'icon' => 'checklist'],
            'contacts' => ['label' => 'Kontak PIC (' . $destination->contacts->count() . ')', 'icon' => 'contacts'],
            'packages' => ['label' => 'Paket Edukasi (' . $destination->allPackages->count() . ')', 'icon' => 'inventory_2'],
            'addons' => ['label' => 'Add-on (' . $destination->allAddons->count() . ')', 'icon' => 'extension'],
            'availability' => ['label' => 'Kalender Slot', 'icon' => 'calendar_month'],
            'policy' => ['label' => 'Kebijakan Batal', 'icon' => 'policy'],
            'seo' => ['label' => 'SEO & Meta', 'icon' => 'search'],
        ];
    @endphp

    <div class="flex items-center gap-1 overflow-x-auto pb-2 border-b border-[#2B211E]/10">
        @foreach($tabs as $tabKey => $tab)
            <a href="{{ route('mitra.destinations.edit', ['destination' => $destination->id, 'tab' => $tabKey]) }}"
               class="px-3.5 py-2 text-xs font-bold rounded-xl whitespace-nowrap flex items-center gap-1.5 transition {{ $activeTab === $tabKey ? 'bg-[#703A3A] text-white shadow-xs' : 'text-[#2B211E]/70 hover:bg-[#2B211E]/5' }}">
                <span class="material-symbols-outlined text-[16px]">{{ $tab['icon'] }}</span>
                <span>{{ $tab['label'] }}</span>
            </a>
        @endforeach
    </div>

    <!-- TAB 1: IDENTITAS & LOKASI -->
    @if($activeTab === 'info')
        <div class="rgs-card p-6 border-t-4 border-t-[#703A3A] space-y-6">
            <h2 class="font-serif text-lg font-bold text-[#2B211E]">Perbarui Identitas & Wilayah Tapak</h2>

            <form action="{{ route('mitra.destinations.update', $destination->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E] mb-1">Nama Resmi Tapak *</label>
                        <input type="text" name="name" value="{{ old('name', $destination->name) }}" required class="w-full bg-[#faf6f0] border border-[#2B211E]/20 px-3.5 py-2 text-xs font-medium text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E] mb-1">Kategori *</label>
                        <select name="category_id" required class="w-full bg-[#faf6f0] border border-[#2B211E]/20 px-3.5 py-2 text-xs font-medium text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id', $destination->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E] mb-1">Provinsi *</label>
                        <select name="province_id" required class="w-full bg-[#faf6f0] border border-[#2B211E]/20 px-3.5 py-2 text-xs font-medium text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                            @foreach($provinces as $prov)
                                <option value="{{ $prov->id }}" {{ old('province_id', $destination->province_id) == $prov->id ? 'selected' : '' }}>{{ $prov->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E] mb-1">Kabupaten / Kota *</label>
                        <input type="text" name="city" value="{{ old('city', $destination->city) }}" required class="w-full bg-[#faf6f0] border border-[#2B211E]/20 px-3.5 py-2 text-xs font-medium text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E] mb-1">Alamat Lengkap Tapak</label>
                    <textarea name="address" rows="2" class="w-full bg-[#faf6f0] border border-[#2B211E]/20 px-3.5 py-2 text-xs font-medium text-[#2B211E] focus:outline-none focus:border-[#703A3A]">{{ old('address', $destination->address) }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E] mb-1">Deskripsi Singkat (Ringkasan Etalase) *</label>
                    <input type="text" name="short_description" value="{{ old('short_description', $destination->short_description) }}" required maxlength="300" class="w-full bg-[#faf6f0] border border-[#2B211E]/20 px-3.5 py-2 text-xs font-medium text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E] mb-1">Deskripsi Lengkap & Narasi Budaya/Alam *</label>
                    <textarea name="description" rows="5" required class="w-full bg-[#faf6f0] border border-[#2B211E]/20 px-3.5 py-2 text-xs font-medium text-[#2B211E] focus:outline-none focus:border-[#703A3A]">{{ old('description', $destination->description) }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E] mb-1">Muatan Edukasi & Nilai Pembelajaran</label>
                    <textarea name="educational_highlights" rows="3" class="w-full bg-[#faf6f0] border border-[#2B211E]/20 px-3.5 py-2 text-xs font-medium text-[#2B211E] focus:outline-none focus:border-[#703A3A]">{{ old('educational_highlights', $destination->educational_highlights) }}</textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E] mb-1">Kapasitas Harian Default (Pax) *</label>
                        <input type="number" name="default_daily_capacity" value="{{ old('default_daily_capacity', $destination->default_daily_capacity) }}" min="1" required class="w-full bg-[#faf6f0] border border-[#2B211E]/20 px-3.5 py-2 text-xs font-medium text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E] mb-1">Kebijakan Pembatalan *</label>
                        <select name="cancellation_policy_id" required class="w-full bg-[#faf6f0] border border-[#2B211E]/20 px-3.5 py-2 text-xs font-medium text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                            @foreach($policies as $pol)
                                <option value="{{ $pol->id }}" {{ old('cancellation_policy_id', $destination->cancellation_policy_id) == $pol->id ? 'selected' : '' }}>{{ $pol->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E] mb-1">Foto Sampul Utama</label>
                    @if($destination->cover_image)
                        <div class="mb-2">
                            <img src="{{ Storage::url($destination->cover_image) }}" alt="Sampul" class="h-32 w-auto object-cover border border-[#2B211E]/20">
                        </div>
                    @endif
                    <input type="file" name="cover_image" accept="image/*" class="text-xs text-[#2B211E] file:mr-4 file:py-2 file:px-4 file:border-0 file:text-xs file:font-bold file:bg-[#703A3A] file:text-white">
                </div>

                <div class="pt-4 border-t border-[#2B211E]/10 flex justify-end">
                    <button type="submit" class="rgs-btn-dark px-5 py-2.5 text-xs font-bold uppercase tracking-wider shadow-xs">
                        Simpan Identitas Tapak
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- TAB 2: GALERI FOTO -->
    @if($activeTab === 'media')
        <div class="rgs-card p-6 border-t-4 border-t-[#703A3A] space-y-6">
            <div class="flex items-center justify-between pb-3 border-b border-[#2B211E]/10">
                <div>
                    <h2 class="font-serif text-lg font-bold text-[#2B211E]">Galeri Foto Tapak</h2>
                    <p class="text-xs text-[#2B211E]/60">Maksimal 10 foto aktivitas, lanskap, dan fasilitas pembelajaran</p>
                </div>
                <button type="button" onclick="document.getElementById('upload-modal').classList.remove('hidden')" class="rgs-btn-dark px-3.5 py-2 text-xs font-bold uppercase tracking-wider flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">add_photo_alternate</span>
                    <span>Upload Foto</span>
                </button>
            </div>

            @if($destination->media->isEmpty())
                <div class="py-12 text-center text-[#2B211E]/40 text-xs">
                    <span class="material-symbols-outlined text-[48px] block mb-2">image_not_supported</span>
                    Belum ada foto galeri. Unggah foto untuk meningkatkan daya tarik kunjungan sekolah.
                </div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    @foreach($destination->media->sortBy('sort_order') as $media)
                        <div class="border border-[#2B211E]/10 bg-[#faf6f0] p-2 space-y-2">
                            <img src="{{ Storage::url($media->path) }}" class="h-36 w-full object-cover">
                            <p class="text-[11px] font-medium text-[#2B211E] truncate">{{ $media->caption ?? 'Tanpa keterangan' }}</p>
                            <form action="{{ route('mitra.destinations.media.delete', ['destination' => $destination->id, 'media' => $media->id]) }}" method="POST" onsubmit="return confirm('Hapus foto ini?')">
                                @csrf
                                <button type="submit" class="w-full text-center py-1 text-xs text-rose-600 hover:text-rose-800 font-bold">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Modal Upload Foto -->
            <div id="upload-modal" class="hidden fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
                <div class="bg-[#faf6f0] p-6 max-w-md w-full border border-[#2B211E]/20 shadow-xl space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="font-serif text-base font-bold text-[#2B211E]">Upload Foto Galeri</h3>
                        <button type="button" onclick="document.getElementById('upload-modal').classList.add('hidden')" class="text-[#2B211E]/60 hover:text-[#2B211E]">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <form action="{{ route('mitra.destinations.media.add', $destination->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E] mb-1">File Gambar *</label>
                            <input type="file" name="image" required accept="image/*" class="w-full text-xs text-[#2B211E] file:mr-3 file:py-2 file:px-4 file:border-0 file:text-xs file:font-bold file:bg-[#703A3A] file:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E] mb-1">Keterangan Foto (Caption)</label>
                            <input type="text" name="caption" placeholder="Contoh: Praktik menanam bibit pohon..." class="w-full bg-white border border-[#2B211E]/20 px-3 py-2 text-xs font-medium text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" onclick="document.getElementById('upload-modal').classList.add('hidden')" class="px-4 py-2 border border-[#2B211E]/20 text-xs font-bold uppercase tracking-wider text-[#2B211E]">Batal</button>
                            <button type="submit" class="rgs-btn-dark px-4 py-2 text-xs font-bold uppercase tracking-wider">Upload</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- TAB 3: FASILITAS -->
    @if($activeTab === 'facilities')
        <div class="rgs-card p-6 border-t-4 border-t-[#703A3A] space-y-6">
            <h2 class="font-serif text-lg font-bold text-[#2B211E]">Fasilitas yang Tersedia di Tapak</h2>
            <p class="text-xs text-[#2B211E]/60">Centang semua sarana pendukung yang siap digunakan oleh rombongan pembeli</p>

            <form action="{{ route('mitra.destinations.facilities.update', $destination->id) }}" method="POST">
                @csrf
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 mb-6">
                    @foreach($facilities as $facility)
                        <label class="flex items-center gap-2 p-3 bg-[#faf6f0] border border-[#2B211E]/15 cursor-pointer text-xs font-medium text-[#2B211E]">
                            <input type="checkbox" name="facilities[]" value="{{ $facility->id }}"
                                   {{ $destination->facilities->contains($facility->id) ? 'checked' : '' }}
                                   class="text-[#703A3A] focus:ring-[#703A3A]">
                            <span>{{ $facility->name }}</span>
                        </label>
                    @endforeach
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="rgs-btn-dark px-5 py-2.5 text-xs font-bold uppercase tracking-wider">
                        Simpan Fasilitas
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- TAB 4: KONTAK PIC -->
    @if($activeTab === 'contacts')
        <div class="rgs-card p-6 border-t-4 border-t-[#703A3A] space-y-6">
            <div class="flex items-center justify-between pb-3 border-b border-[#2B211E]/10">
                <div>
                    <h2 class="font-serif text-lg font-bold text-[#2B211E]">Kontak Narahubung Tapak</h2>
                    <p class="text-xs text-[#2B211E]/60">PIC lapangan yang akan menyambut rombongan dan memverifikasi QR Check-in</p>
                </div>
                <button type="button" onclick="document.getElementById('contact-modal').classList.remove('hidden')" class="rgs-btn-dark px-3.5 py-2 text-xs font-bold uppercase tracking-wider flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">add</span>
                    <span>Tambah PIC</span>
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-[#faf6f0] text-[#2B211E] uppercase font-bold border-b border-[#2B211E]/10">
                        <tr>
                            <th class="py-3 px-4">Nama Narahubung</th>
                            <th class="py-3 px-4">Peran / Posisi</th>
                            <th class="py-3 px-4">Nomor WhatsApp</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#2B211E]/10">
                        @forelse($destination->contacts as $contact)
                            <tr>
                                <td class="py-3 px-4 font-bold text-[#2B211E]">{{ $contact->contact_name }}</td>
                                <td class="py-3 px-4 text-[#2B211E]/70">{{ $contact->role ?? '-' }}</td>
                                <td class="py-3 px-4 font-mono">{{ $contact->phone }}</td>
                                <td class="py-3 px-4">
                                    @if($contact->is_primary)
                                        <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-bold">Utama</span>
                                    @else
                                        <span class="text-[#2B211E]/40 text-[11px]">-</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <form action="{{ route('mitra.destinations.contacts.delete', ['destination' => $destination->id, 'contact' => $contact->id]) }}" method="POST" onsubmit="return confirm('Hapus narahubung ini?')">
                                        @csrf
                                        <button type="submit" class="text-rose-600 hover:text-rose-800 font-bold">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-[#2B211E]/40">Belum ada kontak narahubung terdaftar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Modal Tambah Kontak -->
            <div id="contact-modal" class="hidden fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
                <div class="bg-[#faf6f0] p-6 max-w-md w-full border border-[#2B211E]/20 shadow-xl space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="font-serif text-base font-bold text-[#2B211E]">Tambah Narahubung Lapangan</h3>
                        <button type="button" onclick="document.getElementById('contact-modal').classList.add('hidden')" class="text-[#2B211E]/60 hover:text-[#2B211E]">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <form action="{{ route('mitra.destinations.contacts.save', $destination->id) }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E] mb-1">Nama Lengkap *</label>
                            <input type="text" name="contact_name" required class="w-full bg-white border border-[#2B211E]/20 px-3 py-2 text-xs font-medium text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E] mb-1">Peran / Jabatan</label>
                            <input type="text" name="role" placeholder="Contoh: Pemandu Adat / Petugas Loket" class="w-full bg-white border border-[#2B211E]/20 px-3 py-2 text-xs font-medium text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E] mb-1">Nomor WhatsApp Aktif *</label>
                            <input type="text" name="phone" required placeholder="08xxxxxxxxxx" class="w-full bg-white border border-[#2B211E]/20 px-3 py-2 text-xs font-medium text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="is_primary" value="1" id="m_is_primary" class="text-[#703A3A] focus:ring-[#703A3A]">
                            <label for="m_is_primary" class="text-xs text-[#2B211E]">Jadikan narahubung utama</label>
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" onclick="document.getElementById('contact-modal').classList.add('hidden')" class="px-4 py-2 border border-[#2B211E]/20 text-xs font-bold uppercase tracking-wider text-[#2B211E]">Batal</button>
                            <button type="submit" class="rgs-btn-dark px-4 py-2 text-xs font-bold uppercase tracking-wider">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- TAB 5: PAKET EDUKASI -->
    @if($activeTab === 'packages')
        <div class="rgs-card p-6 border-t-4 border-t-[#703A3A] space-y-6">
            <div class="flex items-center justify-between pb-3 border-b border-[#2B211E]/10">
                <div>
                    <h2 class="font-serif text-lg font-bold text-[#2B211E]">Paket Edukasi & Pengalaman Belajar</h2>
                    <p class="text-xs text-[#2B211E]/60">Wajib memiliki minimal 1 paket aktif agar dapat diajukan kurasi</p>
                </div>
                <button type="button" onclick="document.getElementById('package-modal').classList.remove('hidden')" class="rgs-btn-dark px-3.5 py-2 text-xs font-bold uppercase tracking-wider flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">add</span>
                    <span>Tambah Paket</span>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @forelse($destination->allPackages as $pkg)
                    <div class="p-5 border {{ $pkg->is_active ? 'border-[#2B211E]/20 bg-white' : 'border-[#2B211E]/10 bg-[#faf6f0] opacity-70' }} space-y-3">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="font-serif text-sm font-bold text-[#2B211E]">{{ $pkg->name }}</h3>
                                <p class="text-xs text-[#703A3A] font-extrabold mt-0.5">Rp {{ number_format($pkg->price_per_pax, 0, ',', '.') }} / pax</p>
                            </div>
                            <span class="px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ $pkg->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-600' }}">
                                {{ $pkg->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>

                        <p class="text-xs text-[#2B211E]/70 line-clamp-3">{{ $pkg->description }}</p>

                        <div class="pt-3 border-t border-[#2B211E]/10 flex items-center justify-between text-[11px] text-[#2B211E]/60">
                            <span>Min: {{ $pkg->min_participants }} pax &bull; Durasi: {{ $pkg->duration_days }} hari</span>
                            <div class="flex items-center gap-2">
                                <form action="{{ route('mitra.destinations.packages.toggle', ['destination' => $destination->id, 'package' => $pkg->id]) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="text-xs font-bold text-[#2B211E] hover:underline">
                                        {{ $pkg->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                                <form action="{{ route('mitra.destinations.packages.delete', ['destination' => $destination->id, 'package' => $pkg->id]) }}" method="POST" onsubmit="return confirm('Hapus paket ini?')">
                                    @csrf
                                    <button type="submit" class="text-xs font-bold text-rose-600 hover:text-rose-800">Hapus</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-2 py-8 text-center text-rose-700 text-xs font-bold">
                        Belum ada paket edukasi. Tambahkan paket agar destinasi dapat diajukan kurasi.
                    </div>
                @endforelse
            </div>

            <!-- Modal Tambah Paket -->
            <div id="package-modal" class="hidden fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
                <div class="bg-[#faf6f0] p-6 max-w-lg w-full border border-[#2B211E]/20 shadow-xl space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="font-serif text-base font-bold text-[#2B211E]">Tambah Paket Edukasi Baru</h3>
                        <button type="button" onclick="document.getElementById('package-modal').classList.add('hidden')" class="text-[#2B211E]/60 hover:text-[#2B211E]">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <form action="{{ route('mitra.destinations.packages.save', $destination->id) }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E] mb-1">Nama Paket *</label>
                            <input type="text" name="name" required placeholder="Contoh: Belajar Budidaya Madu Lanceng" class="w-full bg-white border border-[#2B211E]/20 px-3 py-2 text-xs font-medium text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E] mb-1">Tarif per Pax (Rp) *</label>
                                <input type="number" name="price_per_pax" required min="0" placeholder="50000" class="w-full bg-white border border-[#2B211E]/20 px-3 py-2 text-xs font-medium text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E] mb-1">Min. Peserta (Pax) *</label>
                                <input type="number" name="min_participants" required min="1" value="10" class="w-full bg-white border border-[#2B211E]/20 px-3 py-2 text-xs font-medium text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E] mb-1">Durasi Kunjungan (Hari) *</label>
                            <input type="number" name="duration_days" required min="1" value="1" class="w-full bg-white border border-[#2B211E]/20 px-3 py-2 text-xs font-medium text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E] mb-1">Rincian Kegiatan & Fasilitas Termasuk *</label>
                            <textarea name="description" rows="4" required placeholder="Jelaskan alur belajar, output pembelajaran, dan sarana yang diperoleh peserta..." class="w-full bg-white border border-[#2B211E]/20 px-3 py-2 text-xs font-medium text-[#2B211E] focus:outline-none focus:border-[#703A3A]"></textarea>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="is_active" value="1" id="m_pkg_active" checked class="text-[#703A3A] focus:ring-[#703A3A]">
                            <label for="m_pkg_active" class="text-xs text-[#2B211E]">Aktifkan paket langsung</label>
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" onclick="document.getElementById('package-modal').classList.add('hidden')" class="px-4 py-2 border border-[#2B211E]/20 text-xs font-bold uppercase tracking-wider text-[#2B211E]">Batal</button>
                            <button type="submit" class="rgs-btn-dark px-4 py-2 text-xs font-bold uppercase tracking-wider">Simpan Paket</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- TAB 6: ADD-ON -->
    @if($activeTab === 'addons')
        <div class="rgs-card p-6 border-t-4 border-t-[#703A3A] space-y-6">
            <div class="flex items-center justify-between pb-3 border-b border-[#2B211E]/10">
                <div>
                    <h2 class="font-serif text-lg font-bold text-[#2B211E]">Layanan Tambahan (Add-on)</h2>
                    <p class="text-xs text-[#2B211E]/60">Pilihan fasilitas pelengkap seperti konsumsi lokal, cinderamata, atau sewa ruang aula</p>
                </div>
                <button type="button" onclick="document.getElementById('addon-modal').classList.remove('hidden')" class="rgs-btn-dark px-3.5 py-2 text-xs font-bold uppercase tracking-wider flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">add</span>
                    <span>Tambah Add-on</span>
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-[#faf6f0] text-[#2B211E] uppercase font-bold border-b border-[#2B211E]/10">
                        <tr>
                            <th class="py-3 px-4">Nama Add-on</th>
                            <th class="py-3 px-4">Tipe Harga</th>
                            <th class="py-3 px-4">Tarif (Rp)</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#2B211E]/10">
                        @forelse($destination->allAddons as $addon)
                            <tr>
                                <td class="py-3 px-4 font-bold text-[#2B211E]">{{ $addon->name }}</td>
                                <td class="py-3 px-4 text-[#2B211E]/70 uppercase font-mono">{{ $addon->pricing_type }}</td>
                                <td class="py-3 px-4 font-bold">Rp {{ number_format($addon->price, 0, ',', '.') }}</td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase {{ $addon->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-600' }}">
                                        {{ $addon->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <form action="{{ route('mitra.destinations.addons.delete', ['destination' => $destination->id, 'addon' => $addon->id]) }}" method="POST" onsubmit="return confirm('Hapus add-on ini?')">
                                        @csrf
                                        <button type="submit" class="text-rose-600 hover:text-rose-800 font-bold">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-[#2B211E]/40">Belum ada add-on terdaftar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Modal Tambah Addon -->
            <div id="addon-modal" class="hidden fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
                <div class="bg-[#faf6f0] p-6 max-w-md w-full border border-[#2B211E]/20 shadow-xl space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="font-serif text-base font-bold text-[#2B211E]">Tambah Add-on Baru</h3>
                        <button type="button" onclick="document.getElementById('addon-modal').classList.add('hidden')" class="text-[#2B211E]/60 hover:text-[#2B211E]">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <form action="{{ route('mitra.destinations.addons.save', $destination->id) }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E] mb-1">Nama Layanan Add-on *</label>
                            <input type="text" name="name" required placeholder="Contoh: Snack & Teh Herbal Khas Desa" class="w-full bg-white border border-[#2B211E]/20 px-3 py-2 text-xs font-medium text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E] mb-1">Tarif (Rp) *</label>
                                <input type="number" name="price" required min="0" placeholder="15000" class="w-full bg-white border border-[#2B211E]/20 px-3 py-2 text-xs font-medium text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E] mb-1">Skema Biaya *</label>
                                <select name="pricing_type" required class="w-full bg-white border border-[#2B211E]/20 px-3 py-2 text-xs font-medium text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                                    <option value="per_pax">Per Pax</option>
                                    <option value="per_group">Per Rombongan</option>
                                    <option value="fixed">Biaya Tetap</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E] mb-1">Keterangan Layanan</label>
                            <input type="text" name="description" placeholder="Penjelasan isi layanan..." class="w-full bg-white border border-[#2B211E]/20 px-3 py-2 text-xs font-medium text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="is_active" value="1" id="m_addon_active" checked class="text-[#703A3A] focus:ring-[#703A3A]">
                            <label for="m_addon_active" class="text-xs text-[#2B211E]">Aktifkan add-on</label>
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" onclick="document.getElementById('addon-modal').classList.add('hidden')" class="px-4 py-2 border border-[#2B211E]/20 text-xs font-bold uppercase tracking-wider text-[#2B211E]">Batal</button>
                            <button type="submit" class="rgs-btn-dark px-4 py-2 text-xs font-bold uppercase tracking-wider">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- TAB 7: KALENDER SLOT -->
    @if($activeTab === 'availability')
        <div class="rgs-card p-6 border-t-4 border-t-[#703A3A] space-y-6">
            <div class="flex items-center justify-between pb-3 border-b border-[#2B211E]/10">
                <div>
                    <h2 class="font-serif text-lg font-bold text-[#2B211E]">Ketersediaan & Kuota Tanggal Kunjungan</h2>
                    <p class="text-xs text-[#2B211E]/60">Atur kuota harian rombongan agar tidak melebihi daya dukung tapak</p>
                </div>
                <a href="{{ route('mitra.availability', ['destinasi' => $destination->slug]) }}" class="rgs-btn-dark px-3.5 py-2 text-xs font-bold uppercase tracking-wider flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">calendar_month</span>
                    <span>Buka Kalender Lengkap</span>
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-[#faf6f0] text-[#2B211E] uppercase font-bold border-b border-[#2B211E]/10">
                        <tr>
                            <th class="py-3 px-4">Tanggal</th>
                            <th class="py-3 px-4">Kapasitas</th>
                            <th class="py-3 px-4">Dipesan</th>
                            <th class="py-3 px-4">Ditahan (Held)</th>
                            <th class="py-3 px-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#2B211E]/10">
                        @forelse($destination->availabilitySlots as $slot)
                            <tr>
                                <td class="py-3 px-4 font-bold text-[#2B211E]">{{ \Carbon\Carbon::parse($slot->date)->translatedFormat('l, d M Y') }}</td>
                                <td class="py-3 px-4">{{ $slot->capacity }} pax</td>
                                <td class="py-3 px-4 font-bold text-emerald-700">{{ $slot->booked_count }}</td>
                                <td class="py-3 px-4 font-bold text-amber-700">{{ $slot->held_count }}</td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase {{ $slot->status === 'open' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                        {{ $slot->status }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-[#2B211E]/40">Belum ada slot tanggal dibuka. Buka menu Kalender Ketersediaan untuk generate tanggal kunjungan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- TAB 8: KEBIJAKAN PEMBATALAN -->
    @if($activeTab === 'policy')
        <div class="rgs-card p-6 border-t-4 border-t-[#703A3A] space-y-6">
            <h2 class="font-serif text-lg font-bold text-[#2B211E]">Ketentuan Pembatalan & Pengembalian Dana</h2>
            <p class="text-xs text-[#2B211E]/60">Pilih kebijakan pembatalan resmi yang disepakati untuk tapak ini</p>

            <form action="{{ route('mitra.destinations.update', $destination->id) }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="name" value="{{ $destination->name }}">
                <input type="hidden" name="category_id" value="{{ $destination->category_id }}">
                <input type="hidden" name="province_id" value="{{ $destination->province_id }}">
                <input type="hidden" name="city" value="{{ $destination->city }}">
                <input type="hidden" name="short_description" value="{{ $destination->short_description }}">
                <input type="hidden" name="description" value="{{ $destination->description }}">
                <input type="hidden" name="default_daily_capacity" value="{{ $destination->default_daily_capacity }}">

                <div class="space-y-3">
                    @foreach($policies as $pol)
                        <label class="block p-4 bg-[#faf6f0] border {{ $destination->cancellation_policy_id == $pol->id ? 'border-[#703A3A] bg-[#703A3A]/5' : 'border-[#2B211E]/15' }} cursor-pointer">
                            <div class="flex items-center gap-3">
                                <input type="radio" name="cancellation_policy_id" value="{{ $pol->id }}" {{ $destination->cancellation_policy_id == $pol->id ? 'checked' : '' }} class="text-[#703A3A] focus:ring-[#703A3A]">
                                <div>
                                    <h3 class="text-xs font-bold text-[#2B211E]">{{ $pol->name }}</h3>
                                    <p class="text-[11px] text-[#2B211E]/60 mt-0.5">{{ $pol->description }}</p>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>

                <div class="flex justify-end pt-4 border-t border-[#2B211E]/10">
                    <button type="submit" class="rgs-btn-dark px-5 py-2.5 text-xs font-bold uppercase tracking-wider">
                        Perbarui Kebijakan
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- TAB 9: SEO & META -->
    @if($activeTab === 'seo')
        <div class="rgs-card p-6 border-t-4 border-t-[#703A3A] space-y-6">
            <h2 class="font-serif text-lg font-bold text-[#2B211E]">SEO & Tampilan Mesin Pencari</h2>
            <p class="text-xs text-[#2B211E]/60">Bantu calon pemesan menemukan tapak edukasi Anda di mesin pencari</p>

            <form action="{{ route('mitra.destinations.update', $destination->id) }}" method="POST" class="space-y-4">
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
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E] mb-1">Judul Halaman (Meta Title)</label>
                    <input type="text" name="meta_title" value="{{ old('meta_title', $destination->meta_title) }}" placeholder="{{ $destination->name }} - Destinara" class="w-full bg-[#faf6f0] border border-[#2B211E]/20 px-3.5 py-2 text-xs font-medium text-[#2B211E] focus:outline-none focus:border-[#703A3A]">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#2B211E] mb-1">Deskripsi Cuplikan (Meta Description)</label>
                    <textarea name="meta_description" rows="3" class="w-full bg-[#faf6f0] border border-[#2B211E]/20 px-3.5 py-2 text-xs font-medium text-[#2B211E] focus:outline-none focus:border-[#703A3A]">{{ old('meta_description', $destination->meta_description) }}</textarea>
                </div>

                <div class="flex justify-end pt-4 border-t border-[#2B211E]/10">
                    <button type="submit" class="rgs-btn-dark px-5 py-2.5 text-xs font-bold uppercase tracking-wider">
                        Simpan Meta SEO
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
@endsection
