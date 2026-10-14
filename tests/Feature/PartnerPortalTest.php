<?php

namespace Tests\Feature;

use App\Domain\Booking\Services\BookingCodeGenerator;
use App\Enums\AccountType;
use App\Enums\BookingStatus;
use App\Enums\DestinationStatus;
use App\Enums\PaymentStatus;
use App\Enums\VerificationStatus;
use App\Jobs\GenerateDocumentJob;
use App\Models\AvailabilitySlot;
use App\Models\Bank;
use App\Models\Booking;
use App\Models\CancellationPolicy;
use App\Models\Destination;
use App\Models\DestinationCategory;
use App\Models\Institution;
use App\Models\Package;
use App\Models\PartnerProfile;
use App\Models\Payment;
use App\Models\Province;
use App\Models\Review;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PartnerPortalTest extends TestCase
{
    use RefreshDatabase;

    protected User $partner;

    protected User $buyer;

    protected Destination $destination;

    protected Package $package;

    protected Bank $bank;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->partner = User::factory()->create([
            'account_type' => AccountType::PARTNER,
            'status' => 'active',
        ]);

        $this->bank = Bank::firstOrCreate(['code' => 'BCA'], [
            'name' => 'Bank Central Asia',
            'is_active' => true,
        ]);

        $province = Province::first() ?? Province::create(['code' => '34', 'name' => 'DI Yogyakarta']);

        PartnerProfile::create([
            'user_id' => $this->partner->id,
            'organization_name' => 'Koperasi Tani Wanagama',
            'verification_status' => VerificationStatus::VERIFIED,
            'bank_id' => $this->bank->id,
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'Koperasi Tani Wanagama',
            'province_id' => $province->id,
            'city' => 'Gunungkidul',
        ]);

        $category = DestinationCategory::first() ?? DestinationCategory::create([
            'name' => 'Hutan Pendidikan',
            'slug' => 'hutan-pendidikan',
            'is_active' => true,
        ]);

        $policy = CancellationPolicy::first() ?? CancellationPolicy::create([
            'name' => 'Standar',
            'slug' => 'standar',
            'description' => 'Bisa refund s.d H-7',
            'refund_percent_before_h7' => 100,
            'refund_percent_h7_to_h3' => 50,
            'refund_percent_after_h3' => 0,
        ]);

        $this->destination = Destination::create([
            'partner_id' => $this->partner->id,
            'category_id' => $category->id,
            'province_id' => $province->id,
            'cancellation_policy_id' => $policy->id,
            'name' => 'Hutan Wanagama',
            'slug' => 'hutan-wanagama',
            'city' => 'Gunungkidul',
            'address' => 'Banaran, Playen, Gunungkidul',
            'description' => 'Hutan pendidikan dan laboratorium alam riset konservasi.',
            'default_daily_capacity' => 100,
            'status' => DestinationStatus::PUBLISHED,
        ]);

        $this->package = Package::create([
            'destination_id' => $this->destination->id,
            'name' => 'Paket Edukasi Silvikultur',
            'slug' => 'edukasi-silvikultur',
            'inquiry_types' => ['study_tour'],
            'duration_days' => 1,
            'price_per_pax' => 50000,
            'min_pax' => 10,
            'max_pax' => 200,
            'is_active' => true,
        ]);

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
            'city' => 'Bantul',
        ]);
    }

    public function test_partner_can_access_dashboard_and_views(): void
    {
        $response = $this->actingAs($this->partner)->get(route('mitra.dashboard'));
        $response->assertOk();
        $response->assertSee('Panel Pengelola Tapak Adat');
        $response->assertSee('Koperasi Tani Wanagama');
    }

    public function test_partner_can_access_income_report(): void
    {
        $response = $this->actingAs($this->partner)->get(route('mitra.income'));
        $response->assertOk();
        $response->assertSee('Rekonsiliasi Pendapatan');
        $response->assertSee('Pencairan Otomatis via Batch Mingguan Admin');
    }

    public function test_partner_can_view_incoming_bookings(): void
    {
        $booking = Booking::create([
            'booking_code' => app(BookingCodeGenerator::class)->generate(),
            'user_id' => $this->buyer->id,
            'institution_id' => $this->buyer->institution->id,
            'destination_id' => $this->destination->id,
            'package_id' => $this->package->id,
            'inquiry_type' => 'study_tour',
            'planned_date_start' => Carbon::tomorrow()->toDateString(),
            'planned_date_end' => Carbon::tomorrow()->toDateString(),
            'participant_count' => 30,
            'companion_count' => 3,
            'subtotal_amount' => 1500000,
            'total_amount' => 1500000,
            'commission_percent' => 10,
            'commission_amount' => 150000,
            'partner_net_amount' => 1350000,
            'status' => BookingStatus::DIBAYAR,
        ]);

        $response = $this->actingAs($this->partner)->get(route('mitra.bookings'));
        $response->assertOk();
        $response->assertSee($booking->booking_code);
        $response->assertSee('SMA 1 Bantul');
        $response->assertSee('Terima Rombongan');
    }

    public function test_partner_can_confirm_paid_booking(): void
    {
        Queue::fake();

        $booking = Booking::create([
            'booking_code' => app(BookingCodeGenerator::class)->generate(),
            'user_id' => $this->buyer->id,
            'institution_id' => $this->buyer->institution->id,
            'destination_id' => $this->destination->id,
            'package_id' => $this->package->id,
            'inquiry_type' => 'study_tour',
            'planned_date_start' => Carbon::tomorrow()->toDateString(),
            'planned_date_end' => Carbon::tomorrow()->toDateString(),
            'participant_count' => 20,
            'companion_count' => 2,
            'subtotal_amount' => 1000000,
            'total_amount' => 1000000,
            'commission_percent' => 10,
            'commission_amount' => 100000,
            'partner_net_amount' => 900000,
            'status' => BookingStatus::DIBAYAR,
        ]);

        $response = $this->actingAs($this->partner)->post(route('mitra.booking.confirm', $booking->id));
        $response->assertRedirect();

        $booking->refresh();
        $this->assertEquals(BookingStatus::DIKONFIRMASI, $booking->status);
        $this->assertNotNull($booking->confirmed_at);

        Queue::assertPushed(GenerateDocumentJob::class);
    }

    public function test_partner_can_reject_paid_booking_with_refund_created(): void
    {
        $booking = Booking::create([
            'booking_code' => app(BookingCodeGenerator::class)->generate(),
            'user_id' => $this->buyer->id,
            'institution_id' => $this->buyer->institution->id,
            'destination_id' => $this->destination->id,
            'package_id' => $this->package->id,
            'inquiry_type' => 'study_tour',
            'planned_date_start' => Carbon::tomorrow()->toDateString(),
            'planned_date_end' => Carbon::tomorrow()->toDateString(),
            'participant_count' => 20,
            'companion_count' => 2,
            'subtotal_amount' => 1000000,
            'total_amount' => 1000000,
            'commission_percent' => 10,
            'commission_amount' => 100000,
            'partner_net_amount' => 900000,
            'status' => BookingStatus::DIBAYAR,
        ]);

        // Payment record
        Payment::create([
            'booking_id' => $booking->id,
            'gateway' => 'manual_transfer',
            'external_id' => "{$booking->booking_code}-P1",
            'amount' => 1000000,
            'status' => PaymentStatus::PAID,
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($this->partner)->post(route('mitra.booking.reject', $booking->id), [
            'reason' => 'Kawasan ditutup sementara untuk upacara adat setempat.',
        ]);

        $response->assertRedirect();
        $booking->refresh();
        $this->assertEquals(BookingStatus::BATAL, $booking->status);

        $this->assertDatabaseHas('refunds', [
            'booking_id' => $booking->id,
            'amount' => 1000000,
            'percent' => 100,
        ]);
    }

    public function test_partner_can_bulk_generate_slots(): void
    {
        $startDate = Carbon::today()->addDays(1)->toDateString();
        $endDate = Carbon::today()->addDays(5)->toDateString();

        $response = $this->actingAs($this->partner)->post(route('mitra.slots.bulk'), [
            'destination_id' => $this->destination->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'capacity' => 150,
            'days_of_week' => [1, 2, 3, 4, 5],
        ]);

        $response->assertRedirect();

        $this->assertTrue(AvailabilitySlot::where('destination_id', $this->destination->id)->count() > 0);
    }

    public function test_partner_can_process_checkin(): void
    {
        $booking = Booking::create([
            'booking_code' => app(BookingCodeGenerator::class)->generate(),
            'user_id' => $this->buyer->id,
            'institution_id' => $this->buyer->institution->id,
            'destination_id' => $this->destination->id,
            'package_id' => $this->package->id,
            'inquiry_type' => 'study_tour',
            'planned_date_start' => Carbon::today()->toDateString(),
            'planned_date_end' => Carbon::today()->toDateString(),
            'participant_count' => 25,
            'companion_count' => 2,
            'subtotal_amount' => 1250000,
            'total_amount' => 1250000,
            'commission_percent' => 10,
            'commission_amount' => 125000,
            'partner_net_amount' => 1125000,
            'status' => BookingStatus::DIKONFIRMASI,
        ]);

        $response = $this->actingAs($this->partner)->post(route('mitra.checkin.confirm', $booking->booking_code), [
            'actual_participant_count' => 24,
            'note' => '1 peserta izin sakit, 24 peserta hadir lengkap.',
        ]);

        $response->assertRedirect();
        $booking->refresh();

        $this->assertEquals(BookingStatus::BERLANGSUNG, $booking->status);
        $this->assertDatabaseHas('check_ins', [
            'booking_id' => $booking->id,
            'actual_participant_count' => 24,
        ]);
    }

    public function test_partner_can_update_profile(): void
    {
        $response = $this->actingAs($this->partner)->post(route('mitra.profile.update'), [
            'organization_name' => 'Koperasi Tani Wanagama Baru',
            'bank_id' => $this->bank->id,
            'bank_account_number' => '9876543210',
            'bank_account_holder' => 'Koperasi Wanagama',
            'pic_name' => 'Bapak Sudirman',
            'pic_phone' => '6281234567899',
            'address' => 'Jl. Hutan No. 10',
            'city' => 'Gunungkidul',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('partner_profiles', [
            'user_id' => $this->partner->id,
            'organization_name' => 'Koperasi Tani Wanagama Baru',
            'bank_account_number' => '9876543210',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->partner->id,
            'name' => 'Bapak Sudirman',
            'phone' => '6281234567899',
        ]);
    }

    public function test_partner_can_reply_to_review(): void
    {
        $booking = Booking::create([
            'booking_code' => app(BookingCodeGenerator::class)->generate(),
            'user_id' => $this->buyer->id,
            'institution_id' => $this->buyer->institution->id,
            'destination_id' => $this->destination->id,
            'package_id' => $this->package->id,
            'inquiry_type' => 'study_tour',
            'planned_date_start' => Carbon::yesterday()->toDateString(),
            'planned_date_end' => Carbon::yesterday()->toDateString(),
            'participant_count' => 20,
            'companion_count' => 2,
            'subtotal_amount' => 1000000,
            'total_amount' => 1000000,
            'commission_percent' => 10,
            'commission_amount' => 100000,
            'partner_net_amount' => 900000,
            'status' => BookingStatus::SELESAI,
        ]);

        $review = Review::create([
            'booking_id' => $booking->id,
            'destination_id' => $this->destination->id,
            'institution_id' => $this->buyer->institution->id,
            'rating' => 5,
            'comment' => 'Pengalaman belajar yang luar biasa bagi siswa kami!',
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->partner)->post(route('mitra.reviews.reply', $review->id), [
            'reply' => 'Terima kasih atas kunjungannya, kami nantikan kedatangan rombongan berikutnya.',
        ]);

        $response->assertRedirect();
        $review->refresh();

        $this->assertNotNull($review->partner_reply);
        $this->assertNotNull($review->replied_at);
        $this->assertEquals('Terima kasih atas kunjungannya, kami nantikan kedatangan rombongan berikutnya.', $review->partner_reply);
    }
}
