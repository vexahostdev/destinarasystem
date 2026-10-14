<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Institution;
use App\Models\Destination;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Document;
use Carbon\Carbon;
use Illuminate\Support\Str;

class BuyerController extends Controller
{
    /**
     * Helper untuk mendapatkan user buyer aktif di sesi
     */
    protected function ensureBuyerAuth()
    {
        return Auth::user();
    }

    /**
     * Halaman 1: Dashboard Buyer
     */
    public function dashboard(Request $request)
    {
        $user = $this->ensureBuyerAuth();
        $institution = $user->institution;

        // Metrik Rombongan
        $activeBookingsCount = Booking::where('user_id', $user->id)
            ->whereIn('status', ['menunggu_pembayaran', 'dibayar', 'dikonfirmasi'])
            ->count();

        $pendingPaymentCount = Booking::where('user_id', $user->id)
            ->where('status', 'menunggu_pembayaran')
            ->count();

        $confirmedCount = Booking::where('user_id', $user->id)
            ->where('status', 'dikonfirmasi')
            ->count();

        $completedCount = Booking::where('user_id', $user->id)
            ->where('status', 'selesai')
            ->count();

        // Jadwal Kunjungan Terdekat (Hero Booking Card)
        $nextBooking = Booking::with(['destination', 'payment', 'documents'])
            ->where('user_id', $user->id)
            ->whereIn('status', ['menunggu_pembayaran', 'dibayar', 'dikonfirmasi'])
            ->orderBy('planned_date_start', 'asc')
            ->first();

        // Daftar Booking Aktif Terbaru
        $recentBookings = Booking::with(['destination', 'payment'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Rekomendasi Destinasi Terverifikasi
        $recommendedDestinations = Destination::where('status', 'published')
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
     * Halaman 2: Form Pengajuan Booking Baru (Multi-step Archival Form)
     */
    public function createBooking(Request $request)
    {
        $user = $this->ensureBuyerAuth();
        $institution = $user->institution;

        $destinations = Destination::where('status', 'published')->get();

        // Cek jika ada parameter pre-selected destination
        $selectedSlug = $request->query('destinasi') ?? $request->query('tapak');
        $selectedDestination = $selectedSlug 
            ? Destination::where('slug', $selectedSlug)->first() 
            : $destinations->first();

        // Data reorder jika ada
        $reorderCode = $request->query('reorder');
        $reorderData = null;
        if ($reorderCode) {
            $reorderData = Booking::where('booking_code', $reorderCode)->first();
            if ($reorderData) {
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
     * Simpan Pengajuan Booking Baru
     */
    public function storeBooking(Request $request)
    {
        $user = $this->ensureBuyerAuth();
        $institution = $user->institution;

        $request->validate([
            'destination_id' => 'required|exists:destinations,id',
            'inquiry_type' => 'required|in:study_tour,penelitian',
            'planned_date_start' => 'required|date',
            'planned_date_end' => 'required|date|after_or_equal:planned_date_start',
            'participant_count' => 'required|integer|min:5|max:500',
            'guide_count' => 'required|integer|min:1|max:20',
        ]);

        $destination = Destination::findOrFail($request->destination_id);

        // Kalkulasi biaya
        $pricePerPax = $destination->price_per_pax;
        $participantCount = (int) $request->participant_count;
        $subtotal = $pricePerPax * $participantCount;
        $insurance = $participantCount * 2000; // Asuransi lapangan Rp 2.000 / pax
        $platformFee = 25000; // Biaya administrasi BOS & verifikasi dokumen
        $total = $subtotal + $insurance + $platformFee;

        // Generate Booking Code: DST-2026-XXXX-CODE
        $shortCode = strtoupper(Str::substr($destination->slug, 0, 3));
        $randDigits = str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);
        $bookingCode = 'DST-' . date('Y') . '-' . date('md') . $randDigits . '-' . $shortCode;

        $booking = Booking::create([
            'booking_code' => $bookingCode,
            'institution_id' => $institution->id,
            'user_id' => $user->id,
            'destination_id' => $destination->id,
            'inquiry_type' => $request->inquiry_type,
            'planned_date_start' => $request->planned_date_start,
            'planned_date_end' => $request->planned_date_end,
            'participant_count' => $participantCount,
            'guide_count' => (int) $request->guide_count,
            'purpose_notes' => $request->purpose_notes,
            'needs_permit_letter' => $request->has('needs_permit_letter'),
            'subtotal_amount' => $subtotal,
            'insurance_amount' => $insurance,
            'platform_fee' => $platformFee,
            'total_amount' => $total,
            'status' => 'menunggu_pembayaran',
            'internal_notes' => 'Pengajuan otomatis melalui form booking buyer portal.',
        ]);

        // Buat tagihan payment pending
        Payment::create([
            'booking_id' => $booking->id,
            'gateway_ref' => 'MID-SNAP-' . date('Ymd') . '-' . rand(1000, 9999),
            'payment_method' => 'bca_va',
            'amount' => $total,
            'status' => 'pending',
        ]);

        // Buat invoice dokumen awal
        Document::create([
            'booking_id' => $booking->id,
            'type' => 'invoice',
            'title' => 'Invoice Resmi #' . $bookingCode,
            'file_path' => 'assets/docs/invoice-' . strtolower($bookingCode) . '.pdf',
            'generated_at' => Carbon::now(),
        ]);

        return redirect()->route('buyer.booking.show', $booking->booking_code)
            ->with('success', 'Pengajuan booking rombongan berhasil dibuat. Silakan lanjutkan ke konfirmasi verifikasi & pembayaran.');
    }

    /**
     * Halaman 3: Detail Booking & Status Verifikasi
     */
    public function showBooking($code)
    {
        $user = $this->ensureBuyerAuth();
        $institution = $user->institution;

        $booking = Booking::with(['destination.contacts', 'payment', 'documents'])
            ->where('booking_code', $code)
            ->where('user_id', $user->id)
            ->firstOrFail();

        return view('buyer.booking.show', compact('user', 'institution', 'booking'));
    }

    /**
     * Halaman 4: Pembayaran / Checkout & Simulasi Lunas
     */
    public function payment($code)
    {
        $user = $this->ensureBuyerAuth();
        $institution = $user->institution;

        $booking = Booking::with(['destination', 'payment'])
            ->where('booking_code', $code)
            ->where('user_id', $user->id)
            ->firstOrFail();

        return view('buyer.booking.payment', compact('user', 'institution', 'booking'));
    }

    /**
     * Proses / Simulasi Pembayaran Instan (Sandbox Midtrans / BOS / VA)
     */
    public function processPayment(Request $request, $code)
    {
        $user = $this->ensureBuyerAuth();

        $booking = Booking::with(['destination.partner', 'institution', 'payment', 'documents'])
            ->where('booking_code', $code)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $method = $request->input('payment_method', 'bca_va');

        // Perbarui Payment menjadi Settled
        if ($booking->payment) {
            $booking->payment->update([
                'payment_method' => $method,
                'status' => 'settled',
                'paid_at' => Carbon::now(),
            ]);
        }

        // Perbarui Status Booking menjadi 'dibayar' sesuai Alur PRD v1.0
        // (Menunggu konfirmasi slot oleh Mitra Pengelola Tapak)
        $booking->update([
            'status' => 'dibayar',
        ]);

        // Kirim Log Notifikasi ke Pengelola Tapak & Buyer
        \App\Models\NotificationLog::create([
            'recipient_type' => 'partner',
            'recipient_name' => $booking->destination->partner->name ?? 'Pengelola Tapak',
            'channel' => 'whatsapp',
            'target' => $booking->destination->partner->phone ?? '0812-3456-7890',
            'event_type' => 'booking_created',
            'title' => 'Pembayaran Booking #' . $booking->booking_code . ' Diterima',
            'preview_text' => 'Rombongan ' . ($booking->institution->institution_name ?? 'Institusi') . ' (' . $booking->participant_count . ' peserta) telah menyelesaikan pembayaran dan menunggu konfirmasi kesiapan slot dari Anda.',
            'status' => 'sent',
            'sent_at' => Carbon::now(),
        ]);

        \App\Models\NotificationLog::create([
            'recipient_type' => 'buyer',
            'recipient_name' => $user->name,
            'channel' => 'email',
            'target' => $user->email,
            'event_type' => 'payment_reminder',
            'title' => 'Pembayaran Booking #' . $booking->booking_code . ' Lunas',
            'preview_text' => 'Pembayaran Anda berhasil diverifikasi. Pengelola tapak sedang meninjau kesiapan slot rombongan.',
            'status' => 'sent',
            'sent_at' => Carbon::now(),
        ]);

        return redirect()->route('buyer.booking.show', $booking->booking_code)
            ->with('success', 'Pembayaran sebesar Rp ' . number_format($booking->total_amount, 0, ',', '.') . ' berhasil diverifikasi lunas! Status pengajuan kini: DIBAYAR (Menunggu konfirmasi kesiapan slot oleh Pengelola Tapak).');
    }

    /**
     * Halaman 5: Riwayat Transaksi & Re-order
     */
    public function history(Request $request)
    {
        $user = $this->ensureBuyerAuth();
        $institution = $user->institution;

        $statusFilter = $request->query('status', 'semua');

        $query = Booking::with(['destination', 'payment', 'documents'])
            ->where('user_id', $user->id);

        if ($statusFilter && $statusFilter !== 'semua') {
            $query->where('status', $statusFilter);
        }

        $bookings = $query->orderBy('created_at', 'desc')->get();

        // Hitung total nilai transaksi institusi yang sudah diselesaikan
        $totalExpenditure = Booking::where('user_id', $user->id)
            ->whereIn('status', ['dibayar', 'dikonfirmasi', 'selesai'])
            ->sum('total_amount');

        $totalPax = Booking::where('user_id', $user->id)
            ->whereIn('status', ['dibayar', 'dikonfirmasi', 'selesai'])
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
     * Halaman 6: Profil Institusi & Dokumen Pendukung
     */
    public function profile()
    {
        $user = $this->ensureBuyerAuth();
        $institution = $user->institution;

        return view('buyer.profile', compact('user', 'institution'));
    }

    /**
     * Simpan Perubahan Profil Institusi
     */
    public function updateProfile(Request $request)
    {
        $user = $this->ensureBuyerAuth();
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
     * Unduh / Tampilkan Dokumen Resmi Berstempel Destinara
     */
    public function downloadDoc($code, $type)
    {
        $user = $this->ensureBuyerAuth();
        $booking = Booking::with(['destination', 'institution', 'payment'])
            ->where('booking_code', $code)
            ->where('user_id', $user->id)
            ->firstOrFail();
        return view('buyer.documents.template', compact('booking', 'type'));
    }
}
