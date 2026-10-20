# Destinara Core System (`layanan.destinara.my.id`)

Core System backend dan portal operasional terpadu platform edukasi lapangan dan riset berbasis komunitas **Destinara** (PT DESTINARA CHAKRAWAL ARTHA). Dibangun menggunakan **Laravel 12**, **PHP 8.2+**, **Livewire 3**, dan **MySQL**.

---

## 1. Arsitektur & Spesifikasi

- **Framework**: Laravel 12.x dengan arsitektur Domain-Driven Services
- **PHP Version**: PHP 8.2+ (Typed properties, Backed Enums, Constructor property promotion)
- **Database**: MySQL 8.0+ dengan InnoDB, integer rupiah (`unsignedBigInteger`), foreign key strict constraints
- **State Machine**: `BookingStateMachine` mengelola seluruh siklus status booking: `menunggu_pembayaran`, `dibayar`, `dikonfirmasi`, `berlangsung`, `selesai`, `batal`, `kedaluwarsa`
- **Pembayaran**: Multi-driver (`Xendit Invoice v2`, `Midtrans Snap`, `Manual Transfer`) dengan validasi webhook HMAC/token dan idempotensi `payment_webhook_logs`
- **Notifikasi**: Multichannel (`VexaHost WhatsApp API`, `Brevo SMTP Mail`, `Database in-app`) via asynchronous queue
- **Dokumen**: DomPDF generator (`Surat Izin Tapak`, `E-Voucher`, `Faktur / Invoice`, `Kwitansi`) dengan QR verification SVG dan hash publik SHA-256
- **Hak Akses**: Spatie Laravel-Permission dengan 40+ permission granular, `super_admin` Gate bypass, Policy-based authorization di semua resource
- **Public API v1**: Endpoint RESTful terisolasi (`/api/v1/public/*`) dengan rate limiter (60/min), CORS restriction ke Company Profile, dan smart tag caching

---

## 2. Struktur Modul & Domain

```
app/
├── Domain/
│   ├── Booking/        # BookingService, PricingService, StateMachine, CheckInService
│   ├── Catalog/        # DestinationService, MediaService, AvailabilityCalendarService
│   ├── Document/       # DocumentService, DocumentNumberGenerator, SVG QR Code
│   ├── Finance/        # RefundService, PayoutService, ReconciliationService
│   ├── Notification/   # NotificationDispatcher, VexaHostClient, TemplateRenderer
│   ├── Payment/        # PaymentGatewayManager, XenditGateway, MidtransGateway, ManualTransferGateway
│   └── Settings/       # SettingsRepository (Cache + AES-256-CBC encrypted)
├── Http/
│   ├── Controllers/
│   │   ├── Admin/      # Unified Admin Portal Controllers (/admin)
│   │   ├── Api/        # Public API Controllers (/api/v1/public)
│   │   ├── Webhooks/   # Webhook Controllers (Payment & WhatsApp)
│   │   ├── BuyerController.php
│   │   ├── PartnerController.php
│   │   └── PublicController.php
│   ├── Middleware/     # EnsureAccountActive, EnsureUserRole, EnsurePartnerVerified
│   └── Requests/       # FormRequest validation for all mutations
├── Livewire/
│   ├── Buyer/          # Livewire Booking Form with realtime price calculation
│   └── Public/         # Livewire Destination Catalog
└── Models/             # Eloquent Models with Enums, Auditable trait, SoftDeletes
```

---

## 3. Persyaratan Sistem

- PHP >= 8.2
- Ekstensi PHP: `pdo_mysql`, `mbstring`, `openssl`, `bcmath`, `curl`, `gd` / `imagick`, `zip`
- Composer 2.x
- Node.js 18+ & NPM
- MySQL 8.0+

---

## 4. Instalasi & Setup Lokal

1. **Clone repository & masuk ke direktori**:
   ```bash
   git clone <repo-url> destinara-system
   cd destinara-system
   ```

2. **Pasang dependensi PHP & Node**:
   ```bash
   composer install
   npm install && npm run build
   ```

3. **Salin environment file & generate app key**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Konfigurasi `.env`**:
   Atur koneksi database dan kredensial awal superadmin:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=db_destinarasystem
   DB_USERNAME=root
   DB_PASSWORD=

   SUPERADMIN_NAME="Super Administrator"
   SUPERADMIN_EMAIL=superadmin@destinara.id
   SUPERADMIN_PASSWORD=RahasiaDestinara2026!
   ```

5. **Migrasi database & jalankan seeder produksi**:
   ```bash
   php artisan migrate:fresh --seed
   ```

6. **Tautkan storage publik**:
   ```bash
   php artisan storage:link
   ```

7. **Jalankan development server**:
   ```bash
   php artisan serve --port=8020
   ```

> [!IMPORTANT]
> **Catatan Asset Frontend & Vite (`public/hot`)**:
> Saat deployment atau jika dev server Vite (`npm run dev`) tidak berjalan, pastikan file `public/hot` tidak tertinggal (hapus berkas `public/hot` bila ada). Selalu jalankan `npm run build` sebelum menjalankan aplikasi di production atau staging agar aset terkompilasi di `public/build` tersedia secara mandiri tanpa dev server.

---

## 5. Pengujian & Quality Assurance

Jalankan test suite menggunakan PHPUnit:
```bash
php artisan test
```

Pemeriksaan gaya kode (code style) dengan Laravel Pint:
```bash
./vendor/bin/pint --test
```

---

## 6. Penjadwal & Background Worker

### Background Queue Worker
Destinara System memproses pembuatan dokumen PDF dan pengiriman pesan (WA/Email) di latar belakang:
```bash
php artisan queue:work --tries=3 --timeout=90
```

### Cron Scheduler (Task Scheduling)
Jalankan scheduler Laravel setiap menit pada crontab server:
```crontab
* * * * * cd /path-to-system && php artisan schedule:run >> /dev/null 2>&1
```

Daftar command terjadwal otomatis (SPEC §12):
- `bookings:expire-unpaid` (setiap 5 menit) — membatalkan booking melewati batas bayar & melepas slot
- `payments:send-reminders` (setiap 10 menit) — mengirim pengingat bayar H-2 jam dan H-30 menit
- `bookings:partner-sla` (setiap 15 menit) — eskalasi jika mitra belum konfirmasi melewati batas SLA
- `bookings:visit-reminders` (setiap hari 07:00 WIB) — pengingat kunjungan H-1 dan H-0 dengan link e-voucher
- `bookings:auto-complete` (setiap hari 23:00 WIB) — menyelesaikan kunjungan yang telah lewat jadwal
- `payouts:generate` (setiap hari 01:00 WIB) — kalkulasi payout mitra berstatus `completed`
- `notifications:retry-failed` (setiap 30 menit) — retry pengiriman notifikasi gagal
- `whatsapp:health-check` (setiap 10 menit) — monitoring status gateway WA VexaHost
- `system:heartbeat` (setiap menit) — pencatatan liveness scheduler ke sistem monitoring
- `backup:run --only-db` (setiap hari 02:00 WIB) — backup otomatis database

---

## 7. Deployment ke Coolify

Panduan deployment produksi, konfigurasi container Nixpacks, persistent volumes, dan SSL Let's Encrypt tersedia pada berkas [COOLIFY_DEPLOY.md](COOLIFY_DEPLOY.md).

---

## 8. Lisensi & Hak Cipta

Hak Cipta &copy; 2026 PT DESTINARA CHAKRAWAL ARTHA. Seluruh hak cipta dilindungi undang-undang.
