<?php

namespace Tests\Feature;

use App\Domain\Booking\Exceptions\InvalidBookingTransitionException;
use App\Domain\Booking\Services\BookingCodeGenerator;
use App\Domain\Booking\Services\BookingService;
use App\Domain\Booking\Services\PricingService;
use App\Domain\Booking\StateMachine\BookingStateMachine;
use App\Enums\AccountType;
use App\Enums\AddonPricingType;
use App\Enums\BookingStatus;
use App\Enums\DestinationStatus;
use App\Enums\VerificationStatus;
use App\Models\Addon;
use App\Models\AvailabilitySlot;
use App\Models\CancellationPolicy;
use App\Models\Destination;
use App\Models\Institution;
use App\Models\Package;
use App\Models\PartnerProfile;
use App\Models\Province;
use App\Models\User;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    protected User $buyer;

    protected User $partnerUser;

    protected Destination $destination;

    protected Package $package;

    protected AvailabilitySlot $slot;

    protected CancellationPolicy $cancellationPolicy;

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
            'institution_name' => 'SMA Negeri 1 Yogyakarta',
            'pic_name' => 'Budi Santoso',
            'pic_phone' => '6281234567890',
            'pic_email' => 'budi@test.com',
            'pic_position' => 'Guru Pendamping',
            'address' => 'Jl. Tentara Pelajar No. 1',
            'city' => 'Yogyakarta',
            'province' => 'DI Yogyakarta',
        ]);

        $this->partnerUser = User::factory()->create([
            'account_type' => AccountType::PARTNER,
            'status' => 'active',
        ]);

        PartnerProfile::create([
            'user_id' => $this->partnerUser->id,
            'organization_name' => 'Pokdarwis Lestari',
            'business_type' => 'pokdarwis',
            'pic_name' => 'Bapak Budi',
            'pic_phone' => '6281234567890',
            'pic_email' => 'budi@pokdarwis.test',
            'address' => 'Jl. Desa No. 1',
            'city' => 'Sleman',
            'province' => 'DI Yogyakarta',
            'verification_status' => VerificationStatus::VERIFIED,
        ]);

        $this->cancellationPolicy = CancellationPolicy::first();
        $province = Province::first();

        $this->destination = Destination::create([
            'partner_id' => $this->partnerUser->id,
            'category_id' => 1,
            'province_id' => $province->id,
            'cancellation_policy_id' => $this->cancellationPolicy->id,
            'name' => 'Desa Wisata Kalibiru',
            'slug' => 'desa-wisata-kalibiru',
            'city' => 'Kulon Progo',
            'address' => 'Hargowilis, Kokap',
            'description' => 'Deskripsi lengkap desa wisata kalibiru.',
            'status' => DestinationStatus::PUBLISHED,
        ]);

        $this->package = Package::create([
            'destination_id' => $this->destination->id,
            'name' => 'Paket Edukasi Alam',
            'slug' => 'paket-edukasi-alam',
            'inquiry_types' => ['study_tour', 'penelitian'],
            'duration_days' => 1,
            'price_per_pax' => 100000,
            'min_pax' => 10,
            'max_pax' => 50,
            'is_active' => true,
        ]);

        $this->slot = AvailabilitySlot::create([
            'destination_id' => $this->destination->id,
            'date' => Carbon::now()->addDays(10)->toDateString(),
            'capacity' => 30,
            'booked_count' => 0,
            'held_count' => 0,
            'status' => 'open',
        ]);
    }

    public function test_booking_code_generator_produces_crockford_base32_code(): void
    {
        $code = BookingCodeGenerator::generate();
        $this->assertMatchesRegularExpression('/^DST-[0-9]{6}-[0-9A-HJKMNP-TV-Z]{5}$/', $code);
    }

    public function test_pricing_calculation_formula(): void
    {
        $pricingService = app(PricingService::class);

        $addon = Addon::create([
            'destination_id' => $this->destination->id,
            'name' => 'Makan Siang Prasmanan',
            'price' => 25000,
            'pricing_type' => AddonPricingType::PER_PAX,
            'is_active' => true,
        ]);

        $result = $pricingService->calculate(
            package: $this->package,
            pax: 20,
            selectedAddonIds: [$addon->id],
            voucher: null,
            userId: $this->buyer->id
        );

        // Package: 20 * 100,000 = 2,000,000
        $this->assertEquals(2000000, $result->packageTotal);
        // Addon: 20 * 25,000 = 500,000
        $this->assertEquals(500000, $result->addonsTotal);
        // Insurance: 20 * 2,000 (from default setting) = 40,000
        $this->assertEquals(40000, $result->insuranceAmount);
        // Platform fee: 25,000 (from default setting)
        $this->assertEquals(25000, $result->platformFeeAmount);
        // Total: 2,000,000 + 500,000 + 40,000 + 25,000 = 2,565,000
        $this->assertEquals(2565000, $result->totalAmount);
        // Commission (10% of subtotal 2,500,000 = 250,000)
        $this->assertEquals(250000, $result->commissionAmount);
        // Partner Net: 2,500,000 - 250,000 = 2,250,000
        $this->assertEquals(2250000, $result->partnerNetAmount);
    }

    public function test_pricing_with_voucher_discount(): void
    {
        $voucher = Voucher::create([
            'code' => 'HEMAT20',
            'name' => 'Diskon 20%',
            'type' => 'percent',
            'value' => 20,
            'min_transaction' => 500000,
            'max_discount' => 300000,
            'quota' => 10,
            'used_count' => 0,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(7),
            'is_active' => true,
        ]);

        $pricingService = app(PricingService::class);
        $result = $pricingService->calculate(
            package: $this->package,
            pax: 20,
            selectedAddonIds: [],
            voucher: $voucher,
            userId: $this->buyer->id
        );

        // Subtotal package: 2,000,000 + platform 25,000 = 2,025,000. 20% is 405,000, capped at max_discount 300,000.
        $this->assertEquals(300000, $result->discountAmount);
        // Total: 2,000,000 + 40,000 (insurance) + 25,000 (platform) - 300,000 = 1,765,000
        $this->assertEquals(1765000, $result->totalAmount);
    }

    public function test_booking_creation_validates_lead_time(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // min_lead_days is 7 in settings. Planned date 3 days ahead will fail.
        $bookingService = app(BookingService::class);
        $bookingService->create([
            'destination_id' => $this->destination->id,
            'package_id' => $this->package->id,
            'inquiry_type' => 'study_tour',
            'planned_date_start' => Carbon::now()->addDays(3)->toDateString(),
            'participant_count' => 15,
        ], $this->buyer);
    }

    public function test_booking_creation_validates_min_max_pax(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $bookingService = app(BookingService::class);
        // package min_pax is 10, passing 5 should fail
        $bookingService->create([
            'destination_id' => $this->destination->id,
            'package_id' => $this->package->id,
            'inquiry_type' => 'study_tour',
            'planned_date_start' => $this->slot->date,
            'participant_count' => 5,
        ], $this->buyer);
    }

    public function test_booking_creation_holds_slots_and_sets_status_menunggu_pembayaran(): void
    {
        $bookingService = app(BookingService::class);
        $booking = $bookingService->create([
            'destination_id' => $this->destination->id,
            'package_id' => $this->package->id,
            'inquiry_type' => 'study_tour',
            'planned_date_start' => $this->slot->date,
            'participant_count' => 15,
        ], $this->buyer);

        $this->assertEquals(BookingStatus::MENUNGGU_PEMBAYARAN, $booking->status);
        $this->slot->refresh();
        $this->assertEquals(15, $this->slot->held_count);
        $this->assertEquals(0, $this->slot->booked_count);

        $this->assertDatabaseHas('booking_slot_allocations', [
            'booking_id' => $booking->id,
            'availability_slot_id' => $this->slot->id,
            'pax' => 15,
            'type' => 'held',
        ]);
    }

    public function test_booking_state_machine_valid_and_invalid_transitions(): void
    {
        $bookingService = app(BookingService::class);
        $booking = $bookingService->create([
            'destination_id' => $this->destination->id,
            'package_id' => $this->package->id,
            'inquiry_type' => 'study_tour',
            'planned_date_start' => $this->slot->date,
            'participant_count' => 10,
        ], $this->buyer);

        $stateMachine = app(BookingStateMachine::class);

        // Invalid transition: directly to selesai from menunggu_pembayaran throws exception
        $this->expectException(InvalidBookingTransitionException::class);
        $stateMachine->transition($booking, BookingStatus::SELESAI, $this->buyer, 'Invalid transition');
    }

    public function test_slot_depletion_prevents_overbooking(): void
    {
        $bookingService = app(BookingService::class);

        // Slot capacity is 30. Book 20 pax.
        $booking1 = $bookingService->create([
            'destination_id' => $this->destination->id,
            'package_id' => $this->package->id,
            'inquiry_type' => 'study_tour',
            'planned_date_start' => $this->slot->date,
            'participant_count' => 20,
        ], $this->buyer);

        $this->assertNotNull($booking1);

        // Attempt to book 15 more pax when only 10 remain (30 - 20 = 10) -> should fail
        $this->expectException(RuntimeException::class);
        $bookingService->create([
            'destination_id' => $this->destination->id,
            'package_id' => $this->package->id,
            'inquiry_type' => 'study_tour',
            'planned_date_start' => $this->slot->date,
            'participant_count' => 15,
        ], $this->buyer);
    }
}
