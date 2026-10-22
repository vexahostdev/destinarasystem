@extends('layouts.public')

@section('title', 'Alur & Cara Kerja Kemitraan Edukasi Lapangan — Destinara')
@section('meta_description', 'Protokol kemitraan edukasi lapangan yang transparan, aman, dan sah secara hukum antara sekolah, universitas, dan pengelola tapak adat.')

@section('content')
<main class="w-full bg-[#FFFFFF] pb-20 pt-20 sm:pt-24">
  <div class="max-w-[1360px] mx-auto px-4 sm:px-6 lg:px-8 space-y-12">

    <!-- Header Section -->
    <div class="bg-white rounded-3xl p-8 sm:p-12 shadow-xs border border-gray-100 text-center max-w-4xl mx-auto space-y-4">
      <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-[#51634b]/10 text-[#51634b] text-xs font-bold uppercase tracking-wider">
        <span class="material-symbols-outlined text-[16px]">verified</span>
        <span>Protokol Operasional & Tata Kelola</span>
      </span>
      <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-[#111827] tracking-tight leading-tight">
        Alur Kemitraan Edukasi Lapangan yang Transparan dan Sah
      </h1>
      <p class="text-sm sm:text-base text-gray-600 max-w-2xl mx-auto leading-relaxed">
        Menjembatani tuntutan akuntabilitas kurikulum sekolah formal dengan penghormatan tulus terhadap kearifan tetua adat di seluruh pelosok Nusantara.
      </p>
    </div>

    <!-- 4 Langkah Prosedur -->
    <div class="space-y-6">
      <div class="max-w-xl">
        <span class="text-xs font-bold uppercase tracking-wider text-[#1F2B45] block mb-1">
          Alur End-to-End
        </span>
        <h2 class="text-2xl sm:text-3xl font-extrabold text-[#111827] tracking-tight">
          Empat Tahapan Kunjungan Rombongan
        </h2>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <!-- Step 1 -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 border border-gray-100 shadow-xs hover:shadow-md transition-all flex flex-col justify-between gap-4">
          <div class="space-y-3">
            <span class="text-3xl font-extrabold text-[#1F2B45] block leading-none">01</span>
            <span class="text-[10px] font-bold uppercase tracking-wider text-[#51634b] block">Tahap Eksplorasi</span>
            <h3 class="text-lg font-bold text-[#111827]">Reservasi Jadwal & Kuota</h3>
            <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
              Institusi memilih destinasi terkurasi, mencocokkan kalender slot akademik, dan menentukan estimasi rombongan.
            </p>
          </div>
          <div class="pt-3 border-t border-gray-100 text-xs font-semibold text-[#51634b] flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[16px]">schedule</span>
            <span>Realtime Slot Kunjungan</span>
          </div>
        </div>

        <!-- Step 2 -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 border border-gray-100 shadow-xs hover:shadow-md transition-all flex flex-col justify-between gap-4">
          <div class="space-y-3">
            <span class="text-3xl font-extrabold text-[#51634b] block leading-none">02</span>
            <span class="text-[10px] font-bold uppercase tracking-wider text-[#51634b] block">Tahap Verifikasi</span>
            <h3 class="text-lg font-bold text-[#111827]">Konfirmasi Mitra & Invoice</h3>
            <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
              Pengelola desa memvalidasi kesiapan homestay dan jadwal tetua adat. Sistem otomatis menerbitkan invoice instansi.
            </p>
          </div>
          <div class="pt-3 border-t border-gray-100 text-xs font-semibold text-[#51634b] flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[16px]">timer</span>
            <span>Maks. {{ (int) setting('partner_confirm_sla_hours', 48) }} Jam Kerja</span>
          </div>
        </div>

        <!-- Step 3 -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 border border-gray-100 shadow-xs hover:shadow-md transition-all flex flex-col justify-between gap-4">
          <div class="space-y-3">
            <span class="text-3xl font-extrabold text-amber-600 block leading-none">03</span>
            <span class="text-[10px] font-bold uppercase tracking-wider text-amber-700 block">Tahap Pembayaran</span>
            <h3 class="text-lg font-bold text-[#111827]">Escrow Aman Terproteksi</h3>
            <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
              Pembayaran diproses melalui rekening resmi (Virtual Account, Transfer Bank, QRIS) dengan bukti setor sah bendahara BOS.
            </p>
          </div>
          <div class="pt-3 border-t border-gray-100 text-xs font-semibold text-amber-700 flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[16px]">lock</span>
            <span>Rekening Resmi Terjamin</span>
          </div>
        </div>

        <!-- Step 4 -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 border border-gray-100 shadow-xs hover:shadow-md transition-all flex flex-col justify-between gap-4">
          <div class="space-y-3">
            <span class="text-3xl font-extrabold text-[#1F2B45] block leading-none">04</span>
            <span class="text-[10px] font-bold uppercase tracking-wider text-[#1F2B45] block">Tahap Pelaksanaan</span>
            <h3 class="text-lg font-bold text-[#111827]">Kegiatan & Penyaluran</h3>
            <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
              Rombongan belajar di tapak. Setelah program terlaksana, dana diteruskan secara transparan ke BUMDes adat setempat.
            </p>
          </div>
          <div class="pt-3 border-t border-gray-100 text-xs font-semibold text-[#1F2B45] flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[16px]">sync_alt</span>
            <span>Rekonsiliasi Otomatis</span>
          </div>
        </div>

      </div>
    </div>

    <!-- 3 Pilar Landasan Etika Lapangan -->
    <div class="bg-white rounded-3xl p-8 sm:p-12 border border-gray-100 shadow-xs space-y-8">
      <div class="max-w-2xl">
        <span class="text-xs font-bold uppercase tracking-wider text-[#51634b] block mb-1">
          Landasan Komitmen
        </span>
        <h2 class="text-2xl sm:text-3xl font-extrabold text-[#111827]">
          Tiga Komitmen Fundamental Menjaga Kualitas & Etika
        </h2>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8">
        
        <div class="p-6 rounded-2xl bg-[#FFFFFF] border border-gray-200/60 flex flex-col justify-between gap-4">
          <div class="space-y-3">
            <div class="w-12 h-12 rounded-full bg-[#1F2B45]/10 text-[#1F2B45] flex items-center justify-center">
              <span class="material-symbols-outlined text-2xl">health_and_safety</span>
            </div>
            <h3 class="text-lg font-bold text-[#111827]">Standar Keselamatan</h3>
            <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
              Audit fasilitas mencakup sanitasi air bersih, jalur evakuasi bencana, koordinasi faskes terdekat, dan perlindungan asuransi rombongan.
            </p>
          </div>
          <div class="text-xs font-bold text-[#1F2B45]">
            Audit berkala per semester
          </div>
        </div>

        <div class="p-6 rounded-2xl bg-[#FFFFFF] border border-gray-200/60 flex flex-col justify-between gap-4">
          <div class="space-y-3">
            <div class="w-12 h-12 rounded-full bg-[#51634b]/10 text-[#51634b] flex items-center justify-center">
              <span class="material-symbols-outlined text-2xl">handshake</span>
            </div>
            <h3 class="text-lg font-bold text-[#111827]">Protokol Etika Adat (FPIC)</h3>
            <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
              Prinsip <em>Free, Prior, and Informed Consent</em> memastikan tidak ada eksploitasi sakral. Tetua berhak membatasi zona tertentu.
            </p>
          </div>
          <div class="text-xs font-bold text-[#51634b]">
            Perlindungan kearifan tak benda
          </div>
        </div>

        <div class="p-6 rounded-2xl bg-[#FFFFFF] border border-gray-200/60 flex flex-col justify-between gap-4">
          <div class="space-y-3">
            <div class="w-12 h-12 rounded-full bg-amber-500/10 text-amber-700 flex items-center justify-center">
              <span class="material-symbols-outlined text-2xl">gavel</span>
            </div>
            <h3 class="text-lg font-bold text-[#111827]">Keabsahan Dokumen BOS & Riset</h3>
            <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
              Dokumentasi resmi surat izin survei, MoU kemitraan, dan kuitansi sah yang dapat dipertanggungjawabkan pada audit instansi.
            </p>
          </div>
          <div class="text-xs font-bold text-amber-700">
            Standar akuntabilitas resmi
          </div>
        </div>

      </div>
    </div>

    <!-- Misi Kebangsaan Photo Strip -->
    <div class="bg-white rounded-3xl p-8 sm:p-12 border border-gray-100 shadow-xs grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
      <div class="lg:col-span-6 space-y-4">
        <span class="text-xs font-bold uppercase tracking-wider text-[#1F2B45]">
          Misi Kebangsaan
        </span>
        <h2 class="text-2xl sm:text-3xl font-extrabold text-[#111827] leading-tight">
          Menghubungkan Ruang Kelas dengan Realitas Tanah Air
        </h2>
        <p class="text-sm sm:text-base text-gray-600 leading-relaxed">
          "Pendidikan lapangan bukan sekadar tamasya di luar kelas. Ketika siswa duduk beralas tikar di balai desa, menyimak sesepuh memilah tanaman obat, mereka tidak hanya belajar biologi — mereka belajar mencintai dan merawat martabat bangsanya."
        </p>
        <div class="pt-2">
          <span class="text-sm font-bold text-[#111827] block">Tim Kurasi Lapang Destinara</span>
          <span class="text-xs text-gray-400">Yogyakarta & Flores, Indonesia</span>
        </div>
      </div>

      <div class="lg:col-span-6 grid grid-cols-2 gap-4">
        <div class="aspect-[4/3] rounded-2xl overflow-hidden shadow-xs">
          <img src="{{ asset('assets/img/hd/sekolah-diskusi.jpg') }}" alt="Diskusi Siswa" class="w-full h-full object-cover">
        </div>
        <div class="aspect-[4/3] rounded-2xl overflow-hidden shadow-xs mt-6">
          <img src="{{ asset('assets/img/hd/peneliti-wawancara.jpg') }}" alt="Wawancara Lapangan" class="w-full h-full object-cover">
        </div>
      </div>
    </div>

    <!-- Bottom CTA Banner -->
    <div class="bg-gradient-to-r from-[#1F2B45] to-[#16203A] rounded-3xl p-8 sm:p-12 text-white shadow-xl flex flex-col md:flex-row items-center justify-between gap-8">
      <div class="space-y-2 max-w-xl text-center md:text-left">
        <h3 class="text-2xl sm:text-3xl font-extrabold">Siap Mengawali Kemitraan Tapak?</h3>
        <p class="text-sm text-white/80 leading-relaxed">
          Bergabunglah bersama puluhan sekolah dan kampus yang telah mengagendakan pembelajaran lapangan bersama Destinara.
        </p>
      </div>
      <a href="{{ route('register') }}" class="bg-white hover:bg-gray-100 text-[#1F2B45] font-bold px-7 py-3.5 rounded-2xl text-sm shadow-md transition-all hover:scale-103 shrink-0">
        Daftar Akun Lapangan
      </a>
    </div>

  </div>
</main>
@endsection
