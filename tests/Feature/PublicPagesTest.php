<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\DestinationStatus;
use App\Models\CancellationPolicy;
use App\Models\Destination;
use App\Models\DestinationCategory;
use App\Models\Package;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_render_successfully(): void
    {
        $this->seed();

        $this->get('/')->assertStatus(200);
        $this->get('/destinasi')->assertStatus(200);
        $this->get('/cara-kerja')->assertStatus(200);
        $this->get('/faq')->assertStatus(200);
        $this->get('/syarat-ketentuan')->assertStatus(200);
    }

    public function test_destination_detail_page_renders_with_real_data(): void
    {
        $this->seed();

        $partner = User::factory()->create(['account_type' => AccountType::PARTNER]);
        $province = Province::first();
        $policy = CancellationPolicy::first();
        $category = DestinationCategory::first();

        $destination = Destination::create([
            'partner_id' => $partner->id,
            'category_id' => $category->id,
            'province_id' => $province->id,
            'cancellation_policy_id' => $policy->id,
            'name' => 'Wana Wisata Kalibiru',
            'slug' => 'wana-wisata-kalibiru',
            'city' => 'Kulon Progo',
            'address' => 'Hargowilis, Kokap',
            'description' => 'Laboratorium alam untuk studi botani dan vegetasi hutan.',
            'status' => DestinationStatus::PUBLISHED,
        ]);

        Package::create([
            'destination_id' => $destination->id,
            'name' => 'Paket Botani',
            'slug' => 'paket-botani',
            'inquiry_types' => ['study_tour'],
            'duration_days' => 1,
            'price_per_pax' => 45000,
            'min_pax' => 10,
            'max_pax' => 50,
            'is_active' => true,
        ]);

        $response = $this->get('/destinasi/'.$destination->slug);
        $response->assertStatus(200);
        $response->assertSee('Wana Wisata Kalibiru');
        $response->assertSee('Kulon Progo');
        $response->assertSee('Rp 45.000');
    }
}
