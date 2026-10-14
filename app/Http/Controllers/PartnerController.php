<?php

namespace App\Http\Controllers;

use App\Domain\Booking\Services\AvailabilityService;
use App\Domain\Booking\Services\CheckInService;
use App\Domain\Booking\StateMachine\BookingStateMachine;
use App\Domain\Catalog\Services\AvailabilityCalendarService;
use App\Domain\Catalog\Services\ReviewService;
use App\Domain\Notification\Services\NotificationDispatcher;
use App\Enums\AccountType;
use App\Enums\BookingStatus;
use App\Enums\DestinationStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Jobs\GenerateDocumentJob;
use App\Models\Addon;
use App\Models\AvailabilitySlot;
use App\Models\Bank;
use App\Models\Booking;
use App\Models\CancellationPolicy;
use App\Models\Destination;
use App\Models\DestinationCategory;
use App\Models\NotificationLog;
use App\Models\OrganizationType;
use App\Models\Package;
use App\Models\PartnerPayout;
use App\Models\PartnerProfile;
use App\Models\Province;
use App\Models\Refund;
use App\Models\Review;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PartnerController extends Controller
{
    /**
     * Halaman 1: Dashboard Mitra
     */
    public function dashboard(Request $request)
    {
        $user = Auth::user();
        $profile = $user->partnerProfile;

        $destinationIds = Destination::where('partner_id', $user->id)->pluck('id');

        // Metrik Dashboard Real
        $pendingBookingsCount = Booking::whereIn('destination_id', $destinationIds)
            ->whereIn('status', [BookingStatus::MENUNGGU_PEMBAYARAN, BookingStatus::DIBAYAR])
            ->count();

        $confirmedBookingsCount = Booking::whereIn('destination_id', $destinationIds)
            ->where('status', BookingStatus::DIKONFIRMASI)
            ->count();

        $readyBalance = PartnerPayout::where('partner_id', $user->id)
            ->whereIn('status', ['pending', 'scheduled'])
            ->sum('net_amount');

        $totalPaidOut = PartnerPayout::where('partner_id', $user->id)
            ->where('status', 'transferred')
            ->sum('net_amount');

        $activeDestinationsCount = Destination::where('partner_id', $user->id)
            ->where('status', DestinationStatus::PUBLISHED)
            ->count();

        // Urgent Bookings (DIBAYAR, waiting for confirmation, sorted by SLA)
        $urgentBookings = Booking::with(['institution', 'destination'])
            ->whereIn('destination_id', $destinationIds)
            ->where('status', BookingStatus::DIBAYAR)
            ->orderBy('partner_confirm_deadline', 'asc')
            ->take(5)
            ->get();

        $primaryDest = Destination::where('partner_id', $user->id)->first();
        $upcomingSlots = [];
        if ($primaryDest) {
            $upcomingSlots = AvailabilitySlot::where('destination_id', $primaryDest->id)
                ->where('date', '>=', Carbon::today())
                ->orderBy('date', 'asc')
                ->take(7)
                ->get();
        }

        return view('partner.dashboard', compact(
            'user',
            'profile',
            'pendingBookingsCount',
            'confirmedBookingsCount',
            'readyBalance',
            'totalPaidOut',
            'activeDestinationsCount',
            'urgentBookings',
            'primaryDest',
            'upcomingSlots'
        ));
    }

    /**
     * Halaman 2: Laporan Pendapatan (Read-only Payout, Hapus RequestPayout - SPEC §14.3)
     */
    public function incomeReport(Request $request)
    {
        $user = Auth::user();
        $profile = $user->partnerProfile;

        $pendingPayouts = PartnerPayout::where('partner_id', $user->id)
            ->whereIn('status', ['pending', 'scheduled'])
            ->get();
        $readyBalance = $pendingPayouts->sum('net_amount');

        $transferredPayouts = PartnerPayout::where('partner_id', $user->id)
            ->where('status', 'transferred')
            ->get();
        $totalTransferred = $transferredPayouts->sum('net_amount');

        $allPayouts = PartnerPayout::where('partner_id', $user->id)
            ->latest()
            ->paginate(15);

        $monthlyEarnings = [];
        for ($i = 4; $i >= 0; $i--) {
            $monthDate = Carbon::now()->subMonths($i);
            $monthLabel = $monthDate->translatedFormat('M');
            $net = PartnerPayout::where('partner_id', $user->id)
                ->where('status', 'transferred')
                ->whereYear('transferred_at', $monthDate->year)
                ->whereMonth('transferred_at', $monthDate->month)
                ->sum('net_amount');
            $monthlyEarnings[] = [
                'month' => $monthLabel,
                'net' => (int) $net,
            ];
        }

        return view('partner.income', compact(
            'user',
            'profile',
            'readyBalance',
            'totalTransferred',
            'allPayouts',
            'monthlyEarnings'
        ));
    }

    /**
     * Halaman 3: Kelola Listing Destinasi
     */
    public function destinations(Request $request)
    {
        $user = Auth::user();
        $profile = $user->partnerProfile;

        $destinations = Destination::where('partner_id', $user->id)
            ->with(['province', 'category'])
            ->withCount(['bookings', 'availabilitySlots', 'packages'])
            ->latest()
            ->paginate(10);

        return view('partner.destinations.index', compact('user', 'profile', 'destinations'));
    }

    /**
     * Form Ajukan Destinasi Baru
     */
    public function createDestination(Request $request)
    {
        $user = Auth::user();
        $profile = $user->partnerProfile;

        $categories = DestinationCategory::where('is_active', true)->get();
        $provinces = Province::orderBy('name')->get();
        $policies = CancellationPolicy::all();

        return view('partner.destinations.create', compact('user', 'profile', 'categories', 'provinces', 'policies'));
    }

    /**
     * Simpan Pengajuan Destinasi Baru (Status: pending_review)
     */
    public function storeDestination(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:destination_categories,id',
            'province_id' => 'required|exists:provinces,id',
            'cancellation_policy_id' => 'required|exists:cancellation_policies,id',
            'city' => 'required|string|max:100',
            'address' => 'required|string',
            'description' => 'required|string|min:50',
            'default_daily_capacity' => 'required|integer|min:10|max:1000',
        ]);

        $slug = Str::slug($request->name);
        $originalSlug = $slug;
        $counter = 1;
        while (Destination::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-" . $counter++;
        }

        $dest = Destination::create([
            'partner_id' => $user->id,
            'category_id' => $request->category_id,
            'province_id' => $request->province_id,
            'cancellation_policy_id' => $request->cancellation_policy_id,
            'name' => $request->name,
            'slug' => $slug,
            'city' => $request->city,
            'address' => $request->address,
            'description' => $request->description,
            'default_daily_capacity' => (int) $request->default_daily_capacity,
            'status' => DestinationStatus::PENDING_REVIEW,
        ]);

        // Auto create primary contact from partner profile
        if ($user->partnerProfile) {
            $dest->contacts()->create([
                'contact_name' => $user->name,
                'role' => 'Penanggung Jawab Tapak',
                'phone' => $user->phone ?? '081234567890',
                'is_primary' => true,
            ]);
        }

        return redirect()->route('mitra.destinations')
            ->with('success', "Destinasi '{$dest->name}' berhasil diajukan dan sedang menunggu kurasi oleh tim Destinara.");
    }

    /**
     * Halaman 4: Kelola Ketersediaan / Kalender Slot
     */
    public function availability(Request $request)
    {
        $user = Auth::user();
        $profile = $user->partnerProfile;

        $destinations = Destination::where('partner_id', $user->id)->get();
        $selectedSlug = $request->query('destinasi');
        $currentDestination = $selectedSlug 
            ? Destination::where('slug', $selectedSlug)->where('partner_id', $user->id)->first() 
            : $destinations->first();

        $slots = [];
        if ($currentDestination) {
            $slots = AvailabilitySlot::where('destination_id', $currentDestination->id)
                ->where('date', '>=', Carbon::today()->subDays(2))
                ->orderBy('date', 'asc')
                ->take(30)
                ->get();
        }

        return view('partner.availability', compact(
            'user',
            'profile',
            'destinations',
            'currentDestination',
            'slots'
        ));
    }

    /**
     * Bulk Generate Slots via AvailabilityCalendarService
     */
    public function bulkGenerateSlots(Request $request, AvailabilityCalendarService $calendarService)
    {
        $user = Auth::user();

        $request->validate([
            'destination_id' => 'required|exists:destinations,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'days_of_week' => 'required|array|min:1',
            'days_of_week.*' => 'integer|between:0,6',
            'capacity' => 'required|integer|min:1',
        ]);

        $destination = Destination::where('id', $request->destination_id)
            ->where('partner_id', $user->id)
            ->firstOrFail();

        $count = $calendarService->bulkGenerateSlots(
            destination: $destination,
            startDate: Carbon::parse($request->start_date),
            endDate: Carbon::parse($request->end_date),
            daysOfWeek: array_map('intval', $request->days_of_week),
            capacity: (int) $request->capacity
        );

        return redirect()->back()
            ->with('success', "Berhasil membuat/memperbarui {$count} slot ketersediaan pada destinasi {$destination->name}.");
    }

    /**
     * Update Status Slot Tunggal
     */
    public function updateSlot(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'slot_id' => 'required|exists:availability_slots,id',
            'status' => 'required|in:open,closed',
            'capacity' => 'required|integer|min:1|max:1000',
        ]);

        $slot = AvailabilitySlot::with('destination')->findOrFail($request->slot_id);
        if ($slot->destination->partner_id !== $user->id) {
            abort(403);
        }

        $minAllowed = $slot->booked_count + $slot->held_count;
        if ((int) $request->capacity < $minAllowed) {
            return redirect()->back()->with('error', "Kapasitas tidak dapat diturunkan di bawah kuota yang telah dipesan/held ({$minAllowed} pax).");
        }

        $slot->status = $request->status;
        $slot->capacity = (int) $request->capacity;
        $slot->save();

        return redirect()->back()->with('success', 'Slot tanggal ' . Carbon::parse($slot->date)->translatedFormat('d M Y') . ' berhasil diperbarui.');
    }

    /**
     * Halaman 5: Daftar Booking Masuk
     */
    public function incomingBookings(Request $request)
    {
        $user = Auth::user();
        $profile = $user->partnerProfile;

        $destinationIds = Destination::where('partner_id', $user->id)->pluck('id');
        $statusFilter = $request->query('status', 'semua');

        $query = Booking::with(['institution', 'destination.province', 'payments', 'package'])
            ->whereIn('destination_id', $destinationIds);

        if ($statusFilter && $statusFilter !== 'semua') {
            $query->where('status', $statusFilter);
        }

        $bookings = $query->orderBy('planned_date_start', 'asc')->paginate(15);

        return view('partner.bookings.index', compact(
            'user',
            'profile',
            'bookings',
            'statusFilter'
        ));
    }

    /**
     * Konfirmasi Booking Masuk oleh Mitra (SPEC §8.3)
     */
    public function confirmBooking(
        $id,
        BookingStateMachine $stateMachine,
        AvailabilityService $availabilityService,
        NotificationDispatcher $notifier
    ) {
        $user = Auth::user();
        $destinationIds = Destination::where('partner_id', $user->id)->pluck('id');

        $booking = Booking::with(['institution', 'destination', 'user'])
            ->whereIn('destination_id', $destinationIds)
            ->findOrFail($id);

        if ($booking->status !== BookingStatus::DIBAYAR) {
            return redirect()->back()->with('error', 'Hanya booking berstatus DIBAYAR yang dapat dikonfirmasi oleh mitra.');
        }

        DB::transaction(function () use ($booking, $user, $stateMachine, $availabilityService, $notifier) {
            $locked = Booking::where('id', $booking->id)->lockForUpdate()->first();

            // 1. Transition State Machine
            $stateMachine->transition(
                booking: $locked,
                to: BookingStatus::DIKONFIRMASI,
                actor: $user,
                note: "Dikonfirmasi kesiapannya oleh pengelola tapak ({$user->name})."
            );

            // 2. Dispatch e-Voucher & Surat Izin Generation
            GenerateDocumentJob::dispatch($locked, \App\Enums\DocumentType::E_VOUCHER, $user);
            if ($locked->needs_permit_letter) {
                GenerateDocumentJob::dispatch($locked, \App\Enums\DocumentType::PERMIT_DRAFT, $user);
            }

            // 3. Dispatch Notification
            $notifier->send('booking.confirmed', [
                'booking' => $locked,
                'user' => $locked->user,
                'destination_name' => $locked->destination->name,
                'booking_code' => $locked->booking_code,
            ], ['buyer', 'partner']);
        });

        return redirect()->back()->with('success', "Pesanan #{$booking->booking_code} berhasil dikonfirmasi! Dokumen e-Pass dan surat izin diterbitkan.");
    }

    /**
     * Tolak Booking Masuk oleh Mitra (SPEC §8.3 & §8.6)
     */
    public function rejectBooking(
        Request $request,
        $id,
        BookingStateMachine $stateMachine,
        AvailabilityService $availabilityService,
        NotificationDispatcher $notifier
    ) {
        $user = Auth::user();
        $destinationIds = Destination::where('partner_id', $user->id)->pluck('id');

        $booking = Booking::with(['institution', 'destination', 'payments', 'user'])
            ->whereIn('destination_id', $destinationIds)
            ->findOrFail($id);

        $request->validate([
            'reason' => 'required|string|min:5|max:500',
        ]);

        $reason = $request->reason;

        DB::transaction(function () use ($booking, $user, $reason, $stateMachine, $availabilityService, $notifier) {
            $locked = Booking::where('id', $booking->id)->lockForUpdate()->first();

            // 1. Transition to BATAL
            $stateMachine->transition(
                booking: $locked,
                to: BookingStatus::BATAL,
                actor: $user,
                note: "Ditolak oleh pengelola tapak. Alasan: {$reason}"
            );

            // 2. Release allocated slots
            $availabilityService->releaseSlots($locked);

            // 3. Create 100% full refund record for buyer
            $paidPayment = $locked->payments()->where('status', PaymentStatus::PAID)->latest()->first();
            if ($paidPayment) {
                Refund::create([
                    'booking_id' => $locked->id,
                    'payment_id' => $paidPayment->id,
                    'amount' => $locked->total_amount,
                    'percent' => 100,
                    'reason' => "Ditolak oleh pengelola: {$reason}",
                    'status' => RefundStatus::PENDING,
                ]);
            }

            // 4. Dispatch notification
            $notifier->send('booking.cancelled', [
                'booking' => $locked,
                'user' => $locked->user,
                'destination_name' => $locked->destination->name,
                'booking_code' => $locked->booking_code,
                'reason' => $reason,
            ], ['buyer']);
        });

        return redirect()->back()->with('error', "Pesanan #{$booking->booking_code} telah ditolak. Pengembalian dana 100% telah dijadwalkan.");
    }

    /**
     * Check-in QR Landing Page (Signed URL - SPEC §11)
     */
    public function showCheckin(Request $request, string $code)
    {
        $user = Auth::user();
        $booking = Booking::with(['institution', 'destination', 'package', 'checkIn'])
            ->where('booking_code', $code)
            ->firstOrFail();

        if ($user && $user->account_type === AccountType::PARTNER) {
            if ($booking->destination->partner_id !== $user->id) {
                abort(403, 'Anda bukan pengelola destinasi dari pesanan ini.');
            }
        }

        return view('partner.checkin', compact('booking'));
    }

    /**
     * Konfirmasi Check-in via CheckInService
     */
    public function confirmCheckin(Request $request, string $code, CheckInService $checkInService)
    {
        $user = Auth::user();
        $booking = Booking::where('booking_code', $code)->firstOrFail();

        if ($user && $user->account_type === AccountType::PARTNER) {
            if ($booking->destination->partner_id !== $user->id) {
                abort(403, 'Akses ditolak.');
            }
        }

        $request->validate([
            'actual_participant_count' => 'required|integer|min:1',
            'note' => 'nullable|string|max:500',
        ]);

        try {
            $checkInService->processCheckIn(
                booking: $booking,
                scanner: $user,
                actualPax: (int) $request->actual_participant_count,
                note: $request->note
            );

            return redirect()->back()->with('success', "Check-in rombongan {$booking->booking_code} berhasil dicatat!");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Halaman 6: Profil Pengelola & Rekening Payout
     */
    public function profile()
    {
        $user = Auth::user();
        $profile = $user->partnerProfile ?? PartnerProfile::firstOrCreate(['user_id' => $user->id], [
            'organization_name' => $user->name,
        ]);

        $organizationTypes = OrganizationType::all();
        $provinces = Province::orderBy('name')->get();
        $banks = Bank::where('is_active', true)->get();

        return view('partner.profile', compact('user', 'profile', 'organizationTypes', 'provinces', 'banks'));
    }

    /**
     * Simpan Perubahan Profil & Rekening Bank Mitra
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $profile = $user->partnerProfile;

        $request->validate([
            'organization_name' => 'required|string|max:255',
            'organization_type_id' => 'nullable|exists:organization_types,id',
            'legal_document_number' => 'nullable|string|max:100',
            'bank_id' => 'nullable|exists:banks,id',
            'bank_account_number' => 'required|string|max:50',
            'bank_account_holder' => 'required|string|max:255',
            'pic_name' => 'required|string|max:255',
            'pic_phone' => 'required|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'province_id' => 'nullable|exists:provinces,id',
        ]);

        $profile->update([
            'organization_name' => $request->organization_name,
            'organization_type_id' => $request->organization_type_id,
            'legal_document_number' => $request->legal_document_number,
            'bank_id' => $request->bank_id,
            'bank_account_number' => $request->bank_account_number,
            'bank_account_holder' => $request->bank_account_holder,
            'address' => $request->address,
            'city' => $request->city,
            'province_id' => $request->province_id,
        ]);

        $user->update([
            'name' => $request->pic_name,
            'phone' => $request->pic_phone,
        ]);

        return redirect()->route('mitra.profile')
            ->with('success', 'Profil lembaga pengelola dan nomor rekening pencairan berhasil diperbarui.');
    }

    /**
     * Halaman 7: Pusat Notifikasi Mitra
     */
    public function notifications()
    {
        $user = Auth::user();
        $notifications = NotificationLog::where('user_id', $user->id)
            ->orWhere('target', $user->phone)
            ->orWhere('target', $user->email)
            ->latest()
            ->paginate(15);

        return view('partner.notifications', compact('user', 'notifications'));
    }

    /**
     * Balas Ulasan Pembeli (SPEC C8 ReviewService)
     */
    public function replyReview(Request $request, $id, ReviewService $reviewService)
    {
        $user = Auth::user();
        $review = Review::with('destination')->findOrFail($id);

        if ($review->destination->partner_id !== $user->id) {
            abort(403);
        }

        $request->validate([
            'reply' => 'required|string|min:5|max:2000',
        ]);

        $reviewService->reply($review, $user, $request->reply);

        return redirect()->back()->with('success', 'Tanggapan ulasan berhasil disimpan.');
    }
}
