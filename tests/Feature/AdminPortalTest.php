<?php

namespace Tests\Feature;

use App\Domain\Settings\SettingsRepository;
use App\Enums\AccountType;
use App\Enums\DestinationStatus;
use App\Models\CancellationPolicy;
use App\Models\Destination;
use App\Models\DestinationCategory;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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
}
