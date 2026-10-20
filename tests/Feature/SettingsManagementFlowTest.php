<?php

namespace Tests\Feature;

use App\Domain\Settings\SettingsRepository;
use App\Enums\AccountType;
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

    public function test_admin_can_save_settings_with_structured_ui_payload(): void
    {
        // 1. Payment with array checkboxes and array repeater
        $paymentPayload = [
            'group' => 'payment',
            'default_gateway' => 'manual_transfer',
            'enabled_gateways' => ['manual_transfer', 'xendit'],
            'min_payment_amount' => 20000,
            'xendit_mode' => 'test',
            'midtrans_mode' => 'sandbox',
            'manual_transfer_accounts' => [
                [
                    'bank' => 'BCA',
                    'number' => '9988776655',
                    'holder' => 'PT DESTINARA CHAKRAWAL ARTHA',
                ],
                [
                    'bank' => 'Mandiri',
                    'number' => '1122334455',
                    'holder' => 'PT DESTINARA CHAKRAWAL ARTHA',
                ],
            ],
        ];

        $resPayment = $this->actingAs($this->admin)->post(route('admin.settings.update'), $paymentPayload);
        $resPayment->assertRedirect(route('admin.settings.index', ['group' => 'payment']));
        $resPayment->assertSessionHas('success');

        $repo = app(SettingsRepository::class);
        $enabled = $repo->get('enabled_gateways');
        $this->assertEquals(['manual_transfer', 'xendit'], $enabled);
        $accounts = $repo->get('manual_transfer_accounts');
        $this->assertCount(2, $accounts);
        $this->assertEquals('9988776655', $accounts[0]['number']);
        $this->assertEquals('BCA', $accounts[0]['bank']);

        // 2. Notification with structured alert email and whatsapp repeaters
        $notifPayload = [
            'group' => 'notification',
            'admin_alert_emails' => [
                'ops@destinara.test',
                'finance@destinara.test',
            ],
            'admin_alert_whatsapp' => [
                '6281234567890',
            ],
        ];

        $resNotif = $this->actingAs($this->admin)->post(route('admin.settings.update'), $notifPayload);
        $resNotif->assertRedirect(route('admin.settings.index', ['group' => 'notification']));
        $resNotif->assertSessionHas('success');

        $emails = $repo->get('admin_alert_emails');
        $this->assertEquals(['ops@destinara.test', 'finance@destinara.test'], $emails);
        $wa = $repo->get('admin_alert_whatsapp');
        $this->assertEquals(['6281234567890'], $wa);
    }

    public function test_go_live_checklist_marks_admin_alert_emails_unhealthy_when_empty_and_healthy_when_filled(): void
    {
        $repo = app(SettingsRepository::class);

        // Case 1: Empty default -> Unhealthy
        $repo->set('admin_alert_emails', []);
        $resEmpty = $this->actingAs($this->admin)->get(route('admin.health.index'));
        $resEmpty->assertOk();
        $resEmpty->assertSee('Email Alert Darurat Admin (Checklist Go-Live)');
        $resEmpty->assertSee('Kosong! Wajib diisi minimal 1 email admin', false);

        // Case 2: Populated -> Healthy
        $repo->set('admin_alert_emails', ['superadmin@destinara.test']);
        $resFilled = $this->actingAs($this->admin)->get(route('admin.health.index'));
        $resFilled->assertOk();
        $resFilled->assertSee('Terisi: superadmin@destinara.test');
    }

    public function test_validation_error_preserves_active_settings_tab(): void
    {
        $payload = [
            'group' => 'payment',
            'min_payment_amount' => 'not-a-number',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.settings.update'), $payload);

        $response->assertRedirect(route('admin.settings.index', ['group' => 'payment']));
        $response->assertSessionHasErrors(['min_payment_amount']);
    }

    public function test_secret_settings_never_rendered_in_html_retained_on_empty_and_deleted_with_checkbox_k_3(): void
    {
        $repo = app(SettingsRepository::class);
        $repo->set('xendit_secret_key', 'xnd_secret_test_value_1234');

        // 1. Secret is NOT rendered in HTML, status shows masked suffix
        $viewRes = $this->actingAs($this->admin)->get(route('admin.settings.index', ['group' => 'payment']));
        $viewRes->assertOk();
        $viewRes->assertDontSee('xnd_secret_test_value_1234');
        $viewRes->assertSee('Tersimpan (••••1234)');
        $viewRes->assertSee('name="delete_secret_xendit_secret_key"', false);

        // 2. Saving with empty input retains existing secret value
        $updateRes1 = $this->actingAs($this->admin)->post(route('admin.settings.update'), [
            'group' => 'payment',
            'enabled_gateways' => ['xendit'],
            'xendit_secret_key' => '',
        ]);
        $updateRes1->assertRedirect();
        $this->assertEquals('xnd_secret_test_value_1234', $repo->get('xendit_secret_key'));

        // 3. Saving with new value updates and encrypts in DB
        $updateRes2 = $this->actingAs($this->admin)->post(route('admin.settings.update'), [
            'group' => 'payment',
            'enabled_gateways' => ['xendit'],
            'xendit_secret_key' => 'xnd_new_key_abcd_9999',
        ]);
        $updateRes2->assertRedirect();
        $this->assertEquals('xnd_new_key_abcd_9999', $repo->get('xendit_secret_key'));

        $dbRow = \App\Models\Setting::where('key', 'xendit_secret_key')->first();
        $this->assertNotNull($dbRow);
        $this->assertTrue((bool) $dbRow->is_encrypted);
        $this->assertNotEquals('xnd_new_key_abcd_9999', $dbRow->getRawOriginal('value'));

        // 4. Checking delete_secret_... clears the secret value
        $updateRes3 = $this->actingAs($this->admin)->post(route('admin.settings.update'), [
            'group' => 'payment',
            'enabled_gateways' => ['xendit'],
            'delete_secret_xendit_secret_key' => '1',
        ]);
        $updateRes3->assertRedirect();
        $this->assertEquals('', $repo->get('xendit_secret_key'));

        // 5. Viewing empty secret shows "Belum diisi"
        $viewResEmpty = $this->actingAs($this->admin)->get(route('admin.settings.index', ['group' => 'payment']));
        $viewResEmpty->assertOk();
        $viewResEmpty->assertSee('Belum diisi');
    }
}

