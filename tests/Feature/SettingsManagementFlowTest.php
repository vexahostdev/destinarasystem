<?php

namespace Tests\Feature;

use App\Domain\Settings\SettingsRepository;
use App\Enums\AccountType;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SettingsManagementFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->admin = User::factory()->create([
            'name' => 'Super Admin Test',
            'email' => 'superadmin.settings@destinara.test',
            'account_type' => AccountType::INTERNAL,
            'status' => 'active',
        ]);
        $this->admin->assignRole('super_admin');
    }

    public function test_default_settings_registry_has_no_dummy_bank_or_fake_legal_name(): void
    {
        $settings = collect(config('destinara.settings'))->keyBy('key');

        $this->assertEquals('PT DESTINARA CHAKRAWAL ARTHA', $settings['company_legal_name']['default']);
        $this->assertNull($settings['company_address']['default']);
        $this->assertNull($settings['company_npwp']['default']);
        $this->assertNull($settings['company_phone']['default']);
        $this->assertEquals([], $settings['manual_transfer_accounts']['default']);
        $this->assertEquals('Direktur Operasional', $settings['doc_signatory_title']['default']);
        $this->assertStringContainsString('PT DESTINARA CHAKRAWAL ARTHA', $settings['doc_footer_text']['default']);
    }

    public function test_settings_index_renders_with_tabs_and_grouped_forms(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.settings.index'));

        $response->assertOk();
        $response->assertSee('Pengaturan Ekosistem');
        $response->assertSee('value="general"', false);
        $response->assertSee('value="payment"', false);
        $response->assertSee('value="email"', false);
    }

    public function test_admin_can_save_settings_group_individually(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.settings.update'), [
            'group' => 'general',
            'company_name' => 'Destinara Edu Tech',
            'company_legal_name' => 'PT DESTINARA CHAKRAWAL ARTHA',
            'brand_color' => '#8C5151',
            'maintenance_mode' => '0',
        ]);

        $response->assertRedirect(route('admin.settings.index', ['group' => 'general']));
        $response->assertSessionHas('success');

        $repo = app(SettingsRepository::class);
        $this->assertEquals('Destinara Edu Tech', $repo->get('company_name'));
        $this->assertEquals('PT DESTINARA CHAKRAWAL ARTHA', $repo->get('company_legal_name'));
        $this->assertFalse((bool) $repo->get('maintenance_mode'));
    }

    public function test_admin_can_save_payment_settings_with_json_strings(): void
    {
        $payload = [
            'group' => 'payment',
            'default_gateway' => 'manual_transfer',
            'enabled_gateways' => json_encode(['manual_transfer']),
            'min_payment_amount' => 15000,
            'xendit_mode' => 'test',
            'midtrans_mode' => 'sandbox',
            'manual_transfer_accounts' => json_encode([
                [
                    'bank' => 'BCA',
                    'number' => '1234567890',
                    'holder' => 'PT DESTINARA CHAKRAWAL ARTHA',
                ],
            ]),
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.settings.update'), $payload);

        $response->assertRedirect(route('admin.settings.index', ['group' => 'payment']));
        $response->assertSessionHas('success');

        $repo = app(SettingsRepository::class);
        $enabled = $repo->get('enabled_gateways');
        $this->assertIsArray($enabled);
        $this->assertEquals(['manual_transfer'], $enabled);

        $accounts = $repo->get('manual_transfer_accounts');
        $this->assertIsArray($accounts);
        $this->assertCount(1, $accounts);
        $this->assertEquals('1234567890', $accounts[0]['number']);
        $this->assertEquals('PT DESTINARA CHAKRAWAL ARTHA', $accounts[0]['holder']);
    }

    public function test_invalid_json_in_settings_returns_validation_error(): void
    {
        $payload = [
            'group' => 'payment',
            'default_gateway' => 'xendit',
            'enabled_gateways' => '{broken json, missing bracket',
            'min_payment_amount' => 10000,
            'xendit_mode' => 'test',
            'midtrans_mode' => 'sandbox',
        ];

        $response = $this->actingAs($this->admin)->from(route('admin.settings.index', ['group' => 'payment']))
            ->post(route('admin.settings.update'), $payload);

        $response->assertRedirect(route('admin.settings.index', ['group' => 'payment']));
        $response->assertSessionHasErrors('enabled_gateways');
    }

    public function test_system_health_respects_enabled_gateways_and_reports_smtp(): void
    {
        Http::fake([
            'https://wa.vexahostcloud.my.id/api/v1/health' => Http::response(['status' => 'connected'], 200),
            'https://api.xendit.co/*' => Http::response(['balance' => 1000000], 200),
        ]);

        $repo = app(SettingsRepository::class);
        // Only xendit is enabled, midtrans disabled
        $repo->set('enabled_gateways', ['xendit']);

        $response = $this->actingAs($this->admin)->get(route('admin.health.index'));

        $response->assertOk();
        $response->assertSee('Midtrans dinonaktifkan di pengaturan pembayaran.', false);
        $response->assertSee('Email SMTP (Brevo / Relay)');
    }
}
