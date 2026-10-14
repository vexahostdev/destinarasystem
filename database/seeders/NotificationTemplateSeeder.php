<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // auth.welcome
            [
                'event_key' => 'auth.welcome',
                'channel' => 'mail',
                'audience' => 'buyer',
                'subject' => 'Selamat Datang di Destinara, Solusi Eduwisata & Penelitian',
                'body' => "Halo {{user_name}},\n\nSelamat datang di platform Destinara. Akun institusi Anda telah berhasil dibuat. Anda kini dapat menjelajahi direktori tapak eduwisata, mengunduh proposal paket kegiatan, dan mengajukan pemesanan rombongan secara langsung.\n\nSalam hangat,\nTim Destinara",
            ],
            [
                'event_key' => 'auth.welcome',
                'channel' => 'mail',
                'audience' => 'partner',
                'subject' => 'Selamat Bergabung sebagai Mitra Pengelola Tapak Destinara',
                'body' => "Halo {{user_name}},\n\nTerima kasih telah bergabung sebagai Mitra Pengelola Tapak di Destinara. Silakan lengkapi profil organisasi dan dokumen legalitas di portal mitra agar destinasi Anda dapat diverifikasi dan ditayangkan.\n\nSalam,\nTim Destinara",
            ],

            // auth.verify_email
            [
                'event_key' => 'auth.verify_email',
                'channel' => 'mail',
                'audience' => 'buyer',
                'subject' => 'Verifikasi Alamat Email Anda - Destinara',
                'body' => "Halo {{user_name}},\n\nSilakan klik tautan berikut untuk memverifikasi alamat email Anda:\n{{action_url}}\n\nTautan ini berlaku selama 60 menit.\n\nTim Destinara",
            ],

            // auth.reset_password
            [
                'event_key' => 'auth.reset_password',
                'channel' => 'mail',
                'audience' => 'buyer',
                'subject' => 'Atur Ulang Kata Sandi Akun Destinara',
                'body' => "Halo {{user_name}},\n\nKami menerima permintaan untuk mengatur ulang kata sandi akun Anda. Klik tautan berikut:\n{{action_url}}\n\nJika Anda tidak meminta perubahan ini, abaikan email ini.\n\nTim Destinara",
            ],

            // booking.created
            [
                'event_key' => 'booking.created',
                'channel' => 'mail',
                'audience' => 'buyer',
                'subject' => 'Pemesanan Dibuat: {{booking_code}} di {{destination_name}}',
                'body' => "Halo {{pic_name}},\n\nPemesanan kegiatan Anda di {{destination_name}} berhasil dibuat dengan kode {{booking_code}}.\nJumlah Peserta: {{participant_count}} orang\nTanggal: {{planned_date_start}} s.d. {{planned_date_end}}\nTotal Tagihan: Rp {{total_amount}}\n\nSilakan selesaikan pembayaran sebelum batas waktu: {{expires_at}} melalui tautan:\n{{payment_url}}\n\nTerima kasih,\nTim Destinara",
            ],
            [
                'event_key' => 'booking.created',
                'channel' => 'whatsapp',
                'audience' => 'buyer',
                'subject' => 'Pemesanan Dibuat {{booking_code}}',
                'body' => "Halo *{{pic_name}}*, pemesanan eduwisata Anda di *{{destination_name}}* telah dibuat dengan kode *{{booking_code}}*.\n\nTotal: *Rp {{total_amount}}*\nBatas Bayar: {{expires_at}}\n\nSelesaikan pembayaran melalui tautan berikut:\n{{payment_url}}\n\n- Destinara",
            ],
            [
                'event_key' => 'booking.created',
                'channel' => 'database',
                'audience' => 'admin',
                'subject' => 'Booking Baru Masuk: {{booking_code}}',
                'body' => 'Booking baru {{booking_code}} dibuat oleh {{institution_name}} untuk {{destination_name}} (Rp {{total_amount}}).',
            ],

            // payment.reminder
            [
                'event_key' => 'payment.reminder',
                'channel' => 'whatsapp',
                'audience' => 'buyer',
                'subject' => 'Pengingat Pembayaran: {{booking_code}}',
                'body' => "Halo *{{pic_name}}*, pembayaran pemesanan *{{booking_code}}* di *{{destination_name}}* akan kedaluwarsa pada {{expires_at}} (kurang dari {{hours_left}} jam lagi).\n\nSegera selesaikan pembayaran di tautan ini:\n{{payment_url}}\n\n- Destinara",
            ],

            // booking.expired
            [
                'event_key' => 'booking.expired',
                'channel' => 'mail',
                'audience' => 'buyer',
                'subject' => 'Pemesanan Kedaluwarsa: {{booking_code}}',
                'body' => "Halo {{pic_name}},\n\nPemesanan {{booking_code}} di {{destination_name}} telah kedaluwarsa karena pembayaran belum diselesaikan hingga batas waktu. Alokasi slot kunjungan telah dilepaskan kembali.\n\nAnda dapat membuat pemesanan ulang kapan saja di portal Destinara.\n\nTim Destinara",
            ],

            // payment.paid
            [
                'event_key' => 'payment.paid',
                'channel' => 'mail',
                'audience' => 'buyer',
                'subject' => 'Pembayaran Berhasil Diterima: {{booking_code}}',
                'body' => "Halo {{pic_name}},\n\nPembayaran sebesar Rp {{total_amount}} untuk pemesanan {{booking_code}} telah berhasil kami terima. Saat ini pemesanan sedang menunggu konfirmasi ketersediaan akhir dari Mitra Pengelola (SLA maksimal 48 jam).\n\nKwitansi pembayaran resmi dapat diakses di portal Anda.\n\nTim Destinara",
            ],
            [
                'event_key' => 'payment.paid',
                'channel' => 'whatsapp',
                'audience' => 'partner',
                'subject' => 'Pesanan Terbayar: {{booking_code}} Siap Dikonfirmasi',
                'body' => "Halo Pengelola *{{destination_name}}*!\n\nPesanan *{{booking_code}}* dari *{{institution_name}}* ({{participant_count}} peserta) pada tanggal {{planned_date_start}} telah LUNAS.\n\nMohon konfirmasi kesiapan slot sebelum {{confirm_deadline}}:\n{{partner_booking_url}}\n\n- Destinara",
            ],
            [
                'event_key' => 'payment.paid',
                'channel' => 'database',
                'audience' => 'admin',
                'subject' => 'Pembayaran Masuk: {{booking_code}}',
                'body' => 'Pembayaran untuk {{booking_code}} sebesar Rp {{total_amount}} berhasil diterima via {{payment_gateway}}.',
            ],

            // partner.confirm_reminder
            [
                'event_key' => 'partner.confirm_reminder',
                'channel' => 'whatsapp',
                'audience' => 'partner',
                'subject' => 'PENTING: Segera Konfirmasi Pesanan {{booking_code}}',
                'body' => "Halo Mitra *{{destination_name}}*, batas konfirmasi pesanan *{{booking_code}}* tersisa kurang dari 24 jam (Deadline: {{confirm_deadline}}).\n\nSegera konfirmasi ketersediaan di: {{partner_booking_url}} agar pesanan tidak batal otomatis.\n\n- Destinara",
            ],

            // booking.confirmed
            [
                'event_key' => 'booking.confirmed',
                'channel' => 'mail',
                'audience' => 'buyer',
                'subject' => 'Pemesanan Dikonfirmasi: {{booking_code}} di {{destination_name}}',
                'body' => "Halo {{pic_name}},\n\nKabar baik! Mitra Pengelola {{destination_name}} telah MENYETUJUI pemesanan kunjungan Anda (Kode: {{booking_code}}).\n\nSurat Konfirmasi Kunjungan dan E-Voucher resmi ber-QR Code telah diterbitkan dan dapat diunduh di portal institusi Anda. Kontak PIC pengelola lapangan kini telah terbuka untuk koordinasi teknis.\n\nSampai jumpa di lapangan!\nTim Destinara",
            ],
            [
                'event_key' => 'booking.confirmed',
                'channel' => 'whatsapp',
                'audience' => 'buyer',
                'subject' => 'Pemesanan Dikonfirmasi {{booking_code}}',
                'body' => "Kabar baik *{{pic_name}}*! Pesanan *{{booking_code}}* di *{{destination_name}}* telah DIKONFIRMASI oleh mitra pengelola.\n\nE-Voucher dan Surat Konfirmasi dapat diunduh di portal Anda:\n{{booking_url}}\n\nKontak pengelola lapangan kini telah tersedia di detail booking.\n\n- Destinara",
            ],

            // booking.rejected
            [
                'event_key' => 'booking.rejected',
                'channel' => 'mail',
                'audience' => 'buyer',
                'subject' => 'Pemesanan Ditolak Mitra: {{booking_code}}',
                'body' => "Halo {{pic_name}},\n\nMohon maaf, pemesanan {{booking_code}} di {{destination_name}} belum dapat diterima oleh mitra pengelola dengan alasan: {{cancel_reason}}.\n\nDana yang telah Anda bayarkan sebesar Rp {{total_amount}} akan kami kembalikan 100% penuh. Tim keuangan kami sedang memproses pengembalian dana ke rekening Anda.\n\nTim Destinara",
            ],

            // booking.auto_cancelled
            [
                'event_key' => 'booking.auto_cancelled',
                'channel' => 'mail',
                'audience' => 'buyer',
                'subject' => 'Pemesanan Dibatalkan Otomatis (SLA): {{booking_code}}',
                'body' => "Halo {{pic_name}},\n\nPemesanan {{booking_code}} dibatalkan secara otomatis oleh sistem karena mitra pengelola belum mengonfirmasi dalam batas waktu SLA 48 jam. Dana pembayaran Rp {{total_amount}} akan di-refund 100% ke rekening Anda.\n\nTim Destinara",
            ],

            // booking.cancelled
            [
                'event_key' => 'booking.cancelled',
                'channel' => 'mail',
                'audience' => 'buyer',
                'subject' => 'Pembatalan Pemesanan: {{booking_code}}',
                'body' => "Halo {{pic_name}},\n\nPemesanan {{booking_code}} telah dibatalkan. Sesuai kebijakan pembatalan destinasi, estimasi refund Anda adalah: Rp {{refund_amount}} ({{refund_percent}}%).\n\nTim Destinara",
            ],

            // booking.rescheduled
            [
                'event_key' => 'booking.rescheduled',
                'channel' => 'mail',
                'audience' => 'buyer',
                'subject' => 'Jadwal Kunjungan Diperbarui: {{booking_code}}',
                'body' => "Halo {{pic_name}},\n\nJadwal kunjungan pemesanan {{booking_code}} di {{destination_name}} telah diperbarui menjadi:\nTanggal Baru: {{planned_date_start}} s.d. {{planned_date_end}}\n\nDokumen Surat Konfirmasi dan E-Voucher telah diperbarui dengan versi terbaru.\n\nTim Destinara",
            ],

            // visit.reminder
            [
                'event_key' => 'visit.reminder',
                'channel' => 'whatsapp',
                'audience' => 'buyer',
                'subject' => 'Pengingat Kunjungan: H-{{days_left}} di {{destination_name}}',
                'body' => "Halo *{{pic_name}}*, ini pengingat bahwa kunjungan rombongan *{{institution_name}}* ke *{{destination_name}}* (Kode: {{booking_code}}) akan berlangsung dalam {{days_left}} hari lagi (Tanggal: {{planned_date_start}}).\n\nPastikan telah menyiapkan E-Voucher ber-QR dan perlengkapan peserta.\n\n- Destinara",
            ],

            // booking.checked_in
            [
                'event_key' => 'booking.checked_in',
                'channel' => 'whatsapp',
                'audience' => 'buyer',
                'subject' => 'Check-in Berhasil: {{booking_code}}',
                'body' => "Selamat datang di *{{destination_name}}*! Check-in rombongan *{{institution_name}}* telah berhasil diverifikasi oleh petugas lapangan (Peserta aktual: {{actual_participant_count}} pax). Selamat berkegiatan!\n\n- Destinara",
            ],

            // booking.completed
            [
                'event_key' => 'booking.completed',
                'channel' => 'mail',
                'audience' => 'buyer',
                'subject' => 'Kunjungan Selesai: Bagikan Ulasan Anda untuk {{destination_name}}',
                'body' => "Halo {{pic_name}},\n\nTerima kasih telah mempercayakan kegiatan edukasi lapangan institusi Anda bersama Destinara di {{destination_name}}.\n\nKami sangat mengapresiasi jika Anda berkenan meluangkan waktu 1 menit untuk memberikan ulasan dan rating pengalaman Anda melalui tautan berikut:\n{{review_url}}\n\nSampai jumpa pada agenda eduwisata berikutnya!\nTim Destinara",
            ],

            // refund.created & refund.completed
            [
                'event_key' => 'refund.created',
                'channel' => 'mail',
                'audience' => 'buyer',
                'subject' => 'Permintaan Pengembalian Dana (Refund): {{booking_code}}',
                'body' => "Halo {{pic_name}},\n\nPermintaan refund untuk pemesanan {{booking_code}} sebesar Rp {{refund_amount}} telah tercatat dan sedang dalam antrean pemrosesan oleh tim keuangan kami.\n\nTim Destinara",
            ],
            [
                'event_key' => 'refund.completed',
                'channel' => 'mail',
                'audience' => 'buyer',
                'subject' => 'Pengembalian Dana Telah Ditransfer: {{booking_code}}',
                'body' => "Halo {{pic_name}},\n\nDana pengembalian (refund) sebesar Rp {{refund_amount}} untuk pemesanan {{booking_code}} telah berhasil kami transfer ke rekening Anda ({{buyer_bank_name}} - {{buyer_account_number}} a.n {{buyer_account_holder}}). Bukti transfer dapat diunduh di portal Anda.\n\nTim Destinara",
            ],

            // payout.created & payout.transferred
            [
                'event_key' => 'payout.created',
                'channel' => 'mail',
                'audience' => 'partner',
                'subject' => 'Pencairan Pendapatan Terjadwal: {{payout_number}}',
                'body' => "Halo Mitra {{organization_name}},\n\nPendapatan dari pemesanan {{booking_code}} sebesar Rp {{net_amount}} telah masuk jadwal pencairan (Nomor Payout: {{payout_number}}). Tim keuangan akan segera memproses transfer.\n\nTim Destinara",
            ],
            [
                'event_key' => 'payout.transferred',
                'channel' => 'whatsapp',
                'audience' => 'partner',
                'subject' => 'Dana Pendapatan Telah Ditransfer: {{payout_number}}',
                'body' => "Halo Mitra *{{organization_name}}*! Dana bagi hasil kunjungan {{booking_code}} sebesar *Rp {{net_amount}}* telah berhasil kami transfer ke rekening Anda ({{bank_name}} {{account_number}}).\n\nBukti transfer dan rekap dapat diunduh di portal mitra.\n\n- Destinara",
            ],

            // destination.approved / revision / rejected
            [
                'event_key' => 'destination.submitted',
                'channel' => 'database',
                'audience' => 'admin',
                'subject' => 'Destinasi Baru Menunggu Verifikasi: {{destination_name}}',
                'body' => "Mitra {{partner_name}} telah mengajukan destinasi baru '{{destination_name}}' untuk dikurasi.",
            ],
            [
                'event_key' => 'destination.approved',
                'channel' => 'mail',
                'audience' => 'partner',
                'subject' => 'Selamat! Destinasi Anda Telah Disetujui & Tayang: {{destination_name}}',
                'body' => "Halo Mitra {{partner_name}},\n\nDestinasi '{{destination_name}}' telah selesai dikurasi oleh tim Destinara dan kini telah resmi TAYANG di platform. Pengunjung dan institusi dapat mulai melakukan pemesanan.\n\nTim Destinara",
            ],
            [
                'event_key' => 'destination.revision_required',
                'channel' => 'mail',
                'audience' => 'partner',
                'subject' => 'Perlu Revisi: Pengajuan Destinasi {{destination_name}}',
                'body' => "Halo Mitra {{partner_name}},\n\nPengajuan destinasi '{{destination_name}}' memerlukan beberapa perbaikan sebelum dapat ditayangkan. Catatan kurator:\n\"{{moderation_note}}\"\n\nSilakan perbarui data destinasi di portal mitra.\n\nTim Destinara",
            ],

            // partner.verified
            [
                'event_key' => 'partner.verified',
                'channel' => 'whatsapp',
                'audience' => 'partner',
                'subject' => 'Akun Mitra Terverifikasi',
                'body' => "Selamat! Akun Mitra *{{organization_name}}* telah TERVERIFIKASI oleh tim Destinara. Anda kini dapat mengajukan destinasi dan menerima pemesanan institusi.\n\n- Destinara",
            ],

            // institution.verified
            [
                'event_key' => 'institution.verified',
                'channel' => 'mail',
                'audience' => 'buyer',
                'subject' => 'Institusi Anda Terverifikasi di Destinara',
                'body' => "Halo {{pic_name}},\n\nData institusi {{institution_name}} telah berhasil diverifikasi oleh tim Destinara. Badge Terverifikasi kini aktif di profil Anda.\n\nTim Destinara",
            ],

            // system alerts
            [
                'event_key' => 'system.wa_disconnected',
                'channel' => 'mail',
                'audience' => 'admin',
                'subject' => '[ALERT] Sesi WhatsApp Gateway Terputus',
                'body' => 'Peringatan Sistem: Sesi WhatsApp VexaHost terdeteksi terputus pada {{timestamp}}. Notifikasi WhatsApp tidak dapat terkirim sampai sesi dipindai ulang.',
            ],
            [
                'event_key' => 'payment.late_received',
                'channel' => 'mail',
                'audience' => 'admin',
                'subject' => '[PERHATIAN] Pembayaran Masuk Melebihi Batas (Late Payment): {{booking_code}}',
                'body' => 'Pembayaran sebesar Rp {{amount}} diterima untuk booking {{booking_code}} yang sudah berstatus {{booking_status}}. Refund otomatis 100% telah disiapkan dalam antrean.',
            ],
            [
                'event_key' => 'payment.amount_mismatch',
                'channel' => 'mail',
                'audience' => 'admin',
                'subject' => '[DARURAT] Nominal Webhook Pembayaran Tidak Sesuai: {{booking_code}}',
                'body' => 'Nominal pembayaran pada webhook (Rp {{received_amount}}) berbeda dengan tagihan di sistem (Rp {{expected_amount}}) untuk external_id {{external_id}}.',
            ],
        ];

        foreach ($templates as $tmpl) {
            NotificationTemplate::updateOrCreate(
                [
                    'event_key' => $tmpl['event_key'],
                    'channel' => $tmpl['channel'],
                    'audience' => $tmpl['audience'],
                ],
                [
                    'subject' => $tmpl['subject'],
                    'body' => $tmpl['body'],
                    'is_active' => true,
                ]
            );
        }
    }
}
