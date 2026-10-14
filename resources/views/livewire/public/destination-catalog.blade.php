<div>
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
            Temukan dan ajukan booking desa adat serta tapak konservasi terverifikasi untuk ekskursi sekolah, riset etnobotani, dan live-in kearifan lokal.
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
        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
          
          <!-- Keyword Input -->
          <div class="relative sm:col-span-6">
            <input type="text" 
                   wire:model.live.debounce.350ms="search" 
                   placeholder="Cari nama tapak, kota, atau topik pembelajaran..." 
                   class="w-full pl-10 pr-4 py-2.5 text-xs sm:text-sm bg-gray-50 border border-gray-200 rounded-full text-[#1E1B1B] placeholder-gray-400 focus:outline-none focus:border-[#703A3A] focus:bg-white transition-all">
            <span class="material-symbols-outlined absolute left-3.5 top-3 text-base text-[#703A3A]">search</span>
          </div>

          <!-- Province Selector -->
          <div class="sm:col-span-3">
            <select wire:model.live="province" class="w-full p-2.5 border border-gray-200 rounded-full text-xs sm:text-sm bg-gray-50 text-[#1E1B1B] focus:outline-none focus:border-[#703A3A] cursor-pointer">
              <option value="">Semua Provinsi</option>
              @foreach($provinces as $prov)
                <option value="{{ $prov }}">{{ $prov }}</option>
              @endforeach
            </select>
          </div>

          <!-- Sort & Action Button -->
          <div class="sm:col-span-3 flex items-center gap-2">
            <select wire:model.live="sort" class="w-full p-2.5 border border-gray-200 rounded-full text-xs sm:text-sm bg-gray-50 text-[#1E1B1B] focus:outline-none focus:border-[#703A3A] cursor-pointer">
              <option value="popular">Terpopuler</option>
              <option value="lowest_price">Tarif Terendah</option>
              <option value="highest_capacity">Kapasitas Terbesar</option>
            </select>

            @if($search || $category || $province || $sort !== 'popular')
              <button wire:click="resetFilters" class="text-xs text-[#703A3A] hover:underline font-semibold px-2 shrink-0" title="Reset Filter">
                Reset
              </button>
            @endif
          </div>
        </div>

        <!-- Category Pills -->
        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar py-1 w-full flex-nowrap pt-3 border-t border-gray-100">
          <button wire:click="$set('category', '')" 
                  class="px-4 py-2 rounded-full text-xs font-bold uppercase tracking-wider transition-all whitespace-nowrap {{ empty($category) ? 'bg-[#703A3A] text-white shadow-xs' : 'text-gray-700 bg-gray-50 hover:bg-gray-100' }}">
            Semua Kategori
          </button>
          @foreach($categories as $slug => $label)
            <button wire:click="$set('category', '{{ $slug }}')" 
                    class="px-4 py-2 rounded-full text-xs font-semibold uppercase tracking-wider transition-all whitespace-nowrap {{ $category === $slug ? 'bg-[#703A3A] text-white shadow-xs' : 'text-gray-700 bg-gray-50 hover:bg-gray-100' }}">
              {{ $label }}
            </button>
          @endforeach
        </div>

      </div>
    </section>

    <!-- Modern Destination Cards Grid -->
    <section class="max-w-[1360px] mx-auto px-4 sm:px-6 lg:px-8 mt-6 w-full">
      <div wire:loading.class="opacity-50" class="transition-opacity duration-200">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
          @forelse($destinations as $dest)
            @php
              $firstMedia = $dest->media->first();
              $imageSrc = $firstMedia ? asset('storage/' . $firstMedia->file_path) : 'https://images.unsplash.com/photo-1596402184320-417e7178b2cd?auto=format&fit=crop&w=800&q=80';
              $minPrice = $dest->packages->min('price_per_pax') ?? 0;
              $reviewsCount = $dest->reviews->count();
              $avgRating = $dest->rating ?? 0;
            @endphp
            <div class="bg-white rounded-3xl p-3.5 shadow-sm hover:shadow-xl border border-gray-100 transition-all duration-300 hover:-translate-y-1.5 flex flex-col justify-between group">
              
              <div>
                <!-- Image Container -->
                <div class="relative w-full aspect-[16/11] rounded-2xl overflow-hidden bg-gray-100 mb-3">
                  
                  <!-- Rating Pill: ONLY IF REAL REVIEWS EXIST -->
                  @if($reviewsCount > 0)
                    <div class="absolute top-3 left-3 z-10 bg-black/50 backdrop-blur-md text-white text-xs px-2.5 py-1 rounded-full font-bold flex items-center gap-1 shadow-xs">
                      <span class="text-amber-400 text-[13px]">★</span>
                      <span>{{ number_format($avgRating, 1) }}</span>
                      <span class="text-white/70 text-[10px]">({{ $reviewsCount }})</span>
                    </div>
                  @else
                    <div class="absolute top-3 left-3 z-10 bg-black/40 backdrop-blur-md text-white text-[11px] px-2.5 py-1 rounded-full font-medium shadow-xs">
                      Baru
                    </div>
                  @endif

                  <!-- Location & Category Pill -->
                  <div class="absolute top-3 right-3 z-10 bg-white/90 backdrop-blur-md text-[#2B211E] text-[11px] font-semibold px-2.5 py-1 rounded-full shadow-xs">
                    {{ $dest->province?->name ?? $dest->city }}
                  </div>

                  <img src="{{ $imageSrc }}" 
                       alt="{{ $dest->name }}" 
                       class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"/>
                  
                  <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent pointer-events-none"></div>

                  <!-- Destination Title Overlay -->
                  <div class="absolute bottom-3 inset-x-3 text-white">
                    <span class="text-[10px] uppercase font-bold tracking-wider text-[#FFD3D2] block mb-0.5">
                      {{ $dest->category?->name ?? 'Wisata Edukasi' }}
                    </span>
                    <h2 class="text-lg font-bold drop-shadow-sm leading-snug">
                      <a href="{{ route('destinasi.show', $dest->slug) }}" class="hover:underline">
                        {{ $dest->name }}
                      </a>
                    </h2>
                  </div>
                </div>

                <!-- Body Info -->
                <div class="px-2 pb-2">
                  <p class="text-xs text-gray-500 line-clamp-2 leading-relaxed mb-3">
                    {{ \Illuminate\Support\Str::limit($dest->description, 120) }}
                  </p>

                  <div class="flex flex-wrap gap-1.5 mb-2">
                    <span class="text-[10px] px-2.5 py-0.5 bg-gray-100 text-gray-600 rounded-full font-medium">
                      {{ $dest->city }}
                    </span>
                    @if($dest->default_daily_capacity)
                      <span class="text-[10px] px-2.5 py-0.5 bg-emerald-50 text-emerald-700 rounded-full font-medium">
                        Kapasitas {{ $dest->default_daily_capacity }} pax
                      </span>
                    @endif
                  </div>
                </div>
              </div>

              <!-- Footer: Price Tag & Booking Action -->
              <div class="px-2 pt-3 border-t border-gray-100 flex items-center justify-between mt-2">
                <div>
                  <span class="text-[10px] text-gray-400 block font-medium">Mulai dari</span>
                  <span class="text-sm font-extrabold text-[#703A3A]">
                    Rp {{ number_format($minPrice, 0, ',', '.') }}
                    <span class="text-[10px] font-normal text-gray-500">/pax</span>
                  </span>
                </div>

                <a href="{{ route('destinasi.show', $dest->slug) }}" 
                   class="bg-[#703A3A] hover:bg-[#561D1E] text-white px-4 py-2 rounded-full text-xs font-bold transition-all flex items-center gap-1.5 shadow-xs">
                  <span>Lihat Tapak</span>
                  <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                </a>
              </div>

            </div>
          @empty
            <div class="col-span-1 sm:col-span-2 lg:col-span-3 py-16 text-center text-gray-500 bg-white rounded-3xl p-8 border border-gray-100">
              <span class="material-symbols-outlined text-4xl text-gray-400 mb-2">search_off</span>
              <p class="text-sm font-semibold">Tidak ada tapak yang sesuai dengan kriteria pencarian.</p>
              <button wire:click="resetFilters" class="inline-block mt-3 text-xs font-bold text-[#703A3A] hover:underline">
                Reset Pencarian
              </button>
            </div>
          @endforelse
        </div>

        <div class="mt-8">
          {{ $destinations->links() }}
        </div>
      </div>
    </section>
</div>
