<?php

namespace Tests\Feature;

use App\Domain\Booking\Services\BookingCodeGenerator;
use App\Enums\AccountType;
use App\Enums\BookingStatus;
use App\Enums\DestinationStatus;
use App\Enums\PaymentStatus;
use App\Enums\VerificationStatus;
use App\Livewire\Buyer\BookingForm;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        $this->get('/buyer/booking/create?tapak='.$this->destination->slug)->assertStatus(200);
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
            ->assertSet('grandTotal', 1265000)
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

    public function test_livewire_booking_form_validates_pax_bounds_zeros_total_and_differentiates_slot_messages(): void
    {
        $this->actingAs($this->buyer);

        $futureDate = now()->addDays(15)->format('Y-m-d');

        // 1. Participant count < min_pax (5 < 10)
        Livewire::test(BookingForm::class, ['destination' => $this->destination])
            ->set('packageId', $this->package->id)
            ->set('plannedDateStart', $futureDate)
            ->set('participantCount', 5)
            ->call('recalculate')
            ->assertHasErrors(['participantCount' => 'Paket ini untuk 10–100 peserta'])
            ->assertSet('grandTotal', 0)
            ->assertSet('subtotal', 0)
            ->call('submit')
            ->assertHasErrors(['participantCount'])
            ->assertNoRedirect();

        // 2. Participant count > max_pax (150 > 100)
        Livewire::test(BookingForm::class, ['destination' => $this->destination])
            ->set('packageId', $this->package->id)
            ->set('plannedDateStart', $futureDate)
            ->set('participantCount', 150)
            ->call('recalculate')
            ->assertHasErrors(['participantCount' => 'Paket ini untuk 10–100 peserta'])
            ->assertSet('grandTotal', 0)
            ->call('submit')
            ->assertHasErrors(['participantCount'])
            ->assertNoRedirect();

        // 3. Valid participant count clears error and calculates total
        Livewire::test(BookingForm::class, ['destination' => $this->destination])
            ->set('packageId', $this->package->id)
            ->set('plannedDateStart', $futureDate)
            ->set('participantCount', 20)
            ->call('recalculate')
            ->assertHasNoErrors('participantCount')
            ->assertSet('grandTotal', 1265000);

        // 4. Slot message: tanggal di luar rentang
        Livewire::test(BookingForm::class, ['destination' => $this->destination])
            ->set('packageId', $this->package->id)
            ->set('participantCount', 20)
            ->set('plannedDateStart', now()->addDays(1)->format('Y-m-d'))
            ->call('recalculate')
            ->assertSet('isSlotAvailable', false)
            ->assertSee('di luar rentang');

        // 5. Slot message: slot ditutup
        $closedDate = now()->addDays(20)->format('Y-m-d');
        AvailabilitySlot::create([
            'destination_id' => $this->destination->id,
            'date' => $closedDate,
            'capacity' => 100,
            'booked_count' => 0,
            'held_count' => 0,
            'status' => 'closed',
        ]);

        Livewire::test(BookingForm::class, ['destination' => $this->destination])
            ->set('packageId', $this->package->id)
            ->set('participantCount', 20)
            ->set('plannedDateStart', $closedDate)
            ->call('recalculate')
            ->assertSet('isSlotAvailable', false)
            ->assertSet('availabilityMessage', 'Slot ditutup oleh pengelola tapak pada tanggal ini.')
            ->assertSee('Slot ditutup');

        // 6. Slot message: slot penuh
        $fullDate = now()->addDays(25)->format('Y-m-d');
        AvailabilitySlot::create([
            'destination_id' => $this->destination->id,
            'date' => $fullDate,
            'capacity' => 10,
            'booked_count' => 10,
            'held_count' => 0,
            'status' => 'open',
        ]);

        Livewire::test(BookingForm::class, ['destination' => $this->destination])
            ->set('packageId', $this->package->id)
            ->set('participantCount', 20)
            ->set('plannedDateStart', $fullDate)
            ->call('recalculate')
            ->assertSet('isSlotAvailable', false)
            ->assertSet('availabilityMessage', 'Slot penuh untuk tanggal kunjungan yang dipilih.')
            ->assertSee('Slot penuh');
    }

    public function test_destination_detail_accessible_by_buyer(): void
    {
        $this->actingAs($this->buyer)
            ->get(route('destinasi.show', $this->destination->slug))
            ->assertStatus(200)
            ->assertSee('Ajukan Booking');
    }

    public function test_destination_detail_accessible_by_partner(): void
    {
        $this->actingAs($this->partner)
            ->get(route('destinasi.show', $this->destination->slug))
            ->assertStatus(200)
            ->assertSee('Dashboard Mitra');
    }

    public function test_destination_detail_accessible_by_admin(): void
    {
        $admin = User::factory()->create([
            'account_type' => AccountType::INTERNAL,
            'status' => 'active',
        ]);
        $this->actingAs($admin)
            ->get(route('destinasi.show', $this->destination->slug))
            ->assertStatus(200)
            ->assertSee('Dashboard Admin');
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
        $booking = Booking::create([
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
        $this->get('/buyer/booking/'.$booking->booking_code)->assertStatus(404);
    }

    public function test_buyer_can_cancel_unpaid_booking_with_zero_refund(): void
    {
        $this->actingAs($this->buyer);

        $booking = Booking::create([
            'booking_code' => app(BookingCodeGenerator::class)->generate(),
            'user_id' => $this->buyer->id,
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

        $response = $this->post("/buyer/booking/{$booking->booking_code}/batal", [
            'reason' => 'Perubahan agenda sekolah',
        ]);

        $response->assertRedirect("/buyer/booking/{$booking->booking_code}");
        $this->assertEquals(BookingStatus::BATAL, $booking->fresh()->status);
    }

    public function test_buyer_can_check_payment_status(): void
    {
        $this->actingAs($this->buyer);

        $booking = Booking::create([
            'booking_code' => app(BookingCodeGenerator::class)->generate(),
            'user_id' => $this->buyer->id,
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

        Payment::create([
            'booking_id' => $booking->id,
            'gateway' => 'manual_transfer',
            'external_id' => 'PAY-'.$booking->booking_code,
            'amount' => 940000,
            'status' => PaymentStatus::PENDING,
            'payment_method' => 'manual_transfer',
        ]);

        $response = $this->post("/buyer/booking/{$booking->booking_code}/cek-status");
        $response->assertRedirect("/buyer/booking/{$booking->booking_code}");
        $response->assertSessionHas('info');
    }

    public function test_buyer_can_reupload_manual_proof(): void
    {
        Storage::fake('local');
        $this->actingAs($this->buyer);

        $booking = Booking::create([
            'booking_code' => app(BookingCodeGenerator::class)->generate(),
            'user_id' => $this->buyer->id,
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

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'gateway' => 'manual_transfer',
            'external_id' => 'PAY-'.$booking->booking_code,
            'amount' => 940000,
            'status' => PaymentStatus::PENDING,
            'payment_method' => 'manual_transfer',
            'raw_response' => ['rejection_reason' => 'Gambar buram'],
        ]);

        $file = UploadedFile::fake()->create('proof.jpg', 500, 'image/jpeg');

        $response = $this->post("/buyer/booking/{$booking->booking_code}/bukti-transfer", [
            'proof_file' => $file,
        ]);

        $response->assertRedirect("/buyer/booking/{$booking->booking_code}");
        $this->assertNotNull($payment->fresh()->proof_path);
    }

    public function test_buyer_notifications_center(): void
    {
        $this->actingAs($this->buyer);

        $notif = DatabaseNotification::create([
            'id' => Str::uuid()->toString(),
            'type' => 'Destinara\\InAppNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $this->buyer->id,
            'data' => [
                'subject' => 'Tagihan Siap Dibayar',
                'message' => 'Tagihan Anda telah diterbitkan.',
                'event_key' => 'booking.created',
            ],
            'read_at' => null,
        ]);

        $this->get('/buyer/notifikasi')->assertStatus(200)->assertSee('Tagihan Siap Dibayar');

        $this->post("/buyer/notifikasi/{$notif->id}/read")->assertRedirect();
        $this->assertNotNull($notif->fresh()->read_at);

        $notif2 = DatabaseNotification::create([
            'id' => Str::uuid()->toString(),
            'type' => 'Destinara\\InAppNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $this->buyer->id,
            'data' => [
                'subject' => 'Pembayaran Dikonfirmasi',
                'message' => 'Pembayaran lunas.',
                'event_key' => 'payment.paid',
            ],
            'read_at' => null,
        ]);

        $this->post('/buyer/notifikasi/read-all')->assertRedirect();
        $this->assertNotNull($notif2->fresh()->read_at);
    }

    public function test_buyer_and_admin_booking_detail_cost_breakdown_not_double_counting_b7(): void
    {
        $code = app(BookingCodeGenerator::class)->generate();

        $booking = Booking::create([
            'booking_code' => $code,
            'user_id' => $this->buyer->id,
            'institution_id' => $this->buyer->institution->id,
            'destination_id' => $this->destination->id,
            'package_id' => $this->package->id,
            'inquiry_type' => 'study_tour',
            'participant_count' => 25,
            'companion_count' => 2,
            'subtotal_amount' => 3300000,
            'addons_amount' => 1050000,
            'insurance_amount' => 50000,
            'platform_fee_amount' => 35000,
            'discount_amount' => 0,
            'total_amount' => 3385000,
            'commission_percent' => 10,
            'commission_amount' => 330000,
            'partner_net_amount' => 2970000,
            'planned_date_start' => now()->addDays(10)->toDateString(),
            'planned_date_end' => now()->addDays(10)->toDateString(),
            'status' => BookingStatus::MENUNGGU_PEMBAYARAN,
        ]);

        \App\Models\BookingItem::create([
            'booking_id' => $booking->id,
            'type' => 'package',
            'reference_id' => $this->package->id,
            'name' => 'Paket Study Tour Konservasi (1 Hari)',
            'quantity' => 25,
            'unit_price' => 90000,
            'total' => 2250000,
        ]);

        \App\Models\BookingItem::create([
            'booking_id' => $booking->id,
            'type' => 'addon',
            'reference_id' => 1,
            'name' => 'Add-on: Workshop Etnobotani',
            'quantity' => 25,
            'unit_price' => 42000,
            'total' => 1050000,
        ]);

        \App\Models\BookingItem::create([
            'booking_id' => $booking->id,
            'type' => 'insurance',
            'name' => 'Premi Asuransi Siswa',
            'quantity' => 25,
            'unit_price' => 2000,
            'total' => 50000,
        ]);

        \App\Models\BookingItem::create([
            'booking_id' => $booking->id,
            'type' => 'platform_fee',
            'name' => 'Administrasi & Verifikasi Dokumen',
            'quantity' => 1,
            'unit_price' => 35000,
            'total' => 35000,
        ]);

        // 1. Buyer Booking Detail
        $buyerRes = $this->actingAs($this->buyer)->get(route('buyer.booking.show', $code));
        $buyerRes->assertOk();
        $buyerRes->assertSee('Paket Study Tour Konservasi (1 Hari)');
        $buyerRes->assertSee('2.250.000');
        $buyerRes->assertSee('Add-on: Workshop Etnobotani');
        $buyerRes->assertSee('1.050.000');
        $buyerRes->assertSee('Total: Rp 3.385.000');
        $buyerRes->assertDontSee('Biaya Tapak (25 Siswa)');

        // 2. Admin Booking Detail
        $admin = User::factory()->create(['account_type' => AccountType::INTERNAL, 'status' => 'active']);
        $admin->assignRole('super_admin');
        $adminRes = $this->actingAs($admin)->get(route('admin.bookings.show', $code));
        $adminRes->assertOk();
        $adminRes->assertSee('Paket Study Tour Konservasi (1 Hari)');
        $adminRes->assertSee('2.250.000');
        $adminRes->assertSee('Add-on: Workshop Etnobotani');
        $adminRes->assertSee('1.050.000');
        $adminRes->assertSee('3.385.000');
    }
}

