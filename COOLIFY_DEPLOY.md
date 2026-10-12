# 🚀 Panduan Deployment Destinara Core System ke Coolify

Panduan lengkap deployment proyek **Destinara Core System** (Laravel 12 + Vite + MySQL + Queue) ke **Coolify** menggunakan domain **destinara.my.id**.

---

## 1. Arsitektur Domain & Environment

| Environment | Domain / FQDN di Coolify | Database | File Acuan Env |
|---|---|---|---|
| **Development** | `http://127.0.0.1:8031` | `db_destinarasystem` | `.env.development` |
| **Staging** | `https://app-staging.destinara.my.id` | `destinara_system_staging` | `.env.staging` |
| **Production** | `https://app.destinara.my.id` | `destinara_system_prod` | `.env.production` |

*(Subdomain dapat disesuaikan di Coolify, misalnya `system.destinara.my.id` jika Anda memilih penamaan tersebut).*

---

## 2. Langkah Deployment di Coolify Dashboard

### A. Buat Resource Application
1. Buka dashboard **Coolify**.
2. Pilih Project & Environment (misal: `Production` atau `Staging`).
3. Klik **+ New** -> **Application** -> pilih repositori Git Anda.
4. Pilih branch (`main` atau `dev`).
5. Pada pilihan Build Pack, gunakan **Nixpacks** (default).

### B. Konfigurasi Domain (FQDN)
Pada kolom **Domains**:
- **Production**: `https://app.destinara.my.id`
- **Staging**: `https://app-staging.destinara.my.id`

> Coolify secara otomatis mengonfigurasi SSL Let's Encrypt melalui reverse proxy Traefik/Caddy. Laravel sudah dikonfigurasi dengan middleware `trustProxies(at: '*')` sehingga HTTPS dan cookie session aman tanpa loop redirect.

### C. Masukkan Environment Variables
1. Buka tab **Environment Variables** di aplikasi Coolify Anda.
2. Buka file `.env.production` (atau `.env.staging`).
3. Salin (**Copy**) seluruh isi file dan tempel (**Paste**) ke form Environment Variables Coolify.
4. Sesuaikan nilai kredensial:
   - `DB_HOST`: Jika MySQL dibuat sebagai service di Coolify pada project/network yang sama, isi nama servicenya (misal: `mysql` atau `destinara-mysql`). Jika di luar container, gunakan IP private atau `host.docker.internal`.
   - `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
   - `MAIL_PASSWORD` (password SMTP email Hostinger / layanan email Anda).

### D. Perintah Pasca-Deploy (Post-deployment Command)
Di menu konfigurasi aplikasi Coolify, pada bagian **Post-deployment Command** (atau di Terminal Container):
```bash
php artisan migrate --force && php artisan storage:link && php artisan config:cache && php artisan route:cache && php artisan view:cache
```

### E. Background Queue Worker (Opsional namun Direkomendasikan)
Destinara System menggunakan antrean `QUEUE_CONNECTION=database` untuk notifikasi & generator dokumen:
- Di Coolify, Anda dapat menambahkan command worker di tab Process / Worker:
  ```bash
  php artisan queue:work --tries=3 --timeout=90
  ```

---

## 3. Persistent Storage
Untuk menjaga agar file upload (dokumen NPSN, invoice, e-pass, galeri destinasi) tidak hilang saat container di-rebuild:
- Tambahkan **Persistent Storage** di Coolify:
  - **Source Volume**: `system-storage-public`
  - **Destination Path**: `/app/storage/app/public`

---

## 4. Akun Demo / Awal
Jika menjalankan seeder awal (`php artisan db:seed`):
- **Admin**: `admin@destinara.id` / `password`
- **Superadmin**: Akses langsung via `/superadmin` (Quick login tersedia di mode demo)
- **Mitra & Buyer**: Tersedia akun demo atau registrasi baru di `/daftar`.
