<?php

namespace App\Http\Controllers;

use App\Domain\Catalog\Services\ReviewService;
use App\Domain\Payment\Services\PaymentService;
use App\Enums\BookingStatus;
use App\Enums\DestinationStatus;
use App\Enums\PaymentStatus;
use App\Models\Bank;
use App\Models\Booking;
use App\Models\Destination;
use App\Models\NotificationLog;
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

        return view('buyer.booking.show', [
            'user' => $user,
            'institution' => $user->institution,
            'booking' => $booking,
            'isPaid' => $isPaid,
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

        return view('buyer.booking.payment', compact('user', 'booking', 'payment', 'banks'));
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
     * Halaman 8: Notifikasi Buyer
     */
    public function notifications()
    {
        $user = Auth::user();
        $notifications = NotificationLog::where('user_id', $user->id)
            ->orWhere('target', $user->phone)
            ->orWhere('target', $user->email)
            ->latest()
            ->paginate(15);

        return view('buyer.notifications', compact('user', 'notifications'));
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
