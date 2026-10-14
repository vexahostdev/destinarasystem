<?php

namespace App\Http\Controllers;

use App\Domain\Booking\Services\BookingService;
use App\Domain\Catalog\Services\ReviewService;
use App\Domain\Payment\Services\PaymentService;
use App\Enums\BookingStatus;
use App\Enums\DestinationStatus;
use App\Enums\PaymentStatus;
use App\Models\Bank;
use App\Models\Booking;
use App\Models\Destination;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BuyerController extends Controller
{
    /**
     * Halaman 1: Dashboard Buyer
     */
    public function dashboard(Request $request)
    {
        $user = Auth::user();
        $institution = $user->institution;

        // Metrik Rombongan Real
        $activeBookingsCount = Booking::where('user_id', $user->id)
            ->whereIn('status', [
                BookingStatus::MENUNGGU_PEMBAYARAN,
                BookingStatus::DIBAYAR,
                BookingStatus::DIKONFIRMASI,
                BookingStatus::BERLANGSUNG,
            ])
            ->count();

        $pendingPaymentCount = Booking::where('user_id', $user->id)
            ->where('status', BookingStatus::MENUNGGU_PEMBAYARAN)
            ->count();

        $confirmedCount = Booking::where('user_id', $user->id)
            ->where('status', BookingStatus::DIKONFIRMASI)
            ->count();

        $completedCount = Booking::where('user_id', $user->id)
            ->where('status', BookingStatus::SELESAI)
            ->count();

        // Jadwal Kunjungan Terdekat
        $nextBooking = Booking::with(['destination.province', 'payments', 'documents'])
            ->where('user_id', $user->id)
            ->whereIn('status', [
                BookingStatus::MENUNGGU_PEMBAYARAN,
                BookingStatus::DIBAYAR,
                BookingStatus::DIKONFIRMASI,
                BookingStatus::BERLANGSUNG,
            ])
            ->orderBy('planned_date_start', 'asc')
            ->first();

        // Daftar Booking Aktif Terbaru
        $recentBookings = Booking::with(['destination.province', 'payments', 'package'])
            ->where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        // Rekomendasi Destinasi Terverifikasi
        $recommendedDestinations = Destination::with(['province', 'media', 'packages'])
            ->where('status', DestinationStatus::PUBLISHED)
            ->take(3)
            ->get();

        return view('buyer.dashboard', compact(
            'user',
            'institution',
            'activeBookingsCount',
            'pendingPaymentCount',
            'confirmedCount',
            'completedCount',
            'nextBooking',
            'recentBookings',
            'recommendedDestinations'
        ));
    }

    /**
     * Halaman 2: Form Pengajuan Booking Baru (Livewire Wrapper)
     */
    public function createBooking(Request $request)
    {
        $user = Auth::user();
        $institution = $user->institution;

        $destinations = Destination::where('status', DestinationStatus::PUBLISHED)->get();

        $selectedSlug = $request->query('destinasi') ?? $request->query('tapak');
        $selectedDestination = $selectedSlug
            ? Destination::with(['packages', 'addons'])->where('slug', $selectedSlug)->first()
            : $destinations->first();

        if (! $selectedDestination && $destinations->isNotEmpty()) {
            $selectedDestination = $destinations->first();
            $selectedDestination->load(['packages', 'addons']);
        }

        // Reorder: strictly verify ownership (SPEC D3)
        $reorderCode = $request->query('reorder');
        $reorderData = null;
        if ($reorderCode) {
            $reorderData = Booking::where('booking_code', $reorderCode)
                ->where('user_id', $user->id)
                ->with(['destination.packages', 'destination.addons', 'package'])
                ->first();

            if ($reorderData && $reorderData->destination) {
                $selectedDestination = $reorderData->destination;
            }
        }

        return view('buyer.booking.create', compact(
            'user',
            'institution',
            'destinations',
            'selectedDestination',
            'reorderData'
        ));
    }

    /**
     * Halaman 3: Detail Booking & Status Verifikasi
     */
    public function showBooking(string $code)
    {
        $user = Auth::user();

        $booking = Booking::with([
            'destination.contacts',
            'destination.province',
            'destination.media',
            'destination.cancellationPolicy.tiers',
            'package',
            'items',
            'payments',
            'documents',
            'review',
            'institution',
        ])
            ->where('booking_code', $code)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $isPaid = in_array($booking->status, [
            BookingStatus::DIBAYAR,
            BookingStatus::DIKONFIRMASI,
            BookingStatus::BERLANGSUNG,
            BookingStatus::SELESAI,
        ]);
        $bookingService = app(BookingService::class);
        $refundCalc = $bookingService->calculateRefund($booking, 'buyer');
        $banks = Bank::where('is_active', true)->orderBy('name')->get();
        $canCancel = (bool) setting('allow_buyer_cancel', true) && in_array($booking->status, [
            BookingStatus::MENUNGGU_PEMBAYARAN,
            BookingStatus::DIBAYAR,
            BookingStatus::DIKONFIRMASI,
        ]);
        $latestPayment = $booking->payments()->latest('id')->first();
        $manualPayment = $booking->payments()->where('gateway', 'manual_transfer')->latest('id')->first();

        return view('buyer.booking.show', [
            'user' => $user,
            'institution' => $user->institution,
            'booking' => $booking,
            'isPaid' => $isPaid,
            'refundCalc' => $refundCalc,
            'banks' => $banks,
            'canCancel' => $canCancel,
            'latestPayment' => $latestPayment,
            'manualPayment' => $manualPayment,
        ]);
    }

    /**
     * Halaman 4: Kasir & Pembayaran Resmi
     */
    public function payment(string $code, PaymentService $paymentService)
    {
        $user = Auth::user();

        $booking = Booking::with(['destination', 'payments', 'package', 'institution'])
            ->where('booking_code', $code)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($booking->status !== BookingStatus::MENUNGGU_PEMBAYARAN) {
            return redirect()->route('buyer.booking.show', $booking->booking_code)
                ->with('info', 'Pesanan ini tidak sedang menunggu pembayaran.');
        }

        $payment = $booking->payments()->where('status', PaymentStatus::PENDING)->latest()->first();

        // If no pending payment exists but booking is unpaid, create a new attempt
        if (! $payment) {
            $payment = $paymentService->retryPayment($booking);
        }

        $banks = Bank::where('is_active', true)->get();
        $enabledGateways = setting('enabled_gateways', ['xendit', 'midtrans', 'manual_transfer']);

        return view('buyer.booking.payment', compact('user', 'booking', 'payment', 'banks', 'enabledGateways'));
    }

    /**
     * Proses Checkout / Pembayaran
     */
    public function processPayment(Request $request, string $code, PaymentService $paymentService)
    {
        $user = Auth::user();

        $booking = Booking::where('booking_code', $code)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $request->validate([
            'gateway' => 'required|in:xendit,midtrans,manual_transfer',
            'proof_file' => 'required_if:gateway,manual_transfer|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $enabledGateways = setting('enabled_gateways', ['xendit', 'midtrans', 'manual_transfer']);
        if (! in_array($request->gateway, $enabledGateways, true)) {
            return back()->with('error', 'Metode pembayaran yang dipilih sedang tidak tersedia atau dinonaktifkan.');
        }

        $gateway = $request->gateway;
        $payment = $booking->payments()->where('status', PaymentStatus::PENDING)->latest()->first();

        if (! $payment || $payment->gateway !== $gateway) {
            $payment = $paymentService->retryPayment($booking, $gateway);
        }

        // If manual transfer, store the uploaded proof
        if ($gateway === 'manual_transfer') {
            $paymentService->processManualProofUpload($payment, $request->file('proof_file'), $user);

            return redirect()->route('buyer.booking.show', $booking->booking_code)
                ->with('success', 'Bukti transfer manual berhasil diunggah! Tim Destinara akan memverifikasi dalam waktu 1x24 jam.');
        }

        // Online Gateway: initiate payment and redirect to checkout_url
        $initiated = $paymentService->initiatePayment($payment);

        if ($initiated->checkout_url) {
            return redirect()->away($initiated->checkout_url);
        }

        return redirect()->route('buyer.booking.payment', $booking->booking_code)
            ->with('info', 'Tagihan pembayaran telah disiapkan. Silakan selesaikan transaksi sesuai instruksi gateway.');
    }

    /**
     * Halaman 5: Riwayat Transaksi & Re-order (Paginated)
     */
    public function history(Request $request)
    {
        $user = Auth::user();
        $institution = $user->institution;

        $statusFilter = $request->query('status', 'semua');

        $query = Booking::with(['destination.province', 'destination.media', 'payments', 'documents'])
            ->where('user_id', $user->id);

        if ($statusFilter && $statusFilter !== 'semua') {
            $query->where('status', $statusFilter);
        }

        $bookings = $query->latest()->paginate(10);

        // Metric summaries strictly calculated from real DB records
        $totalExpenditure = Booking::where('user_id', $user->id)
            ->whereIn('status', [
                BookingStatus::DIBAYAR,
                BookingStatus::DIKONFIRMASI,
                BookingStatus::BERLANGSUNG,
                BookingStatus::SELESAI,
            ])
            ->sum('total_amount');

        $totalPax = Booking::where('user_id', $user->id)
            ->whereIn('status', [
                BookingStatus::DIBAYAR,
                BookingStatus::DIKONFIRMASI,
                BookingStatus::BERLANGSUNG,
                BookingStatus::SELESAI,
            ])
            ->sum('participant_count');

        return view('buyer.history', compact(
            'user',
            'institution',
            'bookings',
            'statusFilter',
            'totalExpenditure',
            'totalPax'
        ));
    }

    /**
     * Halaman 6: Profil Institusi
     */
    public function profile()
    {
        $user = Auth::user();
        $institution = $user->institution;

        return view('buyer.profile', compact('user', 'institution'));
    }

    /**
     * Simpan Perubahan Profil Institusi
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $institution = $user->institution;

        $request->validate([
            'institution_name' => 'required|string|max:255',
            'institution_type' => 'required|string',
            'npsn' => 'nullable|string|max:50',
            'address' => 'required|string',
            'city' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'pic_name' => 'required|string|max:255',
            'pic_position' => 'required|string|max:255',
            'pic_phone' => 'required|string|max:50',
            'pic_email' => 'required|email|max:255',
        ]);

        $institution->update([
            'institution_name' => $request->institution_name,
            'institution_type' => $request->institution_type,
            'npsn' => $request->npsn,
            'address' => $request->address,
            'city' => $request->city,
            'province' => $request->province,
            'pic_name' => $request->pic_name,
            'pic_position' => $request->pic_position,
            'pic_phone' => $request->pic_phone,
            'pic_email' => $request->pic_email,
        ]);

        $user->update([
            'name' => $request->pic_name,
            'phone' => $request->pic_phone,
        ]);

        return redirect()->route('buyer.profile')
            ->with('success', 'Profil institusi dan legalitas penanggung jawab berhasil diperbarui.');
    }

    /**
     * Halaman 7: Wishlist Destinasi
     */
    public function wishlist()
    {
        $user = Auth::user();
        $wishlists = Wishlist::with(['destination.province', 'destination.media', 'destination.packages'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(12);

        return view('buyer.wishlist', compact('user', 'wishlists'));
    }

    /**
     * Toggle Wishlist
     */
    public function toggleWishlist(Request $request)
    {
        $request->validate(['destination_id' => 'required|exists:destinations,id']);
        $user = Auth::user();
        $destId = (int) $request->destination_id;

        $existing = Wishlist::where('user_id', $user->id)->where('destination_id', $destId)->first();
        if ($existing) {
            $existing->delete();
            $status = 'removed';
        } else {
            Wishlist::create([
                'user_id' => $user->id,
                'destination_id' => $destId,
            ]);
            $status = 'added';
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'status' => $status]);
        }

        return back()->with('success', $status === 'added' ? 'Destinasi ditambahkan ke wishlist.' : 'Destinasi dihapus dari wishlist.');
    }

    /**
     * Batal Mandiri oleh Pemesan (SPEC §8.4)
     */
    public function cancelBooking(Request $request, string $code, BookingService $bookingService)
    {
        $user = Auth::user();
        $booking = Booking::where('booking_code', $code)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if (! (bool) setting('allow_buyer_cancel', true)) {
            abort(403, 'Fitur pembatalan mandiri oleh pemesan saat ini sedang dinonaktifkan.');
        }

        $calc = $bookingService->calculateRefund($booking, 'buyer');
        $bankDetails = null;

        if ($calc['percent'] > 0) {
            $validated = $request->validate([
                'reason' => 'required|string|min:5|max:500',
                'bank_name' => 'required|string|max:100',
                'bank_account_number' => 'required|string|max:50',
                'bank_account_holder' => 'required|string|max:255',
            ]);
            $bankDetails = [
                'bank_name' => $validated['bank_name'],
                'account_number' => $validated['bank_account_number'],
                'account_holder' => $validated['bank_account_holder'],
            ];
            $reason = $validated['reason'];
        } else {
            $validated = $request->validate([
                'reason' => 'required|string|min:5|max:500',
            ]);
            $reason = $validated['reason'];
        }

        try {
            $bookingService->cancel(
                booking: $booking,
                actor: $user,
                reason: $reason,
                source: 'buyer',
                overridePercent: null,
                bankDetails: $bankDetails
            );

            return redirect()->route('buyer.booking.show', $booking->booking_code)
                ->with('success', 'Pesanan Anda berhasil dibatalkan.'.($calc['percent'] > 0 ? " Pengajuan refund {$calc['percent']}% telah diteruskan ke tim keuangan." : ''));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cek Status Transaksi ke Payment Gateway
     */
    public function checkPaymentStatus(string $code, PaymentService $paymentService)
    {
        $user = Auth::user();
        $booking = Booking::where('booking_code', $code)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $payment = $booking->payments()->latest('id')->first();
        if (! $payment) {
            return back()->with('error', 'Tidak ada data transaksi pembayaran untuk pesanan ini.');
        }

        try {
            $paymentService->checkStatus($payment);
            $booking->refresh();

            return redirect()->route('buyer.booking.show', $booking->booking_code)
                ->with('info', "Status pembayaran terkini: {$payment->fresh()->status->label()}. Status pesanan: {$booking->status->label()}.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memeriksa status ke gateway: '.$e->getMessage());
        }
    }

    /**
     * Upload Ulang Bukti Transfer Manual
     */
    public function reuploadManualProof(Request $request, string $code, PaymentService $paymentService)
    {
        $user = Auth::user();
        $booking = Booking::where('booking_code', $code)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $request->validate([
            'proof_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $payment = $booking->payments()
            ->where('gateway', 'manual_transfer')
            ->latest('id')
            ->first();

        if (! $payment) {
            return back()->with('error', 'Tidak ditemukan riwayat pembayaran transfer manual.');
        }

        try {
            $paymentService->processManualProofUpload($payment, $request->file('proof_file'), $user);

            return redirect()->route('buyer.booking.show', $booking->booking_code)
                ->with('success', 'Bukti transfer pembayaran berhasil diunggah ulang. Tim Destinara akan memverifikasi dalam waktu 1x24 jam.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Halaman 8: Notifikasi Buyer (Database Notifications)
     */
    public function notifications()
    {
        $user = Auth::user();
        $notifications = $user->notifications()->latest()->paginate(15);

        return view('buyer.notifications', compact('user', 'notifications'));
    }

    public function markNotificationAsRead($id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return redirect()->back()->with('success', 'Notifikasi ditandai sudah dibaca.');
    }

    public function markAllNotificationsAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();

        return redirect()->back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }

    /**
     * Kirim Ulasan Kunjungan Selesai
     */
    public function submitReview(Request $request, string $code, ReviewService $reviewService)
    {
        $user = Auth::user();
        $booking = Booking::where('booking_code', $code)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:10|max:2000',
        ]);

        $reviewService->submitReview(
            booking: $booking,
            buyer: $user,
            rating: (int) $request->rating,
            comment: $request->comment
        );

        return redirect()->route('buyer.booking.show', $booking->booking_code)
            ->with('success', 'Ulasan dan penilaian berhasil dikirim. Terima kasih atas kontribusi Anda!');
    }
}
