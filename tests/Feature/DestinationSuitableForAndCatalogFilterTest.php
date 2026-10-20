<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\DestinationStatus;
use App\Livewire\Public\DestinationCatalog;
use App\Models\CancellationPolicy;
use App\Models\Destination;
use App\Models\DestinationCategory;
use App\Models\Package;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DestinationSuitableForAndCatalogFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $partner;
    protected DestinationCategory $category;
    protected Province $province;
    protected CancellationPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->partner = User::factory()->create([
            'account_type' => AccountType::PARTNER,
            'status' => 'active',
        ]);

        $this->category = DestinationCategory::first() ?? DestinationCategory::factory()->create();
        $this->province = Province::first() ?? Province::factory()->create();
        $this->policy = CancellationPolicy::first() ?? CancellationPolicy::factory()->create();
    }

    public function test_suitable_for_is_automatically_synced_from_active_packages_b8(): void
    {
        $destination = Destination::create([
            'partner_id' => $this->partner->id,
            'category_id' => $this->category->id,
            'province_id' => $this->province->id,
            'cancellation_policy_id' => $this->policy->id,
            'name' => 'Stasiun Riset Konservasi Merapi',
            'slug' => 'stasiun-riset-merapi',
            'city' => 'Sleman',
            'address' => 'Kaliurang, Sleman',
            'description' => 'Stasiun riset geologi dan vulkanologi komunal.',
            'default_daily_capacity' => 50,
            'status' => DestinationStatus::PUBLISHED,
            'suitable_for' => ['study_tour'],
        ]);

        // 1. Initial suitable_for
        $this->assertEquals(['study_tour'], $destination->suitable_for);

        // 2. Add an active package with inquiry_types = ['penelitian']
        $packageRiset = Package::create([
            'destination_id' => $destination->id,
            'name' => 'Paket Riset Vulkanologi 3 Hari',
            'slug' => 'riset-vulkanologi',
            'inquiry_types' => ['penelitian'],
            'duration_days' => 3,
            'price_per_pax' => 150000,
            'min_pax' => 5,
            'max_pax' => 30,
            'is_active' => true,
        ]);

        $destination->refresh();
        $this->assertContains('penelitian', $destination->suitable_for);

        // 3. Add another active package with inquiry_types = ['study_tour']
        $packageTour = Package::create([
            'destination_id' => $destination->id,
            'name' => 'Paket Ekskursi Siswa',
            'slug' => 'ekskursi-siswa',
            'inquiry_types' => ['study_tour'],
            'duration_days' => 1,
            'price_per_pax' => 50000,
            'min_pax' => 20,
            'max_pax' => 100,
            'is_active' => true,
        ]);

        $destination->refresh();
        $this->assertContains('penelitian', $destination->suitable_for);
        $this->assertContains('study_tour', $destination->suitable_for);

        // 4. Deactivate research package -> suitable_for should only contain study_tour
        $packageRiset->update(['is_active' => false]);
        $destination->refresh();
        $this->assertEquals(['study_tour'], $destination->suitable_for);

        // 5. Reactivate research package -> suitable_for contains both again
        $packageRiset->update(['is_active' => true]);
        $destination->refresh();
        $this->assertContains('penelitian', $destination->suitable_for);
        $this->assertContains('study_tour', $destination->suitable_for);
    }

    public function test_catalog_and_api_filter_by_inquiry_type_b8(): void
    {
        // Destination A: only study_tour
        $destTourOnly = Destination::create([
            'partner_id' => $this->partner->id,
            'category_id' => $this->category->id,
            'province_id' => $this->province->id,
            'cancellation_policy_id' => $this->policy->id,
            'name' => 'Taman Agrowisata Ceria',
            'slug' => 'taman-agrowisata-ceria',
            'city' => 'Bantul',
            'address' => 'Jl. Agro No. 5',
            'description' => 'Tempat wisata edukasi ramah anak.',
            'default_daily_capacity' => 100,
            'status' => DestinationStatus::PUBLISHED,
        ]);

        Package::create([
            'destination_id' => $destTourOnly->id,
            'name' => 'Paket Study Tour Pertanian',
            'slug' => 'paket-study-tour-pertanian',
            'inquiry_types' => ['study_tour'],
            'duration_days' => 1,
            'price_per_pax' => 45000,
            'min_pax' => 20,
            'max_pax' => 100,
            'is_active' => true,
        ]);

        // Destination B: with penelitian
        $destResearch = Destination::create([
            'partner_id' => $this->partner->id,
            'category_id' => $this->category->id,
            'province_id' => $this->province->id,
            'cancellation_policy_id' => $this->policy->id,
            'name' => 'Laboratorium Biologi Laut Karang',
            'slug' => 'lab-biologi-laut-karang',
            'city' => 'Gunungkidul',
            'address' => 'Pantai Sundak',
            'description' => 'Fasilitas penelitian ekologi laut tropis.',
            'default_daily_capacity' => 40,
            'status' => DestinationStatus::PUBLISHED,
        ]);

        Package::create([
            'destination_id' => $destResearch->id,
            'name' => 'Paket Riset Ekologi Pesisir',
            'slug' => 'paket-riset-ekologi',
            'inquiry_types' => ['penelitian'],
            'duration_days' => 3,
            'price_per_pax' => 180000,
            'min_pax' => 5,
            'max_pax' => 25,
            'is_active' => true,
        ]);

        // 1. Livewire Catalog filter by 'penelitian'
        Livewire::test(DestinationCatalog::class)
            ->set('inquiry_type', 'penelitian')
            ->assertSee('Laboratorium Biologi Laut Karang')
            ->assertDontSee('Taman Agrowisata Ceria');

        // 2. Livewire Catalog filter by 'study_tour'
        Livewire::test(DestinationCatalog::class)
            ->set('inquiry_type', 'study_tour')
            ->assertSee('Taman Agrowisata Ceria')
            ->assertDontSee('Laboratorium Biologi Laut Karang');

        // 3. API filter by inquiry_type=penelitian
        $apiRes = $this->getJson(route('api.public.destinations', ['inquiry_type' => 'penelitian']));
        $apiRes->assertOk();
        $apiRes->assertJsonFragment(['slug' => 'lab-biologi-laut-karang']);
        $apiRes->assertJsonMissing(['slug' => 'taman-agrowisata-ceria']);
    }
}
