<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\BookingStatus;
use App\Enums\DestinationStatus;
use App\Models\Booking;
use App\Models\CancellationPolicy;
use App\Models\Destination;
use App\Models\DestinationCategory;
use App\Models\Institution;
use App\Models\Package;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $partner;
    protected DestinationCategory $category;
    protected Province $province;
    protected CancellationPolicy $policy;
    protected Destination $publishedDestination;
    protected Destination $draftDestination;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->partner = User::factory()->create([
            'account_type' => AccountType::PARTNER,
            'status' => 'active',
        ]);

        $this->category = DestinationCategory::first() ?? DestinationCategory::create(['name' => 'Alam', 'slug' => 'alam']);
        $this->province = Province::first() ?? Province::create(['name' => 'DI Yogyakarta', 'slug' => 'diy']);
        $this->policy = CancellationPolicy::first() ?? CancellationPolicy::create([
            'name' => 'Standar',
            'days_before' => 7,
            'refund_percentage' => 100,
        ]);

        $this->publishedDestination = Destination::create([
            'partner_id' => $this->partner->id,
            'category_id' => $this->category->id,
            'province_id' => $this->province->id,
            'cancellation_policy_id' => $this->policy->id,
            'name' => 'Desa Agrowisata Lembah Hijau',
            'slug' => 'desa-agrowisata-lembah-hijau',
            'status' => DestinationStatus::PUBLISHED,
            'city' => 'Bantul',
            'address' => 'Jl. Imogiri Timur',
            'short_description' => 'Ekskursi pertanian organik dan konservasi bambu.',
            'description' => 'Deskripsi lengkap desa agrowisata untuk studi lapangan.',
            'educational_highlights' => ['Konservasi Air', 'Pertanian Permakultur'],
            'rating_avg' => 4.8,
            'rating_count' => 12,
            'is_featured' => true,
            'published_at' => now(),
        ]);

        Package::create([
            'destination_id' => $this->publishedDestination->id,
            'name' => 'Paket Edukasi 1 Hari',
            'slug' => 'paket-edukasi-1-hari',
            'inquiry_types' => ['study_tour', 'field_trip'],
            'duration_days' => 1,
            'price_per_pax' => 75000,
            'min_pax' => 20,
            'max_pax' => 100,
            'includes' => ['Makan Siang', 'Pemandu Lokal', 'Bibit Tanaman'],
            'is_active' => true,
        ]);

        $this->draftDestination = Destination::create([
            'partner_id' => $this->partner->id,
            'category_id' => $this->category->id,
            'province_id' => $this->province->id,
            'cancellation_policy_id' => $this->policy->id,
            'name' => 'Desa Wisata Konsep Tertutup',
            'slug' => 'desa-wisata-konsep-tertutup',
            'status' => DestinationStatus::DRAFT,
            'city' => 'Sleman',
            'address' => 'Jl. Kaliurang',
        ]);
    }

    public function test_destinations_endpoint_returns_only_published_records(): void
    {
        $response = $this->getJson('/api/v1/public/destinations');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'slug',
                        'name',
                        'category' => ['slug', 'name'],
                        'province',
                        'city',
                        'short_description',
                        'cover_url',
                        'price_from',
                        'rating_avg',
                        'rating_count',
                        'is_featured',
                        'detail_url',
                        'booking_url',
                    ],
                ],
                'meta',
            ]);

        $slugs = collect($response->json('data'))->pluck('slug')->all();
        $this->assertContains('desa-agrowisata-lembah-hijau', $slugs);
        $this->assertNotContains('desa-wisata-konsep-tertutup', $slugs);
    }

    public function test_destination_detail_endpoint(): void
    {
        $response = $this->getJson('/api/v1/public/destinations/desa-agrowisata-lembah-hijau');

        $response->assertOk()
            ->assertJsonPath('data.slug', 'desa-agrowisata-lembah-hijau')
            ->assertJsonPath('data.name', 'Desa Agrowisata Lembah Hijau')
            ->assertJsonPath('data.packages.0.name', 'Paket Edukasi 1 Hari')
            ->assertJsonPath('data.packages.0.price_per_pax', 75000);

        // Draft destination returns 404
        $this->getJson('/api/v1/public/destinations/desa-wisata-konsep-tertutup')
            ->assertNotFound();
    }

    public function test_categories_endpoint(): void
    {
        $response = $this->getJson('/api/v1/public/categories');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'destinations_count',
                    ],
                ],
            ]);
    }

    public function test_stats_endpoint_returns_real_metrics(): void
    {
        $buyer = User::factory()->create([
            'account_type' => AccountType::BUYER,
            'status' => 'active',
        ]);

        $inst = Institution::create([
            'user_id' => $buyer->id,
            'institution_name' => 'SMP Negeri 2 Yogyakarta',
            'pic_name' => 'Bu Guru',
            'pic_phone' => '6281234567891',
            'pic_email' => 'guru@smp2jogja.test',
            'address' => 'Jl. Solo',
            'city' => 'Yogyakarta',
            'province' => 'DI Yogyakarta',
        ]);

        $package = $this->publishedDestination->packages()->first();

        Booking::create([
            'booking_code' => 'DST-TST-001',
            'user_id' => $buyer->id,
            'institution_id' => $inst->id,
            'destination_id' => $this->publishedDestination->id,
            'package_id' => $package->id,
            'status' => BookingStatus::SELESAI,
            'inquiry_type' => 'study_tour',
            'planned_date_start' => now()->subDays(5)->toDateString(),
            'planned_date_end' => now()->subDays(5)->toDateString(),
            'participant_count' => 40,
            'companion_count' => 5,
            'subtotal_amount' => 3375000,
            'discount_amount' => 0,
            'insurance_amount' => 0,
            'total_amount' => 3375000,
            'platform_fee_amount' => 0,
            'commission_percent' => 10,
            'commission_amount' => 337500,
            'partner_net_amount' => 3037500,
        ]);

        $response = $this->getJson('/api/v1/public/stats');

        $response->assertOk()
            ->assertJsonPath('data.published_destinations', 1)
            ->assertJsonPath('data.total_pax_facilitated', 45)
            ->assertJsonPath('data.total_institutions', 1);
    }
}
