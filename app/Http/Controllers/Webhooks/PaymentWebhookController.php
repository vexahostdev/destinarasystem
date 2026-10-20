<?php

namespace App\Http\Controllers\Webhooks;

use App\Domain\Payment\Drivers\MidtransGateway;
use App\Domain\Payment\Drivers\XenditGateway;
use App\Domain\Payment\Services\PaymentService;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentWebhookLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    /**
     * Handle Xendit Invoice Callback (SPEC §9.2).
     */
    public function handleXendit(
        Request $request,
        XenditGateway $gateway,
        PaymentService $paymentService
    ): JsonResponse {
        $payload = $request->all();
        $externalId = $payload['external_id'] ?? null;
        $invoiceId = $payload['id'] ?? 'unknown';
        $statusStr = strtoupper($payload['status'] ?? 'unknown');
        $eventId = "{$invoiceId}-{$statusStr}";

        $isValid = $gateway->verifyWebhook($request);

        if (! $isValid) {
            PaymentWebhookLog::create([
                'gateway' => 'xendit',
                'event_id' => $eventId,
                'external_id' => $externalId,
                'payload' => $payload,
                'signature_valid' => false,
                'error' => 'Header x-callback-token tidak cocok atau tidak ada.',
            ]);

            return response()->json(['message' => 'Invalid callback token.'], 401);
        }

        // Idempotency check (SPEC §9.5)
        $existingLog = PaymentWebhookLog::where('gateway', 'xendit')
            ->where('event_id', $eventId)
            ->first();

        if ($existingLog && $existingLog->processed_at !== null) {
            return response()->json(['message' => 'Event already processed.'], 200);
        }

        $log = $existingLog ?? PaymentWebhookLog::create([
            'gateway' => 'xendit',
            'event_id' => $eventId,
            'external_id' => $externalId,
            'payload' => $payload,
            'signature_valid' => true,
        ]);

        try {
            $webhookResult = $gateway->parseWebhook($request);
            $payment = Payment::where('external_id', $webhookResult->externalId)->first();

            if (! $payment) {
                $log->update([
                    'processed_at' => now(),
                    'error' => "Data payment dengan external_id [{$webhookResult->externalId}] tidak ditemukan.",
                ]);

                return response()->json(['message' => 'Payment not found.'], 404);
            }

            if ($webhookResult->status === PaymentStatus::PAID) {
                $paymentService->processPaymentSuccess($payment, $webhookResult);
            } elseif ($webhookResult->status === PaymentStatus::EXPIRED) {
                $paymentService->processPaymentExpiry($payment);
            }

            $log->update([
                'processed_at' => now(),
                'result' => "Payment external_id [{$payment->external_id}] status [{$webhookResult->status->value}] berhasil diproses.",
            ]);

            return response()->json(['message' => 'Webhook processed successfully.'], 200);
        } catch (\Throwable $e) {
            Log::error('Error processing Xendit webhook: '.$e->getMessage(), ['exception' => $e]);
            $log->update([
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Webhook processing error.'], 500);
        }
    }

    /**
     * Handle Midtrans Snap Notification (SPEC §9.3).
     */
    public function handleMidtrans(
        Request $request,
        MidtransGateway $gateway,
        PaymentService $paymentService
    ): JsonResponse {
        $payload = $request->all();
        $orderId = $payload['order_id'] ?? null;
        $transactionId = $payload['transaction_id'] ?? $orderId;
        $statusStr = $payload['transaction_status'] ?? 'unknown';
        $eventId = "midtrans-{$transactionId}-{$statusStr}";

        $isValid = $gateway->verifyWebhook($request);

        if (! $isValid) {
            PaymentWebhookLog::create([
                'gateway' => 'midtrans',
                'event_id' => $eventId,
                'external_id' => $orderId,
                'payload' => $payload,
                'signature_valid' => false,
                'error' => 'Signature key tidak valid.',
            ]);

            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        // Idempotency check
        $existingLog = PaymentWebhookLog::where('gateway', 'midtrans')
            ->where('event_id', $eventId)
            ->first();

        if ($existingLog && $existingLog->processed_at !== null) {
            return response()->json(['message' => 'Event already processed.'], 200);
        }

        $log = $existingLog ?? PaymentWebhookLog::create([
            'gateway' => 'midtrans',
            'event_id' => $eventId,
            'external_id' => $orderId,
            'payload' => $payload,
            'signature_valid' => true,
        ]);

        try {
            $webhookResult = $gateway->parseWebhook($request);
            $payment = Payment::where('external_id', $webhookResult->externalId)->first();

            if (! $payment) {
                $log->update([
                    'processed_at' => now(),
                    'error' => "Data payment dengan order_id [{$webhookResult->externalId}] tidak ditemukan.",
                ]);

                return response()->json(['message' => 'Payment not found.'], 404);
            }

            if ($webhookResult->status === PaymentStatus::PAID) {
                $paymentService->processPaymentSuccess($payment, $webhookResult);
            } elseif ($webhookResult->status === PaymentStatus::EXPIRED) {
                $paymentService->processPaymentExpiry($payment);
            }

            $log->update([
                'processed_at' => now(),
                'result' => "Payment order_id [{$payment->external_id}] status [{$webhookResult->status->value}] berhasil diproses.",
            ]);

            return response()->json(['message' => 'Webhook processed successfully.'], 200);
        } catch (\Throwable $e) {
            Log::error('Error processing Midtrans webhook: '.$e->getMessage(), ['exception' => $e]);
            $log->update([
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Webhook processing error.'], 500);
        }
    }
}
