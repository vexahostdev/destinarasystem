@extends('layouts.public')

@section('title', (string) ($destination['name'] . ' — ' . $destination['city'] . ', ' . $destination['province'] . ' | Destinara'))
@section('meta_description', (string) ($destination['highlights'] ?? 'Destinasi Edukasi Lapangan Terverifikasi'))

@section('content')
<main class="w-full bg-[#FFFFFF] pt-20 sm:pt-24 pb-20" x-data="{
    peserta: 30,
    tipe: 'study_tour',
    basePrice: {{ $destination['price'] }},
    guidePrice: {{ (int) ($model->addons->where('is_active', true)->first()?->price ?? 0) }},
    get subtotal() { return this.peserta * this.basePrice; },
    get total() { return this.subtotal + this.guidePrice; },
    formatRupiah(val) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val);
    },
    activePhoto: '{{ $destination['gallery'][0] ?? $destination['image'] }}'
}">
  <div class="max-w-[1360px] mx-auto px-4 sm:px-6 lg:px-8">

    <!-- Breadcrumb & Top Bar Navigation -->
    <div class="flex items-center justify-between py-4">
      <a href="{{ route('destinasi.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-[#1F2B45] hover:text-[#16203A] bg-white px-4 py-2 rounded-2xl shadow-xs border border-gray-100 transition-all hover:shadow-md">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        <span>Katalog Destinasi</span>
      </a>

      <div class="flex items-center gap-2">
        @if(!empty($destination['rating']))
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-amber-50 text-amber-700 text-xs font-bold border border-amber-200/50">
          <span class="material-symbols-outlined text-[15px] fill-current text-amber-500">star</span>
          <span>{{ $destination['rating'] }} ({{ $destination['reviews_count'] }} ulasan)</span>
        </span>
        @endif
      </div>
    </div>

    <!-- Title & Meta Header -->
    <div class="mt-2 mb-6">
      <div class="flex items-center gap-2 mb-2 flex-wrap">
        <span class="px-3 py-1 rounded-full bg-[#1F2B45]/10 text-[#1F2B45] text-xs font-bold tracking-wide uppercase">
          {{ $destination['category'] }}
        </span>
        <span class="text-xs text-gray-500 font-medium flex items-center gap-1">
          <span class="material-symbols-outlined text-[16px] text-gray-400">location_on</span>
          {{ $destination['city'] }}, {{ $destination['province'] }}
        </span>
      </div>
      <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-[#111827] tracking-tight leading-tight">
        {{ $destination['name'] }}
      </h1>
      <p class="text-sm sm:text-base text-gray-600 mt-2 max-w-3xl leading-relaxed">
        {{ $destination['highlights'] }}
      </p>
    </div>

    <!-- Main Content & Details (2 Kolom: Konten Kiri 8, Sticky Booking Simulator Kanan 4) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 items-start">
      
      <!-- Content Column (Kiri 8) -->
      <div class="lg:col-span-8 flex flex-col gap-8">
        
        <!-- Interactive Photo Gallery -->
        <div class="space-y-3">
          <div class="relative w-full aspect-[16/9] overflow-hidden rounded-3xl bg-[#1F2937] shadow-md border border-gray-100">
            <img :src="activePhoto" alt="{{ $destination['name'] }}" class="w-full h-full object-cover transition-all duration-300">
            <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent pointer-events-none"></div>
            @if(!empty($destination['gallery']) && count($destination['gallery']) > 0)
            <div class="absolute bottom-4 left-4 inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-black/60 backdrop-blur-md text-white text-xs font-medium">
              <span class="material-symbols-outlined text-[16px] text-[#D4E9CA]">photo_camera</span>
              <span>{{ count($destination['gallery']) }} Dokumentasi Foto</span>
            </div>
            @endif
          </div>

          <!-- Sub Photo Thumbnails -->
          <div class="grid grid-cols-5 gap-3">
            @foreach($destination['gallery'] as $idx => $photo)
            <div @click="activePhoto = '{{ $photo }}'" 
                 class="aspect-[4/3] rounded-2xl overflow-hidden cursor-pointer transition-all border-2"
                 :class="activePhoto === '{{ $photo }}' ? 'border-[#1F2B45] shadow-md scale-[1.02]' : 'border-transparent opacity-75 hover:opacity-100'">
              <img src="{{ $photo }}" alt="Foto {{ $idx + 1 }}" class="w-full h-full object-cover">
            </div>
            @endforeach
          </div>
        </div>

        <!-- Konteks Narasi Tapak -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-xs border border-gray-100/80 space-y-4">
          <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-full bg-[#1F2B45]/10 text-[#1F2B45] flex items-center justify-center shrink-0">
              <span class="material-symbols-outlined text-[20px]">menu_book</span>
            </div>
            <h3 class="text-xl sm:text-2xl font-bold text-[#111827]">Konteks & Narasi Hidup Tapak</h3>
          </div>
          <div class="text-gray-600 leading-relaxed text-sm sm:text-base whitespace-pre-line">
            {{ $destination['description'] ?? $destination['highlights'] }}
          </div>
        </div>

        <!-- Focus Riset Box -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-xs border border-[#51634b]/20 flex flex-col gap-3">
          <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-[#51634b]">
            <span class="material-symbols-outlined text-[18px]">nature</span>
            <span>Fokus Pembelajaran & Riset Lapangan</span>
          </div>
          <p class="text-sm sm:text-base text-[#111827] font-semibold leading-relaxed">
            {{ implode(' • ', $destination['suitable_for']) }}
          </p>
          <p class="text-xs sm:text-sm text-gray-500 leading-relaxed">
            Kajian empiris mencakup dokumentasi ekologi lokal, ketahanan pangan adat, dan arsitektur vernakular ramah gempa yang teruji lintas generasi.
          </p>
        </div>

        <!-- 2 Modul Pembelajaran Lapangan -->
        <div class="space-y-4">
          <h3 class="text-xl sm:text-2xl font-bold text-[#111827]">Silabus & Muatan Kurikulum</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            
            <div class="p-6 bg-white rounded-3xl border border-gray-100 shadow-xs space-y-3 hover:shadow-md transition-shadow">
              <span class="inline-block px-3 py-1 rounded-full bg-[#1F2B45]/10 text-[#1F2B45] text-xs font-bold">
                Modul Sekolah (P5)
              </span>
              <h4 class="font-bold text-lg text-[#111827]">Ekskursi & Refleksi Budaya</h4>
              <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
                Siswa diajak mengamati siklus hidup desa, sistem gotong royong, dan mempraktikkan kearifan lokal bimbingan tetua adat.
              </p>
              <div class="pt-2 text-xs text-[#51634b] font-bold flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px]">task_alt</span>
                <span>Output: Lembar refleksi & asesmen P5</span>
              </div>
            </div>

            <div class="p-6 bg-white rounded-3xl border border-gray-100 shadow-xs space-y-3 hover:shadow-md transition-shadow">
              <span class="inline-block px-3 py-1 rounded-full bg-[#51634b]/10 text-[#51634b] text-xs font-bold">
                Modul Akademisi & Riset
              </span>
              <h4 class="font-bold text-lg text-[#111827]">Penelitian Etnografi & Sains</h4>
              <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
                Akses langsung informan kunci, pencatatan data biofisik lapangan, serta kliring etik protokol FPIC yang sah secara hukum.
              </p>
              <div class="pt-2 text-xs text-amber-700 font-bold flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px]">verified</span>
                <span>Output: Data primer & berita acara riset</span>
              </div>
            </div>

          </div>
        </div>

        <!-- Fasilitas & Daya Dukung Lapangan -->
        <div class="space-y-4">
          <h3 class="text-xl sm:text-2xl font-bold text-[#111827]">Fasilitas & Daya Dukung Kunjungan</h3>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            @foreach($destination['facilities'] as $fac)
            <div class="p-4 bg-white rounded-2xl border border-gray-100 shadow-xs flex items-center gap-3 text-xs sm:text-sm text-[#111827] font-semibold">
              <div class="w-7 h-7 rounded-full bg-[#51634b]/10 text-[#51634b] flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[16px]">check</span>
              </div>
              <span>{{ $fac }}</span>
            </div>
            @endforeach
          </div>
        </div>

        <!-- Pratinjau Kalender Akademik Slot Kunjungan -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-xs space-y-5">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
              <h3 class="text-xl font-bold text-[#111827]">Ketersediaan Slot Kunjungan</h3>
              <p class="text-xs text-gray-500">Maksimal 2 rombongan studi per hari guna menjaga daya dukung tapak</p>
            </div>
            <div class="flex items-center gap-3 text-xs">
              <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span> Tersedia</span>
              <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-rose-500 inline-block"></span> Penuh</span>
              <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-500 inline-block"></span> Upacara</span>
            </div>
          </div>

          <!-- Calendar Grid Mockup -->
          <div class="grid grid-cols-7 gap-2 text-center text-xs">
            <span class="font-bold text-gray-400 py-1">Sen</span>
            <span class="font-bold text-gray-400 py-1">Sel</span>
            <span class="font-bold text-gray-400 py-1">Rab</span>
            <span class="font-bold text-gray-400 py-1">Kam</span>
            <span class="font-bold text-gray-400 py-1">Jum</span>
            <span class="font-bold text-gray-400 py-1">Sab</span>
            <span class="font-bold text-gray-400 py-1">Min</span>

            @for($i = 1; $i <= 14; $i++)
              @php
                $isFull = in_array($i, [5, 6, 12]);
                $isCeremony = in_array($i, [9]);
              @endphp
              <div class="py-3 rounded-xl border text-xs font-semibold transition-all
                {{ $isFull ? 'bg-rose-50/70 border-rose-200 text-rose-700' : ($isCeremony ? 'bg-amber-50/70 border-amber-200 text-amber-700' : 'bg-emerald-50/70 border-emerald-200 text-emerald-700 hover:scale-105 cursor-pointer') }}">
                {{ $i }}
                <span class="block text-[9px] font-normal mt-0.5">
                  {{ $isFull ? 'Penuh' : ($isCeremony ? 'Adat' : 'Tersedia') }}
                </span>
              </div>
            @endfor
          </div>
        </div>

        <!-- Kontak Pengelola & PIC Lapangan (Aturan Reveal SPEC §8.5) -->
        <div class="p-6 bg-white rounded-3xl border border-gray-100 shadow-xs space-y-4">
          <div class="flex items-center gap-2 font-bold text-base text-[#111827]">
            <span class="material-symbols-outlined text-[#1F2B45] text-[20px]">contact_phone</span>
            <h4>Kontak Pengelola & Narahubung Tapak</h4>
          </div>

          @if(count($destination['contacts']) > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              @foreach($destination['contacts'] as $contact)
                <div class="p-3.5 bg-gray-50 rounded-2xl border border-gray-100 flex items-center justify-between text-xs">
                  <div>
                    <span class="font-bold text-slate-800 block">{{ $contact['name'] }}</span>
                    <span class="text-slate-500 text-[11px]">{{ $contact['role'] }}</span>
                  </div>
                  <div>
                    @if($contact['is_revealed'])
                      <a href="https://wa.me/{{ preg_replace('/[^\d]/', '', $contact['phone']) }}" target="_blank" class="inline-flex items-center gap-1 font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-2xl hover:bg-emerald-100 transition">
                        <span class="material-symbols-outlined text-[14px]">chat</span>
                        {{ $contact['phone'] }}
                      </a>
                    @else
                      <span class="inline-flex items-center gap-1 text-slate-500 bg-white px-2 py-0.5 rounded border border-slate-200">
                        <span class="material-symbols-outlined text-[14px]">lock</span>
                        Nomor terkunci
                      </span>
                    @endif
                  </div>
                </div>
              @endforeach
            </div>
            @if(!($destination['contacts'][0]['is_revealed'] ?? false))
              <p class="text-[11px] text-slate-500 bg-amber-50 p-3 rounded-xl border border-amber-200/50">
                <span class="font-bold text-amber-800">Privasi Kontak:</span> Nomor kontak pengelola akan terbuka otomatis bagi akun institusi pemesan setelah reservasi berstatus {{ $destination['contact_reveal_label'] }}.
              </p>
            @endif
          @else
            <p class="text-xs text-slate-500">Narahubung terdaftar langsung di bawah koordinasi pengelola tapak resmi.</p>
          @endif
        </div>

        <!-- Kebijakan Pembatalan & Pengembalian Dana (SPEC §8.6) -->
        @if(!empty($destination['cancellationPolicy']))
          <div class="p-6 bg-white rounded-3xl border border-gray-100 shadow-xs space-y-3 text-xs sm:text-sm text-gray-600">
            <div class="flex items-center gap-2 font-bold text-base text-[#111827]">
              <span class="material-symbols-outlined text-[#1F2B45] text-[20px]">verified_user</span>
              <h4>Kebijakan Pembatalan: {{ $destination['cancellationPolicy']->name }}</h4>
            </div>
            <p class="text-xs text-slate-500">{{ $destination['cancellationPolicy']->description }}</p>
            @if($destination['cancellationPolicy']->tiers && $destination['cancellationPolicy']->tiers->isNotEmpty())
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-1">
                @foreach($destination['cancellationPolicy']->tiers as $tier)
                  <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200 text-xs text-center">
                    <span class="font-bold text-slate-700 block">H-{{ $tier->days_before }} atau lebih</span>
                    <span class="text-emerald-700 font-extrabold text-sm">{{ $tier->refund_percent }}% Refund</span>
                  </div>
                @endforeach
              </div>
            @endif
          </div>
        @endif

      </div>

      <!-- Sticky Booking Simulator Card (Kanan 4) -->
      <div class="lg:col-span-4 sticky top-24 space-y-4">
        <div class="bg-white rounded-3xl p-6 sm:p-7 shadow-[0_20px_50px_rgba(31, 43, 69,0.12)] border border-gray-100 flex flex-col gap-5">
          
          <div>
            <span class="text-xs uppercase tracking-wider font-bold text-[#1F2B45] block mb-1">
              Investasi Pendidikan Tapak
            </span>
            <div class="flex items-baseline gap-1.5">
              <span class="text-3xl font-extrabold text-[#111827]">{{ $destination['price_formatted'] }}</span>
              <span class="text-xs text-gray-500">/ peserta didik</span>
            </div>
          </div>

          <!-- Metadata List -->
          <div class="flex flex-col gap-2.5 border-t border-gray-100 pt-4 text-xs">
            <div class="flex justify-between items-center">
              <span class="text-gray-500">Wilayah</span>
              <span class="font-bold text-[#111827]">{{ $destination['city'] }}, {{ $destination['province'] }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-500">Kategori</span>
              <span class="font-bold text-[#111827]">{{ $destination['category'] }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-500">Daya Tampung</span>
              <span class="font-bold text-[#51634b]">{{ $destination['capacity'] }} Orang / Rombongan</span>
            </div>
            @if(!empty($destination['has_permit']))
            <div class="flex justify-between items-center">
              <span class="text-gray-500">Legalitas</span>
              <span class="font-bold text-emerald-600">Dokumen Izin Lengkap</span>
            </div>
            @elseif(!empty($destination['is_partner_verified']))
            <div class="flex justify-between items-center">
              <span class="text-gray-500">Legalitas</span>
              <span class="font-bold text-emerald-600">Mitra Terverifikasi</span>
            </div>
            @endif
          </div>

          <!-- Booking Simulator Form Inputs -->
          <div class="space-y-4 pt-4 border-t border-gray-100">
            <div>
              <label class="block text-xs font-bold text-[#111827] mb-2">Tipe Kunjungan</label>
              <div class="grid grid-cols-2 gap-2 p-1 bg-gray-100 rounded-full">
                <button type="button" @click="tipe = 'study_tour'" 
                        :class="tipe === 'study_tour' ? 'bg-[#1F2B45] text-white shadow-xs' : 'text-gray-600 hover:text-black'" 
                        class="py-2 px-3 text-xs font-bold rounded-full transition-all text-center">
                  Study Tour
                </button>
                <button type="button" @click="tipe = 'riset'" 
                        :class="tipe === 'riset' ? 'bg-[#1F2B45] text-white shadow-xs' : 'text-gray-600 hover:text-black'" 
                        class="py-2 px-3 text-xs font-bold rounded-full transition-all text-center">
                  Riset Lapang
                </button>
              </div>
            </div>

            <div>
              <div class="flex items-center justify-between text-xs font-semibold text-[#111827] mb-1.5">
                <span>Estimasi Peserta:</span>
                <span class="font-bold text-[#1F2B45] text-sm" x-text="peserta + ' Orang'"></span>
              </div>
              <input type="range" min="5" max="150" x-model="peserta" class="w-full accent-[#1F2B45] cursor-pointer">
              <div class="flex justify-between text-[10px] text-gray-400 mt-1">
                <span>Min. 5 org</span>
                <span>Maks. 150 org</span>
              </div>
            </div>

            <!-- Realtime Cost Calculation -->
            <div class="p-4 bg-[#FFFFFF] rounded-2xl space-y-2 text-xs border border-gray-200/50">
              <div class="flex justify-between text-gray-600">
                <span>Retribusi (<span x-text="peserta"></span> org):</span>
                <span class="font-semibold text-[#111827]" x-text="formatRupiah(subtotal)"></span>
              </div>
              <div class="flex justify-between text-gray-600">
                <span>{{ $model->addons->where('is_active', true)->first()?->name ?? 'Layanan Pendamping' }}:</span>
                <span class="font-semibold text-[#111827]" x-text="formatRupiah(guidePrice)"></span>
              </div>
              <div class="flex justify-between text-sm font-extrabold text-[#111827] pt-2 border-t border-gray-200">
                <span>Estimasi Total:</span>
                <span class="text-[#1F2B45]" x-text="formatRupiah(total)"></span>
              </div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="flex flex-col gap-2.5 pt-1">
            @auth
              @if(Auth::user()->isBuyer())
                <a href="{{ route('buyer.booking.create', ['tapak' => $destination['slug']]) }}" 
                   class="bg-[#1F2B45] hover:bg-[#16203A] text-white py-3.5 px-6 rounded-full font-bold text-sm text-center flex items-center justify-center gap-2 shadow-md hover:shadow-lg transition-all">
                  <span class="material-symbols-outlined text-[18px]">add_shopping_cart</span>
                  <span>Ajukan Booking</span>
                </a>
              @elseif(Auth::user()->isPartner())
                <a href="{{ route('partner.dashboard') }}" 
                   class="bg-[#51634b] hover:bg-[#3b4937] text-white py-3.5 px-6 rounded-2xl font-bold text-sm text-center flex items-center justify-center gap-2 shadow-md hover:shadow-lg transition-all">
                  <span class="material-symbols-outlined text-[18px]">dashboard</span>
                  <span>Dashboard Mitra</span>
                </a>
              @else
                <a href="{{ route('admin.dashboard') }}" 
                   class="bg-[#2B3A4A] hover:bg-[#1f2a36] text-white py-3.5 px-6 rounded-2xl font-bold text-sm text-center flex items-center justify-center gap-2 shadow-md hover:shadow-lg transition-all">
                  <span class="material-symbols-outlined text-[18px]">admin_panel_settings</span>
                  <span>Dashboard Admin</span>
                </a>
              @endif
            @else
              <a href="{{ route('login') }}" 
                 class="bg-[#1F2B45] hover:bg-[#16203A] text-white py-3.5 px-6 rounded-2xl font-bold text-sm text-center flex items-center justify-center gap-2 shadow-md hover:shadow-lg transition-all">
                <span class="material-symbols-outlined text-[18px]">login</span>
                <span>Masuk untuk Reservasi</span>
              </a>
            @endauth

            <a href="https://wa.me/{{ preg_replace('/[^\d]/', '', (string) setting('company_phone', '6281200000000')) }}?text=Halo%20Destinara%2C%20mohon%20informasi%20dossier%20kurikulum%20dan%20silabus%20resmi%20untuk%20{{ urlencode($destination['name'] ?? $destination->name) }}" 
               target="_blank"
               class="border border-gray-300 hover:border-gray-400 text-gray-700 py-3 px-6 rounded-full font-semibold text-xs text-center flex items-center justify-center gap-1.5 transition-all bg-white hover:bg-gray-50">
              <span class="material-symbols-outlined text-[16px]">description</span>
              <span>Permohonan Silabus Resmi</span>
            </a>
          </div>

          <div class="p-3 bg-emerald-50 rounded-2xl border border-emerald-200/50 text-[11px] text-emerald-800 space-y-0.5">
            <span class="font-bold block">✓ Akuntabilitas BOS & BUMDes</span>
            <p class="leading-relaxed">Kwitansi resmi instansi diterbitkan otomatis setelah verifikasi pembayaran.</p>
          </div>

        </div>
      </div>

    </div>

  </div>
</main>
@endsection
