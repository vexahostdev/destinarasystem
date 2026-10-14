<?php

namespace App\Domain\Payment\Drivers;

use App\Domain\Payment\Contracts\PaymentGateway;
use App\Domain\Payment\Data\ChargeResult;
use App\Domain\Payment\Data\WebhookResult;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MidtransGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'midtrans';
    }

    protected function isProduction(): bool
    {
        return setting('midtrans_mode', 'sandbox') === 'production';
    }

    protected function getServerKey(): string
    {
        $key = setting('midtrans_server_key');
        if (empty($key)) {
            throw new RuntimeException('Kredensial Midtrans Server Key belum dikonfigurasi di Pengaturan.');
        }

        return $key;
    }

    protected function getSnapBaseUrl(): string
    {
        return $this->isProduction()
            ? 'https://app.midtrans.com/snap/v1'
            : 'https://app.sandbox.midtrans.com/snap/v1';
    }

    protected function getApiBaseUrl(): string
    {
        return $this->isProduction()
            ? 'https://api.midtrans.com/v2'
            : 'https://api.sandbox.midtrans.com/v2';
    }

    public function createCharge(Payment $payment, Booking $booking): ChargeResult
    {
        $serverKey = $this->getServerKey();
        $buyer = $booking->user;

        $durationMinutes = max(5, (int) round(now()->diffInMinutes($booking->expires_at, false)));

        $items = [];
        foreach ($booking->items as $item) {
            $items[] = [
                'id' => (string) ($item->reference_id ?? $item->id),
                'price' => (int) $item->unit_price,
                'quantity' => $item->quantity,
                'name' => mb_substr($item->name, 0, 50),
            ];
        }

        $payload = [
            'transaction_details' => [
                'order_id' => $payment->external_id,
                'gross_amount' => (int) $payment->amount,
            ],
            'item_details' => $items,
            'customer_details' => [
                'first_name' => $buyer->name,
                'email' => $buyer->email,
                'phone' => $buyer->phone,
            ],
            'expiry' => [
                'unit' => 'minute',
                'duration' => $durationMinutes,
            ],
            'callbacks' => [
                'finish' => route('buyer.booking.show', $booking->booking_code),
            ],
        ];

        $response = Http::withBasicAuth($serverKey, '')
            ->timeout(15)
            ->post("{$this->getSnapBaseUrl()}/transactions", $payload);

        if ($response->failed()) {
            throw new RuntimeException('Gagal membuat transaksi Midtrans: '.$response->body());
        }

        $data = $response->json();

        return new ChargeResult(
            checkoutUrl: $data['redirect_url'],
            gatewayReference: $data['token'] ?? null,
            expiresAt: $booking->expires_at,
            raw: $data
        );
    }

    public function verifyWebhook(Request $request): bool
    {
        $serverKey = $this->getServerKey();
        $orderId = $request->input('order_id', '');
        $statusCode = $request->input('status_code', '');
        $grossAmount = $request->input('gross_amount', '');
        $receivedSignature = $request->input('signature_key', '');

        if (empty($orderId) || empty($statusCode) || empty($grossAmount) || empty($receivedSignature)) {
            return false;
        }

        $expectedSignature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        return hash_equals($expectedSignature, $receivedSignature);
    }

    public function parseWebhook(Request $request): WebhookResult
    {
        $data = $request->all();

        $orderId = $data['order_id'] ?? '';
        $transactionStatus = $data['transaction_status'] ?? '';
        $fraudStatus = $data['fraud_status'] ?? 'accept';
        $transactionId = $data['transaction_id'] ?? $orderId;

        $status = match ($transactionStatus) {
            'capture' => ($fraudStatus === 'challenge') ? PaymentStatus::PENDING : PaymentStatus::PAID,
            'settlement' => PaymentStatus::PAID,
            'pending' => PaymentStatus::PENDING,
            'deny', 'cancel', 'failure' => PaymentStatus::FAILED,
            'expire' => PaymentStatus::EXPIRED,
            default => PaymentStatus::FAILED,
        };

        $eventId = "midtrans-{$transactionId}-{$transactionStatus}";
        $grossAmount = isset($data['gross_amount']) ? (int) round((float) $data['gross_amount']) : null;
        $settlementTime = $data['settlement_time'] ?? ($data['transaction_time'] ?? null);
        $paidAt = ($status === PaymentStatus::PAID)
            ? ($settlementTime ? Carbon::parse($settlementTime) : now())
            : null;

        return new WebhookResult(
            eventId: $eventId,
            externalId: $orderId,
            status: $status,
            amount: $grossAmount,
            paidAt: $paidAt,
            paymentMethod: $data['payment_type'] ?? null,
            paymentChannel: $data['channel_response_message'] ?? null,
            gatewayReference: $transactionId,
            raw: $data
        );
    }

    public function fetchStatus(Payment $payment): WebhookResult
    {
        $serverKey = $this->getServerKey();
        $orderId = $payment->external_id;

        $response = Http::withBasicAuth($serverKey, '')
            ->timeout(10)
            ->get("{$this->getApiBaseUrl()}/{$orderId}/status");

        if ($response->failed()) {
            throw new RuntimeException('Gagal mengambil status transaksi Midtrans: '.$response->body());
        }

        $data = $response->json();
        $transactionStatus = $data['transaction_status'] ?? '';
        $fraudStatus = $data['fraud_status'] ?? 'accept';

        $status = match ($transactionStatus) {
            'capture' => ($fraudStatus === 'challenge') ? PaymentStatus::PENDING : PaymentStatus::PAID,
            'settlement' => PaymentStatus::PAID,
            'pending' => PaymentStatus::PENDING,
            'deny', 'cancel', 'failure' => PaymentStatus::FAILED,
            'expire' => PaymentStatus::EXPIRED,
            default => PaymentStatus::FAILED,
        };

        return new WebhookResult(
            eventId: "fetch-midtrans-{$payment->external_id}-{$transactionStatus}",
            externalId: $payment->external_id,
            status: $status,
            amount: isset($data['gross_amount']) ? (int) round((float) $data['gross_amount']) : (int) $payment->amount,
            paidAt: ($status === PaymentStatus::PAID) ? now() : null,
            paymentMethod: $data['payment_type'] ?? null,
            paymentChannel: $data['channel_response_message'] ?? null,
            gatewayReference: $data['transaction_id'] ?? null,
            raw: $data
        );
    }

    public function expire(Payment $payment): void
    {
        $serverKey = $this->getServerKey();
        $orderId = $payment->external_id;

        Http::withBasicAuth($serverKey, '')
            ->timeout(10)
            ->post("{$this->getApiBaseUrl()}/{$orderId}/expire");
    }

    public function testConnection(): bool
    {
        try {
            $serverKey = $this->getServerKey();
            // Test by making request with auth to sandbox or production API
            $response = Http::withBasicAuth($serverKey, '')
                ->timeout(5)
                ->get("{$this->getApiBaseUrl()}/ping");

            // Even 404 on ping means credentials / network reached Midtrans
            return in_array($response->status(), [200, 404]);
        } catch (\Throwable) {
            return false;
        }
    }
}
