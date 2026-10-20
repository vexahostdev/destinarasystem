<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\BookingStatus;
use App\Enums\DestinationStatus;
use App\Enums\PaymentStatus;
use App\Enums\PayoutStatus;
use App\Enums\RefundStatus;
use App\Enums\VerificationStatus;
use App\Models\Bank;
use App\Models\Booking;
use App\Models\CancellationPolicy;
use App\Models\Destination;
use App\Models\DestinationCategory;
use App\Models\Institution;
use App\Models\Package;
use App\Models\PartnerPayout;
use App\Models\PartnerProfile;
use App\Models\Payment;
use App\Models\Province;
use App\Models\Refund;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DynamicActionModalContractTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $partner;
    protected User $buyer;
    protected Destination $destination;
    protected Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('local');
        Storage::fake('public');

        $this->admin = User::factory()->create([
            'name' => 'Super Admin Contract',
            'email' => 'superadmin.contract@destinara.test',
            'account_type' => AccountType::INTERNAL,
            'status' => 'active',
        ]);
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $this->admin->assignRole($superAdminRole);

        $province = Province::first() ?? Province::create(['name' => 'DI Yogyakarta']);
        $bank = Bank::first() ?? Bank::create(['name' => 'Bank BCA', 'code' => 'BCA', 'is_active' => true]);
        $category = DestinationCategory::first() ?? DestinationCategory::create(['name' => 'Budaya', 'slug' => 'budaya', 'is_active' => true]);
        $policy = CancellationPolicy::first() ?? CancellationPolicy::create(['name' => 'Standar', 'description' => 'Standar', 'is_active' => true]);

        $this->partner = User::factory()->create([
            'name' => 'Partner Contract',
            'email' => 'partner.contract@destinara.test',
            'account_type' => AccountType::PARTNER,
            'status' => 'active',
        ]);

        PartnerProfile::create([
            'user_id' => $this->partner->id,
            'organization_name' => 'Kelompok Adat Test',
            'verification_status' => VerificationStatus::PENDING,
            'bank_id' => $bank->id,
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'Kelompok Adat Test',
            'province_id' => $province->id,
            'city' => 'Bantul',
        ]);

        $this->destination = Destination::create([
            'partner_id' => $this->partner->id,
            'category_id' => $category->id,
            'province_id' => $province->id,
            'cancellation_policy_id' => $policy->id,
            'name' => 'Tapak Uji Kontrak',
            'slug' => 'tapak-uji-kontrak',
            'city' => 'Bantul',
            'address' => 'Jl. Adat No. 1',
            'description' => 'Destinasi uji kontrak modal.',
            'status' => DestinationStatus::PENDING_REVIEW,
        ]);

        $package = Package::create([
            'destination_id' => $this->destination->id,
            'name' => 'Paket Edukasi Kontrak',
            'slug' => 'paket-edukasi-kontrak',
            'inquiry_types' => ['study_tour'],
            'duration_days' => 1,
            'price_per_pax' => 50000,
            'min_pax' => 10,
            'max_pax' => 100,
            'is_active' => true,
        ]);

        $this->buyer = User::factory()->create([
            'name' => 'Buyer Contract',
            'email' => 'buyer.contract@destinara.test',
            'account_type' => AccountType::BUYER,
            'status' => 'active',
        ]);

        $institution = Institution::create([
            'user_id' => $this->buyer->id,
            'institution_name' => 'Sekolah Uji Kontrak',
            'pic_name' => $this->buyer->name,
            'pic_phone' => '081234567891',
            'pic_email' => $this->buyer->email,
            'verification_status' => VerificationStatus::PENDING,
            'address' => 'Jl. Sekolah 1',
            'province_id' => $province->id,
            'city' => 'Bantul',
        ]);

        $this->booking = Booking::create([
            'booking_code' => 'DST-KONTRAK-01',
            'user_id' => $this->buyer->id,
            'institution_id' => $institution->id,
            'destination_id' => $this->destination->id,
            'package_id' => $package->id,
            'inquiry_type' => 'study_tour',
            'planned_date_start' => Carbon::today()->addDays(14)->toDateString(),
            'planned_date_end' => Carbon::today()->addDays(14)->toDateString(),
            'participant_count' => 20,
            'subtotal_amount' => 1000000,
            'commission_amount' => 100000,
            'partner_net_amount' => 900000,
            'insurance_amount' => 0,
            'platform_fee_amount' => 25000,
            'total_amount' => 1025000,
            'status' => BookingStatus::DIBAYAR,
        ]);

        // Create availability slots for original date and future reschedule date
        \App\Models\AvailabilitySlot::create([
            'destination_id' => $this->destination->id,
            'date' => Carbon::today()->addDays(14)->toDateString(),
            'capacity' => 100,
            'capacity_booked' => 0,
            'capacity_held' => 20,
            'is_closed' => false,
        ]);

        $minLeadDays = (int) setting('min_lead_days', 3);
        $rescheduleDate = now()->addDays($minLeadDays + 5)->toDateString();
        \App\Models\AvailabilitySlot::create([
            'destination_id' => $this->destination->id,
            'date' => $rescheduleDate,
            'capacity' => 100,
            'capacity_booked' => 0,
            'capacity_held' => 0,
            'is_closed' => false,
        ]);
    }

    /**
     * Helper to extract field names from a form matching a pattern inside a Blade view
     */
    protected function getFieldNamesFromBlade(string $viewPath, string $formIdentifier): array
    {
        $fullPath = resource_path('views/' . str_replace('.', '/', $viewPath) . '.blade.php');
        $this->assertFileExists($fullPath);
        $content = file_get_contents($fullPath);

        // Find the form containing the identifier
        preg_match_all('/<form\b(.*?)>(.*?)<\/form>/s', $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            if (str_contains($match[0], $formIdentifier)) {
                preg_match_all('/name=["\']([a-zA-Z0-9_]+)["\']/', $match[2], $nameMatches);
                return array_unique($nameMatches[1]);
            }
        }

        $this->fail("Form with identifier '{$formIdentifier}' not found in {$viewPath}");
    }

    public function test_contract_admin_bookings_cancel_modal(): void
    {
        $fields = $this->getFieldNamesFromBlade('admin.bookings.index', "value=\"cancel\"");
        $this->assertContains('action', $fields);

        $payload = [
            'action' => 'cancel',
            'reason' => 'Pembatalan darurat oleh admin operasional melalui modal',
            'refund_percent' => 100,
        ];

        $response = $this->actingAs($this->admin)->post(
            route('admin.bookings.updateStatus', $this->booking->id),
            $payload
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->booking->refresh();
        $this->assertEquals(BookingStatus::BATAL, $this->booking->status);
    }

    public function test_contract_admin_bookings_reschedule_modal(): void
    {
        $fields = $this->getFieldNamesFromBlade('admin.bookings.index', "value=\"reschedule\"");
        $this->assertContains('action', $fields);
        $this->assertContains('new_date', $fields);
        $this->assertContains('reschedule_reason', $fields);

        $minLeadDays = (int) setting('min_lead_days', 3);
        $validNewDate = now()->addDays($minLeadDays + 5)->format('Y-m-d');

        $payload = [
            'action' => 'reschedule',
            'new_date' => $validNewDate,
            'reschedule_reason' => 'Penyesuaian jadwal kunjungan sekolah via modal daftar booking',
        ];

        $response = $this->actingAs($this->admin)->post(
            route('admin.bookings.updateStatus', $this->booking->id),
            $payload
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->booking->refresh();
        $this->assertEquals($validNewDate, $this->booking->planned_date_start->format('Y-m-d'));
    }

    public function test_contract_admin_refund_process_and_reject_modals(): void
    {
        $refund = Refund::create([
            'refund_number' => 'REF-KONTRAK-01',
            'booking_id' => $this->booking->id,
            'user_id' => $this->buyer->id,
            'amount' => 500000,
            'percent' => 50,
            'reason' => 'Permintaan refund uji kontrak',
            'status' => RefundStatus::PENDING,
        ]);

        // 1. Process Refund Modal Contract
        $processFields = $this->getFieldNamesFromBlade('admin.finance.refunds', "activeRefund + '/proses'");
        $this->assertContains('proof_file', $processFields);

        $file = UploadedFile::fake()->create('bukti_refund.pdf', 200, 'application/pdf');
        $processPayload = [
            'proof_file' => $file,
            'note' => 'Transfer refund sukses diproses via modal',
        ];

        $processResponse = $this->actingAs($this->admin)->post(
            route('admin.finance.processRefund', $refund->id),
            $processPayload
        );

        $processResponse->assertRedirect();
        $processResponse->assertSessionHas('success');
        $refund->refresh();
        $this->assertEquals(RefundStatus::COMPLETED, $refund->status);

        // 2. Reject Refund Modal Contract
        $refund2 = Refund::create([
            'refund_number' => 'REF-KONTRAK-02',
            'booking_id' => $this->booking->id,
            'user_id' => $this->buyer->id,
            'amount' => 500000,
            'percent' => 50,
            'reason' => 'Permintaan refund kedua',
            'status' => RefundStatus::PENDING,
        ]);

        $rejectFields = $this->getFieldNamesFromBlade('admin.finance.refunds', "activeRefund + '/tolak'");
        $this->assertContains('reason', $rejectFields);

        $rejectPayload = [
            'reason' => 'Pengajuan refund ditolak karena melanggar ketentuan pembatalan.',
        ];

        $rejectResponse = $this->actingAs($this->admin)->post(
            route('admin.finance.rejectRefund', $refund2->id),
            $rejectPayload
        );

        $rejectResponse->assertRedirect();
        $rejectResponse->assertSessionHas('warning');
        $refund2->refresh();
        $this->assertEquals(RefundStatus::REJECTED, $refund2->status);
    }

    public function test_contract_admin_payout_hold_modal(): void
    {
        $payout = PartnerPayout::create([
            'payout_number' => 'PO-KONTRAK-01',
            'partner_id' => $this->partner->id,
            'booking_id' => $this->booking->id,
            'gross_amount' => 1000000,
            'commission_amount' => 100000,
            'net_amount' => 900000,
            'status' => PayoutStatus::PENDING,
            'eligible_at' => now()->subDay(),
        ]);

        $holdFields = $this->getFieldNamesFromBlade('admin.finance.payouts', "activePayout + '/hold'");
        $this->assertContains('reason', $holdFields);

        $holdPayload = [
            'reason' => 'Penahanan payout sementara karena audit rekening mitra.',
        ];

        $holdResponse = $this->actingAs($this->admin)->post(
            route('admin.finance.holdPayout', $payout->id),
            $holdPayload
        );

        $holdResponse->assertRedirect();
        $holdResponse->assertSessionHas('warning');
        $payout->refresh();
        $this->assertEquals(PayoutStatus::ON_HOLD, $payout->status);
    }

    public function test_contract_admin_payment_reject_modal(): void
    {
        $payment = Payment::create([
            'external_id' => 'PAY-KONTRAK-99',
            'booking_id' => $this->booking->id,
            'payment_method' => 'manual_transfer',
            'gateway' => 'manual_transfer',
            'amount' => 1025000,
            'status' => PaymentStatus::PENDING,
            'proof_path' => 'proofs/dummy.jpg',
            'expires_at' => now()->addHours(24),
        ]);

        $rejectFields = $this->getFieldNamesFromBlade('admin.finance.payments', "rejectPaymentId + '/tolak'");
        $this->assertContains('reason', $rejectFields);

        $rejectPayload = [
            'reason' => 'Foto bukti transfer buram tidak terbaca.',
        ];

        $rejectResponse = $this->actingAs($this->admin)->post(
            route('admin.finance.rejectManual', $payment->id),
            $rejectPayload
        );

        $rejectResponse->assertRedirect();
        $rejectResponse->assertSessionHas('warning');
        $payment->refresh();
        $this->assertNull($payment->proof_path);
        $this->assertEquals(PaymentStatus::PENDING, $payment->status);
    }

    public function test_contract_admin_user_status_and_commission_modals(): void
    {
        // 1. Toggle status modal
        $statusFields = $this->getFieldNamesFromBlade('admin.users.index', "activeUser + '/status'");
        $this->assertContains('status', $statusFields);
        $this->assertContains('reason', $statusFields);

        $statusPayload = [
            'status' => 'suspended',
            'reason' => 'Suspensi sementara karena pelanggaran etika komunitas.',
        ];

        $statusResponse = $this->actingAs($this->admin)->post(
            route('admin.users.toggleStatus', $this->buyer->id),
            $statusPayload
        );

        $statusResponse->assertRedirect();
        $statusResponse->assertSessionHas('success');
        $this->buyer->refresh();
        $this->assertEquals('suspended', $this->buyer->status);

        // 2. Commission modal
        $commFields = $this->getFieldNamesFromBlade('admin.users.index', "activeUser + '/komisi'");
        $this->assertContains('commission_rate', $commFields);

        $commPayload = [
            'commission_rate' => 12.5,
        ];

        $commResponse = $this->actingAs($this->admin)->post(
            route('admin.users.updateCommission', $this->partner->id),
            $commPayload
        );

        $commResponse->assertRedirect();
        $commResponse->assertSessionHas('success');
        $this->partner->partnerProfile->refresh();
        $this->assertEquals(12.5, (float) $this->partner->partnerProfile->commission_percent_override);
    }

    public function test_contract_admin_destination_moderation_modal(): void
    {
        $fields = $this->getFieldNamesFromBlade('admin.destinations.verify', "selectedDest + '/status'");
        $this->assertContains('action', $fields);
        $this->assertContains('notes', $fields);

        // Test approve action
        $approvePayload = [
            'action' => 'approve',
            'notes' => 'Destinasi memenuhi seluruh persyaratan kurasi.',
        ];

        $approveResponse = $this->actingAs($this->admin)->post(
            route('admin.destinations.updateStatus', $this->destination->id),
            $approvePayload
        );

        $approveResponse->assertRedirect();
        $approveResponse->assertSessionHas('success');
        $this->destination->refresh();
        $this->assertEquals(DestinationStatus::PUBLISHED, $this->destination->status);
    }

    public function test_contract_admin_user_verification_reject_modal(): void
    {
        $fields = $this->getFieldNamesFromBlade('admin.users.verify', 'rejectAction');
        $this->assertContains('action', $fields);
        $this->assertContains('note', $fields);

        $rejectPayload = [
            'action' => 'reject',
            'note' => 'Dokumen akta pendirian belum dilegalisir notaris.',
        ];

        $rejectResponse = $this->actingAs($this->admin)->post(
            route('admin.users.updateVerification', ['type' => 'partner', 'id' => $this->partner->partnerProfile->id]),
            $rejectPayload
        );

        $rejectResponse->assertRedirect();
        $rejectResponse->assertSessionHas('success');
        $this->partner->partnerProfile->refresh();
        $this->assertEquals(VerificationStatus::REJECTED, $this->partner->partnerProfile->verification_status);
        $this->assertEquals('Dokumen akta pendirian belum dilegalisir notaris.', $this->partner->partnerProfile->verification_note);
    }
}
