<?php

namespace Tests\Feature;

use App\Domain\Document\Services\DocumentNumberGenerator;
use App\Domain\Document\Services\DocumentService;
use App\Enums\AccountType;
use App\Enums\BookingStatus;
use App\Enums\DestinationStatus;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\VerificationStatus;
use App\Models\Booking;
use App\Models\CancellationPolicy;
use App\Models\Destination;
use App\Models\DestinationCategory;
use App\Models\Document;
use App\Models\Institution;
use App\Models\Package;
use App\Models\PartnerProfile;
use App\Models\Province;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    protected User $buyer;

    protected User $unauthorizedBuyer;

    protected User $partnerUser;

    protected User $adminUser;

    protected Destination $destination;

    protected Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('local');

        $this->buyer = User::factory()->create([
            'account_type' => AccountType::BUYER,
            'status' => 'active',
        ]);

        $this->unauthorizedBuyer = User::factory()->create([
            'account_type' => AccountType::BUYER,
            'status' => 'active',
        ]);

        Institution::create([
            'user_id' => $this->buyer->id,
            'institution_name' => 'SMA Kolese Loyola',
            'pic_name' => 'Pak Petrus',
            'pic_phone' => '6281234567890',
            'pic_email' => 'petrus@loyola.sch.id',
        ]);

        $this->partnerUser = User::factory()->create([
            'account_type' => AccountType::PARTNER,
            'status' => 'active',
        ]);

        PartnerProfile::create([
            'user_id' => $this->partnerUser->id,
            'organization_name' => 'Pengelola Hutan Pinus Mangunan',
            'business_type' => 'pokdarwis',
            'pic_name' => 'Pak Joko',
            'pic_phone' => '6281234567891',
            'pic_email' => 'joko@pinus.test',
            'address' => 'Mangunan, Dlingo',
            'city' => 'Bantul',
            'province' => 'DI Yogyakarta',
            'verification_status' => VerificationStatus::VERIFIED,
        ]);

        $this->adminUser = User::factory()->create([
            'account_type' => AccountType::INTERNAL,
            'status' => 'active',
        ]);

        $province = Province::first();
        $policy = CancellationPolicy::first();
        $category = DestinationCategory::firstOrCreate(['slug' => 'ekowisata'], ['name' => 'Ekowisata', 'is_active' => true]);

        $this->destination = Destination::create([
            'partner_id' => $this->partnerUser->id,
            'category_id' => $category->id,
            'province_id' => $province->id,
            'cancellation_policy_id' => $policy->id,
            'name' => 'Hutan Pinus Mangunan',
            'slug' => 'hutan-pinus-mangunan',
            'city' => 'Bantul',
            'address' => 'Mangunan, Dlingo',
            'description' => 'Ekowisata Hutan Pinus.',
            'status' => DestinationStatus::PUBLISHED,
            'default_daily_capacity' => 100,
        ]);

        $package = Package::create([
            'destination_id' => $this->destination->id,
            'name' => 'Paket Edukasi Kehutanan',
            'slug' => 'paket-edukasi-kehutanan',
            'inquiry_types' => ['study_tour'],
            'duration_days' => 1,
            'price_per_pax' => 50000,
            'min_pax' => 10,
            'max_pax' => 100,
            'is_active' => true,
        ]);

        $this->booking = Booking::create([
            'booking_code' => 'DST-2610-DOC12',
            'user_id' => $this->buyer->id,
            'institution_id' => $this->buyer->institution->id,
            'destination_id' => $this->destination->id,
            'package_id' => $package->id,
            'inquiry_type' => 'study_tour',
            'planned_date_start' => Carbon::now()->addDays(5)->toDateString(),
            'planned_date_end' => Carbon::now()->addDays(5)->toDateString(),
            'participant_count' => 20,
            'subtotal_amount' => 1000000,
            'addons_amount' => 0,
            'insurance_amount' => 40000,
            'platform_fee_amount' => 25000,
            'discount_amount' => 0,
            'total_amount' => 1065000,
            'commission_percent' => 10,
            'commission_amount' => 100000,
            'partner_net_amount' => 900000,
            'status' => BookingStatus::DIKONFIRMASI,
        ]);
    }

    public function test_document_number_generator_produces_sequential_numbers(): void
    {
        $generator = app(DocumentNumberGenerator::class);

        $num1 = $generator->generate(DocumentType::INVOICE);
        $num2 = $generator->generate(DocumentType::INVOICE);

        $currentYearMonth = now()->format('Y/m');
        $this->assertEquals("INV/{$currentYearMonth}/00001", $num1);
        $this->assertEquals("INV/{$currentYearMonth}/00002", $num2);
    }

    public function test_document_generation_saves_file_and_creates_record(): void
    {
        $docService = app(DocumentService::class);

        $invoice = $docService->generateForBooking($this->booking, DocumentType::INVOICE);

        $this->assertNotNull($invoice);
        $this->assertEquals(DocumentStatus::FINAL, $invoice->status);
        $this->assertNotNull($invoice->verification_hash);
        $this->assertDatabaseHas('documents', [
            'id' => $invoice->id,
            'booking_id' => $this->booking->id,
            'type' => DocumentType::INVOICE->value,
        ]);

        Storage::disk('local')->assertExists($invoice->file_path);
    }

    public function test_e_voucher_generation_includes_checkin_qr(): void
    {
        $docService = app(DocumentService::class);

        $voucher = $docService->generateForBooking($this->booking, DocumentType::E_VOUCHER);

        $this->assertNotNull($voucher);
        Storage::disk('local')->assertExists($voucher->file_path);
    }

    public function test_public_document_verification_endpoint(): void
    {
        $docService = app(DocumentService::class);
        $document = $docService->generateForBooking($this->booking, DocumentType::CONFIRMATION_LETTER);

        // Valid hash returns 200 with document details
        $response = $this->get(route('verification.document', ['hash' => $document->verification_hash]));
        $response->assertStatus(200);
        $response->assertSee($document->document_number);
        $response->assertSee($this->destination->name);

        // Invalid hash returns page showing not found
        $invalidResponse = $this->get(route('verification.document', ['hash' => 'nonexistent-hash']));
        $invalidResponse->assertStatus(200);
        $invalidResponse->assertSee('Dokumen Tidak Ditemukan');
    }

    public function test_document_download_policy(): void
    {
        $docService = app(DocumentService::class);
        $document = $docService->generateForBooking($this->booking, DocumentType::INVOICE);

        // 1. Owner buyer can download
        $this->actingAs($this->buyer);
        $resBuyer = $this->get(route('documents.download', $document));
        $resBuyer->assertStatus(200);

        // 2. Unauthorized buyer gets 403 Forbidden
        $this->actingAs($this->unauthorizedBuyer);
        $resOther = $this->get(route('documents.download', $document));
        $resOther->assertStatus(403);
    }

    public function test_admin_document_regeneration_voids_old_version(): void
    {
        $docService = app(DocumentService::class);
        $oldDoc = $docService->generateForBooking($this->booking, DocumentType::INVOICE);

        $newDoc = $docService->regenerate($oldDoc, $this->adminUser);

        $oldDoc->refresh();
        $this->assertEquals(DocumentStatus::VOID, $oldDoc->status);
        $this->assertEquals(DocumentStatus::FINAL, $newDoc->status);
        $this->assertEquals(2, $newDoc->version);
    }

    public function test_reschedule_documents_lifecycle_v7_1(): void
    {
        $bookingService = app(\App\Domain\Booking\Services\BookingService::class);
        $docService = app(DocumentService::class);

        // 1. Reschedule booking with status DIBAYAR: no confirmation letter or e-voucher generated
        $this->booking->status = BookingStatus::DIBAYAR;
        $this->booking->save();

        $bookingService->reschedule(
            $this->booking,
            now()->addDays(15),
            $this->adminUser,
            'Penyesuaian jadwal rombongan sekolah'
        );

        $this->booking->refresh();
        $this->assertEquals(BookingStatus::DIBAYAR, $this->booking->status);
        $this->assertEquals(0, Document::where('booking_id', $this->booking->id)
            ->whereIn('type', [DocumentType::CONFIRMATION_LETTER, DocumentType::E_VOUCHER])
            ->count());

        // 2. Reschedule booking with status DIKONFIRMASI: existing documents are voided and new versions issued
        $this->booking->status = BookingStatus::DIKONFIRMASI;
        $this->booking->save();

        $oldConf = $docService->generateForBooking($this->booking, DocumentType::CONFIRMATION_LETTER, $this->adminUser);
        $oldVoucher = $docService->generateForBooking($this->booking, DocumentType::E_VOUCHER, $this->adminUser);
        $this->assertEquals(DocumentStatus::FINAL, $oldConf->status);
        $this->assertEquals(DocumentStatus::FINAL, $oldVoucher->status);

        $bookingService->reschedule(
            $this->booking,
            now()->addDays(25),
            $this->adminUser,
            'Penyesuaian jadwal ulang tahap 2'
        );

        $oldConf->refresh();
        $oldVoucher->refresh();
        $this->assertEquals(DocumentStatus::VOID, $oldConf->status);
        $this->assertEquals(DocumentStatus::VOID, $oldVoucher->status);

        $newConf = Document::where('booking_id', $this->booking->id)
            ->where('type', DocumentType::CONFIRMATION_LETTER)
            ->where('status', DocumentStatus::FINAL)
            ->latest('id')
            ->first();
        $newVoucher = Document::where('booking_id', $this->booking->id)
            ->where('type', DocumentType::E_VOUCHER)
            ->where('status', DocumentStatus::FINAL)
            ->latest('id')
            ->first();

        $this->assertNotNull($newConf);
        $this->assertNotNull($newVoucher);
        $this->assertNotEquals($oldConf->id, $newConf->id);
        $this->assertNotEquals($oldVoucher->id, $newVoucher->id);
    }
}
