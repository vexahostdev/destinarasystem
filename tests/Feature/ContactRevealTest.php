<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\BookingStatus;
use App\Enums\DestinationStatus;
use App\Enums\VerificationStatus;
use App\Models\Booking;
use App\Models\CancellationPolicy;
use App\Models\Destination;
use App\Models\DestinationCategory;
use App\Models\DestinationContact;
use App\Models\Institution;
use App\Models\Package;
use App\Models\PartnerProfile;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Audit v11 K-7: aturan reveal kontak destinasi (SPEC §8.5) berbasis setting contact_reveal_status,
 * satu Policy untuk halaman publik dan detail booking buyer.
 */
class ContactRevealTest extends TestCase
{
    use RefreshDatabase;

    private const PHONE = '081298765432';

    protected Destination $destination;

    protected User $buyer;

    protected Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $partner = User::factory()->create(['account_type' => AccountType::PARTNER, 'status' => 'active']);
        PartnerProfile::create([
            'user_id' => $partner->id,
            'organization_name' => 'Pokdarwis Kontak',
            'business_type' => 'pokdarwis',
            'pic_name' => 'Bu Sari',
            'pic_phone' => '6281200000001',
            'pic_email' => 'sari@kontak.test',
            'verification_status' => VerificationStatus::VERIFIED,
        ]);

        $this->destination = Destination::create([
            'partner_id' => $partner->id,
            'category_id' => DestinationCategory::firstOrCreate(['slug' => 'ekowisata'], ['name' => 'Ekowisata', 'is_active' => true])->id,
            'province_id' => Province::first()->id,
            'cancellation_policy_id' => CancellationPolicy::first()->id,
            'name' => 'Desa Kontak Rahasia',
            'slug' => 'desa-kontak-rahasia',
            'city' => 'Sleman',
            'description' => 'Destinasi uji reveal kontak.',
            'status' => DestinationStatus::PUBLISHED,
            'default_daily_capacity' => 100,
        ]);

        DestinationContact::create([
            'destination_id' => $this->destination->id,
            'contact_name' => 'Pak Narahubung',
            'role' => 'Koordinator Tapak',
            'phone' => self::PHONE,
            'is_primary' => true,
        ]);

        $package = Package::create([
            'destination_id' => $this->destination->id,
            'name' => 'Paket Uji',
            'slug' => 'paket-uji',
            'inquiry_types' => ['study_tour'],
            'duration_days' => 1,
            'price_per_pax' => 50000,
            'min_pax' => 10,
            'max_pax' => 100,
            'is_active' => true,
        ]);

        $this->buyer = User::factory()->create(['account_type' => AccountType::BUYER, 'status' => 'active']);
        $institution = Institution::create([
            'user_id' => $this->buyer->id,
            'institution_name' => 'SMP Uji Kontak',
            'pic_name' => 'Bu Guru',
            'pic_phone' => '6281200000002',
            'pic_email' => 'guru@smp-uji.test',
        ]);

        $this->booking = Booking::create([
            'booking_code' => 'DST-2610-KTK01',
            'user_id' => $this->buyer->id,
            'institution_id' => $institution->id,
            'destination_id' => $this->destination->id,
            'package_id' => $package->id,
            'inquiry_type' => 'study_tour',
            'planned_date_start' => now()->addDays(10)->toDateString(),
            'planned_date_end' => now()->addDays(10)->toDateString(),
            'participant_count' => 20,
            'subtotal_amount' => 1000000,
            'addons_amount' => 0,
            'insurance_amount' => 0,
            'platform_fee_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 1000000,
            'commission_percent' => 10,
            'commission_amount' => 100000,
            'partner_net_amount' => 900000,
            'status' => BookingStatus::DIBAYAR,
        ]);
    }

    public function test_guest_and_unrelated_buyer_never_receive_phone_number(): void
    {
        $this->get(route('destinasi.show', $this->destination->slug))
            ->assertOk()
            ->assertSee('Pak Narahubung')
            ->assertSee('Nomor terkunci')
            ->assertDontSee(self::PHONE)
            ->assertDontSee(substr(self::PHONE, -4));

        $stranger = User::factory()->create(['account_type' => AccountType::BUYER, 'status' => 'active']);
        $this->actingAs($stranger)->get(route('destinasi.show', $this->destination->slug))
            ->assertDontSee(self::PHONE);
    }

    public function test_buyer_reveal_follows_contact_reveal_status_setting_including_completed_bookings(): void
    {
        // Default: dikonfirmasi. Booking masih dibayar -> terkunci
        $this->actingAs($this->buyer)->get(route('destinasi.show', $this->destination->slug))
            ->assertDontSee(self::PHONE)
            ->assertSee('dikonfirmasi');

        // Setting diturunkan ke dibayar -> terbuka
        setting(['contact_reveal_status' => 'dibayar']);
        $this->actingAs($this->buyer)->get(route('destinasi.show', $this->destination->slug))
            ->assertSee(self::PHONE);

        // Booking selesai tetap bisa melihat kontak (dulu hilang karena status hardcode)
        setting(['contact_reveal_status' => 'dikonfirmasi']);
        $this->booking->update(['status' => BookingStatus::SELESAI]);
        $this->actingAs($this->buyer)->get(route('destinasi.show', $this->destination->slug))
            ->assertSee(self::PHONE);

        // Booking batal -> terkunci lagi
        $this->booking->update(['status' => BookingStatus::BATAL]);
        $this->actingAs($this->buyer)->get(route('destinasi.show', $this->destination->slug))
            ->assertDontSee(self::PHONE);
    }

    public function test_internal_staff_without_super_admin_and_owning_partner_can_see_contacts(): void
    {
        $ops = User::factory()->create(['account_type' => AccountType::INTERNAL, 'status' => 'active']);
        $ops->assignRole('admin_operasional');

        $this->actingAs($ops)->get(route('destinasi.show', $this->destination->slug))
            ->assertSee(self::PHONE);

        $this->assertTrue($this->destination->partner->can('revealContacts', $this->destination));

        $otherPartner = User::factory()->create(['account_type' => AccountType::PARTNER, 'status' => 'active']);
        $this->assertFalse($otherPartner->can('revealContacts', $this->destination));
    }

    public function test_booking_policy_and_destination_policy_share_the_same_rule(): void
    {
        foreach (['dibayar', 'dikonfirmasi', 'berlangsung'] as $threshold) {
            setting(['contact_reveal_status' => $threshold]);

            foreach (BookingStatus::cases() as $status) {
                $this->booking->update(['status' => $status]);

                $this->assertSame(
                    $this->buyer->can('revealContact', $this->booking->fresh()),
                    $this->buyer->can('revealContacts', $this->destination),
                    "Policy tidak konsisten untuk threshold {$threshold} dan status {$status->value}"
                );
            }
        }
    }
}
