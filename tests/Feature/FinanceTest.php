<?php

namespace Tests\Feature;

use App\Domain\Finance\Services\PayoutService;
use App\Domain\Finance\Services\ReconciliationService;
use App\Domain\Finance\Services\RefundService;
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
use Tests\TestCase;

class FinanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $buyer;

    protected User $partnerUser;

    protected User $adminUser;

    protected Booking $booking;

    protected Payment $payment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->buyer = User::factory()->create([
            'account_type' => AccountType::BUYER,
            'status' => 'active',
        ]);

        Institution::create([
            'user_id' => $this->buyer->id,
            'institution_name' => 'SMA Kolese De Britto',
            'pic_name' => 'Bruder Johanes',
            'pic_phone' => '6281234567891',
            'pic_email' => 'johanes@debritto.sch.id',
        ]);

        $this->partnerUser = User::factory()->create([
            'account_type' => AccountType::PARTNER,
            'status' => 'active',
        ]);

        PartnerProfile::create([
            'user_id' => $this->partnerUser->id,
            'organization_name' => 'Koperasi Nglanggeran Sejahtera',
            'business_type' => 'koperasi',
            'pic_name' => 'Mugi Raharjo',
            'pic_phone' => '6281234567892',
            'pic_email' => 'mugi@nglanggeran.test',
            'address' => 'Jl. Nglanggeran Wetan',
            'city' => 'Gunungkidul',
            'province' => 'DI Yogyakarta',
            'bank_id' => Bank::first()?->id,
            'bank_account_number' => '1370012345678',
            'bank_account_holder' => 'Koperasi Nglanggeran',
            'verification_status' => VerificationStatus::VERIFIED,
        ]);

        $this->adminUser = User::factory()->create([
            'account_type' => AccountType::INTERNAL,
            'status' => 'active',
        ]);

        $province = Province::first();
        $policy = CancellationPolicy::first();
        $category = DestinationCategory::firstOrCreate(['slug' => 'geowisata'], ['name' => 'Geowisata', 'is_active' => true]);

        $destination = Destination::create([
            'partner_id' => $this->partnerUser->id,
            'category_id' => $category->id,
            'province_id' => $province->id,
            'cancellation_policy_id' => $policy->id,
            'name' => 'Gunung Api Purba Nglanggeran',
            'slug' => 'gunung-api-purba-nglanggeran',
            'city' => 'Gunungkidul',
            'address' => 'Nglanggeran Kulon',
            'description' => 'Geowisata gunung api purba.',
            'status' => DestinationStatus::PUBLISHED,
        ]);

        $package = Package::create([
            'destination_id' => $destination->id,
            'name' => 'Geowisata Purba',
            'slug' => 'geowisata-purba',
            'inquiry_types' => ['study_tour'],
            'duration_days' => 1,
            'price_per_pax' => 60000,
            'min_pax' => 10,
            'max_pax' => 50,
            'is_active' => true,
        ]);

        $this->booking = Booking::create([
            'booking_code' => 'DST-2610-TEST1',
            'user_id' => $this->buyer->id,
            'institution_id' => $this->buyer->institution->id,
            'destination_id' => $destination->id,
            'package_id' => $package->id,
            'inquiry_type' => 'study_tour',
            'planned_date_start' => Carbon::now()->addDays(5)->toDateString(),
            'planned_date_end' => Carbon::now()->addDays(5)->toDateString(),
            'participant_count' => 20,
            'subtotal_amount' => 1200000,
            'addons_amount' => 0,
            'insurance_amount' => 40000,
            'platform_fee_amount' => 25000,
            'discount_amount' => 0,
            'total_amount' => 1265000,
            'commission_percent' => 10,
            'commission_amount' => 120000,
            'partner_net_amount' => 1080000,
            'status' => BookingStatus::DIBAYAR,
            'paid_at' => now(),
        ]);

        $this->payment = Payment::create([
            'booking_id' => $this->booking->id,
            'gateway' => 'xendit',
            'external_id' => 'DST-2610-TEST1-P1',
            'amount' => 1265000,
            'status' => PaymentStatus::PAID,
            'paid_at' => now(),
        ]);
    }

    public function test_admin_process_and_reject_refund(): void
    {
        Storage::fake('local');

        $refund = Refund::create([
            'booking_id' => $this->booking->id,
            'payment_id' => $this->payment->id,
            'amount' => 500000,
            'percent' => 50,
            'reason' => 'Pembatalan rombongan sebagian',
            'status' => RefundStatus::PENDING,
            'buyer_account_number' => '1234567890',
            'buyer_account_holder' => 'Bruder Johanes',
        ]);

        $refundService = app(RefundService::class);
        $file = UploadedFile::fake()->create('bukti_refund.pdf', 300, 'application/pdf');

        $processed = $refundService->process($refund, $this->adminUser, $file, 'Transfer berhasil via KlikBCA');

        $this->assertEquals(RefundStatus::COMPLETED, $processed->status);
        $this->assertEquals($this->adminUser->id, $processed->processed_by);
        $this->assertNotNull($processed->proof_path);
        Storage::disk('local')->assertExists($processed->proof_path);
    }

    public function test_payout_generation_and_transfer_processing(): void
    {
        Storage::fake('local');

        // Mark booking as completed 4 days ago (delay is 3 days)
        $this->booking->status = BookingStatus::SELESAI;
        $this->booking->completed_at = now()->subDays(4);
        $this->booking->save();

        $payoutService = app(PayoutService::class);
        $count = $payoutService->generateEligiblePayouts();

        $this->assertEquals(1, $count);
        $payout = PartnerPayout::where('booking_id', $this->booking->id)->first();
        $this->assertNotNull($payout);
        $this->assertEquals(PayoutStatus::PENDING, $payout->status);
        $this->assertEquals(1080000, $payout->net_amount);

        // Partner cannot modify payout (Policy test)
        $this->assertFalse($this->partnerUser->can('update', $payout));

        // Admin transfers payout
        $file = UploadedFile::fake()->create('bukti_payout.jpg', 400, 'image/jpeg');
        $transferred = $payoutService->processTransfer($payout, $this->adminUser, $file);

        $this->assertEquals(PayoutStatus::TRANSFERRED, $transferred->status);
        $this->assertNotNull($transferred->transfer_proof_path);
        $this->assertEquals($this->adminUser->id, $transferred->processed_by);
    }

    public function test_reconciliation_calculation_from_real_records(): void
    {
        $period = now()->format('Y-m');
        $reconService = app(ReconciliationService::class);

        $recon = $reconService->calculatePeriod($period);

        $this->assertEquals(1265000, $recon->total_paid_in);
        $this->assertEquals(120000, $recon->total_commission);
        $this->assertEquals(25000, $recon->total_platform_fee);
        $this->assertEquals(40000, $recon->total_insurance);
        $this->assertEquals(120000 + 25000, $recon->net_platform_revenue);

        // Close book
        $closed = $reconService->closeBook($period, $this->adminUser, 'Tutup buku sukses.');
        $this->assertEquals('closed', $closed->status);
        $this->assertEquals($this->adminUser->id, $closed->closed_by);

        // Export CSV
        $csv = $reconService->exportCsv($period);
        $this->assertStringContainsString('REKONSILIASI KEUANGAN DESTINARA', $csv);
        $this->assertStringContainsString('1265000', $csv);
    }
}
