# 🚀 Panduan Deployment Destinara Core System ke Coolify

Panduan deployment produksi **Destinara Core System** (`layanan.destinara.my.id`) ke **Coolify** (Nixpacks, PHP 8.2+, MySQL, Queue Worker, Cron Scheduler) sesuai **BACKEND SPEC DESTINARA §21**.

---

## 1. Arsitektur & Kebutuhan Server

- **Build Pack**: Nixpacks
- **PHP Version**: 8.2+
- **Database**: MySQL 8.0+ (`db_destinarasystem`)
- **Queue Connection**: `database`
- **Cache & Session**: `database`
- **Domains (FQDN)**:
  - Production: `https://layanan.destinara.my.id` (SSL dikelola otomatis oleh Let's Encrypt Coolify)
  - Staging: `https://layanan-staging.destinara.my.id`
  - Development: `https://layanan-dev.destinara.my.id`

---

## 2. Environment Variables Wajib

Masukkan variabel-variabel berikut di tab **Environment Variables** aplikasi Coolify Anda:

```dotenv
APP_NAME=Destinara
APP_ENV=production
APP_KEY=base64:... # Hasilkan dengan: php artisan key:generate --show
APP_DEBUG=false
APP_URL=https://layanan.destinara.my.id

LOG_CHANNEL=stack
LOG_LEVEL=info

# Database MySQL Coolify
DB_CONNECTION=mysql
DB_HOST=kgnzm2wodjjnfjfrxdo1avqa # Sesuai container Coolify resource
DB_PORT=3306
DB_DATABASE=db_destinarasystem
DB_USERNAME=mysql # atau root jika akses ditolak
DB_PASSWORD=secret_password_anda

# Queue, Cache & Session
BROADCAST_CONNECTION=log
CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true

# Hubungan ke Company Profile (CORS & Public API)
COMPRO_URL=https://destinara.my.id

# Akun Superadmin Awal (Wajib diisi untuk seeding awal)
SUPERADMIN_NAME="Super Administrator"
SUPERADMIN_EMAIL="superadmin@destinara.my.id"
SUPERADMIN_PASSWORD="GantiDenganPasswordSuperAman123!"
```

> ⚠️ **PENTING: JANGAN SIMPAN SECRET GATEWAY DI .ENV!**
> Kredensial Xendit, Midtrans, VexaHost WA, dan SMTP Brevo diatur langsung melalui panel admin di menu **Pengaturan Sistem** (`/admin/settings`). Kredensial tersebut disimpan di database dengan enkripsi AES-256 (`Crypt::encryptString`).

---

## 3. Persistent Storage (Volume)

Untuk memastikan dokumen PDF ber-QR, upload bukti transfer, media destinasi, dan cadangan sistem tidak terhapus saat rebuild container:

Di Coolify, buka tab **Storages** / **Persistent Storage**:
- **Source Volume**: `destinara-system-storage-app`
- **Destination Path**: `/app/storage/app`

---

## 4. Perintah Pasca-Deploy (Post-deployment Command)

### Deploy Pertama Kali (Inisialisasi Fresh Database):
Karena seluruh schema telah dirombak dan data lama adalah data fiktif / dummy, jalankan perintah fresh seeder **sekali saja**:
```bash
php artisan migrate:fresh --seed --force && php artisan storage:link && php artisan optimize
```

### Update Deploy Selanjutnya (Continuous Deployment):
Untuk build dan update rutin:
```bash
php artisan migrate --force && php artisan db:seed --force && php artisan storage:link && php artisan optimize
```

---

## 5. Background Processes (Worker & Scheduler)

### A. Queue Worker
Di Coolify, pada tab **Processes** / **Worker**, tambahkan background worker berikut:
```bash
php artisan queue:work --queue=webhooks,default,documents,notifications --tries=3 --timeout=120 --max-time=3600
```
- Menangani webhook pembayaran, antrean pembuatan PDF bertanda tangan/QR, dan pengiriman notifikasi WhatsApp/Email.

### B. Cron Scheduler
Jalankan scheduler Laravel setiap menit. Anda dapat mengkonfigurasinya melalui:
1. **Coolify Scheduled Task**:
   - Schedule: `* * * * *`
   - Command: `php artisan schedule:run`
2. **Atau Tambah Daemon Process di Coolify**:
   ```bash
   php artisan schedule:work
   ```

Tugas cron yang otomatis berjalan meliputi:
- `booking:expire-unpaid` (setiap 5 menit)
- `partner:check-confirmation-sla` (setiap 10 menit)
- `booking:auto-complete` (setiap jam)
- `payout:auto-create` (setiap jam)
- `payout:process-pending` (setiap hari pkl 03:00)
- `reconciliation:daily` (setiap hari pkl 01:00)
- `destinara:audit-cleanup` (setiap hari Minggu pkl 02:00)
- `backup:clean` & `backup:run` (setiap hari pkl 02:00)

---

## 6. Pendaftaran URL Webhook Pihak Ketiga

Daftarkan URL webhook berikut pada masing-masing dashboard penyedia:

1. **Xendit Dashboard**:
   - URL: `https://layanan.destinara.my.id/webhooks/payments/xendit`
   - Webhook Verification Token: Salin token dari Xendit dan simpan di menu `/admin/settings` (field `xendit_callback_token`).

2. **Midtrans Dashboard**:
   - Payment Notification URL: `https://layanan.destinara.my.id/webhooks/payments/midtrans`
   - Masukkan Server Key Midtrans di `/admin/settings` untuk verifikasi HMAC signature SHA-512.

3. **VexaHost Dashboard**:
   - Webhook URL: `https://layanan.destinara.my.id/webhooks/whatsapp`
   - Event: `message.status`, `session.status`
   - Webhook Secret: Masukkan di `/admin/settings` (field `vexahost_webhook_secret`).

---

## 7. Akses Awal Admin

Setelah deploy berhasil:
- Akses URL: `https://layanan.destinara.my.id/admin`
- Email & Password: Menggunakan nilai `SUPERADMIN_EMAIL` dan `SUPERADMIN_PASSWORD` yang Anda daftarkan di environment variables Coolify.
- Masuk ke menu **Pengaturan Sistem** untuk melengkapi kredensial payment gateway, WhatsApp, SMTP, dan identitas perusahaan.
