<?php

namespace Tests\Feature;

use App\Domain\Notification\Clients\VexaHostClient;
use App\Domain\Notification\Exceptions\NonRetryableNotificationException;
use App\Domain\Notification\Services\NotificationDispatcher;
use App\Domain\Notification\Services\TemplateRenderer;
use App\Enums\AccountType;
use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Jobs\SendNotificationJob;
use App\Mail\TemplatedMail;
use App\Models\NotificationLog;
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
}
