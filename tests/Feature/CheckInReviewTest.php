<?php

namespace Tests\Feature;

use App\Domain\Booking\Services\CheckInService;
use App\Domain\Catalog\Services\ReviewService;
use App\Enums\AccountType;
use App\Enums\BookingStatus;
use App\Enums\DestinationStatus;
use App\Enums\VerificationStatus;
use App\Models\Booking;
use App\Models\CancellationPolicy;
use App\Models\Destination;
use App\Models\Institution;
use App\Models\Package;
use App\Models\PartnerProfile;
use App\Models\Province;
use App\Models\Review;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use InvalidArgumentException;
use Tests\TestCase;

class CheckInReviewTest extends TestCase
{
    use RefreshDatabase;

    protected User $buyer;

    protected User $partnerUser;

    protected Destination $destination;

    protected Booking $booking;

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
            'institution_name' => 'SMP Negeri 1 Bantul',
            'pic_name' => 'Pak Joko',
            'pic_phone' => '6281234567895',
            'pic_email' => 'joko@smp1bantul.test',
        ]);

        $this->partnerUser = User::factory()->create([
            'account_type' => AccountType::PARTNER,
            'status' => 'active',
        ]);

        PartnerProfile::create([
            'user_id' => $this->partnerUser->id,
            'organization_name' => 'Pokdarwis Kaki Langit',
            'business_type' => 'pokdarwis',
            'pic_name' => 'Pak Lurah Mangunan',
            'pic_phone' => '6281234567894',
            'pic_email' => 'lurah@mangunan.test',
            'address' => 'Mangunan, Dlingo',
            'city' => 'Bantul',
            'province' => 'DI Yogyakarta',
            'verification_status' => VerificationStatus::VERIFIED,
        ]);

        $province = Province::first();
        $policy = CancellationPolicy::first();

        $this->destination = Destination::create([
            'partner_id' => $this->partnerUser->id,
            'category_id' => 1,
            'province_id' => $province->id,
            'cancellation_policy_id' => $policy->id,
            'name' => 'Desa Wisata Kaki Langit',
            'slug' => 'desa-wisata-kaki-langit',
            'city' => 'Bantul',
            'address' => 'Mangunan',
            'description' => 'Desa wisata ramah edukasi.',
            'status' => DestinationStatus::PUBLISHED,
        ]);

        $package = Package::create([
            'destination_id' => $this->destination->id,
            'name' => 'Paket Edukasi Budaya',
            'slug' => 'paket-edukasi-budaya',
            'inquiry_types' => ['study_tour'],
            'duration_days' => 1,
            'price_per_pax' => 50000,
            'min_pax' => 10,
            'max_pax' => 50,
            'is_active' => true,
        ]);

        // Booking on today's date so check-in is valid
        $this->booking = Booking::create([
            'booking_code' => 'DST-2610-CHK1',
            'user_id' => $this->buyer->id,
            'institution_id' => $this->buyer->institution->id,
            'destination_id' => $this->destination->id,
            'package_id' => $package->id,
            'inquiry_type' => 'study_tour',
            'planned_date_start' => Carbon::today()->toDateString(),
            'planned_date_end' => Carbon::today()->toDateString(),
            'participant_count' => 25,
            'subtotal_amount' => 1250000,
            'addons_amount' => 0,
            'insurance_amount' => 50000,
            'platform_fee_amount' => 25000,
            'discount_amount' => 0,
            'total_amount' => 1325000,
            'commission_percent' => 10,
            'commission_amount' => 125000,
            'partner_net_amount' => 1125000,
            'status' => BookingStatus::DIKONFIRMASI,
            'confirmed_at' => now(),
        ]);
    }

    public function test_signed_checkin_url_processes_checkin_and_transitions_booking(): void
    {
        $signedUrl = URL::signedRoute('partner.checkin.show', ['code' => $this->booking->booking_code]);

        // 1. Visit signed URL
        $this->actingAs($this->partnerUser);
        $response = $this->get($signedUrl);
        $response->assertStatus(200);

        // 2. Process check-in
        $checkInService = app(CheckInService::class);
        $checkIn = $checkInService->processCheckIn($this->booking, $this->partnerUser, 25, 'Semua peserta hadir tepat waktu.');

        $this->booking->refresh();
        $this->assertEquals(BookingStatus::BERLANGSUNG, $this->booking->status);
        $this->assertNotNull($this->booking->checked_in_at);
        $this->assertDatabaseHas('check_ins', [
            'booking_id' => $this->booking->id,
            'actual_participant_count' => 25,
            'scanned_by' => $this->partnerUser->id,
        ]);
    }

    public function test_auto_complete_marks_ongoing_booking_as_completed(): void
    {
        $this->booking->status = BookingStatus::BERLANGSUNG;
        $this->booking->planned_date_end = now()->subDays(2)->toDateString();
        $this->booking->save();

        $checkInService = app(CheckInService::class);
        $count = $checkInService->autoCompleteFinishedBookings();

        $this->assertEquals(1, $count);
        $this->booking->refresh();
        $this->assertEquals(BookingStatus::SELESAI, $this->booking->status);
        $this->assertNotNull($this->booking->completed_at);

        // Payout automatically created
        $this->assertDatabaseHas('partner_payouts', [
            'booking_id' => $this->booking->id,
            'partner_id' => $this->partnerUser->id,
        ]);
    }

    public function test_buyer_review_submission_and_aggregate_rating_calculation(): void
    {
        $this->booking->status = BookingStatus::SELESAI;
        $this->booking->completed_at = now()->subDay();
        $this->booking->save();

        $reviewService = app(ReviewService::class);

        // 1. Buyer submits review (rating 5)
        $review = $reviewService->createReview(
            booking: $this->booking,
            buyer: $this->buyer,
            rating: 5,
            comment: 'Pengalaman edukasi luar biasa bagi siswa kami.'
        );

        $this->assertEquals(5, $review->rating);
        $this->destination->refresh();
        $this->assertEquals(5.00, (float) $this->destination->rating_avg);
        $this->assertEquals(1, $this->destination->rating_count);

        // 2. Second review on same booking is rejected
        $this->expectException(InvalidArgumentException::class);
        $reviewService->createReview(
            booking: $this->booking,
            buyer: $this->buyer,
            rating: 4
        );
    }

    public function test_partner_can_reply_to_review_once(): void
    {
        $this->booking->status = BookingStatus::SELESAI;
        $this->booking->completed_at = now()->subDay();
        $this->booking->save();

        $reviewService = app(ReviewService::class);
        $review = $reviewService->createReview(
            booking: $this->booking,
            buyer: $this->buyer,
            rating: 5,
            comment: 'Sangat ramah anak!'
        );

        // Partner replies
        $reviewService->reply($review, $this->partnerUser, 'Terima kasih atas kunjungannya, kami tunggu kedatangan berikutnya!');

        $review->refresh();
        $this->assertNotNull($review->partner_reply);
        $this->assertNotNull($review->replied_at);

        // Second reply is blocked
        $this->expectException(InvalidArgumentException::class);
        $reviewService->reply($review, $this->partnerUser, 'Balasan kedua tidak boleh.');
    }
}
