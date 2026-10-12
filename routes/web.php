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

// 3. Alur & Cara Kerja Kemitraan
Route::get('/cara-kerja', [PublicController::class, 'caraKerja'])->name('cara-kerja');

// 5. Pendaftaran Akun (Split-screen)
Route::get('/daftar', [PublicController::class, 'register'])->name('register');
Route::get('/register', [PublicController::class, 'register']);

// 6. Masuk Akun / Login
Route::get('/masuk', [PublicController::class, 'login'])->name('login');
Route::get('/login', [PublicController::class, 'login']);

// 7. Logout Akun
Route::match(['get', 'post'], '/logout', function() {
    \Illuminate\Support\Facades\Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('destinasi.index');
})->name('logout');

/*
|--------------------------------------------------------------------------
| Destinara Platform — User Login (Buyer Institusi)
|--------------------------------------------------------------------------
| 6 Halaman Portal Buyer (Sekolah, Kampus, Peneliti) sesuai PRD v1.0
*/
use App\Http\Controllers\BuyerController;

Route::prefix('buyer')->name('buyer.')->group(function () {
    // 1. Dashboard Buyer
    Route::get('/dashboard', [BuyerController::class, 'dashboard'])->name('dashboard');

    // 2. Form Pengajuan Booking
    Route::get('/booking/create', [BuyerController::class, 'createBooking'])->name('booking.create');
    Route::post('/booking/store', [BuyerController::class, 'storeBooking'])->name('booking.store');

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

    // Helper: Unduh Dokumen Resmi (Invoice, e-Pass, Draf Izin)
    Route::get('/booking/{code}/dokumen/{type}', [BuyerController::class, 'downloadDoc'])->name('document.download');

    // Helper: Quick Login Demo Buyer
    Route::get('/quick-login', [BuyerController::class, 'quickLogin'])->name('quick-login');
});

// Alias rute umum
Route::get('/dashboard', [BuyerController::class, 'dashboard']);

/*
|--------------------------------------------------------------------------
| Destinara Platform — Layer 3: Mitra / Vendor (Pengelola Tapak)
|--------------------------------------------------------------------------
| 7 Halaman Portal Pengelola Tapak sesuai PRD v1.0 & Figma
*/
use App\Http\Controllers\PartnerController;

Route::prefix('mitra')->name('mitra.')->group(function () {
    // 1. Dashboard Mitra
    Route::get('/dashboard', [PartnerController::class, 'dashboard'])->name('dashboard');

    // 2. Laporan Pendapatan & Payout
    Route::get('/pendapatan', [PartnerController::class, 'incomeReport'])->name('income');
    Route::post('/pendapatan/cairkan', [PartnerController::class, 'requestPayout'])->name('payout.request');

    // 3. Kelola Listing Destinasi
    Route::get('/destinasi', [PartnerController::class, 'destinations'])->name('destinations');

    // 4. Kelola Ketersediaan / Kalender Slot
    Route::get('/ketersediaan', [PartnerController::class, 'availability'])->name('availability');
    Route::post('/ketersediaan/slot/update', [PartnerController::class, 'updateSlot'])->name('slot.update');

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

// Helper: Quick Login Demo Mitra
    Route::get('/quick-login', [PartnerController::class, 'quickLogin'])->name('quick-login');
});

/*
|--------------------------------------------------------------------------
| Destinara Platform — Layer 4: Admin Operasional
|--------------------------------------------------------------------------
| 7 Halaman Konsol Admin Operasional sesuai PRD v1.0 & Figma
*/
use App\Http\Controllers\AdminController;

Route::prefix('admin')->name('admin.')->group(function () {
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

    // Helper: Quick Login Demo Admin
    Route::get('/quick-login', [AdminController::class, 'quickLogin'])->name('quick-login');
});

/*
|--------------------------------------------------------------------------
| Destinara Platform — Layer 5: Super Admin (Dewan Direksi & Kontrol Ekosistem)
|--------------------------------------------------------------------------
| 5 Halaman Eksekutif Super Admin sesuai PRD v1.0 & Figma
*/
use App\Http\Controllers\SuperAdminController;

Route::prefix('superadmin')->name('superadmin.')->group(function () {
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

    // Helper: Quick Login Demo Super Admin
    Route::get('/quick-login', [SuperAdminController::class, 'quickLogin'])->name('quick-login');
});




