<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\VerificationStatus;
use App\Models\Bank;
use App\Models\Institution;
use App\Models\NotificationLog;
use App\Models\OrganizationType;
use App\Models\PartnerProfile;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PartnerVerificationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $partnerUser;

    protected PartnerProfile $partnerProfile;

    protected Bank $bank;

    protected Province $province;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        Storage::fake('local');

        $this->province = Province::first() ?? Province::create(['code' => '34', 'name' => 'DI Yogyakarta']);
        $this->bank = Bank::first() ?? Bank::create(['code' => 'BCA', 'name' => 'Bank Central Asia', 'is_active' => true]);

        // Create partner user
        $this->partnerUser = User::factory()->create([
            'account_type' => AccountType::PARTNER,
            'status' => 'active',
            'name' => 'Budi Santoso',
            'email' => 'budi@mitra-alam.id',
            'phone' => '6281234567890',
        ]);

        $orgType = OrganizationType::first() ?? OrganizationType::create(['name' => 'Kelompok Sadar Wisata', 'slug' => 'pokdarwis']);

        $this->partnerProfile = PartnerProfile::create([
            'user_id' => $this->partnerUser->id,
            'organization_name' => 'Pokdarwis Watu Goyang',
            'organization_type_id' => $orgType->id,
            'legal_document_number' => 'SK-POK-2026-001',
            'address' => 'Mangunan, Dlingo, Bantul',
            'province_id' => $this->province->id,
            'city' => 'Bantul',
            'bank_id' => $this->bank->id,
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'POKDARWIS WATU GOYANG',
            'verification_status' => VerificationStatus::UNVERIFIED,
        ]);

        // Create admin user
        $this->admin = User::factory()->create([
            'account_type' => AccountType::INTERNAL,
            'status' => 'active',
            'name' => 'Admin Verifikasi',
            'email' => 'admin.verif@destinara.test',
        ]);

        $adminRole = Role::firstOrCreate(['name' => 'admin_operasional', 'guard_name' => 'web']);
        $partnerVerifyPerm = Permission::firstOrCreate(['name' => 'partners.verify', 'guard_name' => 'web']);
        $instVerifyPerm = Permission::firstOrCreate(['name' => 'institutions.verify', 'guard_name' => 'web']);
        $adminRole->givePermissionTo([$partnerVerifyPerm, $instVerifyPerm]);
        $this->admin->assignRole($adminRole);
    }

    public function test_unverified_partner_cannot_access_destination_management_and_is_redirected_to_profile(): void
    {
        $response = $this->actingAs($this->partnerUser)->get(route('mitra.destinations.create'));

        $response->assertRedirect(route('mitra.profile'));
        $response->assertSessionHas('warning');

        $profileResponse = $this->actingAs($this->partnerUser)->get(route('mitra.profile'));
        $profileResponse->assertStatus(200);
        $profileResponse->assertSee('Belum Terverifikasi');
        $profileResponse->assertSee('Tahapan Menuju Kemitraan Terverifikasi');
        $profileResponse->assertSee('Dokumen Legalitas');
    }

    public function test_partner_can_upload_and_delete_legal_documents(): void
    {
        $file = UploadedFile::fake()->create('sk_pokdarwis.pdf', 200, 'application/pdf');

        $response = $this->actingAs($this->partnerUser)->post(route('mitra.profile.document.upload'), [
            'type' => 'sk_pengelola',
            'file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('partner_documents', [
            'partner_profile_id' => $this->partnerProfile->id,
            'type' => 'sk_pengelola',
            'original_name' => 'sk_pokdarwis.pdf',
        ]);

        $doc = $this->partnerProfile->documents()->first();
        $this->assertNotNull($doc);
        Storage::disk('local')->assertExists($doc->file_path);

        // Download document
        $downloadResponse = $this->actingAs($this->partnerUser)->get(route('mitra.profile.document.download', $doc->id));
        $downloadResponse->assertStatus(200);

        // Delete document
        $deleteResponse = $this->actingAs($this->partnerUser)->post(route('mitra.profile.document.delete', $doc->id));
        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('partner_documents', ['id' => $doc->id]);
        Storage::disk('local')->assertMissing($doc->file_path);
    }

    public function test_partner_cannot_submit_verification_if_no_documents_uploaded(): void
    {
        $response = $this->actingAs($this->partnerUser)->post(route('mitra.profile.submitVerification'));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals(VerificationStatus::UNVERIFIED, $this->partnerProfile->fresh()->verification_status);
    }

    public function test_full_partner_verification_lifecycle_from_ui(): void
    {
        // 1. Partner uploads a legal document
        $file = UploadedFile::fake()->create('sk_bupati_bantul.pdf', 300, 'application/pdf');
        $this->actingAs($this->partnerUser)->post(route('mitra.profile.document.upload'), [
            'type' => 'sk_pengelola',
            'file' => $file,
        ])->assertRedirect();

        $doc = $this->partnerProfile->fresh()->documents()->first();
        $this->assertNotNull($doc);

        // 2. Partner submits verification
        $submitResponse = $this->actingAs($this->partnerUser)->post(route('mitra.profile.submitVerification'));
        $submitResponse->assertRedirect();
        $submitResponse->assertSessionHas('success');

        $this->assertEquals(VerificationStatus::PENDING, $this->partnerProfile->fresh()->verification_status);

        // 3. Admin visits verification list and sees "Verifikasi Sah" button and the uploaded document
        $adminView = $this->actingAs($this->admin)->get(route('admin.users.verify', ['tab' => 'partners']));
        $adminView->assertStatus(200);
        $adminView->assertSee('Pokdarwis Watu Goyang');
        $adminView->assertSee('MENUNGGU VALIDASI');
        $adminView->assertSee('sk_bupati_bantul.pdf');
        $adminView->assertSee('Verifikasi Sah');

        // 4. Admin downloads partner document
        $adminDownload = $this->actingAs($this->admin)->get(route('admin.users.partnerDocument.download', $doc->id));
        $adminDownload->assertStatus(200);

        // 5. Admin verifies partner
        $verifyResponse = $this->actingAs($this->admin)->post(route('admin.users.updateVerification', [
            'type' => 'partner',
            'id' => $this->partnerProfile->id,
        ]), [
            'action' => 'verify',
        ]);

        $verifyResponse->assertRedirect();
        $this->assertEquals(VerificationStatus::VERIFIED, $this->partnerProfile->fresh()->verification_status);
        $this->assertNotNull($this->partnerProfile->fresh()->verified_at);

        // 6. Partner can now access destination creation without being blocked
        $partnerAccess = $this->actingAs($this->partnerUser->fresh())->get(route('mitra.destinations.create'));
        $partnerAccess->assertStatus(200);
    }

    public function test_admin_can_reject_partner_verification_with_note(): void
    {
        // Upload doc and set to pending
        $file = UploadedFile::fake()->create('sk_draft.pdf', 150, 'application/pdf');
        $this->actingAs($this->partnerUser)->post(route('mitra.profile.document.upload'), [
            'type' => 'sk_pengelola',
            'file' => $file,
        ]);
        $this->actingAs($this->partnerUser)->post(route('mitra.profile.submitVerification'));

        // Admin rejects
        $this->actingAs($this->admin)->post(route('admin.users.updateVerification', [
            'type' => 'partner',
            'id' => $this->partnerProfile->id,
        ]), [
            'action' => 'reject',
            'note' => 'SK belum ditandatangani oleh pejabat berwenang.',
        ])->assertRedirect();

        $this->assertEquals(VerificationStatus::REJECTED, $this->partnerProfile->fresh()->verification_status);
        $this->assertEquals('SK belum ditandatangani oleh pejabat berwenang.', $this->partnerProfile->fresh()->verification_note);

        // Partner sees rejection note on profile
        $partnerProfileView = $this->actingAs($this->partnerUser)->get(route('mitra.profile'));
        $partnerProfileView->assertStatus(200);
        $partnerProfileView->assertSee('Verifikasi Perlu Perbaikan');
        $partnerProfileView->assertSee('SK belum ditandatangani oleh pejabat berwenang.');
    }

    public function test_buyer_institution_document_and_verification(): void
    {
        $buyer = User::factory()->create([
            'account_type' => AccountType::BUYER,
            'status' => 'active',
            'name' => 'Dra. Sri Wahyuni',
            'email' => 'sri@sman1yogya.sch.id',
            'phone' => '6281987654321',
        ]);

        $institution = Institution::create([
            'user_id' => $buyer->id,
            'institution_name' => 'SMA Negeri 1 Yogyakarta',
            'institution_type' => 'sekolah',
            'npsn' => '20403176',
            'address' => 'Jl. HOS Cokroaminoto No. 10',
            'city' => 'Yogyakarta',
            'province' => 'DI Yogyakarta',
            'pic_name' => 'Dra. Sri Wahyuni',
            'pic_position' => 'Wakasek Kesiswaan',
            'pic_phone' => '6281987654321',
            'pic_email' => 'sri@sman1yogya.sch.id',
            'verification_status' => VerificationStatus::UNVERIFIED,
        ]);

        // Buyer uploads letterhead
        $file = UploadedFile::fake()->create('kop_surat_resmi.png', 100, 'image/png');
        $uploadResp = $this->actingAs($buyer)->post(route('buyer.profile.document.upload'), [
            'type' => 'kop_surat',
            'file' => $file,
        ]);
        $uploadResp->assertRedirect();
        $this->assertDatabaseHas('institution_documents', [
            'institution_id' => $institution->id,
            'type' => 'kop_surat',
            'original_name' => 'kop_surat_resmi.png',
        ]);

        // Buyer submits verification
        $this->actingAs($buyer)->post(route('buyer.profile.submitVerification'))->assertRedirect();
        $this->assertEquals(VerificationStatus::PENDING, $institution->fresh()->verification_status);

        // Admin sees institution in verify tab
        $adminView = $this->actingAs($this->admin)->get(route('admin.users.verify', ['tab' => 'institutions']));
        $adminView->assertStatus(200);
        $adminView->assertSee('SMA Negeri 1 Yogyakarta');
        $adminView->assertSee('kop_surat_resmi.png');
        $adminView->assertSee('Verifikasi Sah');

        // Admin verifies institution
        $this->actingAs($this->admin)->post(route('admin.users.updateVerification', [
            'type' => 'institution',
            'id' => $institution->id,
        ]), [
            'action' => 'verify',
        ])->assertRedirect();

        $this->assertEquals(VerificationStatus::VERIFIED, $institution->fresh()->verification_status);
    }

    public function test_rejection_requires_reason_saves_note_and_displays_on_profiles_and_notifications(): void
    {
        // 1. Partner rejection without note fails validation
        $failPartner = $this->actingAs($this->admin)->post(route('admin.users.updateVerification', [
            'type' => 'partner',
            'id' => $this->partnerProfile->id,
        ]), [
            'action' => 'reject',
            'note' => '',
        ]);
        $failPartner->assertSessionHasErrors(['note']);

        // 2. Partner rejection with note succeeds, saves verification_note, dispatches notification
        $partnerRejectNote = 'Dokumen SK Pengurus Pokdarwis tidak memiliki stempel basah';
        $okPartner = $this->actingAs($this->admin)->post(route('admin.users.updateVerification', [
            'type' => 'partner',
            'id' => $this->partnerProfile->id,
        ]), [
            'action' => 'reject',
            'note' => $partnerRejectNote,
        ]);
        $okPartner->assertRedirect();

        $this->partnerProfile->refresh();
        $this->assertEquals(VerificationStatus::REJECTED, $this->partnerProfile->verification_status);
        $this->assertEquals($partnerRejectNote, $this->partnerProfile->verification_note);

        // Partner sees note on profile
        $resPartnerProfile = $this->actingAs($this->partnerUser)->get(route('mitra.profile'));
        $resPartnerProfile->assertStatus(200);
        $resPartnerProfile->assertSee($partnerRejectNote);

        // Notification logged for partner
        $this->assertDatabaseHas('notification_logs', [
            'event_key' => 'partner.rejected',
            'recipient' => (string) $this->partnerUser->id,
        ]);
        $partnerLog = NotificationLog::where('event_key', 'partner.rejected')->latest('id')->first();
        $this->assertStringContainsString($partnerRejectNote, $partnerLog->body);

        // 3. Institution setup
        $buyer = User::factory()->create([
            'account_type' => AccountType::BUYER,
            'status' => 'active',
            'name' => 'Drs. Handoko',
            'email' => 'handoko@sman2bantul.sch.id',
        ]);
        $institution = Institution::create([
            'user_id' => $buyer->id,
            'institution_name' => 'SMA Negeri 2 Bantul',
            'npsn' => '20403199',
            'pic_name' => 'Drs. Handoko',
            'pic_phone' => '628111222333',
            'pic_email' => 'handoko@sman2bantul.sch.id',
            'verification_status' => VerificationStatus::PENDING,
        ]);

        // Institution rejection without note fails validation
        $failInst = $this->actingAs($this->admin)->post(route('admin.users.updateVerification', [
            'type' => 'institution',
            'id' => $institution->id,
        ]), [
            'action' => 'reject',
            'note' => '',
        ]);
        $failInst->assertSessionHasErrors(['note']);

        // Institution rejection with note succeeds
        $instRejectNote = 'NPSN tidak sesuai dengan nama lembaga di pangkalan data Kemendikbud';
        $okInst = $this->actingAs($this->admin)->post(route('admin.users.updateVerification', [
            'type' => 'institution',
            'id' => $institution->id,
        ]), [
            'action' => 'reject',
            'note' => $instRejectNote,
        ]);
        $okInst->assertRedirect();

        $institution->refresh();
        $this->assertEquals(VerificationStatus::REJECTED, $institution->verification_status);
        $this->assertEquals($instRejectNote, $institution->verification_note);

        // Buyer sees note on profile
        $resBuyerProfile = $this->actingAs($buyer)->get(route('buyer.profile'));
        $resBuyerProfile->assertStatus(200);
        $resBuyerProfile->assertSee($instRejectNote);

        // Notification logged for buyer
        $this->assertDatabaseHas('notification_logs', [
            'event_key' => 'institution.rejected',
            'recipient' => (string) $buyer->id,
        ]);
        $instLog = NotificationLog::where('event_key', 'institution.rejected')->latest('id')->first();
        $this->assertStringContainsString($instRejectNote, $instLog->body);
    }
}
