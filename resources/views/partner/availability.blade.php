@extends('layouts.partner')

@section('title', 'Kelola Ketersediaan & Kalender Slot')

@section('content')
<div x-data="{
    showEditModal: false,
    showBulkModal: false,
    selectedSlot: { id: null, date: '', capacity: 100, status: 'open' },
    openEdit(slot) {
        this.selectedSlot = { ...slot };
        this.showEditModal = true;
    }
}" class="space-y-8">

    <!-- Header & Dropdown Pilihan Destinasi -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-[#1F2937]/12">
        <div>
            <div class="flex items-center gap-2 text-xs text-[#1F2937]/60 mb-2">
                <a href="{{ route('mitra.dashboard') }}" class="hover:text-[#1F2B45]">Dashboard</a>
                <span>/</span>
                <span class="text-[#1F2B45] font-semibold">Kalender Ketersediaan Slot</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#1F2937]">Pengaturan Slot Kunjungan Rombongan</h1>
            <p class="text-xs sm:text-sm text-[#1F2937]/70 mt-1">
                Kendalikan daya dukung lingkungan (*carrying capacity*) tapak agar kunjungan sekolah tidak melebihi kapasitas adat.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            @if($currentDestination)
            <button type="button" @click="showBulkModal = true" class="rgs-btn-primary px-4 py-2 text-xs font-bold uppercase tracking-wider flex items-center gap-1.5 shadow-xs">
                <span class="material-symbols-outlined text-[16px]">date_range</span>
                <span>Buat Slot Massal</span>
            </button>
            @endif

            <!-- Selector Destinasi -->
            @if($destinations->count() > 1)
            <div class="flex items-center gap-2">
                <label class="text-xs font-bold uppercase text-[#1F2937]/70">Pilih Tapak:</label>
                <form method="GET" action="{{ route('mitra.availability') }}">
                    <select name="destinasi" onchange="this.form.submit()" class="bg-white border border-[#1F2937]/20 px-3 py-2 text-xs font-bold text-[#1F2937] focus:outline-none focus:border-[#1F2B45]">
                        @foreach($destinations as $d)
                            <option value="{{ $d->slug }}" {{ ($currentDestination && $currentDestination->id == $d->id) ? 'selected' : '' }}>
                                {{ $d->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
            @endif
        </div>
    </div>

    <!-- Ringkasan Tapak Aktif & Keterangan Warna Slot -->
    <div class="rgs-card p-5 border-t-4 border-t-[#1F2B45] flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white">
        <div>
            <span class="text-[10px] font-bold uppercase tracking-wider text-[#1F2B45] block">Kalender Aktif:</span>
            <h2 class="font-serif text-xl font-bold text-[#1F2937]">{{ $currentDestination->name ?? 'Tapak Belum Dipilih' }}</h2>
            <div class="text-xs text-[#1F2937]/60 mt-0.5">
                Kapasitas Standar: <strong>120 Orang / Hari</strong> • Batas Maksimal Rombongan demi Konservasi Lingkungan
            </div>
        </div>

        <!-- Legend Warna Slot -->
        <div class="flex flex-wrap items-center gap-4 text-xs font-semibold">
            <div class="flex items-center gap-1.5">
                <span class="w-3 h-3 bg-[#51634b] border border-[#51634b]"></span>
                <span class="text-[#1F2937]/80">Kuota Buka (Tersedia)</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-3 h-3 bg-red-500 border border-red-600"></span>
                <span class="text-[#1F2937]/80">Kuota Penuh</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-3 h-3 bg-gray-400 border border-gray-500"></span>
                <span class="text-[#1F2937]/80">Tutup (Upacara Adat)</span>
            </div>
        </div>
    </div>

    <!-- Grid Kalender Slot 30 Hari Ke Depan -->
    <div class="space-y-3">
        <h3 class="font-serif text-lg font-bold text-[#1F2937]">Jadwal Ketersediaan 30 Hari Ke Depan</h3>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 lg:grid-cols-6 gap-3">
            @forelse($slots as $slot)
            <div class="rgs-card p-3.5 border-t-4 {{ $slot->status === 'open' ? 'border-t-[#51634b]' : ($slot->status === 'full' ? 'border-t-red-500' : 'border-t-gray-500') }} hover:shadow-md transition-all flex flex-col justify-between space-y-2">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-wider text-[#1F2937]/60">
                        {{ \Carbon\Carbon::parse($slot->date)->translatedFormat('l') }}
                    </div>
                    <div class="font-serif text-base font-bold text-[#1F2937]">
                        {{ \Carbon\Carbon::parse($slot->date)->translatedFormat('d M Y') }}
                    </div>
                </div>

                <div class="space-y-1 text-xs">
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="text-[#1F2937]/60">Terisi:</span>
                        <span class="font-mono font-bold">{{ $slot->booked_count }} / {{ $slot->capacity }}</span>
                    </div>

                    <!-- Okupansi Bar -->
                    @php $pct = min(100, round(($slot->booked_count / max(1, $slot->capacity)) * 100)); @endphp
                    <div class="w-full h-1.5 bg-gray-200 overflow-hidden">
                        <div class="h-full {{ $slot->status === 'open' ? 'bg-[#51634b]' : ($slot->status === 'full' ? 'bg-red-500' : 'bg-gray-400') }}" style="width: {{ $pct }}%;"></div>
                    </div>

                    <div class="pt-1 text-center">
                        @if($slot->status === 'open')
                            <span class="px-2 py-0.5 bg-[#51634b]/15 text-[#51634b] text-[10px] font-bold uppercase tracking-wider block">
                                Buka
                            </span>
                        @elseif($slot->status === 'full')
                            <span class="px-2 py-0.5 bg-red-100 text-red-700 text-[10px] font-bold uppercase tracking-wider block">
                                Penuh
                            </span>
                        @else
                            <span class="px-2 py-0.5 bg-gray-200 text-gray-700 text-[10px] font-bold uppercase tracking-wider block">
                                Tutup Adat
                            </span>
                        @endif
                    </div>
                </div>

                <button type="button" @click="openEdit({ id: {{ $slot->id }}, date: '{{ $slot->date }}', capacity: {{ $slot->capacity }}, status: '{{ $slot->status }}' })" class="w-full py-1 text-[11px] font-bold uppercase tracking-wider text-[#1F2B45] hover:bg-[#1F2B45]/10 border border-[#1F2B45]/20 transition-colors">
                    Ubah Slot
                </button>
            </div>
            @empty
            <div class="col-span-full rgs-card p-8 text-center text-xs text-[#1F2937]/60">
                Belum ada slot tanggal yang dibuat untuk tapak ini.
            </div>
            @endforelse
        </div>
    </div>

    <!-- Modal Dialog Pengaturan Slot (Alpine.js) -->
    <div x-show="showEditModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto bg-[#1F2937]/50 flex items-center justify-center p-4">
        <div @click.away="showEditModal = false" class="bg-white max-w-md w-full border border-[#1F2937]/20 p-6 space-y-5 shadow-xl">
            <div class="flex items-center justify-between pb-3 border-b border-[#1F2937]/10">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#1F2B45] text-[20px]">edit_calendar</span>
                    <h3 class="font-serif text-lg font-bold text-[#1F2937]">Perbarui Slot Tanggal</h3>
                </div>
                <button type="button" @click="showEditModal = false" class="text-gray-400 hover:text-gray-700">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>

            <form action="{{ route('mitra.slot.update') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <input type="hidden" name="slot_id" :value="selectedSlot.id">

                <div class="space-y-1">
                    <label class="block font-bold uppercase text-[#1F2937]/70">Tanggal Terpilih:</label>
                    <div class="p-2.5 bg-[#F4F6F9] font-mono font-bold text-sm text-[#1F2B45]" x-text="selectedSlot.date"></div>
                </div>

                <div class="space-y-1">
                    <label class="block font-bold uppercase text-[#1F2937]">Status Ketersediaan <span class="text-red-500">*</span></label>
                    <select name="status" x-model="selectedSlot.status" class="w-full bg-[#F4F6F9] border border-[#1F2937]/20 p-2.5 text-xs font-bold text-[#1F2937]">
                        <option value="open">Buka Kuota (Menerima Rombongan)</option>
                        <option value="full">Tandai Penuh (Kapasitas Tercapai)</option>
                        <option value="closed">Tutup Kawasan (Upacara Adat / Konservasi)</option>
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="block font-bold uppercase text-[#1F2937]">Kapasitas Maksimal Harian (Orang) <span class="text-red-500">*</span></label>
                    <input type="number" name="capacity" min="10" max="500" x-model="selectedSlot.capacity" class="w-full bg-[#F4F6F9] border border-[#1F2937]/20 p-2 text-xs font-bold">
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-[#1F2937]/10">
                    <button type="button" @click="showEditModal = false" class="rgs-btn-outline px-4 py-2 text-xs font-bold uppercase">Batal</button>
                    <button type="submit" class="rgs-btn-primary px-5 py-2 text-xs font-bold uppercase">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Dialog Bulk Generate Slot (Alpine.js) -->
    @if($currentDestination)
    <div x-show="showBulkModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto bg-[#1F2937]/50 flex items-center justify-center p-4">
        <div @click.away="showBulkModal = false" class="bg-white max-w-lg w-full border border-[#1F2937]/20 p-6 space-y-5 shadow-xl">
            <div class="flex items-center justify-between pb-3 border-b border-[#1F2937]/10">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#1F2B45] text-[20px]">calendar_month</span>
                    <h3 class="font-serif text-lg font-bold text-[#1F2937]">Buat Slot Massal — {{ $currentDestination->name }}</h3>
                </div>
                <button type="button" @click="showBulkModal = false" class="text-gray-400 hover:text-gray-700">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>

            <form action="{{ route('mitra.slots.bulk') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <input type="hidden" name="destination_id" value="{{ $currentDestination->id }}">

                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="block font-bold uppercase text-[#1F2937]">Tanggal Mulai <span class="text-red-500">*</span></label>
                        <input type="date" name="start_date" value="{{ date('Y-m-d') }}" min="{{ date('Y-m-d') }}" required class="w-full bg-[#F4F6F9] border border-[#1F2937]/20 p-2 text-xs font-bold">
                    </div>
                    <div class="space-y-1">
                        <label class="block font-bold uppercase text-[#1F2937]">Tanggal Akhir <span class="text-red-500">*</span></label>
                        <input type="date" name="end_date" value="{{ date('Y-m-d', strtotime('+30 days')) }}" min="{{ date('Y-m-d') }}" required class="w-full bg-[#F4F6F9] border border-[#1F2937]/20 p-2 text-xs font-bold">
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="block font-bold uppercase text-[#1F2937]">Kapasitas Kuota per Hari <span class="text-red-500">*</span></label>
                    <input type="number" name="capacity" value="{{ $currentDestination->default_daily_capacity ?? 100 }}" min="10" max="1000" required class="w-full bg-[#F4F6F9] border border-[#1F2937]/20 p-2 text-xs font-bold">
                </div>

                <div class="space-y-2">
                    <label class="block font-bold uppercase text-[#1F2937]">Pilih Hari Buka Mingguan <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-4 gap-2">
                        @foreach([1 => 'Sen', 2 => 'Sel', 3 => 'Rab', 4 => 'Kam', 5 => 'Jum', 6 => 'Sab', 0 => 'Min'] as $dayVal => $dayLabel)
                        <label class="flex items-center gap-1.5 p-2 bg-[#F4F6F9] border border-[#1F2937]/10 cursor-pointer text-xs">
                            <input type="checkbox" name="days_of_week[]" value="{{ $dayVal }}" checked class="accent-[#1F2B45]">
                            <span>{{ $dayLabel }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-[#1F2937]/10">
                    <button type="button" @click="showBulkModal = false" class="rgs-btn-outline px-4 py-2 text-xs font-bold uppercase">Batal</button>
                    <button type="submit" class="rgs-btn-primary px-5 py-2 text-xs font-bold uppercase">Generate Slot</button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
@endsection
