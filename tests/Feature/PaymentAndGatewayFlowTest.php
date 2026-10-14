<?php

namespace Tests\Feature;

use App\Domain\Settings\SettingsRepository;
use App\Enums\AccountType;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Destination;
use App\Models\Package;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentAndGatewayFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $buyer;

    protected Destination $destination;

    protected Package $package;

    protected Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('local');

        $this->admin = User::factory()->create([
            'name' => 'Admin Keuangan',
            'email' => 'admin.finance@destinara.test',
            'account_type' => AccountType::INTERNAL,
            'status' => 'active',
        ]);
        $this->admin->assignRole('super_admin');

        $this->buyer = User::factory()->create([
            'name' => 'Guru Buyer Test',
            'email' => 'buyer.payment@destinara.test',
            'account_type' => AccountType::BUYER,
            'status' => 'active',
        ]);

        $institution = \App\Models\Institution::create([
            'user_id' => $this->buyer->id,
            'institution_name' => 'SMA Negeri 1 Edukasi',
            'institution_type_id' => 1,
            'pic_name' => 'Sari Guru',
            'pic_position' => 'Guru Pendamping',
            'pic_phone' => '081299998888',
            'pic_email' => 'sari@sekolah.sch.id',
            'address' => 'Jl. Pendidikan No. 1',
            'verification_status' => \App\Enums\VerificationStatus::VERIFIED,
        ]);

        $partnerUser = User::factory()->create([
            'account_type' => AccountType::PARTNER,
            'status' => 'active',
        ]);

        $this->destination = Destination::create([
            'partner_id' => $partnerUser->id,
            'category_id' => \App\Models\DestinationCategory::first()->id ?? 1,
            'name' => 'Kampung Adat Pasir Luhur',
            'slug' => 'kampung-adat-pasir-luhur',
            'status' => 'published',
            'city' => 'Bandung Barat',
            'province_id' => 1,
            'address' => 'Jl. Dataran Luhur',
            'contact_phone' => '081234567890',
            'contact_person' => 'Ki Lurah',
        ]);

        $this->package = Package::create([
            'destination_id' => $this->destination->id,
            'name' => 'Paket Edukasi Budaya',
            'slug' => 'paket-edukasi-budaya',
            'price_per_pax' => 50000,
            'min_pax' => 10,
            'max_pax' => 100,
            'inquiry_types' => ['study_tour'],
            'is_active' => true,
        ]);

        $this->booking = Booking::create([
            'booking_code' => 'DST-PAYTEST-001',
            'user_id' => $this->buyer->id,
            'institution_id' => $institution->id,
            'destination_id' => $this->destination->id,
            'package_id' => $this->package->id,
            'inquiry_type' => 'study_tour',
            'status' => BookingStatus::MENUNGGU_PEMBAYARAN,
            'participant_count' => 20,
            'total_amount' => 1000000,
            'subtotal_amount' => 1000000,
            'insurance_amount' => 40000,
            'platform_fee_amount' => 25000,
            'discount_amount' => 0,
            'commission_percent' => 10,
            'commission_amount' => 100000,
            'partner_net_amount' => 900000,
            'planned_date_start' => now()->addDays(14)->toDateString(),
            'planned_date_end' => now()->addDays(14)->toDateString(),
            'expires_at' => now()->addHours(24),
        ]);
    }

    public function test_buyer_payment_page_renders_manual_accounts_from_settings(): void
    {
        $repo = app(SettingsRepository::class);
        $repo->set('manual_transfer_accounts', [
            [
                'bank' => 'BCA Virtual / Giro',
                'number' => '777000888999',
                'holder' => 'PT DESTINARA CHAKRAWAL ARTHA',
            ],
        ]);

        $response = $this->actingAs($this->buyer)->get(route('buyer.booking.payment', $this->booking->booking_code));

        $response->assertOk();
        $response->assertSee('BCA Virtual / Giro');
        $response->assertSee('777000888999');
        $response->assertSee('PT DESTINARA CHAKRAWAL ARTHA');
        $response->assertSee('Biaya Layanan Platform');
    }

    public function test_buyer_process_payment_handles_gateway_exception_gracefully(): void
    {
        // Xendit has no key configured, initiatePayment will throw RuntimeException
        $response = $this->actingAs($this->buyer)->from(route('buyer.booking.payment', $this->booking->booking_code))
            ->post(route('buyer.booking.pay', $this->booking->booking_code), [
                'gateway' => 'xendit',
            ]);

        $response->assertRedirect(route('buyer.booking.payment', $this->booking->booking_code));
        $response->assertSessionHas('error');
    }

    public function test_buyer_process_payment_rejects_sub_minimum_amount_on_online_gateway(): void
    {
        $this->booking->update(['total_amount' => 5000]);

        $response = $this->actingAs($this->buyer)->from(route('buyer.booking.payment', $this->booking->booking_code))
            ->post(route('buyer.booking.pay', $this->booking->booking_code), [
                'gateway' => 'xendit',
            ]);

        $response->assertRedirect(route('buyer.booking.payment', $this->booking->booking_code));
        $response->assertSessionHas('error');
    }

    public function test_admin_can_reject_manual_payment_with_reason_and_buyer_sees_reupload(): void
    {
        // 1. Buyer uploads manual proof
        $file = UploadedFile::fake()->create('bukti_transfer.jpg', 500, 'image/jpeg');
        $response = $this->actingAs($this->buyer)->post(route('buyer.booking.pay', $this->booking->booking_code), [
            'gateway' => 'manual_transfer',
            'proof_file' => $file,
        ]);
        $response->assertRedirect(route('buyer.booking.show', $this->booking->booking_code));

        $payment = Payment::where('booking_id', $this->booking->id)->where('gateway', 'manual_transfer')->first();
        $this->assertNotNull($payment);
        $this->assertNotNull($payment->proof_path);

        // 2. Admin views payments list and sees both Verifikasi and Tolak
        $response = $this->actingAs($this->admin)->get(route('admin.finance.payments'));
        $response->assertOk();
        $response->assertSee('Verifikasi');
        $response->assertSee('Tolak');

        // 3. Admin rejects payment with reason
        $response = $this->actingAs($this->admin)->post(route('admin.finance.rejectManual', $payment->id), [
            'reason' => 'Foto bukti transfer terpotong dan tidak ada nomor referensi bank.',
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('warning');

        $payment->refresh();
        $this->assertEquals('Foto bukti transfer terpotong dan tidak ada nomor referensi bank.', $payment->raw_response['rejection_reason']);

        // 4. Buyer views show page and sees rejection reason and re-upload button
        $response = $this->actingAs($this->buyer)->get(route('buyer.booking.show', $this->booking->booking_code));
        $response->assertOk();
        $response->assertSee('Foto bukti transfer terpotong');
        $response->assertSee('Unggah Ulang Bukti');
    }

    public function test_admin_payments_page_hides_verify_button_when_no_proof_uploaded(): void
    {
        // Create manual payment without proof_path
        Payment::create([
            'booking_id' => $this->booking->id,
            'gateway' => 'manual_transfer',
            'external_id' => "{$this->booking->booking_code}-P1",
            'amount' => $this->booking->total_amount,
            'status' => PaymentStatus::PENDING,
            'proof_path' => null,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.finance.payments'));
        $response->assertOk();
        $response->assertSee('Menunggu bukti diunggah');
        $response->assertDontSee('onclick="return confirm(\'Verifikasi pembayaran manual ini');
    }
}
