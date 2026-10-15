<?php

namespace App\Http\Controllers;

use App\Domain\Audit\AuditLogger;
use App\Domain\Booking\Services\BookingService;
use App\Domain\Catalog\Services\AvailabilityCalendarService;
use App\Domain\Catalog\Services\DestinationService;
use App\Domain\Notification\Services\NotificationDispatcher;
use App\Domain\Notification\Services\TemplateRenderer;
use App\Enums\AddonPricingType;
use App\Enums\BookingStatus;
use App\Enums\DestinationStatus;
use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Enums\RefundStatus;
use App\Enums\VerificationStatus;
use App\Events\InstitutionRejected;
use App\Events\InstitutionVerified;
use App\Events\PartnerRejected;
use App\Events\PartnerVerified;
use App\Jobs\SendNotificationJob;
use App\Models\Addon;
use App\Models\AuditLog;
use App\Models\AvailabilitySlot;
use App\Models\Booking;
use App\Models\CancellationPolicy;
use App\Models\Destination;
use App\Models\DestinationCategory;
use App\Models\DestinationContact;
use App\Models\DestinationMedia;
use App\Models\Facility;
use App\Models\Institution;
use App\Models\InstitutionDocument;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\Package;
use App\Models\PartnerDocument;
use App\Models\PartnerProfile;
use App\Models\Province;
use App\Models\Refund;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AdminController extends Controller
{
    public function __construct(
        protected DestinationService $destinationService,
        protected BookingService $bookingService,
        protected NotificationDispatcher $notificationDispatcher
    ) {}

    /**
     * Dashboard Operasional Admin (SPEC §14.4)
     */
    public function dashboard()
    {
        $today = today();
        $startOfMonth = now()->startOfMonth();

        $bookingsToday = Booking::whereDate('created_at', $today)->count();
        $waitingPartnerSla = Booking::where('status', BookingStatus::DIBAYAR)->count();
        $pendingDestinations = Destination::where('status', DestinationStatus::PENDING_REVIEW)->count();
        $pendingInstitutions = Institution::where('verification_status', VerificationStatus::PENDING)->count();
        $pendingPartners = PartnerProfile::where('verification_status', VerificationStatus::PENDING)->count();
        $pendingVerifications = $pendingInstitutions + $pendingPartners;
        $unverifiedInstitutions = Institution::where('verification_status', VerificationStatus::UNVERIFIED)->count();
        $unverifiedPartners = PartnerProfile::where('verification_status', VerificationStatus::UNVERIFIED)->count();
        $unverifiedTotal = $unverifiedInstitutions + $unverifiedPartners;
        $failedNotifs = NotificationLog::where('status', NotificationStatus::FAILED)->count();
        $pendingRefunds = Refund::where('status', RefundStatus::PENDING)->count();

        // GMV real
        $gmvToday = (int) Booking::whereDate('created_at', $today)
            ->whereIn('status', [BookingStatus::DIBAYAR, BookingStatus::DIKONFIRMASI, BookingStatus::SELESAI])
            ->sum('total_amount');

        $gmvMonth = (int) Booking::where('created_at', '>=', $startOfMonth)
            ->whereIn('status', [BookingStatus::DIBAYAR, BookingStatus::DIKONFIRMASI, BookingStatus::SELESAI])
            ->sum('total_amount');

        $activeBookings = Booking::whereNotIn('status', [BookingStatus::BATAL, BookingStatus::KEDALUWARSA])->count();
        $urgentBookings = $waitingPartnerSla;

        $recentBookings = Booking::with(['destination', 'user.institution'])
            ->latest()
            ->take(8)
            ->get();

        $urgentItems = [];
        if ($waitingPartnerSla > 0) {
            $urgentItems[] = [
                'type' => 'booking',
                'title' => "{$waitingPartnerSla} Reservasi Menunggu Konfirmasi Mitra",
                'desc' => 'Perlu pemantauan konfirmasi batas SLA ' . (int) setting('partner_confirm_sla_hours', 24) . ' jam.',
                'link' => route('admin.bookings.index', ['status' => 'dibayar']),
                'action_label' => 'Periksa Reservasi',
                'badge' => 'SLA Alert',
                'badge_color' => 'bg-amber-100 text-amber-900 border-amber-300',
            ];
        }

        if ($pendingDestinations > 0) {
            $urgentItems[] = [
                'type' => 'destination',
                'title' => "{$pendingDestinations} Destinasi Menunggu Kurasi",
                'desc' => 'Pengajuan destinasi baru membutuhkan peninjauan konten dan kelayakan.',
                'link' => route('admin.destinations.verify'),
                'action_label' => 'Telaah Kurasi',
                'badge' => 'Kurasi Baru',
                'badge_color' => 'bg-emerald-100 text-emerald-900 border-emerald-300',
            ];
        }

        if ($pendingVerifications > 0) {
            $urgentItems[] = [
                'type' => 'user',
                'title' => "{$pendingVerifications} Berkas Legalitas Menunggu Validasi",
                'desc' => 'Verifikasi keabsahan institusi pemesan atau mitra pengelola.',
                'link' => route('admin.users.verify'),
                'action_label' => 'Validasi Berkas',
                'badge' => 'Legalitas',
                'badge_color' => 'bg-blue-100 text-blue-900 border-blue-300',
            ];
        }

        if ($failedNotifs > 0) {
            $urgentItems[] = [
                'type' => 'notification',
                'title' => "{$failedNotifs} Pesan Notifikasi Gagal Terkirim",
                'desc' => 'Antrean pesan WhatsApp atau Email mengalami kendala transmisi.',
                'link' => route('admin.notifications.index', ['status' => 'failed']),
                'action_label' => 'Log Notifikasi',
                'badge' => 'Queue Error',
                'badge_color' => 'bg-red-100 text-red-900 border-red-300',
            ];
        }

        if ($pendingRefunds > 0) {
            $urgentItems[] = [
                'type' => 'refund',
                'title' => "{$pendingRefunds} Pengajuan Refund Menunggu Eksekusi",
                'desc' => 'Pengembalian dana pembatalan butuh verifikasi dan bukti transfer.',
                'link' => route('admin.finance.refunds', ['status' => 'pending']),
                'action_label' => 'Proses Refund',
                'badge' => 'Refund Alert',
                'badge_color' => 'bg-rose-100 text-rose-900 border-rose-300',
            ];
        }

        $auditTrail = AuditLog::with('user')->latest('id')->take(6)->get();

        return view('admin.dashboard', compact(
            'bookingsToday',
            'waitingPartnerSla',
            'pendingDestinations',
            'pendingVerifications',
            'unverifiedTotal',
            'failedNotifs',
            'pendingRefunds',
            'gmvToday',
            'gmvMonth',
            'activeBookings',
            'urgentBookings',
            'recentBookings',
            'urgentItems',
            'auditTrail'
        ));
    }

    /**
     * Manajemen Destinasi Penuh (CRUD)
     */
    public function destinations(Request $request)
    {
        $query = Destination::with(['partner', 'category', 'province']);

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->query('category_id'));
        }

        if ($request->filled('search')) {
            $s = $request->query('search');
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('city', 'like', "%{$s}%");
            });
        }

        $destinations = $query->latest('id')->paginate(15)->withQueryString();
        $categories = DestinationCategory::where('is_active', true)->get();

        return view('admin.destinations.index', compact('destinations', 'categories'));
    }

    public function createDestination()
    {
        $categories = DestinationCategory::where('is_active', true)->get();
        $provinces = Province::orderBy('name')->get();
        $partners = User::where('account_type', 'partner')->orderBy('name')->get();
        $facilities = Facility::where('is_active', true)->orderBy('name')->get();

        return view('admin.destinations.create', compact('categories', 'provinces', 'partners', 'facilities'));
    }

    public function storeDestination(Request $request)
    {
        $validated = $request->validate([
            'partner_id' => 'required|exists:users,id',
            'category_id' => 'required|exists:destination_categories,id',
            'name' => 'required|string|max:255',
            'province_id' => 'required|exists:provinces,id',
            'city' => 'required|string|max:255',
            'address' => 'nullable|string',
            'short_description' => 'required|string|max:300',
            'description' => 'required|string',
            'educational_highlights' => 'nullable|string',
            'default_daily_capacity' => 'required|integer|min:1',
            'cover_image' => 'nullable|image|max:5120',
            'facilities' => 'nullable|array',
            'facilities.*' => 'exists:facilities,id',
        ]);

        $destination = $this->destinationService->createDestination($validated, Auth::user());

        return redirect()->route('admin.destinations.index')->with('success', "Destinasi '{$destination->name}' berhasil ditambahkan.");
    }

    public function editDestination(Destination $destination)
    {
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
            'partner.partnerProfile',
        ]);

        $categories = DestinationCategory::where('is_active', true)->get();
        $provinces = Province::orderBy('name')->get();
        $policies = CancellationPolicy::all();
        $cancellationPolicies = $policies;
        $facilities = Facility::where('is_active', true)->orderBy('sort_order')->get();
        $partners = User::where('account_type', 'partner')->orderBy('name')->get();

        return view('admin.destinations.edit', compact(
            'destination',
            'categories',
            'provinces',
            'policies',
            'cancellationPolicies',
            'facilities',
            'partners'
        ));
    }

    public function updateDestination(Request $request, Destination $destination)
    {
        $validated = $request->validate([
            'partner_id' => 'nullable|exists:users,id',
            'category_id' => 'required|exists:destination_categories,id',
            'name' => 'required|string|max:255',
            'province_id' => 'required|exists:provinces,id',
            'cancellation_policy_id' => 'required|exists:cancellation_policies,id',
            'city' => 'required|string|max:255',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'short_description' => 'required|string|max:300',
            'description' => 'required|string',
            'educational_highlights' => 'nullable|string',
            'default_daily_capacity' => 'required|integer|min:1',
            'cover_image' => 'nullable|image|max:5120',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'facilities' => 'nullable|array',
            'facilities.*' => 'exists:facilities,id',
        ]);

        $this->destinationService->updateDestination($destination, $validated, Auth::user());

        return back()->with('success', "Informasi destinasi '{$destination->name}' berhasil diperbarui.");
    }

    public function toggleDestinationFeatured(Destination $destination)
    {
        $destination->is_featured = ! $destination->is_featured;
        $destination->save();

        AuditLogger::log(
            action: 'toggle_destination_featured',
            target: $destination,
            description: "Status featured destinasi '{$destination->name}' diubah menjadi ".($destination->is_featured ? 'aktif' : 'nonaktif')
        );

        return back()->with('success', "Status featured destinasi '{$destination->name}' berhasil diperbarui.");
    }

    public function addDestinationMedia(Request $request, Destination $destination)
    {
        $request->validate([
            'image' => 'required|image|max:5120',
            'caption' => 'nullable|string|max:255',
        ]);

        $this->destinationService->addMedia($destination, $request->file('image'), $request->caption);

        return back()->with('success', 'Foto galeri berhasil diunggah.');
    }

    public function deleteDestinationMedia(Destination $destination, DestinationMedia $media)
    {
        if ($media->destination_id !== $destination->id) {
            abort(403);
        }

        $this->destinationService->deleteMedia($media);

        return back()->with('success', 'Foto galeri berhasil dihapus.');
    }

    public function sortDestinationMedia(Request $request, Destination $destination)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer',
        ]);

        $this->destinationService->sortMedia($destination, $request->order);

        return back()->with('success', 'Urutan galeri foto berhasil diperbarui.');
    }

    public function updateDestinationFacilities(Request $request, Destination $destination)
    {
        $request->validate([
            'facilities' => 'nullable|array',
            'facilities.*' => 'exists:facilities,id',
        ]);

        $destination->facilities()->sync($request->facilities ?? []);

        AuditLogger::log('update_destination_facilities', $destination, "Fasilitas destinasi '{$destination->name}' diperbarui.");

        return back()->with('success', 'Fasilitas destinasi berhasil disinkronkan.');
    }

    public function saveDestinationContact(Request $request, Destination $destination)
    {
        $validated = $request->validate([
            'contact_id' => 'nullable|exists:destination_contacts,id',
            'contact_name' => 'required|string|max:255',
            'role' => 'required|string|max:100',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'is_primary' => 'boolean',
        ]);

        $validated['is_primary'] = $request->boolean('is_primary');
        if ($validated['is_primary']) {
            $destination->contacts()->update(['is_primary' => false]);
        }

        $this->destinationService->saveContact($destination, $validated, $request->contact_id);

        return back()->with('success', 'Kontak pengelola destinasi berhasil disimpan.');
    }

    public function deleteDestinationContact(Destination $destination, DestinationContact $contact)
    {
        if ($contact->destination_id !== $destination->id) {
            abort(403);
        }

        $this->destinationService->deleteContact($contact);

        return back()->with('success', 'Kontak pengelola berhasil dihapus.');
    }

    public function saveDestinationPackage(Request $request, Destination $destination)
    {
        $validated = $request->validate([
            'package_id' => 'nullable|exists:packages,id',
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price_per_pax' => 'required|integer|min:0',
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

        $this->destinationService->savePackage($destination, $validated, $request->package_id);

        return back()->with('success', 'Paket kunjungan berhasil disimpan.');
    }

    public function togglePackageActive(Destination $destination, Package $package)
    {
        if ($package->destination_id !== $destination->id) {
            abort(403);
        }

        $this->destinationService->togglePackageActive($package);

        return back()->with('success', "Status aktif paket '{$package->name}' berhasil diubah.");
    }

    public function deleteDestinationPackage(Destination $destination, Package $package)
    {
        if ($package->destination_id !== $destination->id) {
            abort(403);
        }

        try {
            $this->destinationService->deletePackage($package);

            return back()->with('success', "Paket '{$package->name}' berhasil dihapus.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function saveDestinationAddon(Request $request, Destination $destination)
    {
        $validated = $request->validate([
            'addon_id' => 'nullable|exists:addons,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|integer|min:0',
            'pricing_type' => ['required', new Enum(AddonPricingType::class)],
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $this->destinationService->saveAddon($destination, $validated, $request->addon_id);

        return back()->with('success', 'Add-on layanan berhasil disimpan.');
    }

    public function deleteDestinationAddon(Destination $destination, Addon $addon)
    {
        if ($addon->destination_id !== $destination->id) {
            abort(403);
        }

        $this->destinationService->deleteAddon($addon);

        return back()->with('success', 'Add-on berhasil dihapus.');
    }

    public function bulkGenerateDestinationSlots(Request $request, Destination $destination, AvailabilityCalendarService $calendarService)
    {
        $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'days_of_week' => 'required|array|min:1',
            'days_of_week.*' => 'integer|between:0,6',
            'capacity' => 'required|integer|min:1',
        ]);

        $count = $calendarService->bulkGenerateSlots(
            destination: $destination,
            startDate: Carbon::parse($request->start_date),
            endDate: Carbon::parse($request->end_date),
            daysOfWeek: array_map('intval', $request->days_of_week),
            capacity: (int) $request->capacity
        );

        return back()->with('success', "Berhasil membuat/memperbarui {$count} slot ketersediaan pada destinasi {$destination->name}.");
    }

    public function updateDestinationSlot(Request $request, Destination $destination)
    {
        $request->validate([
            'slot_id' => 'required|exists:availability_slots,id',
            'status' => 'required|in:open,closed',
            'capacity' => 'required|integer|min:1',
        ]);

        $slot = AvailabilitySlot::where('destination_id', $destination->id)->findOrFail($request->slot_id);
        $minAllowed = $slot->booked_count + $slot->held_count;
        if ((int) $request->capacity < $minAllowed) {
            return back()->with('error', "Kapasitas tidak boleh lebih kecil dari kuota terpesan/held ({$minAllowed} pax).");
        }

        $slot->status = $request->status;
        $slot->capacity = (int) $request->capacity;
        $slot->save();

        return back()->with('success', 'Slot tanggal '.Carbon::parse($slot->date)->format('d/m/Y').' berhasil diperbarui.');
    }

    public function toggleDestinationActive(Destination $destination)
    {
        $this->destinationService->toggleActive($destination);

        return back()->with('success', "Status aktif destinasi '{$destination->name}' berhasil diperbarui.");
    }

    /**
     * Verifikasi & Kurasi Destinasi (SPEC §14.4)
     */
    public function verifyDestinations(Request $request)
    {
        $status = $request->query('status', 'pending_review');

        $query = Destination::with(['partner.partnerProfile.documents', 'category', 'moderations.admin', 'facilities', 'media', 'contacts']);
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $destinations = $query->latest('id')->paginate(15)->withQueryString();
        $pendingCount = Destination::where('status', DestinationStatus::PENDING_REVIEW)->count();

        return view('admin.destinations.verify', compact('destinations', 'status', 'pendingCount'));
    }

    public function updateDestinationStatus(Request $request, $id)
    {
        $destination = Destination::findOrFail($id);
        $validated = $request->validate([
            'action' => 'required|in:approve,revision,reject',
            'notes' => 'required_if:action,revision|nullable|string',
        ]);

        $action = $validated['action'];
        $notes = $validated['notes'] ?? '';

        try {
            $this->destinationService->moderate($destination, $action, $notes, Auth::user());

            $msg = match ($action) {
                'approve' => "Destinasi '{$destination->name}' berhasil disetujui dan diterbitkan!",
                'revision' => "Catatan revisi untuk '{$destination->name}' berhasil dikirim ke mitra.",
                default => "Pengajuan destinasi '{$destination->name}' telah ditolak/diarsipkan.",
            };

            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Verifikasi Akun Pengguna (Institusi & Mitra)
     */
    public function verifyUsers(Request $request)
    {
        $tab = $request->query('tab', 'institutions');
        $status = $request->query('status');

        $institutionsQuery = Institution::with(['user', 'documents'])->latest('id');
        $partnersQuery = PartnerProfile::with(['user', 'documents', 'bank'])->latest('id');

        if ($status && in_array($status, ['pending', 'verified', 'unverified', 'rejected'], true)) {
            $institutionsQuery->where('verification_status', $status);
            $partnersQuery->where('verification_status', $status);
        }

        $institutions = $institutionsQuery->paginate(15, ['*'], 'inst_page');
        $partners = $partnersQuery->paginate(15, ['*'], 'partner_page');

        $pendingInstitutionsCount = Institution::where('verification_status', VerificationStatus::PENDING)->count();
        $pendingPartnersCount = PartnerProfile::where('verification_status', VerificationStatus::PENDING)->count();
        $unverifiedInstitutionsCount = Institution::where('verification_status', VerificationStatus::UNVERIFIED)->count();
        $unverifiedPartnersCount = PartnerProfile::where('verification_status', VerificationStatus::UNVERIFIED)->count();

        return view('admin.users.verify', compact(
            'institutions',
            'partners',
            'tab',
            'status',
            'pendingInstitutionsCount',
            'pendingPartnersCount',
            'unverifiedInstitutionsCount',
            'unverifiedPartnersCount'
        ));
    }

    public function updateUserVerification(Request $request, $type, $id)
    {
        $action = $request->input('action', 'verify'); // verify or reject

        if ($action === 'reject') {
            $request->validate([
                'note' => 'required|string|min:5|max:1000',
            ], [
                'note.required' => 'Alasan penolakan verifikasi wajib diisi.',
                'note.min' => 'Alasan penolakan minimal 5 karakter.',
            ]);
        }

        $status = ($action === 'verify') ? VerificationStatus::VERIFIED : VerificationStatus::REJECTED;
        $note = $request->input('note');

        if ($type === 'institution') {
            if (! Auth::user()->can('institutions.verify')) {
                abort(403, 'Anda tidak memiliki izin untuk memverifikasi institusi pemesan.');
            }

            $inst = Institution::findOrFail($id);
            $inst->verification_status = $status;
            $inst->verification_note = $note;
            $inst->verified_at = ($action === 'verify') ? now() : null;
            $inst->save();

            AuditLogger::log(
                action: ($action === 'verify') ? 'verify_institution' : 'reject_institution',
                target: $inst,
                description: "Verifikasi institusi '{$inst->institution_name}' diset menjadi {$status->value}."
            );

            if ($action === 'verify') {
                InstitutionVerified::dispatch($inst, Auth::user());
            } else {
                InstitutionRejected::dispatch($inst, $note, Auth::user());
            }

            return back()->with('success', "Status verifikasi institusi {$inst->institution_name} diperbarui.");
        } else {
            if (! Auth::user()->can('partners.verify')) {
                abort(403, 'Anda tidak memiliki izin untuk memverifikasi mitra pengelola.');
            }

            $partner = PartnerProfile::findOrFail($id);
            $partner->verification_status = $status;
            $partner->verification_note = $note;
            $partner->verified_at = ($action === 'verify') ? now() : null;
            $partner->verified_by = ($action === 'verify') ? Auth::id() : null;
            $partner->save();

            AuditLogger::log(
                action: ($action === 'verify') ? 'verify_partner' : 'reject_partner',
                target: $partner,
                description: "Verifikasi lembaga mitra '{$partner->organization_name}' diset menjadi {$status->value}."
            );

            if ($action === 'verify') {
                PartnerVerified::dispatch($partner, Auth::user());
            } else {
                PartnerRejected::dispatch($partner, $note, Auth::user());
            }

            return back()->with('success', "Status verifikasi lembaga mitra {$partner->organization_name} diperbarui.");
        }
    }

    /**
     * Unduh Dokumen Legalitas Mitra untuk Verifikasi Admin
     */
    public function downloadPartnerDocument($id)
    {
        if (! Auth::user()->can('partners.verify')) {
            abort(403, 'Anda tidak memiliki izin untuk mengunduh dokumen legalitas mitra.');
        }

        $doc = PartnerDocument::findOrFail($id);

        if (! Storage::disk('local')->exists($doc->file_path)) {
            abort(404, 'Berkas dokumen tidak ditemukan.');
        }

        return Storage::disk('local')->download($doc->file_path, $doc->original_name);
    }

    /**
     * Unduh Dokumen Pendukung Institusi untuk Verifikasi Admin
     */
    public function downloadInstitutionDocument($id)
    {
        if (! Auth::user()->can('institutions.verify')) {
            abort(403, 'Anda tidak memiliki izin untuk mengunduh dokumen institusi pemesan.');
        }

        $doc = InstitutionDocument::findOrFail($id);

        if (! Storage::disk('local')->exists($doc->file_path)) {
            abort(404, 'Berkas dokumen tidak ditemukan.');
        }

        return Storage::disk('local')->download($doc->file_path, $doc->original_name);
    }

    /**
     * Manajemen Reservasi / Booking
     */
    public function manageBookings(Request $request)
    {
        $status = $request->query('status', 'all');
        $search = $request->query('search', '');

        $query = Booking::with(['destination', 'user.institution', 'package', 'payments']);

        if ($status !== 'all') {
            $mappedStatus = match ($status) {
                'pending_confirmation' => BookingStatus::DIBAYAR,
                'awaiting_payment' => BookingStatus::MENUNGGU_PEMBAYARAN,
                'confirmed' => BookingStatus::DIKONFIRMASI,
                'completed' => BookingStatus::SELESAI,
                'cancelled' => BookingStatus::BATAL,
                default => $status,
            };
            $query->where('status', $mappedStatus);
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('booking_code', 'like', "%{$search}%")
                    ->orWhereHas('destination', fn ($dq) => $dq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('user.institution', fn ($iq) => $iq->where('institution_name', 'like', "%{$search}%"));
            });
        }

        $bookings = $query->latest('id')->paginate(15)->withQueryString();

        $statusCounts = [
            'all' => Booking::count(),
            'menunggu_pembayaran' => Booking::where('status', BookingStatus::MENUNGGU_PEMBAYARAN)->count(),
            'awaiting_payment' => Booking::where('status', BookingStatus::MENUNGGU_PEMBAYARAN)->count(),
            'dibayar' => Booking::where('status', BookingStatus::DIBAYAR)->count(),
            'pending_confirmation' => Booking::where('status', BookingStatus::DIBAYAR)->count(),
            'dikonfirmasi' => Booking::where('status', BookingStatus::DIKONFIRMASI)->count(),
            'confirmed' => Booking::where('status', BookingStatus::DIKONFIRMASI)->count(),
            'selesai' => Booking::where('status', BookingStatus::SELESAI)->count(),
            'completed' => Booking::where('status', BookingStatus::SELESAI)->count(),
            'batal' => Booking::where('status', BookingStatus::BATAL)->count(),
            'cancelled' => Booking::where('status', BookingStatus::BATAL)->count(),
            'kedaluwarsa' => Booking::where('status', BookingStatus::KEDALUWARSA)->count(),
            'expired' => Booking::where('status', BookingStatus::KEDALUWARSA)->count(),
        ];

        return view('admin.bookings.index', compact('bookings', 'status', 'search', 'statusCounts'));
    }

    public function showBooking($code)
    {
        $booking = Booking::with([
            'destination.partner.partnerProfile',
            'user.institution',
            'package',
            'items',
            'payments',
            'statusHistories.actor',
            'documents',
            'refund',
        ])->where('booking_code', $code)->firstOrFail();

        return view('admin.bookings.show', compact('booking'));
    }

    public function updateBookingStatus(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);
        $action = $request->input('action'); // force_confirm, complete, cancel, reschedule

        try {
            if ($action === 'force_confirm') {
                if (! Auth::user()->can('bookings.force_confirm')) {
                    abort(403, 'Anda tidak memiliki hak akses untuk melakukan force confirm booking.');
                }
                $this->bookingService->confirm($booking, Auth::user());

                return back()->with('success', "Reservasi {$booking->booking_code} berhasil dikonfirmasi secara resmi.");
            } elseif ($action === 'complete') {
                if (! Auth::user()->can('bookings.complete')) {
                    abort(403, 'Anda tidak memiliki hak akses untuk menyelesaikan booking.');
                }
                $this->bookingService->complete($booking, Auth::user(), 'Selesai melalui intervensi admin.');

                return back()->with('success', "Reservasi {$booking->booking_code} telah ditandai selesai.");
            } elseif ($action === 'cancel') {
                if (! Auth::user()->can('bookings.cancel')) {
                    abort(403, 'Anda tidak memiliki hak akses untuk membatalkan booking.');
                }
                $reason = $request->input('reason', 'Dibatalkan oleh admin operasional');
                $overridePercent = $request->filled('refund_percent') ? (int) $request->input('refund_percent') : null;
                $this->bookingService->cancel($booking, Auth::user(), $reason, 'admin', $overridePercent);

                return back()->with('success', "Reservasi {$booking->booking_code} berhasil dibatalkan.");
            } elseif ($action === 'reschedule') {
                if (! Auth::user()->can('bookings.reschedule')) {
                    abort(403, 'Anda tidak memiliki hak akses untuk melakukan reschedule booking.');
                }

                if (! $request->has('new_date') && $request->has('visit_date')) {
                    $request->merge(['new_date' => $request->input('visit_date')]);
                }

                $minLeadDays = (int) setting('min_lead_days', 3);
                $minDate = now()->addDays($minLeadDays)->startOfDay()->format('Y-m-d');

                $request->validate([
                    'new_date' => "required|date|after_or_equal:{$minDate}",
                    'reschedule_reason' => 'required|string|min:5|max:500',
                ]);

                $newDate = Carbon::parse($request->input('new_date'));
                $reason = $request->input('reschedule_reason');
                $this->bookingService->reschedule($booking, $newDate, Auth::user(), $reason);

                return back()->with('success', "Jadwal kunjungan reservasi {$booking->booking_code} berhasil diperbarui.");
            }

            return back()->with('error', 'Aksi tidak dikenali.');
        } catch (HttpException $e) {
            throw $e;
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Pusat Notifikasi & Log Queue
     */
    public function notificationLogs(Request $request)
    {
        $channel = $request->query('channel', 'all');
        $status = $request->query('status', 'all');
        $eventKey = $request->query('event_key', 'all');

        $query = NotificationLog::query();

        if ($channel !== 'all' && ! empty($channel)) {
            $query->where('channel', $channel);
        }

        if ($status !== 'all' && ! empty($status)) {
            $query->where('status', $status);
        }

        if ($eventKey !== 'all' && ! empty($eventKey)) {
            $query->where('event_key', $eventKey);
        }

        $logs = $query->latest('id')->paginate(20)->withQueryString();
        $events = NotificationLog::select('event_key')->distinct()->pluck('event_key');
        $channels = NotificationChannel::cases();
        $statuses = NotificationStatus::cases();

        $stats = [
            'total' => NotificationLog::count(),
            'sent' => NotificationLog::where('status', NotificationStatus::SENT)->count(),
            'queued' => NotificationLog::where('status', NotificationStatus::QUEUED)->count(),
            'failed' => NotificationLog::where('status', NotificationStatus::FAILED)->count(),
        ];

        $auditLogs = AuditLog::with('user')->latest('id')->take(10)->get();

        return view('admin.notifications.index', compact('logs', 'events', 'channels', 'statuses', 'stats', 'channel', 'status', 'auditLogs'));
    }

    public function retryNotification($id)
    {
        $log = NotificationLog::findOrFail($id);
        $log->status = NotificationStatus::QUEUED;
        $log->error = null;
        $log->save();

        SendNotificationJob::dispatch($log->id);

        return back()->with('success', "Notifikasi #{$log->id} dijadwalkan ulang untuk pengiriman.");
    }

    public function notificationTemplates()
    {
        $templates = NotificationTemplate::orderBy('event_key')->orderBy('channel')->get();
        $availableEvents = [
            'auth.welcome',
            'booking.created',
            'payment.reminder',
            'booking.expired',
            'payment.paid',
            'partner.confirm_reminder',
            'booking.confirmed',
            'booking.rejected',
            'booking.auto_cancelled',
            'booking.cancelled',
            'booking.rescheduled',
            'visit.reminder',
            'booking.checked_in',
            'booking.completed',
            'refund.created',
            'refund.completed',
            'payout.created',
            'payout.transferred',
            'destination.submitted',
            'destination.approved',
            'destination.revision_required',
            'destination.rejected',
            'partner.verified',
            'partner.rejected',
            'institution.verified',
            'institution.rejected',
            'review.received',
            'payment.late_received',
            'payment.amount_mismatch',
            'system.wa_disconnected',
        ];

        return view('admin.notifications.templates', compact('templates', 'availableEvents'));
    }

    public function storeNotificationTemplate(Request $request)
    {
        $validated = $request->validate([
            'event_key' => 'required|string|max:100',
            'channel' => 'required|string|in:mail,whatsapp,database',
            'audience' => 'required|string|in:buyer,partner,admin',
            'subject' => 'nullable|string|max:255',
            'body' => 'required|string',
            'is_active' => 'nullable|boolean',
        ]);

        $exists = NotificationTemplate::where('event_key', $validated['event_key'])
            ->where('channel', $validated['channel'])
            ->where('audience', $validated['audience'])
            ->exists();

        if ($exists) {
            return back()->with('error', "Template untuk event '{$validated['event_key']}' dengan kanal '{$validated['channel']}' dan sasaran '{$validated['audience']}' sudah terdaftar.");
        }

        $template = NotificationTemplate::create([
            'event_key' => $validated['event_key'],
            'channel' => $validated['channel'],
            'audience' => $validated['audience'],
            'subject' => $validated['subject'] ?? null,
            'body' => $validated['body'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditLogger::log(
            action: 'create_notification_template',
            target: $template,
            description: "Template notifikasi baru dibuat: {$template->event_key} ({$template->channel->value} / {$template->audience})."
        );

        return back()->with('success', 'Template notifikasi baru berhasil ditambahkan.');
    }

    public function updateNotificationTemplate(Request $request, NotificationTemplate $template)
    {
        $validated = $request->validate([
            'subject' => 'nullable|string|max:255',
            'body' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $template->update($validated);

        AuditLogger::log(
            action: 'update_notification_template',
            target: $template,
            description: "Template notifikasi {$template->event_key} ({$template->channel->value}) diperbarui."
        );

        return back()->with('success', 'Template notifikasi berhasil diperbarui.');
    }

    public function toggleNotificationTemplate(NotificationTemplate $template)
    {
        $template->is_active = ! $template->is_active;
        $template->save();

        AuditLogger::log(
            action: 'toggle_notification_template',
            target: $template,
            description: "Status template {$template->event_key} ({$template->channel->value}) diubah menjadi ".($template->is_active ? 'aktif' : 'nonaktif').'.'
        );

        return back()->with('success', "Template {$template->event_key} ({$template->channel->value}) berhasil ".($template->is_active ? 'diaktifkan' : 'dinonaktifkan').'.');
    }

    public function previewNotificationTemplate(NotificationTemplate $template, TemplateRenderer $renderer)
    {
        $sampleContext = [
            'user_name' => 'Budi Santoso',
            'pic_name' => 'Budi Santoso',
            'institution_name' => 'SMA Negeri 1 Yogyakarta',
            'destination_name' => 'Desa Wisata Kalibiru',
            'organization_name' => 'Pokdarwis Kalibiru',
            'partner_name' => 'Bapak Budi',
            'booking_code' => 'DST-2610-SAMPLE',
            'total_amount' => '1.250.000',
            'net_amount' => '1.125.000',
            'refund_amount' => '625.000',
            'refund_percent' => 50,
            'participant_count' => 20,
            'planned_date_start' => now()->addDays(7)->format('d/m/Y'),
            'planned_date_end' => now()->addDays(8)->format('d/m/Y'),
            'expires_at' => now()->addDay()->format('d/m/Y H:i'),
            'payment_url' => url('/bayar/DST-2610-SAMPLE'),
            'booking_url' => url('/booking/DST-2610-SAMPLE'),
            'action_url' => url('/verifikasi/sample'),
            'payout_number' => 'PAY/202610/0001',
            'reason' => 'Perubahan jadwal dinas',
            'moderation_notes' => 'Foto sampul sudah sesuai, informasi lengkap.',
            'days_left' => 3,
            'hours_left' => 6,
            'payment_gateway' => 'Xendit Virtual Account',
            'rating' => 5,
            'review_comment' => 'Kegiatan sangat edukatif dan fasilitas memadai.',
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder' => 'SMA Negeri 1 Yogyakarta',
        ];

        $renderedSubject = $template->subject ? $renderer->render($template->subject, $sampleContext) : null;
        $renderedBody = $renderer->render($template->body, $sampleContext);

        return response()->json([
            'event_key' => $template->event_key,
            'channel' => $template->channel->value ?? $template->channel,
            'audience' => $template->audience,
            'is_active' => $template->is_active,
            'subject' => $renderedSubject,
            'body' => $renderedBody,
        ]);
    }
}
