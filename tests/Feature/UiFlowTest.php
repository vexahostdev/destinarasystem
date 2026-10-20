<?php

namespace Tests\Feature;

use App\Domain\Settings\SettingsRepository;
use App\Enums\AccountType;
use App\Enums\BookingStatus;
use App\Enums\DestinationStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Enums\VerificationStatus;
use App\Events\DestinationModerated;
use App\Events\UserRegistered;
use App\Livewire\Buyer\BookingForm;
use App\Models\Bank;
use App\Models\Booking;
use App\Models\CancellationPolicy;
use App\Models\Destination;
use App\Models\DestinationCategory;
use App\Models\Institution;
use App\Models\OrganizationType;
use App\Models\Package;
use App\Models\PartnerProfile;
use App\Models\Payment;
use App\Models\Province;
use App\Models\Refund;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UiFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $buyer;

    protected User $partner;

    protected Destination $destination;

    protected Package $package;

    protected Province $province;

    protected Bank $bank;

    protected Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('local');
        Storage::fake('public');

        // Create standard entities
        $this->province = Province::first() ?? Province::create(['code' => '34', 'name' => 'DI Yogyakarta']);
        $this->bank = Bank::first() ?? Bank::create(['code' => 'BCA', 'name' => 'Bank Central Asia', 'is_active' => true]);

        app(\App\Domain\Settings\SettingsRepository::class)->set('manual_transfer_accounts', [
            [
                'bank' => 'BCA',
                'number' => '8880001122',
                'holder' => 'PT DESTINARA EDUKASI NUSANTARA',
            ],
        ]);

        // Create Admin
        $this->admin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin.uiflow@destinara.test',
            'account_type' => AccountType::INTERNAL,
            'status' => 'active',
        ]);
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $this->admin->assignRole($superAdminRole);

        // Create Buyer
        $this->buyer = User::factory()->create([
            'name' => 'Bu Guru Rina',
            'email' => 'rina.buyer@sekolah.id',
            'account_type' => AccountType::BUYER,
            'status' => 'active',
        ]);
        $this->institution = Institution::create([
            'user_id' => $this->buyer->id,
            'institution_name' => 'SMA Negeri 1 Edukasi',
            'pic_name' => $this->buyer->name,
            'pic_phone' => '081234567890',
            'pic_email' => $this->buyer->email,
            'address' => 'Jl. Pendidikan No. 10',
            'province_id' => $this->province->id,
            'city' => 'Yogyakarta',
            'verification_status' => VerificationStatus::VERIFIED,
        ]);

        $orgType = OrganizationType::first() ?? OrganizationType::create(['name' => 'Kelompok Sadar Wisata', 'slug' => 'pokdarwis']);

        // Create Partner
        $this->partner = User::factory()->create([
            'name' => 'Pak Joko Pengelola',
            'email' => 'joko.mitra@desa.id',
            'account_type' => AccountType::PARTNER,
            'status' => 'active',
        ]);
        PartnerProfile::create([
            'user_id' => $this->partner->id,
            'organization_name' => 'Pokdarwis Watu Makmur',
            'organization_type_id' => $orgType->id,
            'legal_document_number' => 'SK-DESA-01/2026',
            'address' => 'Desa Karang, Bantul',
            'province_id' => $this->province->id,
            'city' => 'Bantul',
            'bank_id' => $this->bank->id,
            'bank_account_number' => '5432109876',
            'bank_account_holder' => 'POKDARWIS WATU MAKMUR',
            'verification_status' => VerificationStatus::VERIFIED,
        ]);

        // Create published Destination with active Package
        $category = DestinationCategory::first() ?? DestinationCategory::create(['name' => 'Budaya & Tradisi', 'slug' => 'budaya']);
        $policy = CancellationPolicy::first() ?? CancellationPolicy::create([
            'name' => 'Standar Fleksibel',
            'days_before' => 7,
            'refund_percentage' => 80,
            'description' => 'Pembatalan hingga H-7 dapat pengembalian 80%',
        ]);

        $this->destination = Destination::create([
            'partner_id' => $this->partner->id,
            'category_id' => $category->id,
            'province_id' => $this->province->id,
            'city' => 'Bantul',
            'name' => 'Desa Edukasi Budaya Mataram',
            'slug' => 'desa-edukasi-budaya-mataram',
            'short_description' => 'Pusat pembelajaran sejarah dan budaya Mataram.',
            'description' => 'Deskripsi lengkap tapak pembelajaran sejarah Mataram dengan laboratorium hidup.',
            'address' => 'Jl. Makam Raja No. 5',
            'latitude' => -7.89,
            'longitude' => 110.38,
            'status' => DestinationStatus::PUBLISHED,
            'suitable_for' => ['study_tour', 'penelitian'],
            'cover_image' => 'destinations/covers/mataram.jpg',
            'min_lead_days' => 3,
            'max_advance_days' => 180,
            'default_daily_capacity' => 100,
            'cancellation_policy_id' => $policy->id,
        ]);

        $this->package = Package::create([
            'destination_id' => $this->destination->id,
            'name' => 'Paket Jelajah Cagar Mataram',
            'slug' => 'paket-jelajah-cagar-mataram',
            'description' => 'Program 1 hari pengenalan arsitektur dan sistem tata kelola air kuno.',
            'duration_days' => 1,
            'min_pax' => 10,
            'max_pax' => 50,
            'price_per_pax' => 100000,
            'inquiry_types' => ['study_tour', 'penelitian'],
            'includes' => ['Pemandu Budaya', 'Makan Siang Tradisional', 'Lembar Kerja Siswa'],
            'excludes' => ['Transportasi Bus'],
            'is_active' => true,
        ]);
    }

    public function test_registration_form_contract_and_ui_flow(): void
    {
        Event::fake([UserRegistered::class]);

        // 1. GET registration form must render expected UI inputs
        $response = $this->get(route('register'));
        $response->assertStatus(200);
        $response->assertSee('Tipe Pengguna');
        $response->assertSee('name="account_type"', false);
        $response->assertSee('value="buyer"', false);
        $response->assertSee('value="partner"', false);
        $response->assertSee('name="password_confirmation"', false);
        $response->assertSee('name="terms"', false);

        // 2. Submit empty payload must return Indonesian validation errors
        $failResponse = $this->post(route('register'), []);
        $failResponse->assertSessionHasErrors(['name', 'email', 'password', 'account_type', 'terms']);

        // 3. Submit valid buyer registration with exact Blade field names
        $registerPayload = [
            'name' => 'Ahmad Santoso',
            'org_name' => 'SMP Budi Luhur',
            'email' => 'ahmad.santoso@destinara.test',
            'password' => 'Rahasia123!@#',
            'password_confirmation' => 'Rahasia123!@#',
            'account_type' => 'buyer',
            'phone' => '081299988877',
            'terms' => '1',
        ];

        $successResponse = $this->post(route('register'), $registerPayload);
        $successResponse->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'ahmad.santoso@destinara.test',
            'account_type' => AccountType::BUYER->value,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('institutions', [
            'institution_name' => 'SMP Budi Luhur',
        ]);

        Event::assertDispatched(UserRegistered::class);
    }

    public function test_booking_livewire_component_interactivity_and_submission(): void
    {
        $this->actingAs($this->buyer);

        // Test Livewire component
        $component = Livewire::test(BookingForm::class, ['destination' => $this->destination]);

        // 1. Initial state checks
        $component->assertSet('packageId', $this->package->id);
        $component->assertSet('participantCount', 10);
        $component->assertSet('inquiryType', 'study_tour');

        // 2. Grand total must be positive (recalculated correctly without swallowed error)
        $initialTotal = $component->get('grandTotal');
        $this->assertGreaterThan(0, $initialTotal);

        // 3. Change participant count from 10 to 25 and assert grand total recalculates
        $component->set('participantCount', 25);
        $newTotal = $component->get('grandTotal');
        $this->assertGreaterThan($initialTotal, $newTotal);

        // 4. Change inquiry type to 'penelitian' (valid intersection)
        $component->set('inquiryType', 'penelitian');
        $component->assertSet('inquiryType', 'penelitian');

        // 5. Submit the booking via Livewire
        $component->call('submit');

        // 6. Assert booking is created in database with menunggu_pembayaran
        $booking = Booking::where('user_id', $this->buyer->id)->latest()->first();
        $this->assertNotNull($booking);
        $this->assertEquals(BookingStatus::MENUNGGU_PEMBAYARAN, $booking->status);
        $this->assertEquals(25, $booking->participant_count);
        $this->assertEquals('penelitian', $booking->inquiry_type->value);
        $this->assertEquals($newTotal, (int) $booking->total_amount);

        // 7. Verify booking show page renders clean UI without JSON or errors
        $showResponse = $this->get(route('buyer.booking.show', $booking->booking_code));
        $showResponse->assertStatus(200);
        $showResponse->assertSee($booking->booking_code);
        $showResponse->assertSee($this->province->name);
        $showResponse->assertDontSee('{"id":');
        $showResponse->assertSee('Rincian Pembayaran Resmi');
    }

    public function test_destination_show_role_aware_ui_actions(): void
    {
        // 1. Guest visits destination detail
        $guestResponse = $this->get(route('destinasi.show', $this->destination->slug));
        $guestResponse->assertStatus(200);
        $guestResponse->assertSee('Desa Edukasi Budaya Mataram');
        $guestResponse->assertSee(route('login'));

        // 2. Buyer visits destination detail
        $this->actingAs($this->buyer);
        $buyerResponse = $this->get(route('destinasi.show', $this->destination->slug));
        $buyerResponse->assertStatus(200);
        $buyerResponse->assertSee('Ajukan Booking');

        // 3. Partner visits destination detail
        $this->actingAs($this->partner);
        $partnerResponse = $this->get(route('destinasi.show', $this->destination->slug));
        $partnerResponse->assertStatus(200);
        $partnerResponse->assertSee(route('partner.dashboard'));

        // 4. Admin visits destination detail
        $this->actingAs($this->admin);
        $adminResponse = $this->get(route('destinasi.show', $this->destination->slug));
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee(route('admin.dashboard'));
    }

    public function test_partner_verification_full_ui_contract_and_flow(): void
    {
        // 1. Create unverified partner
        $unverifiedPartner = User::factory()->create([
            'name' => 'Mitra Baru Mandiri',
            'email' => 'mitra.baru@desa.id',
            'account_type' => AccountType::PARTNER,
            'status' => 'active',
        ]);
        $orgType = OrganizationType::first() ?? OrganizationType::create(['name' => 'Kelompok Sadar Wisata', 'slug' => 'pokdarwis']);
        $profile = PartnerProfile::create([
            'user_id' => $unverifiedPartner->id,
            'organization_name' => 'Pokdarwis Baru Mandiri',
            'organization_type_id' => $orgType->id,
            'legal_document_number' => 'SK-MANDIRI-2026',
            'address' => 'Desa Baru, Bantul',
            'province_id' => $this->province->id,
            'city' => 'Bantul',
            'bank_id' => $this->bank->id,
            'bank_account_number' => '1122334455',
            'bank_account_holder' => 'POKDARWIS BARU MANDIRI',
            'verification_status' => VerificationStatus::UNVERIFIED,
        ]);

        $this->actingAs($unverifiedPartner);

        // Upload legal document
        $docFile = UploadedFile::fake()->create('sk_pengelola.pdf', 200, 'application/pdf');
        $uploadResponse = $this->post(route('mitra.profile.document.upload'), [
            'type' => 'sk_pengelola',
            'file' => $docFile,
            'label' => 'SK Pengelola Pokdarwis',
        ]);
        $uploadResponse->assertRedirect();
        $this->assertDatabaseHas('partner_documents', [
            'partner_profile_id' => $profile->id,
            'type' => 'sk_pengelola',
        ]);

        // Submit verification request
        $submitResponse = $this->post(route('mitra.profile.submitVerification'));
        $submitResponse->assertRedirect();
        $profile->refresh();
        $this->assertEquals(VerificationStatus::PENDING, $profile->verification_status);

        // 2. Admin logs in and reviews verification page
        $this->actingAs($this->admin);
        $adminViewResponse = $this->get(route('admin.users.verify', ['tab' => 'partners']));
        $adminViewResponse->assertStatus(200);
        // Assert action buttons and uploaded document are rendered (proves enum comparison in Blade is fixed!)
        $adminViewResponse->assertSee('Pokdarwis Baru Mandiri');
        $adminViewResponse->assertSee('MENUNGGU VALIDASI');
        $adminViewResponse->assertSee('sk_pengelola.pdf');
        $adminViewResponse->assertSee('Verifikasi Sah');

        // 3. Admin approves verification
        $approveResponse = $this->post(route('admin.users.updateVerification', [
            'type' => 'partner',
            'id' => $profile->id,
        ]), [
            'action' => 'verify',
        ]);
        $approveResponse->assertRedirect();

        $profile->refresh();
        $this->assertEquals(VerificationStatus::VERIFIED, $profile->verification_status);
    }

    public function test_admin_destination_moderation_ui_and_event_dispatch(): void
    {
        Event::fake([DestinationModerated::class]);

        // Destination with pending_review
        $pendingDestination = Destination::create([
            'partner_id' => $this->partner->id,
            'category_id' => $this->destination->category_id,
            'province_id' => $this->province->id,
            'city' => 'Bantul',
            'name' => 'Tapak Konservasi Mangrove Baros',
            'slug' => 'tapak-konservasi-mangrove-baros',
            'short_description' => 'Laboratorium konservasi pesisir dan ekosistem mangrove.',
            'description' => 'Deskripsi lengkap konservasi mangrove Baros.',
            'address' => 'Tirtohargo, Kretek, Bantul',
            'status' => DestinationStatus::PENDING_REVIEW,
            'suitable_for' => ['study_tour'],
            'cover_image' => 'destinations/covers/baros.jpg',
            'min_lead_days' => 5,
            'max_advance_days' => 120,
            'cancellation_policy_id' => $this->destination->cancellation_policy_id,
        ]);

        Package::create([
            'destination_id' => $pendingDestination->id,
            'name' => 'Paket Restorasi Mangrove',
            'slug' => 'paket-restorasi-mangrove',
            'description' => 'Edukasi dan penanaman bibit mangrove bersama nelayan.',
            'duration_days' => 1,
            'min_pax' => 15,
            'max_pax' => 40,
            'price_per_pax' => 85000,
            'inquiry_types' => ['study_tour'],
            'includes' => ['Bibit Mangrove', 'Pemandu'],
            'is_active' => true,
        ]);

        $this->actingAs($this->admin);

        // 1. Visit curation page
        $curationView = $this->get(route('admin.destinations.verify'));
        $curationView->assertStatus(200);
        // Buttons must be visible for pending_review
        $curationView->assertSee('Setujui & Terbitkan', false);
        $curationView->assertSee('Minta Revisi');
        $curationView->assertSee('Tolak Dossier');

        // 2. Submit approval
        $modResponse = $this->post(route('admin.destinations.updateStatus', $pendingDestination->id), [
            'action' => 'approve',
            'notes' => 'Silabus lapangan dan kesiapan keselamatan tapak memadai.',
        ]);
        $modResponse->assertRedirect();

        $pendingDestination->refresh();
        $this->assertEquals(DestinationStatus::PUBLISHED, $pendingDestination->status);
        Event::assertDispatched(DestinationModerated::class);
    }

    public function test_manual_payment_rejection_and_verification_ui_flow(): void
    {
        // 1. Create a booking in menunggu_pembayaran
        $booking = Booking::create([
            'booking_code' => 'DST-261014-TEST1',
            'user_id' => $this->buyer->id,
            'institution_id' => $this->institution->id,
            'destination_id' => $this->destination->id,
            'package_id' => $this->package->id,
            'status' => BookingStatus::MENUNGGU_PEMBAYARAN,
            'inquiry_type' => 'study_tour',
            'planned_date_start' => now()->addDays(14)->toDateString(),
            'planned_date_end' => now()->addDays(14)->toDateString(),
            'participant_count' => 15,
            'companion_count' => 2,
            'package_price_per_pax' => 100000,
            'package_total' => 1500000,
            'addons_total' => 0,
            'subtotal_amount' => 1500000,
            'discount_amount' => 0,
            'insurance_amount' => 45000,
            'platform_fee_amount' => 25000,
            'total_amount' => 1570000,
            'commission_percent' => 10.00,
            'commission_amount' => 150000,
            'partner_net_amount' => 1350000,
            'expires_at' => now()->addHours(24),
        ]);

        $this->actingAs($this->buyer);

        // 2. Buyer submits manual payment with proof
        $proofFile = UploadedFile::fake()->create('bukti_transfer.jpg', 500, 'image/jpeg');
        $uploadProofResponse = $this->post(route('buyer.booking.pay', $booking->booking_code), [
            'gateway' => 'manual_transfer',
            'proof_file' => $proofFile,
        ]);
        $uploadProofResponse->assertRedirect();

        $payment = Payment::where('booking_id', $booking->id)->where('gateway', 'manual_transfer')->first();
        $this->assertNotNull($payment);
        $this->assertNotNull($payment->proof_path);

        // 3. Admin visits payments page
        $this->actingAs($this->admin);
        $adminPaymentIndex = $this->get(route('admin.finance.payments'));
        $adminPaymentIndex->assertStatus(200);
        $adminPaymentIndex->assertSee('Verifikasi');
        $adminPaymentIndex->assertSee('Tolak');

        // 4. Admin rejects manual payment with reason
        $rejectResponse = $this->post(route('admin.finance.rejectManual', $payment->id), [
            'reason' => 'Nominal transfer tidak sesuai dengan invoice (kurang Rp 50.000).',
        ]);
        $rejectResponse->assertRedirect();

        $payment->refresh();
        $this->assertNull($payment->proof_path);
        $this->assertStringContainsString('kurang Rp 50.000', $payment->raw_response['rejection_reason'] ?? '');
        $this->assertNotEmpty($payment->raw_response['proof_history'] ?? []);

        // Assert notification payment.proof_rejected dispatched to buyer
        $this->assertDatabaseHas('notification_logs', [
            'event_key' => 'payment.proof_rejected',
            'channel' => 'mail',
            'recipient' => $this->buyer->email,
        ]);
        $this->assertDatabaseHas('notification_logs', [
            'event_key' => 'payment.proof_rejected',
            'channel' => 'database',
            'recipient' => (string) $this->buyer->id,
        ]);

        // 5. Buyer visits booking show page and sees rejection reason and re-upload button
        $this->actingAs($this->buyer);
        $buyerAfterReject = $this->get(route('buyer.booking.show', $booking->booking_code));
        $buyerAfterReject->assertStatus(200);
        $buyerAfterReject->assertSee('kurang Rp 50.000');
        $buyerAfterReject->assertSee('Unggah Ulang Bukti');

        // 6. Buyer re-uploads valid proof
        $newProof = UploadedFile::fake()->create('bukti_baru.jpg', 500, 'image/jpeg');
        $this->post(route('buyer.booking.reuploadProof', $booking->booking_code), [
            'proof_file' => $newProof,
        ])->assertRedirect();

        // 7. Admin verifies manual payment
        $this->actingAs($this->admin);
        $verifyResponse = $this->post(route('admin.finance.verifyManual', $payment->id));
        $verifyResponse->assertRedirect();

        $booking->refresh();
        $payment->refresh();
        $this->assertEquals(BookingStatus::DIBAYAR, $booking->status);
        $this->assertEquals(PaymentStatus::PAID, $payment->status);
    }

    public function test_settings_separated_tabs_ui_contract(): void
    {
        $this->actingAs($this->admin);

        // 1. Visit settings page
        $response = $this->get(route('admin.settings.index'));
        $response->assertStatus(200);
        $response->assertSee('Pengaturan Ekosistem');

        // 2. Submit Payment Settings tab
        $paymentPayload = [
            'group' => 'payment',
            'enabled_gateways' => ['manual_transfer', 'xendit'],
            'min_payment_amount' => 15000,
            'manual_transfer_accounts' => [
                [
                    'bank' => 'BCA',
                    'number' => '8880001122',
                    'holder' => 'PT DESTINARA EDUKASI NUSANTARA',
                ],
                [
                    'bank' => 'Mandiri',
                    'number' => '1370099887766',
                    'holder' => 'PT DESTINARA EDUKASI NUSANTARA',
                ],
            ],
        ];
        $gwResponse = $this->post(route('admin.settings.update'), $paymentPayload);
        $gwResponse->assertRedirect();
        $gwResponse->assertSessionHas('success');

        // 3. Submit General tab
        $generalPayload = [
            'group' => 'general',
            'company_legal_name' => 'PT Destinara Cakrawala Edukasi',
            'company_address' => 'Gedung Pusat Inovasi Budaya Lt. 3, Yogyakarta',
            'company_phone' => '0274-555667',
        ];
        $genResponse = $this->post(route('admin.settings.update'), $generalPayload);
        $genResponse->assertRedirect();
        $genResponse->assertSessionHas('success');

        // 4. Buyer accessing admin settings is redirected with error
        $this->actingAs($this->buyer);
        $buyerRedirect = $this->get(route('admin.settings.index'));
        $buyerRedirect->assertRedirect(route('buyer.dashboard'));
        $buyerRedirect->assertSessionHas('error');

        // 5. Internal user without permission gets 403
        $staffUser = User::factory()->create([
            'account_type' => AccountType::INTERNAL,
            'status' => 'active',
        ]);
        $this->actingAs($staffUser);
        $staffForbidden = $this->get(route('admin.settings.index'));
        $staffForbidden->assertStatus(403);
    }

    public function test_partner_registration_via_form_payload_dispatches_email_verification_log(): void
    {
        $partnerPayload = [
            'name' => 'Bambang Sutrisno',
            'org_name' => 'Pokdarwis Watu Ireng',
            'email' => 'bambang.mitra@watuireng.id',
            'phone' => '081234567811',
            'password' => 'Rahasia123!@#',
            'password_confirmation' => 'Rahasia123!@#',
            'account_type' => 'partner',
            'terms' => '1',
        ];

        $response = $this->post(route('register'), $partnerPayload);
        $response->assertRedirect(route('verification.notice'));

        $this->assertDatabaseHas('users', [
            'email' => 'bambang.mitra@watuireng.id',
            'account_type' => AccountType::PARTNER->value,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('partner_profiles', [
            'organization_name' => 'Pokdarwis Watu Ireng',
            'verification_status' => VerificationStatus::UNVERIFIED->value,
        ]);

        // Assert notification log auth.verify_email to partner
        $this->assertDatabaseHas('notification_logs', [
            'event_key' => 'auth.verify_email',
            'channel' => 'mail',
            'recipient' => 'bambang.mitra@watuireng.id',
        ]);
    }

    public function test_xendit_online_payment_flow_redirects_to_invoice_url(): void
    {
        $repo = app(SettingsRepository::class);
        $repo->set('xendit_secret_key', 'xnd_development_fake_secret_key');
        $repo->set('xendit_callback_token', 'fake_callback_token_123');
        $repo->set('enabled_gateways', ['xendit', 'manual_transfer']);

        $booking = Booking::create([
            'booking_code' => 'DST-261014-XENDIT',
            'user_id' => $this->buyer->id,
            'institution_id' => $this->institution->id,
            'destination_id' => $this->destination->id,
            'package_id' => $this->package->id,
            'status' => BookingStatus::MENUNGGU_PEMBAYARAN,
            'inquiry_type' => 'study_tour',
            'planned_date_start' => now()->addDays(14)->toDateString(),
            'planned_date_end' => now()->addDays(14)->toDateString(),
            'participant_count' => 10,
            'companion_count' => 1,
            'package_price_per_pax' => 100000,
            'package_total' => 1000000,
            'addons_total' => 0,
            'subtotal_amount' => 1000000,
            'discount_amount' => 0,
            'insurance_amount' => 30000,
            'platform_fee_amount' => 25000,
            'total_amount' => 1055000,
            'commission_percent' => 10.00,
            'commission_amount' => 100000,
            'partner_net_amount' => 900000,
            'expires_at' => now()->addHours(24),
        ]);

        Http::fake([
            'https://api.xendit.co/v2/invoices' => Http::response([
                'id' => 'xnd_inv_uiflow_789',
                'external_id' => $booking->booking_code . '-P1',
                'invoice_url' => 'https://checkout.xendit.co/web/xnd_inv_uiflow_789',
                'status' => 'PENDING',
                'expiry_date' => now()->addHours(24)->toIso8601String(),
            ], 200),
        ]);

        $this->actingAs($this->buyer);

        $payResponse = $this->post(route('buyer.booking.pay', $booking->booking_code), [
            'gateway' => 'xendit',
        ]);

        $payResponse->assertRedirect('https://checkout.xendit.co/web/xnd_inv_uiflow_789');

        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'gateway' => 'xendit',
            'checkout_url' => 'https://checkout.xendit.co/web/xnd_inv_uiflow_789',
            'status' => PaymentStatus::PENDING->value,
        ]);
    }

    public function test_completed_booking_review_submission_via_form_payload(): void
    {
        $completedBooking = Booking::create([
            'booking_code' => 'DST-261014-REV01',
            'user_id' => $this->buyer->id,
            'institution_id' => $this->institution->id,
            'destination_id' => $this->destination->id,
            'package_id' => $this->package->id,
            'status' => BookingStatus::SELESAI,
            'inquiry_type' => 'study_tour',
            'planned_date_start' => now()->subDays(5)->toDateString(),
            'planned_date_end' => now()->subDays(4)->toDateString(),
            'completed_at' => now()->subDays(4),
            'participant_count' => 20,
            'companion_count' => 2,
            'package_price_per_pax' => 100000,
            'package_total' => 2000000,
            'addons_total' => 0,
            'subtotal_amount' => 2000000,
            'discount_amount' => 0,
            'insurance_amount' => 60000,
            'platform_fee_amount' => 25000,
            'total_amount' => 2085000,
            'commission_percent' => 10.00,
            'commission_amount' => 200000,
            'partner_net_amount' => 1800000,
        ]);

        $this->actingAs($this->buyer);

        $reviewPayload = [
            'rating' => 5,
            'comment' => 'Program kunjungan edukasi budaya sangat inspiratif dan terkelola secara profesional.',
        ];

        $reviewResponse = $this->post(route('buyer.booking.review', $completedBooking->booking_code), $reviewPayload);
        $reviewResponse->assertRedirect(route('buyer.booking.show', $completedBooking->booking_code));
        $reviewResponse->assertSessionHas('success');

        $this->assertDatabaseHas('reviews', [
            'booking_id' => $completedBooking->id,
            'destination_id' => $this->destination->id,
            'institution_id' => $this->institution->id,
            'rating' => 5,
            'comment' => 'Program kunjungan edukasi budaya sangat inspiratif dan terkelola secara profesional.',
            'status' => 'published',
        ]);
    }

    public function test_admin_refund_modal_processing_flow(): void
    {
        $cancelledBooking = Booking::create([
            'booking_code' => 'DST-261014-REF01',
            'user_id' => $this->buyer->id,
            'institution_id' => $this->institution->id,
            'destination_id' => $this->destination->id,
            'package_id' => $this->package->id,
            'status' => BookingStatus::BATAL,
            'inquiry_type' => 'study_tour',
            'planned_date_start' => now()->addDays(10)->toDateString(),
            'planned_date_end' => now()->addDays(10)->toDateString(),
            'participant_count' => 15,
            'companion_count' => 2,
            'package_price_per_pax' => 100000,
            'package_total' => 1500000,
            'addons_total' => 0,
            'subtotal_amount' => 1500000,
            'discount_amount' => 0,
            'insurance_amount' => 45000,
            'platform_fee_amount' => 25000,
            'total_amount' => 1570000,
            'commission_percent' => 10.00,
            'commission_amount' => 150000,
            'partner_net_amount' => 1350000,
        ]);

        $refund = Refund::create([
            'refund_number' => 'REF-UIFLOW-01',
            'booking_id' => $cancelledBooking->id,
            'user_id' => $this->buyer->id,
            'amount' => 1200000,
            'percent' => 80,
            'reason' => 'Pembatalan sebelum H-7 sesuai kebijakan fleksibel tapak',
            'status' => RefundStatus::PENDING,
            'buyer_bank_id' => $this->bank->id,
            'buyer_account_number' => '1234567890',
            'buyer_account_holder' => 'SMA NEGERI 1 EDUKASI',
        ]);

        $this->actingAs($this->admin);

        $proofFile = UploadedFile::fake()->create('bukti_transfer_refund.pdf', 250, 'application/pdf');
        $processPayload = [
            'proof_file' => $proofFile,
            'note' => 'Refund 80% berhasil ditransfer ke rekening sekolah BCA.',
        ];

        $response = $this->post(route('admin.finance.processRefund', $refund->id), $processPayload);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $refund->refresh();
        $this->assertEquals(RefundStatus::COMPLETED, $refund->status);
        $this->assertNotNull($refund->proof_path);
        $this->assertEquals('Refund 80% berhasil ditransfer ke rekening sekolah BCA.', $refund->note);
    }

    public function test_admin_booking_reschedule_modal_flow(): void
    {
        $booking = Booking::create([
            'booking_code' => 'DST-261014-RESCH01',
            'user_id' => $this->buyer->id,
            'institution_id' => $this->institution->id,
            'destination_id' => $this->destination->id,
            'package_id' => $this->package->id,
            'status' => BookingStatus::DIKONFIRMASI,
            'inquiry_type' => 'study_tour',
            'planned_date_start' => now()->addDays(10)->toDateString(),
            'planned_date_end' => now()->addDays(10)->toDateString(),
            'participant_count' => 20,
            'companion_count' => 2,
            'package_price_per_pax' => 100000,
            'package_total' => 2000000,
            'addons_total' => 0,
            'subtotal_amount' => 2000000,
            'discount_amount' => 0,
            'insurance_amount' => 60000,
            'platform_fee_amount' => 25000,
            'total_amount' => 2085000,
            'commission_percent' => 10.00,
            'commission_amount' => 200000,
            'partner_net_amount' => 1800000,
        ]);

        $this->actingAs($this->admin);

        $newDate = now()->addDays(25)->toDateString();
        $reschedulePayload = [
            'action' => 'reschedule',
            'new_date' => $newDate,
            'reschedule_reason' => 'Penyesuaian kalender pendidikan dari Dinas Pendidikan.',
        ];

        $response = $this->post(route('admin.bookings.updateStatus', $booking->id), $reschedulePayload);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $booking->refresh();
        $this->assertEquals($newDate, $booking->planned_date_start->toDateString());
    }
}
