@extends('layouts.public')

@section('title', 'Tanya Jawab (FAQ) — Destinara')
@section('meta_description', 'Pertanyaan umum seputar pemesanan tapak edukasi, kuitansi BOS, perizinan dinas, dan tata kelola adat di Destinara.')

@section('content')
<main class="w-full bg-[#FAF7F5] pt-24 pb-20">
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <div class="text-center mb-12">
      <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-[#51634b]/10 text-[#51634b] text-xs font-bold uppercase tracking-wider rounded-full mb-3">
        <span>Pusat Bantuan & Regulasi</span>
      </div>
      <h1 class="text-3xl sm:text-4xl font-extrabold text-[#1E1B1B] tracking-tight">
        Pertanyaan Umum (FAQ)
      </h1>
      <p class="text-sm text-gray-500 mt-2 max-w-xl mx-auto">
        Jawaban transparan seputar tata cara reservasi rombongan sekolah, pelaporan SPJ BOS, dan protokol etika tapak.
      </p>
    </div>

    <div class="space-y-4" x-data="{ active: 1 }">
      
      <!-- FAQ 1 -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <button @click="active = active === 1 ? null : 1" class="w-full p-5 text-left font-bold text-slate-800 flex justify-between items-center text-sm sm:text-base hover:bg-slate-50 transition">
          <span>Bagaimana legalitas bukti transaksi Destinara untuk pelaporan Dana BOS / APBD?</span>
          <span class="material-symbols-outlined transition-transform duration-200" :class="active === 1 ? 'rotate-180 text-[#8C5151]' : 'text-slate-400'">expand_more</span>
        </button>
        <div x-show="active === 1" x-collapse class="px-5 pb-5 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-50 pt-3">
          Seluruh transaksi di platform Destinara diterbitkan langsung dengan dokumen resmi bertanda tangan digital dan QR verifikasi resmi: Invoice sah, Kuitansi pelunasan ber-NPSN/Nama Institusi, dan Berita Acara Kunjungan. Dokumen ini memenuhi standar akuntabilitas pertanggungjawaban BOSP Kemendikbudristek dan regulasi pengadaan barang/jasa institusi pendidikan.
        </div>
      </div>

      <!-- FAQ 2 -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <button @click="active = active === 2 ? null : 2" class="w-full p-5 text-left font-bold text-slate-800 flex justify-between items-center text-sm sm:text-base hover:bg-slate-50 transition">
          <span>Apa itu protokol FPIC dan bagaimana penerapannya bagi rombongan sekolah?</span>
          <span class="material-symbols-outlined transition-transform duration-200" :class="active === 2 ? 'rotate-180 text-[#8C5151]' : 'text-slate-400'">expand_more</span>
        </button>
        <div x-show="active === 2" x-collapse class="px-5 pb-5 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-50 pt-3">
          FPIC (<em>Free, Prior, and Informed Consent</em>) adalah persetujuan atas dasar informasi di awal tanpa paksaan dari tetua adat dan pengelola komunitas lokal. Melalui Destinara, setiap kunjungan dipastikan telah disetujui pemangku tapak, menghormati zona sakral, serta mematuhi aturan busana dan etika setempat.
        </div>
      </div>

      <!-- FAQ 3 -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <button @click="active = active === 3 ? null : 3" class="w-full p-5 text-left font-bold text-slate-800 flex justify-between items-center text-sm sm:text-base hover:bg-slate-50 transition">
          <span>Bagaimana alur pembayaran dan batas waktu pelunasan?</span>
          <span class="material-symbols-outlined transition-transform duration-200" :class="active === 3 ? 'rotate-180 text-[#8C5151]' : 'text-slate-400'">expand_more</span>
        </button>
        <div x-show="active === 3" x-collapse class="px-5 pb-5 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-50 pt-3">
          Setelah mengajukan reservasi, institusi memiliki batas waktu pembayaran (sesuai invoice) melalui Virtual Account, Transfer Bank, atau QRIS. Setelah pembayaran diverifikasi oleh gateway, slot ketersediaan otomatis dikunci (booked) dan mitra tapak diwajibkan mengonfirmasi kesiapan teknis dalam kurun waktu {{ (int) setting('partner_confirm_sla_hours', 48) }} jam (SLA).
        </div>
      </div>

      <!-- FAQ 4 -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <button @click="active = active === 4 ? null : 4" class="w-full p-5 text-left font-bold text-slate-800 flex justify-between items-center text-sm sm:text-base hover:bg-slate-50 transition">
          <span>Bagaimana kebijakan pembatalan dan pengembalian dana (refund)?</span>
          <span class="material-symbols-outlined transition-transform duration-200" :class="active === 4 ? 'rotate-180 text-[#8C5151]' : 'text-slate-400'">expand_more</span>
        </button>
        <div x-show="active === 4" x-collapse class="px-5 pb-5 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-50 pt-3">
          Kebijakan pembatalan diatur transparan sesuai tiering waktu hari H kunjungan yang tertera di setiap halaman tapak (misal: H-14 refund 100%, H-7 refund 50%, H-3 refund 0%). Jika pembatalan terjadi karena mitra tapak tidak memenuhi SLA konfirmasi atau terjadi keadaan kahar (force majeure), dana dikembalikan 100% penuh.
        </div>
      </div>

      <!-- FAQ 5 -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <button @click="active = active === 5 ? null : 5" class="w-full p-5 text-left font-bold text-slate-800 flex justify-between items-center text-sm sm:text-base hover:bg-slate-50 transition">
          <span>Apakah Destinara menyediakan draf surat izin resmi dinas dan orang tua?</span>
          <span class="material-symbols-outlined transition-transform duration-200" :class="active === 5 ? 'rotate-180 text-[#8C5151]' : 'text-slate-400'">expand_more</span>
        </button>
        <div x-show="active === 5" x-collapse class="px-5 pb-5 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-50 pt-3">
          Ya. Setiap pesanan rombongan yang telah dibayar mendapatkan generator otomatis draf Surat Izin Dinas Pendidikan / Kemenag dan Surat Persetujuan Orang Tua lengkap dengan rincian silabus edukasi dan mitigasi risiko kegiatan.
        </div>
      </div>

    </div>

    <!-- Contact Box -->
    <div class="mt-12 p-6 bg-white rounded-3xl border border-gray-100 shadow-xs text-center">
      <h3 class="text-base font-bold text-slate-800">Masih membutuhkan informasi tambahan?</h3>
      <p class="text-xs text-slate-500 mt-1">Tim pendamping teknis Destinara siap membantu kebutuhan proposal dan surat-menyurat instansi Anda.</p>
      <div class="mt-4 flex justify-center gap-3">
        <a href="https://wa.me/{{ preg_replace('/[^\d]/', '', (string) setting('support_whatsapp', setting('company_phone', '628111111111'))) }}" target="_blank" class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#8C5151] hover:bg-[#734040] text-white text-xs font-bold rounded-full transition shadow-xs">
          <span class="material-symbols-outlined text-[16px]">chat</span>
          <span>Konsultasi via WhatsApp</span>
        </a>
      </div>
    </div>

  </div>
</main>
@endsection
