<?php

namespace Tests\Feature;

use App\Domain\Notification\Clients\VexaHostClient;
use App\Domain\Notification\Exceptions\NonRetryableNotificationException;
use App\Domain\Notification\Services\NotificationDispatcher;
use App\Domain\Notification\Services\TemplateRenderer;
use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Jobs\SendNotificationJob;
use App\Mail\TemplatedMail;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        setting([
            'wa_enabled' => true,
            'wa_base_url' => 'https://wa.vexahostcloud.my.id/api/v1',
            'wa_api_key' => 'test-wa-key',
            'wa_webhook_secret' => 'test-wa-secret',
        ]);
    }

    public function test_phone_normalization(): void
    {
        $client = app(VexaHostClient::class);

        $this->assertEquals('6281234567890', $client->normalizePhone('081234567890'));
        $this->assertEquals('6281234567890', $client->normalizePhone('+62 812-3456-7890'));
        $this->assertEquals('6281987654321', $client->normalizePhone('0819-8765-4321'));

        $this->expectException(NonRetryableNotificationException::class);
        $client->normalizePhone('12345'); // invalid length
    }

    public function test_template_renderer_replaces_placeholders(): void
    {
        $renderer = app(TemplateRenderer::class);

        $template = 'Halo {pic_name}, booking {booking_code} di {destination_name} telah dibuat.';
        $context = [
            'pic_name' => 'Budi Santoso',
            'booking_code' => 'DST-2610-K8P9',
            'destination_name' => 'Desa Wisata Nglanggeran',
        ];

        $rendered = $renderer->render($template, $context);
        $this->assertEquals('Halo Budi Santoso, booking DST-2610-K8P9 di Desa Wisata Nglanggeran telah dibuat.', $rendered);
    }

    public function test_vexahost_send_text_success_stores_provider_message_id(): void
    {
        Http::fake([
            'https://wa.vexahostcloud.my.id/api/v1/messages/text' => Http::response([
                'success' => true,
                'data' => [
                    'id' => 'msg_vexa_123456',
                    'status' => 'queued',
                ],
            ], 200),
        ]);

        $client = app(VexaHostClient::class);
        $messageId = $client->sendText('081234567890', 'Pesan uji coba');

        $this->assertEquals('msg_vexa_123456', $messageId);
    }

    public function test_vexahost_client_error_422_fails_without_retry(): void
    {
        Http::fake([
            'https://wa.vexahostcloud.my.id/api/v1/messages/text' => Http::response([
                'success' => false,
                'message' => 'The provided phone number is not registered on WhatsApp.',
            ], 422),
        ]);

        $log = NotificationLog::create([
            'event_key' => 'booking.created',
            'channel' => NotificationChannel::WHATSAPP,
            'recipient' => '6281234567890',
            'body' => 'Uji coba error 422',
            'status' => NotificationStatus::QUEUED,
        ]);

        $job = new SendNotificationJob($log->id);
        $job->handle(app(VexaHostClient::class));

        $log->refresh();
        $this->assertEquals(NotificationStatus::FAILED, $log->status);
        $this->assertStringContainsString('422', $log->error);
    }

    public function test_mail_notification_sending(): void
    {
        Mail::fake();

        setting([
            'smtp_host' => 'smtp.destinara.test',
            'smtp_username' => 'destinara_user',
            'smtp_password' => 'secret_pass_123',
        ]);

        $log = NotificationLog::create([
            'event_key' => 'booking.created',
            'channel' => NotificationChannel::MAIL,
            'recipient' => 'buyer@destinara.test',
            'subject' => 'Reservasi Kunjungan Dibuat',
            'body' => '<p>Halo, reservasi Anda berhasil diajukan.</p>',
            'status' => NotificationStatus::QUEUED,
        ]);

        $job = new SendNotificationJob($log->id);
        $job->handle(app(VexaHostClient::class));

        $log->refresh();
        $this->assertEquals(NotificationStatus::SENT, $log->status);

        Mail::assertSent(TemplatedMail::class, function ($mail) use ($log) {
            return $mail->hasTo('buyer@destinara.test') && $mail->subjectText === $log->subject;
        });
    }

    public function test_mail_notification_fails_without_retry_when_smtp_unconfigured(): void
    {
        Mail::fake();

        setting([
            'smtp_host' => 'smtp.destinara.test',
            'smtp_username' => '',
            'smtp_password' => '',
        ]);

        $log = NotificationLog::create([
            'event_key' => 'booking.created',
            'channel' => NotificationChannel::MAIL,
            'recipient' => 'buyer@destinara.test',
            'subject' => 'Reservasi Kunjungan Dibuat',
            'body' => '<p>Halo, reservasi Anda berhasil diajukan.</p>',
            'status' => NotificationStatus::QUEUED,
        ]);

        $job = new SendNotificationJob($log->id);
        $job->handle(app(VexaHostClient::class));

        $log->refresh();
        $this->assertEquals(NotificationStatus::FAILED, $log->status);
        $this->assertEquals('SMTP belum dikonfigurasi', $log->error);

        Mail::assertNothingSent();
    }

    public function test_whatsapp_webhook_signature_and_status_update(): void
    {
        $log = NotificationLog::create([
            'event_key' => 'booking.created',
            'channel' => NotificationChannel::WHATSAPP,
            'recipient' => '6281234567890',
            'body' => 'Testing WA webhook',
            'status' => NotificationStatus::SENT,
            'provider_message_id' => 'msg_vexa_789',
        ]);

        $secret = 'test-wa-secret';
        $payload = [
            'delivery_id' => 'dlv_001',
            'event' => 'message.status',
            'data' => [
                'id' => 'msg_vexa_789',
                'status' => 'delivered',
            ],
        ];

        $rawBody = json_encode($payload);

        // 1. Invalid signature returns 401
        $this->call('POST', '/webhooks/whatsapp', [], [], [], [
            'HTTP_X-VexaHost-Signature' => 'invalid-hash',
            'CONTENT_TYPE' => 'application/json',
        ], $rawBody)->assertStatus(401);

        // 2. Valid HMAC signature returns 204 and updates status
        $validSignature = hash_hmac('sha256', $rawBody, $secret);
        $response = $this->call('POST', '/webhooks/whatsapp', [], [], [], [
            'HTTP_X-VexaHost-Signature' => $validSignature,
            'CONTENT_TYPE' => 'application/json',
        ], $rawBody);

        $response->assertStatus(204);

        $log->refresh();
        $this->assertEquals(NotificationStatus::DELIVERED, $log->status);
        $this->assertNotNull($log->delivered_at);
    }

    public function test_payment_paid_dispatches_to_all_three_audiences_and_channels(): void
    {
        Http::fake([
            'https://wa.vexahostcloud.my.id/*' => Http::response([
                'success' => true,
                'data' => ['id' => 'msg_wa_fake', 'status' => 'queued'],
            ], 200),
        ]);
        Mail::fake();

        setting([
            'admin_alert_emails' => ['admin@destinara.test'],
            'admin_alert_whatsapp' => ['081234567899'],
        ]);

        $buyer = User::factory()->create([
            'account_type' => 'buyer',
            'email' => 'buyer@destinara.test',
            'phone' => '081234567891',
        ]);

        $partner = User::factory()->create([
            'account_type' => 'partner',
            'email' => 'partner@destinara.test',
            'phone' => '081234567892',
        ]);

        $context = [
            'user' => $buyer,
            'partner_user' => $partner,
            'booking_code' => 'DST-PAY-001',
            'total_amount' => '1.500.000',
            'payment_method' => 'BCA Virtual Account',
        ];

        $dispatcher = app(NotificationDispatcher::class);
        $logs = $dispatcher->send('payment.paid', $context, ['buyer', 'partner', 'admin']);

        // 3 audiences x 3 channels = 9 logs
        $this->assertCount(9, $logs);

        // Verify each audience received mail, whatsapp, and database channels
        foreach (['buyer', 'partner', 'admin'] as $aud) {
            $templatesForAud = NotificationTemplate::where('event_key', 'payment.paid')
                ->where('audience', $aud)
                ->pluck('channel')
                ->map(fn ($c) => $c instanceof NotificationChannel ? $c->value : $c)
                ->all();

            $this->assertContains('mail', $templatesForAud);
            $this->assertContains('whatsapp', $templatesForAud);
            $this->assertContains('database', $templatesForAud);
        }
    }

    public function test_disabling_single_template_stops_only_that_channel(): void
    {
        Http::fake([
            'https://wa.vexahostcloud.my.id/*' => Http::response([
                'success' => true,
                'data' => ['id' => 'msg_wa_fake', 'status' => 'queued'],
            ], 200),
        ]);
        Mail::fake();

        setting([
            'admin_alert_emails' => ['admin@destinara.test'],
            'admin_alert_whatsapp' => ['081234567899'],
        ]);

        $buyer = User::factory()->create([
            'account_type' => 'buyer',
            'email' => 'buyer-toggle@destinara.test',
            'phone' => '081234567893',
        ]);

        $partner = User::factory()->create([
            'account_type' => 'partner',
            'email' => 'partner-toggle@destinara.test',
            'phone' => '081234567894',
        ]);

        // Deactivate ONLY buyer whatsapp template
        NotificationTemplate::where('event_key', 'payment.paid')
            ->where('audience', 'buyer')
            ->where('channel', NotificationChannel::WHATSAPP)
            ->update(['is_active' => false]);

        $context = [
            'user' => $buyer,
            'partner_user' => $partner,
            'booking_code' => 'DST-PAY-TOGGLE',
            'total_amount' => '750.000',
        ];

        $dispatcher = app(NotificationDispatcher::class);
        $logs = $dispatcher->send('payment.paid', $context, ['buyer', 'partner', 'admin']);

        // Out of 9, buyer whatsapp is skipped -> 8 logs
        $this->assertCount(8, $logs);

        $channelsForBuyer = collect($logs)->filter(function ($log) use ($buyer) {
            return $log->recipient === $buyer->email || $log->recipient === (string) $buyer->id || $log->recipient === $buyer->phone;
        })->pluck('channel')->map(fn ($c) => $c instanceof NotificationChannel ? $c->value : $c)->all();

        $this->assertContains('mail', $channelsForBuyer);
        $this->assertContains('database', $channelsForBuyer);
        $this->assertNotContains('whatsapp', $channelsForBuyer);
    }

    public function test_admin_can_manage_and_preview_notification_templates(): void
    {
        $admin = User::where('account_type', 'internal')->first();

        // 1. Create a custom template for a new event/channel combination
        $response = $this->actingAs($admin)->post('/admin/notifikasi/template', [
            'event_key' => 'custom.school_holiday',
            'channel' => 'whatsapp',
            'audience' => 'buyer',
            'subject' => null,
            'body' => 'Promo liburan sekolah untuk {institution_name}!',
            'is_active' => 1,
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $template = NotificationTemplate::where('event_key', 'custom.school_holiday')
            ->where('channel', 'whatsapp')
            ->where('audience', 'buyer')
            ->firstOrFail();

        $this->assertTrue($template->is_active);

        // 2. Toggle active state
        $toggleResponse = $this->actingAs($admin)->post("/admin/notifikasi/template/{$template->id}/toggle");
        $toggleResponse->assertRedirect();
        $this->assertFalse($template->fresh()->is_active);

        // Toggle back to active
        $this->actingAs($admin)->post("/admin/notifikasi/template/{$template->id}/toggle");
        $this->assertTrue($template->fresh()->is_active);

        // 3. Preview with sample data
        $previewResponse = $this->actingAs($admin)->get("/admin/notifikasi/template/{$template->id}/preview");
        $previewResponse->assertOk();
        $previewResponse->assertJsonStructure([
            'event_key',
            'channel',
            'audience',
            'is_active',
            'subject',
            'body',
        ]);
        $previewResponse->assertJsonFragment([
            'event_key' => 'custom.school_holiday',
            'channel' => 'whatsapp',
            'audience' => 'buyer',
        ]);
    }

    public function test_missing_recipient_creates_failed_notification_log_with_reason(): void
    {
        $dispatcher = app(NotificationDispatcher::class);

        // Send an event with empty context where neither user, booking, nor recipient is provided
        $logs = $dispatcher->send('auth.verify_email', [], ['buyer']);

        $this->assertNotEmpty($logs);
        $this->assertDatabaseHas('notification_logs', [
            'event_key' => 'auth.verify_email',
            'status' => 'failed',
            'error' => 'Penerima tidak ditemukan',
        ]);
    }

    public function test_booking_rescheduled_notifications_for_paid_and_confirmed_status_v8_2(): void
    {
        $partner = User::factory()->create([
            'account_type' => 'partner',
            'status' => 'active',
            'name' => 'Mitra Pengelola Merapi',
            'phone' => '081234567890',
        ]);
        \App\Models\PartnerProfile::create([
            'user_id' => $partner->id,
            'organization_name' => 'Pokdarwis Merapi Indah',
            'status' => 'approved',
        ]);

        $buyer = User::factory()->create([
            'account_type' => 'buyer',
            'status' => 'active',
            'name' => 'Ketua Rombongan',
            'phone' => '081234567891',
        ]);
        $institution = \App\Models\Institution::create([
            'user_id' => $buyer->id,
            'institution_name' => 'SMA Negeri 1 Jogja',
            'pic_name' => 'Bambang Sudiro',
            'pic_phone' => '081234567891',
            'pic_email' => 'bambang@sma1.sch.id',
            'pic_position' => 'Wakasek Kesiswaan',
            'address' => 'Jl. C. Simanjuntak',
            'city' => 'Yogyakarta',
            'province' => 'DI Yogyakarta',
        ]);

        $admin = User::factory()->create([
            'account_type' => 'internal',
            'status' => 'active',
        ]);

        $province = \App\Models\Province::first() ?? \App\Models\Province::create(['code' => 'DIY', 'name' => 'DI Yogyakarta']);
        $cat = \App\Models\DestinationCategory::first() ?? \App\Models\DestinationCategory::create(['name' => 'Alam', 'slug' => 'alam']);

        $destination = \App\Models\Destination::create([
            'partner_id' => $partner->id,
            'province_id' => $province->id,
            'category_id' => $cat->id,
            'name' => 'Wisata Bunker Kaliadem',
            'slug' => 'wisata-bunker-kaliadem',
            'city' => 'Sleman',
            'address' => 'Kinahrejo, Hargobinangun, Pakem',
            'status' => \App\Enums\DestinationStatus::PUBLISHED,
        ]);

        $package = \App\Models\Package::create([
            'destination_id' => $destination->id,
            'name' => 'Paket Edukasi Merapi',
            'slug' => 'paket-edukasi-merapi',
            'inquiry_types' => ['study_tour'],
            'price_per_pax' => 100000,
            'min_pax' => 10,
            'max_pax' => 50,
            'duration_days' => 1,
            'is_active' => true,
        ]);

        $booking = \App\Models\Booking::create([
            'booking_code' => 'DST-2610-TEST82',
            'user_id' => $buyer->id,
            'institution_id' => $institution->id,
            'destination_id' => $destination->id,
            'package_id' => $package->id,
            'inquiry_type' => 'study_tour',
            'planned_date_start' => now()->addDays(10)->toDateString(),
            'planned_date_end' => now()->addDays(10)->toDateString(),
            'participant_count' => 20,
            'subtotal_amount' => 2000000,
            'total_amount' => 2000000,
            'platform_fee_amount' => 0,
            'insurance_amount' => 0,
            'discount_amount' => 0,
            'commission_amount' => 200000,
            'partner_net_amount' => 1800000,
            'status' => \App\Enums\BookingStatus::DIBAYAR,
        ]);

        // 1. Condition: Booking DIBAYAR
        event(new \App\Events\BookingRescheduled($booking, '10/11/2026 - 10/11/2026', $admin));

        $buyerLogsPaid = NotificationLog::where('event_key', 'booking.rescheduled')
            ->where('recipient', '!=', (string) $admin->id)
            ->where(function ($q) use ($buyer) {
                $q->where('notifiable_id', $buyer->id)
                    ->orWhere('recipient', $buyer->email)
                    ->orWhere('recipient', '6281234567891');
            })->get();

        $this->assertNotEmpty($buyerLogsPaid);
        foreach ($buyerLogsPaid as $log) {
            $this->assertStringContainsString('menunggu konfirmasi pengelola', $log->body);
            $this->assertStringNotContainsString('telah diterbitkan', $log->body);
        }

        $partnerLogsPaid = NotificationLog::where('event_key', 'booking.rescheduled')
            ->where('recipient', '!=', (string) $admin->id)
            ->where(function ($q) use ($partner) {
                $q->where('notifiable_id', $partner->id)
                    ->orWhere('recipient', $partner->email)
                    ->orWhere('recipient', '6281234567890');
            })->get();

        $this->assertNotEmpty($partnerLogsPaid);
        foreach ($partnerLogsPaid as $log) {
            $this->assertStringContainsString('menunggu konfirmasi pengelola', $log->body);
            $this->assertStringContainsString('konfirmasi', strtolower($log->body));
        }

        // 2. Condition: Booking DIKONFIRMASI
        NotificationLog::query()->delete();
        $booking->status = \App\Enums\BookingStatus::DIKONFIRMASI;
        $booking->save();

        event(new \App\Events\BookingRescheduled($booking, '10/11/2026 - 10/11/2026', $admin));

        $buyerLogsConfirmed = NotificationLog::where('event_key', 'booking.rescheduled')
            ->where('recipient', '!=', (string) $admin->id)
            ->where(function ($q) use ($buyer) {
                $q->where('notifiable_id', $buyer->id)
                    ->orWhere('recipient', $buyer->email)
                    ->orWhere('recipient', '6281234567891');
            })->get();

        $this->assertNotEmpty($buyerLogsConfirmed);
        foreach ($buyerLogsConfirmed as $log) {
            $this->assertStringContainsString('dokumen baru telah diterbitkan', strtolower($log->body));
        }

        $partnerLogsConfirmed = NotificationLog::where('event_key', 'booking.rescheduled')
            ->where('recipient', '!=', (string) $admin->id)
            ->where(function ($q) use ($partner) {
                $q->where('notifiable_id', $partner->id)
                    ->orWhere('recipient', $partner->email)
                    ->orWhere('recipient', '6281234567890');
            })->get();

        $this->assertNotEmpty($partnerLogsConfirmed);
        foreach ($partnerLogsConfirmed as $log) {
            $this->assertStringContainsString('dokumen baru telah diterbitkan', strtolower($log->body));
        }
    }
}
