@extends('layouts.public')

@section('title', 'Syarat & Ketentuan Layanan — Destinara')
@section('meta_description', 'Ketentuan hukum, hak, kewajiban, dan tata kelola transaksi di platform Destinara.')

@section('content')
<main class="w-full bg-[#FAF7F5] pt-24 pb-20">
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <div class="text-center mb-12">
      <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-[#51634b]/10 text-[#51634b] text-xs font-bold uppercase tracking-wider rounded-full mb-3">
        <span>Ketentuan Legal & Perlindungan Pengguna</span>
      </div>
      <h1 class="text-3xl sm:text-4xl font-extrabold text-[#1E1B1B] tracking-tight">
        Syarat & Ketentuan Layanan
      </h1>
      <p class="text-sm text-gray-500 mt-2 max-w-xl mx-auto">
        Berlaku efektif sejak 2026 bagi seluruh institusi pendidikan, pengelola tapak mitra, dan pengguna ekosistem Destinara.
      </p>
    </div>

    <div class="bg-white rounded-3xl p-6 sm:p-10 border border-gray-100 shadow-sm space-y-8 text-xs sm:text-sm text-slate-700 leading-relaxed">
      
      <section class="space-y-3">
        <h2 class="text-base sm:text-lg font-bold text-slate-900 border-b border-slate-100 pb-2">1. Ketentuan Umum</h2>
        <p>1.1. Platform Destinara (layanan.destinara.my.id) dikelola oleh PT DESTINARA CHAKRAWAL ARTHA sebagai perantara teknologi resmi antara institusi pendidikan (sekolah, universitas, lembaga riset) dan pengelola tapak kearifan lokal (desa wisata, komunitas adat, taman konservasi).</p>
        <p>1.2. Dengan mendaftar, mengakses, atau membuat pesanan di platform ini, Pengguna menyatakan telah membaca, memahami, dan menyetujui seluruh isi Syarat & Ketentuan ini.</p>
      </section>

      <section class="space-y-3">
        <h2 class="text-base sm:text-lg font-bold text-slate-900 border-b border-slate-100 pb-2">2. Akun & Keabsahan Institusi</h2>
        <p>2.1. Pendaftar akun buyer wajib bertindak atas nama instansi resmi atau memiliki surat tugas/mandat dari kepala sekolah, rektorat, atau pimpinan lembaga yang bersangkutan.</p>
        <p>2.2. Mitra tapak wajib menyertakan legalitas kelembagaan (SK Pokdarwis, Surat Keputusan Desa, atau Dokumen Kelembagaan Adat yang diakui) serta menyetujui Pakta Integritas Perlindungan Anak dan Kelestarian Lingkungan.</p>
      </section>

      <section class="space-y-3">
        <h2 class="text-base sm:text-lg font-bold text-slate-900 border-b border-slate-100 pb-2">3. Pemesanan, Pembayaran, dan SLA Konfirmasi</h2>
        <p>3.1. Pemesanan rombongan wajib memenuhi kuota minimal rombongan (min pax) dan batas waktu pemesanan (lead time) yang ditentukan oleh masing-masing tapak.</p>
        <p>3.2. Pembayaran wajib diselesaikan dalam jangka waktu yang tertera pada lembar tagihan resmi (Invoice). Keterlambatan pembayaran berakibat pada pembatalan otomatis alokasi kuota kunjungan.</p>
        <p>3.3. Mitra tapak memiliki batas waktu konfirmasi layanan maksimal 24 jam (SLA) sejak pembayaran diverifikasi. Apabila mitra tidak memberikan konfirmasi dalam batas waktu tersebut, sistem secara otomatis membatalkan pesanan dan mengembalikan dana 100% kepada pembeli.</p>
      </section>

      <section class="space-y-3">
        <h2 class="text-base sm:text-lg font-bold text-slate-900 border-b border-slate-100 pb-2">4. Kebijakan Pembatalan & Pengembalian Dana (Refund)</h2>
        <p>4.1. Pembatalan sepihak oleh institusi pembeli tunduk pada kebijakan pembatalan berjenjang (Cancellation Policy Tiers) yang telah ditetapkan pada profil destinasi.</p>
        <p>4.2. Pengembalian dana diproses secara akuntabel melalui transfer bank ke rekening instansi yang terdaftar resmi di akun pembeli dalam waktu 3-7 hari kerja operasional.</p>
      </section>

      <section class="space-y-3">
        <h2 class="text-base sm:text-lg font-bold text-slate-900 border-b border-slate-100 pb-2">5. Perlindungan Hak Adat & Protokol FPIC</h2>
        <p>5.1. Seluruh peserta kunjungan wajib menghormati norma sosial, etika berpakaian, dan zona larangan yang telah disosialisasikan oleh pengelola tapak.</p>
        <p>5.2. Segala bentuk komersialisasi pengetahuan lokal tanpa persetujuan tertulis dari tetua adat merupakan pelanggaran hukum hak kekayaan intelektual komunal.</p>
      </section>

    </div>

  </div>
</main>
@endsection
