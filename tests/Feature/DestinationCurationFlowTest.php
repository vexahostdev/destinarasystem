<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\DestinationStatus;
use App\Enums\VerificationStatus;
use App\Events\DestinationModerated;
use App\Events\DestinationSubmitted;
use App\Models\Addon;
use App\Models\CancellationPolicy;
use App\Models\Destination;
use App\Models\DestinationCategory;
use App\Models\Facility;
use App\Models\Package;
use App\Models\PartnerProfile;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DestinationCurationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $partner;

    protected DestinationCategory $category;

    protected Province $province;

    protected CancellationPolicy $policy;

    protected Facility $facility1;

    protected Facility $facility2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('local');
        Queue::fake();

        $this->admin = User::where('account_type', AccountType::INTERNAL)->first();
        if (! $this->admin) {
            $this->admin = User::factory()->create([
                'name' => 'Super Administrator',
                'email' => 'superadmin@destinara.test',
                'account_type' => AccountType::INTERNAL,
                'status' => 'active',
            ]);
            $this->admin->assignRole('super_admin');
        }

        $this->partner = User::factory()->create([
            'email' => 'mitra.curation@destinara.test',
            'account_type' => AccountType::PARTNER,
            'status' => 'active',
        ]);

        PartnerProfile::create([
            'user_id' => $this->partner->id,
            'organization_name' => 'Kelompok Tani Cikole',
            'pic_name' => 'Bapak Hendra',
            'pic_phone' => '081234567890',
            'address' => 'Desa Cikole',
            'city' => 'Lembang',
            'verification_status' => VerificationStatus::VERIFIED,
        ]);

        $this->category = DestinationCategory::first() ?? DestinationCategory::create([
            'name' => 'Konservasi Alam',
            'slug' => 'konservasi-alam',
            'is_active' => true,
        ]);

        $this->province = Province::first() ?? Province::create([
            'name' => 'Jawa Barat',
            'slug' => 'jawa-barat',
        ]);

        $this->policy = CancellationPolicy::first() ?? CancellationPolicy::create([
            'name' => 'Standar H-7',
            'refund_percent_before_h7' => 100,
            'refund_percent_after_h7' => 0,
        ]);

        $this->facility1 = Facility::first() ?? Facility::create([
            'name' => 'Area Parkir Bus Besar',
            'icon' => 'directions_bus',
            'is_active' => true,
        ]);

        $this->facility2 = Facility::skip(1)->first() ?? Facility::create([
            'name' => 'Pendopo Rombongan',
            'icon' => 'roofing',
            'is_active' => true,
        ]);
    }

    public function test_partner_can_create_destination_with_exact_form_payload_and_facilities(): void
    {
        $coverFile = UploadedFile::fake()->image('cover_tapak.jpg', 800, 600);

        $payload = [
            'name' => 'Kawasan Konservasi Hutan Cikole',
            'category_id' => $this->category->id,
            'city' => 'Lembang',
            'province_id' => $this->province->id,
            'cancellation_policy_id' => $this->policy->id,
            'cover_image' => $coverFile,
            'address' => 'Jl. Tangkuban Parahu No. 12, Cikole, Lembang',
            'description' => 'Kawasan hutan pinus dan konservasi bambu dengan laboratorium botani dan sejarah budaya Sunda.',
            'educational_highlights' => 'Identifikasi vegetasi endemik dan praktik pembuatan kompos organik bersama pemandu lokal.',
            'facilities' => [$this->facility1->id, $this->facility2->id],
            'default_daily_capacity' => 120,
        ];

        $response = $this->actingAs($this->partner)
            ->post(route('mitra.destinations.store'), $payload);

        $response->assertRedirect();

        $destination = Destination::where('name', 'Kawasan Konservasi Hutan Cikole')->first();
        $this->assertNotNull($destination);
        $this->assertEquals(DestinationStatus::DRAFT, $destination->status);
        $this->assertEquals($this->partner->id, $destination->partner_id);
        $this->assertEquals(120, $destination->default_daily_capacity);
        $this->assertNotEmpty($destination->cover_image);

        // Check synced facilities
        $this->assertCount(2, $destination->facilities);
        $this->assertTrue($destination->facilities->contains($this->facility1));
        $this->assertTrue($destination->facilities->contains($this->facility2));
    }

    public function test_partner_and_admin_can_save_package_with_min_max_pax_and_inquiry_types(): void
    {
        $destination = Destination::create([
            'partner_id' => $this->partner->id,
            'category_id' => $this->category->id,
            'province_id' => $this->province->id,
            'cancellation_policy_id' => $this->policy->id,
            'name' => 'Kebun Riset Biofarmaka',
            'slug' => 'kebun-riset-biofarmaka',
            'city' => 'Lembang',
            'address' => 'Jl. Cikole',
            'description' => 'Deskripsi kebun riset biofarmaka untuk studi tanaman obat keluarga.',
            'default_daily_capacity' => 100,
            'cover_image' => 'destinations/covers/fake.jpg',
            'status' => DestinationStatus::DRAFT,
        ]);

        $packagePayload = [
            'name' => 'Eksplorasi Tanaman Herbal 2 Hari',
            'price_per_pax' => 75000,
            'min_pax' => 15,
            'max_pax' => 90,
            'duration_days' => 2,
            'inquiry_types' => ['study_tour', 'penelitian'],
            'description' => 'Pembelajaran langsung klasifikasi simplisia dan peracikan teh herbal.',
            'included_items' => "Bibit herbal\nBuku panduan lapangan\nMakan siang 2x",
            'excluded_items' => "Penginapan\nTransportasi bus",
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->partner)
            ->post(route('mitra.destinations.packages.save', $destination->id), $packagePayload);

        $response->assertRedirect();

        $pkg = Package::where('destination_id', $destination->id)->first();
        $this->assertNotNull($pkg);
        $this->assertEquals('Eksplorasi Tanaman Herbal 2 Hari', $pkg->name);
        $this->assertEquals(75000, $pkg->price_per_pax);
        $this->assertEquals(15, $pkg->min_pax);
        $this->assertEquals(90, $pkg->max_pax);
        $this->assertEquals(2, $pkg->duration_days);
        $this->assertEquals(['study_tour', 'penelitian'], $pkg->inquiry_types);
        $this->assertEquals(['Bibit herbal', 'Buku panduan lapangan', 'Makan siang 2x'], $pkg->includes);
        $this->assertEquals(['Penginapan', 'Transportasi bus'], $pkg->excludes);
        $this->assertTrue($pkg->is_active);

        // Also test admin saving a package for the destination
        $adminPkgPayload = [
            'name' => 'Kunjungan Singkat 1 Hari Riset',
            'price_per_pax' => 45000,
            'min_pax' => 20,
            'max_pax' => 150,
            'duration_days' => 1,
            'inquiry_types' => ['penelitian'],
            'description' => 'Kunjungan observasi laboratorium basah dan herbarium.',
            'included_items' => "Jas lab sekali pakai\nHerbarium kit",
            'is_active' => '1',
        ];

        $adminResponse = $this->actingAs($this->admin)
            ->post(route('admin.destinations.packages.save', $destination->id), $adminPkgPayload);

        $adminResponse->assertRedirect();
        $adminPkg = Package::where('name', 'Kunjungan Singkat 1 Hari Riset')->first();
        $this->assertNotNull($adminPkg);
        $this->assertEquals(20, $adminPkg->min_pax);
        $this->assertEquals(150, $adminPkg->max_pax);
        $this->assertEquals(['penelitian'], $adminPkg->inquiry_types);
    }

    public function test_addon_pricing_types_validation_and_saving(): void
    {
        $destination = Destination::create([
            'partner_id' => $this->partner->id,
            'category_id' => $this->category->id,
            'province_id' => $this->province->id,
            'cancellation_policy_id' => $this->policy->id,
            'name' => 'Taman Sains Alam',
            'slug' => 'taman-sains-alam',
            'city' => 'Lembang',
            'address' => 'Jl. Cikole',
            'description' => 'Deskripsi lengkap taman sains alam dan edukasi lingkungan.',
            'default_daily_capacity' => 100,
            'cover_image' => 'destinations/covers/fake.jpg',
            'status' => DestinationStatus::DRAFT,
        ]);

        // 1. Invalid pricing type 'fixed' must be rejected
        $invalidPayload = [
            'name' => 'Sewa Tenda Rombongan',
            'price' => 200000,
            'pricing_type' => 'fixed',
            'description' => 'Tenda pleton untuk istirahat rombongan.',
        ];

        $responseInvalid = $this->actingAs($this->partner)
            ->from(route('mitra.destinations.edit', $destination->id))
            ->post(route('mitra.destinations.addons.save', $destination->id), $invalidPayload);

        $responseInvalid->assertSessionHasErrors('pricing_type');

        // 2. All 4 valid pricing types must succeed
        $validTypes = ['per_pax', 'per_group', 'per_day', 'per_pax_per_day'];
        foreach ($validTypes as $type) {
            $validPayload = [
                'name' => "Layanan {$type}",
                'price' => 50000,
                'pricing_type' => $type,
                'description' => "Penjelasan untuk layanan {$type}",
            ];

            $responseValid = $this->actingAs($this->partner)
                ->post(route('mitra.destinations.addons.save', $destination->id), $validPayload);

            $responseValid->assertRedirect();
            $addon = Addon::where('destination_id', $destination->id)->where('name', "Layanan {$type}")->first();
            $this->assertNotNull($addon);
            $this->assertEquals($type, $addon->pricing_type->value);
        }
    }

    public function test_destination_submission_requires_cover_image_and_active_package(): void
    {
        Event::fake([DestinationSubmitted::class]);

        // Destination without cover and without package
        $dest = Destination::create([
            'partner_id' => $this->partner->id,
            'category_id' => $this->category->id,
            'province_id' => $this->province->id,
            'cancellation_policy_id' => $this->policy->id,
            'name' => 'Tapak Draf Belum Siap',
            'slug' => 'tapak-draf-belum-siap',
            'city' => 'Lembang',
            'address' => 'Jl. Cikole',
            'description' => 'Deskripsi tapak draf yang belum memiliki cover maupun paket.',
            'default_daily_capacity' => 100,
            'cover_image' => null,
            'status' => DestinationStatus::DRAFT,
        ]);

        // Submit fails because no packages
        $response1 = $this->actingAs($this->partner)
            ->from(route('mitra.destinations.edit', $dest->id))
            ->post(route('mitra.destinations.submit', $dest->id));

        $response1->assertRedirect();
        $response1->assertSessionHas('error');
        $this->assertEquals(DestinationStatus::DRAFT, $dest->fresh()->status);

        // Add package, but still no cover image
        Package::create([
            'destination_id' => $dest->id,
            'name' => 'Paket Belajar',
            'slug' => 'paket-belajar',
            'description' => 'Deskripsi paket belajar',
            'price_per_pax' => 50000,
            'min_pax' => 10,
            'max_pax' => 100,
            'duration_days' => 1,
            'inquiry_types' => ['study_tour'],
            'is_active' => true,
        ]);

        $response2 = $this->actingAs($this->partner)
            ->from(route('mitra.destinations.edit', $dest->id))
            ->post(route('mitra.destinations.submit', $dest->id));

        $response2->assertRedirect();
        $response2->assertSessionHas('error');
        $this->assertStringContainsString('foto sampul', session('error'));
        $this->assertEquals(DestinationStatus::DRAFT, $dest->fresh()->status);

        // Add cover image and submit successfully
        $dest->update(['cover_image' => 'destinations/covers/cikole.jpg']);

        $response3 = $this->actingAs($this->partner)
            ->from(route('mitra.destinations.edit', $dest->id))
            ->post(route('mitra.destinations.submit', $dest->id));

        $response3->assertRedirect();
        $response3->assertSessionHas('success');
        $this->assertEquals(DestinationStatus::PENDING_REVIEW, $dest->fresh()->status);
        Event::assertDispatched(DestinationSubmitted::class);
    }

    public function test_admin_curation_moderation_flow_and_events(): void
    {
        Event::fake([DestinationModerated::class]);

        $destination = Destination::create([
            'partner_id' => $this->partner->id,
            'category_id' => $this->category->id,
            'province_id' => $this->province->id,
            'cancellation_policy_id' => $this->policy->id,
            'name' => 'Tapak Telaah Kurasi',
            'slug' => 'tapak-telaah-kurasi',
            'city' => 'Lembang',
            'address' => 'Jl. Cikole',
            'description' => 'Deskripsi tapak telaah kurasi admin.',
            'default_daily_capacity' => 100,
            'cover_image' => 'destinations/covers/cikole.jpg',
            'status' => DestinationStatus::PENDING_REVIEW,
        ]);

        Package::create([
            'destination_id' => $destination->id,
            'name' => 'Paket Utama',
            'slug' => 'paket-utama',
            'description' => 'Deskripsi paket utama',
            'price_per_pax' => 50000,
            'min_pax' => 10,
            'max_pax' => 100,
            'duration_days' => 1,
            'inquiry_types' => ['study_tour'],
            'is_active' => true,
        ]);

        // 1. Requesting revision requires note
        $emptyNoteResponse = $this->actingAs($this->admin)
            ->post(route('admin.destinations.updateStatus', $destination->id), [
                'action' => 'revision',
                'notes' => '',
            ]);
        $emptyNoteResponse->assertSessionHasErrors('notes');

        // 2. Request revision with note
        $revisionResponse = $this->actingAs($this->admin)
            ->post(route('admin.destinations.updateStatus', $destination->id), [
                'action' => 'revision',
                'notes' => 'Mohon tambahkan rincian aktivitas interaktif pada deskripsi paket.',
            ]);
        $revisionResponse->assertRedirect();
        $this->assertEquals(DestinationStatus::REVISION_REQUIRED, $destination->fresh()->status);
        Event::assertDispatched(DestinationModerated::class, function ($event) use ($destination) {
            return $event->destination->id === $destination->id && $event->action === 'revision' && str_contains($event->note, 'aktivitas interaktif');
        });

        // 3. Approve destination
        $approveResponse = $this->actingAs($this->admin)
            ->post(route('admin.destinations.updateStatus', $destination->id), [
                'action' => 'approve',
                'notes' => 'Destinasi memenuhi seluruh standar mutu Destinara.',
            ]);
        $approveResponse->assertRedirect();
        $destination->refresh();
        $this->assertEquals(DestinationStatus::PUBLISHED, $destination->status);
        $this->assertNotNull($destination->published_at);
        Event::assertDispatched(DestinationModerated::class, function ($event) use ($destination) {
            return $event->destination->id === $destination->id && $event->action === 'approve';
        });

        // 4. Reject destination
        $rejectResponse = $this->actingAs($this->admin)
            ->post(route('admin.destinations.updateStatus', $destination->id), [
                'action' => 'reject',
                'notes' => 'Lokasi tidak dapat diakses oleh bus besar rombongan pelajar.',
            ]);
        $rejectResponse->assertRedirect();
        $this->assertEquals(DestinationStatus::ARCHIVED, $destination->fresh()->status);
        Event::assertDispatched(DestinationModerated::class, function ($event) use ($destination) {
            return $event->destination->id === $destination->id && $event->action === 'reject';
        });
    }
}
