<?php

namespace Database\Seeders;

use App\Models\DocumentTemplate;
use Illuminate\Database\Seeder;

class DocumentTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'type' => 'invoice',
                'title' => 'Invoice Tagihan Pemesanan',
                'body' => <<<'HTML'
<div class="doc-header">
    <h2>INVOICE TAGIHAN PEMESANAN</h2>
    <p>Nomor: <strong>{{document_number}}</strong> | Tanggal: {{issue_date}}</p>
</div>
<div class="doc-section">
    <table class="info-table">
        <tr>
            <td width="50%">
                <strong>DITAGIHKAN KEPADA:</strong><br>
                {{institution_name}}<br>
                PIC: {{pic_name}} ({{pic_phone}})<br>
                Email: {{pic_email}}
            </td>
            <td width="50%">
                <strong>RINCIAN DESTINASI:</strong><br>
                {{destination_name}}<br>
                Lokasi: {{destination_city}}, {{destination_province}}<br>
                Jadwal: {{planned_date_start}} s.d. {{planned_date_end}}
            </td>
        </tr>
    </table>
</div>
<div class="doc-items">
    <table class="items-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Rincian Layanan / Paket Edukasi</th>
                <th>Jumlah</th>
                <th>Harga Satuan</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            {{items_table_rows}}
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" align="right"><strong>Total Tagihan</strong></td>
                <td><strong>Rp {{total_amount}}</strong></td>
            </tr>
        </tfoot>
    </table>
</div>
<div class="doc-notice">
    <p>Batas Akhir Pembayaran: <strong>{{expires_at}}</strong></p>
    <p>Pembayaran dapat dilakukan melalui gateway resmi Destinara atau transfer ke rekening tertera pada sistem.</p>
</div>
HTML,
            ],
            [
                'type' => 'receipt',
                'title' => 'Kwitansi Pembayaran Lunas',
                'body' => <<<'HTML'
<div class="doc-header">
    <h2>KWITANSI PEMBAYARAN LUNAS</h2>
    <p>Nomor: <strong>{{document_number}}</strong> | Tanggal: {{paid_at}}</p>
</div>
<div class="doc-section">
    <p>Telah diterima pembayaran dari:</p>
    <table class="info-table">
        <tr>
            <td width="30%"><strong>Nama Institusi</strong></td>
            <td>: {{institution_name}}</td>
        </tr>
        <tr>
            <td><strong>Nama PIC</strong></td>
            <td>: {{pic_name}}</td>
        </tr>
        <tr>
            <td><strong>Untuk Pembayaran</strong></td>
            <td>: Pemesanan Eduwisata {{destination_name}} (Kode Booking: {{booking_code}})</td>
        </tr>
        <tr>
            <td><strong>Jumlah Peserta</strong></td>
            <td>: {{participant_count}} Peserta</td>
        </tr>
        <tr>
            <td><strong>Metode Pembayaran</strong></td>
            <td>: {{payment_method}} ({{payment_channel}})</td>
        </tr>
        <tr>
            <td><strong>Jumlah Lunas</strong></td>
            <td>: <strong style="font-size: 16px;">Rp {{total_amount}}</strong> (<em>{{amount_words}} rupiah</em>)</td>
        </tr>
    </table>
</div>
<div class="doc-notice success">
    <p><strong>STATUS: LUNAS</strong> — Kwitansi ini merupakan bukti pembayaran elektronik yang sah dari PT Destinara Edukasi Nusantara.</p>
</div>
HTML,
            ],
            [
                'type' => 'confirmation_letter',
                'title' => 'Surat Konfirmasi Kunjungan Lapangan',
                'body' => <<<'HTML'
<div class="doc-header">
    <h2>SURAT KONFIRMASI KUNJUNGAN LAPANGAN</h2>
    <p>Nomor: <strong>{{document_number}}</strong> | Tanggal: {{confirmed_at}}</p>
</div>
<div class="doc-body">
    <p>Dengan hormat,</p>
    <p>Berdasarkan koordinasi dan persetujuan dari Pengelola <strong>{{destination_name}}</strong>, melalui surat ini kami mengonfirmasi bahwa rombongan edukasi berikut telah resmi terdaftar dan diterima:</p>
    
    <table class="info-table">
        <tr>
            <td width="35%"><strong>Institusi Pemohon</strong></td>
            <td>: {{institution_name}}</td>
        </tr>
        <tr>
            <td><strong>Penanggung Jawab (PIC)</strong></td>
            <td>: {{pic_name}} ({{pic_phone}})</td>
        </tr>
        <tr>
            <td><strong>Destinasi Tujuan</strong></td>
            <td>: {{destination_name}}</td>
        </tr>
        <tr>
            <td><strong>Paket Kegiatan</strong></td>
            <td>: {{package_name}} ({{inquiry_type_label}})</td>
        </tr>
        <tr>
            <td><strong>Tanggal Pelaksanaan</strong></td>
            <td>: <strong>{{planned_date_start}} s.d. {{planned_date_end}}</strong></td>
        </tr>
        <tr>
            <td><strong>Jumlah Peserta</strong></td>
            <td>: {{participant_count}} Orang (Pendamping: {{companion_count}} Orang)</td>
        </tr>
        <tr>
            <td><strong>Kontak Pengelola Lapangan</strong></td>
            <td>: {{contact_person_name}} ({{contact_person_phone}})</td>
        </tr>
    </table>

    <p style="margin-top: 15px;">Pihak pengelola tapak telah menyiapkan fasilitas, instruktur/pemandu lokal, dan kesiapan lokasi sesuai rincian paket kegiatan yang disepakati.</p>
</div>
HTML,
            ],
            [
                'type' => 'e_voucher',
                'title' => 'E-Voucher Kunjungan & Tiket Check-in',
                'body' => <<<'HTML'
<div class="doc-header">
    <h2>E-VOUCHER KUNJUNGAN &amp; CHECK-IN</h2>
    <p>Kode Booking: <strong>{{booking_code}}</strong> | E-Voucher ID: <strong>{{document_number}}</strong></p>
</div>
<div class="voucher-box">
    <table width="100%">
        <tr>
            <td width="70%" valign="top">
                <h3 style="margin-top:0;">{{destination_name}}</h3>
                <p><strong>Institusi:</strong> {{institution_name}}</p>
                <p><strong>Paket:</strong> {{package_name}}</p>
                <p><strong>Jadwal Kunjungan:</strong> {{planned_date_start}} s.d. {{planned_date_end}}</p>
                <p><strong>Kapasitas Terdaftar:</strong> {{participant_count}} Peserta ({{companion_count}} Pendamping)</p>
                <p><strong>Status:</strong> <span class="badge badge-success">SIAP KUNJUNGAN</span></p>
            </td>
            <td width="30%" align="center" valign="middle">
                <div class="qr-container">
                    <img src="{{checkin_qr_svg_base64}}" width="130" height="130" alt="QR Check-in"><br>
                    <small>Tunjukkan QR ini ke Petugas Tapak saat tiba</small>
                </div>
            </td>
        </tr>
    </table>
</div>
<div class="doc-instructions">
    <h4>PETUNJUK KEDATANGAN:</h4>
    <ol>
        <li>Tunjukkan lembar E-Voucher ini (cetak atau dari ponsel) kepada pengelola tapak di pintu masuk / titik kumpul.</li>
        <li>Petugas tapak akan memindai QR Code untuk memvalidasi rombongan dan mencatat peserta aktual.</li>
        <li>Patuhi norma adat, keselamatan kerja, serta protokol kelestarian lingkungan tapak.</li>
    </ol>
</div>
HTML,
            ],
            [
                'type' => 'permit_draft',
                'title' => 'Draf Surat Permohonan Izin Penelitian / Kunjungan',
                'body' => <<<'HTML'
<div class="doc-header watermark-draft">
    <h2>DRAF SURAT PENGANTAR IZIN KEGIATAN</h2>
    <p>Nomor Draf: <strong>{{document_number}}</strong></p>
</div>
<div class="doc-body">
    <p>Kepada Yth.<br>
    <strong>Pengelola / Pimpinan {{destination_name}}</strong><br>
    di Tempat</p>

    <p>Dengan hormat,</p>
    <p>Sehubungan dengan pelaksanaan kegiatan akademik / riset lapangan dari <strong>{{institution_name}}</strong>, bersama surat ini kami mengajukan draf izin kegiatan dengan rincian:</p>

    <table class="info-table">
        <tr>
            <td width="30%"><strong>Judul / Topik</strong></td>
            <td>: {{permit_title}}</td>
        </tr>
        <tr>
            <td><strong>Dosen / Penanggung Jawab</strong></td>
            <td>: {{permit_supervisor}}</td>
        </tr>
        <tr>
            <td><strong>Instansi / Jurusan</strong></td>
            <td>: {{institution_name}}</td>
        </tr>
        <tr>
            <td><strong>Jumlah Peneliti/Peserta</strong></td>
            <td>: {{participant_count}} Orang</td>
        </tr>
        <tr>
            <td><strong>Waktu Pelaksanaan</strong></td>
            <td>: {{planned_date_start}} s.d. {{planned_date_end}}</td>
        </tr>
    </table>

    <p><em>*Catatan: Surat ini berstatus draf elektronik. Versi bertanda tangan resmi kepala institusi dapat diunggah melalui portal Destinara untuk diteruskan ke pihak otoritas setempat.</em></p>
</div>
HTML,
            ],
            [
                'type' => 'permit_signed',
                'title' => 'Surat Izin Bertanda Tangan Resmi',
                'body' => <<<'HTML'
<div class="doc-header">
    <h2>SURAT IZIN KEGIATAN LAPANGAN (FINAL)</h2>
    <p>Nomor: <strong>{{document_number}}</strong></p>
</div>
<div class="doc-body">
    <p>Surat izin kegiatan resmi yang telah ditandatangani oleh pemangku kepentingan dan pihak pengelola untuk pemesanan <strong>{{booking_code}}</strong> di <strong>{{destination_name}}</strong>.</p>
</div>
HTML,
            ],
            [
                'type' => 'payout_receipt',
                'title' => 'Bukti Transfer Pencairan Mitra (Payout Receipt)',
                'body' => <<<'HTML'
<div class="doc-header">
    <h2>BUKTI PENCAIRAN DANA MITRA PENGELOLA</h2>
    <p>Nomor Bukti: <strong>{{document_number}}</strong> | Tanggal: {{transferred_at}}</p>
</div>
<div class="doc-body">
    <table class="info-table">
        <tr>
            <td width="35%"><strong>Nomor Payout</strong></td>
            <td>: {{payout_number}}</td>
        </tr>
        <tr>
            <td><strong>Mitra Penerima</strong></td>
            <td>: {{organization_name}}</td>
        </tr>
        <tr>
            <td><strong>Rekening Tujuan</strong></td>
            <td>: {{bank_name}} - {{account_number}} a.n. {{account_holder}}</td>
        </tr>
        <tr>
            <td><strong>Kode Booking Terkait</strong></td>
            <td>: {{booking_code}} ({{destination_name}})</td>
        </tr>
        <tr>
            <td><strong>Pendapatan Bruto</strong></td>
            <td>: Rp {{gross_amount}}</td>
        </tr>
        <tr>
            <td><strong>Komisi Platform</strong></td>
            <td>: - Rp {{commission_amount}}</td>
        </tr>
        <tr>
            <td><strong>Jumlah Bersih Ditransfer</strong></td>
            <td>: <strong style="font-size: 16px;">Rp {{net_amount}}</strong></td>
        </tr>
    </table>
    <p style="margin-top: 15px;">Dana telah berhasil ditransfer ke rekening mitra. Terima kasih atas kemitraan yang terjalin dengan Destinara.</p>
</div>
HTML,
            ],
        ];

        foreach ($templates as $tmpl) {
            DocumentTemplate::updateOrCreate(
                ['type' => $tmpl['type']],
                [
                    'title' => $tmpl['title'],
                    'body' => $tmpl['body'],
                    'is_active' => true,
                ]
            );
        }
    }
}
