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

class XenditGateway implements PaymentGateway
{
    protected string $baseUrl = 'https://api.xendit.co';

    public function key(): string
    {
        return 'xendit';
    }

    protected function getSecretKey(): string
    {
        $secretKey = setting('xendit_secret_key');
        if (empty($secretKey)) {
            throw new RuntimeException('Kredensial Xendit Secret Key belum dikonfigurasi di Pengaturan.');
        }

        return $secretKey;
    }

    protected function getCallbackToken(): string
    {
        return (string) setting('xendit_callback_token', '');
    }

    public function createCharge(Payment $payment, Booking $booking): ChargeResult
    {
        $secretKey = $this->getSecretKey();
        $buyer = $booking->user;
        $institution = $booking->institution;

        $durationSeconds = max(300, (int) now()->diffInSeconds($booking->expires_at, false));

        // Format items from booking items
        $items = [];
        foreach ($booking->items as $item) {
            $items[] = [
                'name' => $item->name,
                'quantity' => $item->quantity,
                'price' => (int) $item->unit_price,
                'category' => $item->type,
            ];
        }

        $payload = [
            'external_id' => $payment->external_id,
            'amount' => (int) $payment->amount,
            'description' => "Pembayaran Reservasi {$booking->booking_code} - {$booking->destination->name}",
            'invoice_duration' => $durationSeconds,
            'currency' => 'IDR',
            'customer' => [
                'given_names' => $buyer->name,
                'email' => $buyer->email,
                'mobile_number' => $buyer->phone,
            ],
            'customer_notification_preference' => [
                'invoice_created' => ['whatsapp', 'email'],
                'invoice_reminder' => ['whatsapp', 'email'],
                'invoice_paid' => ['whatsapp', 'email'],
            ],
            'success_redirect_url' => route('buyer.booking.show', $booking->booking_code),
            'failure_redirect_url' => route('buyer.booking.show', $booking->booking_code),
            'items' => $items,
        ];

        $response = Http::withBasicAuth($secretKey, '')
            ->timeout(15)
            ->post("{$this->baseUrl}/v2/invoices", $payload);

        if ($response->failed()) {
            throw new RuntimeException('Gagal membuat invoice Xendit: ' . $response->body());
        }

        $data = $response->json();

        return new ChargeResult(
            checkoutUrl: $data['invoice_url'],
            gatewayReference: $data['id'],
            expiresAt: isset($data['expiry_date']) ? Carbon::parse($data['expiry_date']) : null,
            raw: $data
        );
    }

    public function verifyWebhook(Request $request): bool
    {
        $token = $request->header('x-callback-token');
        $configuredToken = $this->getCallbackToken();

        if (empty($token) || empty($configuredToken)) {
            return false;
        }

        return hash_equals($configuredToken, $token);
    }

    public function parseWebhook(Request $request): WebhookResult
    {
        $data = $request->all();

        $xenditStatus = strtoupper($data['status'] ?? '');
        $status = match ($xenditStatus) {
            'PAID', 'SETTLED' => PaymentStatus::PAID,
            'EXPIRED' => PaymentStatus::EXPIRED,
            default => PaymentStatus::FAILED,
        };

        $id = $data['id'] ?? '';
        $eventId = "{$id}-{$xenditStatus}";
        $externalId = $data['external_id'] ?? '';
        $amount = isset($data['paid_amount']) ? (int) $data['paid_amount'] : (isset($data['amount']) ? (int) $data['amount'] : null);
        $paidAt = isset($data['paid_at']) ? Carbon::parse($data['paid_at']) : ($status === PaymentStatus::PAID ? now() : null);

        return new WebhookResult(
            eventId: $eventId,
            externalId: $externalId,
            status: $status,
            amount: $amount,
            paidAt: $paidAt,
            paymentMethod: $data['payment_method'] ?? null,
            paymentChannel: $data['payment_channel'] ?? null,
            gatewayReference: $id,
            raw: $data
        );
    }

    public function fetchStatus(Payment $payment): WebhookResult
    {
        $secretKey = $this->getSecretKey();
        $invoiceId = $payment->gateway_reference;

        if (! $invoiceId) {
            throw new RuntimeException('ID invoice Xendit tidak ditemukan pada data pembayaran.');
        }

        $response = Http::withBasicAuth($secretKey, '')
            ->timeout(10)
            ->get("{$this->baseUrl}/v2/invoices/{$invoiceId}");

        if ($response->failed()) {
            throw new RuntimeException('Gagal mengambil status invoice Xendit: ' . $response->body());
        }

        $data = $response->json();
        $xenditStatus = strtoupper($data['status'] ?? '');
        $status = match ($xenditStatus) {
            'PAID', 'SETTLED' => PaymentStatus::PAID,
            'EXPIRED' => PaymentStatus::EXPIRED,
            'PENDING' => PaymentStatus::PENDING,
            default => PaymentStatus::FAILED,
        };

        return new WebhookResult(
            eventId: "fetch-{$data['id']}-{$xenditStatus}",
            externalId: $payment->external_id,
            status: $status,
            amount: isset($data['paid_amount']) ? (int) $data['paid_amount'] : (int) ($data['amount'] ?? 0),
            paidAt: isset($data['paid_at']) ? Carbon::parse($data['paid_at']) : null,
            paymentMethod: $data['payment_method'] ?? null,
            paymentChannel: $data['payment_channel'] ?? null,
            gatewayReference: $data['id'],
            raw: $data
        );
    }

    public function expire(Payment $payment): void
    {
        $secretKey = $this->getSecretKey();
        $invoiceId = $payment->gateway_reference;

        if (! $invoiceId) {
            return;
        }

        Http::withBasicAuth($secretKey, '')
            ->timeout(10)
            ->post("{$this->baseUrl}/invoices/{$invoiceId}/expire!");
    }

    public function testConnection(): bool
    {
        try {
            $secretKey = $this->getSecretKey();
            $response = Http::withBasicAuth($secretKey, '')
                ->timeout(5)
                ->get("{$this->baseUrl}/balance");

            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }
}
