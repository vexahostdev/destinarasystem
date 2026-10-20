<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console & Scheduled Tasks (SPEC §12)
|--------------------------------------------------------------------------
*/

// 0. Queue worker (PDF dokumen & notifikasi email/WA). Dijalankan scheduler tiap menit agar deployment
//    cukup satu Scheduled Task `schedule:run` tanpa supervisor; berhenti saat antrean kosong atau 55 detik.
Schedule::command('queue:work --queue=notifications,documents,default --stop-when-empty --max-time=55 --tries=3 --timeout=50')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->runInBackground();

// 1. Kedaluwarsa booking unpaid melewati batas waktu
Schedule::command('bookings:expire-unpaid')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// 2. Reminder bayar booking mendekati batas waktu
Schedule::command('payments:send-reminders')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// 3. Batalkan booking menunggu konfirmasi partner yang melampaui SLA
Schedule::command('bookings:partner-sla')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// 4. Reminder kunjungan H-1 / hari H kepada buyer
Schedule::command('bookings:visit-reminders')
    ->dailyAt('07:00')
    ->withoutOverlapping()
    ->runInBackground();

// 5. Auto complete booking yang telah selesai tanggal kunjungan
Schedule::command('bookings:auto-complete')
    ->dailyAt('23:00')
    ->withoutOverlapping()
    ->runInBackground();

// 6. Generate draft payout harian untuk partner
Schedule::command('payouts:generate')
    ->dailyAt('01:00')
    ->withoutOverlapping()
    ->runInBackground();

// 7. Retry pengiriman notifikasi yang sempat gagal
Schedule::command('notifications:retry-failed')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// 8. Cek status kesehatan gateway WhatsApp
Schedule::command('whatsapp:health-check')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// 9. Rekam heartbeat kesehatan operasional sistem
Schedule::command('system:heartbeat')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// 10. Backup basis data otomatis (harian)
Schedule::command('backup:run --only-db')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->runInBackground();

// 11. Backup berkas aplikasi: dokumen, bukti transfer, legalitas, media (mingguan)
Schedule::command('backup:run --only-files')
    ->weeklyOn(0, '03:00')
    ->withoutOverlapping()
    ->runInBackground();

// 12. Hapus arsip backup lama sesuai strategi retensi config/backup.php
Schedule::command('backup:clean')
    ->dailyAt('01:30')
    ->withoutOverlapping()
    ->runInBackground();

// 13. Pantau kesehatan backup (umur & ukuran); kirim alert bila tidak sehat
Schedule::command('backup:monitor')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->runInBackground();
