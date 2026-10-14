<?php

namespace App\Http\Controllers;

use App\Domain\Audit\AuditLogger;
use App\Domain\Booking\Services\BookingService;
use App\Domain\Catalog\Services\DestinationService;
use App\Domain\Notification\Services\NotificationDispatcher;
use App\Enums\BookingStatus;
use App\Enums\DestinationStatus;
use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Enums\RefundStatus;
use App\Jobs\SendNotificationJob;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Destination;
use App\Models\DestinationCategory;
use App\Models\Institution;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\PartnerProfile;
use App\Models\Province;
use App\Models\Refund;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        $pendingInstitutions = Institution::where('verification_status', 'pending')->count();
        $pendingPartners = PartnerProfile::where('verification_status', 'pending')->count();
        $pendingVerifications = $pendingInstitutions + $pendingPartners;
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
                'desc' => 'Perlu pemantauan konfirmasi batas SLA 24 jam.',
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

        return view('admin.destinations.create', compact('categories', 'provinces', 'partners'));
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
        ]);

        $destination = $this->destinationService->createDestination($validated, Auth::user());

        return redirect()->route('admin.destinations.index')->with('success', "Destinasi '{$destination->name}' berhasil ditambahkan.");
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

        $query = Destination::with(['partner.partnerProfile', 'category', 'moderations.admin']);
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
        $action = $request->input('action'); // approve, revision, reject
        $notes = $request->input('notes', '');

        $this->destinationService->moderate($destination, $action, $notes, Auth::user());

        $msg = match ($action) {
            'approve' => "Destinasi '{$destination->name}' berhasil disetujui dan diterbitkan!",
            'revision' => "Catatan revisi untuk '{$destination->name}' berhasil dikirim ke mitra.",
            default => "Pengajuan destinasi '{$destination->name}' telah ditolak/diarsipkan.",
        };

        return back()->with('success', $msg);
    }

    /**
     * Verifikasi Akun Pengguna (Institusi & Mitra)
     */
    public function verifyUsers(Request $request)
    {
        $tab = $request->query('tab', 'institutions');

        $institutions = Institution::with(['user', 'documents'])->latest('id')->paginate(15, ['*'], 'inst_page');
        $partners = PartnerProfile::with(['user', 'documents', 'bank'])->latest('id')->paginate(15, ['*'], 'partner_page');

        $pendingInstitutionsCount = Institution::where('verification_status', 'pending')->count();
        $pendingPartnersCount = PartnerProfile::where('verification_status', 'pending')->count();

        return view('admin.users.verify', compact(
            'institutions',
            'partners',
            'tab',
            'pendingInstitutionsCount',
            'pendingPartnersCount'
        ));
    }

    public function updateUserVerification(Request $request, $type, $id)
    {
        $action = $request->input('action', 'verify'); // verify or reject
        $status = ($action === 'verify') ? 'verified' : 'rejected';

        if ($type === 'institution') {
            $inst = Institution::findOrFail($id);
            $inst->verification_status = $status;
            $inst->verified_at = ($action === 'verify') ? now() : null;
            $inst->save();

            AuditLogger::log(
                action: ($action === 'verify') ? 'verify_institution' : 'reject_institution',
                target: $inst,
                description: "Verifikasi institusi '{$inst->institution_name}' diset menjadi {$status}."
            );

            return back()->with('success', "Status verifikasi institusi {$inst->institution_name} diperbarui.");
        } else {
            $partner = PartnerProfile::findOrFail($id);
            $partner->verification_status = $status;
            $partner->verified_at = ($action === 'verify') ? now() : null;
            $partner->save();

            AuditLogger::log(
                action: ($action === 'verify') ? 'verify_partner' : 'reject_partner',
                target: $partner,
                description: "Verifikasi lembaga mitra '{$partner->organization_name}' diset menjadi {$status}."
            );

            return back()->with('success', "Status verifikasi lembaga mitra {$partner->organization_name} diperbarui.");
        }
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
            $query->where('status', $status);
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
            'dibayar' => Booking::where('status', BookingStatus::DIBAYAR)->count(),
            'dikonfirmasi' => Booking::where('status', BookingStatus::DIKONFIRMASI)->count(),
            'selesai' => Booking::where('status', BookingStatus::SELESAI)->count(),
            'batal' => Booking::where('status', BookingStatus::BATAL)->count(),
            'kedaluwarsa' => Booking::where('status', BookingStatus::KEDALUWARSA)->count(),
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

        if ($action === 'force_confirm') {
            $this->bookingService->confirm($booking, Auth::user());
            return back()->with('success', "Reservasi {$booking->booking_code} berhasil dikonfirmasi secara resmi.");
        } elseif ($action === 'complete') {
            $this->bookingService->complete($booking, Auth::user(), 'Selesai melalui intervensi admin.');
            return back()->with('success', "Reservasi {$booking->booking_code} telah ditandai selesai.");
        } elseif ($action === 'cancel') {
            $reason = $request->input('reason', 'Dibatalkan oleh admin operasional');
            $overridePercent = $request->filled('refund_percent') ? (int) $request->input('refund_percent') : null;
            $this->bookingService->cancel($booking, Auth::user(), $reason, 'admin', $overridePercent);
            return back()->with('success', "Reservasi {$booking->booking_code} berhasil dibatalkan.");
        } elseif ($action === 'reschedule') {
            $newDate = Carbon::parse($request->input('new_date'));
            $reason = $request->input('reschedule_reason', 'Perubahan jadwal oleh admin');
            $this->bookingService->reschedule($booking, $newDate, Auth::user(), $reason);
            return back()->with('success', "Jadwal kunjungan reservasi {$booking->booking_code} berhasil diperbarui.");
        }

        return back()->with('error', 'Aksi tidak dikenali.');
    }

    /**
     * Pusat Notifikasi & Log Queue
     */
    public function notificationLogs(Request $request)
    {
        $query = NotificationLog::query();

        if ($request->filled('channel')) {
            $query->where('channel', $request->query('channel'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('event_key')) {
            $query->where('event_key', $request->query('event_key'));
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

        return view('admin.notifications.index', compact('logs', 'events', 'channels', 'statuses', 'stats'));
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
        $templates = NotificationTemplate::all();
        return view('admin.notifications.templates', compact('templates'));
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

        return back()->with('success', "Template notifikasi berhasil diperbarui.");
    }
}
