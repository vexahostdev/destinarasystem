<?php

namespace Tests\Unit;

use App\Domain\Booking\Services\PricingService;
use App\Models\CancellationPolicy;
use App\Models\Destination;
use App\Models\DestinationCategory;
use App\Models\Package;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_package_item_name_has_no_duplicate_paket_prefix_c1(): void
    {
        $partner = User::factory()->create();
        $category = DestinationCategory::first() ?? DestinationCategory::factory()->create();
        $policy = CancellationPolicy::first() ?? CancellationPolicy::factory()->create();
        $province = Province::first() ?? Province::factory()->create();

        $destination = Destination::create([
            'partner_id' => $partner->id,
            'category_id' => $category->id,
            'province_id' => $province->id,
            'cancellation_policy_id' => $policy->id,
            'name' => 'Desa Wisata Pentingsari',
            'slug' => 'desa-wisata-pentingsari',
            'city' => 'Sleman',
            'address' => 'Umbulharjo, Cangkringan',
            'description' => 'Desa wisata budaya dan alam.',
            'default_daily_capacity' => 100,
        ]);

        $package = Package::create([
            'destination_id' => $destination->id,
            'name' => 'Paket Study Tour Konservasi',
            'slug' => 'paket-study-tour-konservasi',
            'inquiry_types' => ['study_tour'],
            'duration_days' => 2,
            'price_per_pax' => 75000,
            'min_pax' => 10,
            'max_pax' => 100,
            'is_active' => true,
        ]);

        $service = new PricingService();
        $result = $service->calculate($package, 20);

        $packageItem = collect($result->items)->firstWhere('type', 'package');
        $this->assertNotNull($packageItem);

        // Exact assertion: single Paket prefix ("Paket Study Tour Konservasi (2 Hari)"), NOT "Paket Paket ..."
        $this->assertEquals('Paket Study Tour Konservasi (2 Hari)', $packageItem['name']);
        $this->assertStringNotContainsString('Paket Paket', $packageItem['name']);
    }
}
