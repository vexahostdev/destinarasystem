<?php

namespace Tests\Feature;

use App\Domain\Booking\Services\BookingService;
use App\Domain\Payment\Services\PaymentService;
use App\Enums\AccountType;
use App\Enums\BookingStatus;
use App\Enums\DestinationStatus;
use App\Enums\PaymentStatus;
use App\Enums\VerificationStatus;
use App\Models\AvailabilitySlot;
use App\Models\Booking;
use App\Models\CancellationPolicy;
use App\Models\Destination;
use App\Models\DestinationCategory;
use App\Models\Institution;
use App\Models\Package;
use App\Models\PartnerProfile;
use App\Models\Payment;
use App\Models\Province;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected User $buyer;

    protected User $partnerUser;

    protected User $adminUser;

    protected Destination $destination;

    protected Package $package;

    protected AvailabilitySlot $slot;

    protected Booking $booking;

    protected Payment $payment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        // Configure test settings
        setting(['xendit_callback_token' => 'test-xendit-token']);
        setting(['midtrans_server_key' => 'test-midtrans-server-key']);

        $this->buyer = User::factory()->create([
            'account_type' => AccountType::BUYER,
            'status' => 'active',
        ]);

        Institution::create([
            'user_id' => $this->buyer->id,
            'institution_name' => 'SMP Negeri 5 Yogyakarta',
            'pic_name' => 'Siti Aminah',
            'pic_phone' => '6281234567899',
            'pic_email' => 'siti@smp5.test',
        ]);

        $this->partnerUser = User::factory()->create([
            'account_type' => AccountType::PARTNER,
            'status' => 'active',
        ]);

        PartnerProfile::create([
            'user_id' => $this->partnerUser->id,
            'organization_name' => 'Kelompok Sadar Wisata',
            'business_type' => 'pokdarwis',
            'pic_name' => 'Pak Lurah',
            'pic_phone' => '6281234567890',
            'pic_email' => 'lurah@desa.test',
            'address' => 'Jl. Desa',
            'city' => 'Bantul',
            'province' => 'DI Yogyakarta',
            'verification_status' => VerificationStatus::VERIFIED,
        ]);

        $this->adminUser = User::factory()->create([
            'account_type' => AccountType::INTERNAL,
            'status' => 'active',
        ]);

        $province = Province::first();
        $policy = CancellationPolicy::first();
        $category = DestinationCategory::firstOrCreate(['slug' => 'mangrove'], ['name' => 'Mangrove', 'is_active' => true]);

        $this->destination = Destination::create([
            'partner_id' => $this->partnerUser->id,
            'category_id' => $category->id,
            'province_id' => $province->id,
            'cancellation_policy_id' => $policy->id,
            'name' => 'Wisata Mangrove Baros',
            'slug' => 'wisata-mangrove-baros',
            'city' => 'Bantul',
            'address' => 'Tirtohargo, Kretek',
            'description' => 'Ekowisata Hutan Mangrove Baros.',
            'status' => DestinationStatus::PUBLISHED,
        ]);

        $this->package = Package::create([
            'destination_id' => $this->destination->id,
            'name' => 'Paket Tanam Mangrove',
            'slug' => 'paket-tanam-mangrove',
            'inquiry_types' => ['study_tour'],
            'duration_days' => 1,
            'price_per_pax' => 50000,
            'min_pax' => 10,
            'max_pax' => 100,
            'is_active' => true,
        ]);

        $this->slot = AvailabilitySlot::create([
            'destination_id' => $this->destination->id,
            'date' => Carbon::now()->addDays(10)->toDateString(),
            'capacity' => 50,
            'booked_count' => 0,
            'held_count' => 0,
            'status' => 'open',
        ]);

        // Create booking
        $bookingService = app(BookingService::class);
        $this->booking = $bookingService->create([
            'destination_id' => $this->destination->id,
            'package_id' => $this->package->id,
            'inquiry_type' => 'study_tour',
            'planned_date_start' => $this->slot->date,
            'participant_count' => 10,
        ], $this->buyer);

        $this->payment = $this->booking->latestPayment;
    }

    public function test_xendit_webhook_invalid_token_returns_401_and_logs(): void
    {
        $payload = [
            'id' => 'inv_test_123',
            'external_id' => $this->payment->external_id,
            'status' => 'PAID',
            'amount' => $this->payment->amount,
            'paid_amount' => $this->payment->amount,
        ];

        $response = $this->postJson('/webhooks/payments/xendit', $payload, [
            'x-callback-token' => 'wrong-token',
        ]);

        $response->assertStatus(401);

        $this->assertDatabaseHas('payment_webhook_logs', [
            'gateway' => 'xendit',
            'external_id' => $this->payment->external_id,
            'signature_valid' => false,
        ]);
    }

    public function test_xendit_webhook_paid_promotes_slots_and_transitions_booking(): void
    {
        $payload = [
            'id' => 'inv_test_123',
            'external_id' => $this->payment->external_id,
            'status' => 'PAID',
            'amount' => $this->payment->amount,
            'paid_amount' => $this->payment->amount,
            'paid_at' => now()->toIso8601String(),
            'payment_method' => 'BANK_TRANSFER',
            'payment_channel' => 'BCA',
        ];

        $response = $this->postJson('/webhooks/payments/xendit', $payload, [
            'x-callback-token' => 'test-xendit-token',
        ]);

        $response->assertStatus(200);

        $this->payment->refresh();
        $this->booking->refresh();
        $this->slot->refresh();

        $this->assertEquals(PaymentStatus::PAID, $this->payment->status);
        $this->assertEquals(BookingStatus::DIBAYAR, $this->booking->status);
        $this->assertNotNull($this->booking->partner_confirm_deadline);

        // Held slots promoted to booked slots
        $this->assertEquals(0, $this->slot->held_count);
        $this->assertEquals(10, $this->slot->booked_count);

        $this->assertDatabaseHas('payment_webhook_logs', [
            'gateway' => 'xendit',
            'external_id' => $this->payment->external_id,
            'signature_valid' => true,
        ]);
    }

    public function test_xendit_webhook_idempotency_prevents_duplicate_processing(): void
    {
        $payload = [
            'id' => 'inv_test_dup',
            'external_id' => $this->payment->external_id,
            'status' => 'PAID',
            'amount' => $this->payment->amount,
            'paid_amount' => $this->payment->amount,
            'paid_at' => now()->toIso8601String(),
        ];

        // First call
        $first = $this->postJson('/webhooks/payments/xendit', $payload, [
            'x-callback-token' => 'test-xendit-token',
        ]);
        $first->assertStatus(200);

        // Second call with same event_id
        $second = $this->postJson('/webhooks/payments/xendit', $payload, [
            'x-callback-token' => 'test-xendit-token',
        ]);
        $second->assertStatus(200);
        $second->assertJson(['message' => 'Event already processed.']);
    }

    public function test_xendit_webhook_nominal_mismatch_fails_payment(): void
    {
        $payload = [
            'id' => 'inv_test_mismatch',
            'external_id' => $this->payment->external_id,
            'status' => 'PAID',
            'amount' => $this->payment->amount + 50000, // Different amount!
            'paid_amount' => $this->payment->amount + 50000,
            'paid_at' => now()->toIso8601String(),
        ];

        $response = $this->postJson('/webhooks/payments/xendit', $payload, [
            'x-callback-token' => 'test-xendit-token',
        ]);

        $response->assertStatus(200);

        $this->payment->refresh();
        $this->booking->refresh();

        // Payment status failed
        $this->assertEquals(PaymentStatus::FAILED, $this->payment->status);
        // Booking must NOT transition to DIBAYAR
        $this->assertEquals(BookingStatus::MENUNGGU_PEMBAYARAN, $this->booking->status);
    }

    public function test_midtrans_webhook_signature_validation(): void
    {
        // 1. Invalid signature
        $invalidPayload = [
            'order_id' => $this->payment->external_id,
            'status_code' => '200',
            'gross_amount' => (string) $this->payment->amount,
            'transaction_status' => 'settlement',
            'signature_key' => 'invalid-signature-hash',
        ];

        $this->postJson('/webhooks/payments/midtrans', $invalidPayload)->assertStatus(401);

        // 2. Valid signature: sha512(order_id + status_code + gross_amount + server_key)
        $serverKey = 'test-midtrans-server-key';
        $grossStr = number_format($this->payment->amount, 2, '.', '');
        $signature = hash('sha512', $this->payment->external_id.'200'.$grossStr.$serverKey);

        $validPayload = [
            'order_id' => $this->payment->external_id,
            'status_code' => '200',
            'gross_amount' => $grossStr,
            'transaction_status' => 'settlement',
            'signature_key' => $signature,
            'transaction_id' => 'midtrans-tx-123',
        ];

        $response = $this->postJson('/webhooks/payments/midtrans', $validPayload);
        $response->assertStatus(200);

        $this->payment->refresh();
        $this->assertEquals(PaymentStatus::PAID, $this->payment->status);
    }

    public function test_midtrans_webhook_rejected_with_401_when_server_key_not_configured_k9(): void
    {
        setting(['midtrans_server_key' => '']);

        // Signature dihitung dengan server key kosong: tetap harus ditolak, bukan 500 dan bukan diterima
        $grossStr = number_format($this->payment->amount, 2, '.', '');
        $payload = [
            'order_id' => $this->payment->external_id,
            'status_code' => '200',
            'gross_amount' => $grossStr,
            'transaction_status' => 'settlement',
            'signature_key' => hash('sha512', $this->payment->external_id.'200'.$grossStr),
            'transaction_id' => 'midtrans-tx-forged',
        ];

        $this->postJson('/webhooks/payments/midtrans', $payload)->assertStatus(401);

        $this->assertDatabaseHas('payment_webhook_logs', [
            'gateway' => 'midtrans',
            'external_id' => $this->payment->external_id,
            'signature_valid' => false,
        ]);
        $this->assertNotEquals(PaymentStatus::PAID, $this->payment->fresh()->status);
    }

    public function test_manual_transfer_flow(): void
    {
        Storage::fake('local');

        $manualPayment = Payment::create([
            'booking_id' => $this->booking->id,
            'gateway' => 'manual_transfer',
            'external_id' => "{$this->booking->booking_code}-P2",
            'amount' => $this->booking->total_amount,
            'status' => PaymentStatus::PENDING,
            'expires_at' => $this->booking->expires_at,
        ]);

        $paymentService = app(PaymentService::class);

        // 1. Upload proof
        $file = UploadedFile::fake()->create('bukti_transfer.jpg', 500, 'image/jpeg');
        $paymentService->processManualProofUpload($manualPayment, $file, $this->buyer);

        $manualPayment->refresh();
        $this->assertNotNull($manualPayment->proof_path);
        Storage::disk('local')->assertExists($manualPayment->proof_path);

        // 2. Admin verifies
        $paymentService->verifyManualPayment($manualPayment, $this->adminUser, true);

        $manualPayment->refresh();
        $this->booking->refresh();

        $this->assertEquals(PaymentStatus::PAID, $manualPayment->status);
        $this->assertEquals(BookingStatus::DIBAYAR, $this->booking->status);
        $this->assertEquals($this->adminUser->id, $manualPayment->verified_by);
    }
}
