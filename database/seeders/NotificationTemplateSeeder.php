<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // =========================================================================
            // 1. auth.welcome (buyer, partner)
            // =========================================================================
            [
                'event_key' => 'auth.welcome',
                'channel' => 'mail',
                'audience' => 'buyer',
                'subject' => 'Selamat Datang di Destinara, Solusi Eduwisata & Penelitian',
                'body' => "Halo {{user_name}},\n\nSelamat datang di platform Destinara. Akun institusi Anda telah berhasil dibuat. Anda kini dapat menjelajahi direktori tapak eduwisata, mengunduh proposal paket kegiatan, dan mengajukan pemesanan rombongan secara langsung.\n\nSalam hangat,\nTim Destinara",
            ],
            [
                'event_key' => 'auth.welcome',
                'channel' => 'whatsapp',
                'audience' => 'buyer',
                'subject' => null,
                'body' => "Halo *{{user_name}}*, selamat datang di *Destinara*! Akun institusi Anda telah aktif. Jelajahi destinasi eduwisata dan ajukan pemesanan rombongan di platform kami.\n\n- Tim Destinara",
            ],
            [
                'event_key' => 'auth.welcome',
                'channel' => 'database',
                'audience' => 'buyer',
                'subject' => 'Selamat Datang di Destinara',
                'body' => 'Selamat bergabung di Destinara! Akun institusi Anda telah aktif dan siap digunakan untuk pemesanan kegiatan.',
            ],
            [
                'event_key' => 'auth.welcome',
                'channel' => 'mail',
                'audience' => 'partner',
                'subject' => 'Selamat Bergabung sebagai Mitra Pengelola Tapak Destinara',
                'body' => "Halo {{user_name}},\n\nTerima kasih telah bergabung sebagai Mitra Pengelola Tapak di Destinara. Silakan lengkapi profil organisasi dan dokumen legalitas di portal mitra agar destinasi Anda dapat diverifikasi dan ditayangkan.\n\nSalam,\nTim Destinara",
            ],
            [
                'event_key' => 'auth.welcome',
                'channel' => 'whatsapp',
                'audience' => 'partner',
                'subject' => null,
                'body' => "Halo *{{user_name}}*, selamat bergabung sebagai Mitra Pengelola Tapak di *Destinara*. Silakan lengkapi profil organisasi dan dokumen legalitas di portal mitra.\n\n- Tim Destinara",
            ],
            [
                'event_key' => 'auth.welcome',
                'channel' => 'database',
                'audience' => 'partner',
                'subject' => 'Selamat Datang Mitra Destinara',
                'body' => 'Akun kemitraan Anda telah aktif. Silakan lengkapi profil pengelola dan dokumen legalitas untuk mulai menayangkan tapak.',
            ],

            // =========================================================================
            // 2. auth.verify_email & auth.reset_password (user: mail saja)
            // =========================================================================
            [
                'event_key' => 'auth.verify_email',
                'channel' => 'mail',
                'audience' => 'buyer',
                'subject' => 'Verifikasi Alamat Email Anda - Destinara',
                'body' => "Halo {{user_name}},\n\nSilakan klik tautan berikut untuk memverifikasi alamat email Anda:\n{{action_url}}\n\nTautan ini berlaku selama 60 menit.\n\nTim Destinara",
            ],
            [
                'event_key' => 'auth.reset_password',
                'channel' => 'mail',
                'audience' => 'buyer',
                'subject' => 'Atur Ulang Kata Sandi Akun Destinara',
                'body' => "Halo {{user_name}},\n\nKami menerima permintaan untuk mengatur ulang kata sandi akun Anda. Klik tautan berikut:\n{{action_url}}\n\nJika Anda tidak meminta perubahan ini, abaikan email ini.\n\nTim Destinara",
            ],

            // =========================================================================
            // 3. booking.created (buyer, admin)
            // =========================================================================
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
                'subject' => null,
                'body' => "Halo *{{pic_name}}*, pemesanan eduwisata Anda di *{{destination_name}}* telah dibuat dengan kode *{{booking_code}}*.\n\nTotal: *Rp {{total_amount}}*\nBatas Bayar: {{expires_at}}\n\nSelesaikan pembayaran di tautan berikut:\n{{payment_url}}\n\n- Destinara",
            ],
            [
                'event_key' => 'booking.created',
                'channel' => 'database',
                'audience' => 'buyer',
                'subject' => 'Pesanan Dibuat: {{booking_code}}',
                'body' => 'Pemesanan {{booking_code}} di {{destination_name}} berhasil dibuat. Silakan selesaikan pembayaran sebelum batas waktu.',
            ],
            [
                'event_key' => 'booking.created',
                'channel' => 'mail',
                'audience' => 'admin',
                'subject' => '[Booking Baru] {{booking_code}} - {{institution_name}}',
                'body' => "Pemesanan baru {{booking_code}} telah diajukan oleh {{institution_name}} untuk destinasi {{destination_name}}.\nJumlah: {{participant_count}} pax | Total: Rp {{total_amount}}.",
            ],
            [
                'event_key' => 'booking.created',
                'channel' => 'whatsapp',
                'audience' => 'admin',
                'subject' => null,
                'body' => '[Admin] Pemesanan baru *{{booking_code}}* dibuat oleh *{{institution_name}}* di *{{destination_name}}* ({{participant_count}} pax, Rp {{total_amount}}).',
            ],
            [
                'event_key' => 'booking.created',
                'channel' => 'database',
                'audience' => 'admin',
                'subject' => 'Booking Baru Masuk: {{booking_code}}',
                'body' => 'Booking baru {{booking_code}} dibuat oleh {{institution_name}} untuk {{destination_name}} (Rp {{total_amount}}).',
            ],

            // =========================================================================
            // 4. payment.reminder (buyer)
            // =========================================================================
            [
                'event_key' => 'payment.reminder',
                'channel' => 'mail',
                'audience' => 'buyer',
                'subject' => 'Pengingat Batas Waktu Pembayaran: {{booking_code}}',
                'body' => "Halo {{pic_name}},\n\nPembayaran untuk pemesanan {{booking_code}} di {{destination_name}} akan segera berakhir pada {{expires_at}} (kurang dari {{hours_left}} jam lagi).\n\nSegera selesaikan pembayaran agar alokasi slot tidak dibatalkan otomatis:\n{{payment_url}}\n\nTim Destinara",
            ],
            [
                'event_key' => 'payment.reminder',
                'channel' => 'whatsapp',
                'audience' => 'buyer',
                'subject' => null,
                'body' => "Halo *{{pic_name}}*, pembayaran pemesanan *{{booking_code}}* di *{{destination_name}}* akan kedaluwarsa pada {{expires_at}} (kurang dari {{hours_left}} jam lagi).\n\nSegera selesaikan pembayaran di tautan ini:\n{{payment_url}}\n\n- Destinara",
            ],
            [
                'event_key' => 'payment.reminder',
                'channel' => 'database',
                'audience' => 'buyer',
                'subject' => 'Segera Bayar: {{booking_code}}',
                'body' => 'Batas waktu pembayaran pesanan {{booking_code}} tersisa kurang dari {{hours_left}} jam.',
            ],

            // =========================================================================
            // 5. booking.expired (buyer)
            // =========================================================================
            [
                'event_key' => 'booking.expired',
                'channel' => 'mail',
                'audience' => 'buyer',
                'subject' => 'Pemesanan Kedaluwarsa: {{booking_code}}',
                'body' => "Halo {{pic_name}},\n\nPemesanan {{booking_code}} di {{destination_name}} telah kedaluwarsa karena pembayaran belum diselesaikan hingga batas waktu. Alokasi slot kunjungan telah dilepaskan kembali.\n\nAnda dapat membuat pemesanan ulang kapan saja di portal Destinara.\n\nTim Destinara",
            ],
            [
                'event_key' => 'booking.expired',
                'channel' => 'whatsapp',
                'audience' => 'buyer',
                'subject' => null,
                'body' => "Halo *{{pic_name}}*, pemesanan *{{booking_code}}* di *{{destination_name}}* telah kedaluwarsa karena pembayaran melewati batas waktu. Slot telah dilepas kembali.\n\n- Destinara",
            ],
            [
                'event_key' => 'booking.expired',
                'channel' => 'database',
                'audience' => 'buyer',
                'subject' => 'Pesanan Kedaluwarsa: {{booking_code}}',
                'body' => 'Pemesanan {{booking_code}} telah dibatalkan otomatis oleh sistem karena batas waktu pembayaran habis.',
            ],

            // =========================================================================
            // 6. payment.paid (buyer, partner, admin - FULL 9 COMBINATIONS)
            // =========================================================================
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
                'audience' => 'buyer',
                'subject' => null,
                'body' => "Halo *{{pic_name}}*, pembayaran pemesanan *{{booking_code}}* sebesar *Rp {{total_amount}}* telah kami terima. Status saat ini menunggu konfirmasi mitra pengelola (maksimal 48 jam).\n\n- Destinara",
            ],
            [
                'event_key' => 'payment.paid',
                'channel' => 'database',
                'audience' => 'buyer',
                'subject' => 'Pembayaran Lunas: {{booking_code}}',
                'body' => 'Pembayaran pemesanan {{booking_code}} sebesar Rp {{total_amount}} telah diterima. Menunggu konfirmasi mitra.',
            ],
            [
                'event_key' => 'payment.paid',
                'channel' => 'mail',
                'audience' => 'partner',
                'subject' => 'Pemesanan Terbayar: {{booking_code}} Menunggu Konfirmasi Mitra',
                'body' => "Halo Mitra {{organization_name}},\n\nPemesanan {{booking_code}} untuk rombongan {{institution_name}} ({{participant_count}} orang) pada tanggal {{planned_date_start}} telah lunas dibayar sebesar Rp {{total_amount}}.\n\nHarap lakukan konfirmasi ketersediaan sebelum {{confirm_deadline}} melalui tautan:\n{{partner_booking_url}}\n\nTim Destinara",
            ],
            [
                'event_key' => 'payment.paid',
                'channel' => 'whatsapp',
                'audience' => 'partner',
                'subject' => null,
                'body' => "Halo *Mitra {{organization_name}}*, ada pemesanan terbayar *{{booking_code}}* dari *{{institution_name}}* ({{participant_count}} pax, tgl {{planned_date_start}}).\n\nHarap konfirmasi sebelum {{confirm_deadline}} di:\n{{partner_booking_url}}\n\n- Destinara",
            ],
            [
                'event_key' => 'payment.paid',
                'channel' => 'database',
                'audience' => 'partner',
                'subject' => 'Pesanan Terbayar: {{booking_code}}',
                'body' => 'Pemesanan {{booking_code}} oleh {{institution_name}} telah lunas dibayar. Silakan konfirmasi ketersediaan rombongan.',
            ],
            [
                'event_key' => 'payment.paid',
                'channel' => 'mail',
                'audience' => 'admin',
                'subject' => '[Alert Pembayaran] {{booking_code}} Lunas via {{payment_gateway}}',
                'body' => "Pembayaran untuk booking {{booking_code}} telah berhasil diterima.\nInstitusi: {{institution_name}}\nDestinasi: {{destination_name}}\nTotal: Rp {{total_amount}}\nGateway: {{payment_gateway}}.",
            ],
            [
                'event_key' => 'payment.paid',
                'channel' => 'whatsapp',
                'audience' => 'admin',
                'subject' => null,
                'body' => '[Admin] Pembayaran *{{booking_code}}* ({{institution_name}}) sebesar *Rp {{total_amount}}* telah lunas via {{payment_gateway}}.',
            ],
            [
                'event_key' => 'payment.paid',
                'channel' => 'database',
                'audience' => 'admin',
                'subject' => 'Pembayaran Lunas: {{booking_code}}',
                'body' => 'Pembayaran booking {{booking_code}} sebesar Rp {{total_amount}} telah diterima via {{payment_gateway}}.',
            ],

            // =========================================================================
            // 7. partner.confirm_reminder (partner, admin)
            // =========================================================================
            [
                'event_key' => 'partner.confirm_reminder',
                'channel' => 'mail',
                'audience' => 'partner',
                'subject' => 'URGENT: Konfirmasi Kunjungan {{booking_code}} Menjelang Batas Waktu',
                'body' => "Halo Mitra {{organization_name}},\n\nPemesanan terbayar {{booking_code}} dari {{institution_name}} belum Anda konfirmasi dan akan kedaluwarsa dalam {{hours_left}} jam (batas: {{deadline}}).\n\nHarap segera konfirmasi di:\n{{partner_url}}\n\nTim Destinara",
            ],
            [
                'event_key' => 'partner.confirm_reminder',
                'channel' => 'whatsapp',
                'audience' => 'partner',
                'subject' => null,
                'body' => "*PERINGATAN SLA*: Konfirmasi pemesanan *{{booking_code}}* tersisa kurang dari {{hours_left}} jam. Segera konfirmasi agar tidak dibatalkan otomatis:\n{{partner_url}}\n\n- Destinara",
            ],
            [
                'event_key' => 'partner.confirm_reminder',
                'channel' => 'database',
                'audience' => 'partner',
                'subject' => 'Batas Konfirmasi: {{booking_code}}',
                'body' => 'Segera konfirmasi pemesanan {{booking_code}}. Batas waktu SLA tersisa {{hours_left}} jam.',
            ],
            [
                'event_key' => 'partner.confirm_reminder',
                'channel' => 'mail',
                'audience' => 'admin',
                'subject' => '[SLA Alert] Mitra Belum Konfirmasi: {{booking_code}}',
                'body' => 'Mitra {{organization_name}} belum mengonfirmasi booking terbayar {{booking_code}}. Waktu SLA tersisa {{hours_left}} jam.',
            ],
            [
                'event_key' => 'partner.confirm_reminder',
                'channel' => 'whatsapp',
                'audience' => 'admin',
                'subject' => null,
                'body' => '[Admin SLA] Mitra *{{organization_name}}* belum konfirmasi booking *{{booking_code}}*. Sisa waktu: {{hours_left}} jam.',
            ],
            [
                'event_key' => 'partner.confirm_reminder',
                'channel' => 'database',
                'audience' => 'admin',
                'subject' => 'Peringatan SLA Mitra: {{booking_code}}',
                'body' => 'Mitra {{organization_name}} belum mengonfirmasi booking {{booking_code}}. Waktu SLA tersisa {{hours_left}} jam.',
            ],

            // =========================================================================
            // 8. booking.confirmed (buyer, partner, admin)
            // =========================================================================
            [
                'event_key' => 'booking.confirmed',
                'channel' => 'mail',
                'audience' => 'buyer',
                'subject' => 'Pemesanan Dikonfirmasi: {{booking_code}} di {{destination_name}}',
                'body' => "Halo {{pic_name}},\n\nKabar baik! Mitra pengelola telah mengonfirmasi kunjungan rombongan {{institution_name}} ke {{destination_name}} pada {{planned_date_start}}.\n\nSurat Konfirmasi dan E-Voucher resmi telah diterbitkan dan dapat diunduh di portal Anda:\n{{booking_url}}\n\nSampai jumpa di lokasi,\nTim Destinara",
            ],
            [
                'event_key' => 'booking.confirmed',
                'channel' => 'whatsapp',
                'audience' => 'buyer',
                'subject' => null,
                'body' => "Halo *{{pic_name}}*, kunjungan Anda di *{{destination_name}}* (*{{booking_code}}*) telah *DIKONFIRMASI* oleh pengelola. Surat konfirmasi & e-voucher siap diunduh di:\n{{booking_url}}\n\n- Destinara",
            ],
            [
                'event_key' => 'booking.confirmed',
                'channel' => 'database',
                'audience' => 'buyer',
                'subject' => 'Pesanan Dikonfirmasi: {{booking_code}}',
                'body' => 'Pemesanan {{booking_code}} di {{destination_name}} telah dikonfirmasi oleh pengelola. E-Voucher dan Surat Konfirmasi siap diunduh.',
            ],
            [
                'event_key' => 'booking.confirmed',
                'channel' => 'mail',
                'audience' => 'partner',
                'subject' => 'Konfirmasi Kunjungan Berhasil: {{booking_code}}',
                'body' => "Halo Mitra {{organization_name}},\n\nKonfirmasi kunjungan {{booking_code}} (rombongan {{institution_name}}) telah tercatat. Dokumen resmi telah diterbitkan.",
            ],
            [
                'event_key' => 'booking.confirmed',
                'channel' => 'whatsapp',
                'audience' => 'partner',
                'subject' => null,
                'body' => "Mitra *{{organization_name}}*, kunjungan rombongan *{{institution_name}}* (*{{booking_code}}*) berhasil dikonfirmasi.\n\n- Destinara",
            ],
            [
                'event_key' => 'booking.confirmed',
                'channel' => 'database',
                'audience' => 'partner',
                'subject' => 'Kunjungan Dikonfirmasi: {{booking_code}}',
                'body' => 'Pemesanan {{booking_code}} telah berhasil dikonfirmasi. Jadwal telah dicatat pada kalender tapak.',
            ],
            [
                'event_key' => 'booking.confirmed',
                'channel' => 'mail',
                'audience' => 'admin',
                'subject' => '[Confirmed] {{booking_code}} oleh Mitra {{organization_name}}',
                'body' => 'Booking {{booking_code}} telah dikonfirmasi oleh mitra {{organization_name}}. Dokumen resmi telah diterbitkan.',
            ],
            [
                'event_key' => 'booking.confirmed',
                'channel' => 'whatsapp',
                'audience' => 'admin',
                'subject' => null,
                'body' => '[Admin] Booking *{{booking_code}}* telah dikonfirmasi oleh mitra *{{organization_name}}*.',
            ],
            [
                'event_key' => 'booking.confirmed',
                'channel' => 'database',
                'audience' => 'admin',
                'subject' => 'Booking Dikonfirmasi: {{booking_code}}',
                'body' => 'Booking {{booking_code}} telah dikonfirmasi oleh mitra {{organization_name}}.',
            ],

            // =========================================================================
            // 9. booking.rejected (buyer, partner, admin)
            // =========================================================================
            [
                'event_key' => 'booking.rejected',
                'channel' => 'mail',
                'audience' => 'buyer',
                'subject' => 'Pemberitahuan Penolakan Pemesanan: {{booking_code}}',
                'body' => "Halo {{pic_name}},\n\nMohon maaf, pemesanan {{booking_code}} di {{destination_name}} tidak dapat diterima oleh mitra pengelola dengan alasan:\n{{reason}}\n\nDana yang telah dibayarkan akan dikembalikan penuh (100%) sesuai ketentuan platform. Proses pengembalian dana telah dimasukkan ke dalam antrean sistem.\n\nTim Destinara",
            ],
            [
                'event_key' => 'booking.rejected',
                'channel' => 'whatsapp',
                'audience' => 'buyer',
                'subject' => null,
                'body' => "Halo *{{pic_name}}*, pemesanan *{{booking_code}}* di {{destination_name}} tidak dapat disetujui mitra pengelola (Alasan: {{reason}}). Dana Anda akan di-refund 100%.\n\n- Destinara",
            ],
            [
                'event_key' => 'booking.rejected',
                'channel' => 'database',
                'audience' => 'buyer',
                'subject' => 'Pesanan Ditolak: {{booking_code}}',
                'body' => 'Pemesanan {{booking_code}} ditolak oleh mitra pengelola. Refund 100% diproses.',
            ],
            [
                'event_key' => 'booking.rejected',
                'channel' => 'mail',
                'audience' => 'partner',
                'subject' => 'Penolakan Pesanan {{booking_code}} Berhasil Diproses',
                'body' => "Halo Mitra {{organization_name}},\n\nPenolakan pemesanan {{booking_code}} telah dicatat. Refund 100% diteruskan ke pembeli.",
            ],
            [
                'event_key' => 'booking.rejected',
                'channel' => 'whatsapp',
                'audience' => 'partner',
                'subject' => null,
                'body' => "Mitra *{{organization_name}}*, penolakan booking *{{booking_code}}* telah dicatat di sistem.\n\n- Destinara",
            ],
            [
                'event_key' => 'booking.rejected',
                'channel' => 'database',
                'audience' => 'partner',
                'subject' => 'Pesanan Ditolak: {{booking_code}}',
                'body' => 'Anda telah menolak pemesanan {{booking_code}}. Slot kembali dibuka.',
            ],
            [
                'event_key' => 'booking.rejected',
                'channel' => 'mail',
                'audience' => 'admin',
                'subject' => '[Alert Penolakan] {{booking_code}} Ditolak oleh Mitra',
                'body' => 'Pemesanan {{booking_code}} telah ditolak oleh mitra {{organization_name}}. Alasan: {{reason}}. Refund 100% disiapkan.',
            ],
            [
                'event_key' => 'booking.rejected',
                'channel' => 'whatsapp',
                'audience' => 'admin',
                'subject' => null,
                'body' => '[Admin] Booking *{{booking_code}}* DITOLAK oleh mitra *{{organization_name}}*. Alasan: {{reason}}.',
            ],
            [
                'event_key' => 'booking.rejected',
                'channel' => 'database',
                'audience' => 'admin',
                'subject' => 'Booking Ditolak Mitra: {{booking_code}}',
                'body' => 'Pemesanan {{booking_code}} ditolak oleh mitra {{organization_name}} (Alasan: {{reason}}). Refund dipersiapkan.',
            ],

            // =========================================================================
            // 10. booking.auto_cancelled (buyer, partner, admin)
            // =========================================================================
            [
                'event_key' => 'booking.auto_cancelled',
                'channel' => 'mail',
                'audience' => 'buyer',
                'subject' => 'Pemesanan Dibatalkan Otomatis (SLA Mitra Berakhir): {{booking_code}}',
                'body' => "Halo {{pic_name}},\n\nPemesanan {{booking_code}} di {{destination_name}} dibatalkan otomatis karena mitra pengelola tidak memberikan konfirmasi hingga batas waktu SLA. Dana Anda akan dikembalikan 100% penuh.\n\nTim Destinara",
            ],
            [
                'event_key' => 'booking.auto_cancelled',
                'channel' => 'whatsapp',
                'audience' => 'buyer',
                'subject' => null,
                'body' => "Halo *{{pic_name}}*, pemesanan *{{booking_code}}* dibatalkan otomatis karena konfirmasi mitra melebihi batas SLA. Refund 100% segera diproses.\n\n- Destinara",
            ],
            [
                'event_key' => 'booking.auto_cancelled',
                'channel' => 'database',
                'audience' => 'buyer',
                'subject' => 'Pesanan Batal Otomatis: {{booking_code}}',
                'body' => 'Pemesanan {{booking_code}} dibatalkan otomatis oleh sistem karena SLA konfirmasi mitra terlewati. Refund 100% disiapkan.',
            ],
            [
                'event_key' => 'booking.auto_cancelled',
                'channel' => 'mail',
                'audience' => 'partner',
                'subject' => 'Pemberitahuan Pembatalan Otomatis: {{booking_code}}',
                'body' => "Halo Mitra {{organization_name}},\n\nPemesanan {{booking_code}} telah dibatalkan otomatis karena melewati batas waktu konfirmasi 48 jam.",
            ],
            [
                'event_key' => 'booking.auto_cancelled',
                'channel' => 'whatsapp',
                'audience' => 'partner',
                'subject' => null,
                'body' => "Mitra *{{organization_name}}*, booking *{{booking_code}}* telah dibatalkan otomatis karena melebihi SLA konfirmasi 48 jam.\n\n- Destinara",
            ],
            [
                'event_key' => 'booking.auto_cancelled',
                'channel' => 'database',
                'audience' => 'partner',
                'subject' => 'Booking Batal Otomatis (SLA): {{booking_code}}',
                'body' => 'Pemesanan {{booking_code}} dibatalkan otomatis karena batas konfirmasi 48 jam terlewati.',
            ],
            [
                'event_key' => 'booking.auto_cancelled',
                'channel' => 'mail',
                'audience' => 'admin',
                'subject' => '[SLA Breach] Booking {{booking_code}} Batal Otomatis',
                'body' => 'Booking {{booking_code}} dibatalkan otomatis oleh scheduler karena mitra {{organization_name}} tidak merespons dalam 48 jam.',
            ],
            [
                'event_key' => 'booking.auto_cancelled',
                'channel' => 'whatsapp',
                'audience' => 'admin',
                'subject' => null,
                'body' => '[Admin] Booking *{{booking_code}}* otomatis BATAL (SLA breach mitra *{{organization_name}}*).',
            ],
            [
                'event_key' => 'booking.auto_cancelled',
                'channel' => 'database',
                'audience' => 'admin',
                'subject' => 'Booking Batal Otomatis (SLA): {{booking_code}}',
                'body' => 'Booking {{booking_code}} dibatalkan otomatis oleh sistem karena melewati batas SLA konfirmasi.',
            ],

            // =========================================================================
            // 11. booking.cancelled (buyer, partner, admin)
            // =========================================================================
            [
                'event_key' => 'booking.cancelled',
                'channel' => 'mail',
                'audience' => 'buyer',
                'subject' => 'Pemesanan Dibatalkan: {{booking_code}}',
                'body' => "Halo {{pic_name}},\n\nPemesanan {{booking_code}} di {{destination_name}} telah dibatalkan (Inisiator: {{cancellation_source}}).\nAlasan: {{reason}}.\n\nTim Destinara",
            ],
            [
                'event_key' => 'booking.cancelled',
                'channel' => 'whatsapp',
                'audience' => 'buyer',
                'subject' => null,
                'body' => "Halo *{{pic_name}}*, pemesanan *{{booking_code}}* di {{destination_name}} telah dibatalkan. Alasan: {{reason}}.\n\n- Destinara",
            ],
            [
                'event_key' => 'booking.cancelled',
                'channel' => 'database',
                'audience' => 'buyer',
                'subject' => 'Pesanan Dibatalkan: {{booking_code}}',
                'body' => 'Pemesanan {{booking_code}} telah dibatalkan. Alasan: {{reason}}.',
            ],
            [
                'event_key' => 'booking.cancelled',
                'channel' => 'mail',
                'audience' => 'partner',
                'subject' => 'Pemberitahuan Pembatalan Pemesanan: {{booking_code}}',
                'body' => "Halo Mitra {{organization_name}},\n\nPemesanan {{booking_code}} untuk rombongan {{institution_name}} telah dibatalkan. Alokasi slot kunjungan telah dikembalikan ke kalender ketersediaan Anda.\n\nTim Destinara",
            ],
            [
                'event_key' => 'booking.cancelled',
                'channel' => 'whatsapp',
                'audience' => 'partner',
                'subject' => null,
                'body' => "Mitra *{{organization_name}}*, pemesanan *{{booking_code}}* ({{institution_name}}) telah dibatalkan. Slot kunjungan telah dikembalikan.\n\n- Destinara",
            ],
            [
                'event_key' => 'booking.cancelled',
                'channel' => 'database',
                'audience' => 'partner',
                'subject' => 'Pesanan Dibatalkan: {{booking_code}}',
                'body' => 'Pemesanan {{booking_code}} telah dibatalkan. Slot kunjungan dilepaskan kembali.',
            ],
            [
                'event_key' => 'booking.cancelled',
                'channel' => 'mail',
                'audience' => 'admin',
                'subject' => '[Pembatalan Booking] {{booking_code}}',
                'body' => 'Pemesanan {{booking_code}} telah dibatalkan oleh {{cancellation_source}} dengan alasan: {{reason}}.',
            ],
            [
                'event_key' => 'booking.cancelled',
                'channel' => 'whatsapp',
                'audience' => 'admin',
                'subject' => null,
                'body' => '[Admin] Booking *{{booking_code}}* dibatalkan (Inisiator: {{cancellation_source}}, Alasan: {{reason}}).',
            ],
            [
                'event_key' => 'booking.cancelled',
                'channel' => 'database',
                'audience' => 'admin',
                'subject' => 'Booking Dibatalkan: {{booking_code}}',
                'body' => 'Pemesanan {{booking_code}} telah dibatalkan oleh {{cancellation_source}}.',
            ],

            // =========================================================================
            // 12. booking.rescheduled (buyer, partner, admin)
            // =========================================================================
            [
                'event_key' => 'booking.rescheduled',
                'channel' => 'mail',
                'audience' => 'buyer',
                'subject' => 'Jadwal Baru Diterbitkan: {{booking_code}} di {{destination_name}}',
                'body' => "Halo {{pic_name}},\n\nJadwal kunjungan pemesanan {{booking_code}} telah berhasil diperbarui ke tanggal {{new_start_date}} s.d. {{new_end_date}}.\n\nSurat Konfirmasi dan E-Voucher baru telah diterbitkan (dokumen lama telah divoid).\n\nTim Destinara",
            ],
            [
                'event_key' => 'booking.rescheduled',
                'channel' => 'whatsapp',
                'audience' => 'buyer',
                'subject' => null,
                'body' => "Halo *{{pic_name}}*, jadwal kunjungan *{{booking_code}}* di {{destination_name}} telah diperbarui ke tanggal *{{new_start_date}} s.d. {{new_end_date}}*. Dokumen baru telah diterbitkan.\n\n- Destinara",
            ],
            [
                'event_key' => 'booking.rescheduled',
                'channel' => 'database',
                'audience' => 'buyer',
                'subject' => 'Jadwal Kunjungan Diperbarui: {{booking_code}}',
                'body' => 'Jadwal kunjungan {{booking_code}} telah dialihkan ke tanggal {{new_start_date}} s.d. {{new_end_date}}.',
            ],
            [
                'event_key' => 'booking.rescheduled',
                'channel' => 'mail',
                'audience' => 'partner',
                'subject' => 'Penjadwalan Ulang Pemesanan: {{booking_code}}',
                'body' => "Halo Mitra {{organization_name}},\n\nJadwal kunjungan {{booking_code}} untuk rombongan {{institution_name}} telah diperbarui oleh Admin ke tanggal {{new_start_date}} s.d. {{new_end_date}}.",
            ],
            [
                'event_key' => 'booking.rescheduled',
                'channel' => 'whatsapp',
                'audience' => 'partner',
                'subject' => null,
                'body' => "Mitra *{{organization_name}}*, jadwal kunjungan rombongan *{{institution_name}}* (*{{booking_code}}*) telah dialihkan ke *{{new_start_date}} s.d. {{new_end_date}}*.\n\n- Destinara",
            ],
            [
                'event_key' => 'booking.rescheduled',
                'channel' => 'database',
                'audience' => 'partner',
                'subject' => 'Penjadwalan Ulang: {{booking_code}}',
                'body' => 'Jadwal pemesanan {{booking_code}} telah diubah ke {{new_start_date}} s.d. {{new_end_date}}.',
            ],
            [
                'event_key' => 'booking.rescheduled',
                'channel' => 'mail',
                'audience' => 'admin',
                'subject' => '[Reschedule] Booking {{booking_code}} Berhasil Dijadwalkan Ulang',
                'body' => 'Booking {{booking_code}} telah dialihkan ke tanggal baru {{new_start_date}} s.d. {{new_end_date}}.',
            ],
            [
                'event_key' => 'booking.rescheduled',
                'channel' => 'whatsapp',
                'audience' => 'admin',
                'subject' => null,
                'body' => '[Admin] Booking *{{booking_code}}* dijadwalkan ulang ke {{new_start_date}} s.d. {{new_end_date}}.',
            ],
            [
                'event_key' => 'booking.rescheduled',
                'channel' => 'database',
                'audience' => 'admin',
                'subject' => 'Reschedule Booking: {{booking_code}}',
                'body' => 'Booking {{booking_code}} dijadwalkan ulang ke {{new_start_date}} s.d. {{new_end_date}}.',
            ],

            // =========================================================================
            // 13. visit.reminder (buyer, partner)
            // =========================================================================
            [
                'event_key' => 'visit.reminder',
                'channel' => 'mail',
                'audience' => 'buyer',
                'subject' => 'Pengingat Kunjungan H-{{days_left}}: {{booking_code}} di {{destination_name}}',
                'body' => "Halo {{pic_name}},\n\nKunjungan rombongan {{institution_name}} ke {{destination_name}} tinggal {{days_left}} hari lagi (Tanggal: {{planned_date_start}}).\n\nPastikan untuk membawa E-Voucher dan Surat Konfirmasi resmi yang dapat diakses di portal Anda.\n\nTim Destinara",
            ],
            [
                'event_key' => 'visit.reminder',
                'channel' => 'whatsapp',
                'audience' => 'buyer',
                'subject' => null,
                'body' => "Halo *{{pic_name}}*, kunjungan Anda di *{{destination_name}}* (*{{booking_code}}*) tinggal *{{days_left}} hari lagi* ({{planned_date_start}}). Siapkan e-voucher Anda untuk proses check-in di lokasi!\n\n- Destinara",
            ],
            [
                'event_key' => 'visit.reminder',
                'channel' => 'database',
                'audience' => 'buyer',
                'subject' => 'Pengingat Kunjungan: {{booking_code}}',
                'body' => 'Kunjungan rombongan Anda ke {{destination_name}} tinggal {{days_left}} hari lagi.',
            ],
            [
                'event_key' => 'visit.reminder',
                'channel' => 'mail',
                'audience' => 'partner',
                'subject' => 'Pengingat Kedatangan Tamu H-{{days_left}}: {{booking_code}}',
                'body' => "Halo Mitra {{organization_name}},\n\nRombongan {{institution_name}} ({{participant_count}} pax) dijadwalkan tiba di {{destination_name}} dalam {{days_left}} hari (Tanggal: {{planned_date_start}}).\n\nMohon persiapkan tim pemandu dan fasilitas tapak.\n\nTim Destinara",
            ],
            [
                'event_key' => 'visit.reminder',
                'channel' => 'whatsapp',
                'audience' => 'partner',
                'subject' => null,
                'body' => "Mitra *{{organization_name}}*, rombongan *{{institution_name}}* ({{participant_count}} pax, kode *{{booking_code}}*) akan tiba dalam *{{days_left}} hari* ({{planned_date_start}}).\n\n- Destinara",
            ],
            [
                'event_key' => 'visit.reminder',
                'channel' => 'database',
                'audience' => 'partner',
                'subject' => 'Pengingat Kedatangan Tamu: {{booking_code}}',
                'body' => 'Rombongan {{institution_name}} ({{participant_count}} pax) dijadwalkan tiba dalam {{days_left}} hari.',
            ],

            // =========================================================================
            // 14. booking.checked_in (buyer, partner, admin)
            // =========================================================================
            [
                'event_key' => 'booking.checked_in',
                'channel' => 'mail',
                'audience' => 'buyer',
                'subject' => 'Check-in Berhasil: Selamat Menikmati Eduwisata di {{destination_name}}',
                'body' => "Halo {{pic_name}},\n\nCheck-in rombongan {{institution_name}} di {{destination_name}} telah berhasil divalidasi oleh petugas pengelola tapak.\n\nSelamat beraktivitas!\n\nTim Destinara",
            ],
            [
                'event_key' => 'booking.checked_in',
                'channel' => 'whatsapp',
                'audience' => 'buyer',
                'subject' => null,
                'body' => "Halo *{{pic_name}}*, check-in kunjungan di *{{destination_name}}* telah berhasil diverifikasi oleh petugas tapak. Selamat menikmati kegiatan eduwisata!\n\n- Destinara",
            ],
            [
                'event_key' => 'booking.checked_in',
                'channel' => 'database',
                'audience' => 'buyer',
                'subject' => 'Check-in Berhasil: {{booking_code}}',
                'body' => 'Check-in rombongan Anda di {{destination_name}} telah berhasil dicatat.',
            ],
            [
                'event_key' => 'booking.checked_in',
                'channel' => 'mail',
                'audience' => 'partner',
                'subject' => 'Check-in Tamu Berhasil: {{booking_code}}',
                'body' => 'Check-in untuk pemesanan {{booking_code}} (rombongan {{institution_name}}) telah berhasil dipindai oleh petugas tapak.',
            ],
            [
                'event_key' => 'booking.checked_in',
                'channel' => 'whatsapp',
                'audience' => 'partner',
                'subject' => null,
                'body' => "Mitra *{{organization_name}}*, rombongan *{{institution_name}}* (*{{booking_code}}*) telah berhasil check-in di tapak.\n\n- Destinara",
            ],
            [
                'event_key' => 'booking.checked_in',
                'channel' => 'database',
                'audience' => 'partner',
                'subject' => 'Check-in Tercatat: {{booking_code}}',
                'body' => 'Check-in rombongan {{institution_name}} telah berhasil divalidasi.',
            ],
            [
                'event_key' => 'booking.checked_in',
                'channel' => 'mail',
                'audience' => 'admin',
                'subject' => '[Check-In] {{booking_code}} di {{destination_name}}',
                'body' => 'Rombongan {{institution_name}} telah melakukan check-in di tapak {{destination_name}} untuk booking {{booking_code}}.',
            ],
            [
                'event_key' => 'booking.checked_in',
                'channel' => 'whatsapp',
                'audience' => 'admin',
                'subject' => null,
                'body' => '[Admin] Check-in tercatat untuk *{{booking_code}}* di *{{destination_name}}*.',
            ],
            [
                'event_key' => 'booking.checked_in',
                'channel' => 'database',
                'audience' => 'admin',
                'subject' => 'Check-In: {{booking_code}}',
                'body' => 'Check-in booking {{booking_code}} di {{destination_name}} berhasil dilakukan.',
            ],

            // =========================================================================
            // 15. booking.completed (buyer, partner, admin)
            // =========================================================================
            [
                'event_key' => 'booking.completed',
                'channel' => 'mail',
                'audience' => 'buyer',
                'subject' => 'Kunjungan Selesai: Bagikan Pengalaman Anda di {{destination_name}}',
                'body' => "Halo {{pic_name}},\n\nTerima kasih telah melaksanakan kegiatan eduwisata di {{destination_name}} bersama Destinara.\n\nBantu pengunjung lain dan dukung pengelola tapak dengan memberikan ulasan serta rating di tautan berikut:\n{{review_url}}\n\nSalam hangat,\nTim Destinara",
            ],
            [
                'event_key' => 'booking.completed',
                'channel' => 'whatsapp',
                'audience' => 'buyer',
                'subject' => null,
                'body' => "Halo *{{pic_name}}*, terima kasih telah berkunjung ke *{{destination_name}}*. Mohon luangkan 1 menit untuk memberikan ulasan dan masukan di tautan ini:\n{{review_url}}\n\n- Destinara",
            ],
            [
                'event_key' => 'booking.completed',
                'channel' => 'database',
                'audience' => 'buyer',
                'subject' => 'Kunjungan Selesai: Berikan Ulasan Anda',
                'body' => 'Kunjungan di {{destination_name}} telah selesai. Bagikan pengalaman Anda dengan memberikan ulasan.',
            ],
            [
                'event_key' => 'booking.completed',
                'channel' => 'mail',
                'audience' => 'partner',
                'subject' => 'Kunjungan Selesai: {{booking_code}}',
                'body' => "Halo Mitra {{organization_name}},\n\nKunjungan rombongan {{institution_name}} telah selesai. Pendapatan akan diproses sesuai jadwal payout.",
            ],
            [
                'event_key' => 'booking.completed',
                'channel' => 'whatsapp',
                'audience' => 'partner',
                'subject' => null,
                'body' => "Mitra *{{organization_name}}*, kegiatan rombongan *{{institution_name}}* (*{{booking_code}}*) telah berstatus selesai. Payout akan diproses sesuai jadwal.\n\n- Destinara",
            ],
            [
                'event_key' => 'booking.completed',
                'channel' => 'database',
                'audience' => 'partner',
                'subject' => 'Kunjungan Selesai: {{booking_code}}',
                'body' => 'Pemesanan {{booking_code}} telah selesai. Pendapatan dijadwalkan untuk pencairan.',
            ],
            [
                'event_key' => 'booking.completed',
                'channel' => 'mail',
                'audience' => 'admin',
                'subject' => '[Selesai] Booking {{booking_code}} di {{destination_name}}',
                'body' => 'Pemesanan {{booking_code}} telah selesai dilaksanakan. Payout siap digenerate setelah delay.',
            ],
            [
                'event_key' => 'booking.completed',
                'channel' => 'whatsapp',
                'audience' => 'admin',
                'subject' => null,
                'body' => '[Admin] Booking *{{booking_code}}* berstatus SELESAI.',
            ],
            [
                'event_key' => 'booking.completed',
                'channel' => 'database',
                'audience' => 'admin',
                'subject' => 'Booking Selesai: {{booking_code}}',
                'body' => 'Booking {{booking_code}} telah selesai dilaksanakan.',
            ],

            // =========================================================================
            // 16. refund.created & refund.completed (buyer, admin)
            // =========================================================================
            [
                'event_key' => 'refund.created',
                'channel' => 'mail',
                'audience' => 'buyer',
                'subject' => 'Pengajuan Pengembalian Dana: {{booking_code}}',
                'body' => "Halo {{pic_name}},\n\nPengajuan refund untuk pemesanan {{booking_code}} sebesar Rp {{refund_amount}} ({{refund_percent}}%) telah kami terima dan sedang diproses tim keuangan.\n\nTim Destinara",
            ],
            [
                'event_key' => 'refund.created',
                'channel' => 'whatsapp',
                'audience' => 'buyer',
                'subject' => null,
                'body' => "Halo *{{pic_name}}*, pengajuan refund *{{booking_code}}* sebesar *Rp {{refund_amount}}* telah masuk antrean proses tim keuangan.\n\n- Destinara",
            ],
            [
                'event_key' => 'refund.created',
                'channel' => 'database',
                'audience' => 'buyer',
                'subject' => 'Pengajuan Refund Diproses: {{booking_code}}',
                'body' => 'Refund pemesanan {{booking_code}} sebesar Rp {{refund_amount}} sedang diproses.',
            ],
            [
                'event_key' => 'refund.created',
                'channel' => 'mail',
                'audience' => 'admin',
                'subject' => '[Refund Masuk] {{booking_code}} Sebesar Rp {{refund_amount}}',
                'body' => 'Pengajuan refund baru untuk booking {{booking_code}} sebesar Rp {{refund_amount}} perlu diproses transfer.',
            ],
            [
                'event_key' => 'refund.created',
                'channel' => 'whatsapp',
                'audience' => 'admin',
                'subject' => null,
                'body' => '[Admin] Refund baru *{{booking_code}}* sebesar *Rp {{refund_amount}}* perlu diproses.',
            ],
            [
                'event_key' => 'refund.created',
                'channel' => 'database',
                'audience' => 'admin',
                'subject' => 'Pengajuan Refund: {{booking_code}}',
                'body' => 'Pengajuan refund {{booking_code}} sebesar Rp {{refund_amount}} masuk antrean.',
            ],
            [
                'event_key' => 'refund.completed',
                'channel' => 'mail',
                'audience' => 'buyer',
                'subject' => 'Pengembalian Dana Berhasil Ditransfer: {{booking_code}}',
                'body' => "Halo {{pic_name}},\n\nDana refund sebesar Rp {{refund_amount}} untuk pemesanan {{booking_code}} telah berhasil ditransfer ke rekening {{bank_name}} ({{account_number}} a.n {{account_holder}}).\n\nTim Destinara",
            ],
            [
                'event_key' => 'refund.completed',
                'channel' => 'whatsapp',
                'audience' => 'buyer',
                'subject' => null,
                'body' => "Halo *{{pic_name}}*, dana refund *{{booking_code}}* sebesar *Rp {{refund_amount}}* telah berhasil ditransfer ke rekening Anda.\n\n- Destinara",
            ],
            [
                'event_key' => 'refund.completed',
                'channel' => 'database',
                'audience' => 'buyer',
                'subject' => 'Refund Berhasil Ditransfer: {{booking_code}}',
                'body' => 'Refund sebesar Rp {{refund_amount}} telah berhasil ditransfer ke rekening Anda.',
            ],
            [
                'event_key' => 'refund.completed',
                'channel' => 'mail',
                'audience' => 'admin',
                'subject' => '[Refund Selesai] Transfer {{booking_code}} Berhasil',
                'body' => 'Transfer refund {{booking_code}} sebesar Rp {{refund_amount}} telah selesai dilaksanakan.',
            ],
            [
                'event_key' => 'refund.completed',
                'channel' => 'whatsapp',
                'audience' => 'admin',
                'subject' => null,
                'body' => '[Admin] Refund *{{booking_code}}* Rp {{refund_amount}} selesai ditransfer.',
            ],
            [
                'event_key' => 'refund.completed',
                'channel' => 'database',
                'audience' => 'admin',
                'subject' => 'Refund Selesai: {{booking_code}}',
                'body' => 'Refund booking {{booking_code}} sebesar Rp {{refund_amount}} telah berhasil ditransfer.',
            ],

            // =========================================================================
            // 17. payout.created & payout.transferred (partner, admin)
            // =========================================================================
            [
                'event_key' => 'payout.created',
                'channel' => 'mail',
                'audience' => 'partner',
                'subject' => 'Pencairan Pendapatan Terjadwal: {{payout_number}}',
                'body' => "Halo Mitra {{organization_name}},\n\nPendapatan dari pemesanan {{booking_code}} sebesar Rp {{net_amount}} telah masuk jadwal pencairan (Nomor Payout: {{payout_number}}). Tim keuangan akan segera memproses transfer.\n\nTim Destinara",
            ],
            [
                'event_key' => 'payout.created',
                'channel' => 'whatsapp',
                'audience' => 'partner',
                'subject' => null,
                'body' => "Halo *Mitra {{organization_name}}*, pendapatan booking *{{booking_code}}* sebesar *Rp {{net_amount}}* telah terjadwal untuk payout (*{{payout_number}}*).\n\n- Destinara",
            ],
            [
                'event_key' => 'payout.created',
                'channel' => 'database',
                'audience' => 'partner',
                'subject' => 'Payout Terjadwal: {{payout_number}}',
                'body' => 'Pendapatan pemesanan {{booking_code}} sebesar Rp {{net_amount}} telah terjadwal untuk dicairkan.',
            ],
            [
                'event_key' => 'payout.created',
                'channel' => 'mail',
                'audience' => 'admin',
                'subject' => '[Payout Terjadwal] {{payout_number}} Mitra {{organization_name}}',
                'body' => 'Payout baru {{payout_number}} untuk mitra {{organization_name}} sebesar Rp {{net_amount}} telah dibuat dan menunggu proses transfer.',
            ],
            [
                'event_key' => 'payout.created',
                'channel' => 'whatsapp',
                'audience' => 'admin',
                'subject' => null,
                'body' => '[Admin] Payout *{{payout_number}}* (Mitra {{organization_name}}, Rp {{net_amount}}) siap ditransfer.',
            ],
            [
                'event_key' => 'payout.created',
                'channel' => 'database',
                'audience' => 'admin',
                'subject' => 'Payout Terjadwal: {{payout_number}}',
                'body' => 'Payout {{payout_number}} untuk {{organization_name}} sebesar Rp {{net_amount}} telah digenerate.',
            ],
            [
                'event_key' => 'payout.transferred',
                'channel' => 'mail',
                'audience' => 'partner',
                'subject' => 'Dana Pendapatan Telah Ditransfer: {{payout_number}}',
                'body' => "Halo Mitra {{organization_name}},\n\nPembayaran payout {{payout_number}} sebesar Rp {{net_amount}} telah berhasil ditransfer ke rekening {{bank_name}} ({{account_number}}).\n\nBukti transfer dapat diunduh di portal mitra.\n\nTim Destinara",
            ],
            [
                'event_key' => 'payout.transferred',
                'channel' => 'whatsapp',
                'audience' => 'partner',
                'subject' => null,
                'body' => "Halo *Mitra {{organization_name}}*, dana payout *{{payout_number}}* sebesar *Rp {{net_amount}}* telah berhasil ditransfer ke rekening Anda. Bukti transfer tersedia di portal.\n\n- Destinara",
            ],
            [
                'event_key' => 'payout.transferred',
                'channel' => 'database',
                'audience' => 'partner',
                'subject' => 'Dana Payout Ditransfer: {{payout_number}}',
                'body' => 'Dana payout {{payout_number}} sebesar Rp {{net_amount}} telah berhasil ditransfer.',
            ],
            [
                'event_key' => 'payout.transferred',
                'channel' => 'mail',
                'audience' => 'admin',
                'subject' => '[Payout Ditransfer] {{payout_number}} ke {{organization_name}}',
                'body' => 'Payout {{payout_number}} sebesar Rp {{net_amount}} telah ditransfer dan bukti transfer telah diunggah.',
            ],
            [
                'event_key' => 'payout.transferred',
                'channel' => 'whatsapp',
                'audience' => 'admin',
                'subject' => null,
                'body' => '[Admin] Payout *{{payout_number}}* (Rp {{net_amount}}) selesai ditransfer.',
            ],
            [
                'event_key' => 'payout.transferred',
                'channel' => 'database',
                'audience' => 'admin',
                'subject' => 'Payout Selesai Ditransfer: {{payout_number}}',
                'body' => 'Payout {{payout_number}} ke {{organization_name}} sebesar Rp {{net_amount}} telah selesai ditransfer.',
            ],

            // =========================================================================
            // 18. destination.submitted, approved, revision_required, rejected (partner, admin)
            // =========================================================================
            [
                'event_key' => 'destination.submitted',
                'channel' => 'mail',
                'audience' => 'admin',
                'subject' => '[Kurasi Tapak] Destinasi Baru Diajukan: {{destination_name}}',
                'body' => "Mitra {{partner_name}} telah mengajukan destinasi '{{destination_name}}' untuk dikurasi dan dipublikasikan.",
            ],
            [
                'event_key' => 'destination.submitted',
                'channel' => 'whatsapp',
                'audience' => 'admin',
                'subject' => null,
                'body' => '[Admin] Destinasi baru *{{destination_name}}* diajukan oleh *{{partner_name}}* untuk kurasi.',
            ],
            [
                'event_key' => 'destination.submitted',
                'channel' => 'database',
                'audience' => 'admin',
                'subject' => 'Pengajuan Destinasi Baru: {{destination_name}}',
                'body' => "Destinasi '{{destination_name}}' diajukan oleh mitra {{partner_name}} dan siap ditinjau.",
            ],
            [
                'event_key' => 'destination.submitted',
                'channel' => 'mail',
                'audience' => 'partner',
                'subject' => 'Pengajuan Tapak Berhasil Diterima: {{destination_name}}',
                'body' => "Pengajuan destinasi '{{destination_name}}' telah diterima dan masuk ke tahap kurasi tim Destinara.",
            ],
            [
                'event_key' => 'destination.submitted',
                'channel' => 'whatsapp',
                'audience' => 'partner',
                'subject' => null,
                'body' => "Mitra *{{partner_name}}*, pengajuan tapak *{{destination_name}}* telah kami terima untuk proses kurasi.\n\n- Destinara",
            ],
            [
                'event_key' => 'destination.submitted',
                'channel' => 'database',
                'audience' => 'partner',
                'subject' => 'Pengajuan Tapak Diterima: {{destination_name}}',
                'body' => "Pengajuan destinasi '{{destination_name}}' sedang ditinjau oleh tim kurasi.",
            ],
            [
                'event_key' => 'destination.approved',
                'channel' => 'mail',
                'audience' => 'partner',
                'subject' => 'Selamat! Destinasi Anda Telah Disetujui: {{destination_name}}',
                'body' => "Halo Mitra,\n\nDestinasi '{{destination_name}}' telah berhasil lolos kurasi dan kini berstatus tayang di direktori publik Destinara.\n\nTim Destinara",
            ],
            [
                'event_key' => 'destination.approved',
                'channel' => 'whatsapp',
                'audience' => 'partner',
                'subject' => null,
                'body' => "Selamat! Destinasi *{{destination_name}}* telah disetujui kurasi dan sekarang aktif tayang di katalog Destinara.\n\n- Destinara",
            ],
            [
                'event_key' => 'destination.approved',
                'channel' => 'database',
                'audience' => 'partner',
                'subject' => 'Destinasi Disetujui: {{destination_name}}',
                'body' => "Destinasi '{{destination_name}}' telah disetujui dan aktif tayang di katalog publik.",
            ],
            [
                'event_key' => 'destination.revision_required',
                'channel' => 'mail',
                'audience' => 'partner',
                'subject' => 'Catatan Kurasi untuk Destinasi: {{destination_name}}',
                'body' => "Halo Mitra,\n\nPengajuan destinasi '{{destination_name}}' membutuhkan perbaikan sebelum dapat dipublikasikan.\n\nCatatan Kurasi:\n{{moderation_notes}}\n\nSilakan perbaiki melalui portal mitra.\n\nTim Destinara",
            ],
            [
                'event_key' => 'destination.revision_required',
                'channel' => 'whatsapp',
                'audience' => 'partner',
                'subject' => null,
                'body' => "Destinasi *{{destination_name}}* membutuhkan revisi sebelum ditayangkan. Catatan: {{moderation_notes}}. Cek portal mitra.\n\n- Destinara",
            ],
            [
                'event_key' => 'destination.revision_required',
                'channel' => 'database',
                'audience' => 'partner',
                'subject' => 'Revisi Dibutuhkan: {{destination_name}}',
                'body' => "Destinasi '{{destination_name}}' memerlukan revisi: {{moderation_notes}}.",
            ],
            [
                'event_key' => 'destination.rejected',
                'channel' => 'mail',
                'audience' => 'partner',
                'subject' => 'Hasil Kurasi Destinasi: {{destination_name}}',
                'body' => "Halo Mitra,\n\nMohon maaf, pengajuan destinasi '{{destination_name}}' belum dapat disetujui untuk tayang di Destinara.\nAlasan: {{moderation_notes}}.\n\nTim Destinara",
            ],
            [
                'event_key' => 'destination.rejected',
                'channel' => 'whatsapp',
                'audience' => 'partner',
                'subject' => null,
                'body' => "Pengajuan destinasi *{{destination_name}}* tidak dapat disetujui kurasi (Alasan: {{moderation_notes}}).\n\n- Destinara",
            ],
            [
                'event_key' => 'destination.rejected',
                'channel' => 'database',
                'audience' => 'partner',
                'subject' => 'Kurasi Ditolak: {{destination_name}}',
                'body' => "Pengajuan destinasi '{{destination_name}}' ditolak: {{moderation_notes}}.",
            ],

            // =========================================================================
            // 19. partner.verified & partner.rejected (partner)
            // =========================================================================
            [
                'event_key' => 'partner.verified',
                'channel' => 'mail',
                'audience' => 'partner',
                'subject' => 'Verifikasi Lembaga Mitra Berhasil - Destinara',
                'body' => "Halo Pengelola {{organization_name}},\n\nProfil kemitraan dan dokumen legalitas lembaga Anda telah berhasil diverifikasi oleh tim kepatuhan Destinara.\n\nTim Destinara",
            ],
            [
                'event_key' => 'partner.verified',
                'channel' => 'whatsapp',
                'audience' => 'partner',
                'subject' => null,
                'body' => "Selamat! Lembaga mitra *{{organization_name}}* telah berhasil *TERVERIFIKASI* oleh tim Destinara.\n\n- Destinara",
            ],
            [
                'event_key' => 'partner.verified',
                'channel' => 'database',
                'audience' => 'partner',
                'subject' => 'Kemitraan Terverifikasi',
                'body' => "Lembaga mitra '{{organization_name}}' telah resmi terverifikasi.",
            ],
            [
                'event_key' => 'partner.rejected',
                'channel' => 'mail',
                'audience' => 'partner',
                'subject' => 'Verifikasi Lembaga Mitra Memerlukan Perbaikan',
                'body' => "Halo Pengelola {{organization_name}},\n\nDokumen legalitas mitra Anda belum dapat diverifikasi. Catatan: {{reason}}.\n\nTim Destinara",
            ],
            [
                'event_key' => 'partner.rejected',
                'channel' => 'whatsapp',
                'audience' => 'partner',
                'subject' => null,
                'body' => "Verifikasi kemitraan *{{organization_name}}* memerlukan perbaikan (Catatan: {{reason}}). Silakan cek portal mitra.\n\n- Destinara",
            ],
            [
                'event_key' => 'partner.rejected',
                'channel' => 'database',
                'audience' => 'partner',
                'subject' => 'Verifikasi Kemitraan Ditolak',
                'body' => "Verifikasi kemitraan '{{organization_name}}' ditolak: {{reason}}.",
            ],

            // =========================================================================
            // 20. institution.verified & institution.rejected (buyer)
            // =========================================================================
            [
                'event_key' => 'institution.verified',
                'channel' => 'mail',
                'audience' => 'buyer',
                'subject' => 'Institusi Anda Telah Terverifikasi - Destinara',
                'body' => "Halo {{pic_name}},\n\nInstitusi {{institution_name}} telah resmi diverifikasi. Anda kini dapat mengajukan permohonan surat izin dinas langsung di platform.\n\nTim Destinara",
            ],
            [
                'event_key' => 'institution.verified',
                'channel' => 'whatsapp',
                'audience' => 'buyer',
                'subject' => null,
                'body' => "Selamat! Institusi *{{institution_name}}* telah berhasil *TERVERIFIKASI* di platform Destinara.\n\n- Destinara",
            ],
            [
                'event_key' => 'institution.verified',
                'channel' => 'database',
                'audience' => 'buyer',
                'subject' => 'Institusi Terverifikasi',
                'body' => "Institusi '{{institution_name}}' telah berhasil diverifikasi oleh tim Destinara.",
            ],
            [
                'event_key' => 'institution.rejected',
                'channel' => 'mail',
                'audience' => 'buyer',
                'subject' => 'Verifikasi Dokumen Institusi Membutuhkan Perbaikan',
                'body' => "Halo {{pic_name}},\n\nVerifikasi dokumen legalitas institusi {{institution_name}} belum dapat disetujui. Catatan: {{reason}}.\n\nTim Destinara",
            ],
            [
                'event_key' => 'institution.rejected',
                'channel' => 'whatsapp',
                'audience' => 'buyer',
                'subject' => null,
                'body' => "Verifikasi institusi *{{institution_name}}* belum dapat disetujui (Catatan: {{reason}}). Silakan unggah perbaikan di profil.\n\n- Destinara",
            ],
            [
                'event_key' => 'institution.rejected',
                'channel' => 'database',
                'audience' => 'buyer',
                'subject' => 'Verifikasi Institusi Ditolak',
                'body' => "Verifikasi institusi '{{institution_name}}' ditolak: {{reason}}.",
            ],

            // =========================================================================
            // 21. review.received (partner, admin)
            // =========================================================================
            [
                'event_key' => 'review.received',
                'channel' => 'mail',
                'audience' => 'partner',
                'subject' => 'Ulasan Baru untuk Destinasi {{destination_name}}',
                'body' => "Halo Mitra {{organization_name}},\n\nPengunjung telah memberikan ulasan baru (Rating: {{rating}}/5):\n\"{{review_comment}}\"\n\nTim Destinara",
            ],
            [
                'event_key' => 'review.received',
                'channel' => 'whatsapp',
                'audience' => 'partner',
                'subject' => null,
                'body' => "Mitra *{{organization_name}}*, Anda menerima ulasan baru (Rating: {{rating}}/5) untuk *{{destination_name}}*.\n\n- Destinara",
            ],
            [
                'event_key' => 'review.received',
                'channel' => 'database',
                'audience' => 'partner',
                'subject' => 'Ulasan Baru Diterima',
                'body' => 'Destinasi {{destination_name}} menerima ulasan baru dengan rating {{rating}}/5.',
            ],
            [
                'event_key' => 'review.received',
                'channel' => 'mail',
                'audience' => 'admin',
                'subject' => '[Review Baru] {{destination_name}} ({{rating}}/5)',
                'body' => 'Ulasan baru masuk untuk {{destination_name}} dengan rating {{rating}}/5 dari rombongan {{institution_name}}.',
            ],
            [
                'event_key' => 'review.received',
                'channel' => 'whatsapp',
                'audience' => 'admin',
                'subject' => null,
                'body' => '[Admin] Ulasan baru untuk *{{destination_name}}* (Rating: {{rating}}/5).',
            ],
            [
                'event_key' => 'review.received',
                'channel' => 'database',
                'audience' => 'admin',
                'subject' => 'Ulasan Baru: {{destination_name}}',
                'body' => 'Ulasan baru diterima untuk {{destination_name}} (Rating: {{rating}}/5).',
            ],

            // =========================================================================
            // 22. payment.late_received, amount_mismatch, system.wa_disconnected (admin)
            // =========================================================================
            [
                'event_key' => 'payment.late_received',
                'channel' => 'mail',
                'audience' => 'admin',
                'subject' => '[PERHATIAN] Pembayaran Masuk Melebihi Batas (Late Payment): {{booking_code}}',
                'body' => 'Pembayaran sebesar Rp {{amount}} diterima untuk booking {{booking_code}} yang sudah berstatus {{booking_status}}. Refund otomatis 100% telah disiapkan dalam antrean.',
            ],
            [
                'event_key' => 'payment.late_received',
                'channel' => 'whatsapp',
                'audience' => 'admin',
                'subject' => null,
                'body' => '[DARURAT] Late payment masuk untuk *{{booking_code}}* (Rp {{amount}}). Refund 100% disiapkan.',
            ],
            [
                'event_key' => 'payment.late_received',
                'channel' => 'database',
                'audience' => 'admin',
                'subject' => 'Late Payment Diterima: {{booking_code}}',
                'body' => 'Pembayaran Rp {{amount}} diterima pada booking {{booking_code}} yang sudah kedaluwarsa.',
            ],
            [
                'event_key' => 'payment.amount_mismatch',
                'channel' => 'mail',
                'audience' => 'admin',
                'subject' => '[DARURAT] Nominal Webhook Pembayaran Tidak Sesuai: {{booking_code}}',
                'body' => 'Nominal pembayaran pada webhook (Rp {{received_amount}}) berbeda dengan tagihan di sistem (Rp {{expected_amount}}) untuk external_id {{external_id}}.',
            ],
            [
                'event_key' => 'payment.amount_mismatch',
                'channel' => 'whatsapp',
                'audience' => 'admin',
                'subject' => null,
                'body' => '[DARURAT] Mismatch nominal pembayaran pada *{{booking_code}}*: Diterima Rp {{received_amount}} vs Tagihan Rp {{expected_amount}}.',
            ],
            [
                'event_key' => 'payment.amount_mismatch',
                'channel' => 'database',
                'audience' => 'admin',
                'subject' => 'Payment Mismatch: {{booking_code}}',
                'body' => 'Ketidakcocokan nominal pembayaran terdeteksi pada booking {{booking_code}}.',
            ],
            [
                'event_key' => 'system.wa_disconnected',
                'channel' => 'mail',
                'audience' => 'admin',
                'subject' => '[ALERT SISTEM] Layanan WhatsApp Gateway Terputus',
                'body' => 'Koneksi perangkat VexaHost WhatsApp Gateway terputus atau API Key tidak valid. Notifikasi WA tidak dapat dikirim.',
            ],
            [
                'event_key' => 'system.wa_disconnected',
                'channel' => 'whatsapp',
                'audience' => 'admin',
                'subject' => null,
                'body' => '[ALERT] Layanan WhatsApp Gateway Destinara terputus atau tidak terhubung.',
            ],
            [
                'event_key' => 'system.wa_disconnected',
                'channel' => 'database',
                'audience' => 'admin',
                'subject' => 'Layanan WhatsApp Terputus',
                'body' => 'Koneksi gateway WhatsApp terputus. Harap cek pengaturan kredensial VexaHost.',
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
