<?php

namespace Tests\Feature;

use App\Domain\Booking\Services\BookingCodeGenerator;
use App\Enums\AccountType;
use App\Enums\BookingStatus;
use App\Enums\DestinationStatus;
use App\Enums\PackageType;
use App\Models\Booking;
use App\Models\BookingSlotAllocation;
use App\Models\Destination;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SchedulerTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_heartbeat_command_updates_cache(): void
    {
        Artisan::call('system:heartbeat');

        $this->assertNotNull(Cache::get('system_last_heartbeat_at'));
    }

    public function test_whatsapp_health_check_command_records_health(): void
    {
        app(\App\Domain\Settings\SettingsRepository::class)->set('wa_api_key', 'test-api-key');

        Http::fake([
            '*wa.vexahostcloud.my.id*' => Http::response(['status' => 'ok', 'device' => 'connected'], 200),
        ]);

        Artisan::call('whatsapp:health-check');

        $health = Cache::get('system_whatsapp_health');
        $this->assertIsArray($health);
        $this->assertEquals('connected', $health['status']);
    }

    public function test_expire_unpaid_bookings_command_expires_overdue_bookings(): void
    {
        $this->seed();

        $partner = User::factory()->create(['account_type' => AccountType::PARTNER]);
        $buyer = User::factory()->create(['account_type' => AccountType::BUYER]);

        $province = \App\Models\Province::first();
        $policy = \App\Models\CancellationPolicy::first();

        $destination = Destination::create([
            'partner_id' => $partner->id,
            'category_id' => 1,
            'province_id' => $province->id,
            'cancellation_policy_id' => $policy->id,
            'name' => 'Bukit Senja',
            'slug' => 'bukit-senja',
            'city' => 'Bantul',
            'address' => 'Mangunan',
            'default_daily_capacity' => 100,
            'status' => DestinationStatus::PUBLISHED,
        ]);

        $package = Package::create([
            'destination_id' => $destination->id,
            'name' => 'Paket Edukasi Budaya',
            'slug' => 'paket-edukasi-budaya',
            'inquiry_types' => ['study_tour'],
            'duration_days' => 1,
            'price_per_pax' => 50000,
            'min_pax' => 10,
            'max_pax' => 50,
            'is_active' => true,
        ]);

        $institution = \App\Models\Institution::create([
            'user_id' => $buyer->id,
            'institution_name' => 'SMP 1 Test',
            'pic_name' => 'Budi',
            'pic_phone' => '6281234567891',
            'pic_email' => 'budi@test.com',
        ]);

        $booking = Booking::create([
            'booking_code' => app(BookingCodeGenerator::class)->generate(),
            'user_id' => $buyer->id,
            'institution_id' => $institution->id,
            'destination_id' => $destination->id,
            'package_id' => $package->id,
            'inquiry_type' => 'study_tour',
            'planned_date_start' => now()->addDays(2)->toDateString(),
            'planned_date_end' => now()->addDays(2)->toDateString(),
            'participant_count' => 10,
            'status' => BookingStatus::MENUNGGU_PEMBAYARAN,
            'subtotal_amount' => 500000,
            'addons_amount' => 0,
            'insurance_amount' => 20000,
            'platform_fee_amount' => 10000,
            'discount_amount' => 0,
            'total_amount' => 530000,
            'commission_percent' => 10,
            'commission_amount' => 50000,
            'partner_net_amount' => 450000,
            'expires_at' => now()->subMinutes(10), // expired!
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($booking) {
            app(\App\Domain\Booking\Services\AvailabilityService::class)->holdSlots($booking);
        });

        Artisan::call('bookings:expire-unpaid');

        $booking->refresh();
        $this->assertEquals(BookingStatus::KEDALUWARSA, $booking->status);
        $this->assertDatabaseMissing('booking_slot_allocations', [
            'booking_id' => $booking->id,
        ]);
    }
}
