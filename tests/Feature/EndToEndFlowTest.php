<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\BookingStatus;
use App\Enums\DestinationStatus;
use App\Enums\DocumentType;
use App\Enums\PayoutStatus;
use App\Enums\VerificationStatus;
use App\Livewire\Buyer\BookingForm;
use App\Models\AvailabilitySlot;
use App\Models\Bank;
use App\Models\Booking;
use App\Models\CancellationPolicy;
use App\Models\Destination;
use App\Models\DestinationCategory;
use App\Models\Document;
use App\Models\Institution;
use App\Models\Package;
use App\Models\PartnerPayout;
use App\Models\PartnerProfile;
use App\Models\Province;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EndToEndFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $partner;

    protected User $buyer;

    protected Bank $bank;

    protected Province $province;

    protected DestinationCategory $category;

    protected CancellationPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('local');

        $this->bank = Bank::where('is_active', true)->first() ?? Bank::create([
            'name' => 'Bank Mandiri',
            'code' => 'BMRI',
            'account_number' => '1370012345678',
            'account_holder' => 'PT Destinara Chakrawal Artha',
            'is_active' => true,
        ]);

        $this->province = Province::first();
        $this->category = DestinationCategory::first();
        $this->policy = CancellationPolicy::first();

        // 1. Admin User with full permissions
        $this->admin = User::factory()->create([
            'name' => 'Super Administrator',
            'email' => 'admin@destinara.test',
            'account_type' => AccountType::INTERNAL,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->admin->givePermissionTo(Permission::all());

        // 2. Partner User with Verified Profile
        $this->partner = User::factory()->create([
            'name' => 'Lurah Adat Mangunan',
            'email' => 'partner@mangunan.test',
            'phone' => '6281234567890',
            'account_type' => AccountType::PARTNER,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        PartnerProfile::create([
            'user_id' => $this->partner->id,
            'organization_name' => 'Komunitas Kaki Langit',
            'business_type' => 'pokdarwis',
            'organization_type' => 'pokdarwis',
            'pic_name' => 'Lurah Adat',
            'pic_phone' => '6281234567890',
            'pic_email' => 'partner@mangunan.test',
            'address' => 'Mangunan, Dlingo, Bantul',
            'city' => 'Bantul',
            'province' => 'DI Yogyakarta',
            'bank_id' => $this->bank->id,
            'bank_account_number' => '1370098765432',
            'bank_account_holder' => 'Pokdarwis Kaki Langit',
            'verification_status' => VerificationStatus::VERIFIED,
            'verified_at' => now(),
        ]);

        // 3. Buyer User with Verified Email & Institution
        $this->buyer = User::factory()->create([
            'name' => 'Drs. Subroto',
            'email' => 'subroto@sekolah.test',
            'phone' => '6281987654321',
            'account_type' => AccountType::BUYER,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        Institution::create([
            'user_id' => $this->buyer->id,
            'institution_name' => 'SMA Negeri 1 Nusantara',
            'npsn' => '20401234',
            'pic_name' => 'Drs. Subroto',
            'pic_phone' => '6281987654321',
            'pic_email' => 'subroto@sekolah.test',
            'address' => 'Jl. Pendidikan No. 45',
            'city' => 'Yogyakarta',
            'province' => 'DI Yogyakarta',
            'is_verified' => true,
        ]);
    }

    public function test_complete_end_to_end_lifecycle_flow(): void
    {
        // =========================================================================
        // TAHAP 1: Admin membuat Destinasi + Paket + Slot via HTTP
        // =========================================================================
        $this->actingAs($this->admin);

        // 1.1 Buat Destinasi (Draft)
        $destResponse = $this->post(route('admin.destinations.store'), [
            'partner_id' => $this->partner->id,
            'category_id' => $this->category->id,
            'province_id' => $this->province->id,
            'cancellation_policy_id' => $this->policy->id,
            'name' => 'Kampung Adat Mangunan Terpadu',
            'city' => 'Bantul',
            'address' => 'Jl. Hutan Pinus No. 10',
            'short_description' => 'Ekskursi lapangan pelestarian lanskap dan budaya adat.',
            'description' => 'Deskripsi komprehensif mengenai laboratorium hidup di perbukitan Dlingo Bantul dengan partisipasi penuh tetua adat setempat.',
            'default_daily_capacity' => 100,
        ]);
        $destResponse->assertRedirect(route('admin.destinations.index'));

        $destination = Destination::where('name', 'Kampung Adat Mangunan Terpadu')->firstOrFail();
        $this->assertEquals(DestinationStatus::DRAFT, $destination->status);

        // 1.2 Buat Paket Edukasi
        $pkgResponse = $this->post(route('admin.destinations.packages.save', $destination->id), [
            'name' => 'Paket Studi Konservasi 1 Hari',
            'description' => 'Pembelajaran botani, kerajinan bambu, dan kearifan air.',
            'price_per_pax' => 75000,
            'min_participants' => 10,
            'max_participants' => 100,
            'duration_days' => 1,
            'inquiry_types' => ['study_tour'],
            'is_active' => true,
        ]);
        $pkgResponse->assertRedirect();

        $package = Package::where('destination_id', $destination->id)->firstOrFail();
        $this->assertTrue((bool) $package->is_active);

        // 1.3 Bulk Generate Slot Ketersediaan (H+10 s/d H+15)
        $visitDate = Carbon::today()->addDays(12)->toDateString();
        $startDate = Carbon::today()->addDays(10)->toDateString();
        $endDate = Carbon::today()->addDays(15)->toDateString();

        $slotResponse = $this->post(route('admin.destinations.slots.bulk', $destination->id), [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days_of_week' => [0, 1, 2, 3, 4, 5, 6],
            'capacity' => 80,
        ]);
        $slotResponse->assertRedirect();

        $slot = AvailabilitySlot::where('destination_id', $destination->id)
            ->whereDate('date', $visitDate)
            ->firstOrFail();
        $this->assertEquals(80, $slot->capacity);
        $this->assertEquals(0, $slot->held_count);
        $this->assertEquals(0, $slot->booked_count);

        // 1.4 Admin Menyetujui Destinasi (Moderate -> Published)
        $approveResponse = $this->post(route('admin.destinations.updateStatus', $destination->id), [
            'action' => 'approve',
            'notes' => 'Kelengkapan paket dan profil adat memenuhi standar kurasi Destinara.',
        ]);
        $approveResponse->assertRedirect();
        $destination->refresh();
        $this->assertEquals(DestinationStatus::PUBLISHED, $destination->status);

        // =========================================================================
        // TAHAP 2: Buyer Booking via Livewire
        // =========================================================================
        $this->actingAs($this->buyer);

        Livewire::test(BookingForm::class, ['destination' => $destination])
            ->set('packageId', $package->id)
            ->set('participantCount', 20)
            ->set('plannedDateStart', $visitDate)
            ->call('recalculate')
            ->assertSet('isSlotAvailable', true)
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect();

        $booking = Booking::where('user_id', $this->buyer->id)
            ->where('destination_id', $destination->id)
            ->latest('id')
            ->firstOrFail();

        // Verifikasi Status & Slot Held
        $this->assertEquals(BookingStatus::MENUNGGU_PEMBAYARAN, $booking->status);
        $slot->refresh();
        $this->assertEquals(20, $slot->held_count);
        $this->assertEquals(0, $slot->booked_count);

        // Verifikasi Notifikasi Booking Created
        $this->assertDatabaseHas('notification_logs', [
            'event_key' => 'booking.created',
            'notifiable_id' => $this->buyer->id,
        ]);

        // Verifikasi Dokumen Invoice Diterbitkan & File Ada
        $docInvoice = Document::where('booking_id', $booking->id)
            ->where('type', DocumentType::INVOICE)
            ->firstOrFail();
        Storage::disk('local')->assertExists($docInvoice->file_path);

        // Verifikasi Audit Log
        $this->assertDatabaseHas('audit_logs', [
            'target_id' => $booking->id,
            'action' => 'booking_created',
        ]);

        // =========================================================================
        // TAHAP 3: Webhook Xendit PAID
        // =========================================================================
        $payment = $booking->payments()->latest('id')->firstOrFail();

        setting(['xendit_callback_token' => 'test-xendit-token']);

        $webhookResponse = $this->withHeaders([
            'x-callback-token' => 'test-xendit-token',
        ])->postJson('/webhooks/payments/xendit', [
            'id' => 'evt-xendit-'.uniqid(),
            'external_id' => $payment->external_id,
            'status' => 'PAID',
            'amount' => $payment->amount,
            'paid_amount' => $payment->amount,
            'paid_at' => now()->toIso8601String(),
            'payment_method' => 'BANK_TRANSFER',
            'payment_channel' => 'BCA',
        ]);
        $webhookResponse->assertStatus(200);

        // Verifikasi Transisi Status & Slot Promoted (Held -> Booked)
        $booking->refresh();
        $this->assertEquals(BookingStatus::DIBAYAR, $booking->status);

        $slot->refresh();
        $this->assertEquals(0, $slot->held_count);
        $this->assertEquals(20, $slot->booked_count);

        // Verifikasi Notifikasi Payment Paid
        $this->assertDatabaseHas('notification_logs', [
            'event_key' => 'payment.paid',
        ]);

        // Verifikasi Dokumen Kwitansi (Receipt) Diterbitkan & File Ada
        $docReceipt = Document::where('booking_id', $booking->id)
            ->where('type', DocumentType::RECEIPT)
            ->firstOrFail();
        Storage::disk('local')->assertExists($docReceipt->file_path);

        // =========================================================================
        // TAHAP 4: Partner Konfirmasi Booking
        // =========================================================================
        $this->actingAs($this->partner);

        $confirmResponse = $this->post(route('mitra.booking.confirm', $booking->id));
        $confirmResponse->assertRedirect();

        $booking->refresh();
        $this->assertEquals(BookingStatus::DIKONFIRMASI, $booking->status);

        // Verifikasi Notifikasi Booking Confirmed
        $this->assertDatabaseHas('notification_logs', [
            'event_key' => 'booking.confirmed',
        ]);

        // Verifikasi Penerbitan Confirmation Letter & e-Voucher Beserta File-nya
        $docLetter = Document::where('booking_id', $booking->id)
            ->where('type', DocumentType::CONFIRMATION_LETTER)
            ->firstOrFail();
        $docVoucher = Document::where('booking_id', $booking->id)
            ->where('type', DocumentType::E_VOUCHER)
            ->firstOrFail();

        Storage::disk('local')->assertExists($docLetter->file_path);
        Storage::disk('local')->assertExists($docVoucher->file_path);

        // =========================================================================
        // TAHAP 5: Check-in Peserta di Lapangan (Signed URL)
        // =========================================================================
        // Simulasikan hari pelaksanaan ekskursi tiba
        Carbon::setTestNow(Carbon::parse($visitDate)->setHour(9));

        // 5.1 Buka Halaman Check-in Bertanda Tangan Digital
        $signedUrl = URL::signedRoute('mitra.checkin.show', ['code' => $booking->booking_code]);
        $checkinPageResponse = $this->actingAs($this->partner)->get($signedUrl);
        $checkinPageResponse->assertStatus(200);

        // 5.2 Konfirmasi Kedatangan Rombongan (Actual Pax)
        $checkinConfirmResponse = $this->actingAs($this->partner)->post(
            route('mitra.checkin.confirm', $booking->booking_code),
            [
                'actual_participant_count' => 20,
                'note' => 'Seluruh rombongan siswa SMA 1 tiba tepat waktu dengan 2 pembina.',
            ]
        );
        $checkinConfirmResponse->assertRedirect();

        $booking->refresh();
        $this->assertEquals(BookingStatus::BERLANGSUNG, $booking->status);

        $this->assertDatabaseHas('check_ins', [
            'booking_id' => $booking->id,
            'actual_participant_count' => 20,
        ]);

        $this->assertDatabaseHas('notification_logs', [
            'event_key' => 'booking.checked_in',
        ]);

        // =========================================================================
        // TAHAP 6: Auto-Complete Jadwal Ekskursi Selesai
        // =========================================================================
        // Majukan waktu 2 hari setelah kunjungan selesai
        Carbon::setTestNow(Carbon::parse($visitDate)->addDays(2)->setHour(18));

        $this->artisan('bookings:auto-complete')->assertExitCode(0);

        $booking->refresh();
        $this->assertEquals(BookingStatus::SELESAI, $booking->status);

        $this->assertDatabaseHas('notification_logs', [
            'event_key' => 'booking.completed',
        ]);

        // =========================================================================
        // TAHAP 7: Payout Generation & Admin Transfer
        // =========================================================================
        // 7.1 Generator Payout Terjadwal
        $this->artisan('payouts:generate')->assertExitCode(0);

        $payout = PartnerPayout::where('booking_id', $booking->id)->firstOrFail();
        $this->assertEquals(PayoutStatus::PENDING, $payout->status);
        $this->assertEquals($booking->partner_net_amount, $payout->net_amount);
        $this->assertNotNull($payout->payout_number);

        // 7.2 Admin Eksekusi Transfer Payout
        $this->actingAs($this->admin);

        // Percepat tanggal eligible agar siap ditransfer
        $payout->update(['eligible_at' => now()->subHour()]);

        $proofFile = UploadedFile::fake()->create('bukti_transfer_payout.pdf', 800, 'application/pdf');

        $payoutResponse = $this->post(route('admin.finance.processPayout'), [
            'payout_ids' => [$payout->id],
            'proof' => $proofFile,
            'note' => 'Penyaluran hak kelola adat via BNI Giro Giro Destinara.',
        ]);
        $payoutResponse->assertRedirect();

        $payout->refresh();
        $this->assertEquals(PayoutStatus::TRANSFERRED, $payout->status);

        // Verifikasi Notifikasi Payout Transferred
        $this->assertDatabaseHas('notification_logs', [
            'event_key' => 'payout.transferred',
        ]);

        // Verifikasi Dokumen Bukti Payout Diterbitkan & File Ada
        $docPayout = Document::where('payout_id', $payout->id)
            ->where('type', DocumentType::PAYOUT_RECEIPT)
            ->firstOrFail();
        Storage::disk('local')->assertExists($docPayout->file_path);

        // Verifikasi Audit Log Akhir Penyaluran Payout
        $this->assertDatabaseHas('audit_logs', [
            'target_id' => $payout->id,
            'action' => 'payout_transferred',
        ]);
    }
}
