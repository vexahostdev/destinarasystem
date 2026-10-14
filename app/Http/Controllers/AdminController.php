<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Destination;
use App\Models\Institution;
use App\Models\PartnerProfile;
use App\Models\Booking;
use App\Models\Document;
use App\Models\NotificationLog;
use App\Models\AuditLog;
use App\Models\PartnerPayout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AdminController extends Controller
{

    /**
     * Halaman 1: Dashboard Admin Harian
     */
    public function dashboard()
    {
        $pendingDestinations = Destination::where('status', 'pending_review')->count();
        $pendingInstitutions = Institution::where('verification_status', 'pending')->count();
        $pendingPartners = PartnerProfile::where('verification_status', 'pending')->count();
        $pendingVerifications = $pendingInstitutions + $pendingPartners;
        
        $activeBookings = Booking::whereNotIn('status', ['cancelled'])->count();
        $urgentBookings = Booking::where('status', 'pending_confirmation')->count();
        $failedNotifs = NotificationLog::where('status', 'failed')->count();

        $recentBookings = Booking::with(['destination', 'user.institution'])
            ->latest()
            ->take(6)
            ->get();

        $urgentItems = [];
        if ($urgentBookings > 0) {
            $urgentItems[] = [
                'type' => 'booking',
                'title' => "$urgentBookings Rombongan Menunggu Konfirmasi Mitra",
                'desc' => 'Perlu pengecekan respon mitra dalam batas SLA 24 jam.',
                'link' => route('admin.bookings.index', ['status' => 'pending_confirmation']),
                'action_label' => 'Periksa Booking',
                'badge' => 'SLA Alert',
                'badge_color' => 'bg-amber-100 text-amber-900 border-amber-300',
            ];
        }

        if ($pendingDestinations > 0) {
            $urgentItems[] = [
                'type' => 'destination',
                'title' => "$pendingDestinations Dossier Tapak Baru Menunggu Kurasi",
                'desc' => 'Pengajuan tapak baru membutuhkan telaah kurikulum P5 dan daya dukung.',
                'link' => route('admin.destinations.verify'),
                'action_label' => 'Telaah Dossier',
                'badge' => 'Kurasi Baru',
                'badge_color' => 'bg-emerald-100 text-emerald-900 border-emerald-300',
            ];
        }

        if ($pendingVerifications > 0) {
            $urgentItems[] = [
                'type' => 'user',
                'title' => "$pendingVerifications Kredensial Pengguna Menunggu Validasi",
                'desc' => 'Verifikasi nomor NPSN/NIDN sekolah dan legalitas SK lembaga adat mitra.',
                'link' => route('admin.users.verify'),
                'action_label' => 'Validasi Berkas',
                'badge' => 'Legalitas',
                'badge_color' => 'bg-blue-100 text-blue-900 border-blue-300',
            ];
        }

        if ($failedNotifs > 0) {
            $urgentItems[] = [
                'type' => 'notification',
                'title' => "$failedNotifs Notifikasi WhatsApp/Email Gagal Terkirim",
                'desc' => 'Antrian pesan keluar mengalami kendala pengiriman ke penerima.',
                'link' => route('admin.notifications.index', ['status' => 'failed']),
                'action_label' => 'Buka Queue Log',
                'badge' => 'Queue Error',
                'badge_color' => 'bg-red-100 text-red-900 border-red-300',
            ];
        }

        $auditTrail = AuditLog::with('user')->latest()->take(5)->get();

        return view('admin.dashboard', compact(
            'pendingDestinations',
            'pendingVerifications',
            'activeBookings',
            'urgentBookings',
            'failedNotifs',
            'recentBookings',
            'urgentItems',
            'auditTrail'
        ));
    }

    /**
     * Halaman 2: Verifikasi Listing Destinasi Baru
     */
    public function verifyDestinations(Request $request)
    {
        $status = $request->query('status', 'pending_review');
        
        $query = Destination::query();
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        $destinations = $query->latest()->get();
        $pendingCount = Destination::where('status', 'pending_review')->count();

        return view('admin.destinations.verify', compact('destinations', 'status', 'pendingCount'));
    }

    /**
     * Aksi Persetujuan / Penolakan Listing Tapak
     */
    public function updateDestinationStatus(Request $request, $id)
    {
        $destination = Destination::findOrFail($id);
        $action = $request->input('action'); // approve, revision, reject
        $notes = $request->input('notes', '');

        if ($action === 'approve') {
            $destination->status = 'published';
            $destination->save();

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'publish_destination',
                'entity_type' => 'Destination',
                'entity_id' => $destination->id,
                'description' => "Menyetujui dan mempublikasikan tapak '{$destination->name}' ke direktori publik.",
                'ip_address' => $request->ip(),
            ]);

            NotificationLog::create([
                'recipient_type' => 'partner',
                'recipient_name' => 'Pengelola Tapak',
                'channel' => 'whatsapp',
                'target' => '0819-8765-4321',
                'event_type' => 'curation_approved',
                'title' => "Dossier '{$destination->name}' Telah Disetujui & Diterbitkan",
                'preview_text' => "Selamat! Tapak Anda telah lolos telaah kurasi Destinara dan kini aktif menerima reservasi rombongan.",
                'status' => 'sent',
                'sent_at' => Carbon::now(),
            ]);

            return back()->with('success', "Tapak '{$destination->name}' berhasil diverifikasi dan diterbitkan ke katalog publik!");
        } elseif ($action === 'revision') {
            $destination->status = 'draft';
            $destination->save();

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'request_revision_destination',
                'entity_type' => 'Destination',
                'entity_id' => $destination->id,
                'description' => "Meminta revisi dossier tapak '{$destination->name}': $notes",
                'ip_address' => $request->ip(),
            ]);

            return back()->with('info', "Catatan revisi telah dikirimkan ke pengelola tapak.");
        } else {
            $destination->status = 'archived';
            $destination->save();

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'reject_destination',
                'entity_type' => 'Destination',
                'entity_id' => $destination->id,
                'description' => "Menolak pengajuan tapak '{$destination->name}': $notes",
                'ip_address' => $request->ip(),
            ]);

            return back()->with('warning', "Pengajuan tapak '{$destination->name}' ditolak.");
        }
    }

    /**
     * Halaman 3: Verifikasi Akun Mitra & Institusi
     */
    public function verifyUsers(Request $request)
    {
        $tab = $request->query('tab', 'institutions');

        $institutions = Institution::with('user')->latest()->get();
        $partners = PartnerProfile::with('user')->latest()->get();

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

    /**
     * Aksi Verifikasi Pengguna (Institusi / Mitra)
     */
    public function updateUserVerification(Request $request, $type, $id)
    {
        $action = $request->input('action', 'verify'); // verify or reject

        if ($type === 'institution') {
            $inst = Institution::findOrFail($id);
            $inst->verification_status = ($action === 'verify') ? 'verified' : 'unverified';
            $inst->save();

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => ($action === 'verify') ? 'verify_institution' : 'reject_institution',
                'entity_type' => 'Institution',
                'entity_id' => $inst->id,
                'description' => ($action === 'verify')
                    ? "Memverifikasi legalitas institusi '{$inst->institution_name}' (NPSN: {$inst->npsn})."
                    : "Menolak verifikasi institusi '{$inst->institution_name}'.",
                'ip_address' => $request->ip(),
            ]);

            $msg = ($action === 'verify') ? "Institusi {$inst->institution_name} berhasil diverifikasi!" : "Verifikasi institusi ditolak.";
            return back()->with('success', $msg);
        } else {
            $partner = PartnerProfile::findOrFail($id);
            $partner->verification_status = ($action === 'verify') ? 'verified' : 'unverified';
            $partner->save();

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => ($action === 'verify') ? 'verify_partner' : 'reject_partner',
                'entity_type' => 'PartnerProfile',
                'entity_id' => $partner->id,
                'description' => ($action === 'verify')
                    ? "Memverifikasi legalitas lembaga mitra '{$partner->organization_name}'."
                    : "Menolak verifikasi lembaga mitra '{$partner->organization_name}'.",
                'ip_address' => $request->ip(),
            ]);

            $msg = ($action === 'verify') ? "Lembaga pengelola {$partner->organization_name} berhasil diverifikasi!" : "Verifikasi mitra ditolak.";
            return back()->with('success', $msg);
        }
    }

    /**
     * Halaman 4: Manajemen Booking Lintas Destinasi
     */
    public function manageBookings(Request $request)
    {
        $status = $request->query('status', 'all');
        $search = $request->query('search', '');

        $query = Booking::with(['destination', 'user.institution', 'payment']);

        if ($status !== 'all') {
            if ($status === 'confirmed') {
                $query->whereIn('status', ['confirmed', 'dikonfirmasi']);
            } elseif ($status === 'pending_confirmation') {
                $query->whereIn('status', ['pending_confirmation', 'review_mitra', 'dibayar']);
            } elseif ($status === 'awaiting_payment') {
                $query->whereIn('status', ['awaiting_payment', 'menunggu_pembayaran']);
            } elseif ($status === 'completed') {
                $query->whereIn('status', ['completed', 'selesai']);
            } elseif ($status === 'cancelled') {
                $query->whereIn('status', ['cancelled', 'batal']);
            } else {
                $query->where('status', $status);
            }
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('booking_code', 'like', "%{$search}%")
                  ->orWhereHas('destination', function ($dq) use ($search) {
                      $dq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('user.institution', function ($iq) use ($search) {
                      $iq->where('institution_name', 'like', "%{$search}%");
                  });
            });
        }

        $bookings = $query->latest()->get();

        $statusCounts = [
            'all' => Booking::count(),
            'pending_confirmation' => Booking::whereIn('status', ['pending_confirmation', 'review_mitra', 'dibayar'])->count(),
            'awaiting_payment' => Booking::whereIn('status', ['awaiting_payment', 'menunggu_pembayaran'])->count(),
            'confirmed' => Booking::whereIn('status', ['confirmed', 'dikonfirmasi'])->count(),
            'completed' => Booking::whereIn('status', ['completed', 'selesai'])->count(),
            'cancelled' => Booking::whereIn('status', ['cancelled', 'batal'])->count(),
        ];

        return view('admin.bookings.index', compact('bookings', 'status', 'search', 'statusCounts'));
    }

    /**
     * Intervensi Operasional Booking
     */
    public function updateBookingStatus(Request $request, $id)
    {
        $booking = Booking::with(['destination.partner', 'payment'])->findOrFail($id);
        $action = $request->input('action'); // force_confirm, reschedule, complete, cancel

        if ($action === 'force_confirm') {
            $booking->status = 'dikonfirmasi';
            $booking->save();

            // Buat dokumen resmi jika belum ada
            if (!$booking->documents()->where('type', 'surat_konfirmasi')->exists()) {
                Document::create([
                    'booking_id' => $booking->id,
                    'type' => 'surat_konfirmasi',
                    'title' => 'Surat Konfirmasi Kunjungan & e-Pass #' . $booking->booking_code,
                    'file_path' => 'assets/docs/epass-' . strtolower($booking->booking_code) . '.pdf',
                    'generated_at' => Carbon::now(),
                ]);
            }

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'force_confirm_booking',
                'entity_type' => 'Booking',
                'entity_id' => $booking->id,
                'description' => "Admin melakukan konfirmasi darurat langsung untuk booking {$booking->booking_code}.",
                'ip_address' => $request->ip(),
            ]);

            return back()->with('success', "Booking {$booking->booking_code} berhasil dikonfirmasi secara resmi!");
        } elseif ($action === 'complete') {
            $booking->status = 'selesai';
            $booking->save();

            // Buat record PartnerPayout otomatis (90% hak mitra, 10% komisi platform)
            $partnerId = $booking->destination->partner_id ?? null;
            if ($partnerId) {
                $gross = $booking->subtotal_amount > 0 ? $booking->subtotal_amount : $booking->total_amount;
                $commission = round($gross * 0.10, 2);
                $net = $gross - $commission;

                PartnerPayout::firstOrCreate(
                    ['booking_id' => $booking->id],
                    [
                        'partner_id' => $partnerId,
                        'payout_reference' => 'PO-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
                        'gross_amount' => $gross,
                        'commission' => $commission,
                        'net_amount' => $net,
                        'status' => 'pending',
                    ]
                );
            }

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'complete_booking',
                'entity_type' => 'Booking',
                'entity_id' => $booking->id,
                'description' => "Kunjungan booking {$booking->booking_code} ditandai selesai. Saldo payout mitra telah dibuat.",
                'ip_address' => $request->ip(),
            ]);

            return back()->with('success', "Booking {$booking->booking_code} telah ditandai SELESAI. Dana payout siap ditarik oleh Mitra di panel pendapatan.");
        } elseif ($action === 'reschedule') {
            $newDate = $request->input('visit_date');
            $oldDate = $booking->visit_date;
            $booking->visit_date = $newDate;
            $booking->save();

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'reschedule_booking',
                'entity_type' => 'Booking',
                'entity_id' => $booking->id,
                'description' => "Menjadwalkan ulang booking {$booking->booking_code} dari {$oldDate} ke {$newDate}.",
                'ip_address' => $request->ip(),
            ]);

            return back()->with('success', "Tanggal kunjungan untuk {$booking->booking_code} berhasil diubah ke {$newDate}.");
        } elseif ($action === 'cancel') {
            $booking->status = 'batal';
            $booking->save();

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'cancel_booking',
                'entity_type' => 'Booking',
                'entity_id' => $booking->id,
                'description' => "Membatalkan booking {$booking->booking_code} atas permohonan rombongan/force majeure.",
                'ip_address' => $request->ip(),
            ]);

            return back()->with('warning', "Booking {$booking->booking_code} telah dibatalkan.");
        }

        return back();
    }

    /**
     * Halaman 5: Generator Dokumen Resmi
     */
    public function documentGenerator(Request $request)
    {
        $allBookings = Booking::with(['destination', 'user.institution'])->latest()->get();
        $selectedCode = $request->query('code', $allBookings->first()?->booking_code);
        $selectedType = $request->query('type', 'invoice'); // invoice, confirmation, permit, epass

        $selectedBooking = Booking::with(['destination', 'user.institution', 'payment'])
            ->where('booking_code', $selectedCode)
            ->first() ?? $allBookings->first();

        $documentsArchive = Document::with('booking.destination')->latest()->take(10)->get();

        return view('admin.documents.index', compact(
            'allBookings',
            'selectedBooking',
            'selectedType',
            'documentsArchive'
        ));
    }

    /**
     * Halaman 6: Form Kurasi & Manajemen Data Lengkap
     */
    public function curationEditor(Request $request, $id = null)
    {
        $allDestinations = Destination::orderBy('name')->get();
        
        if ($id) {
            $destination = Destination::findOrFail($id);
        } else {
            $destination = Destination::where('status', 'pending_review')->first() 
                ?? Destination::first();
        }

        return view('admin.destinations.curation', compact('allDestinations', 'destination'));
    }

    /**
     * Update Data Kurasi Tapak
     */
    public function updateCuration(Request $request, $id)
    {
        $destination = Destination::findOrFail($id);

        $destination->name = $request->input('name', $destination->name);
        $destination->category = $request->input('category', $destination->category);
        $destination->province = $request->input('province', $destination->province);
        $destination->city = $request->input('city', $destination->city);
        $destination->price_per_pax = $request->input('price_per_pax', $destination->price_per_pax);
        $destination->description = $request->input('description', $destination->description);
        $destination->educational_highlights = $request->input('educational_highlights', $destination->educational_highlights);
        $destination->status = $request->input('status', $destination->status);
        
        if ($request->has('suitable_for')) {
            $destination->suitable_for = (array) $request->input('suitable_for');
        }

        $destination->save();

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'update_destination_curation',
            'entity_type' => 'Destination',
            'entity_id' => $destination->id,
            'description' => "Memperbarui dossier kurasi dan parameter pembelajaran untuk '{$destination->name}'.",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "Dossier kurasi '{$destination->name}' berhasil diperbarui!");
    }

    /**
     * Halaman 7: Pusat Notifikasi & Log Queue
     */
    public function notificationLogs(Request $request)
    {
        $channel = $request->query('channel', 'all');
        $status = $request->query('status', 'all');

        $query = NotificationLog::latest();

        if ($channel !== 'all') {
            $query->where('channel', $channel);
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $logs = $query->get();

        $stats = [
            'total' => NotificationLog::count(),
            'sent' => NotificationLog::where('status', 'sent')->count(),
            'queued' => NotificationLog::where('status', 'queued')->count(),
            'failed' => NotificationLog::where('status', 'failed')->count(),
        ];

        $auditLogs = AuditLog::with('user')->latest()->take(15)->get();

        return view('admin.notifications.index', compact('logs', 'stats', 'channel', 'status', 'auditLogs'));
    }

    /**
     * Kirim Ulang Notifikasi Gagal
     */
    public function retryNotification($id)
    {
        $log = NotificationLog::findOrFail($id);
        $log->status = 'sent';
        $log->retry_count += 1;
        $log->sent_at = Carbon::now();
        $log->save();

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'retry_notification',
            'entity_type' => 'NotificationLog',
            'entity_id' => $log->id,
            'description' => "Memicu kirim ulang pesan keluar #{$log->id} ke {$log->target}.",
            'ip_address' => request()->ip(),
        ]);

        return back()->with('success', "Pesan notifikasi berhasil dikirim ulang ke gateway {$log->channel}!");
    }
}
