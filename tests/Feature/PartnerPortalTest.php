<?php

namespace Tests\Feature;

use App\Domain\Booking\Services\BookingCodeGenerator;
use App\Enums\AccountType;
use App\Enums\BookingStatus;
use App\Enums\DestinationStatus;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\PaymentStatus;
use App\Enums\VerificationStatus;
use App\Jobs\GenerateDocumentJob;
use App\Models\AvailabilitySlot;
use App\Models\Bank;
use App\Models\Booking;
use App\Models\CancellationPolicy;
use App\Models\Destination;
use App\Models\DestinationCategory;
use App\Models\Document;
use App\Models\Institution;
use App\Models\Package;
use App\Models\PartnerProfile;
use App\Models\Payment;
use App\Models\Province;
use App\Models\Review;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        $categoryName = strtoupper($this->destination->category->name);
        $response->assertSee($categoryName);
        $response->assertDontSee('{"id":');
        $response->assertSee('Payout Menunggu Proses');
        $response->assertDontSee('Saldo Siap Tarik');
        $response->assertSee('Ditransfer otomatis oleh admin sesuai jadwal rekonsiliasi');

        $destResponse = $this->actingAs($this->partner)->get(route('mitra.destinations'));
        $destResponse->assertOk();
        $destResponse->assertSee($categoryName);
        $destResponse->assertDontSee('{"id":');
    }

    public function test_partner_without_profile_can_open_dashboard(): void
    {
        $partner = User::factory()->create([
            'account_type' => AccountType::PARTNER,
            'status' => 'active',
        ]);
        $this->assertNull($partner->partnerProfile);

        $response = $this->actingAs($partner)->get(route('mitra.dashboard'));

        $response->assertOk();
        $response->assertSee('Panel Pengelola Tapak Adat');
        $response->assertSee('Akun Belum Terverifikasi');
        $this->assertDatabaseHas('partner_profiles', [
            'user_id' => $partner->id,
            'verification_status' => VerificationStatus::UNVERIFIED->value,
        ]);
    }

    public function test_partner_with_soft_deleted_profile_can_open_dashboard_and_profile(): void
    {
        $partner = User::factory()->create([
            'account_type' => AccountType::PARTNER,
            'status' => 'active',
        ]);
        PartnerProfile::create(['user_id' => $partner->id, 'organization_name' => 'Desa Lama'])->delete();

        $this->actingAs($partner)->get(route('mitra.dashboard'))->assertOk();
        $this->actingAs($partner)->get(route('mitra.profile'))->assertOk();

        $this->assertSame(1, PartnerProfile::withTrashed()->where('user_id', $partner->id)->count());
        $this->assertNotNull($partner->fresh()->partnerProfile);
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

    public function test_partner_notifications_center(): void
    {
        $this->actingAs($this->partner);

        $notif = DatabaseNotification::create([
            'id' => Str::uuid()->toString(),
            'type' => 'Destinara\\InAppNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $this->partner->id,
            'data' => [
                'subject' => 'Pesanan Baru Perlu Konfirmasi',
                'message' => 'Pesanan baru masuk dari sekolah.',
                'event_key' => 'booking.paid',
            ],
            'read_at' => null,
        ]);

        $this->get('/mitra/notifikasi')->assertStatus(200)->assertSee('Pesanan Baru Perlu Konfirmasi');

        $this->post("/mitra/notifikasi/{$notif->id}/read")->assertRedirect();
        $this->assertNotNull($notif->fresh()->read_at);

        $notif2 = DatabaseNotification::create([
            'id' => Str::uuid()->toString(),
            'type' => 'Destinara\\InAppNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $this->partner->id,
            'data' => [
                'subject' => 'Pengajuan Payout Ditransfer',
                'message' => 'Dana telah ditransfer ke rekening Anda.',
                'event_key' => 'payout.transferred',
            ],
            'read_at' => null,
        ]);

        $this->post('/mitra/notifikasi/read-all')->assertRedirect();
        $this->assertNotNull($notif2->fresh()->read_at);
    }

    public function test_partner_upload_signed_permit_flow_v9_1(): void
    {
        Http::fake([
            '*' => Http::response(['status' => 'success'], 200),
        ]);

        $booking = Booking::create([
            'booking_code' => app(BookingCodeGenerator::class)->generate(),
            'user_id' => $this->buyer->id,
            'institution_id' => $this->buyer->institution->id,
            'destination_id' => $this->destination->id,
            'package_id' => $this->package->id,
            'inquiry_type' => 'study_tour',
            'planned_date_start' => Carbon::now()->addDays(14)->toDateString(),
            'planned_date_end' => Carbon::now()->addDays(14)->toDateString(),
            'participant_count' => 20,
            'companion_count' => 2,
            'subtotal_amount' => 1000000,
            'total_amount' => 1000000,
            'commission_percent' => 10,
            'commission_amount' => 100000,
            'partner_net_amount' => 900000,
            'needs_permit_letter' => true,
            'status' => BookingStatus::DIKONFIRMASI,
        ]);

        // Active permit_draft
        $draftPermit = Document::create([
            'booking_id' => $booking->id,
            'type' => DocumentType::PERMIT_DRAFT,
            'document_number' => 'DRAFT-IZIN/2026/10/00001',
            'title' => 'Draf Surat Izin Kegiatan',
            'file_path' => 'documents/draft_001.pdf',
            'verification_hash' => hash('sha256', 'draft-permit-test'),
            'status' => DocumentStatus::DRAFT,
            'version' => 1,
            'generated_at' => now(),
        ]);

        // 1. Another partner attempts upload -> 403 Forbidden
        $otherPartner = User::factory()->create([
            'account_type' => AccountType::PARTNER,
            'status' => 'active',
        ]);
        PartnerProfile::create([
            'user_id' => $otherPartner->id,
            'organization_name' => 'Mitra Lain',
            'verification_status' => VerificationStatus::VERIFIED,
            'bank_id' => $this->bank->id,
            'bank_account_number' => '1111111111',
            'bank_account_holder' => 'Mitra Lain',
        ]);

        $pdfFile = UploadedFile::fake()->create('surat_izin_bertanda_tangan.pdf', 500, 'application/pdf');
        $forbiddenRes = $this->actingAs($otherPartner)->post(route('mitra.booking.uploadPermit', $booking->booking_code), [
            'file' => $pdfFile,
        ]);
        $forbiddenRes->assertStatus(403);

        // 2. Non-PDF file upload rejected
        $txtFile = UploadedFile::fake()->create('surat_izin.txt', 200, 'text/plain');
        $invalidRes = $this->actingAs($this->partner)->post(route('mitra.booking.uploadPermit', $booking->booking_code), [
            'file' => $txtFile,
        ]);
        $invalidRes->assertSessionHasErrors(['file']);

        // 3. Valid PDF uploaded by destination partner
        $validRes = $this->actingAs($this->partner)->post(route('mitra.booking.uploadPermit', $booking->booking_code), [
            'file' => $pdfFile,
        ]);
        $validRes->assertRedirect();
        $validRes->assertSessionHas('success');

        $signedPermit = Document::where('booking_id', $booking->id)
            ->where('type', DocumentType::PERMIT_SIGNED)
            ->where('status', DocumentStatus::FINAL)
            ->first();

        $this->assertNotNull($signedPermit);
        $this->assertEquals(1, $signedPermit->version);
        $this->assertStringStartsWith('IZIN/', $signedPermit->document_number);
        $this->assertNotEmpty($signedPermit->verification_hash);
        $this->assertTrue(Storage::disk('local')->exists($signedPermit->file_path));

        // 4. Notification sent to buyer
        $this->assertDatabaseHas('notification_logs', [
            'event_key' => 'booking.permit_available',
            'notifiable_id' => $this->buyer->id,
        ]);

        // 5. Buyer can download the signed permit
        $downloadRes = $this->actingAs($this->buyer)->get(route('documents.download', $signedPermit->id));
        $downloadRes->assertOk();

        // 6. Partner uploads replacement version -> previous version voided, new version is 2
        $pdfFile2 = UploadedFile::fake()->create('surat_izin_v2.pdf', 600, 'application/pdf');
        $replaceRes = $this->actingAs($this->partner)->post(route('mitra.booking.uploadPermit', $booking->booking_code), [
            'file' => $pdfFile2,
        ]);
        $replaceRes->assertRedirect();

        $signedPermit->refresh();
        $this->assertEquals(DocumentStatus::VOID, $signedPermit->status);

        $newSignedPermit = Document::where('booking_id', $booking->id)
            ->where('type', DocumentType::PERMIT_SIGNED)
            ->where('status', DocumentStatus::FINAL)
            ->first();

        $this->assertNotNull($newSignedPermit);
        $this->assertEquals(2, $newSignedPermit->version);
        $this->assertNotEquals($signedPermit->id, $newSignedPermit->id);

        // 7. Partner booking show page renders with upload form
        $showRes = $this->actingAs($this->partner)->get(route('mitra.booking.show', $booking->booking_code));
        $showRes->assertOk();
        $showRes->assertSee('Surat Izin Bertanda Tangan');
        $showRes->assertSee(route('mitra.booking.uploadPermit', $booking->booking_code));
    }

    public function test_failed_signed_permit_upload_keeps_previous_version_and_removes_orphan_file_v10_1(): void
    {
        Storage::fake('local');

        $booking = Booking::create([
            'booking_code' => app(BookingCodeGenerator::class)->generate(),
            'user_id' => $this->buyer->id,
            'institution_id' => $this->buyer->institution->id,
            'destination_id' => $this->destination->id,
            'package_id' => $this->package->id,
            'inquiry_type' => 'study_tour',
            'planned_date_start' => Carbon::now()->addDays(14)->toDateString(),
            'planned_date_end' => Carbon::now()->addDays(14)->toDateString(),
            'participant_count' => 20,
            'subtotal_amount' => 1000000,
            'total_amount' => 1000000,
            'commission_percent' => 10,
            'commission_amount' => 100000,
            'partner_net_amount' => 900000,
            'needs_permit_letter' => true,
            'status' => BookingStatus::DIKONFIRMASI,
        ]);

        Document::create([
            'booking_id' => $booking->id,
            'type' => DocumentType::PERMIT_DRAFT,
            'document_number' => 'DSI/2026/10/09001',
            'title' => 'Draf Surat Izin',
            'file_path' => 'documents/draft_v10.pdf',
            'verification_hash' => hash('sha256', 'draft-v10'),
            'status' => DocumentStatus::DRAFT,
            'version' => 1,
            'generated_at' => now(),
        ]);
        $previous = Document::create([
            'booking_id' => $booking->id,
            'type' => DocumentType::PERMIT_SIGNED,
            'document_number' => 'IZIN/2026/10/09001',
            'title' => 'Surat Izin Bertanda Tangan Resmi',
            'file_path' => 'documents/signed_v1.pdf',
            'verification_hash' => hash('sha256', 'signed-v10'),
            'status' => DocumentStatus::FINAL,
            'version' => 1,
            'generated_at' => now(),
        ]);

        // Gagal saat menyimpan dokumen baru: setelah berkas tersimpan dan versi lama di-void
        Document::creating(function () {
            throw new \RuntimeException('Gagal menyimpan dokumen');
        });

        $this->withoutExceptionHandling();
        try {
            $this->actingAs($this->partner)->post(route('mitra.booking.uploadPermit', $booking->booking_code), [
                'file' => UploadedFile::fake()->create('izin_baru.pdf', 200, 'application/pdf'),
            ]);
            $this->fail('Upload seharusnya gagal.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Gagal menyimpan dokumen', $e->getMessage());
        }

        $this->assertEquals(DocumentStatus::FINAL, $previous->fresh()->status, 'Versi lama tidak boleh ikut di-void.');
        $this->assertSame(1, Document::where('booking_id', $booking->id)->where('type', DocumentType::PERMIT_SIGNED)->count());
        $this->assertSame([], Storage::disk('local')->allFiles('documents'), 'Berkas yatim harus dihapus.');
    }
}
