<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\PartnerProfile;
use App\Models\PartnerPayout;
use App\Models\Destination;
use App\Models\AvailabilitySlot;
use App\Models\Booking;
use App\Models\Document;
use App\Models\NotificationLog;
use Carbon\Carbon;
use Illuminate\Support\Str;

class PartnerController extends Controller
{
    /**
     * Helper mendapatkan user Mitra Pengelola aktif di sesi
     */
    protected function ensurePartnerAuth()
    {
        return Auth::user();
    }

    /**
     * Halaman 1: Dashboard Mitra
     */
    public function dashboard(Request $request)
    {
        $user = $this->ensurePartnerAuth();
        $profile = $user->partnerProfile ?? PartnerProfile::firstOrCreate([
            'user_id' => $user->id
        ], [
            'organization_name' => 'Badan Pengelola Desa Wisata Adat Penglipuran',
            'organization_type' => 'yayasan_adat',
            'bank_name' => 'Bank BPD Bali',
            'bank_account_number' => '012-02-0049182-1',
            'bank_account_holder' => 'BPD PENGELOLA DESA WISATA PENGLIPURAN',
            'verification_status' => 'verified',
        ]);

        $destinationIds = Destination::where('partner_id', $user->id)->pluck('id');

        // Metrik Dashboard Mitra
        $pendingBookingsCount = Booking::whereIn('destination_id', $destinationIds)
            ->whereIn('status', ['menunggu_pembayaran', 'dibayar'])
            ->count();

        $confirmedBookingsCount = Booking::whereIn('destination_id', $destinationIds)
            ->where('status', 'dikonfirmasi')
            ->count();

        // Pendapatan siap tarik (pending payouts)
        $readyBalance = PartnerPayout::where('partner_id', $user->id)
            ->where('status', 'pending')
            ->sum('net_amount');

        // Total pendapatan yang sudah dicairkan
        $totalPaidOut = PartnerPayout::where('partner_id', $user->id)
            ->where('status', 'transferred')
            ->sum('net_amount');

        // Tapak kelolaan
        $activeDestinationsCount = Destination::where('partner_id', $user->id)
            ->where('status', 'published')
            ->count();

        // Booking masuk yang butuh tindakan segera
        $urgentBookings = Booking::with(['institution', 'destination'])
            ->whereIn('destination_id', $destinationIds)
            ->whereIn('status', ['menunggu_pembayaran', 'dibayar'])
            ->orderBy('planned_date_start', 'asc')
            ->take(5)
            ->get();

        // Slot okupansi pekan ini
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
     * Halaman 2: Laporan Pendapatan & Grafik Kunjungan
     */
    public function incomeReport(Request $request)
    {
        $user = $this->ensurePartnerAuth();
        $profile = $user->partnerProfile;

        // Saldo Siap Ditarik
        $pendingPayouts = PartnerPayout::where('partner_id', $user->id)
            ->where('status', 'pending')
            ->get();
        $readyBalance = $pendingPayouts->sum('net_amount');

        // Total Dana Ditransfer
        $transferredPayouts = PartnerPayout::where('partner_id', $user->id)
            ->where('status', 'transferred')
            ->get();
        $totalTransferred = $transferredPayouts->sum('net_amount');

        // Semua Riwayat Payout
        $allPayouts = PartnerPayout::where('partner_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Rekonsiliasi data riil pendapatan 5 bulan terakhir dari basis data
        $monthlyEarnings = [];
        for ($i = 4; $i >= 0; $i--) {
            $point = Carbon::now()->subMonths($i);
            $payoutsThisMonth = PartnerPayout::where('partner_id', $user->id)
                ->whereYear('created_at', $point->year)
                ->whereMonth('created_at', $point->month)
                ->get();

            $monthlyEarnings[] = [
                'month' => $point->translatedFormat('M'),
                'gross' => (float)$payoutsThisMonth->sum('gross_amount'),
                'net' => (float)$payoutsThisMonth->sum('net_amount'),
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
     * Ajukan Penarikan Dana / Payout
     */
    public function requestPayout(Request $request)
    {
        $user = $this->ensurePartnerAuth();

        $pending = PartnerPayout::where('partner_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($pending) {
            $pending->update([
                'status' => 'transferred',
                'transferred_at' => Carbon::now(),
                'receipt_doc_path' => 'payouts/receipt-TRF-' . $pending->id . '.pdf',
            ]);

            return redirect()->route('mitra.income')
                ->with('success', 'Permintaan penarikan dana sebesar Rp ' . number_format($pending->net_amount, 0, ',', '.') . ' berhasil diajukan dan diproses transfer ke rekening ' . ($user->partnerProfile->bank_name ?? 'BPD Bali') . '.');
        }

        return redirect()->route('mitra.income')
            ->with('info', 'Saat ini tidak ada saldo pending yang siap ditarik.');
    }

    /**
     * Halaman 3: Kelola Listing Destinasi
     */
    public function destinations(Request $request)
    {
        $user = $this->ensurePartnerAuth();
        $profile = $user->partnerProfile;

        $destinations = Destination::where('partner_id', $user->id)
            ->withCount(['bookings', 'slots'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('partner.destinations.index', compact('user', 'profile', 'destinations'));
    }

    /**
     * Halaman 4: Kelola Ketersediaan / Kalender Slot
     */
    public function availability(Request $request)
    {
        $user = $this->ensurePartnerAuth();
        $profile = $user->partnerProfile;

        $destinations = Destination::where('partner_id', $user->id)->get();
        $selectedSlug = $request->query('destinasi');
        $currentDestination = $selectedSlug 
            ? Destination::where('slug', $selectedSlug)->first() 
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
     * Update Status Slot Tanggal
     */
    public function updateSlot(Request $request)
    {
        $user = $this->ensurePartnerAuth();

        $request->validate([
            'slot_id' => 'required|exists:availability_slots,id',
            'status' => 'required|in:open,full,closed',
            'capacity' => 'required|integer|min:10|max:500',
        ]);

        $slot = AvailabilitySlot::findOrFail($request->slot_id);
        $slot->update([
            'status' => $request->status,
            'capacity' => $request->capacity,
        ]);

        return redirect()->back()->with('success', 'Ketersediaan kuota tanggal ' . Carbon::parse($slot->date)->translatedFormat('d M Y') . ' berhasil diperbarui.');
    }

    /**
     * Halaman 5: Daftar Booking Masuk
     */
    public function incomingBookings(Request $request)
    {
        $user = $this->ensurePartnerAuth();
        $profile = $user->partnerProfile;

        $destinationIds = Destination::where('partner_id', $user->id)->pluck('id');
        $statusFilter = $request->query('status', 'semua');

        $query = Booking::with(['institution', 'destination', 'payment'])
            ->whereIn('destination_id', $destinationIds);

        if ($statusFilter && $statusFilter !== 'semua') {
            $query->where('status', $statusFilter);
        }

        $bookings = $query->orderBy('planned_date_start', 'asc')->get();

        return view('partner.bookings.index', compact(
            'user',
            'profile',
            'bookings',
            'statusFilter'
        ));
    }

    /**
     * Aksi Konfirmasi Penerimaan Booking Masuk (Alur PRD v1.0 Tahap 6)
     */
    public function confirmBooking($id)
    {
        $user = $this->ensurePartnerAuth();
        $destinationIds = Destination::where('partner_id', $user->id)->pluck('id');

        $booking = Booking::with(['institution', 'destination', 'documents'])
            ->whereIn('destination_id', $destinationIds)
            ->findOrFail($id);

        $booking->update([
            'status' => 'dikonfirmasi',
            'internal_notes' => ($booking->internal_notes ? $booking->internal_notes . ' ' : '') . '[Dikonfirmasi Pengelola Tapak pada ' . Carbon::now()->translatedFormat('d M Y, H:i') . ']',
        ]);

        // 1. Update atau Buat Slot Ketersediaan Kalender
        $slotDate = Carbon::parse($booking->planned_date_start)->format('Y-m-d');
        $slot = AvailabilitySlot::firstOrCreate(
            ['destination_id' => $booking->destination_id, 'date' => $slotDate],
            ['capacity' => 100, 'booked_count' => 0, 'status' => 'open']
        );
        $slot->booked_count += $booking->participant_count;
        if ($slot->booked_count >= $slot->capacity) {
            $slot->status = 'full';
        }
        $slot->save();

        // 2. Generate Dokumen Resmi (Surat Konfirmasi & Draf Izin)
        if (!$booking->documents()->where('type', 'surat_konfirmasi')->exists()) {
            Document::create([
                'booking_id' => $booking->id,
                'type' => 'surat_konfirmasi',
                'title' => 'Surat Konfirmasi Kunjungan & e-Pass #' . $booking->booking_code,
                'file_path' => 'assets/docs/epass-' . strtolower($booking->booking_code) . '.pdf',
                'generated_at' => Carbon::now(),
            ]);
        }

        if ($booking->needs_permit_letter && !$booking->documents()->where('type', 'draf_surat_izin')->exists()) {
            Document::create([
                'booking_id' => $booking->id,
                'type' => 'draf_surat_izin',
                'title' => 'Draf Surat Permohonan Izin Riset Lapangan #' . $booking->booking_code,
                'file_path' => 'assets/docs/izin-' . strtolower($booking->booking_code) . '.pdf',
                'generated_at' => Carbon::now(),
            ]);
        }

        // 3. Notifikasi Otomatis ke Buyer & Admin
        NotificationLog::create([
            'recipient_type' => 'buyer',
            'recipient_name' => $booking->institution->pic_name ?? 'Penanggung Jawab',
            'channel' => 'whatsapp',
            'target' => $booking->institution->pic_phone ?? '0812-0000-0000',
            'event_type' => 'epass_issued',
            'title' => 'Reservasi #' . $booking->booking_code . ' Dikonfirmasi!',
            'preview_text' => 'Kabar gembira! Pengelola tapak ' . $booking->destination->name . ' telah menyetujui kunjungan rombongan Anda. e-Pass resmi kini dapat diunduh.',
            'status' => 'sent',
            'sent_at' => Carbon::now(),
        ]);

        return redirect()->back()->with('success', 'Rombongan ' . $booking->institution->institution_name . ' (' . $booking->participant_count . ' peserta) berhasil dikonfirmasi diterima! Dokumen e-Pass dan surat izin telah diterbitkan.');
    }

    /**
     * Aksi Tolak Booking Masuk
     */
    public function rejectBooking(Request $request, $id)
    {
        $user = $this->ensurePartnerAuth();
        $destinationIds = Destination::where('partner_id', $user->id)->pluck('id');

        $booking = Booking::with(['institution', 'destination'])
            ->whereIn('destination_id', $destinationIds)
            ->findOrFail($id);

        $reason = $request->input('reason', 'Kapasitas balai adat penuh karena upacara adat.');

        $booking->update([
            'status' => 'batal',
            'internal_notes' => ($booking->internal_notes ? $booking->internal_notes . ' ' : '') . '[Ditolak Pengelola: ' . $reason . ']',
        ]);

        NotificationLog::create([
            'recipient_type' => 'buyer',
            'recipient_name' => $booking->institution->pic_name ?? 'Penanggung Jawab',
            'channel' => 'whatsapp',
            'target' => $booking->institution->pic_phone ?? '0812-0000-0000',
            'event_type' => 'payment_reminder',
            'title' => 'Pengajuan Booking #' . $booking->booking_code . ' Ditolak',
            'preview_text' => 'Mohon maaf, pengajuan booking tapak belum dapat diterima dengan alasan: ' . $reason,
            'status' => 'sent',
            'sent_at' => Carbon::now(),
        ]);

        return redirect()->back()->with('error', 'Booking rombongan #' . $booking->booking_code . ' telah ditolak dengan alasan: ' . $reason);
    }

    /**
     * Halaman 6: Form Ajukan Destinasi Baru (Form Kurasi Panjang)
     */
    public function createDestination(Request $request)
    {
        $user = $this->ensurePartnerAuth();
        $profile = $user->partnerProfile;

        return view('partner.destinations.create', compact('user', 'profile'));
    }

    /**
     * Simpan Pengajuan Destinasi Baru ke Antrean Kurasi Admin
     */
    public function storeDestination(Request $request)
    {
        $user = $this->ensurePartnerAuth();

        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string',
            'province' => 'required|string',
            'city' => 'required|string',
            'price_per_pax' => 'required|numeric|min:10000',
            'description' => 'required|string',
            'educational_highlights' => 'required|string',
        ]);

        $slug = Str::slug($request->name);
        if (Destination::where('slug', $slug)->exists()) {
            $slug .= '-' . rand(10, 99);
        }

        $dest = Destination::create([
            'partner_id' => $user->id,
            'name' => $request->name,
            'slug' => $slug,
            'category' => $request->category,
            'province' => $request->province,
            'city' => $request->city,
            'price_per_pax' => $request->price_per_pax,
            'description' => $request->description,
            'educational_highlights' => $request->educational_highlights,
            'facilities' => $request->input('facilities', ['Toilet Lapangan', 'Area Pertemuan Rombongan', 'Pemandu Adat']),
            'suitable_for' => $request->input('suitable_for', ['study_tour', 'penelitian']),
            'has_permit_document' => true,
            'cover_image' => 'assets/img/hd/dest-wonosadi.jpg',
            'status' => 'pending_review', // Masuk antrean verifikasi admin operasional
        ]);

        // Buat slot ketersediaan 14 hari
        for ($d = 1; $d <= 14; $d++) {
            AvailabilitySlot::create([
                'destination_id' => $dest->id,
                'date' => Carbon::now()->addDays($d)->toDateString(),
                'capacity' => 100,
                'booked_count' => 0,
                'status' => 'open',
            ]);
        }

        return redirect()->route('mitra.destinations')
            ->with('success', 'Pengajuan tapak "' . $dest->name . '" berhasil dikirim ke antrean kurasi Tim Admin Destinara.');
    }

    /**
     * Halaman 7: Profil Pengelola & Rekening Payout
     */
    public function profile()
    {
        $user = $this->ensurePartnerAuth();
        $profile = $user->partnerProfile ?? PartnerProfile::firstOrCreate(['user_id' => $user->id], [
            'organization_name' => 'Badan Pengelola Desa Wisata Adat Penglipuran',
            'organization_type' => 'yayasan_adat',
        ]);

        return view('partner.profile', compact('user', 'profile'));
    }

    /**
     * Simpan Perubahan Profil & Rekening Bank Mitra
     */
    public function updateProfile(Request $request)
    {
        $user = $this->ensurePartnerAuth();
        $profile = $user->partnerProfile;

        $request->validate([
            'organization_name' => 'required|string|max:255',
            'organization_type' => 'required|string',
            'bank_name' => 'required|string|max:100',
            'bank_account_number' => 'required|string|max:50',
            'bank_account_holder' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'contact_phone' => 'required|string|max:50',
        ]);

        $profile->update([
            'organization_name' => $request->organization_name,
            'organization_type' => $request->organization_type,
            'legal_document_number' => $request->legal_document_number,
            'bank_name' => $request->bank_name,
            'bank_account_number' => $request->bank_account_number,
            'bank_account_holder' => $request->bank_account_holder,
        ]);

        $user->update([
            'name' => $request->contact_name,
            'phone' => $request->contact_phone,
        ]);

        return redirect()->route('mitra.profile')
            ->with('success', 'Profil lembaga pengelola dan nomor rekening pencairan berhasil diperbarui.');
    }
}
