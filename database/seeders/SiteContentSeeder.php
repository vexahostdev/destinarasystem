<?php

namespace Database\Seeders;

use App\Models\SiteContent;
use Illuminate\Database\Seeder;

class SiteContentSeeder extends Seeder
{
    public function run(): void
    {
        $contents = [
            [
                'key' => 'hero',
                'title' => 'Eksplorasi Eduwisata & Penelitian Lapangan Berkelanjutan',
                'body' => 'Destinara menghubungkan institusi pendidikan, sekolah, dan perguruan tinggi dengan tapak desa wisata dan kawasan konservasi di seluruh Nusantara untuk pembelajaran kontekstual yang berdaya.',
                'meta' => [
                    'cta_primary_label' => 'Jelajahi Destinasi',
                    'cta_primary_url' => '/destinasi',
                    'cta_secondary_label' => 'Cara Kerja',
                    'cta_secondary_url' => '/cara-kerja',
                ],
            ],
            [
                'key' => 'cara_kerja',
                'title' => 'Cara Kerja Layanan Destinara',
                'body' => <<<'HTML'
<h3>1. Pilih Tapak & Paket Edukasi</h3>
<p>Temukan destinasi berbasis desa wisata atau kawasan konservasi yang sesuai dengan kurikulum pembelajaran atau topik riset institusi Anda.</p>

<h3>2. Ajukan Pemesanan & Rincian Peserta</h3>
<p>Tentukan tanggal kegiatan, jumlah peserta rombongan, dan kebutuhan add-on pendukung seperti akomodasi, pemandu lokal, atau draf surat izin penelitian.</p>

<h3>3. Pelunasan Aman & Terintegrasi</h3>
<p>Lakukan pembayaran instan melalui kanal virtual account, QRIS, e-wallet, atau transfer bank dengan kwitansi resmi elektronik.</p>

<h3>4. Konfirmasi Mitra & Dokumen Resmi</h3>
<p>Mitra pengelola tapak mengonfirmasi ketersediaan dalam SLA 48 jam. Surat konfirmasi kunjungan dan e-voucher ber-QR code otomatis terbit.</p>

<h3>5. Check-in Lapangan & Pelaksanaan</h3>
<p>Saat tiba di tapak, tunjukkan QR Code pada E-Voucher kepada pengelola lapangan untuk check-in instan dan nikmati kegiatan edukasi.</p>
HTML,
                'meta' => null,
            ],
            [
                'key' => 'faq',
                'title' => 'Pertanyaan yang Sering Diajukan (FAQ)',
                'body' => <<<'HTML'
<h3>Apakah institusi harus menunggu verifikasi sebelum bisa memesan?</h3>
<p>Tidak. Institusi dapat langsung mengajukan pemesanan paket setelah mendaftar dan melengkapi data PIC minimum. Verifikasi institusi berfungsi untuk memberikan status terverifikasi resmi pada dokumen.</p>

<h3>Bagaimana jika mitra pengelola tidak merespons dalam 48 jam?</h3>
<p>Sistem Destinara menerapkan SLA ketat 48 jam. Jika mitra tidak mengonfirmasi hingga batas waktu, pemesanan akan otomatis dibatalkan dan 100% dana Anda dikembalikan penuh tanpa potongan.</p>

<h3>Apakah Destinara membantu penerbitan surat izin penelitian?</h3>
<p>Ya. Untuk kategori penelitian, sistem secara otomatis menerbitkan Draf Surat Izin Kegiatan yang memuat judul riset, dosen pembimbing, dan spesifikasi rombongan untuk ditandatangani.</p>
HTML,
                'meta' => null,
            ],
            [
                'key' => 'syarat_ketentuan',
                'title' => 'Syarat dan Ketentuan Pemesanan Eduwisata',
                'body' => <<<'HTML'
<p>1. Seluruh pemesanan eduwisata dan penelitian melalui portal Destinara tunduk pada ketentuan batas waktu pembayaran (24 jam sejak pembuatan tagihan).</p>
<p>2. Pembatalan oleh pihak pembeli mengikuti ketentuan berjenjang destinasi terkait (Standar: H-14 refund 100%, H-7 refund 50%, kurang dari H-7 tanpa refund).</p>
<p>3. Peserta rombongan wajib menaati norma kearifan lokal, etika masyarakat adat, serta menjaga kelestarian ekosistem di kawasan tapak.</p>
<p>4. Kontak langsung perwakilan mitra di lapangan akan dibuka secara otomatis setelah pesanan berstatus Dikonfirmasi.</p>
HTML,
                'meta' => null,
            ],
        ];

        foreach ($contents as $content) {
            SiteContent::updateOrCreate(
                ['key' => $content['key']],
                $content
            );
        }
    }
}
