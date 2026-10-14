<?php

namespace Tests\Feature;

use App\Domain\Document\Services\DocumentService;
use App\Domain\Notification\Services\TemplateRenderer;
use App\Enums\BookingStatus;
use App\Enums\DestinationStatus;
use App\Enums\DocumentType;
use App\Enums\InquiryType;
use App\Enums\PaymentStatus;
use App\Enums\PayoutStatus;
use App\Events\BookingPaid;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\CancellationPolicy;
use App\Models\Destination;
use App\Models\DestinationCategory;
use App\Models\Institution;
use App\Models\NotificationLog;
use App\Models\Package;
use App\Models\PartnerPayout;
use App\Models\PartnerProfile;
use App\Models\Payment;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentAndNotificationRenderTest extends TestCase
{
    use RefreshDatabase;

    protected User $partner;

    protected User $buyer;

    protected User $admin;

    protected Destination $destination;

    protected Package $package;

    protected Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('local');

        $this->partner = User::factory()->create([
            'account_type' => 'partner',
            'name' => 'Pak Budi Mitra',
            'email' => 'budi@mitra.test',
        ]);
        PartnerProfile::create([
            'user_id' => $this->partner->id,
            'organization_name' => 'Pokdarwis Nglanggeran',
            'pic_name' => 'Budi Santoso',
            'pic_phone' => '081234567890',
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'Budi Santoso',
            'is_verified' => true,
        ]);

        $this->buyer = User::factory()->create([
            'account_type' => 'buyer',
            'name' => 'Sari Guru Uji',
            'email' => 'sari@sekolah.test',
            'phone' => '089876543210',
        ]);
        Institution::create([
            'user_id' => $this->buyer->id,
            'institution_name' => 'SMA Negeri 1 Teladan',
            'pic_name' => 'Sari Guru Uji',
            'pic_phone' => '089876543210',
            'pic_email' => 'sari@sekolah.test',
            'status' => 'verified',
        ]);

        $this->admin = User::factory()->create([
            'account_type' => 'internal',
            'name' => 'Admin Operasional',
            'email' => 'admin@destinara.test',
        ]);

        $category = DestinationCategory::firstOrCreate(['slug' => 'ekowisata'], ['name' => 'Ekowisata', 'is_active' => true]);
        $province = Province::first();
        $policy = CancellationPolicy::first();

        $this->destination = Destination::create([
            'partner_id' => $this->partner->id,
            'category_id' => $category->id,
            'province_id' => $province?->id,
            'cancellation_policy_id' => $policy?->id,
            'name' => 'Desa Wisata Nglanggeran',
            'slug' => 'desa-wisata-nglanggeran',
            'city' => 'Gunungkidul',
            'address' => 'Nglanggeran, Patuk',
            'description' => 'Desa Wisata Nglanggeran.',
            'status' => DestinationStatus::PUBLISHED,
        ]);

        $this->package = Package::create([
            'destination_id' => $this->destination->id,
            'name' => 'Paket Edukasi Geologi',
            'slug' => 'paket-edukasi-geologi',
            'price_per_pax' => 75000,
            'duration_days' => 1,
            'min_pax' => 10,
            'max_pax' => 100,
            'inquiry_types' => ['study_tour'],
            'is_active' => true,
        ]);

        $this->booking = Booking::create([
            'booking_code' => 'DST-261014-ZENR9',
            'user_id' => $this->buyer->id,
            'destination_id' => $this->destination->id,
            'package_id' => $this->package->id,
            'institution_id' => $this->buyer->institution->id,
            'inquiry_type' => InquiryType::STUDY_TOUR,
            'participant_count' => 30,
            'companion_count' => 3,
            'planned_date_start' => now()->addDays(14)->toDateString(),
            'planned_date_end' => now()->addDays(14)->toDateString(),
            'subtotal_amount' => 2250000,
            'total_amount' => 2335000,
            'platform_fee_amount' => 25000,
            'insurance_amount' => 60000,
            'discount_amount' => 0,
            'commission_amount' => 225000,
            'partner_net_amount' => 2025000,
            'status' => BookingStatus::MENUNGGU_PEMBAYARAN,
            'expires_at' => now()->addHours(24),
        ]);

        BookingItem::create([
            'booking_id' => $this->booking->id,
            'type' => 'package',
            'name' => 'Paket Edukasi Geologi (30 pax)',
            'quantity' => 30,
            'unit_price' => 75000,
            'total' => 2250000,
        ]);

        BookingItem::create([
            'booking_id' => $this->booking->id,
            'type' => 'addon',
            'name' => 'Dokumentasi Drone',
            'quantity' => 1,
            'unit_price' => 350000,
            'total' => 350000,
        ]);
    }

    protected function extractPdfText(string $pdfBinary): string
    {
        $text = '';
        if (preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/s', $pdfBinary, $matches)) {
            foreach ($matches[1] as $stream) {
                $decompressed = @gzuncompress($stream);
                if ($decompressed !== false) {
                    $text .= ' '.$decompressed;
                } else {
                    $text .= ' '.$stream;
                }
            }
        }

        return $pdfBinary.' '.$text;
    }

    public function test_template_renderer_does_not_leave_braces_and_expands_booking(): void
    {
        $renderer = app(TemplateRenderer::class);

        $template = 'Pembayaran Berhasil: {{booking_code}} untuk Halo {{pic_name}} di {{destination_name}}. Mitra: {{organization_name}}. Unknown: {{non_existent_key}}';
        $rendered = $renderer->render($template, [
            'booking' => $this->booking,
        ]);

        // Must not contain curly brackets around values like {DST-...} or {Sari...}
        $this->assertStringNotContainsString('{DST', $rendered);
        $this->assertStringNotContainsString('{Sari', $rendered);
        $this->assertStringNotContainsString('{{', $rendered);
        $this->assertStringNotContainsString('}}', $rendered);

        // Must contain resolved values
        $this->assertStringContainsString('DST-261014-ZENR9', $rendered);
        $this->assertStringContainsString('Sari Guru Uji', $rendered);
        $this->assertStringContainsString('Desa Wisata Nglanggeran', $rendered);
        $this->assertStringContainsString('Pokdarwis Nglanggeran', $rendered);
    }

    public function test_invoice_document_has_no_unreplaced_placeholders_and_contains_items(): void
    {
        $docService = app(DocumentService::class);
        $doc = $docService->generateForBooking($this->booking, DocumentType::INVOICE);

        Storage::disk('local')->assertExists($doc->file_path);
        $pdfContent = Storage::disk('local')->get($doc->file_path);
        $text = $this->extractPdfText($pdfContent);

        // Placeholders must be replaced
        $this->assertStringNotContainsString('{{institution_name}}', $text);
        $this->assertStringNotContainsString('{{total_amount}}', $text);
        $this->assertStringNotContainsString('{{document_number}}', $text);
        $this->assertStringNotContainsString('{{items_table_rows}}', $text);

        // Check footer legal name and document data
        $this->assertStringContainsString('PT DESTINARA CHAKRAWAL ARTHA', $text);
        $this->assertStringContainsString('SMA Negeri 1 Teladan', $text);
    }

    public function test_receipt_document_has_no_unreplaced_placeholders_and_contains_terbilang(): void
    {
        $payment = Payment::create([
            'booking_id' => $this->booking->id,
            'user_id' => $this->buyer->id,
            'amount' => 2335000,
            'gateway' => 'xendit',
            'external_id' => 'PAY-TEST-001',
            'payment_type' => 'bank_transfer',
            'payment_channel' => 'BCA Virtual Account',
            'status' => PaymentStatus::PAID,
            'paid_at' => now(),
        ]);

        $this->booking->update(['status' => BookingStatus::DIBAYAR]);

        $docService = app(DocumentService::class);
        $doc = $docService->generateForBooking($this->booking, DocumentType::RECEIPT);

        Storage::disk('local')->assertExists($doc->file_path);
        $pdfContent = Storage::disk('local')->get($doc->file_path);
        $text = $this->extractPdfText($pdfContent);

        $this->assertStringNotContainsString('{{amount_words}}', $text);
        $this->assertStringNotContainsString('{{paid_at}}', $text);
        $this->assertStringNotContainsString('{{booking_code}}', $text);

        // Check terbilang in words
        $this->assertStringContainsString('Dua Juta Tiga Ratus Tiga Puluh Lima Ribu', $text);
    }

    public function test_e_voucher_document_has_no_unreplaced_placeholders_and_correct_qr_label(): void
    {
        $this->booking->update(['status' => BookingStatus::DIKONFIRMASI]);

        $docService = app(DocumentService::class);
        $doc = $docService->generateForBooking($this->booking, DocumentType::E_VOUCHER);

        Storage::disk('local')->assertExists($doc->file_path);
        $pdfContent = Storage::disk('local')->get($doc->file_path);
        $text = $this->extractPdfText($pdfContent);

        $this->assertStringNotContainsString('{{checkin_qr_svg_base64}}', $text);
        $this->assertStringNotContainsString('{{booking_code}}', $text);
        $this->assertStringNotContainsString('PASUKAN MASUK', $text);
    }

    public function test_payout_receipt_document_has_no_unreplaced_placeholders(): void
    {
        $payout = PartnerPayout::create([
            'payout_number' => 'PO/2026/10/00001',
            'partner_id' => $this->partner->id,
            'booking_id' => $this->booking->id,
            'gross_amount' => 2250000,
            'commission_amount' => 225000,
            'net_amount' => 2025000,
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'Budi Santoso',
            'status' => PayoutStatus::TRANSFERRED,
            'transferred_at' => now(),
            'eligible_at' => now(),
        ]);

        $docService = app(DocumentService::class);
        $doc = $docService->generateForPayout($payout);

        Storage::disk('local')->assertExists($doc->file_path);
        $pdfContent = Storage::disk('local')->get($doc->file_path);
        $text = $this->extractPdfText($pdfContent);

        $this->assertStringNotContainsString('{{payout_number}}', $text);
        $this->assertStringNotContainsString('{{organization_name}}', $text);
        $this->assertStringNotContainsString('{{net_amount}}', $text);
        $this->assertStringNotContainsString('{{document_number}}', $text);
        $this->assertStringContainsString('Pokdarwis Nglanggeran', $text);
    }

    public function test_domain_notification_dispatch_produces_clean_messages(): void
    {
        $payment = Payment::create([
            'booking_id' => $this->booking->id,
            'user_id' => $this->buyer->id,
            'amount' => 2335000,
            'gateway' => 'xendit',
            'external_id' => 'PAY-TEST-002',
            'payment_type' => 'bank_transfer',
            'payment_channel' => 'BCA Virtual Account',
            'status' => PaymentStatus::PAID,
            'paid_at' => now(),
        ]);

        event(new BookingPaid($this->booking, $payment));

        $logs = NotificationLog::where('event_key', 'payment.paid')->get();
        $this->assertNotEmpty($logs);

        foreach ($logs as $log) {
            if ($log->subject) {
                $this->assertStringNotContainsString('{DST-', $log->subject);
                $this->assertStringNotContainsString('{{', $log->subject);
                $this->assertStringNotContainsString('}}', $log->subject);
            }
            $this->assertStringNotContainsString('{Sari', $log->body);
            $this->assertStringNotContainsString('{DST-', $log->body);
            $this->assertStringNotContainsString('{{organization_name}}', $log->body);
            $this->assertStringNotContainsString('{{', $log->body);
            $this->assertStringNotContainsString('}}', $log->body);
        }
    }
}
