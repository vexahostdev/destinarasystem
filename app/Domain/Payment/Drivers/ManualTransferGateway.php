<?php

namespace App\Domain\Payment\Drivers;

use App\Domain\Payment\Contracts\PaymentGateway;
use App\Domain\Payment\Data\ChargeResult;
use App\Domain\Payment\Data\WebhookResult;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Request;

class ManualTransferGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'manual_transfer';
    }

    public function createCharge(Payment $payment, Booking $booking): ChargeResult
    {
        return new ChargeResult(
            checkoutUrl: route('buyer.booking.show', $booking->booking_code),
            gatewayReference: $payment->external_id,
            expiresAt: $booking->expires_at,
            raw: [
                'accounts' => setting('manual_transfer_accounts', []),
                'instructions' => 'Silakan transfer ke rekening resmi Destinara dan unggah bukti transfer.',
            ]
        );
    }

    public function verifyWebhook(Request $request): bool
    {
        return false;
    }

    public function parseWebhook(Request $request): WebhookResult
    {
        return new WebhookResult(
            eventId: 'manual-' . uniqid(),
            externalId: $request->input('external_id', ''),
            status: PaymentStatus::PENDING
        );
    }

    public function fetchStatus(Payment $payment): WebhookResult
    {
        return new WebhookResult(
            eventId: "fetch-manual-{$payment->id}",
            externalId: $payment->external_id,
            status: $payment->status,
            amount: (int) $payment->amount,
            paidAt: $payment->paid_at,
            paymentMethod: 'manual_transfer',
            paymentChannel: 'bank_transfer',
            gatewayReference: $payment->gateway_reference,
            raw: ['proof_path' => $payment->proof_path]
        );
    }

    public function expire(Payment $payment): void
    {
        // No-op for manual transfer, status handled by booking state machine
    }

    public function testConnection(): bool
    {
        $accounts = setting('manual_transfer_accounts', []);

        return is_array($accounts) && count($accounts) > 0;
    }
}
