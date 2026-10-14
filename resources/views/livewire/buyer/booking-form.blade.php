<form wire:submit="submit" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
    @if($errors->has('form'))
        <div class="lg:col-span-12 p-4 bg-rose-50 border border-rose-200 text-rose-700 rounded-2xl text-sm flex items-center gap-3">
            <span class="material-symbols-outlined text-rose-600">error</span>
            <span>{{ $errors->first('form') }}</span>
        </div>
    @endif

    <!-- Form Controls Column (Kiri 8) -->
    <div class="lg:col-span-8 space-y-6">
        
        <!-- Pilihan Paket Edukasi -->
        <div class="bg-white p-6 sm:p-7 rounded-3xl border border-gray-100 shadow-sm space-y-4">
            <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-[#8C5151]"></span>
                <span>1. Pilih Paket Pembelajaran</span>
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($packages as $pkg)
                    <label class="cursor-pointer p-4 rounded-2xl border-2 transition-all flex flex-col justify-between 
                        {{ $packageId === $pkg->id ? 'border-[#8C5151] bg-[#8C5151]/5 shadow-sm' : 'border-slate-200 hover:border-slate-300' }}">
                        <div class="flex items-start justify-between">
                            <div>
                                <span class="font-bold text-slate-800 text-sm block">{{ $pkg->name }}</span>
                                <span class="text-xs text-slate-500">{{ $pkg->duration_days }} Hari • Kuota {{ $pkg->min_pax }}-{{ $pkg->max_pax }} Pax</span>
                            </div>
                            <input type="radio" wire:model.live="packageId" value="{{ $pkg->id }}" class="text-[#8C5151] focus:ring-[#8C5151]">
                        </div>
                        <div class="mt-3 pt-2 border-t border-slate-100">
                            <span class="text-xs text-slate-400">Tarif per peserta:</span>
                            <span class="text-sm font-extrabold text-[#8C5151] block">Rp {{ number_format($pkg->price_per_pax, 0, ',', '.') }}</span>
                        </div>
                    </label>
                @endforeach
            </div>
            @error('packageId') <p class="text-xs text-rose-500 font-semibold">{{ $message }}</p> @enderror
        </div>

        <!-- Tipe Kunjungan & Tanggal Rencana -->
        <div class="bg-white p-6 sm:p-7 rounded-3xl border border-gray-100 shadow-sm space-y-5">
            <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-[#8C5151]"></span>
                <span>2. Jadwal & Bentuk Kunjungan</span>
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tipe Kunjungan</label>
                    <select wire:model.live="inquiryType" class="w-full text-sm border border-slate-300 rounded-xl px-3.5 py-2.5 focus:ring-2 focus:ring-[#8C5151] outline-none">
                        <option value="study_tour">Ekskursi / Study Tour Sekolah (P5)</option>
                        <option value="penelitian">Riset Akademik / Pengabdian Masyarakat</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tanggal Mulai Kunjungan</label>
                    <input type="date" 
                           wire:model.live="plannedDateStart" 
                           min="{{ now()->addDays((int) setting('min_lead_days', 7))->format('Y-m-d') }}"
                           class="w-full text-sm border border-slate-300 rounded-xl px-3.5 py-2.5 focus:ring-2 focus:ring-[#8C5151] outline-none">
                    <p class="text-[11px] text-slate-400 mt-1">Minimal H-{{ (int) setting('min_lead_days', 7) }} sebelum pelaksanaan untuk kliring adat.</p>
                    @error('plannedDateStart') <p class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- Slot Status Alert -->
            <div class="p-3.5 rounded-2xl text-xs flex items-center gap-2.5 border 
                {{ $isSlotAvailable ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' }}">
                <span class="material-symbols-outlined text-[18px]">{{ $isSlotAvailable ? 'check_circle' : 'cancel' }}</span>
                <span class="font-medium">{{ $availabilityMessage }}</span>
            </div>
        </div>

        <!-- Jumlah Rombongan -->
        <div class="bg-white p-6 sm:p-7 rounded-3xl border border-gray-100 shadow-sm space-y-4">
            <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-[#8C5151]"></span>
                <span>3. Kuota Peserta & Pendamping</span>
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Jumlah Siswa / Peserta (Pax)</label>
                    <input type="number" wire:model.live.debounce.300ms="participantCount" min="1" class="w-full text-sm border border-slate-300 rounded-xl px-3.5 py-2.5 focus:ring-2 focus:ring-[#8C5151] outline-none">
                    @error('participantCount') <p class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Jumlah Guru Pendamping</label>
                    <input type="number" wire:model.live.debounce.300ms="companionCount" min="0" class="w-full text-sm border border-slate-300 rounded-xl px-3.5 py-2.5 focus:ring-2 focus:ring-[#8C5151] outline-none">
                    @error('companionCount') <p class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <!-- Layanan Tambahan (Add-ons) -->
        @if($addons->isNotEmpty())
            <div class="bg-white p-6 sm:p-7 rounded-3xl border border-gray-100 shadow-sm space-y-4">
                <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-[#8C5151]"></span>
                    <span>4. Fasilitas Tambahan (Opsional)</span>
                </h3>

                <div class="space-y-2.5">
                    @foreach($addons as $addon)
                        <label class="flex items-center justify-between p-3.5 rounded-2xl border border-slate-200 hover:bg-slate-50 cursor-pointer transition">
                            <div class="flex items-center gap-3">
                                <input type="checkbox" wire:model.live="selectedAddonIds" value="{{ $addon->id }}" class="rounded text-[#8C5151] focus:ring-[#8C5151]">
                                <div>
                                    <span class="text-xs font-bold text-slate-800 block">{{ $addon->name }}</span>
                                    <span class="text-[11px] text-slate-500">{{ $addon->description }}</span>
                                </div>
                            </div>
                            <span class="text-xs font-extrabold text-[#8C5151]">
                                +Rp {{ number_format($addon->price, 0, ',', '.') }}
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Catatan Pembelajaran & Surat Izin -->
        <div class="bg-white p-6 sm:p-7 rounded-3xl border border-gray-100 shadow-sm space-y-4">
            <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-[#8C5151]"></span>
                <span>5. Catatan Khusus & Administrasi</span>
            </h3>

            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Fokus Kajian / Harapan Pembelajaran</label>
                    <textarea wire:model="purposeNotes" rows="2" class="w-full text-sm border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-[#8C5151] outline-none" placeholder="Misal: Kajian vegetasi hutan bambu, wawancara tetua adat, dsb."></textarea>
                </div>

                <label class="flex items-start gap-2.5 cursor-pointer pt-2">
                    <input type="checkbox" wire:model="needsPermitLetter" class="rounded mt-0.5 text-[#8C5151] focus:ring-[#8C5151]">
                    <span class="text-xs text-slate-700">
                        <strong>Butuh draf Surat Izin Dinas Pendidikan / Kemenag:</strong> Sistem akan membuatkan draf resmi otomatis setelah pemesanan dikonfirmasi.
                    </span>
                </label>
            </div>
        </div>

    </div>

    <!-- Live Calculation Column (Kanan 4) -->
    <div class="lg:col-span-4 sticky top-24 space-y-4">
        <div class="bg-white rounded-3xl p-6 sm:p-7 shadow-lg border border-gray-100 flex flex-col gap-5">
            
            <div>
                <span class="text-xs uppercase tracking-wider font-bold text-[#8C5151] block mb-1">
                    Ringkasan Tagihan Resmi
                </span>
                <h4 class="text-xl font-extrabold text-slate-800">{{ $destination->name }}</h4>
                <p class="text-xs text-slate-500">{{ $destination->city }}, {{ $destination->province?->name }}</p>
            </div>

            <!-- Voucher Code Input -->
            <div class="pt-3 border-t border-slate-100">
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Voucher Diskon</label>
                <div class="flex gap-2">
                    <input type="text" 
                           wire:model.live.debounce.400ms="voucherCode" 
                           placeholder="KODE VOUCHER" 
                           class="w-full text-xs font-mono uppercase px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#8C5151] outline-none">
                </div>
                @if($voucherError)
                    <p class="text-[11px] text-rose-500 font-semibold mt-1">{{ $voucherError }}</p>
                @elseif(!empty($voucherCode) && $discount > 0)
                    <p class="text-[11px] text-emerald-600 font-semibold mt-1">✓ Voucher berhasil digunakan!</p>
                @endif
            </div>

            <!-- Price Breakdown -->
            <div class="space-y-2 text-xs border-t border-slate-100 pt-3">
                <div class="flex justify-between text-slate-600">
                    <span>Paket ({{ $participantCount }} pax):</span>
                    <span class="font-semibold text-slate-900">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                </div>
                @if($addonsTotal > 0)
                    <div class="flex justify-between text-slate-600">
                        <span>Layanan Tambahan:</span>
                        <span class="font-semibold text-slate-900">Rp {{ number_format($addonsTotal, 0, ',', '.') }}</span>
                    </div>
                @endif
                <div class="flex justify-between text-slate-600">
                    <span>Asuransi Lapangan:</span>
                    <span class="font-semibold text-slate-900">Rp {{ number_format($insuranceTotal, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Biaya Layanan & Administrasi:</span>
                    <span class="font-semibold text-slate-900">Rp {{ number_format($platformFee, 0, ',', '.') }}</span>
                </div>
                @if($discount > 0)
                    <div class="flex justify-between text-emerald-600 font-semibold">
                        <span>Diskon Voucher:</span>
                        <span>-Rp {{ number_format($discount, 0, ',', '.') }}</span>
                    </div>
                @endif
            </div>

            <!-- Total Investasi -->
            <div class="p-4 bg-[#FAF7F5] rounded-2xl border border-slate-200/60 flex justify-between items-baseline">
                <div>
                    <span class="text-xs text-slate-500 block font-medium">Total Tagihan:</span>
                    <span class="text-2xl font-black text-[#8C5151]">Rp {{ number_format($grandTotal, 0, ',', '.') }}</span>
                </div>
                <span class="text-[11px] text-slate-400">Termasuk BOS</span>
            </div>

            <!-- Submit Button -->
            <button type="submit" 
                    wire:loading.attr="disabled"
                    {{ ! $isSlotAvailable ? 'disabled' : '' }}
                    class="w-full bg-[#8C5151] hover:bg-[#734040] disabled:bg-slate-300 disabled:cursor-not-allowed text-white py-3.5 px-6 rounded-full font-bold text-sm text-center flex items-center justify-center gap-2 shadow-md hover:shadow-lg transition">
                <span wire:loading.remove>Lanjut ke Pembayaran</span>
                <span wire:loading class="inline-flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                    <span>Memproses Slot...</span>
                </span>
            </button>

            <div class="p-3 bg-emerald-50 rounded-2xl border border-emerald-200/50 text-[11px] text-emerald-800 space-y-0.5">
                <span class="font-bold block">✓ Slot Dikunci Otomatis</span>
                <p class="leading-relaxed">Kapasitas rombongan langsung di-hold aman selama batas waktu pembayaran berlaku.</p>
            </div>

        </div>
    </div>
</form>
