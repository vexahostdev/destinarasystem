<?php

namespace App\Http\Controllers\Webhooks;

use App\Enums\NotificationStatus;
use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use App\Models\WaWebhookLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    /**
     * Handle incoming VexaHost WhatsApp Webhook (SPEC §10.1).
     */
    public function handle(Request $request): Response
    {
        $rawBody = $request->getContent();
        $secret = (string) setting('wa_webhook_secret', '');

        // Verify HMAC-SHA256 signature from raw body
        $receivedSignature = $request->header('X-VexaHost-Signature', '');
        $expectedSignature = hash_hmac('sha256', $rawBody, $secret);

        if (empty($secret) || empty($receivedSignature) || ! hash_equals($expectedSignature, $receivedSignature)) {
            Log::warning('WhatsApp Webhook rejected: Signature mismatch or missing secret.');

            return response()->noContent(401);
        }

        $payload = json_decode($rawBody, true) ?? [];
        $deliveryId = $payload['delivery_id'] ?? ($payload['id'] ?? uniqid('dlv_'));
        $event = $payload['event'] ?? 'unknown';

        // Idempotency check via wa_webhook_logs
        $existingLog = WaWebhookLog::where('delivery_id', $deliveryId)->first();
        if ($existingLog && $existingLog->processed_at !== null) {
            return response()->noContent(204);
        }

        $waLog = $existingLog ?? WaWebhookLog::create([
            'delivery_id' => $deliveryId,
            'event' => $event,
            'payload' => $payload,
            'processed_at' => null,
        ]);

        try {
            if ($event === 'message.status') {
                $providerId = $payload['data']['id'] ?? ($payload['data']['message_id'] ?? null);
                $statusStr = strtolower($payload['data']['status'] ?? '');

                if ($providerId) {
                    $log = NotificationLog::where('provider_message_id', $providerId)->first();
                    if ($log) {
                        $now = now();
                        match ($statusStr) {
                            'sent' => $log->update(['status' => NotificationStatus::SENT, 'sent_at' => $now]),
                            'delivered' => $log->update(['status' => NotificationStatus::DELIVERED, 'delivered_at' => $now]),
                            'read' => $log->update(['status' => NotificationStatus::READ, 'read_at' => $now]),
                            'failed' => $log->update(['status' => NotificationStatus::FAILED, 'error' => $payload['data']['error'] ?? 'Gagal terkirim dari provider']),
                            default => null,
                        };
                    }
                }
            } elseif ($event === 'session.status') {
                $status = $payload['data']['status'] ?? '';
                if ($status === 'disconnected') {
                    Log::critical('VexaHost WhatsApp session is DISCONNECTED!');
                    // Alert admin if configured
                }
            }

            $waLog->update(['processed_at' => now()]);
        } catch (\Throwable $e) {
            Log::error('Error processing WhatsApp webhook: '.$e->getMessage());
        }

        return response()->noContent(204);
    }
}
