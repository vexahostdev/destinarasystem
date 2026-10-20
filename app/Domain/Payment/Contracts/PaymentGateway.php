<?php

namespace App\Domain\Payment\Contracts;

use App\Domain\Payment\Data\ChargeResult;
use App\Domain\Payment\Data\WebhookResult;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Request;

interface PaymentGateway
{
    /**
     * Gateway identifier (e.g. 'xendit', 'midtrans', 'manual_transfer').
     */
    public function key(): string;

    /**
     * Create payment charge/invoice.
     */
    public function createCharge(Payment $payment, Booking $booking): ChargeResult;

    /**
     * Verify incoming webhook request signature/token.
     */
    public function verifyWebhook(Request $request): bool;

    /**
     * Parse webhook payload into standardized result.
     */
    public function parseWebhook(Request $request): WebhookResult;

    /**
     * Actively query gateway for latest payment status.
     */
    public function fetchStatus(Payment $payment): WebhookResult;

    /**
     * Expire/cancel charge on gateway.
     */
    public function expire(Payment $payment): void;

    /**
     * Test gateway connectivity and credentials.
     */
    public function testConnection(): bool;
}
