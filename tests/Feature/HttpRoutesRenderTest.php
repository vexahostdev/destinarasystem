<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\BookingStatus;
use App\Enums\DestinationStatus;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\PaymentStatus;
use App\Enums\PayoutStatus;
use App\Enums\RefundStatus;
use App\Enums\ReviewStatus;
use App\Models\AuditLog;
use App\Models\AvailabilitySlot;
use App\Models\Bank;
use App\Models\Booking;
use App\Models\CancellationPolicy;
use App\Models\Destination;
use App\Models\DestinationCategory;
use App\Models\DestinationContact;
use App\Models\Document;
use App\Models\DocumentTemplate;
use App\Models\Facility;
use App\Models\Institution;
use App\Models\Package;
use App\Models\PartnerPayout;
use App\Models\PartnerProfile;
use App\Models\Payment;
use App\Models\Province;
use App\Models\Refund;
use App\Models\Review;
use App\Models\User;
use App\Models\Voucher;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class HttpRoutesRenderTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $partner;

    protected User $buyer;

    protected Destination $publishedDestination;

    protected Destination $pendingDestination;

    protected Destination $draftDestination;

    protected Package $package;

    protected Bank $bank;

    protected Document $sampleDocument;

    protected DocumentTemplate $sampleTemplate;

    protected AuditLog $sampleAuditLog;

    /** @var array<string, Booking> */
    protected array $statusBookings = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('local');

        $this->bank = Bank::firstOrCreate(
            ['code' => 'BMRI'],
            [
                'name' => 'Bank Mandiri',
                'account_number' => '1370012345678',
                'account_holder' => 'PT Destinara Chakrawal Artha',
                'is_active' => true,
            ]
        );

        $province = Province::first() ?? Province::create(['name' => 'DI Yogyakarta']);
        $category = DestinationCategory::first() ?? DestinationCategory::create([
            'name' => 'Budaya & Sejarah',
            'slug' => 'budaya-sejarah',
            'is_active' => true,
        ]);
        $policy = CancellationPolicy::first() ?? CancellationPolicy::create([
            'name' => 'Fleksibel',
            'description' => 'Pembatalan fleksibel',
            'is_active' => true,
        ]);

        // 1. Setup Admin with full permissions
        $this->admin = User::factory()->create([
            'name' => 'Super Administrator',
            'email' => 'admin_test@destinara.test',
            'account_type' => AccountType::INTERNAL,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->admin->givePermissionTo(Permission::all());

        // 2. Setup Verified Partner
        $this->partner = User::factory()->create([
            'name' => 'Mitra Pengelola Tapak',
            'email' => 'partner_test@destinara.test',
            'phone' => '6281234567890',
            'account_type' => AccountType::PARTNER,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        PartnerProfile::create([
            'user_id' => $this->partner->id,
            'organization_name' => 'Yayasan Konservasi Candi Kedulan',
            'business_type' => 'yayasan',
            'verification_status' => 'verified',
            'verified_at' => now(),
            'bank_id' => $this->bank->id,
            'bank_account_number' => '9876543210',
            'bank_account_holder' => 'Yayasan Candi Kedulan',
        ]);

        // 3. Setup Verified Buyer
        $this->buyer = User::factory()->create([
            'name' => 'Drs. Bambang Sudarsono',
            'email' => 'buyer_test@destinara.test',
            'phone' => '6289876543210',
            'account_type' => AccountType::BUYER,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        Institution::create([
            'user_id' => $this->buyer->id,
            'institution_name' => 'SMA Negeri 1 Yogyakarta',
            'institution_type' => 'sma',
            'verification_status' => 'verified',
            'verified_at' => now(),
            'pic_name' => 'Drs. Bambang Sudarsono',
            'pic_phone' => '6289876543210',
            'pic_email' => 'buyer_test@destinara.test',
            'pic_position' => 'Wakil Kepala Sekolah Bidang Kesiswaan',
            'address' => 'Jl. C. Simanjuntak No. 2',
            'city' => 'Yogyakarta',
            'province' => 'DI Yogyakarta',
        ]);

        // 4. Create Destinasi: Published, Pending Review, Draft
        $this->publishedDestination = Destination::create([
            'partner_id' => $this->partner->id,
            'category_id' => $category->id,
            'province_id' => $province->id,
            'cancellation_policy_id' => $policy->id,
            'name' => 'Desa Wisata Arkeologi Candi Kedulan',
            'slug' => 'desa-wisata-arkeologi-candi-kedulan',
            'city' => 'Sleman',
            'address' => 'Tirtomartani, Kalasan, Sleman, DIY',
            'short_description' => 'Destinasi tapak purbakala dan pembelajaran geologi bawah tanah.',
            'description' => 'Penjelasan komprehensif mengenai candi dan lapisan sedimentasi merapi purba.',
            'default_daily_capacity' => 150,
            'status' => DestinationStatus::PUBLISHED,
            'is_active' => true,
            'is_featured' => true,
        ]);

        $this->pendingDestination = Destination::create([
            'partner_id' => $this->partner->id,
            'category_id' => $category->id,
            'province_id' => $province->id,
            'cancellation_policy_id' => $policy->id,
            'name' => 'Pusat Observasi Vulkanologi Merapi',
            'slug' => 'pusat-observasi-vulkanologi-merapi',
            'city' => 'Sleman',
            'address' => 'Kaliurang, Pakem, Sleman, DIY',
            'short_description' => 'Laboratorium alam dinamika kubah lava dan mitigasi erupsi.',
            'description' => 'Penelitian vulkanologi interaktif untuk mahasiswa geofisika dan siswa sains.',
            'default_daily_capacity' => 100,
            'status' => DestinationStatus::PENDING_REVIEW,
            'is_active' => false,
            'is_featured' => false,
        ]);

        $this->draftDestination = Destination::create([
            'partner_id' => $this->partner->id,
            'category_id' => $category->id,
            'province_id' => $province->id,
            'cancellation_policy_id' => $policy->id,
            'name' => 'Museum Maritim Samudera Hindia',
            'slug' => 'museum-maritim-samudera-hindia',
            'city' => 'Bantul',
            'address' => 'Parangtritis, Kretek, Bantul, DIY',
            'short_description' => 'Kajian oseanografi dan keanekaragaman hayati pesisir.',
            'description' => 'Ekosistem muara sungai Opak dan konservasi mangrove.',
            'default_daily_capacity' => 80,
            'status' => DestinationStatus::DRAFT,
            'is_active' => false,
            'is_featured' => false,
        ]);

        // Packages, slots, facilities, contacts for published & pending
        foreach ([$this->publishedDestination, $this->pendingDestination] as $dest) {
            $pkg = Package::create([
                'destination_id' => $dest->id,
                'name' => 'Paket Ekskursi Geologi & Budaya',
                'slug' => 'paket-ekskursi-geologi-budaya-'.$dest->id,
                'description' => 'Tur lapangan 2 hari dengan modul ajar resmi.',
                'price_per_pax' => 200000,
                'min_pax' => 10,
                'max_pax' => 100,
                'duration_days' => 2,
                'inquiry_types' => ['study_tour', 'penelitian'],
                'includes' => ['Pemandu bersertifikat', 'Tiket masuk situs', 'Modul'],
                'excludes' => ['Akomodasi hotel'],
                'is_active' => true,
            ]);

            AvailabilitySlot::create([
                'destination_id' => $dest->id,
                'date' => now()->addDays(5)->toDateString(),
                'capacity' => 100,
                'held_count' => 0,
                'booked_count' => 0,
                'status' => 'available',
            ]);

            DestinationContact::create([
                'destination_id' => $dest->id,
                'contact_name' => 'PIC Edukasi Tapak',
                'role' => 'Koordinator Edukasi',
                'phone' => '6281234567890',
                'email' => 'edukasi@kedulan.test',
                'is_primary' => true,
            ]);
        }

        $facility = Facility::first() ?? Facility::create([
            'name' => 'Mushola Bersih',
            'icon' => 'mosque',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $this->publishedDestination->facilities()->sync([$facility->id]);

        $this->package = $this->publishedDestination->packages()->first();

        // 5. Create 1 Booking for each BookingStatus
        $statuses = [
            BookingStatus::MENUNGGU_PEMBAYARAN,
            BookingStatus::DIBAYAR,
            BookingStatus::DIKONFIRMASI,
            BookingStatus::BERLANGSUNG,
            BookingStatus::SELESAI,
            BookingStatus::BATAL,
            BookingStatus::KEDALUWARSA,
        ];

        foreach ($statuses as $idx => $status) {
            $code = 'BK-TEST-'.strtoupper(substr($status->value, 0, 4)).'-'.($idx + 1);
            $booking = Booking::create([
                'booking_code' => $code,
                'user_id' => $this->buyer->id,
                'destination_id' => $this->publishedDestination->id,
                'package_id' => $this->package->id,
                'institution_id' => $this->buyer->institution->id,
                'inquiry_type' => 'study_tour',
                'planned_date_start' => now()->addDays(10 + $idx)->toDateString(),
                'planned_date_end' => now()->addDays(11 + $idx)->toDateString(),
                'participant_count' => 25,
                'subtotal_amount' => 5000000,
                'addons_amount' => 0,
                'insurance_amount' => 250000,
                'platform_fee_amount' => 5000,
                'discount_amount' => 0,
                'total_amount' => 5255000,
                'commission_percent' => 10.00,
                'commission_amount' => 500000,
                'partner_net_amount' => 4500000,
                'status' => $status,
                'expires_at' => now()->addHours(24),
                'paid_at' => in_array($status, [BookingStatus::DIBAYAR, BookingStatus::DIKONFIRMASI, BookingStatus::BERLANGSUNG, BookingStatus::SELESAI]) ? now() : null,
                'confirmed_at' => in_array($status, [BookingStatus::DIKONFIRMASI, BookingStatus::BERLANGSUNG, BookingStatus::SELESAI]) ? now() : null,
                'cancelled_at' => $status === BookingStatus::BATAL ? now() : null,
                'cancel_reason' => $status === BookingStatus::BATAL ? 'Pembatalan darurat instansi' : null,
            ]);

            // Create associated Payment
            Payment::create([
                'booking_id' => $booking->id,
                'external_id' => 'PAY-'.$booking->booking_code,
                'gateway' => 'manual_transfer',
                'amount' => $booking->total_amount,
                'currency' => 'IDR',
                'status' => in_array($status, [BookingStatus::MENUNGGU_PEMBAYARAN, BookingStatus::BATAL, BookingStatus::KEDALUWARSA]) ? PaymentStatus::PENDING : PaymentStatus::PAID,
                'bank_id' => $this->bank->id,
                'bank_name' => $this->bank->name,
                'account_number' => $this->bank->account_number,
                'account_holder' => $this->bank->account_holder,
            ]);

            $this->statusBookings[$status->value] = $booking;
        }

        // 6. Create Refunds, Payouts, Documents, Vouchers, AuditLogs, Reviews
        $completedBooking = $this->statusBookings[BookingStatus::SELESAI->value];
        $cancelledBooking = $this->statusBookings[BookingStatus::BATAL->value];

        // Refund
        Refund::create([
            'booking_id' => $cancelledBooking->id,
            'payment_id' => $cancelledBooking->payments()->first()?->id,
            'amount' => $cancelledBooking->total_amount,
            'percent' => 100,
            'reason' => 'Pembatalan siswa berhalangan hadir',
            'buyer_bank_id' => $this->bank->id,
            'buyer_account_number' => '1370098765432',
            'buyer_account_holder' => 'SMA Negeri 1 Yogyakarta',
            'status' => RefundStatus::PENDING,
        ]);

        // Payout
        PartnerPayout::create([
            'partner_id' => $this->partner->id,
            'booking_id' => $completedBooking->id,
            'payout_number' => 'PO-2026-TEST-001',
            'gross_amount' => $completedBooking->total_amount,
            'commission_amount' => $completedBooking->commission_amount,
            'net_amount' => $completedBooking->partner_net_amount,
            'bank_name' => 'Bank Mandiri',
            'bank_account_number' => '9876543210',
            'bank_account_holder' => 'Yayasan Candi Kedulan',
            'eligible_at' => now(),
            'status' => PayoutStatus::PENDING,
        ]);

        // Document record + physical fake file
        $filePath = 'documents/2026/10/invoice_INV_TEST_001_hash.pdf';
        Storage::disk('local')->put($filePath, '%PDF-1.4 sample fake content');

        $this->sampleDocument = Document::create([
            'booking_id' => $completedBooking->id,
            'type' => DocumentType::INVOICE,
            'document_number' => 'INV/2026/10/001',
            'title' => 'Invoice Pemesanan Paket Edukasi',
            'file_path' => $filePath,
            'verification_hash' => 'sample_hash_verification_123',
            'status' => DocumentStatus::FINAL,
            'generated_at' => now(),
            'version' => 1,
        ]);

        $this->sampleTemplate = DocumentTemplate::firstOrCreate(
            ['type' => DocumentType::INVOICE->value],
            [
                'title' => 'Template Invoice Resmi',
                'body' => '<p>Invoice untuk {{booking.code}}</p>',
            ]
        );

        // Voucher
        Voucher::create([
            'code' => 'EDUKASI2026',
            'name' => 'Voucher Diskon Sekolah',
            'type' => 'fixed',
            'value' => 50000,
            'min_transaction' => 1000000,
            'quota' => 100,
            'used_count' => 0,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'is_active' => true,
        ]);

        // Audit Log
        $this->sampleAuditLog = AuditLog::create([
            'user_id' => $this->admin->id,
            'action' => 'test_audit_action',
            'target_type' => Destination::class,
            'target_id' => $this->publishedDestination->id,
            'description' => 'Pengujian entri audit log sistem.',
            'ip_address' => '127.0.0.1',
        ]);

        // Review
        Review::create([
            'booking_id' => $completedBooking->id,
            'institution_id' => $this->buyer->institution->id,
            'destination_id' => $this->publishedDestination->id,
            'rating' => 5,
            'comment' => 'Kunjungan edukasi yang sangat memuaskan dan berbobot akademis.',
            'status' => ReviewStatus::PUBLISHED,
        ]);

        // Wishlist
        Wishlist::create([
            'user_id' => $this->buyer->id,
            'destination_id' => $this->publishedDestination->id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | A. Admin GET Routes Render (Status 200, No Exceptions)
    |--------------------------------------------------------------------------
    */

    public function test_admin_dashboard_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $response->assertStatus(200);
    }

    public function test_admin_destinations_index_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.destinations.index'));
        $response->assertStatus(200);
    }

    public function test_admin_destinations_create_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.destinations.create'));
        $response->assertStatus(200);
    }

    public function test_admin_destinations_edit_renders_with_pending_review_and_published(): void
    {
        // 1. Pending review destination
        $resPending = $this->actingAs($this->admin)->get(route('admin.destinations.edit', $this->pendingDestination));
        $resPending->assertStatus(200);

        // 2. Published destination
        $resPublished = $this->actingAs($this->admin)->get(route('admin.destinations.edit', $this->publishedDestination));
        $resPublished->assertStatus(200);
    }

    public function test_admin_destinations_verify_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.destinations.verify'));
        $response->assertStatus(200);
    }

    public function test_admin_users_verify_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users.verify'));
        $response->assertStatus(200);
    }

    public function test_admin_users_index_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users.index'));
        $response->assertStatus(200);
    }

    public function test_admin_bookings_index_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.bookings.index'));
        $response->assertStatus(200);
    }

    public function test_admin_bookings_show_renders_for_every_booking_status(): void
    {
        foreach ($this->statusBookings as $status => $booking) {
            $response = $this->actingAs($this->admin)->get(route('admin.bookings.show', $booking->booking_code));
            $response->assertStatus(200);
        }
    }

    public function test_admin_finance_payments_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.finance.payments'));
        $response->assertStatus(200);
    }

    public function test_admin_finance_refunds_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.finance.refunds'));
        $response->assertStatus(200);
    }

    public function test_admin_finance_payouts_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.finance.payouts'));
        $response->assertStatus(200);
    }

    public function test_admin_finance_reconciliation_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.finance.reconciliation'));
        $response->assertStatus(200);
    }

    public function test_admin_finance_export_csv_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.finance.exportCsv'));
        $response->assertStatus(200);
        $this->assertEquals('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));
    }

    public function test_admin_documents_index_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.documents.index'));
        $response->assertStatus(200);
    }

    public function test_admin_documents_preview_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.documents.preview', $this->sampleDocument));
        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_admin_documents_templates_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.documents.templates'));
        $response->assertStatus(200);
    }

    public function test_admin_documents_templates_preview_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.documents.templates.preview', $this->sampleTemplate));
        $response->assertStatus(200);
    }

    public function test_admin_reviews_index_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.reviews.index'));
        $response->assertStatus(200);
    }

    public function test_admin_vouchers_index_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.vouchers.index'));
        $response->assertStatus(200);
    }

    public function test_admin_notifications_index_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.notifications.index'));
        $response->assertStatus(200);
    }

    public function test_admin_notifications_templates_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.notifications.templates'));
        $response->assertStatus(200);
    }

    public function test_admin_master_index_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.master.index'));
        $response->assertStatus(200);
    }

    public function test_admin_site_contents_index_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.siteContents.index'));
        $response->assertStatus(200);
    }

    public function test_admin_team_index_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.team.index'));
        $response->assertStatus(200);
    }

    public function test_admin_settings_index_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.settings.index'));
        $response->assertStatus(200);
    }

    public function test_admin_audit_logs_index_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.auditLogs.index'));
        $response->assertStatus(200);
    }

    public function test_admin_audit_logs_show_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.auditLogs.show', $this->sampleAuditLog));
        $response->assertStatus(200);
    }

    public function test_admin_analytics_index_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.analytics.index'));
        $response->assertStatus(200);
    }

    public function test_admin_analytics_export_csv_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.analytics.exportCsv'));
        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_admin_health_index_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.health.index'));
        $response->assertStatus(200);
    }

    /*
    |--------------------------------------------------------------------------
    | B. Partner (Mitra) GET Routes Render (Status 200, No Exceptions)
    |--------------------------------------------------------------------------
    */

    public function test_partner_dashboard_renders_successfully(): void
    {
        $response = $this->actingAs($this->partner)->get(route('mitra.dashboard'));
        $response->assertStatus(200);
    }

    public function test_partner_destinations_renders_successfully(): void
    {
        $response = $this->actingAs($this->partner)->get(route('mitra.destinations'));
        $response->assertStatus(200);
    }

    public function test_partner_destinations_create_renders_successfully(): void
    {
        $response = $this->actingAs($this->partner)->get(route('mitra.destinations.create'));
        $response->assertStatus(200);
    }

    public function test_partner_destinations_edit_renders_successfully(): void
    {
        $response = $this->actingAs($this->partner)->get(route('mitra.destinations.edit', $this->publishedDestination));
        $response->assertStatus(200);
    }

    public function test_partner_availability_renders_successfully(): void
    {
        $response = $this->actingAs($this->partner)->get(route('mitra.availability'));
        $response->assertStatus(200);
    }

    public function test_partner_bookings_renders_successfully(): void
    {
        $response = $this->actingAs($this->partner)->get(route('mitra.bookings'));
        $response->assertStatus(200);
    }

    public function test_partner_income_renders_successfully(): void
    {
        $response = $this->actingAs($this->partner)->get(route('mitra.income'));
        $response->assertStatus(200);
    }

    public function test_partner_profile_renders_successfully(): void
    {
        $response = $this->actingAs($this->partner)->get(route('mitra.profile'));
        $response->assertStatus(200);
    }

    public function test_partner_notifications_renders_successfully(): void
    {
        $response = $this->actingAs($this->partner)->get(route('mitra.notifications'));
        $response->assertStatus(200);
    }

    public function test_partner_checkin_show_renders_with_signed_url(): void
    {
        $confirmedBooking = $this->statusBookings[BookingStatus::DIKONFIRMASI->value];
        $signedUrl = URL::signedRoute('mitra.checkin.show', ['code' => $confirmedBooking->booking_code]);

        $response = $this->actingAs($this->partner)->get($signedUrl);
        $response->assertStatus(200);
    }

    /*
    |--------------------------------------------------------------------------
    | C. Buyer (Pembeli/Institusi) GET Routes Render (Status 200, No Exceptions)
    |--------------------------------------------------------------------------
    */

    public function test_buyer_dashboard_renders_successfully(): void
    {
        $response = $this->actingAs($this->buyer)->get(route('buyer.dashboard'));
        $response->assertStatus(200);
    }

    public function test_buyer_booking_create_renders_successfully(): void
    {
        $response = $this->actingAs($this->buyer)->get(route('buyer.booking.create'));
        $response->assertStatus(200);
    }

    public function test_buyer_booking_show_renders_for_every_booking_status(): void
    {
        foreach ($this->statusBookings as $status => $booking) {
            $response = $this->actingAs($this->buyer)->get(route('buyer.booking.show', $booking->booking_code));
            $response->assertStatus(200);
        }
    }

    public function test_buyer_booking_payment_renders_for_unpaid_booking(): void
    {
        app(\App\Domain\Settings\SettingsRepository::class)->set('manual_transfer_accounts', [
            ['bank' => 'Bank Mandiri', 'number' => '1370012345678', 'holder' => 'PT Destinara Chakrawal Artha'],
        ]);

        $unpaidBooking = $this->statusBookings[BookingStatus::MENUNGGU_PEMBAYARAN->value];
        $response = $this->actingAs($this->buyer)->get(route('buyer.booking.payment', $unpaidBooking->booking_code));
        $response->assertStatus(200);
    }

    public function test_buyer_history_renders_successfully(): void
    {
        $response = $this->actingAs($this->buyer)->get(route('buyer.history'));
        $response->assertStatus(200);
    }

    public function test_buyer_profile_renders_successfully(): void
    {
        $response = $this->actingAs($this->buyer)->get(route('buyer.profile'));
        $response->assertStatus(200);
    }

    public function test_buyer_wishlist_renders_successfully(): void
    {
        $response = $this->actingAs($this->buyer)->get(route('buyer.wishlist'));
        $response->assertStatus(200);
    }

    public function test_buyer_notifications_renders_successfully(): void
    {
        $response = $this->actingAs($this->buyer)->get(route('buyer.notifications'));
        $response->assertStatus(200);
    }

    /*
    |--------------------------------------------------------------------------
    | D. Public GET Routes Render (Status 200, No Exceptions)
    |--------------------------------------------------------------------------
    */

    public function test_public_catalog_and_content_pages_render_successfully(): void
    {
        $this->get(route('destinasi.index'))->assertStatus(200);
        $this->get(route('destinasi.show', $this->publishedDestination->slug))->assertStatus(200);
        $this->get(route('cara-kerja'))->assertStatus(200);
        $this->get(route('faq'))->assertStatus(200);
        $this->get(route('terms'))->assertStatus(200);
        $this->get(route('register'))->assertStatus(200);
        $this->get(route('login'))->assertStatus(200);
        $this->get(route('password.request'))->assertStatus(200);
        $this->get(route('verification.document', $this->sampleDocument->verification_hash))->assertStatus(200);
    }
}
