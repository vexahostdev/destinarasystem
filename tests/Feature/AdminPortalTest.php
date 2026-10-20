<?php

namespace Tests\Feature;

use App\Domain\Settings\SettingsRepository;
use App\Enums\AccountType;
use App\Enums\DestinationStatus;
use App\Models\CancellationPolicy;
use App\Models\Destination;
use App\Models\DestinationCategory;
use App\Models\Package;
use App\Models\Province;
use App\Models\Setting;
use App\Models\User;
use App\Providers\RuntimeConfigServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminPortalTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $buyer;

    protected User $partner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->superAdmin = User::where('email', config('destinara.superadmin.email', 'superadmin@destinara.test'))
            ->orWhere('account_type', AccountType::INTERNAL)
            ->first();

        if (! $this->superAdmin) {
            $this->superAdmin = User::factory()->create([
                'name' => 'Super Administrator',
                'email' => 'superadmin@destinara.test',
                'account_type' => AccountType::INTERNAL,
                'status' => 'active',
            ]);
            $this->superAdmin->assignRole('super_admin');
        }

        $this->buyer = User::factory()->create([
            'account_type' => AccountType::BUYER,
            'status' => 'active',
        ]);

        $this->partner = User::factory()->create([
            'account_type' => AccountType::PARTNER,
            'status' => 'active',
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/admin/dashboard');
        $response->assertRedirect(route('login'));
    }

    public function test_buyer_and_partner_cannot_access_admin(): void
    {
        $this->actingAs($this->buyer)->get('/admin/dashboard')->assertRedirect(route('buyer.dashboard'));
        $this->actingAs($this->partner)->get('/admin/dashboard')->assertRedirect(route('partner.dashboard'));
    }

    public function test_superadmin_can_access_dashboard_and_major_sections(): void
    {
        $this->actingAs($this->superAdmin);

        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Dashboard Operasional');
        $this->get(route('admin.destinations.index'))->assertOk();
        $this->get(route('admin.finance.payments'))->assertOk();
        $this->get(route('admin.finance.refunds'))->assertOk();
        $this->get(route('admin.finance.payouts'))->assertOk();
        $this->get(route('admin.finance.reconciliation'))->assertOk();
        $this->get(route('admin.master.index'))->assertOk();
        $this->get(route('admin.settings.index'))->assertOk();
        $this->get(route('admin.team.index'))->assertOk();
        $this->get(route('admin.auditLogs.index'))->assertOk();
        $this->get(route('admin.analytics.index'))->assertOk();
        $this->get(route('admin.health.index'))->assertOk();
        $this->get(route('admin.reviews.index'))->assertOk();
        $this->get(route('admin.vouchers.index'))->assertOk();
        $this->get(route('admin.siteContents.index'))->assertOk();
        $this->get(route('admin.documents.index'))->assertOk();
        $this->get(route('admin.users.index'))->assertOk();
    }

    public function test_superadmin_legacy_url_redirects_301_to_admin(): void
    {
        $this->actingAs($this->superAdmin);
        $response = $this->get('/superadmin/destinasi');
        $response->assertStatus(301);
        $response->assertRedirect('/admin/destinasi');
    }

    public function test_superadmin_can_moderate_destination(): void
    {
        $category = DestinationCategory::first() ?? DestinationCategory::create(['name' => 'Alam', 'slug' => 'alam']);
        $province = Province::first() ?? Province::create(['name' => 'DIY', 'slug' => 'diy']);
        $policy = CancellationPolicy::first() ?? CancellationPolicy::create([
            'name' => 'Standar',
            'days_before' => 7,
            'refund_percentage' => 100,
        ]);

        $destination = Destination::create([
            'partner_id' => $this->partner->id,
            'category_id' => $category->id,
            'province_id' => $province->id,
            'cancellation_policy_id' => $policy->id,
            'name' => 'Desa Wisata Test Admin',
            'slug' => 'desa-wisata-test-admin',
            'status' => DestinationStatus::DRAFT,
            'city' => 'Sleman',
            'address' => 'Jl. Kaliurang',
        ]);

        Package::create([
            'destination_id' => $destination->id,
            'name' => 'Paket Edukasi Test',
            'slug' => 'paket-edukasi-test',
            'description' => 'Deskripsi paket edukasi',
            'price_per_pax' => 50000,
            'min_participants' => 10,
            'duration_days' => 1,
            'inquiry_types' => ['study_tour'],
            'is_active' => true,
        ]);

        $this->actingAs($this->superAdmin);

        $response = $this->post(route('admin.destinations.updateStatus', $destination->id), [
            'action' => 'approve',
            'notes' => 'Disetujui untuk publikasi.',
        ]);

        $response->assertRedirect();
        $this->assertEquals(DestinationStatus::PUBLISHED, $destination->fresh()->status);
    }

    public function test_superadmin_can_suspend_and_activate_user(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->post(route('admin.users.toggleStatus', $this->buyer), [
            'status' => 'suspended',
            'reason' => 'Pelanggaran syarat & ketentuan.',
        ]);

        $response->assertRedirect();
        $this->assertEquals('suspended', $this->buyer->fresh()->status);

        $response = $this->post(route('admin.users.toggleStatus', $this->buyer), [
            'status' => 'active',
        ]);
        $response->assertRedirect();
        $this->assertEquals('active', $this->buyer->fresh()->status);
    }

    public function test_superadmin_can_update_settings(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->post(route('admin.settings.update'), [
            'company_name' => 'Destinara Education Travel',
            'min_lead_days' => '10',
        ]);

        $response->assertRedirect();
        $settings = app(SettingsRepository::class);
        $this->assertEquals('Destinara Education Travel', $settings->get('company_name'));
        $this->assertEquals(10, (int) $settings->get('min_lead_days'));
    }

    public function test_health_check_endpoint_renders_live_status(): void
    {
        Http::fake([
            'https://wa.vexahostcloud.my.id/api/v1/health' => Http::response(['status' => 'ok'], 200),
            'https://api.xendit.co/balance' => Http::response(['balance' => 1000000], 200),
        ]);

        $this->actingAs($this->superAdmin);
        $response = $this->get(route('admin.health.index'));
        $response->assertOk();
        $response->assertSee('Diagnostik', false);
        $response->assertSee('Database Relasional (MySQL)', false);
    }

    public function test_settings_test_connection_endpoints(): void
    {
        Http::fake([
            'https://wa.vexahostcloud.my.id/api/v1/*' => Http::response(['status' => 'connected'], 200),
            'https://api.xendit.co/*' => Http::response(['balance' => 5000000], 200),
            'https://api.sandbox.midtrans.com/*' => Http::response(['status_code' => '200'], 200),
        ]);
        Mail::fake();

        $this->actingAs($this->superAdmin);

        // WhatsApp test
        $response = $this->post(route('admin.settings.test', 'whatsapp'));
        $response->assertRedirect();

        // Mail test
        $response = $this->post(route('admin.settings.test', 'mail'), ['test_email' => 'admin@destinara.id']);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Xendit test
        $response = $this->post(route('admin.settings.test', 'xendit'));
        $response->assertRedirect();

        // Midtrans test
        $response = $this->post(route('admin.settings.test', 'midtrans'));
        $response->assertRedirect();
    }

    public function test_settings_group_permissions_and_validation(): void
    {
        $this->actingAs($this->superAdmin);

        // Invalid validation should fail (e.g. min_lead_days must be between 1 and 90)
        $response = $this->post(route('admin.settings.update'), [
            'min_lead_days' => 999,
        ]);
        $response->assertSessionHasErrors(['min_lead_days']);
    }

    public function test_all_setting_keys_used_in_codebase_exist_in_registry(): void
    {
        $registeredKeys = collect(config('destinara.settings'))->pluck('key')->all();

        $scanDirs = [
            app_path(),
            resource_path('views'),
        ];

        $usedKeys = [];

        foreach ($scanDirs as $dir) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
            foreach ($iterator as $file) {
                if ($file->isDir() || $file->getFilename() === 'helpers.php' || ! in_array($file->getExtension(), ['php', 'blade'])) {
                    continue;
                }
                $content = file_get_contents($file->getPathname());
                if (preg_match_all("/setting\(\s*['\"]([a-zA-Z0-9_\.]+)['\"]/", $content, $matches)) {
                    foreach ($matches[1] as $k) {
                        $usedKeys[$k] = true;
                    }
                }
            }
        }

        $usedKeyList = array_keys($usedKeys);
        $orphanKeys = array_diff($usedKeyList, $registeredKeys);

        $this->assertEmpty(
            $orphanKeys,
            'Orphan setting keys found in codebase that are missing from config/destinara.php: '.implode(', ', $orphanKeys)
        );
    }

    public function test_settings_repository_distinguishes_unset_from_deliberately_emptied(): void
    {
        $repo = app(SettingsRepository::class);

        // 1. Condition "Belum pernah diset": row does not exist in DB -> returns registry default
        Setting::where('key', 'smtp_host')->delete();
        $repo->clearCache();

        $this->assertEquals('smtp-relay.brevo.com', $repo->get('smtp_host'));
        $this->assertEquals('smtp-relay.brevo.com', $repo->group('email')['smtp_host']);

        // 2. Condition "Sengaja dikosongkan": row exists with empty string -> returns ''
        $repo->set('smtp_host', '');

        $this->assertSame('', $repo->get('smtp_host'));
        $this->assertSame('', $repo->group('email')['smtp_host']);
    }

    public function test_runtime_config_switches_to_smtp_only_when_fully_configured(): void
    {
        $provider = new RuntimeConfigServiceProvider($this->app);
        $repo = app(SettingsRepository::class);

        // 1. Incomplete SMTP settings (missing password) -> defaults to MAIL_MAILER / log
        $repo->set('smtp_host', 'smtp.brevo.com');
        $repo->set('smtp_username', 'user@example.com');
        $repo->set('smtp_password', '');

        $provider->applyRuntimeConfig($repo);
        $this->assertEquals(env('MAIL_MAILER', 'log'), config('mail.default'));

        // 2. Complete SMTP settings (host, user, pass all set) -> switches to 'smtp'
        $repo->set('smtp_host', 'smtp.brevo.com');
        $repo->set('smtp_username', 'user@example.com');
        $repo->set('smtp_password', 'secret_pass_xyz');

        $provider->applyRuntimeConfig($repo);
        $this->assertEquals('smtp', config('mail.default'));
        $this->assertEquals('smtp.brevo.com', config('mail.mailers.smtp.host'));
    }

    public function test_admin_booking_reschedule_modal_payload_and_validation(): void
    {
        $category = DestinationCategory::firstOrCreate(['slug' => 'alam'], ['name' => 'Wisata Alam', 'is_active' => true]);
        $province = Province::firstOrCreate(['code' => '32'], ['name' => 'Jawa Barat']);

        $destination = Destination::create([
            'partner_id' => $this->partner->id,
            'category_id' => $category->id,
            'province_id' => $province->id,
            'name' => 'Kawasan Edukasi Geologi',
            'slug' => 'kawasan-edukasi-geologi',
            'status' => DestinationStatus::PUBLISHED,
            'city' => 'Bandung',
            'address' => 'Jl. Geologi No. 1',
            'default_daily_capacity' => 100,
        ]);

        $package = Package::create([
            'destination_id' => $destination->id,
            'name' => 'Paket Vulkanologi',
            'slug' => 'paket-vulkanologi',
            'price_per_pax' => 60000,
            'min_pax' => 10,
            'max_pax' => 50,
            'inquiry_types' => ['study_tour'],
            'is_active' => true,
        ]);

        $institution = \App\Models\Institution::create([
            'user_id' => $this->buyer->id,
            'institution_name' => 'SMA Negeri 3 Bandung',
            'pic_name' => 'Dra. Ratna',
            'pic_phone' => '081234567890',
            'pic_email' => 'ratna@sman3bdg.sch.id',
        ]);

        $booking = \App\Models\Booking::create([
            'booking_code' => 'DST-RESCHED-001',
            'user_id' => $this->buyer->id,
            'institution_id' => $institution->id,
            'destination_id' => $destination->id,
            'package_id' => $package->id,
            'inquiry_type' => 'study_tour',
            'status' => \App\Enums\BookingStatus::DIKONFIRMASI,
            'participant_count' => 20,
            'total_amount' => 1200000,
            'subtotal_amount' => 1200000,
            'insurance_amount' => 40000,
            'platform_fee_amount' => 25000,
            'commission_percent' => 10,
            'commission_amount' => 120000,
            'partner_net_amount' => 1080000,
            'planned_date_start' => now()->addDays(7)->toDateString(),
            'planned_date_end' => now()->addDays(7)->toDateString(),
        ]);

        // 1. Validation error when new_date is before min_lead_days (e.g. tomorrow when min lead is 3 days)
        setting(['min_lead_days' => 3]);
        $response = $this->actingAs($this->superAdmin)->post("/admin/booking/{$booking->id}/status", [
            'action' => 'reschedule',
            'new_date' => now()->addDays(1)->toDateString(),
            'reschedule_reason' => 'Pergeseran jadwal sekolah',
        ]);
        $response->assertSessionHasErrors(['new_date']);
        $newDateError = session('errors')->first('new_date');
        $this->assertStringStartsWith('Tanggal baru harus', $newDateError);

        // 2. Validation error when reschedule_reason is missing
        $responseMissingReason = $this->actingAs($this->superAdmin)->post("/admin/booking/{$booking->id}/status", [
            'action' => 'reschedule',
            'new_date' => now()->addDays(10)->toDateString(),
        ]);
        $responseMissingReason->assertSessionHasErrors(['reschedule_reason']);

        // 3. Valid modal payload successfully updates planned_date_start
        $targetDate = now()->addDays(14)->toDateString();
        $successResponse = $this->actingAs($this->superAdmin)->post("/admin/booking/{$booking->id}/status", [
            'action' => 'reschedule',
            'new_date' => $targetDate,
            'reschedule_reason' => 'Permintaan komite sekolah pergeseran hari jumat',
        ]);

        $successResponse->assertRedirect();
        $successResponse->assertSessionHas('success');

        $booking->refresh();
        $this->assertEquals($targetDate, $booking->planned_date_start->toDateString());
    }

    public function test_clean_static_claims_and_verify_real_data_b5(): void
    {
        $this->actingAs($this->superAdmin);

        $category = DestinationCategory::first() ?? DestinationCategory::factory()->create();
        $policy = CancellationPolicy::first() ?? CancellationPolicy::factory()->create();
        $province = Province::first() ?? Province::factory()->create();

        $destination = Destination::create([
            'partner_id' => $this->partner->id,
            'category_id' => $category->id,
            'name' => 'Kampung Adat Sinar Resmi',
            'slug' => 'kampung-adat-sinar-resmi',
            'province_id' => $province->id,
            'city' => 'Sukabumi',
            'address' => 'Jl. Kasepuhan No. 1',
            'short_description' => 'Eksplorasi budaya kasepuhan adat',
            'description' => 'Deskripsi nyata tapak adat mandiri yang spesifik.',
            'status' => DestinationStatus::PENDING_REVIEW,
            'cancellation_policy_id' => $policy->id,
            'default_daily_capacity' => 80,
        ]);

        // 1. Admin Destination Verification page
        $verifyRes = $this->get(route('admin.destinations.verify', ['status' => 'pending_review']));
        $verifyRes->assertOk();
        $verifyRes->assertDontSee('Protokol FPIC Adat Sah');
        $verifyRes->assertDontSee('Akses Bus Besar Terverifikasi');
        $verifyRes->assertDontSee('Surat Penetapan Pengelola');
        $verifyRes->assertSee('Foto');
        $verifyRes->assertSee('Fasilitas');
        $verifyRes->assertSee('Dokumen Legalitas');
        $verifyRes->assertSee('PIC:');

        // 2. Public destination show page (published)
        $destination->update(['status' => DestinationStatus::PUBLISHED]);
        $showRes = $this->get(route('destinasi.show', $destination->slug));
        $showRes->assertOk();
        $showRes->assertDontSee('Protokol FPIC Sah');
        $showRes->assertDontSee('Tapak Baru Terverifikasi');
        $showRes->assertDontSee('Dokumentasi Lapangan Terverifikasi');
        $showRes->assertDontSee('merupakan ruang hidup komunal');
        $showRes->assertSee('Deskripsi nyata tapak adat mandiri yang spesifik.');

        // 3. Cara Kerja public page
        $caraKerjaRes = $this->get(route('cara-kerja'));
        $caraKerjaRes->assertOk();
        $caraKerjaRes->assertDontSee('tapak terakreditasi');
        $caraKerjaRes->assertSee('destinasi terkurasi');
    }

    public function test_date_validation_rule_replacers_v8_3(): void
    {
        $v1 = \Illuminate\Support\Facades\Validator::make(
            ['start_date' => '2026-10-20', 'end_date' => '2026-10-10'],
            ['end_date' => 'after_or_equal:start_date']
        );
        $this->assertEquals(
            'Tanggal selesai harus berupa tanggal setelah atau sama dengan tanggal mulai.',
            $v1->errors()->first('end_date')
        );

        $v2 = \Illuminate\Support\Facades\Validator::make(
            ['starts_at' => '2026-10-20', 'ends_at' => '2026-10-10'],
            ['ends_at' => 'after_or_equal:starts_at']
        );
        $this->assertEquals(
            'Waktu berakhir harus berupa tanggal setelah atau sama dengan waktu berlaku mulai.',
            $v2->errors()->first('ends_at')
        );

        $v3 = \Illuminate\Support\Facades\Validator::make(
            ['new_date' => '2026-10-10'],
            ['new_date' => 'after_or_equal:2026-10-22']
        );
        $this->assertEquals(
            'Tanggal baru harus berupa tanggal setelah atau sama dengan 22 Oktober 2026.',
            $v3->errors()->first('new_date')
        );
    }

    public function test_super_admin_privilege_escalation_and_protection_rules_k_1(): void
    {
        // 1. Setup a custom staff role with internal_users.manage and roles.manage permissions
        $customRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'hr_manager', 'guard_name' => 'web']);
        $customRole->givePermissionTo(['internal_users.manage', 'roles.manage']);

        $staffUser = User::factory()->create([
            'account_type' => AccountType::INTERNAL,
            'status' => 'active',
        ]);
        $staffUser->assignRole('hr_manager');

        // Target super admin (the existing one from setUp)
        $superAdmin = $this->superAdmin;

        // A. Staff attempts to create super_admin user -> must 403
        $createSaResponse = $this->actingAs($staffUser)->post(route('admin.team.user.store'), [
            'name' => 'Hacker Admin',
            'email' => 'hacker@destinara.test',
            'phone' => '081234567890',
            'password' => 'Secret123456!',
            'role' => 'super_admin',
        ]);
        $createSaResponse->assertStatus(403);

        // B. Staff attempts to promote self to super_admin -> must 403
        $selfPromoteResponse = $this->actingAs($staffUser)->post(route('admin.team.user.update', $staffUser->id), [
            'name' => $staffUser->name,
            'email' => $staffUser->email,
            'role' => 'super_admin',
            'status' => 'active',
        ]);
        $selfPromoteResponse->assertStatus(403);

        // C. Staff attempts to change self status to suspended -> must 403
        $selfStatusResponse = $this->actingAs($staffUser)->post(route('admin.team.user.update', $staffUser->id), [
            'name' => $staffUser->name,
            'email' => $staffUser->email,
            'role' => 'hr_manager',
            'status' => 'suspended',
        ]);
        $selfStatusResponse->assertStatus(403);

        // D. Staff attempts to update existing super admin -> must 403
        $updateSaResponse = $this->actingAs($staffUser)->post(route('admin.team.user.update', $superAdmin->id), [
            'name' => 'Changed Name',
            'email' => $superAdmin->email,
            'role' => 'hr_manager',
            'status' => 'active',
        ]);
        $updateSaResponse->assertStatus(403);

        // E. Staff attempts to delete super admin -> must 403
        $deleteSaResponse = $this->actingAs($staffUser)->post(route('admin.team.user.delete', $superAdmin->id));
        $deleteSaResponse->assertStatus(403);

        // F. Role super_admin cannot be updated via updateRole -> must 403
        $saRole = \Spatie\Permission\Models\Role::where('name', 'super_admin')->firstOrFail();
        $updateSaRoleRes = $this->actingAs($superAdmin)->post(route('admin.team.role.update', $saRole->id), [
            'permissions' => [],
        ]);
        $updateSaRoleRes->assertStatus(403);

        // G. Last super admin protection: cannot suspend, demote, or delete the only super admin
        $this->assertEquals(1, User::where('account_type', AccountType::INTERNAL)->where('status', 'active')->role('super_admin')->count());

        // Attempt suspend
        $suspendLastSaRes = $this->actingAs($superAdmin)->post(route('admin.team.user.update', $superAdmin->id), [
            'name' => $superAdmin->name,
            'email' => $superAdmin->email,
            'role' => 'super_admin',
            'status' => 'suspended',
        ]);
        $suspendLastSaRes->assertSessionHas('error');
        $this->assertEquals('active', $superAdmin->fresh()->status);

        // Attempt demote
        $demoteLastSaRes = $this->actingAs($superAdmin)->post(route('admin.team.user.update', $superAdmin->id), [
            'name' => $superAdmin->name,
            'email' => $superAdmin->email,
            'role' => 'hr_manager',
            'status' => 'active',
        ]);
        $demoteLastSaRes->assertSessionHas('error');
        $this->assertTrue($superAdmin->fresh()->hasRole('super_admin'));

        // Attempt delete
        $deleteLastSaRes = $this->actingAs($superAdmin)->post(route('admin.team.user.delete', $superAdmin->id));
        $deleteLastSaRes->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $superAdmin->id]);

        // H. Super admin CAN create another super admin, and then can update
        $newSaRes = $this->actingAs($superAdmin)->post(route('admin.team.user.store'), [
            'name' => 'Second Super Admin',
            'email' => 'second_sa@destinara.test',
            'phone' => '081234567899',
            'password' => 'Password123!',
            'role' => 'super_admin',
        ]);
        $newSaRes->assertRedirect();
        $secondSa = User::where('email', 'second_sa@destinara.test')->firstOrFail();
        $this->assertTrue($secondSa->hasRole('super_admin'));

        // Now that there are 2 super admins, one can be demoted by a super admin
        $demoteRes = $this->actingAs($superAdmin)->post(route('admin.team.user.update', $secondSa->id), [
            'name' => $secondSa->name,
            'email' => $secondSa->email,
            'role' => 'hr_manager',
            'status' => 'active',
        ]);
        $demoteRes->assertRedirect();
        $this->assertTrue($secondSa->fresh()->hasRole('hr_manager'));
    }

    public function test_admin_user_controller_restrictions_and_partner_suspend_permission_k_2(): void
    {
        $operasional = User::factory()->create([
            'account_type' => AccountType::INTERNAL,
            'status' => 'active',
        ]);
        $operasional->assignRole('admin_operasional');

        // 1. admin_operasional cannot toggleStatus on super admin / internal staff -> 403
        $toggleSaRes = $this->actingAs($operasional)->post(route('admin.users.toggleStatus', $this->superAdmin->id), [
            'status' => 'suspended',
            'reason' => 'Coba suspend super admin',
        ]);
        $toggleSaRes->assertStatus(403);

        // 2. admin_operasional cannot send password reset or verify email for internal staff -> 403
        $resetSaRes = $this->actingAs($operasional)->post(route('admin.users.sendPasswordReset', $this->superAdmin->id));
        $resetSaRes->assertStatus(403);

        $verifySaRes = $this->actingAs($operasional)->post(route('admin.users.verifyEmail', $this->superAdmin->id));
        $verifySaRes->assertStatus(403);

        // 3. admin_operasional CAN toggle status of buyer -> 302
        $toggleBuyerRes = $this->actingAs($operasional)->post(route('admin.users.toggleStatus', $this->buyer->id), [
            'status' => 'suspended',
            'reason' => 'Pelanggaran akun pembeli.',
        ]);
        $toggleBuyerRes->assertRedirect();
        $this->assertEquals('suspended', $this->buyer->fresh()->status);

        // 4. Partner suspension requires partners.suspend permission
        $staffWithoutPartnerSuspend = User::factory()->create([
            'account_type' => AccountType::INTERNAL,
            'status' => 'active',
        ]);
        $roleWithoutPartnerSuspend = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'buyer_manager', 'guard_name' => 'web']);
        $roleWithoutPartnerSuspend->syncPermissions(['users.manage', 'users.view']);
        $staffWithoutPartnerSuspend->assignRole($roleWithoutPartnerSuspend);

        $suspendPartnerDenied = $this->actingAs($staffWithoutPartnerSuspend)->post(route('admin.users.toggleStatus', $this->partner->id), [
            'status' => 'suspended',
            'reason' => 'Pelanggaran mitra.',
        ]);
        $suspendPartnerDenied->assertStatus(403);

        // Super Admin (or user with partners.suspend) can suspend partner
        $suspendPartnerAllowed = $this->actingAs($this->superAdmin)->post(route('admin.users.toggleStatus', $this->partner->id), [
            'status' => 'suspended',
            'reason' => 'Pelanggaran mitra sah.',
        ]);
        $suspendPartnerAllowed->assertRedirect();
        $this->assertEquals('suspended', $this->partner->fresh()->status);
    }
}

