<?php

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

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
Route::get('/dokumen/unduh/{document}', [DocumentController::class, 'download'])->middleware('auth')->name('documents.download');

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

Route::prefix('buyer')->name('buyer.')->middleware(['auth', 'active', 'account:buyer', 'verified'])->group(function () {
    // 1. Dashboard Buyer
    Route::get('/dashboard', [BuyerController::class, 'dashboard'])->name('dashboard');

    // 2. Form Pengajuan Booking
    Route::get('/booking/create', [BuyerController::class, 'createBooking'])->name('booking.create');

    // 3. Detail Booking & Status Verifikasi
    Route::get('/booking/{code}', [BuyerController::class, 'showBooking'])->name('booking.show');

    // 4. Pembayaran / Checkout & Simulasi Pelunasan
    Route::get('/booking/{code}/pembayaran', [BuyerController::class, 'payment'])->name('booking.payment');
    Route::post('/booking/{code}/bayar', [BuyerController::class, 'processPayment'])->name('booking.pay');
    Route::post('/booking/{code}/cek-status', [BuyerController::class, 'checkPaymentStatus'])->middleware('throttle:6,1')->name('booking.checkStatus');
    Route::post('/booking/{code}/bukti-transfer', [BuyerController::class, 'reuploadManualProof'])->name('booking.reuploadProof');
    Route::post('/booking/{code}/batal', [BuyerController::class, 'cancelBooking'])->name('booking.cancel');

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
    Route::post('/notifikasi/{id}/read', [BuyerController::class, 'markNotificationAsRead'])->name('notifications.read');
    Route::post('/notifikasi/read-all', [BuyerController::class, 'markAllNotificationsAsRead'])->name('notifications.readAll');

    // 9. Ulasan Kunjungan Selesai
    Route::post('/booking/{code}/ulasan', [BuyerController::class, 'submitReview'])->name('booking.review');
});

// Pengalihan cerdas /dashboard ke portal sesuai tipe akun
Route::get('/dashboard', function () {
    if (! Auth::check()) {
        return redirect()->route('login');
    }
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

Route::prefix('mitra')->name('mitra.')->middleware(['auth', 'active', 'account:partner', 'verified'])->group(function () {
    // 1. Dashboard Mitra (Akses terbuka untuk melengkapi profil)
    Route::get('/dashboard', [PartnerController::class, 'dashboard'])->name('dashboard');

    // 2. Laporan Pendapatan (Read-only Payout)
    Route::get('/pendapatan', [PartnerController::class, 'incomeReport'])->name('income');

    // 3. Profil Pengelola, Rekening Payout & Dokumen Legalitas
    Route::get('/profil', [PartnerController::class, 'profile'])->name('profile');
    Route::post('/profil/update', [PartnerController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profil/dokumen', [PartnerController::class, 'uploadLegalDocument'])->name('profile.document.upload');
    Route::post('/profil/dokumen/{id}/delete', [PartnerController::class, 'deleteLegalDocument'])->name('profile.document.delete');
    Route::post('/profil/ajukan-verifikasi', [PartnerController::class, 'submitVerification'])->name('profile.submitVerification');

    // 4. Pusat Notifikasi Mitra (Database Notifications)
    Route::get('/notifikasi', [PartnerController::class, 'notifications'])->name('notifications');
    Route::post('/notifikasi/{id}/read', [PartnerController::class, 'markNotificationAsRead'])->name('notifications.read');
    Route::post('/notifikasi/read-all', [PartnerController::class, 'markAllNotificationsAsRead'])->name('notifications.readAll');

    // 5. Check-in QR (Satu route saja dengan signed URL - SPEC §11, §8.8)
    Route::get('/checkin/{code}', [PartnerController::class, 'showCheckin'])->middleware('signed')->name('checkin.show');
    Route::post('/checkin/{code}/konfirmasi', [PartnerController::class, 'confirmCheckin'])->name('checkin.confirm');

    // 6. Fitur Operasional yang Memerlukan Mitra Terverifikasi (partner.verified)
    Route::middleware('partner.verified')->group(function () {
        // Kelola Listing Destinasi
        Route::get('/destinasi', [PartnerController::class, 'destinations'])->name('destinations');
        Route::get('/destinasi/tambah', [PartnerController::class, 'createDestination'])->name('destinations.create');
        Route::post('/destinasi/simpan', [PartnerController::class, 'storeDestination'])->name('destinations.store');
        Route::get('/destinasi/{destination}/edit', [PartnerController::class, 'editDestination'])->name('destinations.edit');
        Route::post('/destinasi/{destination}/update', [PartnerController::class, 'updateDestination'])->name('destinations.update');
        Route::post('/destinasi/{destination}/submit', [PartnerController::class, 'submitDestination'])->name('destinations.submit');
        Route::post('/destinasi/{destination}/toggle-active', [PartnerController::class, 'toggleDestinationActive'])->name('destinations.toggleActive');

        // Sub-resources Destinasi Mitra
        Route::post('/destinasi/{destination}/media', [PartnerController::class, 'addDestinationMedia'])->name('destinations.media.add');
        Route::post('/destinasi/{destination}/media/{media}/delete', [PartnerController::class, 'deleteDestinationMedia'])->name('destinations.media.delete');
        Route::post('/destinasi/{destination}/media/sort', [PartnerController::class, 'sortDestinationMedia'])->name('destinations.media.sort');
        Route::post('/destinasi/{destination}/facilities', [PartnerController::class, 'updateDestinationFacilities'])->name('destinations.facilities.update');
        Route::post('/destinasi/{destination}/contacts', [PartnerController::class, 'saveDestinationContact'])->name('destinations.contacts.save');
        Route::post('/destinasi/{destination}/contacts/{contact}/delete', [PartnerController::class, 'deleteDestinationContact'])->name('destinations.contacts.delete');
        Route::post('/destinasi/{destination}/packages', [PartnerController::class, 'saveDestinationPackage'])->name('destinations.packages.save');
        Route::post('/destinasi/{destination}/packages/{package}/toggle', [PartnerController::class, 'togglePackageActive'])->name('destinations.packages.toggle');
        Route::post('/destinasi/{destination}/packages/{package}/delete', [PartnerController::class, 'deleteDestinationPackage'])->name('destinations.packages.delete');
        Route::post('/destinasi/{destination}/addons', [PartnerController::class, 'saveDestinationAddon'])->name('destinations.addons.save');
        Route::post('/destinasi/{destination}/addons/{addon}/delete', [PartnerController::class, 'deleteDestinationAddon'])->name('destinations.addons.delete');

        // Kelola Ketersediaan / Kalender Slot
        Route::get('/ketersediaan', [PartnerController::class, 'availability'])->name('availability');
        Route::post('/ketersediaan/slot/update', [PartnerController::class, 'updateSlot'])->name('slot.update');
        Route::post('/ketersediaan/bulk', [PartnerController::class, 'bulkGenerateSlots'])->name('slots.bulk');

        // Booking Masuk & Konfirmasi / Tolak
        Route::get('/booking', [PartnerController::class, 'incomingBookings'])->name('bookings');
        Route::post('/booking/{id}/terima', [PartnerController::class, 'confirmBooking'])->name('booking.confirm');
        Route::post('/booking/{id}/tolak', [PartnerController::class, 'rejectBooking'])->name('booking.reject');

        // Balas Ulasan Pembeli
        Route::post('/ulasan/{id}/balas', [PartnerController::class, 'replyReview'])->name('reviews.reply');
    });
});

// Route alias for partner dashboard
Route::get('/partner/dashboard', fn () => redirect()->route('mitra.dashboard'))->name('partner.dashboard');

/*
|--------------------------------------------------------------------------
| Destinara Platform — Layer 4: Admin Panel Terpadu (SPEC §14.4)
|--------------------------------------------------------------------------
| Seluruh modul operasional & dewan eksekutif disatukan dalam prefix /admin
| dengan pengamanan akun internal dan sistem izin granular Spatie (SPEC §4)
*/
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DocumentAdminController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\HealthController;
use App\Http\Controllers\Admin\MasterDataController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SiteContentController;
use App\Http\Controllers\Admin\TeamController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VoucherController;
use App\Http\Controllers\AdminController;

// Pengalihan permanen URL lama superadmin ke admin (SPEC §13, §14.4)
Route::redirect('/superadmin/{any?}', '/admin/{any?}', 301)->where('any', '.*');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'active', 'account:internal'])->group(function () {
    // 1. Dashboard Operasional & Eksekutif (SPEC §14.4)
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->middleware('can:dashboard.view')->name('dashboard');

    // 2. Destinasi (CRUD Penuh untuk Admin)
    Route::get('/destinasi', [AdminController::class, 'destinations'])->middleware('can:destinations.view')->name('destinations.index');
    Route::get('/destinasi/tambah', [AdminController::class, 'createDestination'])->middleware('can:destinations.create')->name('destinations.create');
    Route::post('/destinasi', [AdminController::class, 'storeDestination'])->middleware('can:destinations.create')->name('destinations.store');
    Route::get('/destinasi/verifikasi', [AdminController::class, 'verifyDestinations'])->middleware('can:destinations.verify')->name('destinations.verify');
    Route::post('/destinasi/{id}/status', [AdminController::class, 'updateDestinationStatus'])->middleware('can:destinations.verify')->name('destinations.updateStatus');

    Route::get('/destinasi/{destination}', [AdminController::class, 'editDestination'])->middleware('can:destinations.update')->name('destinations.edit');
    Route::post('/destinasi/{destination}/update', [AdminController::class, 'updateDestination'])->middleware('can:destinations.update')->name('destinations.update');
    Route::post('/destinasi/{destination}/featured', [AdminController::class, 'toggleDestinationFeatured'])->middleware('can:destinations.feature')->name('destinations.toggleFeatured');
    Route::post('/destinasi/{destination}/toggle-active', [AdminController::class, 'toggleDestinationActive'])->middleware('can:destinations.update')->name('destinations.toggleActive');

    // Sub-resources Destinasi Admin
    Route::post('/destinasi/{destination}/media', [AdminController::class, 'addDestinationMedia'])->middleware('can:destinations.update')->name('destinations.media.add');
    Route::post('/destinasi/{destination}/media/{media}/delete', [AdminController::class, 'deleteDestinationMedia'])->middleware('can:destinations.update')->name('destinations.media.delete');
    Route::post('/destinasi/{destination}/media/sort', [AdminController::class, 'sortDestinationMedia'])->middleware('can:destinations.update')->name('destinations.media.sort');
    Route::post('/destinasi/{destination}/facilities', [AdminController::class, 'updateDestinationFacilities'])->middleware('can:destinations.update')->name('destinations.facilities.update');
    Route::post('/destinasi/{destination}/contacts', [AdminController::class, 'saveDestinationContact'])->middleware('can:destinations.update')->name('destinations.contacts.save');
    Route::post('/destinasi/{destination}/contacts/{contact}/delete', [AdminController::class, 'deleteDestinationContact'])->middleware('can:destinations.update')->name('destinations.contacts.delete');
    Route::post('/destinasi/{destination}/packages', [AdminController::class, 'saveDestinationPackage'])->middleware('can:packages.manage')->name('destinations.packages.save');
    Route::post('/destinasi/{destination}/packages/{package}/toggle', [AdminController::class, 'togglePackageActive'])->middleware('can:packages.manage')->name('destinations.packages.toggle');
    Route::post('/destinasi/{destination}/packages/{package}/delete', [AdminController::class, 'deleteDestinationPackage'])->middleware('can:packages.manage')->name('destinations.packages.delete');
    Route::post('/destinasi/{destination}/addons', [AdminController::class, 'saveDestinationAddon'])->middleware('can:addons.manage')->name('destinations.addons.save');
    Route::post('/destinasi/{destination}/addons/{addon}/delete', [AdminController::class, 'deleteDestinationAddon'])->middleware('can:addons.manage')->name('destinations.addons.delete');
    Route::post('/destinasi/{destination}/slots/bulk', [AdminController::class, 'bulkGenerateDestinationSlots'])->middleware('can:availability.manage')->name('destinations.slots.bulk');
    Route::post('/destinasi/{destination}/slots/update', [AdminController::class, 'updateDestinationSlot'])->middleware('can:availability.manage')->name('destinations.slots.update');

    // 4. Pengguna & Validasi Legalitas
    Route::get('/verifikasi-pengguna', [AdminController::class, 'verifyUsers'])->middleware('can:institutions.verify')->name('users.verify');
    Route::post('/verifikasi-pengguna/{type}/{id}', [AdminController::class, 'updateUserVerification'])->middleware('can:institutions.verify')->name('users.updateVerification');
    Route::get('/pengguna', [UserController::class, 'index'])->middleware('can:users.view')->name('users.index');
    Route::post('/pengguna/{user}/status', [UserController::class, 'toggleStatus'])->middleware('can:users.manage')->name('users.toggleStatus');
    Route::post('/pengguna/{user}/reset-password', [UserController::class, 'sendPasswordReset'])->middleware('can:users.manage')->name('users.sendPasswordReset');
    Route::post('/pengguna/{user}/verify-email', [UserController::class, 'verifyEmail'])->middleware('can:users.manage')->name('users.verifyEmail');
    Route::post('/pengguna/{user}/komisi', [UserController::class, 'updatePartnerCommission'])->middleware('can:partners.update')->name('users.updateCommission');

    // 5. Reservasi / Booking Lintas Destinasi
    Route::get('/booking', [AdminController::class, 'manageBookings'])->middleware('can:bookings.view')->name('bookings.index');
    Route::get('/booking/{code}', [AdminController::class, 'showBooking'])->middleware('can:bookings.view')->name('bookings.show');
    Route::post('/booking/{id}/status', [AdminController::class, 'updateBookingStatus'])->middleware('can:bookings.update')->name('bookings.updateStatus');

    // 6. Keuangan: Pembayaran, Refund, Payout, Rekonsiliasi
    Route::prefix('keuangan')->name('finance.')->group(function () {
        Route::get('/pembayaran', [FinanceController::class, 'payments'])->middleware('can:payments.view')->name('payments');
        Route::post('/pembayaran/{payment}/verifikasi', [FinanceController::class, 'verifyManualPayment'])->middleware('can:payments.verify_manual')->name('verifyManual');
        Route::post('/pembayaran/{payment}/cek-status', [FinanceController::class, 'checkGatewayStatus'])->middleware('can:payments.view')->name('checkGatewayStatus');

        Route::get('/refund', [FinanceController::class, 'refunds'])->middleware('can:refunds.view')->name('refunds');
        Route::post('/refund/{refund}/proses', [FinanceController::class, 'processRefund'])->middleware('can:refunds.process')->name('processRefund');
        Route::post('/refund/{refund}/tolak', [FinanceController::class, 'rejectRefund'])->middleware('can:refunds.process')->name('rejectRefund');

        Route::get('/payout', [FinanceController::class, 'payouts'])->middleware('can:payouts.view')->name('payouts');
        Route::post('/payout/proses', [FinanceController::class, 'processPayout'])->middleware('can:payouts.process')->name('processPayout');
        Route::post('/payout/{payout}/hold', [FinanceController::class, 'holdPayout'])->middleware('can:payouts.process')->name('holdPayout');
        Route::post('/payout/{payout}/release-hold', [FinanceController::class, 'releaseHoldPayout'])->middleware('can:payouts.process')->name('releaseHoldPayout');

        Route::get('/rekonsiliasi', [FinanceController::class, 'reconciliation'])->middleware('can:reconciliation.view')->name('reconciliation');
        Route::post('/rekonsiliasi/tutup-buku', [FinanceController::class, 'closePeriod'])->middleware('can:reconciliation.close')->name('closePeriod');
        Route::get('/rekonsiliasi/export-csv', [FinanceController::class, 'exportCsv'])->middleware('can:reconciliation.view')->name('exportCsv');
    });

    // 7. Dokumen Arsip & Template Dokumen
    Route::get('/dokumen', [DocumentAdminController::class, 'index'])->middleware('can:documents.view')->name('documents.index');
    Route::get('/dokumen/{document}/preview', [DocumentAdminController::class, 'preview'])->middleware('can:documents.view')->name('documents.preview');
    Route::post('/dokumen/{document}/regenerate', [DocumentAdminController::class, 'regenerate'])->middleware('can:documents.regenerate')->name('documents.regenerate');
    Route::post('/dokumen/{document}/void', [DocumentAdminController::class, 'void'])->middleware('can:documents.regenerate')->name('documents.void');
    Route::get('/dokumen-templates', [DocumentAdminController::class, 'templates'])->middleware('can:documents.templates')->name('documents.templates');
    Route::post('/dokumen-templates/{template}', [DocumentAdminController::class, 'updateTemplate'])->middleware('can:documents.templates')->name('documents.templates.update');
    Route::get('/dokumen-templates/{template}/preview', [DocumentAdminController::class, 'previewTemplate'])->middleware('can:documents.templates')->name('documents.templates.preview');

    // 8. Moderasi Ulasan
    Route::get('/ulasan', [ReviewController::class, 'index'])->middleware('can:reviews.moderate')->name('reviews.index');
    Route::post('/ulasan/{review}/moderasi', [ReviewController::class, 'moderate'])->middleware('can:reviews.moderate')->name('reviews.moderate');

    // 9. Kelola Voucher Diskon
    Route::get('/voucher', [VoucherController::class, 'index'])->middleware('can:vouchers.manage')->name('vouchers.index');
    Route::post('/voucher', [VoucherController::class, 'store'])->middleware('can:vouchers.manage')->name('vouchers.store');
    Route::post('/voucher/{voucher}/update', [VoucherController::class, 'update'])->middleware('can:vouchers.manage')->name('vouchers.update');
    Route::post('/voucher/{voucher}/delete', [VoucherController::class, 'destroy'])->middleware('can:vouchers.manage')->name('vouchers.delete');

    // 10. Notifikasi & Log Queue
    Route::get('/notifikasi', [AdminController::class, 'notificationLogs'])->middleware('can:notifications.view')->name('notifications.index');
    Route::post('/notifikasi/{id}/retry', [AdminController::class, 'retryNotification'])->middleware('can:notifications.retry')->name('notifications.retry');
    Route::get('/notifikasi/template', [AdminController::class, 'notificationTemplates'])->middleware('can:notifications.templates')->name('notifications.templates');
    Route::post('/notifikasi/template/{template}', [AdminController::class, 'updateNotificationTemplate'])->middleware('can:notifications.templates')->name('notifications.templates.update');

    // 11. Master Data Platform
    Route::get('/master-data', [MasterDataController::class, 'index'])->middleware('can:master_data.manage')->name('master.index');
    Route::post('/master-data/{type}', [MasterDataController::class, 'store'])->middleware('can:master_data.manage')->name('master.store');
    Route::post('/master-data/{type}/{id}/update', [MasterDataController::class, 'update'])->middleware('can:master_data.manage')->name('master.update');
    Route::post('/master-data/{type}/{id}/delete', [MasterDataController::class, 'destroy'])->middleware('can:master_data.manage')->name('master.destroy');

    // 12. Konten Halaman (CMS)
    Route::get('/konten-halaman', [SiteContentController::class, 'index'])->middleware('can:site_content.manage')->name('siteContents.index');
    Route::post('/konten-halaman/{siteContent}', [SiteContentController::class, 'update'])->middleware('can:site_content.manage')->name('siteContents.update');

    // 13. Tim Internal & Manajemen Peran/Role
    Route::get('/tim-internal', [TeamController::class, 'index'])->middleware('can:internal_users.manage')->name('team.index');
    Route::post('/tim-internal/user', [TeamController::class, 'storeUser'])->middleware('can:internal_users.manage')->name('team.user.store');
    Route::post('/tim-internal/user/{user}/update', [TeamController::class, 'updateUser'])->middleware('can:internal_users.manage')->name('team.user.update');
    Route::post('/tim-internal/user/{user}/delete', [TeamController::class, 'deleteUser'])->middleware('can:internal_users.manage')->name('team.user.delete');
    Route::post('/tim-internal/role', [TeamController::class, 'storeRole'])->middleware('can:roles.manage')->name('team.role.store');
    Route::post('/tim-internal/role/{role}/update', [TeamController::class, 'updateRole'])->middleware('can:roles.manage')->name('team.role.update');

    // 14. Pengaturan Sistem
    Route::get('/pengaturan', [SettingsController::class, 'index'])->middleware('can:settings.general')->name('settings.index');
    Route::post('/pengaturan', [SettingsController::class, 'update'])->middleware('can:settings.general')->name('settings.update');
    Route::post('/pengaturan/test/{target}', [SettingsController::class, 'testConnection'])->middleware('can:settings.general')->name('settings.test');

    // 15. Audit Log Ekosistem
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->middleware('can:audit_logs.view')->name('auditLogs.index');
    Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show'])->middleware('can:audit_logs.view')->name('auditLogs.show');

    // 16. Analitik Bisnis & Laporan Makro
    Route::get('/analitik', [AnalyticsController::class, 'index'])->middleware('can:analytics.view')->name('analytics.index');
    Route::get('/analitik/export-csv', [AnalyticsController::class, 'exportCsv'])->middleware('can:analytics.view')->name('analytics.exportCsv');

    // 17. System Health Diagnostics
    Route::get('/system-health', [HealthController::class, 'index'])->middleware('can:system_health.view')->name('health.index');
});
