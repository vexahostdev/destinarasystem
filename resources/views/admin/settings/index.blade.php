@extends('layouts.admin')

@section('title', 'Pengaturan Sistem Global')

@section('content')
<div class="space-y-6" x-data="{ activeGroup: '{{ old('group', request('group', $activeGroup)) }}' }">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-[#111827]">Pengaturan Ekosistem</h1>
            <p class="text-sm text-gray-500">Konfigurasi parameter bisnis, integrasi pihak ketiga terenkripsi, dan preferensi operasional</p>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="border-b border-gray-200">
        <nav class="flex space-x-6 overflow-x-auto text-xs font-bold uppercase tracking-wider">
            @foreach($groups as $grpKey => $grpLabel)
            <button @click="activeGroup = '{{ $grpKey }}'"
                    :class="activeGroup === '{{ $grpKey }}' ? 'border-[#1F2B45] text-[#1F2B45]' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-3 px-1 border-b-2 transition-all shrink-0">
                {{ $grpLabel }}
            </button>
            @endforeach
        </nav>
    </div>

    <!-- Settings Forms per Group -->
    @foreach($groups as $grpKey => $grpLabel)
    <form action="{{ route('admin.settings.update', ['group' => $grpKey]) }}" method="POST"
          x-show="activeGroup === '{{ $grpKey }}'" class="space-y-6" {!! $grpKey !== $activeGroup ? 'style="display: none;"' : '' !!}>
        @csrf
        <input type="hidden" name="group" value="{{ $grpKey }}">

        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-xs space-y-6">
            <div class="border-b border-gray-100 pb-4">
                <h3 class="text-base font-bold text-[#111827]">{{ $grpLabel }}</h3>
                <p class="text-xs text-gray-400">Parameter pengaturan grup {{ strtolower($grpLabel) }}</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                @forelse($groupedSettings[$grpKey] ?? [] as $field)
                    @php
                        $val = $field['current_value'];
                    @endphp

                    {{-- 1. Structured UI: Enabled Gateways Checkboxes --}}
                    @if($field['key'] === 'enabled_gateways')
                    <div class="sm:col-span-2 space-y-2">
                        <label class="block text-xs font-bold uppercase text-gray-700">
                            {{ $field['label'] ?? 'Gateway Pembayaran yang Diaktifkan' }}
                        </label>
                        @if(!empty($field['description']))
                            <p class="text-[11px] text-gray-400 mb-1">{{ $field['description'] }}</p>
                        @endif
                        @php
                            $eg = is_array($val) ? $val : (json_decode((string)$val, true) ?? ['xendit', 'manual_transfer']);
                        @endphp
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
                            <label class="flex items-center gap-3 p-3.5 rounded-2xl border border-gray-200 bg-gray-50/50 hover:bg-white cursor-pointer transition-all">
                                <input type="checkbox" name="enabled_gateways[]" value="xendit" {{ in_array('xendit', $eg) ? 'checked' : '' }} class="rounded border-gray-300 text-[#1F2B45] focus:ring-[#1F2B45]">
                                <div class="text-xs">
                                    <span class="font-bold text-gray-800 block">Xendit Invoice v2</span>
                                    <span class="text-[10px] text-gray-500">VA, QRIS, E-Wallet, CC</span>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 p-3.5 rounded-2xl border border-gray-200 bg-gray-50/50 hover:bg-white cursor-pointer transition-all">
                                <input type="checkbox" name="enabled_gateways[]" value="midtrans" {{ in_array('midtrans', $eg) ? 'checked' : '' }} class="rounded border-gray-300 text-[#1F2B45] focus:ring-[#1F2B45]">
                                <div class="text-xs">
                                    <span class="font-bold text-gray-800 block">Midtrans Snap</span>
                                    <span class="text-[10px] text-gray-500">Popup Multipayment</span>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 p-3.5 rounded-2xl border border-gray-200 bg-gray-50/50 hover:bg-white cursor-pointer transition-all">
                                <input type="checkbox" name="enabled_gateways[]" value="manual_transfer" {{ in_array('manual_transfer', $eg) ? 'checked' : '' }} class="rounded border-gray-300 text-[#1F2B45] focus:ring-[#1F2B45]">
                                <div class="text-xs">
                                    <span class="font-bold text-gray-800 block">Transfer Manual</span>
                                    <span class="text-[10px] text-gray-500">Multi-rekening & verifikasi bukti</span>
                                </div>
                            </label>
                        </div>
                        @error('enabled_gateways')
                            <p class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- 2. Structured UI: Manual Transfer Accounts Repeater --}}
                    @elseif($field['key'] === 'manual_transfer_accounts')
                    @php
                        $accountsList = is_array($val) ? $val : (json_decode((string)$val, true) ?? []);
                        if (!is_array($accountsList)) { $accountsList = []; }
                    @endphp
                    <div class="sm:col-span-2 space-y-3" x-data="{ accounts: {{ json_encode(array_values($accountsList)) }} }">
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-700">
                                    {{ $field['label'] ?? 'Daftar Rekening Transfer Manual' }}
                                </label>
                                <p class="text-[11px] text-gray-400">Pilihan rekening bank resmi tujuan transfer pemesanan buyer</p>
                            </div>
                            <button type="button" @click="accounts.push({ bank: 'Bank Central Asia', number: '', holder: '{{ setting('company_name', config('app.name')) }}' })"
                                    class="px-3.5 py-1.5 bg-stone-100 hover:bg-stone-200 text-stone-700 rounded-2xl text-xs font-bold flex items-center gap-1 transition-all">
                                <span class="material-symbols-outlined text-[16px]">add</span>
                                <span>Tambah Rekening</span>
                            </button>
                        </div>

                        <div class="space-y-3">
                            <template x-for="(acc, idx) in accounts" :key="idx">
                                <div class="p-3.5 bg-stone-50 rounded-2xl border border-stone-200/80 flex flex-col sm:flex-row items-center gap-3">
                                    <div class="w-full sm:w-1/3">
                                        <label class="block text-[10px] uppercase font-bold text-gray-500 mb-0.5">Bank Tujuan</label>
                                        <select :name="'manual_transfer_accounts[' + idx + '][bank]'" x-model="acc.bank"
                                                class="w-full text-xs rounded-xl border-gray-200 focus:border-[#1F2B45] focus:ring-[#1F2B45] bg-white">
                                            @foreach($banks as $b)
                                                <option value="{{ $b->name }}">{{ $b->name }} ({{ $b->code }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="w-full sm:w-1/3">
                                        <label class="block text-[10px] uppercase font-bold text-gray-500 mb-0.5">Nomor Rekening</label>
                                        <input type="text" :name="'manual_transfer_accounts[' + idx + '][number]'" x-model="acc.number"
                                               placeholder="1234567890" required
                                               class="w-full text-xs font-mono rounded-xl border-gray-200 focus:border-[#1F2B45] focus:ring-[#1F2B45] bg-white">
                                    </div>
                                    <div class="w-full sm:w-1/3">
                                        <label class="block text-[10px] uppercase font-bold text-gray-500 mb-0.5">Atas Nama</label>
                                        <input type="text" :name="'manual_transfer_accounts[' + idx + '][holder]'" x-model="acc.holder"
                                               placeholder="PT Destinara" required
                                               class="w-full text-xs rounded-xl border-gray-200 focus:border-[#1F2B45] focus:ring-[#1F2B45] bg-white">
                                    </div>
                                    <div class="sm:pt-4">
                                        <button type="button" @click="accounts.splice(idx, 1)"
                                                class="p-2 text-rose-600 hover:bg-rose-50 rounded-xl transition-all" title="Hapus rekening">
                                            <span class="material-symbols-outlined text-[18px]">delete</span>
                                        </button>
                                    </div>
                                </div>
                            </template>
                            <div x-show="accounts.length === 0" class="p-6 text-center text-xs text-gray-400 bg-stone-50 rounded-2xl border border-dashed border-stone-200">
                                Belum ada rekening transfer manual yang didaftarkan. Klik "Tambah Rekening" di atas.
                            </div>
                        </div>
                        @error('manual_transfer_accounts')
                            <p class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- 3. Structured UI: Admin Alert Emails Repeater --}}
                    @elseif($field['key'] === 'admin_alert_emails')
                    @php
                        $emailsList = is_array($val) ? $val : (json_decode((string)$val, true) ?? []);
                        if (!is_array($emailsList)) { $emailsList = []; }
                    @endphp
                    <div class="sm:col-span-2 space-y-3" x-data="{ emails: {{ json_encode(array_values($emailsList)) }} }">
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-700">
                                    {{ $field['label'] ?? 'Daftar Email Admin Notifikasi Darurat' }}
                                </label>
                                <p class="text-[11px] text-gray-400">Email admin internal untuk menerima alert gateway error, insiden sistem, dan pembayaran</p>
                            </div>
                            <button type="button" @click="emails.push('')"
                                    class="px-3.5 py-1.5 bg-stone-100 hover:bg-stone-200 text-stone-700 rounded-2xl text-xs font-bold flex items-center gap-1 transition-all">
                                <span class="material-symbols-outlined text-[16px]">add</span>
                                <span>Tambah Email</span>
                            </button>
                        </div>

                        <div class="space-y-2">
                            <template x-for="(email, idx) in emails" :key="idx">
                                <div class="flex items-center gap-2">
                                    <input type="email" :name="'admin_alert_emails[' + idx + ']'" x-model="emails[idx]"
                                           placeholder="admin@destinara.my.id" required
                                           class="flex-1 text-xs rounded-xl border-gray-200 focus:border-[#1F2B45] focus:ring-[#1F2B45] bg-white">
                                    <button type="button" @click="emails.splice(idx, 1)"
                                            class="p-2 text-rose-600 hover:bg-rose-50 rounded-xl transition-all" title="Hapus email">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </div>
                            </template>
                            <div x-show="emails.length === 0" class="p-4 text-center text-xs text-amber-700 bg-amber-50 rounded-2xl border border-amber-200">
                                Daftar email admin kosong. Wajib diisi minimal 1 email sebelum go-live.
                            </div>
                        </div>
                        @error('admin_alert_emails')
                            <p class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- 4. Structured UI: Admin Alert WhatsApp Repeater --}}
                    @elseif($field['key'] === 'admin_alert_whatsapp')
                    @php
                        $waList = is_array($val) ? $val : (json_decode((string)$val, true) ?? []);
                        if (!is_array($waList)) { $waList = []; }
                    @endphp
                    <div class="sm:col-span-2 space-y-3" x-data="{ numbers: {{ json_encode(array_values($waList)) }} }">
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-700">
                                    {{ $field['label'] ?? 'Daftar WhatsApp Admin Notifikasi Darurat' }}
                                </label>
                                <p class="text-[11px] text-gray-400">Nomor WhatsApp PIC admin (format internasional, cth: 6281234567890)</p>
                            </div>
                            <button type="button" @click="numbers.push('')"
                                    class="px-3.5 py-1.5 bg-stone-100 hover:bg-stone-200 text-stone-700 rounded-2xl text-xs font-bold flex items-center gap-1 transition-all">
                                <span class="material-symbols-outlined text-[16px]">add</span>
                                <span>Tambah Nomor</span>
                            </button>
                        </div>

                        <div class="space-y-2">
                            <template x-for="(num, idx) in numbers" :key="idx">
                                <div class="flex items-center gap-2">
                                    <input type="text" :name="'admin_alert_whatsapp[' + idx + ']'" x-model="numbers[idx]"
                                           placeholder="6281234567890" required
                                           class="flex-1 text-xs font-mono rounded-xl border-gray-200 focus:border-[#1F2B45] focus:ring-[#1F2B45] bg-white">
                                    <button type="button" @click="numbers.splice(idx, 1)"
                                            class="p-2 text-rose-600 hover:bg-rose-50 rounded-xl transition-all" title="Hapus nomor">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </div>
                            </template>
                            <div x-show="numbers.length === 0" class="p-4 text-center text-xs text-gray-400 bg-stone-50 rounded-2xl border border-dashed border-stone-200">
                                Belum ada nomor WhatsApp alert darurat yang didaftarkan.
                            </div>
                        </div>
                        @error('admin_alert_whatsapp')
                            <p class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Default Standard Fields --}}
                    @else
                    <div class="{{ ($field['type'] ?? 'text') === 'textarea' || is_array($field['current_value']) || ($field['type'] ?? '') === 'json' ? 'sm:col-span-2' : '' }} space-y-1">
                        <label class="block text-xs font-bold uppercase text-gray-700">
                            {{ $field['label'] ?? $field['key'] }}
                        </label>
                        @if(!empty($field['description']))
                            <p class="text-[11px] text-gray-400 mb-1">{{ $field['description'] }}</p>
                        @endif

                        @php
                            if (is_array($val)) {
                                $val = json_encode($val, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                            }
                        @endphp

                        @if(($field['type'] ?? 'text') === 'textarea' || is_array($field['current_value']) || ($field['type'] ?? '') === 'json')
                            <textarea name="{{ $field['key'] }}" rows="4"
                                      class="w-full text-xs font-mono rounded-xl border-gray-200 focus:border-[#1F2B45] focus:ring-[#1F2B45]">{{ $val }}</textarea>
                        @elseif(($field['type'] ?? 'bool') === 'bool' || ($field['type'] ?? '') === 'boolean')
                            <select name="{{ $field['key'] }}" class="w-full text-xs rounded-xl border-gray-200 focus:border-[#1F2B45] focus:ring-[#1F2B45]">
                                <option value="1" {{ $val ? 'selected' : '' }}>Aktif / Ya</option>
                                <option value="0" {{ !$val ? 'selected' : '' }}>Nonaktif / Tidak</option>
                            </select>
                        @elseif(($field['type'] ?? '') === 'secret')
                            <div class="space-y-1.5">
                                <input type="password" name="{{ $field['key'] }}" value=""
                                       placeholder="••••••••"
                                       autocomplete="new-password"
                                       class="w-full text-xs rounded-xl border-gray-200 focus:border-[#1F2B45] focus:ring-[#1F2B45]">
                                <div class="flex items-center justify-between text-[11px] text-gray-500">
                                    <span>
                                        Status: 
                                        @if(!empty($field['has_value']))
                                            <strong class="text-emerald-700">Tersimpan (••••{{ $field['masked_suffix'] }})</strong>
                                        @else
                                            <strong class="text-gray-400">Belum diisi</strong>
                                        @endif
                                    </span>
                                    @if(!empty($field['has_value']))
                                        <label class="inline-flex items-center gap-1.5 text-rose-600 cursor-pointer">
                                            <input type="checkbox" name="delete_secret_{{ $field['key'] }}" value="1" class="rounded border-gray-300 text-rose-600 focus:ring-rose-500">
                                            <span>Hapus nilai</span>
                                        </label>
                                    @endif
                                </div>
                            </div>
                        @elseif(($field['type'] ?? 'text') === 'password')
                            <input type="password" name="{{ $field['key'] }}" value=""
                                   placeholder="••••••••"
                                   class="w-full text-xs rounded-xl border-gray-200 focus:border-[#1F2B45] focus:ring-[#1F2B45]">
                        @elseif(($field['type'] ?? '') === 'select' && !empty($field['options']))
                            <select name="{{ $field['key'] }}" class="w-full text-xs rounded-xl border-gray-200 focus:border-[#1F2B45] focus:ring-[#1F2B45]">
                                @foreach($field['options'] as $optVal => $optLabel)
                                    <option value="{{ $optVal }}" {{ (string)$val === (string)$optVal ? 'selected' : '' }}>{{ $optLabel }}</option>
                                @endforeach
                            </select>
                        @else
                            <input type="{{ in_array($field['type'] ?? 'text', ['string', 'text', 'int', 'money', 'percent']) ? 'text' : ($field['type'] ?? 'text') }}" name="{{ $field['key'] }}" value="{{ $val }}"
                                   class="w-full text-xs rounded-xl border-gray-200 focus:border-[#1F2B45] focus:ring-[#1F2B45]">
                        @endif
                        @error($field['key'])
                            <p class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    @endif
                @empty
                <div class="sm:col-span-2 text-center py-6 text-gray-400 text-xs">
                    Tidak ada pengaturan di grup ini.
                </div>
                @endforelse
            </div>

            <div class="flex items-center justify-between pt-6 border-t border-gray-100">
                <div>
                    @if($grpKey === 'whatsapp')
                        <button type="submit" formaction="{{ route('admin.settings.test', 'whatsapp') }}"
                                class="border border-stone-300 text-stone-700 px-4 py-2 rounded-2xl text-xs font-bold uppercase hover:bg-stone-50">
                            Tes Koneksi WhatsApp
                        </button>
                    @elseif($grpKey === 'email' || $grpKey === 'mail')
                        <button type="submit" formaction="{{ route('admin.settings.test', 'mail') }}"
                                class="border border-stone-300 text-stone-700 px-4 py-2 rounded-2xl text-xs font-bold uppercase hover:bg-stone-50">
                            Tes Kirim Email SMTP
                        </button>
                    @elseif($grpKey === 'payment')
                        <div class="flex items-center gap-2">
                            <button type="submit" formaction="{{ route('admin.settings.test', 'xendit') }}"
                                    class="border border-stone-300 text-stone-700 px-3 py-1.5 rounded-2xl text-xs font-bold uppercase hover:bg-stone-50">
                                Tes Xendit
                            </button>
                            <button type="submit" formaction="{{ route('admin.settings.test', 'midtrans') }}"
                                    class="border border-stone-300 text-stone-700 px-3 py-1.5 rounded-2xl text-xs font-bold uppercase hover:bg-stone-50">
                                Tes Midtrans
                            </button>
                        </div>
                    @endif
                </div>

                <button type="submit" class="bg-[#1F2B45] hover:bg-[#16203A] text-white px-6 py-2.5 rounded-2xl text-xs font-bold uppercase tracking-wider shadow-xs transition-all">
                    Simpan Pengaturan {{ $grpLabel }}
                </button>
            </div>
        </div>
    </form>
    @endforeach
</div>
@endsection
