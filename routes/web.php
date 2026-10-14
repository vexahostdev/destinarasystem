<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicController;

/*
|--------------------------------------------------------------------------
| Destinara Platform — User No Login (Desktop & Mobile)
|--------------------------------------------------------------------------
| 6 Halaman Publik Gerbang Sistem sesuai PRD v1.0 & Kanvas Figma
*/

// 1. Marketplace Etalase Tapak Nusantara (Root & Katalog — Portal Sistem Langsung ke Katalog)
Route::get('/', [PublicController::class, 'destinasi'])->name('destinasi.index');
Route::get('/destinasi', [PublicController::class, 'destinasi']);
Route::redirect('/home', '/')->name('home');

// 2. Detail Destinasi & Silabus Kurikulum Lapangan
Route::get('/destinasi/{slug}', [PublicController::class, 'showDestinasi'])->name('destinasi.show');

use App\Http\Controllers\AuthController;

// 3. Alur & Cara Kerja Kemitraan
Route::get('/cara-kerja', [PublicController::class, 'caraKerja'])->name('cara-kerja');
Route::get('/faq', [PublicController::class, 'faq'])->name('faq');
Route::get('/syarat-ketentuan', [PublicController::class, 'terms'])->name('terms');

// 4. Verifikasi Keabsahan Dokumen (SPEC §11, §13)
Route::get('/verifikasi/{hash}', [PublicController::class, 'verifyDocument'])->name('verification.document');
Route::get('/dokumen/unduh/{document}', [\App\Http\Controllers\DocumentController::class, 'download'])->middleware('auth')->name('documents.download');

// 5. Pendaftaran Akun
Route::get('/daftar', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/daftar', [AuthController::class, 'register'])->name('register.post');

// 6. Masuk Akun / Login
Route::get('/masuk', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/masuk', [AuthController::class, 'login'])->name('login.post');

// 7. Logout Akun (POST saja - SPEC K11 & §13)
Route::post('/keluar', [AuthController::class, 'logout'])->name('logout');
Route::post('/logout', [AuthController::class, 'logout']);

// Legacy redirects (SPEC §13)
Route::redirect('/login', '/masuk', 301);
Route::redirect('/register', '/daftar', 301);

// 8. Password Reset
Route::get('/lupa-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
Route::post('/lupa-password', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

// 9. Email Verification
Route::get('/email/verifikasi', [AuthController::class, 'showVerificationNotice'])->middleware('auth')->name('verification.notice');
Route::get('/email/verifikasi/{id}/{hash}', [AuthController::class, 'verifyEmail'])->middleware(['auth', 'signed'])->name('verification.verify');
Route::post('/email/verifikasi/kirim-ulang', [AuthController::class, 'resendVerificationEmail'])->middleware(['auth', 'throttle:6,1'])->name('verification.send');

// 10. Webhooks (SPEC §9.5, §10.1, §13)
use App\Http\Controllers\Webhooks\PaymentWebhookController;
use App\Http\Controllers\Webhooks\WhatsAppWebhookController;

Route::prefix('webhooks')->group(function () {
    Route::post('/payments/xendit', [PaymentWebhookController::class, 'handleXendit'])->middleware('throttle:120,1');
    Route::post('/payments/midtrans', [PaymentWebhookController::class, 'handleMidtrans'])->middleware('throttle:120,1');
    Route::post('/whatsapp', [WhatsAppWebhookController::class, 'handle'])->middleware('throttle:120,1');
});


/*
|--------------------------------------------------------------------------
| Destinara Platform — User Login (Buyer Institusi)
|--------------------------------------------------------------------------
| 6 Halaman Portal Buyer (Sekolah, Kampus, Peneliti) sesuai PRD v1.0
*/
use App\Http\Controllers\BuyerController;

Route::prefix('buyer')->name('buyer.')->middleware(['auth', 'active', 'account:buyer'])->group(function () {
    // 1. Dashboard Buyer
    Route::get('/dashboard', [BuyerController::class, 'dashboard'])->name('dashboard');

    // 2. Form Pengajuan Booking
    Route::get('/booking/create', [BuyerController::class, 'createBooking'])->name('booking.create');

    // 3. Detail Booking & Status Verifikasi
    Route::get('/booking/{code}', [BuyerController::class, 'showBooking'])->name('booking.show');

    // 4. Pembayaran / Checkout & Simulasi Pelunasan
    Route::get('/booking/{code}/pembayaran', [BuyerController::class, 'payment'])->name('booking.payment');
    Route::post('/booking/{code}/bayar', [BuyerController::class, 'processPayment'])->name('booking.pay');

    // 5. Riwayat Transaksi & Re-order
    Route::get('/riwayat', [BuyerController::class, 'history'])->name('history');

    // 6. Profil Institusi & Dokumen Pendukung
    Route::get('/profil', [BuyerController::class, 'profile'])->name('profile');
    Route::post('/profil/update', [BuyerController::class, 'updateProfile'])->name('profile.update');

    // 7. Wishlist Tapak Edukasi
    Route::get('/wishlist', [BuyerController::class, 'wishlist'])->name('wishlist');
    Route::post('/wishlist/toggle', [BuyerController::class, 'toggleWishlist'])->name('wishlist.toggle');

    // 8. Pusat Notifikasi Buyer
    Route::get('/notifikasi', [BuyerController::class, 'notifications'])->name('notifications');

    // 9. Ulasan Kunjungan Selesai
    Route::post('/booking/{code}/ulasan', [BuyerController::class, 'submitReview'])->name('booking.review');
});

// Pengalihan cerdas /dashboard ke portal sesuai tipe akun
Route::get('/dashboard', function() {
    if (!Auth::check()) return redirect()->route('login');
    $user = Auth::user();
    if ($user->isPartner()) {
        return redirect()->route('mitra.dashboard');
    }
    if ($user->isInternal()) {
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('buyer.dashboard');
})->middleware('auth')->name('dashboard');

/*
|--------------------------------------------------------------------------
| Destinara Platform — Layer 3: Mitra / Vendor (Pengelola Tapak)
|--------------------------------------------------------------------------
| 7 Halaman Portal Pengelola Tapak sesuai PRD v1.0 & Figma
*/
use App\Http\Controllers\PartnerController;

Route::prefix('mitra')->name('mitra.')->middleware(['auth', 'active', 'account:partner'])->group(function () {
    // 1. Dashboard Mitra
    Route::get('/dashboard', [PartnerController::class, 'dashboard'])->name('dashboard');

    // 2. Laporan Pendapatan (Read-only Payout)
    Route::get('/pendapatan', [PartnerController::class, 'incomeReport'])->name('income');

    // 3. Kelola Listing Destinasi
    Route::get('/destinasi', [PartnerController::class, 'destinations'])->name('destinations');

    // 4. Kelola Ketersediaan / Kalender Slot
    Route::get('/ketersediaan', [PartnerController::class, 'availability'])->name('availability');
    Route::post('/ketersediaan/slot/update', [PartnerController::class, 'updateSlot'])->name('slot.update');
    Route::post('/ketersediaan/bulk', [PartnerController::class, 'bulkGenerateSlots'])->name('slots.bulk');

    // 5. Booking Masuk
    Route::get('/booking', [PartnerController::class, 'incomingBookings'])->name('bookings');
    Route::post('/booking/{id}/terima', [PartnerController::class, 'confirmBooking'])->name('booking.confirm');
    Route::post('/booking/{id}/tolak', [PartnerController::class, 'rejectBooking'])->name('booking.reject');

    // 6. Form Ajukan Destinasi Baru
    Route::get('/destinasi/tambah', [PartnerController::class, 'createDestination'])->name('destinations.create');
    Route::post('/destinasi/simpan', [PartnerController::class, 'storeDestination'])->name('destinations.store');

    // 7. Profil Pengelola & Rekening Payout
    Route::get('/profil', [PartnerController::class, 'profile'])->name('profile');
    Route::post('/profil/update', [PartnerController::class, 'updateProfile'])->name('profile.update');

    // 8. Check-in QR (Signed URL - SPEC §11, §8.8)
    Route::get('/checkin/{code}', [PartnerController::class, 'showCheckin'])->name('checkin.show');
    Route::post('/checkin/{code}/konfirmasi', [PartnerController::class, 'confirmCheckin'])->name('checkin.confirm');

    // 9. Pusat Notifikasi Mitra
    Route::get('/notifikasi', [PartnerController::class, 'notifications'])->name('notifications');

    // 10. Balas Ulasan Pembeli
    Route::post('/ulasan/{id}/balas', [PartnerController::class, 'replyReview'])->name('reviews.reply');
});

// Route alias for partner dashboard
Route::get('/partner/dashboard', fn() => redirect()->route('mitra.dashboard'))->name('partner.dashboard');

// Named alias for signed URL generation
Route::get('/mitra/checkin/{code}', [PartnerController::class, 'showCheckin'])->middleware(['signed'])->name('partner.checkin.show');

/*
|--------------------------------------------------------------------------
| Destinara Platform — Layer 4: Admin Operasional
|--------------------------------------------------------------------------
| 7 Halaman Konsol Admin Operasional sesuai PRD v1.0 & Figma
*/
use App\Http\Controllers\AdminController;

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin|super_admin'])->group(function () {
    // 1. Dashboard Admin Harian
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    // 2. Verifikasi Listing Destinasi Baru
    Route::get('/destinasi/verifikasi', [AdminController::class, 'verifyDestinations'])->name('destinations.verify');
    Route::post('/destinasi/{id}/status', [AdminController::class, 'updateDestinationStatus'])->name('destinations.updateStatus');

    // 3. Verifikasi Akun Mitra & Institusi
    Route::get('/verifikasi-pengguna', [AdminController::class, 'verifyUsers'])->name('users.verify');
    Route::post('/verifikasi-pengguna/{type}/{id}', [AdminController::class, 'updateUserVerification'])->name('users.updateVerification');

    // 4. Manajemen Booking Lintas Destinasi
    Route::get('/booking', [AdminController::class, 'manageBookings'])->name('bookings.index');
    Route::post('/booking/{id}/status', [AdminController::class, 'updateBookingStatus'])->name('bookings.updateStatus');

    // 5. Generator Dokumen Resmi
    Route::get('/dokumen', [AdminController::class, 'documentGenerator'])->name('documents.index');

    // 6. Form Kurasi & Manajemen Data Lengkap
    Route::get('/kurasi/{id?}', [AdminController::class, 'curationEditor'])->name('destinations.curation');
    Route::post('/kurasi/{id}', [AdminController::class, 'updateCuration'])->name('destinations.updateCuration');

    // 7. Pusat Notifikasi & Log Queue
    Route::get('/log-notifikasi', [AdminController::class, 'notificationLogs'])->name('notifications.index');
    Route::post('/log-notifikasi/{id}/retry', [AdminController::class, 'retryNotification'])->name('notifications.retry');
});

/*
|--------------------------------------------------------------------------
| Destinara Platform — Layer 5: Super Admin (Dewan Direksi & Kontrol Ekosistem)
|--------------------------------------------------------------------------
| 5 Halaman Eksekutif Super Admin sesuai PRD v1.0 & Figma
*/
use App\Http\Controllers\SuperAdminController;

Route::prefix('superadmin')->name('superadmin.')->middleware(['auth', 'role:super_admin'])->group(function () {
    // 1. Dashboard Kontrol Ekosistem
    Route::get('/dashboard', [SuperAdminController::class, 'dashboard'])->name('dashboard');

    // 2. Pengaturan Master
    Route::get('/pengaturan', [SuperAdminController::class, 'settings'])->name('settings');
    Route::post('/pengaturan', [SuperAdminController::class, 'updateSettings'])->name('settings.update');

    // 3. Modal Dialog Tindakan Kritis & Manajemen Akses Pengguna
    Route::get('/pengguna', [SuperAdminController::class, 'users'])->name('users');
    Route::post('/pengguna/kritis', [SuperAdminController::class, 'executeCriticalAction'])->name('users.critical');

    // 4. Analitik Bisnis & Reporting Ekosistem
    Route::get('/analitik', [SuperAdminController::class, 'analytics'])->name('analytics');

    // 5. Rekonsiliasi Keuangan & Audit Log Master
    Route::get('/rekonsiliasi', [SuperAdminController::class, 'reconciliation'])->name('reconciliation');
    Route::post('/rekonsiliasi/tutup-buku', [SuperAdminController::class, 'triggerReconciliation'])->name('reconciliation.trigger');
});




