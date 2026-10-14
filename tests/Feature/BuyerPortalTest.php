<?php

namespace Tests\Feature;

use App\Domain\Booking\Services\BookingCodeGenerator;
use App\Enums\AccountType;
use App\Enums\BookingStatus;
use App\Enums\DestinationStatus;
use App\Enums\VerificationStatus;
use App\Livewire\Buyer\BookingForm;
use App\Models\CancellationPolicy;
use App\Models\Destination;
use App\Models\DestinationCategory;
use App\Models\Institution;
use App\Models\Package;
use App\Models\PartnerProfile;
use App\Models\Province;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BuyerPortalTest extends TestCase
{
    use RefreshDatabase;

    protected User $buyer;
    protected User $partner;
    protected Destination $destination;
    protected Package $package;

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
            'institution_name' => 'SMA 1 Bantul',
            'pic_name' => 'Pak Joko',
            'pic_phone' => '6281234567891',
            'pic_email' => 'joko@sma1bantul.test',
            'address' => 'Jl. Bantul No. 1',
            'city' => 'Bantul',
            'province' => 'DI Yogyakarta',
        ]);

        $this->partner = User::factory()->create([
            'account_type' => AccountType::PARTNER,
            'status' => 'active',
        ]);

        PartnerProfile::create([
            'user_id' => $this->partner->id,
            'organization_name' => 'Pokdarwis Kaki Langit',
            'business_type' => 'pokdarwis',
            'pic_name' => 'Lurah Mangunan',
            'pic_phone' => '6281234567892',
            'pic_email' => 'lurah@mangunan.test',
            'address' => 'Mangunan',
            'city' => 'Bantul',
            'province' => 'DI Yogyakarta',
            'verification_status' => VerificationStatus::VERIFIED,
        ]);

        $province = Province::first();
        $policy = CancellationPolicy::first();
        $category = DestinationCategory::first();

        $this->destination = Destination::create([
            'partner_id' => $this->partner->id,
            'category_id' => $category->id,
            'province_id' => $province->id,
            'cancellation_policy_id' => $policy->id,
            'name' => 'Kaki Langit Mangunan',
            'slug' => 'kaki-langit-mangunan',
            'city' => 'Bantul',
            'address' => 'Mangunan, Dlingo',
            'default_daily_capacity' => 100,
            'status' => DestinationStatus::PUBLISHED,
        ]);

        $this->package = Package::create([
            'destination_id' => $this->destination->id,
            'name' => 'Paket Budaya Edukasi',
            'slug' => 'paket-budaya-edukasi',
            'inquiry_types' => ['study_tour'],
            'duration_days' => 1,
            'price_per_pax' => 60000,
            'min_pax' => 10,
            'max_pax' => 100,
            'is_active' => true,
        ]);
    }

    public function test_buyer_dashboard_and_create_page_render(): void
    {
        $this->actingAs($this->buyer);

        $this->get('/buyer/dashboard')->assertStatus(200);
        $this->get('/buyer/booking/create?tapak=' . $this->destination->slug)->assertStatus(200);
    }

    public function test_livewire_booking_form_calculates_and_submits(): void
    {
        $this->actingAs($this->buyer);

        $futureDate = now()->addDays(15)->format('Y-m-d');

        Livewire::test(BookingForm::class, ['destination' => $this->destination])
            ->set('packageId', $this->package->id)
            ->set('participantCount', 20)
            ->set('plannedDateStart', $futureDate)
            ->call('recalculate')
            ->assertSet('isSlotAvailable', true)
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('bookings', [
            'user_id' => $this->buyer->id,
            'destination_id' => $this->destination->id,
            'participant_count' => 20,
            'status' => BookingStatus::MENUNGGU_PEMBAYARAN,
        ]);
    }

    public function test_buyer_can_toggle_wishlist(): void
    {
        $this->actingAs($this->buyer);

        $this->post('/buyer/wishlist/toggle', [
            'destination_id' => $this->destination->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('wishlists', [
            'user_id' => $this->buyer->id,
            'destination_id' => $this->destination->id,
        ]);

        // Toggle again to remove
        $this->post('/buyer/wishlist/toggle', [
            'destination_id' => $this->destination->id,
        ])->assertRedirect();

        $this->assertDatabaseMissing('wishlists', [
            'user_id' => $this->buyer->id,
            'destination_id' => $this->destination->id,
        ]);
    }

    public function test_buyer_cannot_access_other_buyers_booking_dossier(): void
    {
        $otherBuyer = User::factory()->create(['account_type' => AccountType::BUYER]);
        $booking = \App\Models\Booking::create([
            'booking_code' => app(BookingCodeGenerator::class)->generate(),
            'user_id' => $otherBuyer->id,
            'institution_id' => $this->buyer->institution->id,
            'destination_id' => $this->destination->id,
            'package_id' => $this->package->id,
            'inquiry_type' => 'study_tour',
            'planned_date_start' => now()->addDays(10)->toDateString(),
            'planned_date_end' => now()->addDays(10)->toDateString(),
            'participant_count' => 15,
            'status' => BookingStatus::MENUNGGU_PEMBAYARAN,
            'subtotal_amount' => 900000,
            'addons_amount' => 0,
            'insurance_amount' => 30000,
            'platform_fee_amount' => 10000,
            'discount_amount' => 0,
            'total_amount' => 940000,
            'commission_percent' => 10,
            'commission_amount' => 90000,
            'partner_net_amount' => 810000,
            'expires_at' => now()->addHours(24),
        ]);

        $this->actingAs($this->buyer);
        $this->get('/buyer/booking/' . $booking->booking_code)->assertStatus(404);
    }
}
