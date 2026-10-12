@extends('layouts.public')

@section('title', 'Direktori Destinasi Tapak Terkurasi — Destinara')
@section('meta_description', 'Inventaris dan direktori desa adat serta tapak pembelajaran terkurasi di seluruh Nusantara untuk ekskursi sekolah, penelitian botani, hingga kajian antropologi adat.')

@section('content')
<main class="w-full bg-[#FAF7F5] pt-20 sm:pt-24 pb-20">
  <div class="flex flex-col w-full">
    
    <!-- Top Header Banner & Filter Console -->
    <section class="max-w-[1360px] mx-auto px-4 sm:px-6 lg:px-8 w-full pt-8 sm:pt-12 pb-4">
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-6 border-b border-gray-200/60">
        <div>
          <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-[#51634b]/10 text-[#51634b] text-xs font-bold uppercase tracking-wider rounded-full mb-3">
            <span class="w-1.5 h-1.5 rounded-full bg-[#51634b]"></span>
            <span>Etalase Resmi Terakreditasi {{ date('Y') }}</span>
          </div>
          <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-[#1E1B1B] tracking-tight">
            Etalase Tapak & Laboratorium Alam
          </h1>
          <p class="text-xs sm:text-sm text-gray-500 mt-2 max-w-2xl leading-relaxed">
            Temukan dan ajukan booking desa adat serta tapak konservasi terverifikasi untuk ekskursi sekolah (Proyek P5), riset etnobotani, dan live-in kearifan lokal.
          </p>
        </div>

        <div class="flex items-center gap-3 text-xs font-semibold text-gray-500 shrink-0">
          <span class="flex items-center gap-1 text-[#51634b]"><span class="material-symbols-outlined text-[16px]">verified</span> FPIC Sah</span>
          <span>•</span>
          <span class="flex items-center gap-1 text-[#703A3A]"><span class="material-symbols-outlined text-[16px]">receipt_long</span> Standar Dana BOS</span>
        </div>
      </div>

      <!-- Marketplace Filter & Search Console -->
      <div class="mt-6 bg-white p-4 sm:p-5 rounded-3xl border border-gray-100 shadow-sm flex flex-col gap-4">
        
        <!-- Search Input Bar -->
        <form action="{{ route('destinasi.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
          @if(request('category'))
          <input type="hidden" name="category" value="{{ request('category') }}">
          @endif
          
          <!-- Keyword Input -->
          <div class="relative sm:col-span-6">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama tapak, pulau, atau topik riset..." 
                   class="w-full pl-10 pr-4 py-2.5 text-xs sm:text-sm bg-gray-50 border border-gray-200 rounded-full text-[#1E1B1B] placeholder-gray-400 focus:outline-none focus:border-[#703A3A] focus:bg-white transition-all">
            <span class="material-symbols-outlined absolute left-3.5 top-3 text-base text-[#703A3A]">search</span>
          </div>

          <!-- Province Selector -->
          <div class="sm:col-span-3">
            <select name="province" class="w-full p-2.5 border border-gray-200 rounded-full text-xs sm:text-sm bg-gray-50 text-[#1E1B1B] focus:outline-none focus:border-[#703A3A] cursor-pointer">
              <option value="">Semua Provinsi</option>
              @foreach($provinces as $prov)
              <option value="{{ $prov }}" {{ request('province') === $prov ? 'selected' : '' }}>{{ $prov }}</option>
              @endforeach
            </select>
          </div>

          <!-- Sort & Action Button -->
          <div class="sm:col-span-3 flex items-center gap-2">
            <select name="sort" class="w-full p-2.5 border border-gray-200 rounded-full text-xs sm:text-sm bg-gray-50 text-[#1E1B1B] focus:outline-none focus:border-[#703A3A] cursor-pointer">
              <option value="popular" {{ request('sort') == 'popular' || !request('sort') ? 'selected' : '' }}>Terpopuler</option>
              <option value="lowest_price" {{ request('sort') == 'lowest_price' ? 'selected' : '' }}>Tarif Terendah</option>
              <option value="highest_capacity" {{ request('sort') == 'highest_capacity' ? 'selected' : '' }}>Kapasitas Terbesar</option>
            </select>
            <button type="submit" class="bg-[#703A3A] hover:bg-[#561D1E] text-white py-2.5 px-5 text-xs font-bold rounded-full shrink-0 flex items-center gap-1.5 shadow-xs transition-colors">
              <span class="material-symbols-outlined text-[16px]">filter_alt</span>
              <span>Cari</span>
            </button>
            @if(request('q') || request('category') || request('province') || request('sort'))
            <a href="{{ route('destinasi.index') }}" class="text-xs text-[#703A3A] hover:underline font-semibold px-1 shrink-0" title="Reset Filter">
              Reset
            </a>
            @endif
          </div>
        </form>

        <!-- Category Pills (Rounded-full, Clean) -->
        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar py-1 w-full flex-nowrap pt-3 border-t border-gray-100">
          <a href="{{ route('destinasi.index', array_filter(['q' => request('q'), 'province' => request('province'), 'sort' => request('sort')])) }}" 
             class="px-4 py-2 rounded-full text-xs font-bold uppercase tracking-wider transition-all whitespace-nowrap {{ !request('category') ? 'bg-[#703A3A] text-white shadow-xs' : 'text-gray-700 bg-gray-50 hover:bg-gray-100' }}">
            Semua Kategori
          </a>
          @foreach($categories as $code => $label)
          <a href="{{ route('destinasi.index', array_filter(['category' => $code, 'q' => request('q'), 'province' => request('province'), 'sort' => request('sort')])) }}" 
             class="px-4 py-2 rounded-full text-xs font-semibold uppercase tracking-wider transition-all whitespace-nowrap {{ request('category') === $code ? 'bg-[#703A3A] text-white shadow-xs' : 'text-gray-700 bg-gray-50 hover:bg-gray-100' }}">
            {{ $label }}
          </a>
          @endforeach
        </div>

      </div>
    </section>

    <!-- Modern Destination Cards Grid (Clean, Rounded-3xl Cards) -->
    <section class="max-w-[1360px] mx-auto px-4 sm:px-6 lg:px-8 mt-6 w-full">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
        @forelse($destinations as $dest)
          <div class="bg-white rounded-3xl p-3.5 shadow-sm hover:shadow-xl border border-gray-100 transition-all duration-300 hover:-translate-y-1.5 flex flex-col justify-between group">
            
            <div>
              <!-- Image Container -->
              <div class="relative w-full aspect-[16/11] rounded-2xl overflow-hidden bg-gray-100 mb-3">
                
                <!-- Floating Rating Pill (Frosted Glass) -->
                <div class="absolute top-3 left-3 z-10 bg-black/50 backdrop-blur-md text-white text-xs px-2.5 py-1 rounded-full font-bold flex items-center gap-1 shadow-xs">
                  <span class="text-amber-400 text-[13px]">★</span>
                  <span>{{ $dest['rating'] }}</span>
                  <span class="text-white/70 text-[10px]">({{ $dest['reviews_count'] }})</span>
                </div>

                <!-- Location & Category Pill -->
                <div class="absolute top-3 right-3 z-10 bg-white/90 backdrop-blur-md text-[#2B211E] text-[11px] font-semibold px-2.5 py-1 rounded-full shadow-xs">
                  {{ $dest['province'] }}
                </div>

                <img src="{{ $dest['image'] }}" 
                     alt="{{ $dest['name'] }}" 
                     class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-106"/>
                
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent pointer-events-none"></div>

                <!-- Destination Title Overlay -->
                <div class="absolute bottom-3 inset-x-3 text-white">
                  <span class="text-[10px] uppercase font-bold tracking-wider text-[#FFD3D2] block mb-0.5">
                    {{ $dest['category'] }}
                  </span>
                  <h2 class="text-lg font-bold drop-shadow-sm leading-snug">
                    <a href="{{ route('destinasi.show', $dest['slug']) }}" class="hover:underline">
                      {{ $dest['name'] }}
                    </a>
                  </h2>
                </div>
              </div>

              <!-- Body Info: Clean & Concise -->
              <div class="px-2 pb-2">
                <p class="text-xs text-gray-500 line-clamp-2 leading-relaxed mb-3">
                  {{ $dest['highlights'] }}
                </p>

                <!-- Suitable Focus Pill -->
                <div class="flex flex-wrap gap-1.5 mb-2">
                  @foreach(array_slice($dest['suitable_for'], 0, 2) as $suit)
                  <span class="text-[10px] px-2.5 py-0.5 bg-gray-100 text-gray-600 rounded-full font-medium">
                    {{ $suit }}
                  </span>
                  @endforeach
                </div>
              </div>
            </div>

            <!-- Footer: Price Tag & Booking Action -->
            <div class="px-2 pt-3 border-t border-gray-100 flex items-center justify-between mt-2">
              <div>
                <span class="text-[10px] text-gray-400 block font-medium">Mulai dari</span>
                <span class="text-sm font-extrabold text-[#703A3A]">{{ $dest['price_formatted'] }}<span class="text-[10px] font-normal text-gray-500">/pax</span></span>
              </div>

              <a href="{{ route('destinasi.show', $dest['slug']) }}" 
                 class="bg-[#703A3A] hover:bg-[#561D1E] text-white px-4 py-2 rounded-full text-xs font-bold transition-all flex items-center gap-1.5 shadow-xs">
                <span>Pesan Tapak</span>
                <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
              </a>
            </div>

          </div>
        @empty
          <div class="col-span-1 sm:col-span-2 lg:col-span-3 py-16 text-center text-gray-500 bg-white rounded-3xl p-8 border border-gray-100">
            <span class="material-symbols-outlined text-4xl text-gray-400 mb-2">search_off</span>
            <p class="text-sm font-semibold">Tidak ada tapak yang sesuai dengan filter pencarian.</p>
            <a href="{{ route('destinasi.index') }}" class="inline-block mt-3 text-xs font-bold text-[#703A3A] hover:underline">Reset Pencarian</a>
          </div>
        @endforelse
      </div>
    </section>

  </div>
</main>
@endsection
