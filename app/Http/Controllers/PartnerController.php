<?php

namespace App\Http\Controllers;

use App\Domain\Audit\AuditLogger;
use App\Domain\Booking\Services\BookingService;
use App\Domain\Booking\Services\CheckInService;
use App\Domain\Catalog\Services\AvailabilityCalendarService;
use App\Domain\Catalog\Services\DestinationService;
use App\Domain\Catalog\Services\ReviewService;
use App\Domain\Notification\Services\NotificationDispatcher;
use App\Enums\AccountType;
use App\Enums\AddonPricingType;
use App\Enums\BookingStatus;
use App\Enums\DestinationStatus;
use App\Enums\VerificationStatus;
use App\Events\DestinationSubmitted;
use App\Models\Addon;
use App\Models\AvailabilitySlot;
use App\Models\Bank;
use App\Models\Booking;
use App\Models\CancellationPolicy;
use App\Models\Destination;
use App\Models\DestinationCategory;
use App\Models\DestinationContact;
use App\Models\DestinationMedia;
use App\Models\Facility;
use App\Models\OrganizationType;
use App\Models\Package;
use App\Models\PartnerPayout;
use App\Models\PartnerProfile;
use App\Models\Province;
use App\Models\Review;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;

class PartnerController extends Controller
{
    public function __construct(
        protected DestinationService $destinationService,
        protected BookingService $bookingService
    ) {}

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
        $facilities = Facility::where('is_active', true)->orderBy('name')->get();

        return view('partner.destinations.create', compact('user', 'profile', 'categories', 'provinces', 'policies', 'facilities'));
    }

    /**
     * Simpan Pengajuan Destinasi Baru (Status: pending_review)
     */
    public function storeDestination(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:destination_categories,id',
            'province_id' => 'required|exists:provinces,id',
            'cancellation_policy_id' => 'required|exists:cancellation_policies,id',
            'city' => 'required|string|max:100',
            'address' => 'required|string',
            'description' => 'required|string|min:50',
            'educational_highlights' => 'nullable|string',
            'default_daily_capacity' => 'required|integer|min:10|max:1000',
            'cover_image' => 'nullable|image|max:5120',
            'facilities' => 'nullable|array',
            'facilities.*' => 'exists:facilities,id',
        ]);
        $validated['short_description'] = Str::limit(strip_tags($validated['description']), 200);

        $dest = $this->destinationService->createDestination($validated, $user);

        // Auto create primary contact from partner profile
        if ($user->partnerProfile && $dest->contacts()->count() === 0) {
            $dest->contacts()->create([
                'contact_name' => $user->partnerProfile->pic_name ?? $user->name,
                'role' => 'Penanggung Jawab Tapak',
                'phone' => $user->partnerProfile->pic_phone ?? $user->phone ?? '-',
                'is_primary' => true,
            ]);
        }

        return redirect()->route('mitra.destinations.edit', $dest->id)
            ->with('success', "Draf destinasi '{$dest->name}' berhasil dibuat. Silakan tambahkan minimal 1 paket aktif, foto, dan slot ketersediaan sebelum mengajukan kurasi.");
    }

    public function editDestination($id)
    {
        $destination = Destination::where('partner_id', Auth::id())->findOrFail($id);
        $destination->load([
            'category',
            'province',
            'cancellationPolicy',
            'facilities',
            'media',
            'contacts',
            'allPackages',
            'allAddons',
            'availabilitySlots' => fn ($q) => $q->where('date', '>=', now()->toDateString())->orderBy('date')->limit(60),
            'moderations.admin',
        ]);

        $categories = DestinationCategory::where('is_active', true)->get();
        $provinces = Province::orderBy('name')->get();
        $policies = CancellationPolicy::all();
        $facilities = Facility::where('is_active', true)->get();

        return view('partner.destinations.edit', compact('destination', 'categories', 'provinces', 'policies', 'facilities'));
    }

    public function updateDestination(Request $request, $id)
    {
        $destination = Destination::where('partner_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'category_id' => 'required|exists:destination_categories,id',
            'name' => 'required|string|max:255',
            'province_id' => 'required|exists:provinces,id',
            'city' => 'required|string|max:255',
            'address' => 'nullable|string',
            'short_description' => 'required|string|max:300',
            'description' => 'required|string',
            'educational_highlights' => 'nullable|string',
            'default_daily_capacity' => 'required|integer|min:1',
            'cancellation_policy_id' => 'required|exists:cancellation_policies,id',
            'cover_image' => 'nullable|image|max:5120',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ]);

        $this->destinationService->updateDestination($destination, $validated, Auth::user());

        return back()->with('success', "Informasi destinasi '{$destination->name}' berhasil diperbarui.");
    }

    public function submitDestination($id)
    {
        $destination = Destination::where('partner_id', Auth::id())->findOrFail($id);

        try {
            $this->destinationService->submitForReview($destination, Auth::user());
            DestinationSubmitted::dispatch($destination, Auth::user());

            return back()->with('success', "Destinasi '{$destination->name}' berhasil diajukan untuk kurasi tim Destinara.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function toggleDestinationActive($id)
    {
        $destination = Destination::where('partner_id', Auth::id())->findOrFail($id);

        try {
            $this->destinationService->toggleActive($destination);
            $statusLabel = $destination->status === DestinationStatus::PUBLISHED ? 'diaktifkan kembali' : 'dinonaktifkan sementara';

            return back()->with('success', "Status destinasi '{$destination->name}' berhasil {$statusLabel}.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function addDestinationMedia(Request $request, $destinationId)
    {
        $destination = Destination::where('partner_id', Auth::id())->findOrFail($destinationId);

        $request->validate([
            'image' => 'required|image|max:5120',
            'caption' => 'nullable|string|max:255',
        ]);

        try {
            $this->destinationService->addMedia($destination, $request->file('image'), $request->caption);

            return back()->with('success', 'Foto galeri berhasil ditambahkan.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function deleteDestinationMedia($destinationId, $mediaId)
    {
        $destination = Destination::where('partner_id', Auth::id())->findOrFail($destinationId);
        $media = DestinationMedia::where('destination_id', $destination->id)->findOrFail($mediaId);

        $this->destinationService->deleteMedia($media);

        return back()->with('success', 'Foto galeri berhasil dihapus.');
    }

    public function sortDestinationMedia(Request $request, $destinationId)
    {
        $destination = Destination::where('partner_id', Auth::id())->findOrFail($destinationId);
        $request->validate(['order' => 'required|array']);

        $this->destinationService->sortMedia($destination, $request->order);

        return back()->with('success', 'Urutan galeri foto berhasil diperbarui.');
    }

    public function updateDestinationFacilities(Request $request, $destinationId)
    {
        $destination = Destination::where('partner_id', Auth::id())->findOrFail($destinationId);
        $facilities = $request->input('facilities', []);

        $destination->facilities()->sync($facilities);

        return back()->with('success', 'Daftar fasilitas destinasi berhasil diperbarui.');
    }

    public function saveDestinationContact(Request $request, $destinationId)
    {
        $destination = Destination::where('partner_id', Auth::id())->findOrFail($destinationId);

        $validated = $request->validate([
            'contact_id' => 'nullable|exists:destination_contacts,id',
            'contact_name' => 'required|string|max:255',
            'role' => 'nullable|string|max:100',
            'phone' => 'required|string|max:50',
            'is_primary' => 'nullable|boolean',
        ]);

        $this->destinationService->saveContact($destination, $validated, $validated['contact_id'] ?? null);

        return back()->with('success', 'Kontak narahubung destinasi berhasil disimpan.');
    }

    public function deleteDestinationContact($destinationId, $contactId)
    {
        $destination = Destination::where('partner_id', Auth::id())->findOrFail($destinationId);
        $contact = DestinationContact::where('destination_id', $destination->id)->findOrFail($contactId);

        $this->destinationService->deleteContact($contact);

        return back()->with('success', 'Kontak narahubung berhasil dihapus.');
    }

    public function saveDestinationPackage(Request $request, $destinationId)
    {
        $destination = Destination::where('partner_id', Auth::id())->findOrFail($destinationId);

        $validated = $request->validate([
            'package_id' => 'nullable|exists:packages,id',
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price_per_pax' => 'required|numeric|min:0',
            'min_pax' => 'nullable|integer|min:1',
            'min_participants' => 'nullable|integer|min:1',
            'max_pax' => 'nullable|integer|min:1',
            'max_participants' => 'nullable|integer|min:1',
            'duration_days' => 'required|integer|min:1',
            'inquiry_types' => 'nullable|array',
            'inquiry_types.*' => 'in:study_tour,penelitian',
            'included_items' => 'nullable|string',
            'excluded_items' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $minPax = (int) ($validated['min_pax'] ?? $validated['min_participants'] ?? 1);
        $maxPax = (int) ($validated['max_pax'] ?? $validated['max_participants'] ?? 500);
        if ($maxPax < $minPax) {
            $maxPax = $minPax;
        }

        $validated['min_pax'] = $minPax;
        $validated['max_pax'] = $maxPax;
        unset($validated['min_participants'], $validated['max_participants']);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['inquiry_types'] = ! empty($validated['inquiry_types']) ? array_values($validated['inquiry_types']) : ['study_tour'];

        if (! empty($validated['included_items']) && is_string($validated['included_items'])) {
            $validated['includes'] = array_values(array_filter(array_map('trim', explode("\n", str_replace("\r", '', $validated['included_items'])))));
        } else {
            $validated['includes'] = is_array($request->input('includes')) ? $request->input('includes') : null;
        }
        unset($validated['included_items']);

        if (! empty($validated['excluded_items']) && is_string($validated['excluded_items'])) {
            $validated['excludes'] = array_values(array_filter(array_map('trim', explode("\n", str_replace("\r", '', $validated['excluded_items'])))));
        } else {
            $validated['excludes'] = is_array($request->input('excludes')) ? $request->input('excludes') : null;
        }
        unset($validated['excluded_items']);

        $this->destinationService->savePackage($destination, $validated, $validated['package_id'] ?? null);

        return back()->with('success', 'Paket edukasi berhasil disimpan.');
    }

    public function togglePackageActive($destinationId, $packageId)
    {
        $destination = Destination::where('partner_id', Auth::id())->findOrFail($destinationId);
        $pkg = Package::where('destination_id', $destination->id)->findOrFail($packageId);

        $this->destinationService->togglePackageActive($pkg);

        return back()->with('success', "Status paket '{$pkg->name}' berhasil diperbarui.");
    }

    public function deleteDestinationPackage($destinationId, $packageId)
    {
        $destination = Destination::where('partner_id', Auth::id())->findOrFail($destinationId);
        $pkg = Package::where('destination_id', $destination->id)->findOrFail($packageId);

        try {
            $this->destinationService->deletePackage($pkg);

            return back()->with('success', 'Paket berhasil dihapus.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function saveDestinationAddon(Request $request, $destinationId)
    {
        $destination = Destination::where('partner_id', Auth::id())->findOrFail($destinationId);

        $validated = $request->validate([
            'addon_id' => 'nullable|exists:addons,id',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'pricing_type' => ['required', new Enum(AddonPricingType::class)],
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $this->destinationService->saveAddon($destination, $validated, $validated['addon_id'] ?? null);

        return back()->with('success', 'Add-on fasilitas/layanan berhasil disimpan.');
    }

    public function deleteDestinationAddon($destinationId, $addonId)
    {
        $destination = Destination::where('partner_id', Auth::id())->findOrFail($destinationId);
        $addon = Addon::where('destination_id', $destination->id)->findOrFail($addonId);

        $this->destinationService->deleteAddon($addon);

        return back()->with('success', 'Add-on berhasil dihapus.');
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

        return redirect()->back()->with('success', 'Slot tanggal '.Carbon::parse($slot->date)->translatedFormat('d M Y').' berhasil diperbarui.');
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
    public function confirmBooking($id)
    {
        $user = Auth::user();
        $destinationIds = Destination::where('partner_id', $user->id)->pluck('id');

        $booking = Booking::with(['institution', 'destination', 'user'])
            ->whereIn('destination_id', $destinationIds)
            ->findOrFail($id);

        try {
            $this->bookingService->confirm($booking, $user);

            return redirect()->back()->with('success', "Pesanan #{$booking->booking_code} berhasil dikonfirmasi! Dokumen e-Pass dan surat izin diterbitkan.");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Tolak Booking Masuk oleh Mitra (SPEC §8.3 & §8.6)
     */
    public function rejectBooking(Request $request, $id)
    {
        $user = Auth::user();
        $destinationIds = Destination::where('partner_id', $user->id)->pluck('id');

        $booking = Booking::with(['institution', 'destination', 'payments', 'user'])
            ->whereIn('destination_id', $destinationIds)
            ->findOrFail($id);

        $request->validate([
            'reason' => 'required|string|min:5|max:500',
        ]);

        try {
            $this->bookingService->reject($booking, $user, $request->reason);

            return redirect()->back()->with('error', "Pesanan #{$booking->booking_code} telah ditolak. Pengembalian dana 100% telah dijadwalkan.");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
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

        // Date validation: visit range with tolerance (H-1 start to end + 1 day) (SPEC §11)
        $now = Carbon::now()->startOfDay();
        $earliestAllowed = Carbon::parse($booking->planned_date_start)->subDay()->startOfDay();
        $latestAllowed = Carbon::parse($booking->planned_date_end)->addDay()->endOfDay();

        $isWithinWindow = ! ($now->lt($earliestAllowed) || $now->gt($latestAllowed));
        $dateWindowError = null;

        if (! $isWithinWindow) {
            $dateWindowError = 'Check-in hanya dapat dilakukan pada rentang jadwal kunjungan ('.
                Carbon::parse($booking->planned_date_start)->format('d/m/Y').' s.d. '.
                Carbon::parse($booking->planned_date_end)->format('d/m/Y').').';
        }

        return view('partner.checkin', compact('booking', 'isWithinWindow', 'dateWindowError'));
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
        $profile = $user->partnerProfile()->first() ?? PartnerProfile::firstOrCreate(['user_id' => $user->id], [
            'organization_name' => $user->name,
        ]);

        $profile->load('documents', 'bank');

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
     * Upload Dokumen Legalitas Mitra
     */
    public function uploadLegalDocument(Request $request)
    {
        $user = Auth::user();
        $profile = $user->partnerProfile;
        if (! $profile) {
            abort(403);
        }

        $request->validate([
            'type' => 'required|in:sk_pengelola,ktp_pic,npwp,lainnya',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $path = $request->file('file')->store("partner_documents/{$profile->id}", 'local');

        $profile->documents()->create([
            'type' => $request->type,
            'file_path' => $path,
            'original_name' => $request->file('file')->getClientOriginalName(),
        ]);

        return redirect()->back()->with('success', 'Dokumen legalitas berhasil diunggah.');
    }

    /**
     * Hapus Dokumen Legalitas Mitra
     */
    public function deleteLegalDocument($id)
    {
        $user = Auth::user();
        $profile = $user->partnerProfile;
        if (! $profile) {
            abort(403);
        }

        $doc = $profile->documents()->findOrFail($id);
        Storage::disk('local')->delete($doc->file_path);
        $doc->delete();

        return redirect()->back()->with('success', 'Dokumen legalitas berhasil dihapus.');
    }

    /**
     * Unduh Dokumen Legalitas Mitra
     */
    public function downloadLegalDocument($id)
    {
        $user = Auth::user();
        $profile = $user->partnerProfile;
        if (! $profile) {
            abort(403);
        }

        $doc = $profile->documents()->findOrFail($id);

        if (! Storage::disk('local')->exists($doc->file_path)) {
            abort(404, 'Berkas dokumen tidak ditemukan.');
        }

        return Storage::disk('local')->download($doc->file_path, $doc->original_name);
    }

    /**
     * Ajukan Verifikasi Akun Mitra ke Administrator
     */
    public function submitVerification(NotificationDispatcher $dispatcher)
    {
        $user = Auth::user();
        $profile = $user->partnerProfile;
        if (! $profile) {
            abort(403);
        }

        // Validate that profile, bank account, and at least 1 document are complete
        if (empty($profile->organization_name) || empty($profile->bank_id) || empty($profile->bank_account_number) || empty($profile->bank_account_holder)) {
            return redirect()->back()->with('error', 'Silakan lengkapi identitas organisasi dan rekening payout sebelum mengajukan verifikasi.');
        }

        if ($profile->documents()->count() === 0) {
            return redirect()->back()->with('error', 'Silakan unggah minimal satu dokumen legalitas (SK/KTP/NPWP) sebelum mengajukan verifikasi.');
        }

        $profile->verification_status = VerificationStatus::PENDING;
        $profile->save();

        AuditLogger::log(
            action: 'submit_partner_verification',
            target: $profile,
            description: "Mitra '{$profile->organization_name}' mengajukan verifikasi legalitas.",
            userId: $user->id
        );

        $dispatcher->send('partner.submitted', [
            'organization_name' => $profile->organization_name,
            'partner_name' => $user->name,
        ], ['admin']);

        return redirect()->back()->with('success', 'Pengajuan verifikasi berhasil dikirimkan ke administrator. Kami akan memproses verifikasi berkas legalitas Anda.');
    }

    /**
     * Halaman 7: Pusat Notifikasi Mitra
     */
    public function notifications()
    {
        $user = Auth::user();
        $notifications = $user->notifications()->latest()->paginate(15);

        return view('partner.notifications', compact('user', 'notifications'));
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
